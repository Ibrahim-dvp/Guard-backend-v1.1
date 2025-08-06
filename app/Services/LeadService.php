<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadStatus;
use App\Interfaces\LeadRepositoryInterface;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LeadService
{
    protected $leadRepository;

    public function __construct(LeadRepositoryInterface $leadRepository)
    {
        $this->leadRepository = $leadRepository;
    }

    public function getLeads(array $filters): LengthAwarePaginator
    {
        return $this->leadRepository->getAll(Auth::user(), $filters);
    }

    public function createLead(array $details): Lead
    {
        $user = Auth::user();
        $details['referral_id'] = $user->id;
        // Organization is set upon assignment
        $details['organization_id'] = null;
        $details['status'] = LeadStatus::NEW;
        return $this->leadRepository->create($details);
    }

    public function updateLead(Lead $lead, array $details): Lead
    {
        return $this->leadRepository->update($lead, $details);
    }

    public function getLeadById(string $id): ?Lead
    {
        return $this->leadRepository->getById($id);
    }

    public function assignLead(Lead $lead, string $assigneeId): Lead
    {
        /** @var User $assigner */
        $assigner = Auth::user();
        $assignee = User::findOrFail($assigneeId);
        $newStatus = LeadStatus::NEW; // Default, should not be used

        if ($assigner->hasRole('Coordinator')) {
            if (!$assignee->hasRole('Sales Manager')) {
                throw ValidationException::withMessages([
                    'assignedTo' => 'Coordinators can only assign leads to Sales Managers.',
                ]);
            }
            $newStatus = LeadStatus::ASSIGNED_TO_MANAGER;
        }

        if ($assigner->hasRole('Sales Manager')) {
            if (!$assignee->hasRole('Sales Agent')) {
                throw ValidationException::withMessages([
                    'assignedTo' => 'Sales Managers can only assign leads to Sales Agents.',
                ]);
            }
            if ($assigner->organization_id !== $assignee->organization_id) {
                throw ValidationException::withMessages([
                    'assignedTo' => 'You can only assign leads to agents within your own organization.',
                ]);
            }
            $newStatus = LeadStatus::ASSIGNED_TO_AGENT;
        }

        return $this->leadRepository->assign($lead, $assignee, $assigner, $newStatus->value);
    }

    public function updateLeadStatus(Lead $lead, string $status): Lead
    {
        // Add business logic here, e.g., check for valid status transitions
        return $this->leadRepository->updateStatus($lead, $status);
    }
}
