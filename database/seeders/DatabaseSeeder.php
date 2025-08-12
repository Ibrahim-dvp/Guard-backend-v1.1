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
        $this->command->info('🌱 Starting database seeding...');

        // First, seed roles and permissions
        $this->call(RolesAndPermissionsSeeder::class);

        // Create default organizations
        $this->command->info('📦 Creating organizations...');
        $intelligentb2b = Organization::create(['name' => 'Intelligentb2b']);
        $protectaGroup = Organization::create(['name' => 'Protecta Group']);

        // Create Demo Users
        $this->command->info('👤 Creating users...');
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
            ['first_name' => 'Protecta', 'last_name' => 'Director', 'email' => 'director@protecta.com', 'role' => 'Group Director', 'organization' => $protectaGroup->id],
            ['first_name' => 'Coordinator', 'last_name' => 'User', 'email' => 'coordinator@guard.com', 'role' => 'Coordinator', 'organization' => $protectaGroup->id],
            ['first_name' => 'Sales', 'last_name' => 'Manager', 'email' => 'manager1@guard.com', 'role' => 'Sales Manager', 'organization' => $protectaGroup->id],
            ['first_name' => 'Sales', 'last_name' => 'Manager Two', 'email' => 'manager2@guard.com', 'role' => 'Sales Manager', 'organization' => $protectaGroup->id],
            ['first_name' => 'Sales', 'last_name' => 'Agent Alpha', 'email' => 'agent1@guard.com', 'role' => 'Sales Agent', 'organization' => $protectaGroup->id],
            ['first_name' => 'Sales', 'last_name' => 'Agent Beta', 'email' => 'agent2@guard.com', 'role' => 'Sales Agent', 'organization' => $protectaGroup->id],
            ['first_name' => 'Sales', 'last_name' => 'Agent Charlie', 'email' => 'agent3@guard.com', 'role' => 'Sales Agent', 'organization' => $protectaGroup->id],
            ['first_name' => 'Sales', 'last_name' => 'Agent Delta', 'email' => 'agent4@guard.com', 'role' => 'Sales Agent', 'organization' => $protectaGroup->id],
            ['first_name' => 'Referral', 'last_name' => 'Partner One', 'email' => 'referral1@partner.com', 'role' => 'Referral', 'organization' => $protectaGroup->id],
            ['first_name' => 'Referral', 'last_name' => 'Partner Two', 'email' => 'referral2@partner.com', 'role' => 'Referral', 'organization' => $protectaGroup->id],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
                'password' => Hash::make('password123'),
                'organization_id' => $userData['organization'],
                'is_active' => true,
            ]);
            $user->assignRole($userData['role']);
        }

        // Create Partner Director and other users in partner organizations (intelligentb2b)
        $partnerUsers = [
            ['first_name' => 'Partner', 'last_name' => 'Director', 'email' => 'partner@intelligentb2b.com', 'role' => 'Partner Director'],
            ['first_name' => 'Tech', 'last_name' => 'Director', 'email' => 'tech.director@intelligentb2b.com', 'role' => 'Partner Director'],
            ['first_name' => 'Tech', 'last_name' => 'Manager', 'email' => 'tech.manager@intelligentb2b.com', 'role' => 'Sales Manager'],
            ['first_name' => 'Tech', 'last_name' => 'Agent', 'email' => 'tech.agent@intelligentb2b.com', 'role' => 'Sales Agent'],
            ['first_name' => 'Tech', 'last_name' => 'Referral', 'email' => 'tech.referral@intelligentb2b.com', 'role' => 'Referral'],
        ];

        foreach ($partnerUsers as $userData) {
            $user = User::create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
                'password' => Hash::make('password123'),
                'organization_id' => $intelligentb2b->id,
                'is_active' => true,
            ]);
            $user->assignRole($userData['role']);
        }

        // Seed Teams
        $this->command->info('👥 Creating teams...');
        $this->call(TeamSeeder::class);

        // Seed Leads
        $this->command->info('📈 Creating leads...');
        $this->call(LeadSeeder::class);

        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->line('');
        $this->command->info('🔑 Login Credentials:');
        $this->command->line('   Admin: admin@guard.com / password123');
        $this->command->line('   Group Director: director@protecta.com / password123');
        $this->command->line('   Partner Director: partner@intelligentb2b.com / password123');
        $this->command->line('   Manager: manager1@guard.com / password123');
        $this->command->line('   Agent: agent1@guard.com / password123');
        $this->command->line('   Referral: referral1@partner.com / password123');
    }
}
