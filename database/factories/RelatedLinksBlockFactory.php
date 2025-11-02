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
                'title_de' => fake()->sentence(3),
                'title_en' => fake()->sentence(3),
                'url' => fake()->url(),
            ];
        }

        return [
            'title_de' => 'Das könnte Dich auch interessieren',
            'title_en' => 'You might also be interested in',
            'links' => $links,
        ];
    }
}
