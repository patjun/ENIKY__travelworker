<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::firstOrCreate(
            ['email' => 'jungbluth@eniky.com'],
            [
                'name' => 'Patrick Jungbluth',
                'email_verified_at' => now(),
                'password' => bcrypt('abcd1234'),
            ]
        );

        // Seed roles and permissions first
        $this->call([
            RolePermissionSeeder::class,
        ]);

        // Seed test users for each role
        $this->call([
            TestUserSeeder::class,
        ]);

        // Seed countries first as cities depend on them, then attractions depend on cities
        $this->call([
            AccessibilityAttributeSeeder::class,
            CountrySeeder::class,
            CitySeeder::class,
            AttractionSeeder::class,
            ListicleSeeder::class,
        ]);

    }
}
