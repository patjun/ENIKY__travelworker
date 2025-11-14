<?php

namespace Database\Factories;

use App\Models\Change;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Change>
 */
class ChangeFactory extends Factory
{
    protected $model = Change::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'modification_date' => fake()->date(),
            'modification_description' => fake()->sentence(),
        ];
    }
}

