<?php

namespace App\Console\Commands;

use App\Models\Attraction;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class RefreshAttractionStructuredData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attractions:refresh-structured-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate the JSON-LD of attractions with opening hours so season dates stay current';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $refreshedCount = 0;

        Attraction::query()
            ->whereNotNull('manual_opening_hours')
            ->chunkById(100, function (Collection $attractions) use (&$refreshedCount): void {
                foreach ($attractions as $attraction) {
                    $attraction->refreshStructuredData();

                    if ($attraction->isDirty(['structured_data_changed_at', 'en_structured_data_changed_at'])) {
                        $attraction->save();
                        $refreshedCount++;
                    }
                }
            });

        $this->info("Refreshed structured data of {$refreshedCount} attraction(s).");

        return self::SUCCESS;
    }
}
