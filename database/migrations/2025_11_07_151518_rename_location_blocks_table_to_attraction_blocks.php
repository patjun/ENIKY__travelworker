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
        Schema::rename('location_blocks', 'attraction_blocks');

        Schema::table('attraction_blocks', function (Blueprint $table) {
            $table->renameColumn('location_id', 'attraction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attraction_blocks', function (Blueprint $table) {
            $table->renameColumn('attraction_id', 'location_id');
        });

        Schema::rename('attraction_blocks', 'location_blocks');
    }
};
