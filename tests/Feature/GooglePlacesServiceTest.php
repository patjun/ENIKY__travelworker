<?php

namespace Tests\Feature;

use App\Services\GooglePlacesService;
use Tests\TestCase;

class GooglePlacesServiceTest extends TestCase
{
    public function test_google_places_service_can_be_instantiated(): void
    {
        $service = new GooglePlacesService();
        $this->assertInstanceOf(GooglePlacesService::class, $service);
    }

    public function test_parse_formatted_address(): void
    {
        $service = new GooglePlacesService();
        
        $formattedAddress = "123 Main St, Berlin, 10115, Germany";
        $result = $service->parseFormattedAddress($formattedAddress);
        
        $this->assertEquals('123 Main St', $result['street']);
        $this->assertEquals('Berlin', $result['city']);
        $this->assertEquals('10115', $result['zip']);
        $this->assertEquals('Germany', $result['country']);
    }

    public function test_extract_location_data(): void
    {
        $service = new GooglePlacesService();
        
        $samplePlace = [
            'name' => 'Test Location',
            'geometry' => [
                'location' => [
                    'lat' => 52.520008,
                    'lng' => 13.404954
                ]
            ],
            'rating' => 4.5,
            'price_level' => 2,
            'international_phone_number' => '+49 30 12345678',
            'website' => 'https://example.com',
            'opening_hours' => [
                'weekday_text' => [
                    'Monday: 9:00 AM – 5:00 PM',
                    'Tuesday: 9:00 AM – 5:00 PM'
                ]
            ],
            'types' => ['restaurant', 'food'],
            'place_id' => 'ChIJAVkDPzdOqEcRcDteW0YgIQQ',
            'formatted_address' => '123 Test St, Berlin, 10115, Germany'
        ];
        
        $result = $service->extractLocationData($samplePlace);
        
        $this->assertEquals('Test Location', $result['name']);
        $this->assertEquals(52.520008, $result['latitude']);
        $this->assertEquals(13.404954, $result['longitude']);
        $this->assertEquals(4.5, $result['rating']);
        $this->assertEquals(2, $result['price_level']);
        $this->assertEquals('+49 30 12345678', $result['phone']);
        $this->assertEquals('https://example.com', $result['website']);
        $this->assertEquals('restaurant, food', $result['category']);
        $this->assertEquals('ChIJAVkDPzdOqEcRcDteW0YgIQQ', $result['place_id']);
        $this->assertIsArray($result['opening_hours']);
    }
}