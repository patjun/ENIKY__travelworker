<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('keywords', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
            $table->dropColumn('parent_id');
        });
    }

    public function down()
    {
        Schema::table('keywords', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->nullable();
            $table->foreign('post_id')->references('id')->on('keywords')->onDelete('set null');
        });
    }
};
