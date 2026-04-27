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
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->text('prompt_de')->nullable();
            $table->text('prompt_en')->nullable();
            $table->string('model')->default('claude-haiku-4-5');
            $table->integer('max_tokens')->default(1000);
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
