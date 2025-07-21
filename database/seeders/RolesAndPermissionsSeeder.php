<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define Permissions using a simple resource.action convention
        $permissions = [
            'users.create', 'users.view', 'users.update', 'users.delete',
            'organizations.create', 'organizations.view', 'organizations.update',
            'leads.create', 'leads.view', 'leads.assign', 'leads.accept-decline', 'leads.update-status',
            'reports.view'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // --- ROLE DEFINITIONS ---

        // Admin: Has all permissions
        Role::create(['name' => 'Admin'])->givePermissionTo(Permission::all());

        // Group Director: Has all permissions (similar to Admin for business logic)
        Role::create(['name' => 'Group Director'])->givePermissionTo(Permission::all());

        // Partner Director: Manages their own organization and users
        Role::create(['name' => 'Partner Director'])->givePermissionTo([
            'organizations.view', 'organizations.update',
            'users.create', 'users.view', 'users.update',
            'reports.view'
        ]);

        // Coordinator: Manages incoming leads and assignment
        Role::create(['name' => 'Coordinator'])->givePermissionTo([
            'leads.view',
            'leads.assign'
        ]);

        // Sales Manager: Manages a team of agents and their leads
        Role::create(['name' => 'Sales Manager'])->givePermissionTo([
            'users.create', 'users.view',
            'leads.view', 'leads.assign', 'leads.accept-decline',
            'reports.view'
        ]);

        // Sales Agent: Manages leads assigned to them
        Role::create(['name' => 'Sales Agent'])->givePermissionTo([
            'leads.view',
            'leads.accept-decline',
            'leads.update-status'
        ]);

        // Referral: Can create leads and view their own
        Role::create(['name' => 'Referral'])->givePermissionTo([
            'leads.create',
            'leads.view'
        ]);
    }
}
