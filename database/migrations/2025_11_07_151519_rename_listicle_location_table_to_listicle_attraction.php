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
        Schema::rename('listicle_location', 'listicle_attraction');

        Schema::table('listicle_attraction', function (Blueprint $table) {
            $table->renameColumn('location_id', 'attraction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listicle_attraction', function (Blueprint $table) {
            $table->renameColumn('attraction_id', 'location_id');
        });

        Schema::rename('listicle_attraction', 'listicle_location');
    }
};
