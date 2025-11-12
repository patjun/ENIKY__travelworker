<?php

namespace Tests\Feature;

use App\Filament\Pages\AiSettings;
use App\Models\AiSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AiSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        \Filament\Facades\Filament::setCurrentPanel(
            \Filament\Facades\Filament::getPanel('admin')
        );
    }
    
    protected function tearDown(): void
    {
        // Clean up after each test
        AiSetting::query()->delete();
        parent::tearDown();
    }

    public function test_ai_settings_page_is_accessible(): void
    {
        Livewire::test(AiSettings::class)
            ->assertSuccessful();
    }

    public function test_it_loads_existing_settings(): void
    {
        // Ensure clean slate and create the record with id=1 that getInstance() expects
        AiSetting::query()->delete();
        
        $setting = AiSetting::factory()->create([
            'id' => 1,
            'prompt_de' => 'Test German prompt',
            'prompt_en' => 'Test English prompt',
            'model' => 'claude-haiku-4-5',
            'max_tokens' => 1500,
            'temperature' => 0.8,
        ]);

        // Ensure the record is persisted and fresh
        $setting->refresh();

        Livewire::test(AiSettings::class)
            ->assertFormSet([
                'prompt_de' => 'Test German prompt',
                'prompt_en' => 'Test English prompt',
                'model' => 'claude-haiku-4-5',
                'max_tokens' => 1500,
                'temperature' => 0.8,
            ]);
    }

    public function test_it_can_save_settings(): void
    {
        AiSetting::factory()->create();

        Livewire::test(AiSettings::class)
            ->fillForm([
                'prompt_de' => 'Updated German prompt',
                'prompt_en' => 'Updated English prompt',
                'model' => 'claude-3-opus-20240229',
                'max_tokens' => 2000,
                'temperature' => 0.5,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('ai_settings', [
            'prompt_de' => 'Updated German prompt',
            'prompt_en' => 'Updated English prompt',
            'model' => 'claude-3-opus-20240229',
            'max_tokens' => 2000,
            'temperature' => 0.5,
        ]);
    }

    public function test_it_validates_required_fields(): void
    {
        AiSetting::factory()->create();

        Livewire::test(AiSettings::class)
            ->fillForm([
                'prompt_de' => null,
                'prompt_en' => null,
                'model' => null,
                'max_tokens' => null,
                'temperature' => null,
            ])
            ->call('save')
            ->assertHasFormErrors(['prompt_de', 'prompt_en', 'model', 'max_tokens', 'temperature']);
    }

    public function test_it_validates_numeric_fields(): void
    {
        AiSetting::factory()->create();

        Livewire::test(AiSettings::class)
            ->fillForm([
                'max_tokens' => 'not-a-number',
                'temperature' => 'not-a-number',
            ])
            ->call('save')
            ->assertHasFormErrors(['max_tokens', 'temperature']);
    }

    public function test_it_validates_min_max_values(): void
    {
        AiSetting::factory()->create();

        Livewire::test(AiSettings::class)
            ->fillForm([
                'max_tokens' => 50,
                'temperature' => 1.5,
            ])
            ->call('save')
            ->assertHasFormErrors(['max_tokens', 'temperature']);
    }
}
