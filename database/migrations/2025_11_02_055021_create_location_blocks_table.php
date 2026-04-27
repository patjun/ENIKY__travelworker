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
        Schema::create('location_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')
                ->constrained()
                ->onDelete('cascade');
            $table->text('custom_intro_de')->nullable();
            $table->text('custom_intro_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_blocks');
    }
};
