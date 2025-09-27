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
            $table->dropColumn('en_job_status');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->enum('en_job_status', [
                'pending',
                'orchestrating',
                'posting_task',
                'task_posted',
                'checking_ready',
                'task_ready',
                'task_not_ready',
                'getting_results',
                'processing',
                'completed',
                'failed'
            ])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('en_job_status');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->enum('en_job_status', [
                'pending',
                'orchestrating',
                'posting_task',
                'task_posted',
                'checking_ready',
                'getting_results',
                'completed',
                'failed'
            ])->nullable();
        });
    }
};
