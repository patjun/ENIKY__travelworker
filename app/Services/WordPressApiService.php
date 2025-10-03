<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class WordPressApiService
{
    private string $apiUrl;
    private string $username;
    private string $password;
    private string $language;
    private int $timeout = 30;
    private int $maxRetries = 3;

    public function __construct(string $language)
    {
        $this->language = $language;
        $config = config("wordpress.{$language}");

        if (!$config || !$config['api_url'] || !$config['username'] || !$config['password']) {
            throw new \InvalidArgumentException("WordPress configuration for language '{$language}' is incomplete");
        }

        $this->apiUrl = rtrim($config['api_url'], '/');
        $this->username = $config['username'];
        // Remove spaces from WordPress Application Password
        $this->password = str_replace(' ', '', $config['password']);
    }

    /**
     * Create a new attraction in WordPress
     */
    public function createAttraction(array $data): array
    {
        return $this->executeWithRetry(function () use ($data) {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->post("{$this->apiUrl}/attractions", $data);

            if (!$response->successful()) {
                Log::error("WordPress API create failed ({$this->language})", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'data' => $data
                ]);
                $response->throw();
            }

            $result = $response->json();

            Log::info("WordPress attraction created ({$this->language})", [
                'wp_id' => $result['id'] ?? null,
                'name' => $data['name'] ?? null
            ]);

            return $result;
        });
    }

    /**
     * Update an existing attraction in WordPress
     */
    public function updateAttraction(int $wpId, array $data): array
    {
        return $this->executeWithRetry(function () use ($wpId, $data) {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->put("{$this->apiUrl}/attractions/{$wpId}", $data);

            // If 404, the attraction doesn't exist anymore - throw to trigger create
            if ($response->status() === 404) {
                Log::warning("WordPress attraction not found, needs recreation ({$this->language})", [
                    'wp_id' => $wpId
                ]);
                throw new \RuntimeException("Attraction not found (404) - needs recreation");
            }

            if (!$response->successful()) {
                Log::error("WordPress API update failed ({$this->language})", [
                    'wp_id' => $wpId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'data' => $data
                ]);
                $response->throw();
            }

            $result = $response->json();

            Log::info("WordPress attraction updated ({$this->language})", [
                'wp_id' => $wpId,
                'name' => $data['name'] ?? null
            ]);

            return $result;
        });
    }

    /**
     * Delete an attraction from WordPress
     */
    public function deleteAttraction(int $wpId): bool
    {
        return $this->executeWithRetry(function () use ($wpId) {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->delete("{$this->apiUrl}/attractions/{$wpId}");

            if ($response->status() === 404) {
                // Already deleted or never existed
                Log::info("WordPress attraction already deleted or not found ({$this->language})", [
                    'wp_id' => $wpId
                ]);
                return true;
            }

            if (!$response->successful()) {
                Log::error("WordPress API delete failed ({$this->language})", [
                    'wp_id' => $wpId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                $response->throw();
            }

            Log::info("WordPress attraction deleted ({$this->language})", [
                'wp_id' => $wpId
            ]);

            return true;
        });
    }

    /**
     * Get a single attraction from WordPress
     */
    public function getAttraction(int $wpId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->get("{$this->apiUrl}/attractions/{$wpId}");

            if ($response->status() === 404) {
                return null;
            }

            if (!$response->successful()) {
                Log::error("WordPress API get failed ({$this->language})", [
                    'wp_id' => $wpId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("WordPress API get exception ({$this->language})", [
                'wp_id' => $wpId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * List all attractions from WordPress with pagination
     */
    public function listAttractions(int $page = 1, int $perPage = 100): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->get("{$this->apiUrl}/attractions", [
                    'page' => $page,
                    'per_page' => $perPage
                ]);

            if (!$response->successful()) {
                Log::error("WordPress API list failed ({$this->language})", [
                    'page' => $page,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("WordPress API list exception ({$this->language})", [
                'page' => $page,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Execute a callable with retry logic for network failures
     */
    private function executeWithRetry(callable $callback)
    {
        $attempt = 1;
        $lastException = null;

        while ($attempt <= $this->maxRetries) {
            try {
                return $callback();
            } catch (RequestException $e) {
                $lastException = $e;

                // Don't retry on 4xx errors (except 404 which is handled separately)
                if ($e->response && $e->response->status() >= 400 && $e->response->status() < 500) {
                    throw $e;
                }

                if ($attempt < $this->maxRetries) {
                    $delay = $attempt * 1000000; // Microseconds: 1s, 2s, 3s
                    Log::warning("WordPress API retry attempt {$attempt}/{$this->maxRetries} ({$this->language})", [
                        'error' => $e->getMessage()
                    ]);
                    usleep($delay);
                }

                $attempt++;
            } catch (\RuntimeException $e) {
                // Re-throw RuntimeException (like 404 not found)
                throw $e;
            } catch (\Exception $e) {
                $lastException = $e;

                if ($attempt < $this->maxRetries) {
                    $delay = $attempt * 1000000;
                    Log::warning("WordPress API retry attempt {$attempt}/{$this->maxRetries} ({$this->language})", [
                        'error' => $e->getMessage()
                    ]);
                    usleep($delay);
                }

                $attempt++;
            }
        }

        // All retries failed
        Log::error("WordPress API all retries failed ({$this->language})", [
            'error' => $lastException ? $lastException->getMessage() : 'Unknown error'
        ]);

        throw $lastException ?? new \Exception("All retry attempts failed");
    }
}
