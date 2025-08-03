<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    private string $apiKey;
    private string $baseUrl = 'https://maps.googleapis.com/maps/api/place';

    public function __construct()
    {
        $this->apiKey = config('services.google.places_api_key');
    }

    /**
     * Search for places by text query
     */
    public function searchPlaces(string $query): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/textsearch/json", [
                'query' => $query,
                'key' => $this->apiKey,
                'fields' => 'place_id,name,formatted_address,geometry,rating,price_level,types,international_phone_number,website,opening_hours'
            ]);

            if (!$response->successful()) {
                Log::error('Google Places API search failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $data = $response->json();
            
            if (isset($data['error_message'])) {
                Log::error('Google Places API error', ['error' => $data['error_message']]);
                return [];
            }

            return $data['results'] ?? [];
        } catch (Exception $e) {
            Log::error('Google Places API exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get detailed place information by place ID
     */
    public function getPlaceDetails(string $placeId): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/details/json", [
                'place_id' => $placeId,
                'key' => $this->apiKey,
                'fields' => 'name,formatted_address,geometry,rating,price_level,types,international_phone_number,website,opening_hours,reviews'
            ]);

            if (!$response->successful()) {
                Log::error('Google Places API details failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            $data = $response->json();
            
            if (isset($data['error_message'])) {
                Log::error('Google Places API error', ['error' => $data['error_message']]);
                return null;
            }

            return $data['result'] ?? null;
        } catch (Exception $e) {
            Log::error('Google Places API exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Parse address components from Google Places result
     */
    public function parseAddressComponents(array $place): array
    {
        $components = $place['address_components'] ?? [];
        $parsed = [
            'street' => '',
            'zip' => '',
            'city' => '',
            'country' => ''
        ];

        foreach ($components as $component) {
            $types = $component['types'];
            
            if (in_array('street_number', $types)) {
                $parsed['street'] = $component['long_name'] . ' ';
            } elseif (in_array('route', $types)) {
                $parsed['street'] .= $component['long_name'];
            } elseif (in_array('postal_code', $types)) {
                $parsed['zip'] = $component['long_name'];
            } elseif (in_array('locality', $types)) {
                $parsed['city'] = $component['long_name'];
            } elseif (in_array('country', $types)) {
                $parsed['country'] = $component['long_name'];
            }
        }

        $parsed['street'] = trim($parsed['street']);
        return $parsed;
    }

    /**
     * Extract relevant data for our Location model
     */
    public function extractLocationData(array $place): array
    {
        $data = [
            'name' => $place['name'] ?? '',
            'latitude' => $place['geometry']['location']['lat'] ?? null,
            'longitude' => $place['geometry']['location']['lng'] ?? null,
            'rating' => $place['rating'] ?? null,
            'price_level' => $place['price_level'] ?? null,
            'phone' => $place['international_phone_number'] ?? null,
            'website' => $place['website'] ?? null,
            'opening_hours' => $place['opening_hours']['weekday_text'] ?? null,
            'category' => isset($place['types']) ? implode(', ', array_slice($place['types'], 0, 3)) : null,
            'place_id' => $place['place_id'] ?? null,
        ];

        // Parse address if available
        if (isset($place['address_components'])) {
            $addressData = $this->parseAddressComponents($place);
            $data = array_merge($data, $addressData);
        } elseif (isset($place['formatted_address'])) {
            // Fallback to formatted address
            $data['street'] = $place['formatted_address'];
        }

        return $data;
    }
}