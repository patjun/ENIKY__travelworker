<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentPage>
 */
class ContentPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titleDe = fake()->sentence(3);
        $titleEn = fake()->sentence(3);

        return [
            'title_de' => $titleDe,
            'title_en' => $titleEn,
            'slug_de' => \Illuminate\Support\Str::slug($titleDe),
            'slug_en' => \Illuminate\Support\Str::slug($titleEn),
            'intro_de' => '<p>' . fake()->paragraph(3) . '</p>',
            'intro_en' => '<p>' . fake()->paragraph(3) . '</p>',
            'meta_description_de' => fake()->text(150),
            'meta_description_en' => fake()->text(150),
            'status' => fake()->randomElement(['draft', 'published']),
            'published_at' => fake()->optional(0.7)->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
