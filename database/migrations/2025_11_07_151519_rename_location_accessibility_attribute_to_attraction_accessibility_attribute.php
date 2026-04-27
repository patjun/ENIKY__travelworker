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
        Schema::rename('location_accessibility_attribute', 'attraction_accessibility_attribute');

        Schema::table('attraction_accessibility_attribute', function (Blueprint $table) {
            $table->renameColumn('location_id', 'attraction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attraction_accessibility_attribute', function (Blueprint $table) {
            $table->renameColumn('attraction_id', 'location_id');
        });

        Schema::rename('attraction_accessibility_attribute', 'location_accessibility_attribute');
    }
};
