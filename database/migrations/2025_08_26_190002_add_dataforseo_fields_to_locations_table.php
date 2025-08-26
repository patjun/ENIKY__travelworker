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
            $table->string('cid')->nullable();
            $table->integer('location_code')->nullable();
            $table->string('language_code')->default('de');
            $table->json('business_data')->nullable();
            $table->timestamp('last_dataforseo_update')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['cid', 'location_code', 'language_code', 'business_data', 'last_dataforseo_update']);
        });
    }
};
