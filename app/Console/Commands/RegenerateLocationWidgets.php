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
                    $location->generateWidgets();
                    $location->save();
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
