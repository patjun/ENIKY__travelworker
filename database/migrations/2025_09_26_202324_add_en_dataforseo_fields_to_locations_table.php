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
            // English DataForSEO task fields
            $table->string('en_task_id')->nullable();
            $table->json('en_task_post_output')->nullable();
            $table->json('en_task_get_output')->nullable();
            $table->json('en_business_data')->nullable();
            $table->datetime('en_last_dataforseo_update')->nullable();
            $table->enum('en_job_status', ['pending', 'orchestrating', 'posting_task', 'task_posted', 'checking_ready', 'task_ready', 'getting_results', 'completed', 'failed'])->nullable();
            $table->integer('en_post_attempts')->default(0);
            $table->integer('en_get_attempts')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'en_task_id', 'en_task_post_output', 'en_task_get_output',
                'en_business_data', 'en_last_dataforseo_update', 'en_job_status',
                'en_post_attempts', 'en_get_attempts'
            ]);
        });
    }
};
