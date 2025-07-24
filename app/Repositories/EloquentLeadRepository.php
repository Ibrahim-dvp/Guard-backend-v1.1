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
        $query = Lead::with(['referral', 'assignedTo', 'organization']);

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

    public function assign(Lead $lead, User $assignee, User $assigner): Lead
    {
        $lead->assigned_to_id = $assignee->id;
        $lead->assigned_by_id = $assigner->id; // Assuming an 'assigned_by_id' column exists
        $lead->status = 'assigned_to_manager'; // Or determine status dynamically
        $lead->save();
        return $lead->fresh(['referral', 'assignedTo', 'organization']);
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
            $query->where('assigned_to_id', $currentUser->id);
        }

        if ($currentUser->hasRole('Sales Manager')) {
            $query->where(function (Builder $q) use ($currentUser) {
                $q->where('assigned_to_id', $currentUser->id) // Leads assigned to the manager
                  ->orWhereIn('assigned_to_id', function ($subQuery) use ($currentUser) {
                      $subQuery->select('id')->from('users')->where('created_by', $currentUser->id);
                  }); // Or leads assigned to their team members
            });
        }

        // Add more complex role filters for Partner Director, etc. here
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
            $query->where(function (Builder $q) use ($filters) {
                $q->where('client_info->firstName', 'like', "%{$filters['search']}%")
                  ->orWhere('client_info->lastName', 'like', "%{$filters['search']}%")
                  ->orWhere('client_info->email', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['sortField'])) {
            $sortOrder = $filters['sortOrder'] ?? 'asc';
            $query->orderBy($filters['sortField'], $sortOrder);
        }
    }
}
