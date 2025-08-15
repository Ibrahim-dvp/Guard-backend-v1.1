<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeamPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole(['Admin','Group Director'])) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('teams.view') || 
               $user->hasRole(['Partner Director', 'Group Director', 'Sales Manager', 'Sales Agent', 'Coordinator']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Team $team): bool
    {
        if (!$user->can('teams.view')) {
            return false;
        }

        // Team creator can always view their team
        if ($team->creator_id === $user->id) {
            return true;
        }

        // Directors can view teams within their organization
        if ($user->hasRole('Partner Director')) {
            return $user->organization_id === $team->creator->organization_id;
        }

        // Team members can view the team
        return $team->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('teams.create') || 
               $user->hasRole(['Partner Director', 'Group Director', 'Sales Manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $team): bool
    {
        if (!$user->can('teams.update')) {
            return false;
        }

        // Team creator can update their team
        if ($team->creator_id === $user->id) {
            return true;
        }

        // Partner Directors can update teams within their organization
        if ($user->hasRole('Partner Director')) {
            return $user->organization_id === $team->creator->organization_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $team): bool
    {
        if (!$user->can('teams.delete') && !$user->hasRole(['Partner Director', 'Group Director'])) {
            return false;
        }

        // Team creator can delete their team
        if ($team->creator_id === $user->id) {
            return true;
        }

        // Directors can delete teams within their organization
        if ($user->hasRole(['Partner Director', 'Group Director'])) {
            return $user->organization_id === $team->creator->organization_id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage team members.
     */
    public function manageMembers(User $user, Team $team): bool
    {
        if (!$user->can('teams.manage_members') && !$user->hasRole(['Partner Director', 'Group Director', 'Sales Manager'])) {
            return false;
        }

        // Team creator can manage members
        if ($team->creator_id === $user->id) {
            return true;
        }

        // Directors can manage members of teams within their organization
        if ($user->hasRole(['Partner Director', 'Group Director'])) {
            return $user->organization_id === $team->creator->organization_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Team $team): bool
    {
        return $this->update($user, $team);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Team $team): bool
    {
        return $this->delete($user, $team);
    }
}
