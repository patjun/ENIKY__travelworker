<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Auth\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(
            Filament::getPanel('admin')
        );
    }

    public function test_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_login_route_redirects_to_filament_login(): void
    {
        $this->get('/login')->assertRedirect('/admin/login');
    }

    public function test_user_with_role_can_log_in(): void
    {
        $user = $this->createUserWithRole('admin');

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = $this->createUserWithRole('admin');

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_user_without_role_cannot_log_in(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }
}
