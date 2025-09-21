<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('locations', function (Blueprint $table) {
            $table->json('opening_hours')->nullable()->after('longitude');
            $table->string('entrance_fee')->nullable()->after('opening_hours');
            $table->decimal('rating', 2, 1)->nullable()->after('entrance_fee');
            $table->string('phone')->nullable()->after('rating');
            $table->string('website')->nullable()->after('phone');
            $table->integer('price_level')->nullable()->after('website');
            $table->string('category')->nullable()->after('price_level');
            $table->string('place_id')->nullable()->after('category');
        });
    }

    public function down(): void {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'opening_hours',
                'entrance_fee', 
                'rating',
                'phone',
                'website',
                'price_level',
                'category',
                'place_id'
            ]);
        });
    }
};