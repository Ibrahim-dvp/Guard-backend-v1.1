<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Enums\LeadStatus;
use Illuminate\Auth\Access\Response;

class LeadPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole(['Admin', 'Group Director'])) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('leads.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Lead $lead): bool
    {
        if (!$user->can('leads.view')) {
            return false;
        }

        if ($user->hasRole('Sales Manager')) {
            // Sales Manager can view leads assigned to them or assigned by them
            return $user->id === $lead->assigned_to_id || 
                   $user->id === $lead->assigned_by_id;
        }

        if ($user->hasRole('Sales Agent')) {
            return $user->id === $lead->assigned_to_id;
        }

        if ($user->hasRole('Referral')) {
            return $user->id === $lead->referral_id;
        }

        if ($user->hasRole(['Partner Director', 'Coordinator'])) {
            return $user->organization_id === $lead->organization_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('leads.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Lead $lead): bool
    {
        // This is a generic update check. Specific actions like 'assign' or 'updateStatus' will have their own checks.
        return $user->can('leads.update-status') && $user->id === $lead->assigned_to_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('leads.delete');
    }

    public function assign(User $user, Lead $lead): bool
    {
        if (!$user->can('leads.assign')) {
            return false;
        }

        if ($user->hasRole('Coordinator')) {
            // Coordinators can assign any lead in their organization
            return $user->organization_id === $lead->organization_id;
        }

        if ($user->hasRole('Sales Manager')) {
            // Sales Manager can reassign leads assigned to them or assigned by them
            return $user->id === $lead->assigned_to_id || 
                   $user->id === $lead->assigned_by_id;
        }

        return false;
    }

    public function updateStatus(User $user, Lead $lead): bool
    {
        if (!$user->can('leads.update-status')) {
            return false;
        }

        if ($user->hasRole('Sales Manager')) {
            // A manager can update the status of a lead assigned to them or assigned by them
            return $user->id === $lead->assigned_to_id || 
                   $user->id === $lead->assigned_by_id;
        }

        if ($user->hasRole('Sales Agent')) {
            // An agent can only update the status of a lead assigned to them.
            return $user->id === $lead->assigned_to_id;
        }

        return false;
    }
}
