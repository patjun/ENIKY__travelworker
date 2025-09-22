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
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->decimal('rating_value', 3, 1)->nullable();
            $table->integer('rating_votes_count')->nullable();
            $table->json('opening_hours')->nullable();
            $table->json('attributes')->nullable();
            $table->string('main_image_url')->nullable();
            $table->boolean('is_claimed')->nullable();
            $table->string('price_level')->nullable();
            $table->json('additional_categories')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'website', 'description', 'category',
                'rating_value', 'rating_votes_count', 'opening_hours',
                'attributes', 'main_image_url', 'is_claimed',
                'price_level', 'additional_categories'
            ]);
        });
    }
};
