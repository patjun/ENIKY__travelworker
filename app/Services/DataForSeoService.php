<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataForSeoService
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey = config('services.dataforseo.api_key');
        $this->baseUrl = config('services.dataforseo.base_url');
        $this->timeout = config('services.dataforseo.timeout');
    }

    public function getBusinessData(string $locationId, string $languageCode = 'en', int $locationCode = 2276): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Basic ' . base64_encode($this->apiKey),
                    'Content-Type' => 'application/json'
                ])
                ->post("{$this->baseUrl}/v3/business_data/google/my_business_info/task_post", [
                    [
                        'keyword' => 'place_id:' . $locationId,
                        'language_code' => $languageCode,
                        'location_code' => $locationCode,
                        'tag' => 'location_' . $locationId
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('DataForSEO API response', ['data' => $data]);
                return $data;
            }

            Log::error('DataForSEO API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => "{$this->baseUrl}/v3/business_data/google/my_business_info/task_post"
            ]);

            return [
                'error' => 'API request failed',
                'status' => $response->status(),
                'response' => $response->body(),
                'url' => "{$this->baseUrl}/v3/business_data/google/my_business_info/task_post"
            ];

        } catch (\Exception $e) {
            Log::error('DataForSEO API exception', ['message' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    public function getTaskResult(string $taskId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Basic ' . base64_encode($this->apiKey)
                ])
                ->get("{$this->baseUrl}/v3/business_data/google/my_business_info/task_get/{$taskId}");

            if ($response->successful()) {
                $data = $response->json();
                Log::info('DataForSEO task result', ['data' => $data]);
                return $data;
            }

            Log::error('DataForSEO task result error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return ['error' => 'Task result request failed', 'status' => $response->status()];

        } catch (\Exception $e) {
            Log::error('DataForSEO task result exception', ['message' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }
}