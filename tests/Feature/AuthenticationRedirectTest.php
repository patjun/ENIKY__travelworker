<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_login_route_exists(): void
    {
        $this->assertTrue(Route::has('filament.admin.auth.login'));
    }

    public function test_login_route_exists(): void
    {
        $this->assertTrue(Route::has('login'));
    }

    public function test_login_route_redirects_to_filament(): void
    {
        $response = $this->get('/login');

        $response->assertRedirect('/admin/login');
    }

    public function test_authentication_redirects_to_filament_login(): void
    {
        // Try to access admin area without authentication
        $response = $this->get('/admin');
        
        // Should redirect to login page
        $response->assertRedirect('/admin/login');
    }

    public function test_home_page_accessible_without_authentication(): void
    {
        // Home page should be accessible without authentication
        $response = $this->get('/');
        
        $response->assertStatus(200);
    }
}
