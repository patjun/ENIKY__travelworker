<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Route;

class AuthenticationRedirectTest extends TestCase
{
    public function test_filament_login_route_exists(): void
    {
        $this->assertTrue(Route::has('filament.admin.auth.login'));
    }

    public function test_login_route_does_not_exist(): void
    {
        $this->assertFalse(Route::has('login'));
    }
}
