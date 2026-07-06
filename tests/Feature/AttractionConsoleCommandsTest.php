<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AttractionConsoleCommandsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * These commands previously referenced the removed App\Models\Location class
     * and fatally failed with "Class \"App\Models\Location\" not found" as soon as
     * the model was autoloaded. Running each command against an empty database
     * proves the class now resolves (to App\Models\Attraction) without a fatal error.
     *
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function attractionCommandProvider(): array
    {
        return [
            'attractions:sync-wordpress' => ['attractions:sync-wordpress', ['--language' => 'de']],
            'dataforseo:map-existing-data' => ['dataforseo:map-existing-data', []],
            'dataforseo:check-pending' => ['dataforseo:check-pending', []],
            'locations:update-outdated' => ['locations:update-outdated', ['--force' => true]],
            'locations:regenerate-widgets' => ['locations:regenerate-widgets', []],
            'location:update-business-data' => ['location:update-business-data', []],
        ];
    }

    /**
     * @dataProvider attractionCommandProvider
     *
     * @param  array<string, mixed>  $parameters
     */
    public function test_command_resolves_attraction_model_and_runs(string $command, array $parameters): void
    {
        Queue::fake();

        $this->artisan($command, $parameters)->assertSuccessful();
    }
}
