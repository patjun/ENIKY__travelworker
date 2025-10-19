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
            $table->dropColumn(['city', 'en_city', 'country', 'en_country']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('city')->nullable()->after('zip');
            $table->string('country')->nullable()->after('city');
            $table->string('en_city')->nullable()->after('en_zip');
            $table->string('en_country')->nullable()->after('en_city');
        });
    }
};
