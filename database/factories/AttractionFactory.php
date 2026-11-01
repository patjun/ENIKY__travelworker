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
            'en_name' => fake()->company(),
            'street' => fake()->streetAddress(),
            'zip' => fake()->postcode(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'email' => fake()->safeEmail(),
            'website' => fake()->url(),
            'en_website' => fake()->url(),
            'description' => fake()->paragraph(),
            'en_description' => fake()->paragraph(),
            'category' => fake()->word(),
            'en_category' => fake()->word(),
            'rating_value' => fake()->randomFloat(1, 3.0, 5.0),
            'rating_votes_count' => fake()->numberBetween(10, 500),
            'place_id' => 'ChIJ' . fake()->bothify('??????????'),
            'manual_opening_hours' => $this->generateSampleOpeningHours(),
            'contact_info_html' => '<div>Contact info widget</div>',
            'rating_html' => '<div>Rating widget</div>',
            'opening_hours_html' => '<div>Opening hours widget</div>',
            'accessibility_html' => '<div>Accessibility widget</div>',
            'en_contact_info_html' => '<div>Contact info widget</div>',
            'en_rating_html' => '<div>Rating widget</div>',
            'en_opening_hours_html' => '<div>Opening hours widget</div>',
            'en_accessibility_html' => '<div>Accessibility widget</div>',
        ];
    }

    /**
     * Two seasons with one price row each, in EUR.
     */
    public function withPrices(): static
    {
        return $this->state(fn (): array => [
            'price_currency' => 'EUR',
            'prices' => [
                [
                    'name_de' => 'Wintersaison',
                    'name_en' => 'Winter season',
                    'start_date' => '11-01',
                    'end_date' => '02-28',
                    'rows' => [
                        ['id' => 'row-winter-adult', 'label_de' => 'Erwachsene', 'label_en' => 'Adults', 'amount' => 6, 'is_free' => false],
                    ],
                ],
                [
                    'name_de' => 'Sommersaison',
                    'name_en' => 'Summer season',
                    'start_date' => '03-01',
                    'end_date' => '10-31',
                    'rows' => [
                        ['id' => 'row-summer-adult', 'label_de' => 'Erwachsene', 'label_en' => 'Adults', 'amount' => 8, 'is_free' => false],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Generate sample opening hours with seasonal structure
     */
    private function generateSampleOpeningHours(): array
    {
        $variants = [
            // Year-round only
            [
                [
                    'name' => null,
                    'is_year_round' => true,
                    'start_date' => null,
                    'end_date' => null,
                    'time_slots' => [
                        [
                            'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                            'open_time' => '09:00',
                            'close_time' => '18:00',
                        ],
                        [
                            'days' => ['saturday'],
                            'open_time' => '10:00',
                            'close_time' => '16:00',
                        ],
                    ],
                ],
            ],
            // Multiple seasons
            [
                [
                    'name' => 'Winter Season',
                    'is_year_round' => false,
                    'start_date' => '11-01',
                    'end_date' => '03-31',
                    'time_slots' => [
                        [
                            'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                            'open_time' => '09:00',
                            'close_time' => '17:00',
                        ],
                    ],
                ],
                [
                    'name' => 'Summer Season',
                    'is_year_round' => false,
                    'start_date' => '04-01',
                    'end_date' => '10-31',
                    'time_slots' => [
                        [
                            'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                            'open_time' => '08:00',
                            'close_time' => '20:00',
                        ],
                    ],
                ],
            ],
        ];

        return fake()->randomElement($variants);
    }
}
