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
        $pendingLocations = Location::whereIn('job_status', ['task_posted', 'task_not_ready'])
            ->where(function($query) {
                $query->whereNull('next_check_at')
                      ->orWhere('next_check_at', '<=', now());
            })
            ->get();

        foreach ($pendingLocations as $location) {
            $this->info("Dispatching TasksReady check for location {$location->id}");
            ProcessDataForSeoTasksReady::dispatch($location);
        }

        $this->info("Checked {$pendingLocations->count()} pending locations");
    }
}
