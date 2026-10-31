<?php

namespace Tests\Feature;

use App\Models\Attraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
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
     * @param  array<string, mixed>  $parameters
     */
    #[DataProvider('attractionCommandProvider')]
    public function test_command_resolves_attraction_model_and_runs(string $command, array $parameters): void
    {
        Queue::fake();

        $this->artisan($command, $parameters)->assertSuccessful();
    }

    public function test_sync_wordpress_command_syncs_existing_attractions(): void
    {
        config(['wordpress.de' => [
            'api_url' => 'https://wp.test/wp-json/attraction-manager/v1',
            'username' => 'admin',
            'password' => 'secret',
        ]]);
        Http::fake(['wp.test/*' => Http::response(['id' => 77], 201)]);
        $attraction = Attraction::factory()->create();

        $this->artisan('attractions:sync-wordpress', ['--language' => 'de'])->assertSuccessful();

        Http::assertSent(fn ($request): bool => $request->url() === 'https://wp.test/wp-json/attraction-manager/v1/attractions'
            && $request['city'] === $attraction->city->name_de
            && $request['country'] === $attraction->city->country->name_de);
        $this->assertSame(77, $attraction->fresh()->wp_de_id);
    }
}
