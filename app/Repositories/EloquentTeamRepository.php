<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\TeamRepositoryInterface;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentTeamRepository implements TeamRepositoryInterface
{
    public function getAll(User $currentUser, array $filters): LengthAwarePaginator
    {
        $query = Team::with(['creator', 'users']);

        $this->applyRoleBasedFilters($query, $currentUser);
        $this->applyFrontendFilters($query, $filters);

        return $query->paginate($filters['pageSize'] ?? 10);
    }

    public function getById(string $id): ?Team
    {
        return Team::with(['creator', 'users'])->find($id);
    }

    public function create(array $details): Team
    {
        return Team::create($details);
    }

    public function update(Team $team, array $newDetails): Team
    {
        $team->update($newDetails);
        return $team->fresh(['creator', 'users']);
    }

    public function delete(Team $team): bool
    {
        // Remove all user associations before deleting the team
        $team->users()->detach();
        return $team->delete();
    }

    public function addUserToTeam(Team $team, User $user): bool
    {
        if (!$team->users()->where('user_id', $user->id)->exists()) {
            $team->users()->attach($user->id);
            return true;
        }
        return false; // User already in team
    }

    public function removeUserFromTeam(Team $team, User $user): bool
    {
        return $team->users()->detach($user->id) > 0;
    }

    public function getTeamUsers(Team $team): Collection
    {
        return $team->users()->get();
    }

    public function getUserTeams(User $user): Collection
    {
        return $user->teams()->with(['creator'])->get();
    }

    /**
     * Apply role-based filters to the query based on the current user's role
     */
    private function applyRoleBasedFilters(Builder $query, User $currentUser): void
    {
        // Super Admin can see all teams
        if ($currentUser->hasRole('Super Admin')) {
            return;
        }

        // Directors can see teams within their organization
        if ($currentUser->hasRole('Director')) {
            $query->whereHas('creator', function ($q) use ($currentUser) {
                $q->where('organization_id', $currentUser->organization_id);
            });
            return;
        }

        // Other roles can only see teams they created or are members of
        $query->where(function ($q) use ($currentUser) {
            $q->where('creator_id', $currentUser->id)
              ->orWhereHas('users', function ($userQuery) use ($currentUser) {
                  $userQuery->where('user_id', $currentUser->id);
              });
        });
    }

    /**
     * Apply frontend filters to the query
     */
    private function applyFrontendFilters(Builder $query, array $filters): void
    {
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('slug', 'LIKE', "%{$search}%");
            });
        }

        if (isset($filters['creator_id']) && !empty($filters['creator_id'])) {
            $query->where('creator_id', $filters['creator_id']);
        }

        // Add sorting
        if (isset($filters['sort_by']) && isset($filters['sort_order'])) {
            $sortBy = $filters['sort_by'];
            $sortOrder = in_array($filters['sort_order'], ['asc', 'desc']) ? $filters['sort_order'] : 'asc';
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }
}
