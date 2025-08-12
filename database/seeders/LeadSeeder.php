<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\User;
use App\Models\Organization;
use App\Enums\LeadStatus;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users for lead creation
        $referralUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Referral');
        })->get();

        $salesManagers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Sales Manager');
        })->get();

        $salesAgents = User::whereHas('roles', function ($query) {
            $query->where('name', 'Sales Agent');
        })->get();

        $organizations = Organization::all();

        // Create sample leads with different statuses
        $leadTemplates = [
            [
                'client_first_name' => 'John',
                'client_last_name' => 'Smith',
                'client_email' => 'john.smith@example.com',
                'client_phone' => '+1-555-0123',
                'client_company' => 'ABC Corporation',
                'source' => 'Website Contact Form',
                'status' => LeadStatus::NEW,
                'revenue' => 0.00
            ],
            [
                'client_first_name' => 'Sarah',
                'client_last_name' => 'Johnson',
                'client_email' => 'sarah.johnson@techcorp.com',
                'client_phone' => '+1-555-0124',
                'client_company' => 'TechCorp Inc',
                'source' => 'LinkedIn',
                'status' => LeadStatus::ASSIGNED_TO_MANAGER,
                'revenue' => 15000
            ],
            [
                'client_first_name' => 'Michael',
                'client_last_name' => 'Brown',
                'client_email' => 'michael.brown@startup.io',
                'client_phone' => '+1-555-0125',
                'client_company' => 'Startup Solutions',
                'source' => 'Referral',
                'status' => LeadStatus::ASSIGNED_TO_AGENT,
                'revenue' => 8500
            ],
            [
                'client_first_name' => 'Emily',
                'client_last_name' => 'Davis',
                'client_email' => 'emily.davis@bigcompany.com',
                'client_phone' => '+1-555-0126',
                'client_company' => 'Big Company Ltd',
                'source' => 'Trade Show',
                'status' => LeadStatus::QUALIFIED,
                'revenue' => 25000
            ],
            [
                'client_first_name' => 'Robert',
                'client_last_name' => 'Wilson',
                'client_email' => 'robert.wilson@enterprise.com',
                'client_phone' => '+1-555-0127',
                'client_company' => 'Enterprise Solutions',
                'source' => 'Cold Call',
                'status' => LeadStatus::CONVERTED,
                'revenue' => 45000
            ]
        ];

        // Create specific leads with different statuses and assignments
        foreach ($leadTemplates as $index => $template) {
            $referral = $referralUsers->random();
            
            $leadData = [
                'client_first_name' => $template['client_first_name'],
                'client_last_name' => $template['client_last_name'],
                'client_email' => $template['client_email'],
                'client_phone' => $template['client_phone'],
                'client_company' => $template['client_company'],
                'source' => $template['source'],
                'status' => $template['status'],
                'revenue' => $template['revenue'],
                'referral_id' => $referral->id,
                'organization_id' => $referral->organization_id, // Use referral's organization
            ];

            // Assign based on status
            switch ($template['status']) {
                case LeadStatus::ASSIGNED_TO_MANAGER:
                    $manager = $salesManagers->random();
                    $leadData['assigned_to_id'] = $manager->id;
                    $leadData['assigned_by_id'] = $referral->id;
                    $leadData['organization_id'] = $manager->organization_id;
                    break;

                case LeadStatus::ASSIGNED_TO_AGENT:
                case LeadStatus::QUALIFIED:
                case LeadStatus::CONVERTED:
                    $agent = $salesAgents->random();
                    $manager = $salesManagers->where('organization_id', $agent->organization_id)->first();
                    
                    if ($manager) {
                        $leadData['assigned_to_id'] = $agent->id;
                        $leadData['assigned_by_id'] = $manager->id;
                        $leadData['organization_id'] = $agent->organization_id;
                    }
                    break;

                default:
                    // NEW status leads remain unassigned
                    break;
            }

            $lead = Lead::create($leadData);
            $this->command->info("Created lead: {$lead->client_first_name} {$lead->client_last_name} - Status: {$lead->status->value}");
        }

        // Create additional random leads for testing
        $this->command->info("Creating additional random leads...");
        
        for ($i = 0; $i < 20; $i++) {
            $referral = $referralUsers->random();
            $status = collect([
                LeadStatus::NEW,
                LeadStatus::ASSIGNED_TO_MANAGER,
                LeadStatus::ASSIGNED_TO_AGENT,
                LeadStatus::QUALIFIED,
                LeadStatus::ACCEPTED,
                LeadStatus::CONTACTED,
                LeadStatus::CONVERTED,
                LeadStatus::REJECTED
            ])->random();

            $leadData = [
                'client_first_name' => fake()->firstName(),
                'client_last_name' => fake()->lastName(),
                'client_email' => fake()->unique()->safeEmail(),
                'client_phone' => fake()->phoneNumber(),
                'client_company' => fake()->company(),
                'source' => fake()->randomElement([
                    'Website', 'LinkedIn', 'Referral', 'Cold Call', 
                    'Trade Show', 'Email Campaign', 'Social Media'
                ]),
                'status' => $status,
                'revenue' => $status === LeadStatus::CONVERTED ? fake()->numberBetween(5000, 100000) : 
                           (in_array($status, [LeadStatus::QUALIFIED, LeadStatus::ACCEPTED, LeadStatus::CONTACTED]) ? fake()->numberBetween(1000, 50000) : 0.00),
                'referral_id' => $referral->id,
                'organization_id' => $referral->organization_id, // Use referral's organization
            ];

            // Assign based on status (similar logic as above)
            if ($status === LeadStatus::ASSIGNED_TO_MANAGER) {
                $manager = $salesManagers->random();
                $leadData['assigned_to_id'] = $manager->id;
                $leadData['assigned_by_id'] = $referral->id;
                $leadData['organization_id'] = $manager->organization_id;
            } elseif (in_array($status, [
                LeadStatus::ASSIGNED_TO_AGENT,
                LeadStatus::QUALIFIED,
                LeadStatus::ACCEPTED,
                LeadStatus::CONTACTED,
                LeadStatus::CONVERTED,
                LeadStatus::REJECTED
            ])) {
                $agent = $salesAgents->random();
                $manager = $salesManagers->where('organization_id', $agent->organization_id)->first();
                
                if ($manager) {
                    $leadData['assigned_to_id'] = $agent->id;
                    $leadData['assigned_by_id'] = $manager->id;
                    $leadData['organization_id'] = $agent->organization_id;
                }
            }

            Lead::create($leadData);
        }

        $this->command->info("Created " . Lead::count() . " leads total");
    }
}
