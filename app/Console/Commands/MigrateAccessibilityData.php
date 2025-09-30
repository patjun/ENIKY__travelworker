<?php

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Command;

class MigrateAccessibilityData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locations:migrate-accessibility-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate accessibility data from business_data to accessibility columns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting accessibility data migration...');

        $locations = Location::all();
        $total = $locations->count();

        if ($total === 0) {
            $this->warn('No locations found.');
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $migratedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($locations as $location) {
            try {
                $updated = false;

                // Migrate DE accessibility data
                if (empty($location->accessibility) && !empty($location->business_data)) {
                    $attributes = $location->business_data['items'][0]['attributes'] ?? null;
                    if ($attributes) {
                        $location->accessibility = $attributes;
                        $updated = true;
                    }
                }

                // Migrate EN accessibility data
                if (empty($location->en_accessibility) && !empty($location->en_business_data)) {
                    $enAttributes = $location->en_business_data['items'][0]['attributes'] ?? null;
                    if ($enAttributes) {
                        $location->en_accessibility = $enAttributes;
                        $updated = true;
                    }
                }

                if ($updated) {
                    // Save without triggering the saving event to avoid regenerating widgets
                    $location->saveQuietly();
                    $migratedCount++;
                } else {
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->newLine();
                $this->error("Error migrating location ID {$location->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->newLine();

        // Summary
        $this->info("✅ Accessibility data migration completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['✅ Migrated', $migratedCount],
                ['⏭️  Skipped (already has data)', $skippedCount],
                ['❌ Errors', $errorCount],
                ['📊 Total', $total]
            ]
        );

        if ($migratedCount > 0) {
            $this->newLine();
            $this->info("Now run: php artisan locations:regenerate-widgets");
        }

        return 0;
    }
}
