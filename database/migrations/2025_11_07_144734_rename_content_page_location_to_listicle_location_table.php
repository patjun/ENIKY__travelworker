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
        // Drop foreign key constraint before renaming
        Schema::table('content_page_location', function (Blueprint $table) {
            $table->dropForeign(['content_page_id']);
        });

        // Drop primary key and index before renaming column
        Schema::table('content_page_location', function (Blueprint $table) {
            $table->dropPrimary('content_page_location_primary');
            $table->dropIndex(['content_page_id', 'order']);
        });

        // Rename the column
        Schema::table('content_page_location', function (Blueprint $table) {
            $table->renameColumn('content_page_id', 'listicle_id');
        });

        // Rename the table
        Schema::rename('content_page_location', 'listicle_location');

        // Recreate primary key and index with new names
        Schema::table('listicle_location', function (Blueprint $table) {
            $table->primary(['listicle_id', 'location_id'], 'listicle_location_primary');
            $table->index(['listicle_id', 'order']);
        });

        // Note: Foreign key will be recreated after content_pages table is renamed to listicles
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop primary key and index
        Schema::table('listicle_location', function (Blueprint $table) {
            $table->dropPrimary('listicle_location_primary');
            $table->dropIndex(['listicle_id', 'order']);
        });

        // Rename the table back
        Schema::rename('listicle_location', 'content_page_location');

        // Rename the column back
        Schema::table('content_page_location', function (Blueprint $table) {
            $table->renameColumn('listicle_id', 'content_page_id');
        });

        // Recreate primary key and index
        Schema::table('content_page_location', function (Blueprint $table) {
            $table->primary(['content_page_id', 'location_id'], 'content_page_location_primary');
            $table->index(['content_page_id', 'order']);
        });

        // Recreate foreign key
        Schema::table('content_page_location', function (Blueprint $table) {
            $table->foreign('content_page_id')
                ->references('id')
                ->on('content_pages')
                ->onDelete('cascade');
        });
    }
};
