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
        Schema::table('related_links_blocks', function (Blueprint $table) {
            $table->dropColumn(['title_de', 'title_en']);
            $table->string('title')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('related_links_blocks', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->string('title_de')->nullable();
            $table->string('title_en')->nullable();
        });
    }
};
