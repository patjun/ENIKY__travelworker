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

            // Extract business data from results
            $businessData = $results['tasks'][0]['result'][0]['items'][0] ?? null;

            if ($businessData) {
                // Map DataForSEO data to location fields
                $updateData = [
                    'task_get_output' => $results,
                    'business_data' => $results['tasks'][0]['result'][0] ?? null,
                    'last_dataforseo_update' => now(),
                    'job_status' => 'completed'
                ];

                // Map basic business information
                if (isset($businessData['title'])) {
                    $updateData['name'] = $businessData['title'];
                }

                if (isset($businessData['phone'])) {
                    $updateData['phone'] = $businessData['phone'];
                }

                if (isset($businessData['url'])) {
                    $updateData['website'] = $businessData['url'];
                }

                if (isset($businessData['description'])) {
                    $updateData['description'] = $businessData['description'];
                }

                if (isset($businessData['category'])) {
                    $updateData['category'] = $businessData['category'];
                }

                // Map location data
                if (isset($businessData['latitude'])) {
                    $updateData['latitude'] = $businessData['latitude'];
                }

                if (isset($businessData['longitude'])) {
                    $updateData['longitude'] = $businessData['longitude'];
                }

                if (isset($businessData['cid'])) {
                    $updateData['cid'] = $businessData['cid'];
                }

                // Map address information
                if (isset($businessData['address_info'])) {
                    $addressInfo = $businessData['address_info'];

                    if (isset($addressInfo['address'])) {
                        $updateData['street'] = $addressInfo['address'];
                    }

                    if (isset($addressInfo['zip'])) {
                        $updateData['zip'] = $addressInfo['zip'];
                    }

                    if (isset($addressInfo['city'])) {
                        $updateData['city'] = $addressInfo['city'];
                    }

                    if (isset($addressInfo['country_code'])) {
                        $updateData['country'] = $addressInfo['country_code'];
                    }
                }

                // Map rating information
                if (isset($businessData['rating']['value'])) {
                    $updateData['rating_value'] = $businessData['rating']['value'];
                }

                if (isset($businessData['rating']['votes_count'])) {
                    $updateData['rating_votes_count'] = $businessData['rating']['votes_count'];
                }

                // Map additional business data
                if (isset($businessData['work_time'])) {
                    $updateData['opening_hours'] = $businessData['work_time'];
                }

                if (isset($businessData['attributes'])) {
                    $updateData['attributes'] = $businessData['attributes'];
                }

                if (isset($businessData['main_image'])) {
                    $updateData['main_image_url'] = $businessData['main_image'];
                }

                if (isset($businessData['is_claimed'])) {
                    $updateData['is_claimed'] = $businessData['is_claimed'];
                }

                if (isset($businessData['price_level'])) {
                    $updateData['price_level'] = $businessData['price_level'];
                }

                if (isset($businessData['additional_categories'])) {
                    $updateData['additional_categories'] = $businessData['additional_categories'];
                }

                $location->update($updateData);

                Log::info('DataForSEO data mapped to location fields', [
                    'location_id' => $location->id,
                    'mapped_fields' => array_keys($updateData)
                ]);
            } else {
                $location->update([
                    'task_get_output' => $results,
                    'business_data' => $results['tasks'][0]['result'][0] ?? null,
                    'last_dataforseo_update' => now(),
                    'job_status' => 'completed'
                ]);

                Log::warning('No business data found in DataForSEO response', [
                    'location_id' => $location->id
                ]);
            }

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