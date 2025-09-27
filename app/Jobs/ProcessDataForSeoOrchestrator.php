<?php

namespace App\Jobs;

use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDataForSeoOrchestrator implements ShouldQueue
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
        try {
            $this->location->update([
                'job_status' => 'orchestrating',
                'en_job_status' => 'orchestrating'
            ]);

            Log::info('Starting DataForSEO orchestration for location (German + English)', ['location_id' => $this->location->id]);

            // Dispatch both German and English tasks simultaneously
            \App\Jobs\ProcessDataForSeoTaskPost::dispatch($this->location);
            \App\Jobs\ProcessDataForSeoTaskPostEnglish::dispatch($this->location);

        } catch (\Exception $e) {
            Log::error('DataForSEO orchestration failed', [
                'location_id' => $this->location->id,
                'error' => $e->getMessage()
            ]);

            $this->location->update([
                'job_status' => 'failed',
                'en_job_status' => 'failed'
            ]);
            throw $e;
        }
    }
}
