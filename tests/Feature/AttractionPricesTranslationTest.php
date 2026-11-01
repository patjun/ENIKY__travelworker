<?php

namespace Tests\Feature;

use App\Filament\Resources\AttractionResource\Pages\EditAttraction;
use App\Models\Attraction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class AttractionPricesTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        config(['ai.anthropic.api_key' => 'test']);
        $this->actingAs($this->createUserWithRole('admin'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function attractionWithPrices(array $attributes = []): Attraction
    {
        return Attraction::factory()->create(array_merge([
            'price_currency' => 'EUR',
            'price_intro' => 'Einleitung',
            'en_price_intro' => null,
            'price_footer' => 'Fußtext',
            'en_price_footer' => null,
            'prices' => [
                [
                    'name_de' => 'Saison',
                    'name_en' => null,
                    'start_date' => '04-01',
                    'end_date' => '10-31',
                    'rows' => [
                        ['id' => 'row-adult', 'label_de' => 'Erwachsene', 'label_en' => null, 'amount' => 12.5, 'is_free' => false],
                        ['id' => 'row-child', 'label_de' => 'Kinder', 'label_en' => null, 'amount' => null, 'is_free' => true],
                    ],
                ],
            ],
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $translated
     */
    private function fakeClaude(array $translated): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['content' => [['text' => json_encode($translated)]]]),
        ]);
    }

    private function fakeClaudeText(string $text): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['content' => [['text' => $text]]]),
        ]);
    }

    /**
     * @return array{intro: ?string, footer: ?string, sections: list<array{name: ?string, rows: list<?string>}>}
     */
    private function englishTranslation(): array
    {
        return [
            'intro' => 'Introduction',
            'footer' => 'Footnote',
            'sections' => [['name' => 'Season', 'rows' => ['Adults', 'Children']]],
        ];
    }

    private function editComponent(Attraction $attraction): Testable
    {
        return Livewire::test(EditAttraction::class, ['record' => $attraction->getRouteKey()]);
    }

    /**
     * Mounts the header action and confirms it when it asks for confirmation.
     *
     * The test helper callFormComponentAction() looks components up by their state path, which a Section
     * with a plain key does not have, so the Livewire methods are called directly.
     */
    private function runTranslation(Testable $component, string $actionName): Testable
    {
        $component->call('mountFormComponentAction', 'prices-section', $actionName);

        if ($component->instance()->mountedFormComponentActions !== []) {
            $component->call('callMountedFormComponentAction');
        }

        return $component;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function formPrices(Testable $component): array
    {
        return array_values($component->get('data.prices'));
    }

    public function test_german_texts_are_translated_into_the_english_fields(): void
    {
        $attraction = $this->attractionWithPrices();
        $this->fakeClaude($this->englishTranslation());

        $component = $this->runTranslation($this->editComponent($attraction), 'translatePricesToEn')
            ->assertSet('data.en_price_intro', 'Introduction')
            ->assertSet('data.en_price_footer', 'Footnote');

        $prices = $this->formPrices($component);

        $this->assertSame('Season', $prices[0]['name_en']);
        $this->assertSame('Adults', array_values($prices[0]['rows'])[0]['label_en']);
        $this->assertSame('Children', array_values($prices[0]['rows'])[1]['label_en']);
        $this->assertSame('Saison', $prices[0]['name_de']);
        $this->assertSame('Einleitung', $component->get('data.price_intro'));

        Http::assertSent(function (Request $request): bool {
            $prompt = $request['messages'][0]['content'];

            return str_contains($prompt, 'Einleitung')
                && str_contains($prompt, 'Erwachsene')
                && $request['max_tokens'] >= 4000;
        });
    }

    public function test_english_texts_are_translated_into_the_german_fields(): void
    {
        $attraction = $this->attractionWithPrices([
            'price_intro' => null,
            'en_price_intro' => 'Introduction',
            'price_footer' => null,
            'en_price_footer' => null,
            'prices' => [
                [
                    'name_de' => null,
                    'name_en' => 'Season',
                    'rows' => [
                        ['id' => 'row-adult', 'label_de' => null, 'label_en' => 'Adults', 'amount' => 12.5, 'is_free' => false],
                    ],
                ],
            ],
        ]);
        $this->fakeClaude([
            'intro' => 'Einführung',
            'footer' => null,
            'sections' => [['name' => 'Saison', 'rows' => ['Erwachsene']]],
        ]);

        $component = $this->runTranslation($this->editComponent($attraction), 'translatePricesToDe')
            ->assertSet('data.price_intro', 'Einführung')
            ->assertSet('data.price_footer', null);

        $prices = $this->formPrices($component);

        $this->assertSame('Saison', $prices[0]['name_de']);
        $this->assertSame('Erwachsene', array_values($prices[0]['rows'])[0]['label_de']);
        $this->assertSame('Season', $prices[0]['name_en']);
    }

    public function test_translation_uses_unsaved_form_state(): void
    {
        $attraction = $this->attractionWithPrices();
        $this->fakeClaude($this->englishTranslation());

        $component = $this->editComponent($attraction);
        $seasonKey = array_key_first($component->get('data.prices'));
        $rowKey = array_key_first($component->get("data.prices.{$seasonKey}.rows"));

        $component->set("data.prices.{$seasonKey}.rows.{$rowKey}.label_de", 'Senioren');
        $this->runTranslation($component, 'translatePricesToEn');

        Http::assertSent(fn (Request $request): bool => str_contains($request['messages'][0]['content'], 'Senioren')
            && ! str_contains($request['messages'][0]['content'], 'Erwachsene'));
    }

    public function test_translation_does_not_save_the_record(): void
    {
        $attraction = $this->attractionWithPrices();
        $this->fakeClaude($this->englishTranslation());

        $this->runTranslation($this->editComponent($attraction), 'translatePricesToEn');

        $attraction = $attraction->fresh();

        $this->assertNull($attraction->en_price_intro);
        $this->assertNull($attraction->en_price_footer);
        $this->assertNull($attraction->prices[0]['name_en']);
        $this->assertNull($attraction->prices[0]['rows'][0]['label_en']);
    }

    public function test_amounts_dates_and_ids_stay_unchanged(): void
    {
        $attraction = $this->attractionWithPrices(['price_ticket_url' => 'https://example.com/tickets']);
        $this->fakeClaude($this->englishTranslation());

        $component = $this->runTranslation($this->editComponent($attraction), 'translatePricesToEn')
            ->assertSet('data.price_currency', 'EUR')
            ->assertSet('data.price_ticket_url', 'https://example.com/tickets');

        $prices = $this->formPrices($component);
        $rows = array_values($prices[0]['rows']);

        $this->assertSame('04-01', $prices[0]['start_date']);
        $this->assertSame('10-31', $prices[0]['end_date']);
        $this->assertSame('row-adult', $rows[0]['id']);
        $this->assertEquals(12.5, $rows[0]['amount']);
        $this->assertFalse($rows[0]['is_free']);
        $this->assertSame('row-child', $rows[1]['id']);
        $this->assertTrue($rows[1]['is_free']);
    }

    public function test_source_language_empty_text_is_not_sent_as_translation_target(): void
    {
        $attraction = $this->attractionWithPrices([
            'price_intro' => null,
            'en_price_intro' => 'Existing intro',
        ]);
        $this->fakeClaude([
            'intro' => 'Invented intro',
            'footer' => 'Footnote',
            'sections' => [['name' => 'Season', 'rows' => ['Adults', 'Children']]],
        ]);

        $this->runTranslation($this->editComponent($attraction), 'translatePricesToEn')
            ->assertSet('data.en_price_intro', 'Existing intro')
            ->assertSet('data.en_price_footer', 'Footnote');

        Http::assertSent(fn (Request $request): bool => str_contains($request['messages'][0]['content'], '"intro":null'));
    }

    public function test_translation_without_source_texts_shows_a_warning_and_sends_nothing(): void
    {
        $attraction = $this->attractionWithPrices([
            'price_intro' => null,
            'price_footer' => null,
            'prices' => [],
        ]);
        Http::fake();

        $this->runTranslation($this->editComponent($attraction), 'translatePricesToEn')
            ->assertNotified();

        Http::assertNothingSent();
    }

    public function test_confirmation_is_only_required_when_the_target_language_has_text(): void
    {
        $this->fakeClaude($this->englishTranslation());

        $withoutTargetText = $this->editComponent($this->attractionWithPrices())
            ->call('mountFormComponentAction', 'prices-section', 'translatePricesToEn');

        $this->assertSame([], $withoutTargetText->instance()->mountedFormComponentActions);
        $withoutTargetText->assertSet('data.en_price_intro', 'Introduction');

        $withTargetText = $this->editComponent($this->attractionWithPrices(['en_price_intro' => 'Existing intro']))
            ->call('mountFormComponentAction', 'prices-section', 'translatePricesToEn');

        $this->assertSame(['translatePricesToEn'], $withTargetText->instance()->mountedFormComponentActions);
        $withTargetText->assertSet('data.en_price_intro', 'Existing intro');

        $withTargetText->call('callMountedFormComponentAction');

        $this->assertSame([], $withTargetText->instance()->mountedFormComponentActions);
        $withTargetText->assertSet('data.en_price_intro', 'Introduction');
    }

    public function test_invalid_response_leaves_form_unchanged_and_shows_error(): void
    {
        $attraction = $this->attractionWithPrices();
        $this->fakeClaudeText('kein json');

        $this->runTranslation($this->editComponent($attraction), 'translatePricesToEn')
            ->assertNotified()
            ->assertSet('data.en_price_intro', null)
            ->assertSet('data.en_price_footer', null);
    }

    public function test_response_wrapped_in_a_code_fence_is_accepted(): void
    {
        $attraction = $this->attractionWithPrices();
        $this->fakeClaudeText("```json\n".json_encode($this->englishTranslation())."\n```");

        $this->runTranslation($this->editComponent($attraction), 'translatePricesToEn')
            ->assertSet('data.en_price_intro', 'Introduction');
    }

    public function test_response_with_other_structure_leaves_form_unchanged(): void
    {
        $attraction = $this->attractionWithPrices();
        $this->fakeClaude([
            'intro' => 'Introduction',
            'footer' => 'Footnote',
            'sections' => [],
        ]);

        $component = $this->runTranslation($this->editComponent($attraction), 'translatePricesToEn')
            ->assertNotified()
            ->assertSet('data.en_price_intro', null)
            ->assertSet('data.en_price_footer', null);

        $this->assertNull($this->formPrices($component)[0]['name_en']);
    }

    public function test_response_with_fewer_rows_leaves_form_unchanged(): void
    {
        $attraction = $this->attractionWithPrices();
        $translated = $this->englishTranslation();
        $translated['sections'][0]['rows'] = ['Adults'];
        $this->fakeClaude($translated);

        $component = $this->runTranslation($this->editComponent($attraction), 'translatePricesToEn')
            ->assertNotified()
            ->assertSet('data.en_price_intro', null);

        $this->assertNull($this->formPrices($component)[0]['name_en']);
    }

    public function test_connection_error_leaves_form_unchanged_and_shows_error(): void
    {
        $attraction = $this->attractionWithPrices();
        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $this->runTranslation($this->editComponent($attraction), 'translatePricesToEn')
            ->assertNotified()
            ->assertSet('data.en_price_intro', null);
    }
}
