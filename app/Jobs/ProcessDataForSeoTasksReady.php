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

class ProcessDataForSeoTasksReady implements ShouldQueue
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
            // Reload location to get latest status
            $currentLocation = Location::find($this->location->id);

            // Check if another job is already handling this location
            if (in_array($currentLocation->job_status, ['getting_results', 'completed', 'failed'])) {
                Log::info('Location already processed or being processed', [
                    'location_id' => $this->location->id,
                    'status' => $currentLocation->job_status
                ]);
                return;
            }

            // Check if it's too early to check (respect timing)
            if ($currentLocation->next_check_at && $currentLocation->next_check_at->isFuture()) {
                Log::info('Too early to check, skipping', [
                    'location_id' => $this->location->id,
                    'next_check_at' => $currentLocation->next_check_at
                ]);

                // Don't reschedule here - use a command to periodically check
                return;
            }

            $currentLocation->update(['job_status' => 'checking_ready']);

            Log::info('Checking tasks_ready for location', ['location_id' => $this->location->id]);

            $readyTasks = $dataForSeoService->getTasksReady();

            if (isset($readyTasks['error'])) {
                throw new \Exception('Failed to check tasks_ready: ' . $readyTasks['error']);
            }

            // Check for rate limiting
            if (isset($readyTasks['tasks'][0]['status_code']) && $readyTasks['tasks'][0]['status_code'] == 40202) {
                Log::warning('Rate limit exceeded, rescheduling check', ['location_id' => $this->location->id]);
                $currentLocation->update(['job_status' => 'task_not_ready']);

                $waitingTime = config('services.dataforseo.waiting_time', 180);
                ProcessDataForSeoTasksReady::dispatch($currentLocation)
                    ->delay(now()->addSeconds($waitingTime));
                return;
            }

            $taskId = $currentLocation->task_id;
            $isReady = false;

            if (isset($readyTasks['tasks'])) {
                foreach ($readyTasks['tasks'] as $task) {
                    if ($task['id'] === $taskId) {
                        $isReady = true;
                        break;
                    }
                }
            }

            $currentLocation->update([
                'tasks_ready_output' => $readyTasks,
            ]);

            if ($isReady) {
                Log::info('Task is ready', ['task_id' => $taskId]);
                $currentLocation->update(['job_status' => 'task_ready']);

                ProcessDataForSeoTaskGet::dispatch($currentLocation);
            } else {
                Log::info('Task not ready yet, rescheduling check', ['task_id' => $taskId]);
                $currentLocation->update([
                    'job_status' => 'task_not_ready',
                    'next_check_at' => now()->addSeconds(config('services.dataforseo.waiting_time', 180))
                ]);

                // Dispatch without delay - timing is controlled by next_check_at
                ProcessDataForSeoTasksReady::dispatch($currentLocation);
            }

        } catch (\Exception $e) {
            Log::error('DataForSEO tasks_ready check failed', [
                'location_id' => $this->location->id,
                'error' => $e->getMessage()
            ]);

            $this->location->update(['job_status' => 'failed']);
            throw $e;
        }
    }
}