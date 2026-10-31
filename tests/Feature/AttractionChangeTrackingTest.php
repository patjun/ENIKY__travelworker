<?php

namespace Tests\Feature;

use App\Models\Attraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttractionChangeTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_changed_at_columns_exist_for_both_languages(): void
    {
        foreach (['', 'en_'] as $prefix) {
            foreach (['contact_info', 'rating', 'opening_hours', 'accessibility', 'structured_data'] as $widget) {
                $this->assertTrue(
                    Schema::hasColumn('attractions', "{$prefix}{$widget}_changed_at"),
                    "Missing column {$prefix}{$widget}_changed_at"
                );
            }
        }
    }

    public function test_widget_map_maps_api_fields_per_language(): void
    {
        $de = Attraction::widgetMap('de');
        $en = Attraction::widgetMap('en');

        $this->assertSame(
            ['html' => 'opening_hours_html', 'changed_at' => 'opening_hours_changed_at', 'generator' => 'generateOpeningHoursHtml'],
            $de['widget_opening_hours']
        );
        $this->assertSame(
            ['html' => 'en_structured_data', 'changed_at' => 'en_structured_data_changed_at', 'generator' => 'generateStructuredData'],
            $en['json_ld']
        );
        $this->assertSame(
            ['widget_contact', 'widget_rating', 'widget_opening_hours', 'widget_accessibility', 'json_ld'],
            array_keys($de)
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function hours(string $open, string $close): array
    {
        return [[
            'name' => null,
            'is_year_round' => true,
            'start_date' => null,
            'end_date' => null,
            'time_slots' => [
                ['days' => ['monday', 'tuesday'], 'open_time' => $open, 'close_time' => $close],
            ],
        ]];
    }

    public function test_first_generation_marks_every_widget_as_changed(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->create(['manual_opening_hours' => $this->hours('09:00', '17:00')]);

        $attraction->generateWidgets();

        foreach (['de', 'en'] as $language) {
            foreach (Attraction::widgetMap($language) as $definition) {
                $this->assertTrue(
                    $attraction->{$definition['changed_at']}->equalTo(now()),
                    $definition['changed_at'].' should be set on first generation'
                );
            }
        }
    }

    public function test_regenerating_unchanged_data_keeps_timestamps(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->create(['manual_opening_hours' => $this->hours('09:00', '17:00')]);
        $attraction->generateWidgets();
        $attraction->save();

        Carbon::setTestNow('2026-10-12 11:00:00');
        $attraction = $attraction->fresh();
        $attraction->generateWidgets();

        $this->assertTrue($attraction->opening_hours_changed_at->equalTo(Carbon::parse('2026-10-12 10:00:00')));
        $this->assertTrue($attraction->contact_info_changed_at->equalTo(Carbon::parse('2026-10-12 10:00:00')));
        $this->assertFalse($attraction->isDirty(['opening_hours_changed_at', 'structured_data_changed_at']));
    }

    public function test_changing_opening_hours_only_marks_dependent_widgets(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->create(['manual_opening_hours' => $this->hours('09:00', '17:00')]);
        $attraction->generateWidgets();
        $attraction->save();

        Carbon::setTestNow('2026-10-12 12:00:00');
        $attraction = $attraction->fresh();
        $attraction->manual_opening_hours = $this->hours('10:00', '18:00');
        $attraction->generateWidgets();

        $later = Carbon::parse('2026-10-12 12:00:00');
        $earlier = Carbon::parse('2026-10-12 10:00:00');
        $this->assertTrue($attraction->opening_hours_changed_at->equalTo($later));
        $this->assertTrue($attraction->en_opening_hours_changed_at->equalTo($later));
        $this->assertTrue($attraction->structured_data_changed_at->equalTo($later), 'JSON-LD contains the opening hours');
        $this->assertTrue($attraction->contact_info_changed_at->equalTo($earlier));
        $this->assertTrue($attraction->rating_changed_at->equalTo($earlier));
        $this->assertTrue($attraction->accessibility_changed_at->equalTo($earlier));
    }

    public function test_changing_english_name_does_not_mark_german_widgets(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->create(['manual_opening_hours' => $this->hours('09:00', '17:00')]);
        $attraction->generateWidgets();
        $attraction->save();

        Carbon::setTestNow('2026-10-12 12:00:00');
        $attraction = $attraction->fresh();
        $attraction->en_name = 'A completely different English name';
        $attraction->generateWidgets();

        $earlier = Carbon::parse('2026-10-12 10:00:00');
        $this->assertTrue($attraction->en_contact_info_changed_at->equalTo(Carbon::parse('2026-10-12 12:00:00')));
        $this->assertTrue($attraction->contact_info_changed_at->equalTo($earlier));
        $this->assertTrue($attraction->structured_data_changed_at->equalTo($earlier));
    }

    public function test_whitespace_only_differences_are_not_a_change(): void
    {
        $this->assertSame(
            Attraction::normalizeHtml("<div>\n    <p>Hallo   Welt</p>\n</div>"),
            Attraction::normalizeHtml('<div><p>Hallo Welt</p></div>')
        );
        $this->assertNotSame(
            Attraction::normalizeHtml('<p>9:00</p>'),
            Attraction::normalizeHtml('<p>10:00</p>')
        );
    }
}
