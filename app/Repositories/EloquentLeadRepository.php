<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\LeadRepositoryInterface;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EloquentLeadRepository implements LeadRepositoryInterface
{
    public function getAll(User $currentUser, array $filters): LengthAwarePaginator
    {
        $query = Lead::with(relations: ['referral', 'assignedTo', 'assignedBy', 'organization']);

        $this->applyRoleBasedFilters($query, $currentUser);
        $this->applyFrontendFilters($query, $filters);

        return $query->paginate($filters['pageSize'] ?? 10);
    }

    public function getById(string $id): ?Lead
    {
        return Lead::with(['referral', 'assignedTo', 'organization', 'notes', 'appointments'])->find($id);
    }

    public function create(array $details): Lead
    {
        return Lead::create($details);
    }

    public function update(Lead $lead, array $newDetails): Lead
    {
        $lead->update($newDetails);
        return $lead->fresh(['referral', 'assignedTo', 'organization']);
    }

    public function delete(Lead $lead): bool
    {
        return $lead->delete();
    }

    public function assign(Lead $lead, User $assignee, User $assigner, string $newStatus): Lead
    {
        $lead->assigned_to_id = $assignee->id;
        $lead->assigned_by_id = $assigner->id;
        $lead->organization_id = $assignee->organization_id;
        $lead->status = $newStatus;
        $lead->save();
        return $lead->fresh(['referral', 'assignedTo', 'assignedBy', 'organization']);
    }

    public function updateStatus(Lead $lead, string $status): Lead
    {
        $lead->status = $status;
        $lead->save();
        return $lead->fresh();
    }

    private function applyRoleBasedFilters(Builder $query, User $currentUser): void
    {
        if ($currentUser->hasRole('Sales Agent')) {
            // Sales Agents only see leads assigned to them
            $query->where('assigned_to_id', $currentUser->id);
        }

        if ($currentUser->hasRole('Sales Manager')) {
            // Sales Managers see leads assigned to them OR leads they assigned to others
            $query->where(function (Builder $q) use ($currentUser) {
                $q->where('assigned_to_id', $currentUser->id)     // Leads assigned to them
                  ->orWhere('assigned_by_id', $currentUser->id);  // Leads they assigned to others
            });
        }

        if ($currentUser->hasRole('Partner Director')) {
            // Partner Directors see all leads in their organization
            $query->where('organization_id', $currentUser->organization_id);
        }

        if ($currentUser->hasRole('Coordinator')) {
            // Coordinators see all leads in their organization (for assignment purposes)
            $query->where('organization_id', $currentUser->organization_id);
        }
    }

    private function applyFrontendFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['assignedTo'])) {
            $query->where('assigned_to_id', $filters['assignedTo']);
        }

        if (isset($filters['search'])) {
            $query->searchClient($filters['search']);
        }

        if (isset($filters['sortField'])) {
            $sortOrder = $filters['sortOrder'] ?? 'asc';
            $query->orderBy($filters['sortField'], $sortOrder);
        }
    }
}
