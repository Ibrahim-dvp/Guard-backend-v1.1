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
            'organizations.create', 'organizations.view', 'organizations.update', 'organizations.delete',
            'leads.create', 'leads.view', 'leads.assign', 'leads.accept-decline', 'leads.update-status','leads.delete',
            'teams.create', 'teams.view', 'teams.update', 'teams.delete', 'teams.manage_members',
            'appointments.create', 'appointments.view', 'appointments.update', 'appointments.delete',
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
            'organizations.view', 'organizations.update', 'organizations.delete',
            'users.create', 'users.view', 'users.update',
            'teams.create', 'teams.view', 'teams.update', 'teams.delete', 'teams.manage_members',
            'appointments.view', 'appointments.create', 'appointments.update', 'appointments.delete',
            'reports.view'
        ]);

        // Coordinator: Manages incoming leads and assignment
        Role::create(['name' => 'Coordinator'])->givePermissionTo([
            'organizations.view',
            'teams.view',
            'leads.view',
            'leads.assign',
            'appointments.view', 'appointments.create'
        ]);

        // Sales Manager: Manages a team of agents and their leads
        Role::create(['name' => 'Sales Manager'])->givePermissionTo([
            'organizations.view',
            'users.create', 'users.view',
            'teams.manage_members',
            'leads.view', 'leads.assign', 'leads.accept-decline', 'leads.update-status',
            'appointments.view', 'appointments.create', 'appointments.update', 'appointments.delete',
            'reports.view'
        ]);

        // Sales Agent: Manages leads assigned to them
        Role::create(['name' => 'Sales Agent'])->givePermissionTo([
            'organizations.view',
            'teams.view',
            'leads.view',
            'leads.accept-decline',
            'leads.update-status',
            'appointments.view', 'appointments.create', 'appointments.update'
        ]);

        // Referral: Can create leads and view their own
        Role::create(['name' => 'Referral'])->givePermissionTo([
            'organizations.view',
            'teams.view',
            'leads.create',
            'leads.view',
            'appointments.view'
        ]);
    }
}
