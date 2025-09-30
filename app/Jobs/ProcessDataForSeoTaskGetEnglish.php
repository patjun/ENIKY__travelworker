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
                // Map DataForSEO data to English location fields
                $updateData = [
                    'en_task_get_output' => $results,
                    'en_business_data' => $results['tasks'][0]['result'][0] ?? null,
                    'en_last_dataforseo_update' => now(),
                    'en_job_status' => 'completed'
                ];

                // Map basic business information to English fields
                if (isset($businessData['title'])) {
                    $updateData['en_name'] = $businessData['title'];
                }

                if (isset($businessData['phone'])) {
                    $updateData['en_phone'] = $businessData['phone'];
                }

                if (isset($businessData['url'])) {
                    $updateData['en_website'] = $businessData['url'];
                }

                if (isset($businessData['description'])) {
                    $updateData['en_description'] = $businessData['description'];
                }

                if (isset($businessData['category'])) {
                    $updateData['en_category'] = $businessData['category'];
                }

                // Map address information to English fields
                if (isset($businessData['address_info'])) {
                    $addressInfo = $businessData['address_info'];

                    if (isset($addressInfo['address'])) {
                        $updateData['en_street'] = $addressInfo['address'];
                    }

                    if (isset($addressInfo['city'])) {
                        $updateData['en_city'] = $addressInfo['city'];
                    }

                    if (isset($addressInfo['country_code'])) {
                        $updateData['en_country'] = $addressInfo['country_code'];
                    }
                }

                // Map additional business data to English fields
                if (isset($businessData['work_time'])) {
                    $updateData['en_opening_hours'] = $businessData['work_time'];
                }

                if (isset($businessData['attributes'])) {
                    $updateData['en_accessibility'] = $businessData['attributes'];
                }

                if (isset($businessData['main_image'])) {
                    $updateData['en_main_image_url'] = $businessData['main_image'];
                }

                if (isset($businessData['price_level'])) {
                    $updateData['en_price_level'] = $businessData['price_level'];
                }

                if (isset($businessData['additional_categories'])) {
                    $updateData['en_additional_categories'] = $businessData['additional_categories'];
                }

                $location->update($updateData);

                // Generate widgets after updating location data
                $location->generateWidgets();
                $location->save();

                Log::info('English DataForSEO data mapped to location fields', [
                    'location_id' => $location->id,
                    'mapped_fields' => array_keys($updateData)
                ]);
            } else {
                $location->update([
                    'en_task_get_output' => $results,
                    'en_business_data' => $results['tasks'][0]['result'][0] ?? null,
                    'en_last_dataforseo_update' => now(),
                    'en_job_status' => 'completed'
                ]);

                Log::warning('No English business data found in DataForSEO response', [
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