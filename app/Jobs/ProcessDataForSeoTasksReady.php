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

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $dataForSeoService = new DataForSeoService();

        try {
            Log::info('Starting global tasks_ready check');

            $readyTasks = $dataForSeoService->getTasksReady();

            if (isset($readyTasks['error'])) {
                throw new \Exception('Failed to check tasks_ready: ' . $readyTasks['error']);
            }

            // Check for rate limiting
            if (isset($readyTasks['tasks'][0]['status_code']) && $readyTasks['tasks'][0]['status_code'] == 40202) {
                Log::warning('Rate limit exceeded, will retry later');
                throw new \Exception('Rate limit exceeded');
            }

            $readyTaskIds = [];
            if (isset($readyTasks['tasks'][0]['result']) && is_array($readyTasks['tasks'][0]['result'])) {
                foreach ($readyTasks['tasks'][0]['result'] as $task) {
                    $readyTaskIds[] = $task['id'];
                }
            }

            Log::info('Found ready tasks', ['count' => count($readyTaskIds), 'task_ids' => $readyTaskIds]);

            if (empty($readyTaskIds)) {
                Log::info('No tasks ready for processing');
                return;
            }

            // Find locations with ready German tasks
            $locationsWithReadyTasks = Location::whereIn('task_id', $readyTaskIds)
                ->where('job_status', 'task_posted')
                ->get();

            // Find locations with ready English tasks
            $locationsWithReadyEnglishTasks = Location::whereIn('en_task_id', $readyTaskIds)
                ->where('en_job_status', 'task_posted')
                ->get();

            Log::info('Found locations with ready tasks', [
                'german_count' => $locationsWithReadyTasks->count(),
                'english_count' => $locationsWithReadyEnglishTasks->count()
            ]);

            // Process German tasks
            foreach ($locationsWithReadyTasks as $location) {
                Log::info('Processing ready German task for location', [
                    'location_id' => $location->id,
                    'task_id' => $location->task_id
                ]);

                $location->update([
                    'job_status' => 'task_ready',
                    'get_attempts' => 0,
                    'tasks_ready_output' => $readyTasks
                ]);

                ProcessDataForSeoTaskGet::dispatch($location);
            }

            // Process English tasks
            foreach ($locationsWithReadyEnglishTasks as $location) {
                Log::info('Processing ready English task for location', [
                    'location_id' => $location->id,
                    'en_task_id' => $location->en_task_id
                ]);

                $location->update([
                    'en_job_status' => 'task_ready',
                    'en_get_attempts' => 0,
                    'en_tasks_ready_output' => $readyTasks
                ]);

                ProcessDataForSeoTaskGetEnglish::dispatch($location);
            }

        } catch (\Exception $e) {
            Log::error('DataForSEO global tasks_ready check failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}