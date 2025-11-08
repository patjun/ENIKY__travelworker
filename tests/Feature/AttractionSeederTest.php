<?php

namespace Tests\Feature;

use App\Models\Attraction;
use App\Models\City;
use App\Models\Country;
use Database\Seeders\AttractionSeeder;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttractionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_attraction_seeder_creates_attractions(): void
    {
        // First seed countries and cities as attractions depend on them
        $this->seed(CountrySeeder::class);
        $this->seed(CitySeeder::class);

        // Verify cities exist before seeding attractions
        $this->assertDatabaseHas('cities', ['name_de' => 'Berlin']);
        $this->assertDatabaseHas('cities', ['name_de' => 'München']);
        $this->assertDatabaseHas('cities', ['name_de' => 'Wien']);

        // Seed attractions
        $this->seed(AttractionSeeder::class);

        // Verify attractions were created
        $this->assertDatabaseHas('attractions', [
            'name' => 'Brandenburg Gate Hotel',
            'street' => 'Unter den Linden 77',
            'zip' => '10117',
        ]);

        $this->assertDatabaseHas('attractions', [
            'name' => 'Marienplatz Hotel',
            'street' => 'Marienplatz 8',
            'zip' => '80331',
        ]);

        $this->assertDatabaseHas('attractions', [
            'name' => 'Hotel Sacher Wien',
            'street' => 'Philharmoniker Str. 4',
            'zip' => '1010',
        ]);

        // Verify relationships work correctly
        $berlinHotel = Attraction::where('name', 'Brandenburg Gate Hotel')->first();
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

        // Verify we have the expected number of attractions
        $expectedAttractionsCount = 11; // Based on the seeder data
        $this->assertEquals($expectedAttractionsCount, Attraction::count());
    }
    
    public function test_attraction_seeder_handles_missing_cities_gracefully(): void
    {
        // Run seeder without cities - should not create any attractions
        $this->seed(AttractionSeeder::class);

        $this->assertEquals(0, Attraction::count());
    }
}
