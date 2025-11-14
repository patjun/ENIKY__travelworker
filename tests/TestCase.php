<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Erstellt einen Benutzer mit der angegebenen Rolle.
     * Führt automatisch den RolePermissionSeeder aus, falls noch nicht geschehen.
     */
    protected function createUserWithRole(string $role = 'admin'): User
    {
        // Prüfe, ob Rollen bereits existieren
        if (!\Spatie\Permission\Models\Role::where('name', $role)->exists()) {
            $this->seed(RolePermissionSeeder::class);
        }

        $user = User::factory()->create();
        $user->assignRole($role);
        
        return $user;
    }
}
