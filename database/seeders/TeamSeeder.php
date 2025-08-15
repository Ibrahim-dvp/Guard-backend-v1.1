<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get organizations
        $protectaGroup = Organization::where('name', 'Protecta Group')->first();
        $intelligentb2b = Organization::where('name', 'Intelligentb2b')->first();

        // Get specific users from Protecta Group organization
        $protectaDirector = User::where('email', 'director@protecta.com')->first();
        $partnerDirector = User::where('email', 'partner@example.com')->first();
        $coordinator = User::where('email', 'coordinator@guard.com')->first();
        $salesManager1 = User::where('email', 'manager1@guard.com')->first();
        $salesManager2 = User::where('email', 'manager2@guard.com')->first();
        $salesAgent1 = User::where('email', 'agent1@guard.com')->first();
        $salesAgent2 = User::where('email', 'agent2@guard.com')->first();
        $salesAgent3 = User::where('email', 'agent3@guard.com')->first();
        $salesAgent4 = User::where('email', 'agent4@guard.com')->first();

        // Get specific users from Intelligentb2b organization
        $techDirector = User::where('email', 'tech.director@intelligentb2b.com')->first();
        $techManager = User::where('email', 'tech.manager@intelligentb2b.com')->first();
        $techAgent = User::where('email', 'tech.agent@intelligentb2b.com')->first();

        // Create teams for Protecta Group
        $protectaTeams = [
            [
                'name' => 'Enterprise Sales Team',
                'description' => 'Primary sales team focusing on enterprise clients',
                'slug' => 'enterprise-sales-team',
                'creator' => $partnerDirector,
                'manager' => $salesManager1,
                'agents' => [$salesAgent1, $salesAgent2]
            ],
            [
                'name' => 'Mid-Market Sales Team',
                'description' => 'Sales team targeting mid-market opportunities',
                'slug' => 'mid-market-sales-team',
                'creator' => $partnerDirector,
                'manager' => $salesManager2,
                'agents' => [$salesAgent3, $salesAgent4]
            ],
            [
                'name' => 'Lead Management Team',
                'description' => 'Team responsible for lead qualification and distribution',
                'slug' => 'lead-management-team',
                'creator' => $protectaDirector,
                'manager' => $salesManager1,
                'agents' => [$coordinator, $salesAgent1]
            ]
        ];

        foreach ($protectaTeams as $teamData) {
            if ($teamData['creator'] && $protectaGroup) {
                $team = Team::create([
                    'name' => $teamData['name'],
                    'description' => $teamData['description'],
                    'slug' => $teamData['slug'],
                    'creator_id' => $teamData['creator']->id,
                    'organization_id' => $protectaGroup->id,
                ]);

                // Add manager and agents to the team
                $members = [];
                if ($teamData['manager']) {
                    $members[] = $teamData['manager']->id;
                }
                if (!empty($teamData['agents'])) {
                    foreach ($teamData['agents'] as $agent) {
                        if ($agent) {
                            $members[] = $agent->id;
                        }
                    }
                }

                if (!empty($members)) {
                    $team->users()->attach($members);
                }

                $this->command->info("✅ Created team: {$team->name} with " . count($members) . " members for {$protectaGroup->name}");
            }
        }

        // Create teams for Intelligentb2b
        if ($techDirector && $techManager && $techAgent && $intelligentb2b) {
            $intelligentTeams = [
                [
                    'name' => 'Tech Solutions Team',
                    'description' => 'Technology-focused sales and support team',
                    'slug' => 'tech-solutions-team',
                    'creator' => $techDirector,
                    'manager' => $techManager,
                    'agents' => [$techAgent]
                ]
            ];

            foreach ($intelligentTeams as $teamData) {
                $team = Team::create([
                    'name' => $teamData['name'],
                    'description' => $teamData['description'],
                    'slug' => $teamData['slug'],
                    'creator_id' => $teamData['creator']->id,
                    'organization_id' => $intelligentb2b->id,
                ]);

                // Add manager and agents to the team
                $members = [];
                if ($teamData['manager']) {
                    $members[] = $teamData['manager']->id;
                }
                if (!empty($teamData['agents'])) {
                    foreach ($teamData['agents'] as $agent) {
                        if ($agent) {
                            $members[] = $agent->id;
                        }
                    }
                }

                if (!empty($members)) {
                    $team->users()->attach($members);
                }

                $this->command->info("✅ Created team: {$team->name} with " . count($members) . " members for {$intelligentb2b->name}");
            }
        }

        $this->command->info('👥 Teams created successfully with proper Sales Manager and Agent structure!');
    }
}
