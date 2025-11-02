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
        Schema::create('content_page_location', function (Blueprint $table) {
            $table->foreignId('content_page_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('location_id')
                ->constrained()
                ->onDelete('cascade');
            $table->unsignedInteger('order')->default(0);
            $table->text('custom_intro_de')->nullable();
            $table->text('custom_intro_en')->nullable();
            $table->timestamps();

            $table->primary(['content_page_id', 'location_id'], 'content_page_location_primary');
            $table->index(['content_page_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_page_location');
    }
};
