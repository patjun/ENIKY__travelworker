<?php

namespace App\Services;

use App\Models\Attraction;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class AttractionSyncService
{
    /**
     * @var array{reason: string, is_login_error: bool}|null
     */
    private ?array $lastFailure = null;

    /**
     * Why the most recent syncAttraction() call failed, null if it succeeded
     *
     * @return array{reason: string, is_login_error: bool}|null
     */
    public function lastFailure(): ?array
    {
        return $this->lastFailure;
    }

    /**
     * Transfer an attraction to both languages and record the outcome in the report.
     *
     * With $refreshWidgets the widgets are regenerated and all of them are sent
     * without dating posts, see docs/ARTIKELDATUM_UND_CACHE.md.
     */
    public function syncToAllLanguages(Attraction $attraction, AttractionSyncReport $report, bool $refreshWidgets = false): void
    {
        $attraction->refresh();

        if ($refreshWidgets && ! $this->regenerateWidgets($attraction, $report)) {
            return;
        }

        foreach (AttractionSyncReport::LANGUAGES as $language) {
            if ($report->hasLoginFailed($language)) {
                $report->add($language, AttractionSyncReport::SKIPPED);

                continue;
            }

            if (! $refreshWidgets && ! $this->needsSync($attraction, $language)) {
                $report->add($language, AttractionSyncReport::UNCHANGED);

                continue;
            }

            if ($this->syncAttraction($attraction, $language, refreshWidgets: $refreshWidgets)) {
                $report->add($language, AttractionSyncReport::SYNCED);

                continue;
            }

            $report->addFailure($this->displayName($attraction, $language), $language, $this->lastFailure['reason']);

            if ($this->lastFailure['is_login_error']) {
                $report->addLoginFailure($language, $this->lastFailure['reason']);
            }
        }
    }

    private function regenerateWidgets(Attraction $attraction, AttractionSyncReport $report): bool
    {
        try {
            $attraction->regenerateWidgetsKeepingChangeDates();
            $attraction->timestamps = false;

            try {
                $attraction->save();
            } finally {
                $attraction->timestamps = true;
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('wordpress-sync')->error('Widget regeneration failed', [
                'attraction_id' => $attraction->id,
                'error' => $e->getMessage(),
            ]);

            foreach (AttractionSyncReport::LANGUAGES as $language) {
                $report->addFailure($this->displayName($attraction, $language), $language, $e->getMessage());
            }

            return false;
        }
    }

    private function displayName(Attraction $attraction, string $language): string
    {
        return $language === 'en' ? ($attraction->en_name ?: $attraction->name) : $attraction->name;
    }

    /**
     * Synchronize a single attraction to WordPress
     */
    public function syncAttraction(Attraction $attraction, string $language, bool $force = false, bool $refreshWidgets = false): bool
    {
        $this->lastFailure = null;

        try {
            $syncStartedAt = now();
            $attraction->refresh();

            $wpIdField = "wp_{$language}_id";
            $wpSyncField = "wp_{$language}_last_sync";
            $wpId = $attraction->$wpIdField;

            if (! $force && ! $refreshWidgets && ! $this->needsSync($attraction, $language)) {
                return true;
            }

            $api = new WordPressApiService($language);
            $data = $refreshWidgets
                ? $this->mapRefreshedAttractionToWordPress($attraction, $language)
                : $this->mapAttractionToWordPress($attraction, $language, $wpId && ! $force);

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
                    $this->lastFailure = ['reason' => 'Deleted in WordPress', 'is_login_error' => false];

                    return false;
                }
            } else {
                // No WordPress ID exists - create new attraction
                $result = $api->createAttraction($data);
            }

            // Update attraction with sync info
            $attraction->$wpIdField = $result['id'];
            $attraction->$wpSyncField = $syncStartedAt;
            $attraction->timestamps = false;

            try {
                $attraction->save();
            } finally {
                $attraction->timestamps = true;
            }

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
            $this->lastFailure = $this->describeFailure($e);

            return false;
        }
    }

    /**
     * A 403 only counts as a login error with the WordPress code, a firewall can block single requests.
     *
     * @return array{reason: string, is_login_error: bool}
     */
    private function describeFailure(\Exception $exception): array
    {
        if (! $exception instanceof RequestException) {
            return ['reason' => $exception->getMessage(), 'is_login_error' => false];
        }

        $status = $exception->response->status();
        $code = $exception->response->json('code');

        return [
            'reason' => trim("HTTP {$status} {$code}"),
            'is_login_error' => $status === 401 || ($status === 403 && $code === 'rest_forbidden'),
        ];
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
     * Send all widgets after a regeneration. Only json_ld_changed_at is added, which
     * clears the post cache without dating posts. Real changes still waiting for a
     * sync keep their timestamp, so their posts are dated as usual.
     */
    private function mapRefreshedAttractionToWordPress(Attraction $attraction, string $language): array
    {
        $data = $this->mapAttractionToWordPress($attraction, $language);
        $lastSync = $attraction->{"wp_{$language}_last_sync"};

        if (! $attraction->{"wp_{$language}_id"}) {
            return $data;
        }

        foreach (Attraction::widgetMap($language) as $field => $definition) {
            $changedAt = $attraction->{$definition['changed_at']};

            if ($changedAt !== null && $lastSync !== null && $changedAt->greaterThan($lastSync)) {
                $data["{$field}_changed_at"] = $changedAt->copy()->utc()->toIso8601String();
            }
        }

        $data['json_ld_changed_at'] = now()->utc()->toIso8601String();

        return $data;
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
