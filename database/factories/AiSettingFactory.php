<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiSetting>
 */
class AiSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prompt_de' => \App\Models\AiSetting::getDefaultPromptDe(),
            'prompt_en' => \App\Models\AiSetting::getDefaultPromptEn(),
            'model' => 'claude-haiku-4-5',
            'max_tokens' => 1000,
            'temperature' => 0.7,
        ];
    }
}
