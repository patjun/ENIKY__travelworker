<?php

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Command;

class MapExistingDataForSeoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dataforseo:map-existing-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Map existing DataForSEO data to location fields';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $locations = Location::whereNotNull('task_get_output')->get();

        $this->info("Found {$locations->count()} locations with DataForSEO data");

        $mappedCount = 0;

        foreach ($locations as $location) {
            try {
                $results = $location->task_get_output;
                $businessData = $results['tasks'][0]['result'][0]['items'][0] ?? null;

                if ($businessData) {
                    $updateData = [];

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

                    if (!empty($updateData)) {
                        $location->update($updateData);
                        $mappedCount++;
                        $this->info("✓ Mapped data for location ID {$location->id}: {$location->name}");
                    } else {
                        $this->warn("⚠ No mappable data found for location ID {$location->id}");
                    }
                } else {
                    $this->warn("⚠ No business data structure found for location ID {$location->id}");
                }
            } catch (\Exception $e) {
                $this->error("✗ Error mapping location ID {$location->id}: {$e->getMessage()}");
            }
        }

        $this->info("Mapping completed. {$mappedCount} locations updated.");
    }
}
