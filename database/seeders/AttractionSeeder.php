<?php

namespace Database\Seeders;

use App\Models\Attraction;
use App\Models\City;
use Illuminate\Database\Seeder;

class AttractionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            // Berlin, Germany
            [
                'city_name' => 'Berlin',
                'locations' => [
                    [
                        'name' => 'Brandenburg Gate Hotel',
                        'street' => 'Unter den Linden 77',
                        'zip' => '10117',
                        'latitude' => 52.516272,
                        'longitude' => 13.377722,
                        'email' => 'info@brandenburggate.de',
                        'website' => 'https://www.brandenburggate-hotel.de',
                        'description' => 'Luxushotel am Brandenburger Tor im Herzen von Berlin',
                        'category' => 'Hotel',
                        'rating_value' => 4.5,
                        'rating_votes_count' => 1250,
                        'en_name' => 'Brandenburg Gate Hotel',
                        'en_website' => 'https://www.brandenburggate-hotel.de',
                        'en_description' => 'Luxury hotel near Brandenburg Gate in the heart of Berlin',
                        'en_category' => 'Hotel',
                        'manual_opening_hours' => [
                            [
                                'name' => null,
                                'is_year_round' => true,
                                'start_date' => null,
                                'end_date' => null,
                                'time_slots' => [
                                    [
                                        'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                                        'open_time' => '00:00',
                                        'close_time' => '23:59',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Museumsinsel Café',
                        'street' => 'Bodestraße 1',
                        'zip' => '10178',
                        'latitude' => 52.521918,
                        'longitude' => 13.396706,
                        'email' => 'cafe@museumsinsel.de',
                        'website' => 'https://www.museumsinsel-cafe.de',
                        'description' => 'Charmantes Café auf der Museumsinsel mit traditioneller deutscher Küche',
                        'category' => 'Restaurant',
                        'rating_value' => 4.2,
                        'rating_votes_count' => 890,
                        'en_name' => 'Museum Island Café',
                        'en_website' => 'https://www.museumsinsel-cafe.de',
                        'en_description' => 'Charming café located on Museum Island with traditional German cuisine',
                        'en_category' => 'Restaurant',
                        'manual_opening_hours' => [
                            [
                                'name' => 'Wintersaison',
                                'is_year_round' => false,
                                'start_date' => '10-01',
                                'end_date' => '03-31',
                                'time_slots' => [
                                    [
                                        'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                                        'open_time' => '09:00',
                                        'close_time' => '18:00',
                                    ],
                                    [
                                        'days' => ['saturday', 'sunday'],
                                        'open_time' => '10:00',
                                        'close_time' => '17:00',
                                    ],
                                ],
                            ],
                            [
                                'name' => 'Sommersaison',
                                'is_year_round' => false,
                                'start_date' => '04-01',
                                'end_date' => '09-30',
                                'time_slots' => [
                                    [
                                        'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                                        'open_time' => '08:00',
                                        'close_time' => '20:00',
                                    ],
                                    [
                                        'days' => ['saturday', 'sunday'],
                                        'open_time' => '09:00',
                                        'close_time' => '19:00',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // Munich, Germany
            [
                'city_name' => 'München',
                'locations' => [
                    [
                        'name' => 'Marienplatz Hotel',
                        'street' => 'Marienplatz 8',
                        'zip' => '80331',
                        'latitude' => 48.137154,
                        'longitude' => 11.575490,
                        'email' => 'info@marienplatz-hotel.de',
                        'website' => 'https://www.marienplatz-hotel.de',
                        'description' => 'Historisches Hotel im Zentrum von München mit Blick auf den Marienplatz',
                        'category' => 'Hotel',
                        'rating_value' => 4.3,
                        'rating_votes_count' => 1100,
                        'en_name' => 'Marienplatz Hotel',
                        'en_website' => 'https://www.marienplatz-hotel.de',
                        'en_description' => 'Historic hotel in the center of Munich overlooking Marienplatz',
                        'en_category' => 'Hotel',
                        'manual_opening_hours' => [
                            [
                                'name' => null,
                                'is_year_round' => true,
                                'start_date' => null,
                                'end_date' => null,
                                'time_slots' => [
                                    [
                                        'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                                        'open_time' => '00:00',
                                        'close_time' => '23:59',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Augustiner Bräu München',
                        'street' => 'Neuhauserstraße 27',
                        'zip' => '80331',
                        'latitude' => 48.138920,
                        'longitude' => 11.570120,
                        'email' => 'info@augustiner-braeu.de',
                        'website' => 'https://www.augustiner-braeu.de',
                        'description' => 'Traditionelle bayerische Brauerei und Restaurant mit authentischem Bier und Essen',
                        'category' => 'Restaurant',
                        'rating_value' => 4.6,
                        'rating_votes_count' => 2100,
                        'en_name' => 'Augustiner Brewery Munich',
                        'en_website' => 'https://www.augustiner-braeu.de',
                        'en_description' => 'Traditional Bavarian brewery and restaurant serving authentic beer and food',
                        'en_category' => 'Restaurant',
                        'manual_opening_hours' => [
                            [
                                'name' => null,
                                'is_year_round' => true,
                                'start_date' => null,
                                'end_date' => null,
                                'time_slots' => [
                                    [
                                        'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                                        'open_time' => '10:00',
                                        'close_time' => '23:00',
                                    ],
                                    [
                                        'days' => ['sunday'],
                                        'open_time' => '10:00',
                                        'close_time' => '22:00',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // Vienna, Austria
            [
                'city_name' => 'Wien',
                'locations' => [
                    [
                        'name' => 'Hotel Sacher Wien',
                        'street' => 'Philharmoniker Str. 4',
                        'zip' => '1010',
                        'latitude' => 48.203891,
                        'longitude' => 16.369426,
                        'email' => 'info@sacher.com',
                        'website' => 'https://www.sacher.com',
                        'description' => 'Legendäres Luxushotel berühmt für die originale Sachertorte',
                        'category' => 'Hotel',
                        'rating_value' => 4.7,
                        'rating_votes_count' => 1850,
                        'en_name' => 'Hotel Sacher Vienna',
                        'en_website' => 'https://www.sacher.com',
                        'en_description' => 'Legendary luxury hotel famous for the original Sachertorte',
                        'en_category' => 'Hotel',
                    ],
                ],
            ],
            // Paris, France
            [
                'city_name' => 'Paris',
                'locations' => [
                    [
                        'name' => 'Le Meurice',
                        'street' => '228 Rue de Rivoli',
                        'zip' => '75001',
                        'latitude' => 48.865572,
                        'longitude' => 2.328020,
                        'email' => 'reservations@lemeurice.com',
                        'website' => 'https://www.lemeurice.com',
                        'description' => 'Hôtel palace face au jardin des Tuileries dans le centre de Paris',
                        'category' => 'Hôtel',
                        'rating_value' => 4.8,
                        'rating_votes_count' => 1650,
                        'en_name' => 'Le Meurice',
                        'en_website' => 'https://www.lemeurice.com',
                        'en_description' => 'Palace hotel facing the Tuileries Garden in central Paris',
                        'en_category' => 'Hotel',
                    ],
                    [
                        'name' => 'Café de Flore',
                        'street' => '172 Boulevard Saint-Germain',
                        'zip' => '75006',
                        'latitude' => 48.854228,
                        'longitude' => 2.332483,
                        'email' => 'contact@cafedeflore.fr',
                        'website' => 'https://www.cafedeflore.fr',
                        'description' => 'Café historique à Saint-Germain-des-Prés, fréquenté par des écrivains et philosophes célèbres',
                        'category' => 'Café',
                        'rating_value' => 4.1,
                        'rating_votes_count' => 3200,
                        'en_name' => 'Café de Flore',
                        'en_website' => 'https://www.cafedeflore.fr',
                        'en_description' => 'Historic café in Saint-Germain-des-Prés, frequented by famous writers and philosophers',
                        'en_category' => 'Café',
                    ],
                ],
            ],
            // London, UK
            [
                'city_name' => 'London',
                'locations' => [
                    [
                        'name' => 'The Savoy',
                        'street' => 'Strand',
                        'zip' => 'WC2R 0EU',
                        'latitude' => 51.510357,
                        'longitude' => -0.120586,
                        'email' => 'info@thesavoylondon.com',
                        'website' => 'https://www.thesavoylondon.com',
                        'description' => 'Legendary luxury hotel on the Strand with Art Deco elegance',
                        'category' => 'Hotel',
                        'rating_value' => 4.6,
                        'rating_votes_count' => 2850,
                        'en_name' => 'The Savoy',
                        'en_website' => 'https://www.thesavoylondon.com',
                        'en_description' => 'Legendary luxury hotel on the Strand with Art Deco elegance',
                        'en_category' => 'Hotel',
                    ],
                ],
            ],
            // Rome, Italy
            [
                'city_name' => 'Rom',
                'locations' => [
                    [
                        'name' => 'Hotel de Russie',
                        'street' => 'Via del Babuino 9',
                        'zip' => '00187',
                        'latitude' => 41.908874,
                        'longitude' => 12.477349,
                        'email' => 'reservations@roccofortehotels.com',
                        'website' => 'https://www.roccofortehotels.com/hotels-and-resorts/hotel-de-russie',
                        'description' => 'Hotel di lusso tra Piazza del Popolo e la Scalinata di Spagna',
                        'category' => 'Hotel',
                        'rating_value' => 4.5,
                        'rating_votes_count' => 1400,
                        'en_name' => 'Hotel de Russie',
                        'en_website' => 'https://www.roccofortehotels.com/hotels-and-resorts/hotel-de-russie',
                        'en_description' => 'Luxury hotel between Piazza del Popolo and Spanish Steps',
                        'en_category' => 'Hotel',
                    ],
                ],
            ],
            // New York, USA
            [
                'city_name' => 'New York',
                'locations' => [
                    [
                        'name' => 'The Plaza',
                        'street' => '768 5th Ave',
                        'zip' => '10019',
                        'latitude' => 40.764749,
                        'longitude' => -73.974717,
                        'email' => 'info@theplazany.com',
                        'website' => 'https://www.theplazany.com',
                        'description' => 'Iconic luxury hotel overlooking Central Park',
                        'category' => 'Hotel',
                        'rating_value' => 4.3,
                        'rating_votes_count' => 4200,
                        'en_name' => 'The Plaza',
                        'en_website' => 'https://www.theplazany.com',
                        'en_description' => 'Iconic luxury hotel overlooking Central Park',
                        'en_category' => 'Hotel',
                    ],
                ],
            ],
            // Tokyo, Japan
            [
                'city_name' => 'Tokio',
                'locations' => [
                    [
                        'name' => 'Imperial Hotel Tokyo',
                        'street' => '1-1-1 Uchisaiwaicho, Chiyoda City',
                        'zip' => '100-8558',
                        'latitude' => 35.675785,
                        'longitude' => 139.758954,
                        'email' => 'info@imperialhotel.co.jp',
                        'website' => 'https://www.imperialhotel.co.jp/e/',
                        'description' => '皇居近くの東京中心部にある歴史ある高級ホテル',
                        'category' => 'ホテル',
                        'rating_value' => 4.4,
                        'rating_votes_count' => 1950,
                        'en_name' => 'Imperial Hotel Tokyo',
                        'en_website' => 'https://www.imperialhotel.co.jp/e/',
                        'en_description' => 'Historic luxury hotel in the heart of Tokyo near the Imperial Palace',
                        'en_category' => 'Hotel',
                    ],
                ],
            ],
        ];

        foreach ($locations as $cityData) {
            $city = City::where('name_de', $cityData['city_name'])
                ->orWhere('name_en', $cityData['city_name'])
                ->first();

            if ($city) {
                foreach ($cityData['locations'] as $locationData) {
                    Attraction::create([
                        'city_id' => $city->id,
                        'name' => $locationData['name'],
                        'street' => $locationData['street'],
                        'zip' => $locationData['zip'],
                        'latitude' => $locationData['latitude'],
                        'longitude' => $locationData['longitude'],
                        'email' => $locationData['email'],
                        'website' => $locationData['website'],
                        'description' => $locationData['description'],
                        'category' => $locationData['category'],
                        'rating_value' => $locationData['rating_value'],
                        'rating_votes_count' => $locationData['rating_votes_count'],
                        'contact_info_html' => '<div>Contact info widget</div>',
                        'rating_html' => '<div>Rating widget</div>',
                        'opening_hours_html' => '<div>Opening hours widget</div>',
                        'accessibility_html' => '<div>Accessibility widget</div>',
                        // English fields
                        'en_name' => $locationData['en_name'] ?? $locationData['name'],
                        'en_website' => $locationData['en_website'] ?? $locationData['website'],
                        'en_description' => $locationData['en_description'] ?? $locationData['description'],
                        'en_category' => $locationData['en_category'] ?? $locationData['category'],
                        'en_contact_info_html' => '<div>Contact info widget</div>',
                        'en_rating_html' => '<div>Rating widget</div>',
                        'en_opening_hours_html' => '<div>Opening hours widget</div>',
                        'en_accessibility_html' => '<div>Accessibility widget</div>',
                        'manual_opening_hours' => $locationData['manual_opening_hours'] ?? null,
                    ]);
                }
            }
        }
    }
}
