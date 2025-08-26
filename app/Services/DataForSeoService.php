<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DataForSeoService
{
    private $baseUrl = 'https://api.dataforseo.com/v3';
    private $auth;

    public function __construct()
    {
        $this->auth = base64_encode(config('services.dataforseo.username') . ':' . config('services.dataforseo.password'));
    }

    public function getKeywordsForKeywords(array $data)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $this->auth,
        ])->post($this->baseUrl . '/keywords_data/google_ads/keywords_for_keywords/task_post', $data);

        return $response->json();
    }

    public function KeywordsForKeywordsTasksReady()
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $this->auth,
        ])->get($this->baseUrl . '/keywords_data/google_ads/keywords_for_keywords/tasks_ready');

        return $response->json();
    }

    public function KeywordsForKeywordsTaskGet($taskId)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $this->auth,
        ])->get($this->baseUrl . '/keywords_data/google_ads/keywords_for_keywords/task_get/' . $taskId);

        return $response->json();
    }

    public function searchLocations(string $query): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $this->auth,
        ])->get($this->baseUrl . '/dataforseo_labs/locations', [
            'limit' => 10,
            'search' => $query
        ]);

        return $response->json();
    }

    public function getMyBusinessInfo(string $cid, int $locationCode = 2276, string $languageCode = 'de'): array
    {
        $data = [
            [
                'keyword' => "cid:$cid",
                'location_code' => $locationCode,
                'language_code' => $languageCode
            ]
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $this->auth,
        ])->post($this->baseUrl . '/business_data/google/my_business_info/live', $data);

        return $response->json();
    }
}
