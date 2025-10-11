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
            $table->string('task_id')->nullable()->after('place_id');
            $table->json('task_post_output')->nullable()->after('task_id');
            $table->json('task_get_output')->nullable()->after('task_post_output');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['task_id', 'task_post_output', 'task_get_output']);
        });
    }
};
