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
        // Performance optimization: load only essential relationships for listing
        $query = Team::select(['id', 'name', 'description', 'slug', 'creator_id', 'organization_id', 'created_at', 'updated_at'])
            ->with([
                'creator' => function ($query) {
                    $query->select(['id', 'first_name', 'last_name', 'organization_id']);
                },
                'organization' => function ($query) {
                    $query->select(['id', 'name', 'parent_id']);
                }
            ])
            ->withCount([
                'users',
                'users as active_users_count' => function ($query) {
                    $query->where('users.is_active', true);
                }
            ]);

        $this->applyRoleBasedFilters($query, $currentUser);
        $this->applyFrontendFilters($query, $filters);

        return $query->paginate($filters['pageSize'] ?? 10);
    }

    public function getById(string $id): ?Team
    {
        // For single team view, include basic info without users (use getTeamWithUsers if users needed)
        return Team::select(['id', 'name', 'description', 'slug', 'creator_id', 'organization_id', 'created_at', 'updated_at'])
            ->with([
                'creator' => function ($query) {
                    $query->select(['id', 'first_name', 'last_name', 'organization_id']);
                },
                'organization' => function ($query) {
                    $query->select(['id', 'name', 'parent_id']);
                }
            ])
            ->withCount([
                'users',
                'users as active_users_count' => function ($query) {
                    $query->where('users.is_active', true);
                }
            ])
            ->find($id);
    }

    public function create(array $details): Team
    {
        return Team::create($details);
    }

    public function update(Team $team, array $newDetails): Team
    {
        $team->update($newDetails);
        // Return fresh team with basic relationships only
        return $team->fresh([
            'creator' => function ($query) {
                $query->select(['id', 'first_name', 'last_name', 'organization_id']);
            },
            'organization' => function ($query) {
                $query->select(['id', 'name', 'parent_id']);
            }
        ]);
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
        return $team->users()->select(['users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.is_active'])->get();
    }

    public function getUserTeams(User $user): Collection
    {
        return $user->teams()
            ->select(['teams.id', 'teams.name', 'teams.description', 'teams.slug', 'teams.creator_id', 'teams.organization_id'])
            ->with([
                'creator' => function ($query) {
                    $query->select(['id', 'first_name', 'last_name', 'organization_id']);
                },
                'organization' => function ($query) {
                    $query->select(['id', 'name', 'parent_id']);
                }
            ])
            ->get();
    }

    /**
     * Get team with users loaded (separate method for when users are specifically needed)
     */
    public function getTeamWithUsers(string $id): ?Team
    {
        return Team::select(['id', 'name', 'description', 'slug', 'creator_id', 'organization_id', 'created_at', 'updated_at'])
            ->with([
                'creator' => function ($query) {
                    $query->select(['id', 'first_name', 'last_name', 'organization_id']);
                },
                'organization' => function ($query) {
                    $query->select(['id', 'name', 'parent_id']);
                },
                'users' => function ($query) {
                    $query->select(['users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.is_active','users.created_at', 'users.updated_at']);
                }
            ])
            ->withCount([
                'users',
                'users as active_users_count' => function ($query) {
                    $query->where('users.is_active', true);
                }
            ])
            ->find($id);
    }

    /**
     * Get teams by organization with performance optimization
     */
    public function getByOrganization(string $organizationId, array $filters = []): LengthAwarePaginator
    {
        $query = Team::select(['id', 'name', 'description', 'slug', 'creator_id', 'organization_id', 'created_at', 'updated_at'])
            ->where('organization_id', $organizationId)
            ->with([
                'creator' => function ($query) {
                    $query->select(['id', 'first_name', 'last_name', 'organization_id']);
                },
                'organization' => function ($query) {
                    $query->select(['id', 'name', 'parent_id']);
                }
            ])
            ->withCount([
                'users',
                'users as active_users_count' => function ($query) {
                    $query->where('users.is_active', true);
                }
            ]);

        $this->applyFrontendFilters($query, $filters);

        return $query->paginate($filters['pageSize'] ?? 10);
    }

    /**
     * Apply role-based filters to the query based on the current user's role
     */
    private function applyRoleBasedFilters(Builder $query, User $currentUser): void
    {
        // Admin and Group Director can see all teams
        if ($currentUser->hasRole(['Admin', 'Group Director'])) {
            return;
        }

        // Partner Directors can see teams within their organization only
        if ($currentUser->hasRole('Partner Director')) {
            $query->where('organization_id', $currentUser->organization_id);
            return;
        }

        // Coordinators can see teams within their organization
        if ($currentUser->hasRole('Coordinator')) {
            $query->where('organization_id', $currentUser->organization_id);
            return;
        }

        // Other roles (Sales Manager, Sales Agent) can see teams they created or are members of
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

        if (isset($filters['organization_id']) && !empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
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
