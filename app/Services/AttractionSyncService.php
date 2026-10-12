<?php

namespace App\Services;

use App\Models\Attraction;
use Illuminate\Support\Facades\Log;

class AttractionSyncService
{
    /**
     * Synchronize a single attraction to WordPress
     */
    public function syncAttraction(Attraction $attraction, string $language, bool $force = false): bool
    {
        try {
            $wpIdField = "wp_{$language}_id";
            $wpSyncField = "wp_{$language}_last_sync";
            $wpId = $attraction->$wpIdField;

            if (! $force && ! $this->needsSync($attraction, $language)) {
                return true;
            }

            $api = new WordPressApiService($language);
            $data = $this->mapAttractionToWordPress($attraction, $language, $wpId && ! $force);

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
                    Log::channel('wordpress-sync')->warning('WordPress attraction was deleted, skipping sync', [
                        'attraction_id' => $attraction->id,
                        'language' => $language,
                        'wp_id' => $wpId,
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
            $attraction->timestamps = false;
            $attraction->save();
            $attraction->timestamps = true;

            Log::channel('wordpress-sync')->info('Attraction synced successfully', [
                'attraction_id' => $attraction->id,
                'language' => $language,
                'wp_id' => $result['id'],
                'name' => $data['name'],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('wordpress-sync')->error('Attraction sync failed', [
                'attraction_id' => $attraction->id,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Synchronize all active attractions to WordPress
     */
    public function syncAllAttractions(string $language, bool $force = false): array
    {
        $attractions = Attraction::whereNull('deleted_at')->get();
        $stats = [
            'total' => $attractions->count(),
            'success_count' => 0,
            'error_count' => 0,
            'errors' => [],
        ];

        foreach ($attractions as $attraction) {
            if ($this->syncAttraction($attraction, $language, $force)) {
                $stats['success_count']++;
            } else {
                $stats['error_count']++;
                $stats['errors'][] = [
                    'attraction_id' => $attraction->id,
                    'name' => $language === 'en' ? ($attraction->en_name ?: $attraction->name) : $attraction->name,
                ];
            }
        }

        Log::channel('wordpress-sync')->info("Sync completed for language: {$language}", $stats);

        return $stats;
    }

    /**
     * Whether the attraction changed since its last successful sync to the given language.
     */
    public function needsSync(Attraction $attraction, string $language): bool
    {
        $wpIdField = "wp_{$language}_id";
        $lastSync = $attraction->{"wp_{$language}_last_sync"};

        if (! $attraction->$wpIdField || $lastSync === null) {
            return true;
        }

        return $attraction->updated_at !== null && $attraction->updated_at->greaterThan($lastSync);
    }

    /**
     * Map Laravel Attraction model to WordPress attraction data structure
     *
     * @param  bool  $onlyChangedWidgets  Send only widgets changed since the last sync, with their change timestamps
     */
    private function mapAttractionToWordPress(Attraction $attraction, string $language, bool $onlyChangedWidgets = false): array
    {
        $attraction->loadMissing('city.country');
        $city = $attraction->city;
        $country = $city?->country;

        if ($language === 'en') {
            $data = [
                'name' => $attraction->en_name ?: $attraction->name,
                'city' => $city?->name_en ?: $city?->name_de,
                'country' => $country?->name_en ?: $country?->name_de,
            ];
        } else {
            $data = [
                'name' => $attraction->name,
                'city' => $city?->name_de ?: $city?->name_en,
                'country' => $country?->name_de ?: $country?->name_en,
            ];
        }

        $lastSync = $attraction->{"wp_{$language}_last_sync"};

        foreach (Attraction::widgetMap($language) as $field => $definition) {
            $changedAt = $attraction->{$definition['changed_at']};
            $isChanged = $lastSync === null || ($changedAt !== null && $changedAt->greaterThan($lastSync));

            if ($onlyChangedWidgets && ! $isChanged) {
                continue;
            }

            $data[$field] = $attraction->{$definition['html']};

            if ($onlyChangedWidgets && $changedAt !== null) {
                $data["{$field}_changed_at"] = $changedAt->copy()->utc()->toIso8601String();
            }
        }

        return array_filter($data, fn ($value): bool => $value !== null);
    }
}
