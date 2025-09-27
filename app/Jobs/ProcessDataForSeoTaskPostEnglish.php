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

class ProcessDataForSeoTaskPostEnglish implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
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
            $this->location->increment('en_post_attempts');
            $this->location->update(['en_job_status' => 'posting_task']);

            Log::info('Starting English DataForSEO task_post for location', [
                'location_id' => $this->location->id,
                'attempt' => $this->location->en_post_attempts
            ]);

            $taskResult = $dataForSeoService->getBusinessData(
                $this->location->place_id,
                'en', // English language code
                2826  // English location code
            );

            if (isset($taskResult['error'])) {
                throw new \Exception('English task creation failed: ' . $taskResult['error']);
            }

            $taskId = $taskResult['tasks'][0]['id'] ?? null;
            if (!$taskId) {
                throw new \Exception('No English task ID received from DataForSEO');
            }

            $this->location->update([
                'en_task_id' => $taskId,
                'en_task_post_output' => $taskResult,
                'en_job_status' => 'task_posted'
            ]);

            Log::info('English task posted successfully', [
                'location_id' => $this->location->id,
                'en_task_id' => $taskId
            ]);

        } catch (\Exception $e) {
            Log::error('English DataForSEO task_post failed', [
                'location_id' => $this->location->id,
                'attempt' => $this->location->en_post_attempts,
                'error' => $e->getMessage()
            ]);

            $this->location->update(['en_job_status' => 'failed']);
            throw $e;
        }
    }
}