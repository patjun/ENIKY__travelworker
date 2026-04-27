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
        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title_de');
            $table->string('title_en')->nullable();
            $table->string('slug_de')->unique();
            $table->string('slug_en')->unique()->nullable();
            $table->text('intro_de')->nullable();
            $table->text('intro_en')->nullable();
            $table->string('meta_description_de')->nullable();
            $table->string('meta_description_en')->nullable();
            $table->longText('generated_html_de')->nullable();
            $table->longText('generated_html_en')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_pages');
    }
};
