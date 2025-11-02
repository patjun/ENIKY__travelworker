<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            // Germany
            ['country_code' => 'DE', 'name_en' => 'Berlin', 'name_de' => 'Berlin'],
            ['country_code' => 'DE', 'name_en' => 'Munich', 'name_de' => 'München'],
            ['country_code' => 'DE', 'name_en' => 'Hamburg', 'name_de' => 'Hamburg'],
            
            // Austria
            ['country_code' => 'AT', 'name_en' => 'Vienna', 'name_de' => 'Wien'],
            ['country_code' => 'AT', 'name_en' => 'Salzburg', 'name_de' => 'Salzburg'],
            
            // Switzerland
            ['country_code' => 'CH', 'name_en' => 'Zurich', 'name_de' => 'Zürich'],
            ['country_code' => 'CH', 'name_en' => 'Geneva', 'name_de' => 'Genf'],
            
            // France
            ['country_code' => 'FR', 'name_en' => 'Paris', 'name_de' => 'Paris'],
            ['country_code' => 'FR', 'name_en' => 'Lyon', 'name_de' => 'Lyon'],
            ['country_code' => 'FR', 'name_en' => 'Nice', 'name_de' => 'Nizza'],
            
            // United Kingdom
            ['country_code' => 'GB', 'name_en' => 'London', 'name_de' => 'London'],
            ['country_code' => 'GB', 'name_en' => 'Edinburgh', 'name_de' => 'Edinburgh'],
            
            // Italy
            ['country_code' => 'IT', 'name_en' => 'Rome', 'name_de' => 'Rom'],
            ['country_code' => 'IT', 'name_en' => 'Milan', 'name_de' => 'Mailand'],
            ['country_code' => 'IT', 'name_en' => 'Venice', 'name_de' => 'Venedig'],
            
            // Spain
            ['country_code' => 'ES', 'name_en' => 'Madrid', 'name_de' => 'Madrid'],
            ['country_code' => 'ES', 'name_en' => 'Barcelona', 'name_de' => 'Barcelona'],
            
            // Netherlands
            ['country_code' => 'NL', 'name_en' => 'Amsterdam', 'name_de' => 'Amsterdam'],
            
            // USA
            ['country_code' => 'US', 'name_en' => 'New York', 'name_de' => 'New York'],
            
            // Japan
            ['country_code' => 'JP', 'name_en' => 'Tokyo', 'name_de' => 'Tokio'],
        ];

        foreach ($cities as $cityData) {
            $country = Country::where('code', $cityData['country_code'])->first();
            
            if ($country) {
                City::create([
                    'country_id' => $country->id,
                    'name_en' => $cityData['name_en'],
                    'name_de' => $cityData['name_de'],
                ]);
            }
        }
    }
}
