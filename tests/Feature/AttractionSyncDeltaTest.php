<?php

namespace Tests\Feature;

use App\Models\Attraction;
use App\Services\AttractionSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AttractionSyncDeltaTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://wp.test/wp-json/attraction-manager/v1';

    protected function setUp(): void
    {
        parent::setUp();

        config(['wordpress.de' => ['api_url' => self::API, 'username' => 'admin', 'password' => 'secret']]);
    }

    private function syncedAttraction(): Attraction
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->create();
        $attraction->generateWidgets();
        $attraction->wp_de_id = 77;
        $attraction->wp_de_last_sync = now();
        $attraction->save();

        Carbon::setTestNow('2026-10-12 12:00:00');

        return $attraction->fresh();
    }

    public function test_create_sends_all_widgets_without_changed_at(): void
    {
        Http::fake(['wp.test/*' => Http::response(['id' => 77], 201)]);
        $attraction = Attraction::factory()->create();
        $attraction->generateWidgets();
        $attraction->save();

        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction, 'de'));

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && isset($request['widget_opening_hours'], $request['json_ld'])
                && ! isset($request['widget_opening_hours_changed_at']);
        });
    }

    public function test_unchanged_attraction_is_not_sent(): void
    {
        Http::fake();
        $attraction = $this->syncedAttraction();

        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction, 'de'));

        Http::assertNothingSent();
    }

    public function test_changed_widget_is_sent_with_changed_at_and_others_are_omitted(): void
    {
        Http::fake([
            'wp.test/*/attractions/77' => Http::sequence()
                ->push(['id' => 77], 200)
                ->push(['id' => 77], 200),
        ]);
        $attraction = $this->syncedAttraction();
        $attraction->opening_hours_html = '<div>neue Zeiten</div>';
        $attraction->opening_hours_changed_at = Carbon::parse('2026-10-12 11:30:00');
        $attraction->save();

        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction, 'de'));

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'PUT'
                && $request['widget_opening_hours'] === '<div>neue Zeiten</div>'
                && $request['widget_opening_hours_changed_at'] === '2026-10-12T11:30:00+00:00'
                && ! isset($request['widget_contact'])
                && ! isset($request['widget_contact_changed_at'])
                && isset($request['name']);
        });
    }

    public function test_sync_does_not_bump_updated_at(): void
    {
        Http::fake(['wp.test/*/attractions/77' => Http::response(['id' => 77], 200)]);
        $attraction = $this->syncedAttraction();
        $attraction->name = 'Umbenannt';
        $attraction->save();
        $updatedAt = $attraction->fresh()->updated_at;

        Carbon::setTestNow('2026-10-12 13:00:00');
        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction->fresh(), 'de'));

        $fresh = $attraction->fresh();
        $this->assertTrue($fresh->updated_at->equalTo($updatedAt));
        $this->assertTrue($fresh->wp_de_last_sync->equalTo(Carbon::parse('2026-10-12 13:00:00')));
        $this->assertFalse((new AttractionSyncService)->needsSync($fresh, 'de'));
    }

    public function test_rename_is_synced_without_any_changed_at(): void
    {
        Http::fake(['wp.test/*/attractions/77' => Http::response(['id' => 77], 200)]);
        $attraction = $this->syncedAttraction();
        $attraction->name = 'Neuer Name';
        $attraction->save();

        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction->fresh(), 'de'));

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'PUT'
                && $request['name'] === 'Neuer Name'
                && ! isset($request['widget_opening_hours'])
                && ! isset($request['widget_opening_hours_changed_at']);
        });
    }

    public function test_legacy_rows_without_changed_at_never_send_changed_at(): void
    {
        Http::fake(['wp.test/*/attractions/77' => Http::response(['id' => 77], 200)]);
        $attraction = $this->syncedAttraction();
        foreach (Attraction::widgetMap('de') as $definition) {
            $attraction->{$definition['changed_at']} = null;
        }
        $attraction->name = 'Neuer Name';
        $attraction->save();

        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction->fresh(), 'de', force: true));

        Http::assertSent(fn (Request $request): bool => isset($request['widget_contact'])
            && ! isset($request['widget_contact_changed_at'])
            && ! isset($request['widget_opening_hours_changed_at']));
    }

    public function test_force_sends_everything_without_changed_at(): void
    {
        Http::fake(['wp.test/*/attractions/77' => Http::response(['id' => 77], 200)]);
        $attraction = $this->syncedAttraction();

        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction, 'de', force: true));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && isset($request['widget_contact'], $request['widget_rating'], $request['widget_opening_hours'], $request['widget_accessibility'], $request['json_ld'])
            && ! isset($request['widget_contact_changed_at']));
    }

    public function test_failed_request_keeps_last_sync(): void
    {
        Http::fake(['wp.test/*/attractions/77' => Http::sequence()
            ->push(['id' => 77], 200)
            ->push('error', 500),
        ]);
        $attraction = $this->syncedAttraction();
        $before = $attraction->wp_de_last_sync;
        $attraction->name = 'Neuer Name';
        $attraction->save();

        $this->assertFalse((new AttractionSyncService)->syncAttraction($attraction->fresh(), 'de'));

        $this->assertTrue($attraction->fresh()->wp_de_last_sync->equalTo($before));
        $this->assertTrue((new AttractionSyncService)->needsSync($attraction->fresh(), 'de'));
    }

    public function test_command_force_option_sends_unchanged_attractions(): void
    {
        Http::fake(['wp.test/*/attractions/77' => Http::response(['id' => 77], 200)]);
        $this->syncedAttraction();

        $this->artisan('attractions:sync-wordpress', ['--language' => 'de'])->assertSuccessful();
        Http::assertNothingSent();

        $this->artisan('attractions:sync-wordpress', ['--language' => 'de', '--force' => true])->assertSuccessful();
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT');
    }
}
