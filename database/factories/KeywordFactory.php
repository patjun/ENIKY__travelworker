<?php

namespace Database\Factories;

use App\Models\Keyword;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Keyword>
 */
class KeywordFactory extends Factory
{
    protected $model = Keyword::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'keyword' => fake()->words(2, true),
            'date' => fake()->date(),
            'post_id' => null,
            'task_post_output' => null,
            'task_id' => null,
            'task_get_output' => null,
            'location_code' => fake()->countryCode(),
            'language_code' => fake()->languageCode(),
            'search_partners' => false,
            'competition' => (string) fake()->randomFloat(2, 0, 1),
            'competition_index' => fake()->numberBetween(0, 100),
            'search_volume' => fake()->numberBetween(0, 10000),
            'low_top_of_page_bid' => fake()->randomFloat(2, 0, 10),
            'high_top_of_page_bid' => fake()->randomFloat(2, 0, 10),
            'cpc' => fake()->randomFloat(2, 0, 5),
            'monthly_searches' => null,
            'keyword_annotations' => null,
            'is_processed' => false,
        ];
    }
}

