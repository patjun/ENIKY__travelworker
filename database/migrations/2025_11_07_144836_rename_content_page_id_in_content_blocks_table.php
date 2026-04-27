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
        Schema::table('content_blocks', function (Blueprint $table) {
            $table->dropForeign(['content_page_id']);
        });

        // Rename the column
        Schema::table('content_blocks', function (Blueprint $table) {
            $table->renameColumn('content_page_id', 'listicle_id');
        });

        // Note: Foreign key will be recreated after content_pages table is renamed to listicles
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename the column back
        Schema::table('content_blocks', function (Blueprint $table) {
            $table->renameColumn('listicle_id', 'content_page_id');
        });

        // Recreate foreign key
        Schema::table('content_blocks', function (Blueprint $table) {
            $table->foreign('content_page_id')
                ->references('id')
                ->on('content_pages')
                ->onDelete('cascade');
        });
    }
};
