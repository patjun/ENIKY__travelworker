<?php

namespace Tests\Feature;

use App\Models\Attraction;
use App\Services\AttractionSyncService;
use App\Services\WordPressApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AttractionSyncFailureTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://wp.test/wp-json/attraction-manager/v1';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'wordpress.de' => ['api_url' => self::API, 'username' => 'admin', 'password' => 'secret'],
            'wordpress.en' => ['api_url' => 'https://en.wp.test/wp-json/attraction-manager/v1', 'username' => 'admin', 'password' => 'secret'],
        ]);
    }

    private function changedAttraction(int $wpId = 77, ?int $enWpId = null): Attraction
    {
        Carbon::setTestNow('2026-10-12 10:00:00');
        $attraction = Attraction::factory()->create();
        $attraction->wp_de_id = $wpId;
        $attraction->wp_de_last_sync = now();
        $attraction->wp_en_id = $enWpId;
        $attraction->wp_en_last_sync = $enWpId ? now() : null;
        $attraction->save();

        Carbon::setTestNow('2026-10-12 12:00:00');
        $attraction->name = 'Neuer Name '.$wpId;
        $attraction->save();

        return $attraction->fresh();
    }

    /**
     * @return array<string, array{0: int, 1: array<string, string>|string, 2: string}>
     */
    public static function apiErrorProvider(): array
    {
        return [
            'wrong credentials' => [401, ['code' => 'rest_forbidden'], 'HTTP 401 rest_forbidden'],
            'missing capability' => [403, ['code' => 'rest_forbidden'], 'HTTP 403 rest_forbidden'],
            'server error without json' => [500, '<html>Fatal error</html>', 'HTTP 500'],
            'plugin not active' => [404, ['code' => 'rest_no_route'], 'HTTP 404 rest_no_route'],
        ];
    }

    /**
     * @param  array<string, string>|string  $body
     */
    #[DataProvider('apiErrorProvider')]
    public function test_api_error_is_reported_with_its_reason_instead_of_as_deleted(int $status, array|string $body, string $reason): void
    {
        Http::fake(['wp.test/*' => Http::response($body, $status)]);
        $attraction = $this->changedAttraction();
        $service = new AttractionSyncService;

        $this->assertFalse($service->syncAttraction($attraction, 'de'));

        $this->assertSame($reason, $service->lastFailure()['reason']);
        Http::assertSentCount(1);
    }

    public function test_attraction_missing_in_wordpress_is_reported_as_deleted(): void
    {
        Http::fake(['wp.test/*' => Http::response(['code' => 'attraction_not_found'], 404)]);
        $attraction = $this->changedAttraction();
        $service = new AttractionSyncService;

        $this->assertFalse($service->syncAttraction($attraction, 'de'));

        $this->assertSame('Deleted in WordPress', $service->lastFailure()['reason']);
        $this->assertFalse($service->lastFailure()['is_login_error']);
        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'PUT');
    }

    public function test_network_failure_is_not_reported_as_deleted(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: timed out'));
        $attraction = $this->changedAttraction();
        $service = new AttractionSyncService;

        $this->assertFalse($service->syncAttraction($attraction, 'de'));

        $this->assertSame('cURL error 28: timed out', $service->lastFailure()['reason']);
        $this->assertFalse($service->lastFailure()['is_login_error']);
    }

    public function test_successful_status_with_unreadable_body_is_not_reported_as_deleted(): void
    {
        Http::fake(['wp.test/*' => Http::response('<html>Wartungsmodus</html>', 200)]);
        $attraction = $this->changedAttraction();
        $service = new AttractionSyncService;

        $this->assertFalse($service->syncAttraction($attraction, 'de'));

        $this->assertNotSame('Deleted in WordPress', $service->lastFailure()['reason']);
    }

    public function test_login_error_while_creating_is_recognized(): void
    {
        Http::fake(['wp.test/*' => Http::response(['code' => 'rest_forbidden'], 401)]);
        $attraction = Attraction::factory()->create();
        $service = new AttractionSyncService;

        $this->assertFalse($service->syncAttraction($attraction, 'de'));

        $this->assertSame('HTTP 401 rest_forbidden', $service->lastFailure()['reason']);
        $this->assertTrue($service->lastFailure()['is_login_error']);
    }

    public function test_successful_sync_clears_the_previous_failure(): void
    {
        Http::fake(['wp.test/*' => Http::sequence()
            ->push(['code' => 'rest_forbidden'], 401)
            ->push(['id' => 77], 200)
            ->push(['id' => 77], 200),
        ]);
        $attraction = $this->changedAttraction();
        $service = new AttractionSyncService;

        $this->assertFalse($service->syncAttraction($attraction, 'de'));
        $this->assertTrue($service->syncAttraction($attraction, 'de'));

        $this->assertNull($service->lastFailure());
    }

    /**
     * @return array<string, array{0: int, 1: array<string, string>|string, 2: string}>
     */
    public static function loginErrorProvider(): array
    {
        return [
            'wrong credentials' => [401, ['code' => 'rest_forbidden'], 'HTTP 401 rest_forbidden'],
            'missing capability' => [403, ['code' => 'rest_forbidden'], 'HTTP 403 rest_forbidden'],
        ];
    }

    /**
     * @param  array<string, string>|string  $body
     */
    #[DataProvider('loginErrorProvider')]
    public function test_command_stops_the_language_after_a_login_error(int $status, array|string $body, string $reason): void
    {
        Http::fake(['wp.test/*' => Http::response($body, $status)]);
        $this->changedAttraction(77);
        $this->changedAttraction(78);
        $this->changedAttraction(79);

        $this->artisan('attractions:sync-wordpress', ['--language' => 'de'])
            ->expectsOutputToContain("({$reason}), 2 locations skipped")
            ->assertFailed();

        Http::assertSentCount(1);
    }

    /**
     * @return array<string, array{0: int, 1: array<string, string>|string, 2: string}>
     */
    public static function singleAttractionErrorProvider(): array
    {
        return [
            'server error' => [500, '<html>Fatal error</html>', 'HTTP 500'],
            'firewall block' => [403, '<html>Blocked</html>', 'HTTP 403'],
        ];
    }

    /**
     * @param  array<string, string>|string  $body
     */
    #[DataProvider('singleAttractionErrorProvider')]
    public function test_command_continues_after_other_errors_and_prints_the_reason(int $status, array|string $body, string $reason): void
    {
        Http::fake(['wp.test/*' => Http::response($body, $status)]);
        $this->changedAttraction(77);
        $this->changedAttraction(78);

        $this->artisan('attractions:sync-wordpress', ['--language' => 'de'])
            ->expectsOutputToContain($reason)
            ->assertFailed();

        Http::assertSentCount(2);
    }

    public function test_login_error_in_one_language_does_not_stop_the_other_language(): void
    {
        Http::fake([
            'en.wp.test/*' => Http::response(['id' => 88], 200),
            'wp.test/*' => Http::response(['code' => 'rest_forbidden'], 401),
        ]);
        $this->changedAttraction(77, 88);

        $this->artisan('attractions:sync-wordpress', ['--language' => 'both'])->assertFailed();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_contains($request->url(), 'en.wp.test'));
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function listErrorProvider(): array
    {
        return [
            'wrong credentials' => [401],
            'server error' => [500],
        ];
    }

    #[DataProvider('listErrorProvider')]
    public function test_list_attractions_throws_instead_of_returning_an_empty_list(int $status): void
    {
        Http::fake(['wp.test/*' => Http::response(['code' => 'some_error'], $status)]);

        $this->expectException(RequestException::class);

        (new WordPressApiService('de'))->listAttractions();
    }

    public function test_list_attractions_network_failure_is_passed_on(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: timed out'));

        $this->expectException(ConnectionException::class);

        (new WordPressApiService('de'))->listAttractions();
    }
}
