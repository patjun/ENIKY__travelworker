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

class ProcessLocationBusinessDataTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $dataForSeoService = app(DataForSeoService::class);
            
            // Step 2: Get ready tasks
            $result = $dataForSeoService->myBusinessInfoTasksReady();
            
            if (!is_null($result['tasks'][0]['result'])) {
                foreach ($result['tasks'][0]['result'] as $task) {
                    
                    // Get the task result
                    $taskResult = $dataForSeoService->myBusinessInfoTaskGet($task['id']);
                    
                    // Find the location with this task_id
                    $location = Location::where('task_id', $taskResult['tasks'][0]['id'])->first();
                    
                    if ($location) {
                        $location->update([
                            'task_get_output' => $taskResult['tasks'][0]['result'],
                            'business_data' => $taskResult['tasks'][0]['result'],
                            'last_dataforseo_update' => now(),
                        ]);
                        
                        Log::info("Successfully processed business data task for location {$location->id}, task_id: {$task['id']}");
                    } else {
                        Log::warning("No location found for task_id: {$task['id']}");
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error("Failed to process business data tasks: " . $e->getMessage());
            throw $e;
        }
    }
}
