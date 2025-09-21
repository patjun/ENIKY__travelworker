<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Jobs\ProcessDataForSeoTasksReady;
use Illuminate\Console\Command;

class CheckPendingDataForSeoTasks extends Command
{
    protected $signature = 'dataforseo:check-pending';
    protected $description = 'Check pending DataForSEO tasks and dispatch ready ones';

    public function handle()
    {
        $pendingLocations = Location::where('job_status', 'task_posted')->get();

        if ($pendingLocations->count() > 0) {
            $this->info("Found {$pendingLocations->count()} locations with posted tasks");
            $this->info("Dispatching global TasksReady check");
            ProcessDataForSeoTasksReady::dispatch();
        } else {
            $this->info("No pending locations found");
        }
    }
}
