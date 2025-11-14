<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Definiere alle Benutzergruppen
        $roles = ['super_admin', 'admin', 'editor', 'author', 'attractions-author'];

        foreach ($roles as $roleName) {
            // Prüfe ob die Rolle existiert
            $role = Role::where('name', $roleName)->first();
            
            if (!$role) {
                $this->command->warn("Rolle '{$roleName}' existiert nicht. Überspringe...");
                continue;
            }

            // Erstelle den Benutzernamen im Format "User[Benutzergruppe]"
            // Konvertiere z.B. "super_admin" zu "UserSuperAdmin"
            $userName = 'User' . $this->formatRoleName($roleName);
            
            // Erstelle oder aktualisiere den User
            $user = User::firstOrCreate(
                ['email' => "{$roleName}@eniky.net"],
                [
                    'name' => $userName,
                    'password' => Hash::make('abcd1234'),
                    'email_verified_at' => now(),
                ]
            );

            // Aktualisiere den Namen falls der User bereits existiert
            if ($user->name !== $userName) {
                $user->update(['name' => $userName]);
            }

            // Weise die Rolle zu (falls noch nicht zugewiesen)
            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
                $this->command->info("User '{$userName}' ({$roleName}@eniky.net) erstellt/aktualisiert mit Rolle '{$roleName}'");
            } else {
                $this->command->info("User '{$userName}' ({$roleName}@eniky.net) existiert bereits mit Rolle '{$roleName}'");
            }
        }
    }

    /**
     * Formatiert den Rollennamen für den Benutzernamen
     * z.B. "super_admin" -> "SuperAdmin", "attractions-author" -> "AttractionsAuthor"
     */
    private function formatRoleName(string $roleName): string
    {
        // Ersetze Unterstriche und Bindestriche durch Leerzeichen
        $formatted = str_replace(['_', '-'], ' ', $roleName);
        
        // Konvertiere zu Title Case (jedes Wort beginnt mit Großbuchstaben)
        $formatted = ucwords($formatted);
        
        // Entferne Leerzeichen
        return str_replace(' ', '', $formatted);
    }
}

