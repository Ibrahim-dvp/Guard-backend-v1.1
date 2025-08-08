<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AppointmentPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admin and Group Director can view all appointments
        if ($user->hasRole(['Admin', 'Group Director'])) {
            return true;
        }

        // Partner Directors, Sales Managers, Sales Agents can view appointments based on their scope
        if ($user->can('appointments.view')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        // Check basic permission
        if (!$user->can('appointments.view')) {
            return false;
        }

        // Admin and Group Director can view all appointments
        if ($user->hasRole(['Admin', 'Group Director'])) {
            return true;
        }

        // User can view appointments they scheduled
        if ($appointment->scheduled_by === $user->id) {
            return true;
        }

        // User can view appointments scheduled with them
        if ($appointment->scheduled_with === $user->id) {
            return true;
        }

        // Partner Directors can view appointments within their organization
        if ($user->hasRole('Partner Director')) {
            $appointmentLeadOrganization = $appointment->lead->organization_id ?? null;
            return $user->organization_id === $appointmentLeadOrganization;
        }

        // Sales Managers can view appointments of their team members or leads they manage
        if ($user->hasRole('Sales Manager')) {
            $appointmentLeadOrganization = $appointment->lead->organization_id ?? null;
            
            // Can view if appointment is within their organization
            if ($user->organization_id === $appointmentLeadOrganization) {
                return true;
            }

            // Can view if appointment is with someone in their teams
            $userTeams = $user->teams()->pluck('id');
            $scheduledWithUser = User::find($appointment->scheduled_with);
            $scheduledByUser = User::find($appointment->scheduled_by);
            
            if ($scheduledWithUser && $scheduledWithUser->teams()->whereIn('team_id', $userTeams)->exists()) {
                return true;
            }
            
            if ($scheduledByUser && $scheduledByUser->teams()->whereIn('team_id', $userTeams)->exists()) {
                return true;
            }
        }

        // Sales Agents can view appointments where they are involved or assigned to the lead
        if ($user->hasRole('Sales Agent')) {
            // Can view if they are the one scheduled or scheduling
            if ($appointment->scheduled_by === $user->id || $appointment->scheduled_with === $user->id) {
                return true;
            }

            // Can view if the appointment is for a lead assigned to them
            if ($appointment->lead->assigned_to_id === $user->id) {
                return true;
            }
        }

        // Coordinators can view appointments within their organization
        if ($user->hasRole('Coordinator')) {
            $appointmentLeadOrganization = $appointment->lead->organization_id ?? null;
            return $user->organization_id === $appointmentLeadOrganization;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only users with appointment creation permission can create appointments
        return $user->can('appointments.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        // Check basic permission
        if (!$user->can('appointments.update')) {
            return false;
        }

        // Admin and Group Director can update all appointments
        if ($user->hasRole(['Admin', 'Group Director'])) {
            return true;
        }

        // User can update appointments they scheduled
        if ($appointment->scheduled_by === $user->id) {
            return true;
        }

        // User can update appointments scheduled with them (to change status, add notes, etc.)
        if ($appointment->scheduled_with === $user->id) {
            return true;
        }

        // Partner Directors can update appointments within their organization
        if ($user->hasRole('Partner Director')) {
            $appointmentLeadOrganization = $appointment->lead->organization_id ?? null;
            return $user->organization_id === $appointmentLeadOrganization;
        }

        // Sales Managers can update appointments of their team members
        if ($user->hasRole('Sales Manager')) {
            $userTeams = $user->teams()->pluck('id');
            $scheduledWithUser = User::find($appointment->scheduled_with);
            $scheduledByUser = User::find($appointment->scheduled_by);
            
            if ($scheduledWithUser && $scheduledWithUser->teams()->whereIn('team_id', $userTeams)->exists()) {
                return true;
            }
            
            if ($scheduledByUser && $scheduledByUser->teams()->whereIn('team_id', $userTeams)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        // Check basic permission
        if (!$user->can('appointments.delete')) {
            return false;
        }

        // Admin and Group Director can delete all appointments
        if ($user->hasRole(['Admin', 'Group Director'])) {
            return true;
        }

        // User can delete appointments they scheduled
        if ($appointment->scheduled_by === $user->id) {
            return true;
        }

        // Partner Directors can delete appointments within their organization
        if ($user->hasRole('Partner Director')) {
            $appointmentLeadOrganization = $appointment->lead->organization_id ?? null;
            return $user->organization_id === $appointmentLeadOrganization;
        }

        // Sales Managers can delete appointments of their team members
        if ($user->hasRole('Sales Manager')) {
            $userTeams = $user->teams()->pluck('id');
            $scheduledByUser = User::find($appointment->scheduled_by);
            
            if ($scheduledByUser && $scheduledByUser->teams()->whereIn('team_id', $userTeams)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Appointment $appointment): bool
    {
        return $this->update($user, $appointment);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return $this->delete($user, $appointment);
    }

    /**
     * Determine whether the user can reschedule the appointment.
     */
    public function reschedule(User $user, Appointment $appointment): bool
    {
        return $this->update($user, $appointment);
    }

    /**
     * Determine whether the user can cancel the appointment.
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        // Check basic permission
        if (!$user->can('appointments.update')) {
            return false;
        }

        // Both participants can cancel the appointment
        if ($appointment->scheduled_by === $user->id || $appointment->scheduled_with === $user->id) {
            return true;
        }

        // Higher level roles can cancel appointments under their scope
        return $this->update($user, $appointment);
    }

    /**
     * Determine whether the user can mark the appointment as completed.
     */
    public function markCompleted(User $user, Appointment $appointment): bool
    {
        // Only participants or managers can mark appointments as completed
        if ($appointment->scheduled_by === $user->id || $appointment->scheduled_with === $user->id) {
            return true;
        }

        // Managers can mark appointments of their team members as completed
        return $this->update($user, $appointment);
    }
}
