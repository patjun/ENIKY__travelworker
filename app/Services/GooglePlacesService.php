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
        
        if (empty($this->apiKey)) {
            Log::warning('Google Places API key is not configured. Please set GOOGLE_PLACES_API_KEY in your environment.');
        }
    }

    /**
     * Search for places by text query
     */
    public function searchPlaces(string $query): array
    {
        if (empty($this->apiKey)) {
            Log::error('Cannot search places: Google Places API key is not configured');
            return [];
        }

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
        if (empty($this->apiKey)) {
            Log::error('Cannot get place details: Google Places API key is not configured');
            return null;
        }

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
     * Parse formatted address as a fallback when address components are not available
     */
    public function parseFormattedAddress(string $formattedAddress): array
    {
        // Basic parsing - this is a simple implementation
        // In a real-world scenario, you might want to use a more sophisticated parser
        $parts = explode(', ', $formattedAddress);
        
        $parsed = [
            'street' => '',
            'zip' => '',
            'city' => '',
            'country' => ''
        ];

        if (count($parts) >= 1) {
            $parsed['street'] = $parts[0];
        }
        if (count($parts) >= 2) {
            $parsed['city'] = $parts[1];
        }
        if (count($parts) >= 3) {
            // Try to extract ZIP from the last parts
            $lastPart = end($parts);
            $parsed['country'] = $lastPart;
            
            // Look for ZIP code pattern
            foreach ($parts as $part) {
                if (preg_match('/\b\d{4,5}\b/', $part, $matches)) {
                    $parsed['zip'] = $matches[0];
                    break;
                }
            }
        }

        return $parsed;
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
            // Fallback to formatted address parsing
            $addressData = $this->parseFormattedAddress($place['formatted_address']);
            $data = array_merge($data, $addressData);
        }

        return $data;
    }
}