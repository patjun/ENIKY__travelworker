<?php

namespace Tests\Feature;

use App\Models\Attraction;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AttractionSeasonalStructuredDataTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    public static function seasonOccurrenceProvider(): array
    {
        return [
            'before the season' => ['2026-03-15', '04-01', '10-31', '2026-04-01', '2026-10-31'],
            'first day of the season' => ['2026-04-01', '04-01', '10-31', '2026-04-01', '2026-10-31'],
            'during the season' => ['2026-07-01', '04-01', '10-31', '2026-04-01', '2026-10-31'],
            'last day of the season' => ['2026-10-31', '04-01', '10-31', '2026-04-01', '2026-10-31'],
            'day after the season' => ['2026-11-01', '04-01', '10-31', '2027-04-01', '2027-10-31'],
            'full year season' => ['2026-01-01', '01-01', '12-31', '2026-01-01', '2026-12-31'],
            'winter before the turn of the year' => ['2026-12-15', '11-01', '03-31', '2026-11-01', '2027-03-31'],
            'winter after the turn of the year' => ['2027-02-01', '11-01', '03-31', '2026-11-01', '2027-03-31'],
            'last day of the winter' => ['2027-03-31', '11-01', '03-31', '2026-11-01', '2027-03-31'],
            'day after the winter' => ['2027-04-01', '11-01', '03-31', '2027-11-01', '2028-03-31'],
            'leap day in a common year' => ['2026-01-10', '02-01', '02-29', '2026-02-01', '2026-02-28'],
            'leap day season still running on the shortened end' => ['2026-02-28', '02-01', '02-29', '2026-02-01', '2026-02-28'],
            'leap day in the next leap year' => ['2027-03-01', '02-01', '02-29', '2028-02-01', '2028-02-29'],
            'leap day in a leap year' => ['2028-01-10', '02-01', '02-29', '2028-02-01', '2028-02-29'],
        ];
    }

    #[DataProvider('seasonOccurrenceProvider')]
    public function test_season_is_output_as_its_running_or_next_occurrence(
        string $today,
        string $startDate,
        string $endDate,
        string $expectedFrom,
        string $expectedThrough,
    ): void {
        Carbon::setTestNow($today.' 12:00:00');

        $attraction = $this->attractionWithSeasons([$this->season($startDate, $endDate)]);
        $attraction->generateWidgets();

        foreach ([$attraction->structured_data, $attraction->en_structured_data] as $structuredData) {
            $specification = $this->openingHoursSpecifications($structuredData)[0];

            $this->assertSame($expectedFrom, $specification['validFrom']);
            $this->assertSame($expectedThrough, $specification['validThrough']);
        }
    }

    public function test_year_round_season_has_no_validity_dates(): void
    {
        $attraction = $this->attractionWithSeasons([
            ['name' => null, 'is_year_round' => true, 'start_date' => null, 'end_date' => null, 'time_slots' => $this->timeSlots()],
        ]);
        $attraction->generateWidgets();

        $specification = $this->openingHoursSpecifications($attraction->structured_data)[0];

        $this->assertArrayNotHasKey('validFrom', $specification);
        $this->assertArrayNotHasKey('validThrough', $specification);
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function incompleteSeasonProvider(): array
    {
        return [
            'missing start' => [null, '10-31'],
            'missing end' => ['04-01', null],
            'impossible month' => ['13-01', '10-31'],
            'impossible day' => ['04-01', '04-31'],
            'unknown format' => ['1. April', '10-31'],
            'legacy year-less format' => ['--04-01', '--10-31'],
        ];
    }

    #[DataProvider('incompleteSeasonProvider')]
    public function test_season_without_two_usable_dates_outputs_no_validity_dates(?string $startDate, ?string $endDate): void
    {
        $attraction = $this->attractionWithSeasons([$this->season($startDate, $endDate)]);
        $attraction->generateWidgets();

        $specification = $this->openingHoursSpecifications($attraction->structured_data)[0];

        $this->assertArrayNotHasKey('validFrom', $specification);
        $this->assertArrayNotHasKey('validThrough', $specification);
        $this->assertSame('09:00', $specification['opens']);
    }

    public function test_multiple_seasons_each_get_their_own_occurrence(): void
    {
        Carbon::setTestNow('2026-11-01 12:00:00');

        $attraction = $this->attractionWithSeasons([
            $this->season('01-01', '04-30'),
            $this->season('05-01', '12-31'),
        ]);
        $attraction->generateWidgets();

        $specifications = $this->openingHoursSpecifications($attraction->structured_data);

        $this->assertSame('2027-01-01', $specifications[0]['validFrom']);
        $this->assertSame('2027-04-30', $specifications[0]['validThrough']);
        $this->assertSame('2026-05-01', $specifications[1]['validFrom']);
        $this->assertSame('2026-12-31', $specifications[1]['validThrough']);
    }

    public function test_command_moves_a_finished_season_to_its_next_occurrence(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');
        $attraction = $this->savedAttractionWithWidgets();
        $this->assertSame('2026-04-01', $this->openingHoursSpecifications($attraction->structured_data)[0]['validFrom']);

        Carbon::setTestNow('2026-11-01 01:45:00');
        $this->artisan('attractions:refresh-structured-data')->assertSuccessful();

        $attraction->refresh();

        foreach ([$attraction->structured_data, $attraction->en_structured_data] as $structuredData) {
            $specification = $this->openingHoursSpecifications($structuredData)[0];

            $this->assertSame('2027-04-01', $specification['validFrom']);
            $this->assertSame('2027-10-31', $specification['validThrough']);
        }

        $this->assertTrue($attraction->structured_data_changed_at->equalTo('2026-11-01 01:45:00'));
        $this->assertTrue($attraction->en_structured_data_changed_at->equalTo('2026-11-01 01:45:00'));
    }

    public function test_command_leaves_the_visible_widgets_and_their_change_dates_alone(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');
        $attraction = $this->savedAttractionWithWidgets();
        $widgetColumns = collect(['de', 'en'])
            ->flatMap(fn (string $language): array => Attraction::widgetMap($language))
            ->reject(fn (array $definition, string $field): bool => $field === 'json_ld')
            ->flatMap(fn (array $definition): array => [$definition['html'], $definition['changed_at']])
            ->all();
        $widgetsBefore = $attraction->only($widgetColumns);

        Carbon::setTestNow('2026-11-01 01:45:00');
        $this->artisan('attractions:refresh-structured-data')->assertSuccessful();

        $this->assertEquals($widgetsBefore, $attraction->refresh()->only($widgetColumns));
    }

    public function test_command_does_not_touch_attractions_with_current_dates(): void
    {
        Carbon::setTestNow('2026-07-01 12:00:00');
        $attraction = $this->savedAttractionWithWidgets();
        $updatedAt = $attraction->updated_at;

        Carbon::setTestNow('2026-07-02 01:45:00');
        $this->artisan('attractions:refresh-structured-data')->assertSuccessful();

        $attraction->refresh();

        $this->assertTrue($attraction->updated_at->equalTo($updatedAt));
        $this->assertTrue($attraction->structured_data_changed_at->equalTo('2026-07-01 12:00:00'));
    }

    public function test_command_converts_stored_legacy_dates(): void
    {
        Carbon::setTestNow('2026-07-01 12:00:00');
        $attraction = $this->savedAttractionWithWidgets();
        $attraction->updateQuietly([
            'structured_data' => str_replace(['2026-04-01', '2026-10-31'], ['--04-01', '--10-31'], $attraction->structured_data),
        ]);

        Carbon::setTestNow('2026-07-02 01:45:00');
        $this->artisan('attractions:refresh-structured-data')->assertSuccessful();

        $specification = $this->openingHoursSpecifications($attraction->refresh()->structured_data)[0];

        $this->assertSame('2026-04-01', $specification['validFrom']);
        $this->assertSame('2026-10-31', $specification['validThrough']);
    }

    public function test_refreshed_dates_reach_wordpress_without_dating_any_post(): void
    {
        config(['wordpress.de' => [
            'api_url' => 'https://wp.test/wp-json/attraction-manager/v1',
            'username' => 'admin',
            'password' => 'secret',
        ]]);
        Http::fake(['wp.test/*' => Http::response(['id' => 77])]);

        Carbon::setTestNow('2026-10-31 12:00:00');
        $attraction = $this->savedAttractionWithWidgets();
        $attraction->updateQuietly(['wp_de_id' => 77, 'wp_de_last_sync' => now()->addHour()]);

        Carbon::setTestNow('2026-11-01 01:45:00');
        $this->artisan('attractions:refresh-structured-data')->assertSuccessful();

        Carbon::setTestNow('2026-11-01 02:00:00');
        $this->artisan('attractions:sync-wordpress', ['--language' => 'de'])->assertSuccessful();

        Http::assertSent(function ($request): bool {
            $widgetFields = array_filter(
                array_keys($request->data()),
                fn (string $field): bool => str_starts_with($field, 'widget_')
            );

            return $request->method() === 'PUT'
                && str_contains($request['json_ld'], '2027-04-01')
                && isset($request['json_ld_changed_at'])
                && $widgetFields === [];
        });
    }

    public function test_command_runs_on_an_empty_database(): void
    {
        $this->artisan('attractions:refresh-structured-data')->assertSuccessful();
    }

    public function test_command_is_scheduled_before_the_nightly_sync(): void
    {
        $this->app->make(Kernel::class)->bootstrap();

        $events = collect($this->app->make(Schedule::class)->events());
        $find = fn (string $command): ?Event => $events->first(
            fn (Event $event): bool => str_contains((string) $event->command, $command)
        );

        $this->assertSame('45 1 * * *', $find('attractions:refresh-structured-data')?->expression);
        $this->assertSame('0 2 * * *', $find('attractions:sync-wordpress')?->expression);
    }

    /**
     * @param  array<int, array<string, mixed>>  $seasons
     */
    private function attractionWithSeasons(array $seasons): Attraction
    {
        return Attraction::factory()->create(['manual_opening_hours' => $seasons]);
    }

    private function savedAttractionWithWidgets(): Attraction
    {
        $attraction = $this->attractionWithSeasons([$this->season('04-01', '10-31')]);
        $attraction->generateWidgets();
        $attraction->save();

        return $attraction;
    }

    /**
     * @return array<string, mixed>
     */
    private function season(?string $startDate, ?string $endDate): array
    {
        return [
            'name' => 'Season',
            'is_year_round' => false,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'time_slots' => $this->timeSlots(),
        ];
    }

    /**
     * @return array<int, array{days: array<int, string>, open_time: string, close_time: string}>
     */
    private function timeSlots(): array
    {
        return [['days' => ['monday', 'tuesday'], 'open_time' => '09:00', 'close_time' => '17:00']];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function openingHoursSpecifications(?string $structuredData): array
    {
        return json_decode(strip_tags((string) $structuredData), true)['openingHoursSpecification'];
    }
}
