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
            $table->text('opening_hours_html')->nullable();
            $table->text('structured_data')->nullable();
            $table->text('en_opening_hours_html')->nullable();
            $table->text('en_structured_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'opening_hours_html',
                'structured_data',
                'en_opening_hours_html',
                'en_structured_data'
            ]);
        });
    }
};
