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
            // Rename attributes columns to accessibility to avoid conflict with Eloquent's $attributes property
            $table->renameColumn('attributes', 'accessibility');
            $table->renameColumn('en_attributes', 'en_accessibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // Rollback: Rename back to original names
            $table->renameColumn('accessibility', 'attributes');
            $table->renameColumn('en_accessibility', 'en_attributes');
        });
    }
};
