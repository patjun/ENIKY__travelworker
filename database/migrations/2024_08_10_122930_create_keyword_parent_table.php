<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('keyword_parent', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('keyword_id');
            $table->unsignedBigInteger('parent_id');
            $table->timestamps();

            $table->foreign('keyword_id')->references('id')->on('keywords')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('keywords')->onDelete('cascade');

            $table->unique(['keyword_id', 'parent_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('keyword_parent');
    }
};
