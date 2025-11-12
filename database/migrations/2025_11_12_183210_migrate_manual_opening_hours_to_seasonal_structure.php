<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Attraction;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing manual_opening_hours to new seasonal structure
        Attraction::whereNotNull('manual_opening_hours')->each(function ($attraction) {
            $oldHours = $attraction->manual_opening_hours;
            
            // Skip if already in new format (has time_slots key)
            if (isset($oldHours[0]['time_slots'])) {
                return;
            }
            
            // Skip if empty
            if (empty($oldHours)) {
                return;
            }
            
            // Convert old format to new format with a single "year-round" season
            $newHours = [
                [
                    'name' => null,
                    'start_date' => null,
                    'end_date' => null,
                    'is_year_round' => true,
                    'time_slots' => $oldHours,
                ],
            ];
            
            $attraction->manual_opening_hours = $newHours;
            $attraction->save();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migrate back to old structure (flatten time_slots)
        Attraction::whereNotNull('manual_opening_hours')->each(function ($attraction) {
            $newHours = $attraction->manual_opening_hours;
            
            // Skip if already in old format (no time_slots key)
            if (empty($newHours) || !isset($newHours[0]['time_slots'])) {
                return;
            }
            
            // Take time_slots from the first season (should be the year-round one)
            $oldHours = $newHours[0]['time_slots'] ?? [];
            
            $attraction->manual_opening_hours = $oldHours;
            $attraction->save();
        });
    }
};
