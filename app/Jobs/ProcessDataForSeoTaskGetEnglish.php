<?php

namespace App\Jobs;

use App\Models\Attraction;
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
        public Attraction $location
    ) {
        //
    }

    public function handle(): void
    {
        $dataForSeoService = new DataForSeoService;

        try {
            // Increment attempt counter for English
            $this->location->increment('en_get_attempts');
            $this->location->update([
                'job_status' => 'getting_results',
                'en_job_status' => 'getting_results',
            ]);

            Log::info('Getting English task results for location', [
                'location_id' => $this->location->id,
                'en_task_id' => $this->location->en_task_id,
                'attempt' => $this->location->en_get_attempts,
            ]);

            $taskId = $this->location->en_task_id;
            if (! $taskId) {
                throw new \Exception('No English task ID found for location');
            }

            $results = $dataForSeoService->getTaskResult($taskId);

            if (isset($results['error'])) {
                throw new \Exception('Failed to get English results: '.$results['error']);
            }

            $location = Attraction::find($this->location->id);

            // Extract business data from results
            $businessData = $results['tasks'][0]['result'][0]['items'][0] ?? null;

            if ($businessData) {
                // Extract latitude and longitude from DataForSEO data
                $updateData = [
                    'last_dataforseo_update' => now(),
                    'en_last_dataforseo_update' => now(),
                    'job_status' => 'completed',
                    'en_job_status' => 'completed',
                ];

                // Map coordinates (language-independent)
                if (isset($businessData['latitude'])) {
                    $updateData['latitude'] = $businessData['latitude'];
                }

                if (isset($businessData['longitude'])) {
                    $updateData['longitude'] = $businessData['longitude'];
                }

                $location->update($updateData);

                Log::info('Successfully extracted coordinates from English task', [
                    'location_id' => $location->id,
                    'latitude' => $updateData['latitude'] ?? 'not found',
                    'longitude' => $updateData['longitude'] ?? 'not found',
                ]);
            } else {
                $location->update([
                    'last_dataforseo_update' => now(),
                    'en_last_dataforseo_update' => now(),
                    'job_status' => 'completed',
                    'en_job_status' => 'completed',
                ]);

                Log::warning('No business data found in English DataForSEO response', [
                    'location_id' => $location->id,
                ]);
            }

            Log::info('English DataForSEO task_get completed successfully', [
                'location_id' => $location->id,
                'en_task_id' => $taskId,
                'attempt' => $location->en_get_attempts,
            ]);

        } catch (\Exception $e) {
            Log::error('English DataForSEO task_get failed', [
                'location_id' => $this->location->id,
                'attempt' => $this->location->en_get_attempts,
                'error' => $e->getMessage(),
            ]);

            $this->location->update([
                'job_status' => 'failed',
                'en_job_status' => 'failed',
            ]);
            throw $e;
        }
    }
}
