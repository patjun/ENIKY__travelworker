<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_seeder_creates_locations(): void
    {
        // First seed countries and cities as locations depend on them
        $this->seed(CountrySeeder::class);
        $this->seed(CitySeeder::class);
        
        // Verify cities exist before seeding locations
        $this->assertDatabaseHas('cities', ['name_de' => 'Berlin']);
        $this->assertDatabaseHas('cities', ['name_de' => 'München']);
        $this->assertDatabaseHas('cities', ['name_de' => 'Wien']);
        
        // Seed locations
        $this->seed(LocationSeeder::class);
        
        // Verify locations were created
        $this->assertDatabaseHas('locations', [
            'name' => 'Brandenburg Gate Hotel',
            'street' => 'Unter den Linden 77',
            'zip' => '10117',
        ]);
        
        $this->assertDatabaseHas('locations', [
            'name' => 'Marienplatz Hotel',
            'street' => 'Marienplatz 8',
            'zip' => '80331',
        ]);
        
        $this->assertDatabaseHas('locations', [
            'name' => 'Hotel Sacher Wien',
            'street' => 'Philharmoniker Str. 4',
            'zip' => '1010',
        ]);
        
        // Verify relationships work correctly
        $berlinHotel = Location::where('name', 'Brandenburg Gate Hotel')->first();
        $this->assertNotNull($berlinHotel);
        $this->assertNotNull($berlinHotel->city);
        $this->assertEquals('Berlin', $berlinHotel->city->name_de);
        
        // Verify English fields are populated
        $this->assertNotNull($berlinHotel->en_name);
        $this->assertNotNull($berlinHotel->en_description);
        $this->assertNotNull($berlinHotel->en_category);
        $this->assertEquals('Brandenburg Gate Hotel', $berlinHotel->en_name);
        $this->assertEquals('Luxury hotel near Brandenburg Gate in the heart of Berlin', $berlinHotel->en_description);
        $this->assertEquals('Hotel', $berlinHotel->en_category);
        
        // Verify we have the expected number of locations
        $expectedLocationsCount = 11; // Based on the seeder data
        $this->assertEquals($expectedLocationsCount, Location::count());
    }
    
    public function test_location_seeder_handles_missing_cities_gracefully(): void
    {
        // Run seeder without cities - should not create any locations
        $this->seed(LocationSeeder::class);
        
        $this->assertEquals(0, Location::count());
    }
}
