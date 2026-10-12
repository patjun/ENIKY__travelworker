<?php

namespace Tests\Feature;

use App\Models\Attraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
