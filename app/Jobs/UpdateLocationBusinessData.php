<?php

namespace App\Jobs;

use App\Models\Location;
use App\Services\DataForSeoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateLocationBusinessData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Location $location
    ) {}

    public function handle(): void
    {
        if (empty($this->location->cid)) {
            Log::warning("Location {$this->location->id} has no CID, skipping update");
            return;
        }

        try {
            $dataForSeoService = app(DataForSeoService::class);
            $result = $dataForSeoService->getMyBusinessInfo(
                $this->location->cid,
                $this->location->location_code ?? 2276,
                $this->location->language_code ?? 'de',
                $this->location->place_id
            );

            $this->location->update([
                'business_data' => $result,
                'last_dataforseo_update' => now(),
            ]);

            Log::info("Successfully updated business data for location {$this->location->id}");
        } catch (\Exception $e) {
            Log::error("Failed to update business data for location {$this->location->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
