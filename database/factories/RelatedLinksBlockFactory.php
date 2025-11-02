<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RelatedLinksBlock>
 */
class RelatedLinksBlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $links = [];
        $count = fake()->numberBetween(2, 5);

        for ($i = 0; $i < $count; $i++) {
            $links[] = [
                'title' => fake()->sentence(3),
                'url' => fake()->url(),
            ];
        }

        return [
            'title' => 'Das könnte Dich auch interessieren',
            'links' => $links,
        ];
    }
}
