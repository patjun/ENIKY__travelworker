<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attractions', function (Blueprint $table) {
            $table->json('prices')->nullable();
            $table->string('price_currency', 3)->default('EUR');
            $table->text('price_intro')->nullable();
            $table->text('en_price_intro')->nullable();
            $table->text('price_footer')->nullable();
            $table->text('en_price_footer')->nullable();
            $table->date('price_valid_from')->nullable();
            $table->string('price_ticket_url')->nullable();
            $table->text('prices_html')->nullable();
            $table->text('en_prices_html')->nullable();
            $table->timestamp('prices_changed_at')->nullable();
            $table->timestamp('en_prices_changed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attractions', function (Blueprint $table) {
            $table->dropColumn([
                'prices',
                'price_currency',
                'price_intro',
                'en_price_intro',
                'price_footer',
                'en_price_footer',
                'price_valid_from',
                'price_ticket_url',
                'prices_html',
                'en_prices_html',
                'prices_changed_at',
                'en_prices_changed_at',
            ]);
        });
    }
};
