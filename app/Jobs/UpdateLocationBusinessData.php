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
        if (empty($this->location->cid) && empty($this->location->place_id)) {
            Log::warning("Location {$this->location->id} has no CID or Place ID, skipping update");
            return;
        }

        try {
            $dataForSeoService = app(DataForSeoService::class);
            
            // Step 1: Post the task
            $result = $dataForSeoService->getMyBusinessInfo(
                $this->location->cid,
                $this->location->location_code ?? 2276,
                $this->location->language_code ?? 'de',
                $this->location->place_id
            );

            // Update location with task info
            $this->location->update([
                'task_post_output' => $result,
                'task_id' => $result['tasks'][0]['id'] ?? null,
                'last_dataforseo_update' => now(),
            ]);

            Log::info("Successfully posted business data task for location {$this->location->id}, task_id: " . ($result['tasks'][0]['id'] ?? 'unknown'));
            
            // Dispatch the second job to process results after a delay
            ProcessLocationBusinessDataTasks::dispatch()->delay(now()->addMinutes(2));
        } catch (\Exception $e) {
            Log::error("Failed to post business data task for location {$this->location->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
