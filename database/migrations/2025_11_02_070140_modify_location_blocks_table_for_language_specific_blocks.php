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
        Schema::table('location_blocks', function (Blueprint $table) {
            $table->dropColumn(['custom_intro_de', 'custom_intro_en']);
            $table->text('custom_intro')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_blocks', function (Blueprint $table) {
            $table->dropColumn('custom_intro');
            $table->text('custom_intro_de')->nullable();
            $table->text('custom_intro_en')->nullable();
        });
    }
};
