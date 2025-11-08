<?php

namespace App\Services;

use App\Models\Attraction;
use Illuminate\Support\Facades\Log;

class AttractionSyncService
{
    /**
     * Synchronize a single attraction to WordPress
     */
    public function syncAttraction(Attraction $attraction, string $language): bool
    {
        try {
            $api = new WordPressApiService($language);
            $data = $this->mapAttractionToWordPress($attraction, $language);

            // Determine if we need to create or update
            $wpIdField = "wp_{$language}_id";
            $wpSyncField = "wp_{$language}_last_sync";
            $wpId = $attraction->$wpIdField;

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
                        'attraction_id' => $attraction->id,
                        'language' => $language,
                        'wp_id' => $wpId
                    ]);
                    return false;
                }
            } else {
                // No WordPress ID exists - create new attraction
                $result = $api->createAttraction($data);
            }

            // Update attraction with sync info
            $attraction->$wpIdField = $result['id'];
            $attraction->$wpSyncField = now();
            $attraction->save();

            Log::channel('wordpress-sync')->info("Attraction synced successfully", [
                'attraction_id' => $attraction->id,
                'language' => $language,
                'wp_id' => $result['id'],
                'name' => $data['name']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('wordpress-sync')->error("Attraction sync failed", [
                'attraction_id' => $attraction->id,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Synchronize all active attractions to WordPress
     */
    public function syncAllAttractions(string $language): array
    {
        $attractions = Attraction::whereNull('deleted_at')->get();
        $stats = [
            'total' => $attractions->count(),
            'success_count' => 0,
            'error_count' => 0,
            'errors' => []
        ];

        foreach ($attractions as $attraction) {
            if ($this->syncAttraction($attraction, $language)) {
                $stats['success_count']++;
            } else {
                $stats['error_count']++;
                $stats['errors'][] = [
                    'attraction_id' => $attraction->id,
                    'name' => $language === 'en' ? ($attraction->en_name ?: $attraction->name) : $attraction->name
                ];
            }
        }

        Log::channel('wordpress-sync')->info("Sync completed for language: {$language}", $stats);

        return $stats;
    }

    /**
     * Map Laravel Attraction model to WordPress attraction data structure
     */
    private function mapAttractionToWordPress(Attraction $attraction, string $language): array
    {
        if ($language === 'en') {
            $data = [
                'name' => $attraction->en_name ?: $attraction->name,
                'city' => $attraction->en_city ?: $attraction->city,
                'country' => $attraction->en_country ?: $attraction->country,
                'widget_contact' => $attraction->en_contact_info_html,
                'widget_rating' => $attraction->en_rating_html,
                'widget_opening_hours' => $attraction->en_opening_hours_html,
                'widget_accessibility' => $attraction->en_accessibility_html,
                'json_ld' => $attraction->en_structured_data,
            ];
        } else {
            $data = [
                'name' => $attraction->name,
                'city' => $attraction->city,
                'country' => $attraction->country,
                'widget_contact' => $attraction->contact_info_html,
                'widget_rating' => $attraction->rating_html,
                'widget_opening_hours' => $attraction->opening_hours_html,
                'widget_accessibility' => $attraction->accessibility_html,
                'json_ld' => $attraction->structured_data,
            ];
        }

        // Filter out null values to avoid sending "null" strings
        return array_filter($data, function ($value) {
            return $value !== null;
        });
    }
}
