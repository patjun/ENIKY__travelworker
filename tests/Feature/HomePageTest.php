<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_loads_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('welcome');
    }

    public function test_home_page_contains_login_link(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Log in');
    }
}
