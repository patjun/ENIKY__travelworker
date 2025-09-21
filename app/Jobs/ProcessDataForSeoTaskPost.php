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

class ProcessDataForSeoTaskPost implements ShouldQueue
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
            $this->location->update(['job_status' => 'posting_task']);

            Log::info('Starting DataForSEO task_post for location', ['location_id' => $this->location->id]);

            $taskResult = $dataForSeoService->getBusinessData(
                $this->location->place_id,
                $this->location->language_code ?? 'en',
                $this->location->location_code ?? 2276
            );

            if (isset($taskResult['error'])) {
                throw new \Exception('Task creation failed: ' . $taskResult['error']);
            }

            $taskId = $taskResult['tasks'][0]['id'] ?? null;
            if (!$taskId) {
                throw new \Exception('No task ID received from DataForSEO');
            }

            $this->location->update([
                'task_id' => $taskId,
                'task_post_output' => $taskResult,
                'job_status' => 'task_posted'
            ]);

            Log::info('Task posted successfully', ['task_id' => $taskId]);

            // Schedule next check without delay - use a command or different approach
            $this->location->update([
                'next_check_at' => now()->addSeconds(config('services.dataforseo.waiting_time', 180))
            ]);

            // Dispatch immediately but the job will check timing internally
            ProcessDataForSeoTasksReady::dispatch($this->location);

        } catch (\Exception $e) {
            Log::error('DataForSEO task_post failed', [
                'location_id' => $this->location->id,
                'error' => $e->getMessage()
            ]);

            $this->location->update(['job_status' => 'failed']);
            throw $e;
        }
    }
}