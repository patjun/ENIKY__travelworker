<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Erstelle Berechtigungen
        $permissions = [
            // Content Management
            'view posts',
            'create posts',
            'edit posts',
            'delete posts',
            'view pages',
            'create pages',
            'edit pages',
            'delete pages',
            'view listicles',
            'create listicles',
            'edit listicles',
            'delete listicles',
            
            // Places Management
            'view attractions',
            'create attractions',
            'edit attractions',
            'delete attractions',
            'view cities',
            'create cities',
            'edit cities',
            'delete cities',
            'view countries',
            'create countries',
            'edit countries',
            'delete countries',
            
            // Settings
            'view settings',
            'edit settings',
            'manage users',
            'manage roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Erstelle Rollen
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $editor = Role::firstOrCreate(['name' => 'editor']);
        $author = Role::firstOrCreate(['name' => 'author']);

        // Weise Berechtigungen zu
        // Super Admin hat alle Berechtigungen
        $superAdmin->givePermissionTo(Permission::all());

        // Admin hat fast alle Berechtigungen außer Rollenverwaltung
        $admin->givePermissionTo([
            'view posts', 'create posts', 'edit posts', 'delete posts',
            'view pages', 'create pages', 'edit pages', 'delete pages',
            'view listicles', 'create listicles', 'edit listicles', 'delete listicles',
            'view attractions', 'create attractions', 'edit attractions', 'delete attractions',
            'view cities', 'create cities', 'edit cities', 'delete cities',
            'view countries', 'create countries', 'edit countries', 'delete countries',
            'view settings', 'edit settings', 'manage users',
        ]);

        // Editor kann Inhalte bearbeiten, aber nicht löschen
        $editor->givePermissionTo([
            'view posts', 'create posts', 'edit posts',
            'view pages', 'create pages', 'edit pages',
            'view listicles', 'create listicles', 'edit listicles',
            'view attractions', 'create attractions', 'edit attractions',
            'view cities', 'create cities', 'edit cities',
            'view countries', 'create countries', 'edit countries',
        ]);

        // Author kann nur eigene Inhalte erstellen und bearbeiten
        $author->givePermissionTo([
            'view posts', 'create posts', 'edit posts',
            'view pages', 'create pages', 'edit pages',
            'view listicles', 'create listicles', 'edit listicles',
            'view attractions', 'create attractions', 'edit attractions',
            'view cities', 'view countries',
        ]);

        // Weise dem ersten User die Super-Admin-Rolle zu
        $user = User::where('email', 'jungbluth@eniky.com')->first();
        if ($user) {
            $user->assignRole('super_admin');
        }
    }
}

