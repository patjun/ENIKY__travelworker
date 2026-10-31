<?php

namespace Tests\Feature;

use App\Models\AccessibilityAttribute;
use App\Models\Attraction;
use App\Models\City;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttractionWidgetHtmlTest extends TestCase
{
    use RefreshDatabase;

    private function makeAttraction(array $attributes = [], string $countryCode = 'PL'): Attraction
    {
        $country = Country::query()->firstOrCreate(
            ['code' => $countryCode],
            ['name_de' => 'Polen', 'name_en' => 'Poland']
        );
        $city = City::factory()->create([
            'country_id' => $country->id,
            'name_de' => 'Łódź',
            'name_en' => 'Lodz',
        ]);

        return Attraction::factory()->create(array_merge([
            'city_id' => $city->id,
            'name' => 'Centralne Muzeum',
            'street' => 'ul. Piotrkowska 282',
            'zip' => '93-034',
            'website' => 'https://www.cmwl.pl/de',
            'email' => 'cmw@cmwl.pl',
        ], $attributes));
    }

    public function test_contact_widget_has_labelled_rows_and_website_host(): void
    {
        $html = $this->makeAttraction()->generateContactInfoHtml('de');

        $this->assertStringContainsString('am-contact', $html);
        $this->assertStringContainsString('Kontakt', $html);
        $this->assertStringContainsString('Centralne Muzeum', $html);
        $this->assertStringContainsString('<dt>Adresse</dt>', $html);
        $this->assertStringContainsString('ul. Piotrkowska 282<br>93-034 Łódź<br>Polen', $html);
        $this->assertStringContainsString('cmwl.pl ↗', $html);
        $this->assertStringContainsString('href="mailto:cmw@cmwl.pl"', $html);
    }

    public function test_contact_widget_english_labels_and_escaping(): void
    {
        $attraction = $this->makeAttraction(['name' => 'Tom & <b>Jerry</b>', 'en_name' => 'Tom & <b>Jerry</b>']);

        $html = $attraction->generateContactInfoHtml('en');

        $this->assertStringContainsString('<dt>Address</dt>', $html);
        $this->assertStringContainsString('<dt>Email</dt>', $html);
        $this->assertStringContainsString('Lodz', $html);
        $this->assertStringNotContainsString('<b>Jerry</b>', $html);
    }

    public function test_contact_widget_omits_missing_rows(): void
    {
        $html = $this->makeAttraction(['website' => null, 'en_website' => null, 'email' => null])->generateContactInfoHtml('de');

        $this->assertStringNotContainsString('Website', $html);
        $this->assertStringNotContainsString('E-Mail', $html);
    }

    public function test_rating_widget_shows_german_number_stars_and_text(): void
    {
        $html = $this->makeAttraction([
            'rating_value' => 4.6,
            'rating_votes_count' => 120,
            'rating_text' => 'Starke Maschinenvorführung.',
        ])->generateRatingHtml('de');

        $this->assertStringContainsString('Lohnt sich der Besuch?', $html);
        $this->assertStringContainsString('>4,6<', $html);
        $this->assertSame(5, substr_count($html, 'am-star-full'));
        $this->assertStringContainsString('von 5 Punkten', $html);
        $this->assertStringContainsString('Starke Maschinenvorführung.', $html);
    }

    public function test_rating_widget_english_uses_dot_and_falls_back_to_german_text(): void
    {
        $html = $this->makeAttraction([
            'rating_value' => 3.2,
            'rating_votes_count' => 10,
            'rating_text' => 'Nur deutscher Text',
            'en_rating_text' => null,
        ])->generateRatingHtml('en');

        $this->assertStringContainsString('>3.2<', $html);
        $this->assertSame(3, substr_count($html, 'am-star-full'));
        $this->assertSame(2, substr_count($html, 'am-star-empty'));
        $this->assertStringContainsString('Nur deutscher Text', $html);
    }

    public function test_rating_widget_without_text_has_no_paragraph_and_without_rating_is_null(): void
    {
        $withRating = $this->makeAttraction(['rating_value' => 4.0, 'rating_votes_count' => 5, 'rating_text' => null]);

        $this->assertStringNotContainsString('am-text', $withRating->generateRatingHtml('de'));

        $withoutRating = $this->makeAttraction(['rating_value' => null, 'rating_votes_count' => null]);

        $this->assertNull($withoutRating->generateRatingHtml('de'));
    }

    public function test_opening_hours_widget_formats_slots_and_closed_days(): void
    {
        $attraction = $this->makeAttraction([
            'manual_opening_hours' => [[
                'name' => null,
                'is_year_round' => true,
                'start_date' => null,
                'end_date' => null,
                'time_slots' => [
                    ['days' => ['wednesday'], 'open_time' => '12:00', 'close_time' => '17:00'],
                    ['days' => ['thursday'], 'open_time' => '09:30', 'close_time' => '12:00'],
                    ['days' => ['thursday'], 'open_time' => '14:00', 'close_time' => '18:00'],
                ],
            ]],
        ]);
        $attraction->generateWidgets();

        $html = $attraction->opening_hours_html;

        $this->assertStringContainsString('Wann geöffnet?', $html);
        $this->assertStringContainsString('Ganzjährig', $html);
        $this->assertStringContainsString('data-timezone="Europe/Warsaw"', $html);
        $this->assertStringContainsString('data-today-label="Heute"', $html);
        $this->assertStringContainsString('12–17 Uhr', $html);
        $this->assertStringContainsString('9:30–12, 14–18 Uhr', $html);
        $this->assertStringContainsString('data-day="monday"><span class="am-day-name">Montag</span><span class="am-time am-closed">geschlossen</span>', $html);
        $this->assertSame(7, substr_count($html, 'data-day="'));
        $this->assertStringContainsString('<details class="am-season" open>', $html);
    }

    public function test_opening_hours_widget_english_uses_twelve_hour_format(): void
    {
        $attraction = $this->makeAttraction([
            'manual_opening_hours' => [[
                'name' => null,
                'is_year_round' => true,
                'start_date' => null,
                'end_date' => null,
                'time_slots' => [
                    ['days' => ['monday'], 'open_time' => '09:00', 'close_time' => '17:00'],
                ],
            ]],
        ]);
        $attraction->generateWidgets();

        $html = $attraction->en_opening_hours_html;

        $this->assertStringContainsString('9:00 AM–5:00 PM', $html);
        $this->assertStringContainsString('When is it open?', $html);
        $this->assertStringContainsString('data-today-label="Today"', $html);
        $this->assertStringContainsString('closed', $html);
    }

    public function test_opening_hours_widget_omits_timezone_for_countries_with_several_zones(): void
    {
        $attraction = $this->makeAttraction([], 'US');
        $attraction->generateWidgets();

        $this->assertStringNotContainsString('data-timezone', (string) $attraction->opening_hours_html);
    }

    public function test_accessibility_widget_lists_attributes_with_check_marks(): void
    {
        $attraction = $this->makeAttraction();
        $attraction->accessibilityAttributes()->attach(
            AccessibilityAttribute::factory()->create(['name_de' => 'Aufzug im Hauptgebäude', 'name_en' => 'Lift'])
        );

        $html = $attraction->fresh()->generateAccessibilityHtml('de');

        $this->assertStringContainsString('Gut zugänglich?', $html);
        $this->assertStringContainsString('Barrierefreiheit', $html);
        $this->assertStringContainsString('am-ok', $html);
        $this->assertStringContainsString('Aufzug im Hauptgebäude', $html);
        $this->assertStringContainsString('Lift', $attraction->fresh()->generateAccessibilityHtml('en'));
    }

    public function test_accessibility_widget_is_null_without_attributes(): void
    {
        $this->assertNull($this->makeAttraction()->generateAccessibilityHtml('de'));
    }

    public function test_structured_data_keeps_closing_script_tag_in_value_inside_the_block(): void
    {
        $name = 'Museum </script><script>alert(1)</script>';

        $html = $this->makeAttraction(['name' => $name])->generateStructuredData('de');

        $this->assertSame(1, substr_count($html, '</script>'));
        $this->assertSame($name, $this->decodeStructuredData($html)['name']);
    }

    public function test_structured_data_encodes_ampersands_for_the_wordpress_filter(): void
    {
        $html = $this->makeAttraction(['name' => 'Kunst & Kultur'])->generateStructuredData('de');

        $this->assertStringNotContainsString('&', $html);
        $this->assertSame('Kunst & Kultur', $this->decodeStructuredData($html)['name']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeStructuredData(string $html): array
    {
        $this->assertSame(1, preg_match('/^<script type="application\/ld\+json">(.*)<\/script>$/s', $html, $matches));

        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
