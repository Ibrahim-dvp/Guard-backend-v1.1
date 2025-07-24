<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganizationPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole(['Admin', 'Group Director'])) {
            return true;
        }

        return null; // let other methods decide
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('organizations.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Organization $organization): bool
    {
        if (!$user->can('organizations.view')) {
            return false;
        }

        // A user can view their own organization or a child of their organization.
        return $user->organization_id === $organization->id || $user->organization_id === $organization->parent_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('organizations.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organization $organization): bool
    {
        if (!$user->can('organizations.update')) {
            return false;
        }

        // A user can update their own organization if they are the director.
        return $user->id === $organization->director_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organization $organization): bool
    {
        if (!$user->can('organizations.delete')) {
            return false;
        }

        // A user can delete their own organization if they are the director.
        return $user->id === $organization->director_id;
    }
}
