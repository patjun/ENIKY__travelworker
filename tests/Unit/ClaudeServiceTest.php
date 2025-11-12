<?php

namespace Tests\Unit;

use App\Models\AiSetting;
use App\Models\Attraction;
use App\Models\AttractionBlock;
use App\Models\ContentBlock;
use App\Models\Listicle;
use App\Services\ClaudeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClaudeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.anthropic.api_key' => 'test-api-key',
            'ai.anthropic.base_url' => 'https://api.anthropic.com/v1',
            'ai.anthropic.timeout' => 60,
        ]);
    }
    
    protected function tearDown(): void
    {
        // Clean up after each test
        AiSetting::query()->delete();
        parent::tearDown();
    }

    public function test_it_generates_intro_successfully(): void
    {
        AiSetting::factory()->create([
            'prompt_de' => 'Generate intro for {title} with locations: {locations}',
            'model' => 'claude-haiku-4-5',
            'max_tokens' => 1000,
            'temperature' => 0.7,
        ]);

        $listicle = Listicle::factory()->create([
            'title_de' => 'Top 10 Destinations',
        ]);

        $attraction = Attraction::factory()->create(['name' => 'Paris']);
        $attractionBlock = AttractionBlock::factory()->create([
            'attraction_id' => $attraction->id,
        ]);
        ContentBlock::create([
            'blockable_type' => AttractionBlock::class,
            'blockable_id' => $attractionBlock->id,
            'listicle_id' => $listicle->id,
            'language' => 'de',
            'order' => 1,
        ]);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => 'This is a generated intro text.']
                ],
            ], 200),
        ]);

        $service = new ClaudeService();
        $result = $service->generateIntro('de', $listicle);

        $this->assertEquals('This is a generated intro text.', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.anthropic.com/v1/messages'
                && $request['model'] === 'claude-haiku-4-5'
                && $request['max_tokens'] === 1000
                && $request['temperature'] === 0.7;
        });
    }

    public function test_it_throws_exception_when_api_key_is_missing(): void
    {
        config(['ai.anthropic.api_key' => null]);

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessageMatches('/Anthropic API key|Cannot assign null/');

        new ClaudeService();
    }

    public function test_it_handles_api_error_with_retry(): void
    {
        AiSetting::factory()->create();
        $listicle = Listicle::factory()->create();

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([], 500)
                ->push([], 500)
                ->push([
                    'content' => [
                        ['text' => 'Success after retries']
                    ],
                ], 200),
        ]);

        $service = new ClaudeService();
        $result = $service->generateIntro('de', $listicle);

        $this->assertEquals('Success after retries', $result);
    }

    public function test_it_renders_prompt_with_placeholders(): void
    {
        // Ensure clean slate and create with custom prompt
        AiSetting::query()->delete();
        
        AiSetting::factory()->create([
            'id' => 1,
            'prompt_de' => 'Title: {title}, Locations: {locations}, Language: {language}',
            'prompt_en' => AiSetting::getDefaultPromptEn(),
            'model' => 'claude-haiku-4-5',
            'max_tokens' => 1000,
            'temperature' => 0.7,
        ]);

        $listicle = Listicle::factory()->create([
            'title_de' => 'Best Places',
        ]);

        $attraction1 = Attraction::factory()->create(['name' => 'Berlin']);
        $attraction2 = Attraction::factory()->create(['name' => 'Munich']);

        $attractionBlock1 = AttractionBlock::factory()->create(['attraction_id' => $attraction1->id]);
        $attractionBlock2 = AttractionBlock::factory()->create(['attraction_id' => $attraction2->id]);

        ContentBlock::create([
            'blockable_type' => AttractionBlock::class,
            'blockable_id' => $attractionBlock1->id,
            'listicle_id' => $listicle->id,
            'language' => 'de',
            'order' => 1,
        ]);
        ContentBlock::create([
            'blockable_type' => AttractionBlock::class,
            'blockable_id' => $attractionBlock2->id,
            'listicle_id' => $listicle->id,
            'language' => 'de',
            'order' => 2,
        ]);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => 'Generated text']
                ],
            ], 200),
        ]);

        $service = new ClaudeService();
        $service->generateIntro('de', $listicle);

        Http::assertSent(function ($request) {
            $prompt = $request['messages'][0]['content'];
            return str_contains($prompt, 'Best Places')
                && str_contains($prompt, 'Berlin, Munich')
                && str_contains($prompt, 'DE');
        });
    }
}
