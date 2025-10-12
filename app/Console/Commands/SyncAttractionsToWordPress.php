<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Services\AttractionSyncService;
use Illuminate\Console\Command;

class SyncAttractionsToWordPress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attractions:sync-wordpress {--language=both : de, en, or both}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize attractions to WordPress installations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $language = $this->option('language');

        // Validate language option
        if (!in_array($language, ['de', 'en', 'both'])) {
            $this->error('Invalid language option. Use: de, en, or both');
            return Command::FAILURE;
        }

        $this->info('Starting WordPress synchronization...');
        $this->newLine();

        $syncService = new AttractionSyncService();
        $languages = $language === 'both' ? ['de', 'en'] : [$language];
        $results = [];

        foreach ($languages as $lang) {
            $this->info("Syncing to WordPress ({$lang})...");

            $locations = Location::whereNull('deleted_at')->get();
            $total = $locations->count();

            if ($total === 0) {
                $this->warn("No locations found to sync for language: {$lang}");
                continue;
            }

            // Create progress bar
            $progressBar = $this->output->createProgressBar($total);
            $progressBar->start();

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($locations as $location) {
                if ($syncService->syncLocation($location, $lang)) {
                    $successCount++;
                } else {
                    $errorCount++;
                    $name = $lang === 'en' ? ($location->en_name ?: $location->name) : $location->name;
                    $errors[] = [
                        'id' => $location->id,
                        'name' => $name
                    ];
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            $results[$lang] = [
                'total' => $total,
                'success' => $successCount,
                'failed' => $errorCount,
                'errors' => $errors
            ];
        }

        // Display summary table
        $this->info('Synchronization Summary:');
        $this->newLine();

        $tableData = [];
        foreach ($results as $lang => $stats) {
            $tableData[] = [
                'Language' => strtoupper($lang),
                'Total' => $stats['total'],
                'Success' => $stats['success'],
                'Failed' => $stats['failed']
            ];
        }

        $this->table(
            ['Language', 'Total', 'Success', 'Failed'],
            $tableData
        );

        // Display errors if any
        $hasErrors = false;
        foreach ($results as $lang => $stats) {
            if (!empty($stats['errors'])) {
                $hasErrors = true;
                $this->newLine();
                $this->error("Failed locations for {$lang}:");
                foreach ($stats['errors'] as $error) {
                    $this->line("  - ID {$error['id']}: {$error['name']}");
                }
            }
        }

        $this->newLine();

        if ($hasErrors) {
            $this->warn('Synchronization completed with errors. Check logs for details.');
            return Command::FAILURE;
        }

        $this->info('Synchronization completed successfully!');
        return Command::SUCCESS;
    }
}
