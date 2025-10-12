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
        Schema::table('locations', function (Blueprint $table) {
            // Basic location fields in English
            $table->string('en_name')->nullable();
            $table->string('en_street')->nullable();
            $table->string('en_city')->nullable();
            $table->string('en_country')->nullable();

            // Business data fields in English
            $table->string('en_phone')->nullable();
            $table->string('en_website')->nullable();
            $table->text('en_description')->nullable();
            $table->string('en_category')->nullable();
            $table->json('en_opening_hours')->nullable();
            $table->json('en_attributes')->nullable();
            $table->string('en_main_image_url')->nullable();
            $table->string('en_price_level')->nullable();
            $table->json('en_additional_categories')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'en_name', 'en_street', 'en_city', 'en_country',
                'en_phone', 'en_website', 'en_description', 'en_category',
                'en_opening_hours', 'en_attributes', 'en_main_image_url',
                'en_price_level', 'en_additional_categories'
            ]);
        });
    }
};
