<?php

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Command;

class RegenerateLocationWidgets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locations:regenerate-widgets {--id= : Regenerate widgets for a specific location ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate HTML widgets for all locations or a specific location';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $locationId = $this->option('id');

        if ($locationId) {
            // Regenerate for specific location
            $location = Location::find($locationId);

            if (!$location) {
                $this->error("Location with ID {$locationId} not found.");
                return 1;
            }

            $this->info("Regenerating widgets for location: {$location->name}");
            $location->generateWidgets();
            $location->save();
            $this->info("✅ Widgets regenerated successfully!");

        } else {
            // Regenerate for all locations
            $this->info('Starting widget regeneration for all locations...');

            $locations = Location::all();
            $total = $locations->count();

            if ($total === 0) {
                $this->warn('No locations found.');
                return 0;
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $successCount = 0;
            $errorCount = 0;

            foreach ($locations as $location) {
                try {
                    // Debug: Check raw data before generation
                    $hasAccessibility = !empty($location->accessibility);
                    $hasEnAccessibility = !empty($location->en_accessibility);

                    $location->generateWidgets();
                    $location->save();

                    // Refresh from database to get actual saved values
                    $location->refresh();

                    // Check if HTML fields are actually populated after save
                    $hasAccessibilityHtml = !empty($location->accessibility_html);
                    $hasEnAccessibilityHtml = !empty($location->en_accessibility_html);

                    // Report any issues
                    if ($hasAccessibility && !$hasAccessibilityHtml) {
                        $this->newLine();
                        $this->warn("Location ID {$location->id} ({$location->name}): Has DE accessibility data but HTML is empty");
                    }
                    if ($hasEnAccessibility && !$hasEnAccessibilityHtml) {
                        $this->newLine();
                        $this->warn("Location ID {$location->id} ({$location->name}): Has EN accessibility data but HTML is empty");
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->newLine();
                    $this->error("Error regenerating widgets for location ID {$location->id}: " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->newLine();

            // Summary
            $this->info("✅ Widget regeneration completed!");
            $this->table(
                ['Status', 'Count'],
                [
                    ['✅ Success', $successCount],
                    ['❌ Errors', $errorCount],
                    ['📊 Total', $total]
                ]
            );
        }

        return 0;
    }
}
