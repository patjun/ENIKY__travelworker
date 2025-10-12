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
        Schema::table('locations', function (Blueprint $table) {
            $table->string('cid')->nullable();
            $table->string('place_id')->nullable();
            $table->string('task_id')->nullable();
            $table->json('task_post_output')->nullable();
            $table->json('task_get_output')->nullable();
            $table->integer('location_code')->nullable();
            $table->string('language_code')->default('de');
            $table->json('business_data')->nullable();
            $table->timestamp('last_dataforseo_update')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'cid',
                'place_id',
                'task_id',
                'task_post_output',
                'task_get_output',
                'location_code',
                'language_code',
                'business_data',
                'last_dataforseo_update'
            ]);
        });
    }
};
