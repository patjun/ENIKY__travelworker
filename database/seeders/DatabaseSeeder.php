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
    }
}
