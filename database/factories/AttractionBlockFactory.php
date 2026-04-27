<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttractionBlock>
 */
class AttractionBlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attraction_id' => \App\Models\Attraction::factory(),
            'custom_intro' => '<p>' . fake()->paragraph() . '</p>',
        ];
    }
}
