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
        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->dateTime('date')->nullable();
            $table->unsignedBigInteger('post_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->json('task_post_output')->nullable();
            $table->string('task_id')->nullable();
            $table->json('task_get_output')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->timestamps();

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('set null');
            $table->unique('task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};
