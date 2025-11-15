<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Definiere die Rollen für die Test-User
        $roles = ['super_admin', 'admin', 'editor', 'author', 'attractions-author'];

        foreach ($roles as $roleName) {
            // Prüfe ob die Rolle existiert
            $role = Role::where('name', $roleName)->first();
            
            if (!$role) {
                $this->command->warn("Rolle '{$roleName}' existiert nicht. Überspringe...");
                continue;
            }

            // Erstelle oder aktualisiere den Test-User
            $user = User::firstOrCreate(
                ['email' => "{$roleName}@eniky.net"],
                [
                    'name' => ucfirst(str_replace('_', ' ', $roleName)),
                    'password' => Hash::make('abcd1234'),
                    'email_verified_at' => now(),
                ]
            );

            // Weise die Rolle zu (falls noch nicht zugewiesen)
            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
                $this->command->info("Test-User '{$roleName}' erstellt/aktualisiert mit Rolle '{$roleName}'");
            } else {
                $this->command->info("Test-User '{$roleName}' existiert bereits mit Rolle '{$roleName}'");
            }
        }
    }
}




