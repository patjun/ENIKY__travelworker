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
        Schema::table('listicles', function (Blueprint $table) {
            $table->string('image_de')->nullable()->after('intro_de');
            $table->string('image_en')->nullable()->after('intro_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listicles', function (Blueprint $table) {
            $table->dropColumn(['image_de', 'image_en']);
        });
    }
};
