<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // Create default organizations
        $intelligentb2b = Organization::create(['name' => 'Intelligentb2b']);
        $protectaGroup = Organization::create(['name' => 'Protecta Group']);

        // Create Demo Users
        $admin = User::create([
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'admin@guard.com',
            'password' => Hash::make('password123'),
            'organization_id' => $intelligentb2b->id,
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        $users = [
            ['first_name' => 'Protecta', 'last_name' => 'Director', 'email' => 'director@protecta.com', 'role' => 'Group Director'],
            ['first_name' => 'Partner', 'last_name' => 'Director', 'email' => 'partner@example.com', 'role' => 'Partner Director'],
            ['first_name' => 'Coordinator', 'last_name' => 'User', 'email' => 'coordinator@guard.com', 'role' => 'Coordinator'],
            ['first_name' => 'Sales', 'last_name' => 'Manager', 'email' => 'manager1@guard.com', 'role' => 'Sales Manager'],
            ['first_name' => 'Sales', 'last_name' => 'Agent 1', 'email' => 'agent1@guard.com', 'role' => 'Sales Agent'],
            ['first_name' => 'Sales', 'last_name' => 'Agent 2', 'email' => 'agent2@guard.com', 'role' => 'Sales Agent'],
            ['first_name' => 'Referral', 'last_name' => 'User', 'email' => 'referral1@partner.com', 'role' => 'Referral'],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
                'password' => Hash::make('password123'),
                'organization_id' => $protectaGroup->id,
                'is_active' => true,
            ]);
            $user->assignRole($userData['role']);
        }
    }
}
