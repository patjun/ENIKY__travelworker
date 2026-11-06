<?php

namespace Tests\Feature;

use App\Filament\Resources\AttractionResource\Pages\EditAttraction;
use App\Filament\Resources\AttractionResource\Pages\ListAttractions;
use App\Jobs\SyncAttractionsToWordPressJob;
use App\Models\Attraction;
use App\Services\AttractionSyncReport;
use App\Services\AttractionSyncService;
use Filament\Facades\Filament;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class AttractionInstantSyncTest extends TestCase
{
    use RefreshDatabase;

    private const API_DE = 'https://wp-de.test/wp-json/attraction-manager/v1';

    private const API_EN = 'https://wp-en.test/wp-json/attraction-manager/v1';

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        config([
            'wordpress.de' => ['api_url' => self::API_DE, 'username' => 'admin', 'password' => 'secret'],
            'wordpress.en' => ['api_url' => self::API_EN, 'username' => 'admin', 'password' => 'secret'],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function syncedAttraction(array $attributes = []): Attraction
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->create($attributes);
        $attraction->generateWidgets();
        $attraction->wp_de_id = 77;
        $attraction->wp_de_last_sync = now();
        $attraction->wp_en_id = 88;
        $attraction->wp_en_last_sync = now();
        $attraction->save();

        Carbon::setTestNow('2026-10-12 12:00:00');

        return $attraction->fresh();
    }

    private function fakeWordPress(): void
    {
        Http::fake(['wp-*.test/*' => Http::response(['id' => 77])]);
    }

    private function changeName(Attraction $attraction, string $name): void
    {
        $attraction->update(['name' => $name, 'en_name' => $name]);
        $attraction->generateWidgets();
        $attraction->save();
    }

    /**
     * @return array<int, Request>
     */
    private function sentUpdates(): array
    {
        return Http::recorded(fn (Request $request): bool => $request->method() === 'PUT')
            ->map(fn (array $pair): Request => $pair[0])
            ->values()
            ->all();
    }

    // ===== Report =====

    public function test_report_summarizes_counts_per_language(): void
    {
        $report = new AttractionSyncReport;

        $this->assertSame('0 synced', $report->summary());

        $report->add('de', AttractionSyncReport::SYNCED, 2);
        $report->add('en', AttractionSyncReport::UNCHANGED);
        $report->addFailure('Burg', 'en', 'HTTP 500');

        $this->assertSame("de: 2 synced\nen: 1 unchanged, 1 failed\nBurg (en): HTTP 500", $report->summary());
        $this->assertSame(2, $report->total(AttractionSyncReport::SYNCED));
        $this->assertTrue($report->hasProblems());
    }

    public function test_report_shortens_long_failure_lists(): void
    {
        $report = new AttractionSyncReport;

        foreach (range(1, 8) as $number) {
            $report->addFailure("Burg {$number}", 'de', 'HTTP 500');
        }

        $this->assertStringContainsString('Burg 5 (de)', $report->summary());
        $this->assertStringNotContainsString('Burg 6 (de)', $report->summary());
        $this->assertStringContainsString('and 3 more', $report->summary());
    }

    public function test_report_names_login_errors_and_skipped_attractions(): void
    {
        $report = new AttractionSyncReport;
        $report->addLoginFailure('de', 'HTTP 401 rest_forbidden');
        $report->add('en', AttractionSyncReport::SKIPPED, 4);

        $this->assertTrue($report->hasLoginFailed('de'));
        $this->assertFalse($report->hasLoginFailed('en'));
        $this->assertFalse($report->allLanguagesFailedLogin());
        $this->assertStringContainsString('4 skipped', $report->summary());
        $this->assertStringContainsString('Login de failed: HTTP 401 rest_forbidden', $report->summary());

        $report->addLoginFailure('en', 'HTTP 401 rest_forbidden');
        $this->assertTrue($report->allLanguagesFailedLogin());
    }

    // ===== Transfer like the nightly sync =====

    public function test_changed_attraction_is_sent_to_both_languages_with_change_dates(): void
    {
        $this->fakeWordPress();
        $attraction = $this->syncedAttraction();
        $this->changeName($attraction, 'Neuer Name');
        Carbon::setTestNow('2026-10-12 12:05:00');

        $report = new AttractionSyncReport;
        (new AttractionSyncService)->syncToAllLanguages($attraction, $report);

        $this->assertSame(2, $report->total(AttractionSyncReport::SYNCED));
        $this->assertCount(2, $this->sentUpdates());

        foreach ($this->sentUpdates() as $request) {
            $this->assertArrayHasKey('widget_contact', $request->data());
            $this->assertArrayHasKey('widget_contact_changed_at', $request->data());
        }

        $this->assertTrue($attraction->fresh()->wp_de_last_sync->equalTo('2026-10-12 12:05:00'));
        $this->assertTrue($attraction->fresh()->wp_en_last_sync->equalTo('2026-10-12 12:05:00'));
    }

    public function test_unchanged_attraction_counts_as_unchanged_and_sends_nothing(): void
    {
        Http::fake();
        $attraction = $this->syncedAttraction();

        $report = new AttractionSyncReport;
        (new AttractionSyncService)->syncToAllLanguages($attraction, $report);

        $this->assertSame(2, $report->total(AttractionSyncReport::UNCHANGED));
        $this->assertSame(0, $report->total(AttractionSyncReport::SYNCED));
        Http::assertNothingSent();
    }

    public function test_failure_in_one_language_keeps_the_other_language_in_the_report(): void
    {
        Http::fake([
            'wp-de.test/*' => Http::response(['code' => 'server_error'], 500),
            'wp-en.test/*' => Http::response(['id' => 88]),
        ]);
        $attraction = $this->syncedAttraction();
        $this->changeName($attraction, 'Neuer Name');
        Carbon::setTestNow('2026-10-12 12:05:00');

        $report = new AttractionSyncReport;
        (new AttractionSyncService)->syncToAllLanguages($attraction, $report);

        $this->assertSame(1, $report->total(AttractionSyncReport::SYNCED));
        $this->assertStringContainsString('en: 1 synced', $report->summary());
        $this->assertStringContainsString('de: 1 failed', $report->summary());
        $this->assertCount(1, $report->failures);
        $this->assertSame('de', $report->failures[0]['language']);
        $this->assertTrue($attraction->fresh()->wp_en_last_sync->equalTo('2026-10-12 12:05:00'));
    }

    public function test_login_error_skips_that_language_for_following_attractions(): void
    {
        Http::fake([
            'wp-de.test/*' => Http::response(['code' => 'rest_forbidden'], 401),
            'wp-en.test/*' => Http::response(['id' => 88]),
        ]);
        $first = $this->syncedAttraction();
        $second = $this->syncedAttraction();
        $this->changeName($first, 'Erste');
        $this->changeName($second, 'Zweite');

        $report = new AttractionSyncReport;
        $service = new AttractionSyncService;
        $service->syncToAllLanguages($first, $report);
        $service->syncToAllLanguages($second, $report);

        $this->assertTrue($report->hasLoginFailed('de'));
        $this->assertSame(1, $report->total(AttractionSyncReport::SKIPPED));
        $this->assertSame(2, $report->total(AttractionSyncReport::SYNCED));
        $this->assertCount(1, $report->failures);
        Http::assertSentCount(5);
    }

    // ===== Renew all widgets =====

    public function test_refresh_sends_all_widgets_and_clears_the_cache_without_dating_posts(): void
    {
        $this->fakeWordPress();
        $attraction = $this->syncedAttraction();
        $attraction->updateQuietly(['contact_info_html' => '<div class="old">old</div>']);
        Carbon::setTestNow('2026-10-12 12:05:00');

        $report = new AttractionSyncReport;
        (new AttractionSyncService)->syncToAllLanguages($attraction->fresh(), $report, refreshWidgets: true);

        $this->assertSame(2, $report->total(AttractionSyncReport::SYNCED));
        $this->assertStringNotContainsString('old', $attraction->fresh()->contact_info_html);

        $updates = $this->sentUpdates();
        $this->assertCount(2, $updates);

        foreach ($updates as $request) {
            $this->assertArrayHasKey('widget_contact', $request->data());
            $this->assertArrayHasKey('widget_opening_hours', $request->data());
            $this->assertArrayHasKey('json_ld', $request->data());
            $this->assertArrayHasKey('json_ld_changed_at', $request->data());
            $this->assertArrayNotHasKey('widget_contact_changed_at', $request->data());
            $this->assertArrayNotHasKey('widget_opening_hours_changed_at', $request->data());
        }
    }

    public function test_refresh_does_not_stamp_changes_so_the_nightly_sync_dates_nothing(): void
    {
        $this->fakeWordPress();
        $attraction = $this->syncedAttraction();
        $attraction->updateQuietly(['contact_info_html' => '<div class="old">old</div>']);
        $changedAtBefore = $attraction->fresh()->contact_info_changed_at;
        Carbon::setTestNow('2026-10-12 12:05:00');

        (new AttractionSyncService)->syncToAllLanguages($attraction->fresh(), new AttractionSyncReport, refreshWidgets: true);
        $this->assertTrue($attraction->fresh()->contact_info_changed_at->equalTo($changedAtBefore));

        Carbon::setTestNow('2026-10-13 02:00:00');
        $this->assertFalse((new AttractionSyncService)->needsSync($attraction->fresh(), 'de'));
    }

    public function test_refresh_keeps_the_last_modified_date(): void
    {
        $this->fakeWordPress();
        $attraction = $this->syncedAttraction();
        $attraction->updateQuietly(['contact_info_html' => '<div class="old">old</div>']);
        $updatedAtBefore = $attraction->fresh()->updated_at;
        Carbon::setTestNow('2026-10-12 12:05:00');

        (new AttractionSyncService)->syncToAllLanguages($attraction->fresh(), new AttractionSyncReport, refreshWidgets: true);

        $this->assertStringNotContainsString('class="old"', $attraction->fresh()->contact_info_html);
        $this->assertTrue($attraction->fresh()->updated_at->equalTo($updatedAtBefore));
    }

    public function test_failed_refresh_keeps_the_last_modified_date(): void
    {
        Http::fake(['wp-*.test/*' => Http::response(['code' => 'server_error'], 500)]);
        $attraction = $this->syncedAttraction();
        $attraction->updateQuietly(['contact_info_html' => '<div class="old">old</div>']);
        $updatedAtBefore = $attraction->fresh()->updated_at;
        Carbon::setTestNow('2026-10-12 12:05:00');

        (new AttractionSyncService)->syncToAllLanguages($attraction->fresh(), new AttractionSyncReport, refreshWidgets: true);

        $this->assertStringNotContainsString('class="old"', $attraction->fresh()->contact_info_html);
        $this->assertTrue($attraction->fresh()->updated_at->equalTo($updatedAtBefore));
    }

    public function test_failed_refresh_does_not_date_posts_in_the_nightly_sync_and_can_be_repeated(): void
    {
        Http::fake(['wp-*.test/*' => Http::response(['code' => 'server_error'], 500)]);
        $attraction = $this->syncedAttraction();
        $attraction->updateQuietly(['contact_info_html' => '<div class="old">old</div>']);
        Carbon::setTestNow('2026-10-12 12:05:00');

        $report = new AttractionSyncReport;
        (new AttractionSyncService)->syncToAllLanguages($attraction->fresh(), $report, refreshWidgets: true);

        $this->assertCount(2, $report->failures);
        $this->assertTrue($attraction->fresh()->contact_info_changed_at->lessThan('2026-10-12 12:00:00'));

        Http::swap(new Factory);
        $this->fakeWordPress();
        Carbon::setTestNow('2026-10-12 12:10:00');

        $retryReport = new AttractionSyncReport;
        (new AttractionSyncService)->syncToAllLanguages($attraction->fresh(), $retryReport, refreshWidgets: true);

        $this->assertSame(2, $retryReport->total(AttractionSyncReport::SYNCED));
        $this->assertCount(2, $this->sentUpdates());
        $this->assertArrayHasKey('widget_contact', $this->sentUpdates()[0]->data());
        $this->assertArrayNotHasKey('widget_contact_changed_at', $this->sentUpdates()[0]->data());
    }

    public function test_refresh_keeps_a_real_pending_change_datable(): void
    {
        $this->fakeWordPress();
        $attraction = $this->syncedAttraction();
        $this->changeName($attraction, 'Neuer Name');
        Carbon::setTestNow('2026-10-12 12:05:00');

        (new AttractionSyncService)->syncToAllLanguages($attraction->fresh(), new AttractionSyncReport, refreshWidgets: true);

        $update = $this->sentUpdates()[0]->data();
        $this->assertSame('2026-10-12T12:00:00+00:00', $update['widget_contact_changed_at']);
        $this->assertArrayNotHasKey('widget_rating_changed_at', $update);
    }

    public function test_refresh_of_an_attraction_missing_in_wordpress_creates_it_without_timestamps(): void
    {
        Http::fake(['wp-*.test/*' => Http::response(['id' => 99], 201)]);
        $attraction = Attraction::factory()->create();

        $report = new AttractionSyncReport;
        (new AttractionSyncService)->syncToAllLanguages($attraction, $report, refreshWidgets: true);

        $this->assertSame(2, $report->total(AttractionSyncReport::SYNCED));
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && ! isset($request['json_ld_changed_at']));
    }

    public function test_regeneration_keeps_change_dates_of_both_languages(): void
    {
        $attraction = $this->syncedAttraction();
        $attraction->updateQuietly(['en_rating_html' => 'old', 'structured_data' => 'old']);
        $attraction = $attraction->fresh();
        $ratingChangedAt = $attraction->en_rating_changed_at;
        $jsonLdChangedAt = $attraction->structured_data_changed_at;

        $attraction->regenerateWidgetsKeepingChangeDates();

        $this->assertNotSame('old', $attraction->structured_data);
        $this->assertEquals($ratingChangedAt, $attraction->en_rating_changed_at);
        $this->assertEquals($jsonLdChangedAt, $attraction->structured_data_changed_at);
    }

    // ===== Background job =====

    public function test_job_reports_the_result_to_the_user(): void
    {
        $this->fakeWordPress();
        $user = $this->createUserWithRole('admin');
        $changed = $this->syncedAttraction();
        $this->changeName($changed, 'Neuer Name');
        $unchanged = $this->syncedAttraction();

        (new SyncAttractionsToWordPressJob([$changed->id, $unchanged->id], $user->id))->handle(new AttractionSyncService);

        $notification = $user->notifications()->sole();
        $this->assertSame(DatabaseNotification::class, $notification->type);
        $this->assertSame('Sync finished', $notification->data['title']);
        $this->assertSame("de: 1 synced, 1 unchanged<br>\nen: 1 synced, 1 unchanged", $notification->data['body']);
        $this->assertSame('success', $notification->data['status']);
    }

    public function test_job_marks_problems_in_the_notification(): void
    {
        Http::fake(['wp-*.test/*' => Http::response(['code' => 'server_error'], 500)]);
        $user = $this->createUserWithRole('admin');
        $attraction = $this->syncedAttraction();
        $this->changeName($attraction, 'Neuer Name');

        (new SyncAttractionsToWordPressJob([$attraction->id], $user->id))->handle(new AttractionSyncService);

        $notification = $user->notifications()->sole();
        $this->assertSame('warning', $notification->data['status']);
        $this->assertStringContainsString('de: 1 failed', $notification->data['body']);
        $this->assertStringContainsString('en: 1 failed', $notification->data['body']);
    }

    public function test_job_continues_itself_when_its_time_is_up(): void
    {
        Queue::fake();
        $user = $this->createUserWithRole('admin');
        $first = $this->syncedAttraction();
        $second = $this->syncedAttraction();
        $this->changeName($first, 'Neuer Name');

        Http::fake(function (Request $request) {
            Carbon::setTestNow(now()->addMinute());

            return Http::response(['id' => 77]);
        });

        (new SyncAttractionsToWordPressJob([$first->id, $second->id], $user->id))->handle(new AttractionSyncService);

        Queue::assertPushed(
            SyncAttractionsToWordPressJob::class,
            fn (SyncAttractionsToWordPressJob $job): bool => $job->attractionIds === [$second->id]
                && $job->userId === $user->id
                && $job->report->total(AttractionSyncReport::SYNCED) === 2
        );
        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_job_stops_when_login_fails_in_every_language(): void
    {
        Http::fake(['wp-*.test/*' => Http::response(['code' => 'rest_forbidden'], 401)]);
        $user = $this->createUserWithRole('admin');
        $attractions = collect(range(1, 3))->map(function (): Attraction {
            $attraction = $this->syncedAttraction();
            $this->changeName($attraction, 'Neuer Name');

            return $attraction;
        });

        (new SyncAttractionsToWordPressJob($attractions->pluck('id')->all(), $user->id))->handle(new AttractionSyncService);

        Http::assertSentCount(2);
        $body = $user->notifications()->sole()->data['body'];
        $this->assertStringContainsString('2 skipped', $body);
        $this->assertStringContainsString('Login de failed', $body);
        $this->assertStringContainsString('Login en failed', $body);
    }

    public function test_job_ignores_attractions_deleted_in_the_meantime(): void
    {
        Http::fake();
        $user = $this->createUserWithRole('admin');
        $attraction = $this->syncedAttraction();
        $attraction->delete();

        (new SyncAttractionsToWordPressJob([$attraction->id], $user->id))->handle(new AttractionSyncService);

        Http::assertNothingSent();
        $this->assertSame('0 synced', $user->notifications()->sole()->data['body']);
    }

    public function test_job_keeps_its_progress_through_the_queue(): void
    {
        $report = new AttractionSyncReport;
        $report->add('de', AttractionSyncReport::SYNCED, 3);
        $report->addFailure('Burg', 'de', 'HTTP 500');

        $job = unserialize(serialize(new SyncAttractionsToWordPressJob([1, 2], 7, true, $report)));

        $this->assertSame([1, 2], $job->attractionIds);
        $this->assertSame(7, $job->userId);
        $this->assertTrue($job->refreshWidgets);
        $this->assertSame(3, $job->report->total(AttractionSyncReport::SYNCED));
        $this->assertSame('Burg', $job->report->failures[0]['name']);
    }

    public function test_failed_job_tells_the_user(): void
    {
        $user = $this->createUserWithRole('admin');
        $report = new AttractionSyncReport;
        $report->add('de', AttractionSyncReport::SYNCED, 3);

        (new SyncAttractionsToWordPressJob([1], $user->id, report: $report))->failed(new \RuntimeException('Worker lost'));

        $notification = $user->notifications()->sole();
        $this->assertSame('Sync aborted', $notification->data['title']);
        $this->assertSame('danger', $notification->data['status']);
        $this->assertStringContainsString('de: 3 synced', $notification->data['body']);
        $this->assertStringContainsString('Worker lost', $notification->data['body']);
    }

    // ===== Buttons =====

    public function test_edit_page_button_transfers_at_once_and_shows_the_result(): void
    {
        $this->fakeWordPress();
        $this->actingAs($this->createUserWithRole('admin'));
        $attraction = $this->syncedAttraction();
        $this->changeName($attraction, 'Neuer Name');

        Livewire::test(EditAttraction::class, ['record' => $attraction->id])
            ->callAction('syncNow')
            ->assertNotified('Synced');

        $this->assertCount(2, $this->sentUpdates());
    }

    public function test_button_reports_when_there_is_nothing_new(): void
    {
        Http::fake();
        $this->actingAs($this->createUserWithRole('admin'));
        $attraction = $this->syncedAttraction();

        Livewire::test(EditAttraction::class, ['record' => $attraction->id])
            ->callAction('syncNow')
            ->assertNotified('No changes');

        Http::assertNothingSent();
    }

    public function test_button_shows_the_failure_reason(): void
    {
        Http::fake(['wp-*.test/*' => Http::response(['code' => 'rest_forbidden'], 401)]);
        $this->actingAs($this->createUserWithRole('admin'));
        $attraction = $this->syncedAttraction();
        $this->changeName($attraction, 'Neuer Name');

        Livewire::test(ListAttractions::class)
            ->callTableAction('syncNow', $attraction)
            ->assertNotified('Sync failed');
    }

    public function test_button_reports_a_partial_transfer(): void
    {
        Http::fake([
            'wp-de.test/*' => Http::response(['id' => 77]),
            'wp-en.test/*' => Http::response(['code' => 'rest_forbidden'], 401),
        ]);
        $this->actingAs($this->createUserWithRole('admin'));
        $attraction = $this->syncedAttraction();
        $this->changeName($attraction, 'Neuer Name');

        Livewire::test(EditAttraction::class, ['record' => $attraction->id])
            ->callAction('syncNow')
            ->assertNotified('Partially synced');
    }

    public function test_job_keeps_the_german_count_when_english_login_fails(): void
    {
        Http::fake([
            'wp-de.test/*' => Http::response(['id' => 77]),
            'wp-en.test/*' => Http::response(['code' => 'rest_forbidden'], 401),
        ]);
        $user = $this->createUserWithRole('admin');
        $ids = collect(range(1, 3))->map(fn (): int => $this->syncedAttraction()->id)->all();

        (new SyncAttractionsToWordPressJob($ids, $user->id, refreshWidgets: true))->handle(new AttractionSyncService);

        $body = $user->notifications()->sole()->data['body'];
        $this->assertStringContainsString('de: 3 synced', $body);
        $this->assertStringContainsString('en: 1 failed, 2 skipped', $body);
    }

    public function test_table_row_button_transfers_at_once(): void
    {
        $this->fakeWordPress();
        $this->actingAs($this->createUserWithRole('admin'));
        $attraction = $this->syncedAttraction();
        $this->changeName($attraction, 'Neuer Name');

        Livewire::test(ListAttractions::class)->callTableAction('syncNow', $attraction);

        $this->assertCount(2, $this->sentUpdates());
    }

    public function test_bulk_action_queues_the_selected_attractions(): void
    {
        Queue::fake();
        $user = $this->createUserWithRole('admin');
        $this->actingAs($user);
        $selected = Attraction::factory()->count(2)->create();
        Attraction::factory()->create();

        Livewire::test(ListAttractions::class)
            ->callTableBulkAction('syncSelected', $selected)
            ->assertNotified('Sync started');

        Queue::assertPushed(
            SyncAttractionsToWordPressJob::class,
            fn (SyncAttractionsToWordPressJob $job): bool => collect($job->attractionIds)->sort()->values()->all() === $selected->pluck('id')->all()
                && $job->userId === $user->id
                && $job->refreshWidgets === false
        );
    }

    public function test_sync_all_queues_every_active_attraction(): void
    {
        Queue::fake();
        $this->actingAs($this->createUserWithRole('admin'));
        $active = Attraction::factory()->count(2)->create();
        Attraction::factory()->create()->delete();

        Livewire::test(ListAttractions::class)->callAction('syncAll');

        Queue::assertPushed(
            SyncAttractionsToWordPressJob::class,
            fn (SyncAttractionsToWordPressJob $job): bool => $job->attractionIds === $active->pluck('id')->all()
                && $job->refreshWidgets === false
        );
    }

    public function test_refresh_all_queues_with_regeneration(): void
    {
        Queue::fake();
        $this->actingAs($this->createUserWithRole('admin'));
        Attraction::factory()->create();

        Livewire::test(ListAttractions::class)
            ->callAction('refreshAllWidgets')
            ->assertNotified('Renewing widgets');

        Queue::assertPushed(
            SyncAttractionsToWordPressJob::class,
            fn (SyncAttractionsToWordPressJob $job): bool => $job->refreshWidgets === true
        );
    }

    public function test_renew_job_reports_its_result_as_renewal(): void
    {
        $this->fakeWordPress();
        $user = $this->createUserWithRole('admin');
        $attraction = $this->syncedAttraction();

        (new SyncAttractionsToWordPressJob([$attraction->id], $user->id, refreshWidgets: true))->handle(new AttractionSyncService);

        $this->assertSame('Widgets renewed', $user->notifications()->sole()->data['title']);
    }

    public function test_failed_renew_job_reports_an_aborted_renewal(): void
    {
        $user = $this->createUserWithRole('admin');

        (new SyncAttractionsToWordPressJob([1], $user->id, refreshWidgets: true))->failed(new \RuntimeException('Worker lost'));

        $this->assertSame('Widget renewal aborted', $user->notifications()->sole()->data['title']);
    }

    public function test_sync_all_needs_confirmation(): void
    {
        $this->actingAs($this->createUserWithRole('admin'));

        Livewire::test(ListAttractions::class)
            ->assertActionExists('syncAll', fn ($action): bool => $action->isConfirmationRequired())
            ->assertActionExists('refreshAllWidgets', fn ($action): bool => $action->isConfirmationRequired());
    }

    public function test_sync_all_warns_without_attractions(): void
    {
        Queue::fake();
        $this->actingAs($this->createUserWithRole('admin'));

        Livewire::test(ListAttractions::class)
            ->callAction('syncAll')
            ->assertNotified('No attractions found');

        Queue::assertNothingPushed();
    }

    public function test_editors_can_transfer_but_not_run_the_all_actions(): void
    {
        $this->actingAs($this->createUserWithRole('editor'));
        $attraction = Attraction::factory()->create();

        Livewire::test(ListAttractions::class)
            ->assertTableActionVisible('syncNow', $attraction)
            ->assertActionHidden('syncAll')
            ->assertActionHidden('refreshAllWidgets');
    }

    public function test_authors_cannot_transfer(): void
    {
        $this->actingAs($this->createUserWithRole('attractions-author'));
        $attraction = Attraction::factory()->create();

        Livewire::test(ListAttractions::class)
            ->assertTableActionHidden('syncNow', $attraction)
            ->assertActionHidden('syncAll');
    }

    public function test_panel_shows_database_notifications(): void
    {
        $this->assertTrue(Filament::getPanel('admin')->hasDatabaseNotifications());
    }
}
