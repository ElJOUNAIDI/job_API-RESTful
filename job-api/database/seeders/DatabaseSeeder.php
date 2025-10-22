<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Créer les rôles
        $adminRole = Role::create(['name' => 'admin']);
        $employerRole = Role::create(['name' => 'employer']);
        $candidateRole = Role::create(attributes: ['name' => 'candidate']);

        // Créer les permissions
        $permissions = [
            'manage_users',
            'manage_jobs',
            'manage_applications',
            'view_statistics',
            'create_job',
            'edit_job',
            'delete_job',
            'view_applications',
            'apply_job',
            'view_favorites'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assigner les permissions aux rôles
        $adminRole->givePermissionTo(Permission::all());
        
        $employerRole->givePermissionTo([
            'create_job',
            'edit_job', 
            'delete_job',
            'view_applications'
        ]);

        $candidateRole->givePermissionTo([
            'apply_job',
            'view_favorites'
        ]);

        // Créer un utilisateur admin
        $admin = \App\Models\User::create([
            'name' => 'Admin',
            'email' => 'admin@jobplatform.com',
            'password' => bcrypt('password'),
            'type' => 'admin'
        ]);
        $admin->assignRole('admin');
    }
}