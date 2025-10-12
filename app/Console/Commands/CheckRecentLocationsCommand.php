<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Jobs\ProcessDataForSeoOrchestrator;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckRecentLocationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locations:update-outdated {--days=7 : Number of days to look back} {--dry-run : Show what would be updated without actually doing it} {--force : Skip confirmation prompt for cronjob execution}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and update outdated locations that have been modified in the last X days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        $this->info("Checking for locations that haven't been updated for more than {$days} days...");

        // Find locations that haven't been updated in the last X days (outdated)
        $outdatedLocations = Location::where(function ($query) use ($days) {
            $query->where(function ($q) use ($days) {
                // Check if last_dataforseo_update is older than X days OR is null
                $q->where('last_dataforseo_update', '<', Carbon::now()->subDays($days))
                  ->orWhereNull('last_dataforseo_update');
            })
            ->where(function ($q) use ($days) {
                // Check if en_last_dataforseo_update is older than X days OR is null
                $q->where('en_last_dataforseo_update', '<', Carbon::now()->subDays($days))
                  ->orWhereNull('en_last_dataforseo_update');
            });
        })
        ->whereNotNull('place_id')
        ->where('place_id', '!=', '')
        ->get();

        if ($outdatedLocations->isEmpty()) {
            $this->info('No outdated locations found that need updating.');
            return 0;
        }

        $this->info("Found {$outdatedLocations->count()} outdated location(s) that haven't been updated for more than {$days} days:");

        $table = [];
        $jobsToStart = 0;
        $skippedJobs = 0;

        foreach ($outdatedLocations as $location) {
            $canStartJob = !in_array($location->job_status, ['processing', 'posting_task', 'checking_ready', 'getting_results', 'orchestrating', 'task_posted', 'task_ready']) &&
                          !in_array($location->en_job_status, ['processing', 'posting_task', 'checking_ready', 'getting_results', 'orchestrating', 'task_posted', 'task_ready']);

            $status = $canStartJob ? 'Ready for update' : 'Job already running';

            if ($canStartJob) {
                $jobsToStart++;
            } else {
                $skippedJobs++;
            }

            $lastUpdate = 'Never';
            if ($location->last_dataforseo_update || $location->en_last_dataforseo_update) {
                $deUpdate = $location->last_dataforseo_update;
                $enUpdate = $location->en_last_dataforseo_update;

                if ($deUpdate && $enUpdate) {
                    $lastUpdate = $deUpdate->gt($enUpdate) ? $deUpdate->format('d.m.Y H:i') : $enUpdate->format('d.m.Y H:i');
                } elseif ($deUpdate) {
                    $lastUpdate = $deUpdate->format('d.m.Y H:i');
                } elseif ($enUpdate) {
                    $lastUpdate = $enUpdate->format('d.m.Y H:i');
                }
            }

            $table[] = [
                $location->id,
                $location->name ?: 'Unnamed',
                $location->city ?: 'Unknown',
                $lastUpdate,
                $location->job_status ?: 'none',
                $location->en_job_status ?: 'none',
                $status
            ];
        }

        $this->table([
            'ID', 'Name', 'City', 'Last DataForSEO Update', 'DE Status', 'EN Status', 'Action'
        ], $table);

        if ($isDryRun) {
            $this->warn("DRY RUN MODE: Would start {$jobsToStart} job(s), skip {$skippedJobs} job(s)");
            return 0;
        }

        if ($jobsToStart === 0) {
            $this->info('No jobs need to be started (all locations are already being processed).');
            return 0;
        }

        // Skip confirmation if force flag is set
        if (!$isForced) {
            $confirm = $this->confirm("Do you want to start {$jobsToStart} ProcessDataForSeoOrchestrator job(s)?");

            if (!$confirm) {
                $this->info('Operation cancelled.');
                return 0;
            }
        } else {
            $this->info("Force mode: Starting {$jobsToStart} job(s) without confirmation...");
        }

        $startedJobs = 0;
        $this->info('Starting jobs...');
        $progressBar = $this->output->createProgressBar($jobsToStart);

        foreach ($outdatedLocations as $location) {
            $canStartJob = !in_array($location->job_status, ['processing', 'posting_task', 'checking_ready', 'getting_results', 'orchestrating', 'task_posted', 'task_ready']) &&
                          !in_array($location->en_job_status, ['processing', 'posting_task', 'checking_ready', 'getting_results', 'orchestrating', 'task_posted', 'task_ready']);

            if ($canStartJob) {
                ProcessDataForSeoOrchestrator::dispatch($location);
                $location->update([
                    'job_status' => 'pending',
                    'en_job_status' => 'pending'
                ]);
                $startedJobs++;
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Successfully started {$startedJobs} job(s).");

        if ($skippedJobs > 0) {
            $this->warn("Skipped {$skippedJobs} location(s) that already have running jobs.");
        }

        return 0;
    }
}
