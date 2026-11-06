<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Currencies that were hard-coded before, with the same formatting as before.
     *
     * @var array<string, array{symbol: ?string, decimals: int}>
     */
    private const DEFAULT_CURRENCIES = [
        'EUR' => ['symbol' => '€', 'decimals' => 2],
        'CZK' => ['symbol' => null, 'decimals' => 0],
        'CHF' => ['symbol' => null, 'decimals' => 2],
        'GBP' => ['symbol' => '£', 'decimals' => 2],
        'PLN' => ['symbol' => null, 'decimals' => 2],
        'HUF' => ['symbol' => null, 'decimals' => 0],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique()->comment('ISO 4217 currency code');
            $table->string('symbol', 5)->nullable();
            $table->unsignedTinyInteger('decimals')->default(2);
            $table->timestamps();
        });

        $this->seedCurrencies();
    }

    /**
     * Insert the former default currencies and every currency already assigned to an attraction,
     * so that existing assignments stay valid and selectable.
     */
    public function seedCurrencies(): void
    {
        $now = now();
        $currencies = self::DEFAULT_CURRENCIES;

        DB::table('attractions')
            ->whereNotNull('price_currency')
            ->where('price_currency', '!=', '')
            ->distinct()
            ->pluck('price_currency')
            ->each(function (string $code) use (&$currencies): void {
                $currencies[$code] ??= ['symbol' => null, 'decimals' => 2];
            });

        foreach ($currencies as $code => $currency) {
            DB::table('currencies')->insertOrIgnore([
                'code' => $code,
                'symbol' => $currency['symbol'],
                'decimals' => $currency['decimals'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
