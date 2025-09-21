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

class ProcessDataForSeoTaskGet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 3;

    public function __construct(
        public Location $location
    ) {
        //
    }

    public function handle(): void
    {
        $dataForSeoService = new DataForSeoService();

        try {
            // Increment attempt counter
            $this->location->increment('get_attempts');
            $this->location->update(['job_status' => 'getting_results']);

            Log::info('Getting task results for location', [
                'location_id' => $this->location->id,
                'task_id' => $this->location->task_id,
                'attempt' => $this->location->get_attempts
            ]);

            $taskId = $this->location->task_id;
            if (!$taskId) {
                throw new \Exception('No task ID found for location');
            }

            $results = $dataForSeoService->getTaskResult($taskId);

            if (isset($results['error'])) {
                throw new \Exception('Failed to get results: ' . $results['error']);
            }

            $location = Location::find($this->location->id);
            $location->update([
                'task_get_output' => $results,
                'business_data' => $results['tasks'][0]['result'][0] ?? null,
                'last_dataforseo_update' => now(),
                'job_status' => 'completed'
            ]);

            Log::info('DataForSEO task_get completed successfully', [
                'location_id' => $location->id,
                'task_id' => $taskId,
                'attempt' => $location->get_attempts
            ]);

        } catch (\Exception $e) {
            Log::error('DataForSEO task_get failed', [
                'location_id' => $this->location->id,
                'attempt' => $this->location->get_attempts,
                'error' => $e->getMessage()
            ]);

            $this->location->update(['job_status' => 'failed']);
            throw $e;
        }
    }
}