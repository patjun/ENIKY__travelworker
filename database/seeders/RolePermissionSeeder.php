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
            'edit own attractions',
            'delete attractions',
            'view cities',
            'create cities',
            'edit cities',
            'edit own cities',
            'delete cities',
            'view countries',
            'create countries',
            'edit countries',
            'delete countries',
            'view accessibility_attributes',
            'create accessibility_attributes',
            'edit accessibility_attributes',
            'delete accessibility_attributes',
            
            // Keywords & Changes
            'view keywords',
            'manage keywords',
            'view changes',
            'manage changes',
            
            // Settings
            'view settings',
            'edit settings',
            'view ai_settings',
            'edit ai_settings',
            'view backups',
            'manage backups',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',
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
        $attractionsAuthor = Role::firstOrCreate(['name' => 'attractions-author']);

        // Weise Berechtigungen zu
        // Super Admin hat alle Berechtigungen
        // syncPermissions stellt sicher, dass nur die gewünschten Berechtigungen zugewiesen sind
        $superAdmin->syncPermissions(Permission::all());

        // Admin hat fast alle Berechtigungen außer Rollenverwaltung
        $admin->syncPermissions([
            'view posts', 'create posts', 'edit posts', 'delete posts',
            'view pages', 'create pages', 'edit pages', 'delete pages',
            'view listicles', 'create listicles', 'edit listicles', 'delete listicles',
            'view attractions', 'create attractions', 'edit attractions', 'edit own attractions', 'delete attractions',
            'view cities', 'create cities', 'edit cities', 'edit own cities', 'delete cities',
            'view countries', 'create countries', 'edit countries', 'delete countries',
            'view accessibility_attributes', 'create accessibility_attributes', 'edit accessibility_attributes', 'delete accessibility_attributes',
            'view keywords', 'manage keywords',
            'view changes', 'manage changes',
            'view settings', 'edit settings',
            'view ai_settings', 'edit ai_settings',
            'view backups', 'manage backups',
            'view users', 'create users', 'edit users', 'delete users',
            'manage users',
        ]);

        // Editor kann Inhalte bearbeiten, aber nicht löschen
        $editor->syncPermissions([
            'view posts', 'create posts', 'edit posts',
            'view pages', 'create pages', 'edit pages',
            'view listicles', 'create listicles', 'edit listicles',
            'view attractions', 'create attractions', 'edit attractions', 'edit own attractions',
            'view cities', 'create cities', 'edit cities', 'edit own cities',
            'view countries', 'create countries', 'edit countries',
            'view accessibility_attributes', 'create accessibility_attributes', 'edit accessibility_attributes',
            'view keywords', 'manage keywords',
            'view changes', 'manage changes',
            'view ai_settings',
        ]);

        // Author kann nur eigene Listicles erstellen und bearbeiten
        $author->syncPermissions([
            'view posts', 'create posts', 'edit posts',
            'view pages', 'create pages', 'edit pages',
            'view listicles', 'create listicles', 'edit listicles',
            'view cities',
            'view countries',
            'view accessibility_attributes',
        ]);

        // Attractions Author kann nur eigene Attractions erstellen und bearbeiten
        $attractionsAuthor->syncPermissions([
            'view attractions', 'create attractions', 'edit own attractions',
            'view cities',
            'view countries',
            'view accessibility_attributes',
        ]);

        // Weise dem ersten User die Super-Admin-Rolle zu
        $user = User::where('email', 'jungbluth@eniky.com')->first();
        if ($user) {
            $user->assignRole('super_admin');
        }
    }
}

