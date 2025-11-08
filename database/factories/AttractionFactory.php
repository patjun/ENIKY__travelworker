<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attraction>
 */
class AttractionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'city_id' => \App\Models\City::factory(),
            'name' => fake()->company(),
            'street' => fake()->streetAddress(),
            'zip' => fake()->postcode(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'website' => fake()->url(),
            'description' => fake()->paragraph(),
            'category' => fake()->word(),
            'rating_value' => fake()->randomFloat(1, 3.0, 5.0),
            'rating_votes_count' => fake()->numberBetween(10, 500),
            'contact_info_html' => '<div>Contact info widget</div>',
            'rating_html' => '<div>Rating widget</div>',
            'opening_hours_html' => '<div>Opening hours widget</div>',
            'accessibility_html' => '<div>Accessibility widget</div>',
        ];
    }
}
