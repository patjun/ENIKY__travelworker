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

class ProcessDataForSeoTaskGetEnglish implements ShouldQueue
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
            // Increment attempt counter for English
            $this->location->increment('en_get_attempts');
            $this->location->update(['en_job_status' => 'getting_results']);

            Log::info('Getting English task results for location', [
                'location_id' => $this->location->id,
                'en_task_id' => $this->location->en_task_id,
                'attempt' => $this->location->en_get_attempts
            ]);

            $taskId = $this->location->en_task_id;
            if (!$taskId) {
                throw new \Exception('No English task ID found for location');
            }

            $results = $dataForSeoService->getTaskResult($taskId);

            if (isset($results['error'])) {
                throw new \Exception('Failed to get English results: ' . $results['error']);
            }

            $location = Location::find($this->location->id);

            // Extract business data from results
            $businessData = $results['tasks'][0]['result'][0]['items'][0] ?? null;

            if ($businessData) {
                // Coordinates are language-independent, so we only update job status for English task
                $updateData = [
                    'en_last_dataforseo_update' => now(),
                    'en_job_status' => 'completed'
                ];

                // Note: latitude and longitude are already set by the German task
                // We only track that the English task completed successfully

                $location->update($updateData);

                Log::info('English DataForSEO task completed (coordinates already set by German task)', [
                    'location_id' => $location->id
                ]);
            } else {
                $location->update([
                    'en_last_dataforseo_update' => now(),
                    'en_job_status' => 'completed'
                ]);

                Log::warning('No business data found in English DataForSEO response', [
                    'location_id' => $location->id
                ]);
            }

            Log::info('English DataForSEO task_get completed successfully', [
                'location_id' => $location->id,
                'en_task_id' => $taskId,
                'attempt' => $location->en_get_attempts
            ]);

        } catch (\Exception $e) {
            Log::error('English DataForSEO task_get failed', [
                'location_id' => $this->location->id,
                'attempt' => $this->location->en_get_attempts,
                'error' => $e->getMessage()
            ]);

            $this->location->update(['en_job_status' => 'failed']);
            throw $e;
        }
    }
}