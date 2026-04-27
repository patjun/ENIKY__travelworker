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
        // Rename the main table
        Schema::rename('content_pages', 'listicles');

        // Recreate foreign key in listicle_location table
        Schema::table('listicle_location', function (Blueprint $table) {
            $table->foreign('listicle_id')
                ->references('id')
                ->on('listicles')
                ->onDelete('cascade');
        });

        // Recreate foreign key in content_blocks table
        Schema::table('content_blocks', function (Blueprint $table) {
            $table->foreign('listicle_id')
                ->references('id')
                ->on('listicles')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys
        Schema::table('listicle_location', function (Blueprint $table) {
            $table->dropForeign(['listicle_id']);
        });

        Schema::table('content_blocks', function (Blueprint $table) {
            $table->dropForeign(['listicle_id']);
        });

        // Rename table back
        Schema::rename('listicles', 'content_pages');
    }
};
