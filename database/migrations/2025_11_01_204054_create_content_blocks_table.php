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
        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_page_id')
                ->constrained()
                ->onDelete('cascade');
            $table->morphs('blockable');
            $table->unsignedInteger('order')->default(0);
            $table->enum('language', ['de', 'en'])->default('de');
            $table->timestamps();

            $table->index(['content_page_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_blocks');
    }
};
