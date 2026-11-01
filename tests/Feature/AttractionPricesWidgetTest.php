<?php

namespace Tests\Feature;

use App\Models\Attraction;
use App\Models\AttractionBlock;
use App\Services\AttractionSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AttractionPricesWidgetTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://wp.test/wp-json/attraction-manager/v1';

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function section(array $rows, string $nameDe = 'Wintersaison', string $nameEn = 'Winter season', ?string $start = '11-01', ?string $end = '02-28'): array
    {
        return [
            'name_de' => $nameDe,
            'name_en' => $nameEn,
            'start_date' => $start,
            'end_date' => $end,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function row(mixed $amount = 6, string $labelDe = 'Erwachsene', ?string $labelEn = 'Adults', bool $isFree = false): array
    {
        return [
            'id' => 'row-'.$labelDe,
            'label_de' => $labelDe,
            'label_en' => $labelEn,
            'amount' => $amount,
            'is_free' => $isFree,
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $sections
     * @param  array<string, mixed>  $attributes
     */
    private function makeAttraction(?array $sections, array $attributes = []): Attraction
    {
        return Attraction::factory()->create(array_merge([
            'prices' => $sections,
            'price_currency' => 'EUR',
        ], $attributes));
    }

    public function test_german_widget_shows_sections_and_formatted_prices(): void
    {
        $html = Attraction::factory()->withPrices()->create()->generatePricesHtml('de');

        $this->assertStringContainsString('class="am-widget am-prices"', $html);
        $this->assertStringContainsString('Preise', $html);
        $this->assertStringContainsString('Was kostet es?', $html);
        $this->assertStringContainsString('Wintersaison', $html);
        $this->assertStringContainsString('Sommersaison', $html);
        $this->assertStringContainsString('Erwachsene', $html);
        $this->assertStringContainsString('<span class="am-price-amount">6,00 €</span>', $html);
        $this->assertStringContainsString('<span class="am-price-amount">8,00 €</span>', $html);
    }

    public function test_english_widget_uses_english_format(): void
    {
        $html = Attraction::factory()->withPrices()->create()->generatePricesHtml('en');

        $this->assertStringContainsString('€6.00', $html);
        $this->assertStringContainsString('What does it cost?', $html);
        $this->assertStringContainsString('Prices', $html);
        $this->assertStringContainsString('Winter season', $html);
        $this->assertStringContainsString('Adults', $html);
    }

    public function test_czk_has_no_decimals_and_iso_code(): void
    {
        $attraction = $this->makeAttraction([$this->section([$this->row(250)])], ['price_currency' => 'CZK']);

        $this->assertStringContainsString('>250 CZK<', $attraction->generatePricesHtml('de'));
        $this->assertStringContainsString('>CZK 250<', $attraction->generatePricesHtml('en'));
    }

    public function test_chf_keeps_two_decimals(): void
    {
        $attraction = $this->makeAttraction([$this->section([$this->row(12)])], ['price_currency' => 'CHF']);

        $this->assertStringContainsString('>12,00 CHF<', $attraction->generatePricesHtml('de'));
        $this->assertStringContainsString('>CHF 12.00<', $attraction->generatePricesHtml('en'));
    }

    public function test_free_row_shows_gratis_instead_of_amount(): void
    {
        $attraction = $this->makeAttraction([$this->section([$this->row(0, 'Kinder', 'Children', true)])]);

        $de = $attraction->generatePricesHtml('de');
        $en = $attraction->generatePricesHtml('en');

        $this->assertStringContainsString('>gratis<', $de);
        $this->assertStringNotContainsString('0,00', $de);
        $this->assertStringContainsString('>free<', $en);
        $this->assertStringNotContainsString('0.00', $en);
    }

    public function test_amount_as_string_and_zero_are_formatted_as_amounts(): void
    {
        $attraction = $this->makeAttraction([$this->section([
            $this->row('3.70', 'Ermäßigt', 'Reduced'),
            $this->row('0', 'Kinder', 'Children'),
        ])]);

        $html = $attraction->generatePricesHtml('de');

        $this->assertStringContainsString('>3,70 €<', $html);
        $this->assertStringContainsString('>0,00 €<', $html);
        $this->assertStringNotContainsString('gratis', $html);
    }

    public function test_missing_translation_falls_back_to_other_language(): void
    {
        $attraction = $this->makeAttraction([
            $this->section([$this->row(6, 'Erwachsene', null)], 'Wintersaison', ''),
        ]);

        $html = $attraction->generatePricesHtml('en');

        $this->assertStringContainsString('Erwachsene', $html);
        $this->assertStringContainsString('Wintersaison', $html);

        $onlyEnglish = $this->makeAttraction([[
            'name_de' => null,
            'name_en' => 'Winter season',
            'start_date' => null,
            'end_date' => null,
            'rows' => [['id' => 'x', 'label_de' => null, 'label_en' => 'Adults', 'amount' => 6, 'is_free' => false]],
        ]])->generatePricesHtml('de');

        $this->assertStringContainsString('Adults', $onlyEnglish);
        $this->assertStringContainsString('Winter season', $onlyEnglish);
    }

    public function test_section_without_title_in_both_languages_is_called_prices(): void
    {
        $attraction = $this->makeAttraction([$this->section([$this->row()], '', '', null, null)]);

        $this->assertStringContainsString('<span>Preise</span>', $attraction->generatePricesHtml('de'));
        $this->assertStringContainsString('<span>Prices</span>', $attraction->generatePricesHtml('en'));
    }

    public function test_widget_is_empty_without_usable_rows(): void
    {
        $unusable = [
            'null' => null,
            'empty list' => [],
            'section without rows' => [$this->section([])],
            'row without amount and not free' => [$this->section([$this->row(null)])],
            'row with empty amount' => [$this->section([$this->row('')])],
        ];

        foreach ($unusable as $case => $prices) {
            $attraction = $this->makeAttraction($prices, ['price_intro' => 'Einleitung', 'en_price_intro' => 'Intro']);

            $this->assertNull($attraction->generatePricesHtml('de'), $case);
            $this->assertNull($attraction->generatePricesHtml('en'), $case);
        }
    }

    public function test_row_without_amount_is_skipped_but_section_stays(): void
    {
        $attraction = $this->makeAttraction([
            $this->section([$this->row(null, 'Ohne Preis', 'No price'), $this->row(6)]),
            $this->section([$this->row(null, 'Auch ohne', 'Also none')], 'Leere Saison', 'Empty season'),
        ]);

        $html = $attraction->generatePricesHtml('de');

        $this->assertStringNotContainsString('Ohne Preis', $html);
        $this->assertStringNotContainsString('Leere Saison', $html);
        $this->assertStringContainsString('Wintersaison', $html);
        $this->assertSame(1, substr_count($html, '<div class="am-price-row">'));
    }

    public function test_season_dates_are_shown_in_the_section_title(): void
    {
        $attraction = Attraction::factory()->make(['price_currency' => 'EUR', 'prices' => [
            $this->section([$this->row()], 'Sommersaison', 'Summer season', '05-01', '09-30'),
        ]]);

        $this->assertStringContainsString('<span>Sommersaison (01.05. - 30.09.)</span>', $attraction->generatePricesHtml('de'));
        $this->assertStringContainsString('<span>Summer season (May 1 - Sep 30)</span>', $attraction->generatePricesHtml('en'));
    }

    public function test_section_title_stays_without_dates_when_the_season_is_incomplete(): void
    {
        $attraction = Attraction::factory()->make(['price_currency' => 'EUR', 'prices' => [
            $this->section([$this->row()], 'Einzeltickets', 'Single tickets', '05-01', null),
        ]]);

        $this->assertStringContainsString('<span>Einzeltickets</span>', $attraction->generatePricesHtml('de'));
    }

    public function test_section_without_title_shows_the_fallback_title_with_its_season_dates(): void
    {
        $attraction = Attraction::factory()->make(['price_currency' => 'EUR', 'prices' => [
            $this->section([$this->row()], '', '', '05-01', '09-30'),
        ]]);

        $this->assertStringContainsString('<span>Preise (01.05. - 30.09.)</span>', $attraction->generatePricesHtml('de'));
    }

    public function test_season_ending_on_february_29_is_shown_correctly_outside_leap_years(): void
    {
        $this->travelTo('2026-11-01');

        $attraction = Attraction::factory()->make(['price_currency' => 'EUR', 'prices' => [
            $this->section([$this->row()], 'Winter', 'Winter', '11-01', '02-29'),
        ]]);

        $this->assertStringContainsString('<span>Winter (01.11. - 29.02.)</span>', $attraction->generatePricesHtml('de'));
        $this->assertStringContainsString('<span>Winter (Nov 1 - Feb 29)</span>', $attraction->generatePricesHtml('en'));
    }

    public function test_first_section_is_open_and_only_complete_seasons_have_data_attributes(): void
    {
        $attraction = $this->makeAttraction([
            $this->section([$this->row()], 'Leer', 'Empty', null, null),
            $this->section([$this->row()], 'Wintersaison', 'Winter season', '11-01', '02-28'),
            $this->section([$this->row()], 'Nur Start', 'Start only', '03-01', null),
        ]);
        $attraction->prices = array_merge([$this->section([])], $attraction->prices);

        $html = $attraction->generatePricesHtml('de');

        $this->assertSame(1, substr_count($html, '<details class="am-season" open'));
        $this->assertStringContainsString('<details class="am-season" open>', $html);
        $this->assertStringContainsString('data-season-start="11-01" data-season-end="02-28"', $html);
        $this->assertSame(1, substr_count($html, 'data-season-start'));
        $this->assertSame(1, substr_count($html, 'data-season-end'));
        $this->assertSame(3, substr_count($html, '<details class="am-season"'));
    }

    public function test_intro_footer_valid_from_and_ticket_link_are_rendered(): void
    {
        $attraction = Attraction::factory()->withPrices()->create([
            'price_intro' => 'Eintritt pro Person.',
            'en_price_intro' => 'Admission per person.',
            'price_footer' => 'Kinder bis 6 Jahre frei.',
            'en_price_footer' => 'Children under 6 free.',
            'price_valid_from' => '2026-09-01',
            'price_ticket_url' => 'https://tickets.example.com/devin?a=1&b=2',
        ]);

        $de = $attraction->generatePricesHtml('de');
        $en = $attraction->generatePricesHtml('en');

        $this->assertStringContainsString('<p class="am-price-intro">Eintritt pro Person.</p>', $de);
        $this->assertStringContainsString('<p class="am-price-footer">Kinder bis 6 Jahre frei.</p>', $de);
        $this->assertStringContainsString('Stand: 01.09.2026', $de);
        $this->assertStringContainsString('<a class="am-link" href="https://tickets.example.com/devin?a=1&amp;b=2" target="_blank" rel="noopener">Online-Tickets</a>', $de);

        $this->assertStringContainsString('Admission per person.', $en);
        $this->assertStringContainsString('Children under 6 free.', $en);
        $this->assertStringContainsString('As of Sep 1, 2026', $en);
        $this->assertStringContainsString('>Online tickets</a>', $en);
    }

    public function test_meta_line_is_absent_without_valid_from_and_ticket_url(): void
    {
        $html = Attraction::factory()->withPrices()->create()->generatePricesHtml('de');

        $this->assertStringNotContainsString('am-price-meta', $html);
        $this->assertStringNotContainsString('am-price-intro', $html);
        $this->assertStringNotContainsString('am-price-footer', $html);

        $onlyDate = Attraction::factory()->withPrices()->create(['price_valid_from' => '2026-09-01'])->generatePricesHtml('de');

        $this->assertStringContainsString('Stand: 01.09.2026', $onlyDate);
        $this->assertStringNotContainsString('<a ', $onlyDate);
    }

    public function test_texts_are_escaped(): void
    {
        $attraction = $this->makeAttraction(
            [$this->section([$this->row(6, '<b>Fett</b>', '<b>Bold</b>')], '<script>x</script> & Co', '<script>x</script> & Co')],
            ['price_intro' => '<i>Hallo</i>', 'price_footer' => '<u>Ende</u>']
        );

        $html = $attraction->generatePricesHtml('de');

        $this->assertStringContainsString('&lt;script&gt;x&lt;/script&gt; &amp; Co', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringNotContainsString('<i>', $html);
        $this->assertStringNotContainsString('<u>', $html);
    }

    public function test_multiline_intro_keeps_line_breaks(): void
    {
        $attraction = $this->makeAttraction(
            [$this->section([$this->row()])],
            ['price_intro' => "Zeile eins\nZeile zwei", 'price_footer' => "A\r\nB"]
        );

        $html = $attraction->generatePricesHtml('de');

        $this->assertStringContainsString("Zeile eins<br />\nZeile zwei", $html);
        $this->assertStringContainsString("A<br />\r\nB", $html);
    }

    public function test_price_change_sets_changed_at_and_unchanged_prices_do_not(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->withPrices()->create();
        $attraction->generateWidgets();
        $attraction->save();

        $firstChangedAt = $attraction->fresh()->prices_changed_at;
        $this->assertNotNull($firstChangedAt);
        $this->assertNotNull($attraction->fresh()->en_prices_changed_at);
        $this->assertStringContainsString('6,00 €', $attraction->fresh()->prices_html);

        Carbon::setTestNow('2026-10-12 12:00:00');
        $attraction = $attraction->fresh();
        $attraction->generateWidgets();
        $attraction->save();

        $this->assertTrue($attraction->fresh()->prices_changed_at->equalTo($firstChangedAt));

        Carbon::setTestNow('2026-10-12 14:00:00');
        $prices = $attraction->prices;
        $prices[0]['rows'][0]['amount'] = 7;
        $attraction->prices = $prices;
        $attraction->generateWidgets();
        $attraction->save();

        $fresh = $attraction->fresh();
        $this->assertTrue($fresh->prices_changed_at->equalTo(Carbon::parse('2026-10-12 14:00:00')));
        $this->assertTrue($fresh->en_prices_changed_at->equalTo(Carbon::parse('2026-10-12 14:00:00')));
        $this->assertStringContainsString('7,00 €', $fresh->prices_html);
    }

    public function test_sync_sends_prices_widget_with_changed_at(): void
    {
        config(['wordpress.de' => ['api_url' => self::API, 'username' => 'admin', 'password' => 'secret']]);
        Http::fake(['wp.test/*/attractions/77' => Http::response(['id' => 77], 200)]);

        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->withPrices()->create();
        $attraction->generateWidgets();
        $attraction->wp_de_id = 77;
        $attraction->wp_de_last_sync = now();
        $attraction->save();

        Carbon::setTestNow('2026-10-12 12:00:00');
        $attraction = $attraction->fresh();
        $prices = $attraction->prices;
        $prices[0]['rows'][0]['amount'] = 7;
        $attraction->prices = $prices;
        $attraction->generateWidgets();
        $attraction->save();

        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction->fresh(), 'de'));

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'PUT'
                && str_contains($request['widget_prices'], '7,00 €')
                && $request['widget_prices_changed_at'] === '2026-10-12T12:00:00+00:00'
                && ! isset($request['widget_contact']);
        });
    }

    public function test_clearing_prices_sends_an_empty_widget(): void
    {
        config(['wordpress.de' => ['api_url' => self::API, 'username' => 'admin', 'password' => 'secret']]);
        Http::fake(['wp.test/*/attractions/77' => Http::response(['id' => 77], 200)]);

        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->withPrices()->create();
        $attraction->generateWidgets();
        $attraction->wp_de_id = 77;
        $attraction->wp_de_last_sync = now();
        $attraction->save();

        Carbon::setTestNow('2026-10-12 12:00:00');
        $attraction = $attraction->fresh();
        $attraction->prices = [];
        $attraction->generateWidgets();
        $attraction->save();

        $this->assertTrue((new AttractionSyncService)->syncAttraction($attraction->fresh(), 'de'));

        Http::assertSent(function (Request $request): bool {
            $data = $request->data();

            return $request->method() === 'PUT'
                && array_key_exists('widget_prices', $data)
                && $data['widget_prices'] === ''
                && $data['widget_prices_changed_at'] === '2026-10-12T12:00:00+00:00';
        });
    }

    public function test_saving_adds_ids_to_new_rows_and_keeps_existing_ones(): void
    {
        $attraction = $this->makeAttraction([$this->section([
            ['label_de' => 'Neu', 'label_en' => 'New', 'amount' => 5, 'is_free' => false],
            $this->row(6),
        ])]);

        $rows = $attraction->fresh()->prices[0]['rows'];

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $rows[0]['id']);
        $this->assertSame('row-Erwachsene', $rows[1]['id']);

        $attraction = $attraction->fresh();
        $attraction->name = 'Umbenannt';
        $attraction->save();

        $this->assertSame($rows[0]['id'], $attraction->fresh()->prices[0]['rows'][0]['id']);
    }

    public function test_saving_without_prices_leaves_them_untouched(): void
    {
        $attraction = Attraction::factory()->create();
        $this->assertNull($attraction->fresh()->prices);

        $attraction->prices = [$this->section([])];
        $attraction->save();

        $this->assertSame([], $attraction->fresh()->prices[0]['rows']);
    }

    public function test_attraction_block_renders_prices_after_opening_hours(): void
    {
        $attraction = Attraction::factory()->withPrices()->create([
            'opening_hours_html' => '<div>Opening hours widget</div>',
            'accessibility_html' => '<div>Accessibility widget</div>',
            'prices_html' => '<div>Prices widget</div>',
            'en_prices_html' => '<div>English prices widget</div>',
        ]);
        $block = AttractionBlock::factory()->create(['attraction_id' => $attraction->id]);

        $html = $block->renderHtml('de');

        $this->assertStringContainsString('<div>Prices widget</div>', $html);
        $this->assertLessThan(strpos($html, 'Prices widget'), strpos($html, 'Opening hours widget'));
        $this->assertLessThan(strpos($html, 'Accessibility widget'), strpos($html, 'Prices widget'));
        $this->assertStringContainsString('English prices widget', $block->renderHtml('en'));
    }
}
