<?php

namespace Tests\Feature;

use App\Models\Attraction;
use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_06_121750_create_currencies_table.php');
    }

    public function test_existing_assignments_stay_untouched_and_selectable(): void
    {
        Currency::query()->delete();
        $czk = Attraction::factory()->create(['price_currency' => 'CZK']);
        $sek = Attraction::factory()->create(['price_currency' => 'SEK']);

        $this->migration()->seedCurrencies();

        $this->assertSame('CZK', $czk->fresh()->price_currency);
        $this->assertSame('SEK', $sek->fresh()->price_currency);
        $this->assertArrayHasKey('CZK', Currency::selectOptions());
        $this->assertArrayHasKey('SEK', Currency::selectOptions());
        $this->assertArrayHasKey('EUR', Currency::selectOptions());
    }

    public function test_unknown_assigned_currency_keeps_former_formatting(): void
    {
        Currency::query()->delete();
        $attraction = Attraction::factory()->create([
            'price_currency' => 'SEK',
            'prices' => [[
                'name_de' => 'Standard',
                'name_en' => 'Standard',
                'rows' => [['label_de' => 'Erwachsene', 'label_en' => 'Adults', 'amount' => 120, 'is_free' => false]],
            ]],
        ]);

        $this->migration()->seedCurrencies();

        $this->assertStringContainsString('>120,00 SEK<', $attraction->fresh()->generatePricesHtml('de'));
    }

    public function test_seeding_twice_does_not_duplicate_or_change_currencies(): void
    {
        Currency::query()->where('code', 'CHF')->update(['symbol' => 'Fr.']);

        $this->migration()->seedCurrencies();

        $this->assertSame(6, Currency::query()->count());
        $this->assertSame('Fr.', Currency::query()->where('code', 'CHF')->value('symbol'));
    }
}
