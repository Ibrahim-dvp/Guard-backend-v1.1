<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users by roles for team creation
        $director = User::whereHas('roles', function ($query) {
            $query->where('name', 'Partner Director');
        })->first();

        $salesManager = User::whereHas('roles', function ($query) {
            $query->where('name', 'Sales Manager');
        })->first();

        $salesAgents = User::whereHas('roles', function ($query) {
            $query->where('name', 'Sales Agent');
        })->get();

        $coordinator = User::whereHas('roles', function ($query) {
            $query->where('name', 'Coordinator');
        })->first();

        // Create teams with realistic scenarios
        $teams = [
            [
                'name' => 'Sales Team Alpha',
                'description' => 'Primary sales team focusing on enterprise clients',
                'slug' => 'sales-team-alpha',
                'creator' => $director,
                'members' => [$salesManager, $salesAgents->first()]
            ],
            [
                'name' => 'Sales Team Beta',
                'description' => 'Secondary sales team for mid-market clients',
                'slug' => 'sales-team-beta',
                'creator' => $director,
                'members' => [$salesAgents->skip(1)->first()]
            ],
            [
                'name' => 'Lead Management Team',
                'description' => 'Team responsible for lead qualification and distribution',
                'slug' => 'lead-management-team',
                'creator' => $salesManager,
                'members' => [$coordinator, $salesAgents->first()]
            ],
            [
                'name' => 'Customer Success Team',
                'description' => 'Team focused on customer retention and upselling',
                'slug' => 'customer-success-team',
                'creator' => $salesManager,
                'members' => $salesAgents->take(2)->toArray()
            ]
        ];

        foreach ($teams as $teamData) {
            if ($teamData['creator']) {
                $team = Team::create([
                    'name' => $teamData['name'],
                    'description' => $teamData['description'],
                    'slug' => $teamData['slug'],
                    'creator_id' => $teamData['creator']->id,
                ]);

                // Add members to the team
                if (!empty($teamData['members'])) {
                    $memberIds = collect($teamData['members'])
                        ->filter() // Remove null values
                        ->pluck('id')
                        ->toArray();
                    
                    if (!empty($memberIds)) {
                        $team->users()->attach($memberIds);
                    }
                }

                $this->command->info("Created team: {$team->name} with " . count($teamData['members']) . " members");
            }
        }

        // Create additional teams using factories for more test data
        $additionalUsers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['Sales Manager', 'Partner Director']);
        })->get();

        foreach ($additionalUsers as $user) {
            // Create 1-2 additional teams per manager/director
            $teamCount = rand(1, 2);
            
            for ($i = 0; $i < $teamCount; $i++) {
                $team = Team::factory()
                    ->createdBy($user)
                    ->create();

                // Add random team members from the same organization
                $potentialMembers = User::where('organization_id', $user->organization_id)
                    ->where('id', '!=', $user->id)
                    ->inRandomOrder()
                    ->limit(rand(2, 4))
                    ->get();

                if ($potentialMembers->isNotEmpty()) {
                    $team->users()->attach($potentialMembers->pluck('id'));
                }

                $this->command->info("Created additional team: {$team->name}");
            }
        }
    }
}
