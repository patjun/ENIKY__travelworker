<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\Log;

class AttractionSyncService
{
    /**
     * Synchronize a single location to WordPress
     */
    public function syncLocation(Location $location, string $language): bool
    {
        try {
            $api = new WordPressApiService($language);
            $data = $this->mapLocationToWordPress($location, $language);

            // Determine if we need to create or update
            $wpIdField = "wp_{$language}_id";
            $wpSyncField = "wp_{$language}_last_sync";
            $wpId = $location->$wpIdField;

            if ($wpId) {
                // WordPress ID exists - only update, never create
                // First verify the attraction still exists in WordPress
                $existing = $api->getAttraction($wpId);

                if ($existing) {
                    // Attraction exists, update it
                    $result = $api->updateAttraction($wpId, $data);
                } else {
                    // Attraction doesn't exist in WordPress but we have an ID stored
                    // This means it was deleted in WordPress
                    Log::channel('wordpress-sync')->warning("WordPress attraction was deleted, skipping sync", [
                        'location_id' => $location->id,
                        'language' => $language,
                        'wp_id' => $wpId
                    ]);
                    return false;
                }
            } else {
                // No WordPress ID exists - create new attraction
                $result = $api->createAttraction($data);
            }

            // Update location with sync info
            $location->$wpIdField = $result['id'];
            $location->$wpSyncField = now();
            $location->save();

            Log::channel('wordpress-sync')->info("Location synced successfully", [
                'location_id' => $location->id,
                'language' => $language,
                'wp_id' => $result['id'],
                'name' => $data['name']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('wordpress-sync')->error("Location sync failed", [
                'location_id' => $location->id,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Synchronize all active locations to WordPress
     */
    public function syncAllLocations(string $language): array
    {
        $locations = Location::whereNull('deleted_at')->get();
        $stats = [
            'total' => $locations->count(),
            'success_count' => 0,
            'error_count' => 0,
            'errors' => []
        ];

        foreach ($locations as $location) {
            if ($this->syncLocation($location, $language)) {
                $stats['success_count']++;
            } else {
                $stats['error_count']++;
                $stats['errors'][] = [
                    'location_id' => $location->id,
                    'name' => $language === 'en' ? ($location->en_name ?: $location->name) : $location->name
                ];
            }
        }

        Log::channel('wordpress-sync')->info("Sync completed for language: {$language}", $stats);

        return $stats;
    }

    /**
     * Map Laravel Location model to WordPress attraction data structure
     */
    private function mapLocationToWordPress(Location $location, string $language): array
    {
        if ($language === 'en') {
            $data = [
                'name' => $location->en_name ?: $location->name,
                'city' => $location->en_city ?: $location->city,
                'country' => $location->en_country ?: $location->country,
                'widget_contact' => $location->en_contact_info_html,
                'widget_rating' => $location->en_rating_html,
                'widget_opening_hours' => $location->en_opening_hours_html,
                'widget_accessibility' => $location->en_accessibility_html,
                'json_ld' => $location->en_structured_data,
            ];
        } else {
            $data = [
                'name' => $location->name,
                'city' => $location->city,
                'country' => $location->country,
                'widget_contact' => $location->contact_info_html,
                'widget_rating' => $location->rating_html,
                'widget_opening_hours' => $location->opening_hours_html,
                'widget_accessibility' => $location->accessibility_html,
                'json_ld' => $location->structured_data,
            ];
        }

        // Filter out null values to avoid sending "null" strings
        return array_filter($data, function ($value) {
            return $value !== null;
        });
    }
}
