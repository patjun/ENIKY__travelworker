<?php

namespace App\Console\Commands;

use App\Jobs\UpdateLocationBusinessData;
use App\Models\Location;
use Illuminate\Console\Command;

class UpdateAllLocationBusinessData extends Command
{
    protected $signature = 'location:update-business-data {--force : Update all locations regardless of last update time}';

    protected $description = 'Update business data for all locations with CID from Dataforseo API';

    public function handle(): int
    {
        $this->info('Starting location business data update...');

        $query = Location::query()->whereNotNull('cid')->where('cid', '!=', '');

        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('last_dataforseo_update')
                  ->orWhere('last_dataforseo_update', '<', now()->subDays(7));
            });
        }

        $locations = $query->get();

        if ($locations->isEmpty()) {
            $this->info('No locations need updating.');
            return 0;
        }

        $this->info("Found {$locations->count()} location(s) to update");

        $progressBar = $this->output->createProgressBar($locations->count());
        $progressBar->start();

        foreach ($locations as $location) {
            UpdateLocationBusinessData::dispatch($location);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('All location business data update jobs have been queued.');

        return 0;
    }
}
