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
            $table->timestamp('wp_de_last_sync')->nullable()->after('structured_data');
            $table->bigInteger('wp_de_id')->nullable()->after('wp_de_last_sync');
            $table->timestamp('wp_en_last_sync')->nullable()->after('wp_de_id');
            $table->bigInteger('wp_en_id')->nullable()->after('wp_en_last_sync');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['wp_de_last_sync', 'wp_de_id', 'wp_en_last_sync', 'wp_en_id']);
        });
    }
};
