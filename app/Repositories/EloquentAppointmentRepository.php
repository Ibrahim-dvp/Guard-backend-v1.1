<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\AppointmentRepositoryInterface;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentAppointmentRepository implements AppointmentRepositoryInterface
{
    /**
     * Get all appointments with filtering and pagination.
     */
    public function getAppointments(User $currentUser, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Appointment::with(['lead', 'scheduledBy', 'scheduledWith']);

        // Apply role-based filtering
        $this->applyRoleBasedFiltering($query, $currentUser);

        // Apply additional filters
        $this->applyFilters($query, $filters);

        return $query->orderBy('scheduled_at', 'desc')->paginate($perPage);
    }

    /**
     * Get appointment by ID with relationships.
     */
    public function getAppointmentById(string $id): ?Appointment
    {
        return Appointment::with(['lead', 'scheduledBy', 'scheduledWith'])->find($id);
    }

    /**
     * Create a new appointment.
     */
    public function createAppointment(array $data): Appointment
    {
        $appointment = Appointment::create($data);
        return $appointment->load(['lead', 'scheduledBy', 'scheduledWith']);
    }

    /**
     * Update an appointment.
     */
    public function updateAppointment(Appointment $appointment, array $data): Appointment
    {
        $appointment->update($data);
        return $appointment->load(['lead', 'scheduledBy', 'scheduledWith']);
    }

    /**
     * Delete an appointment.
     */
    public function deleteAppointment(Appointment $appointment): bool
    {
        return $appointment->delete();
    }

    /**
     * Get appointments for a specific user.
     */
    public function getUserAppointments(string $userId, array $filters = []): Collection
    {
        $query = Appointment::with(['lead', 'scheduledBy', 'scheduledWith'])
            ->where(function ($q) use ($userId) {
                $q->where('scheduled_by', $userId)
                  ->orWhere('scheduled_with', $userId);
            });

        $this->applyFilters($query, $filters);

        return $query->orderBy('scheduled_at', 'desc')->get();
    }

    /**
     * Get appointments for a specific lead.
     */
    public function getLeadAppointments(string $leadId): Collection
    {
        return Appointment::with(['scheduledBy', 'scheduledWith'])
            ->where('lead_id', $leadId)
            ->orderBy('scheduled_at', 'desc')
            ->get();
    }

    /**
     * Get upcoming appointments for a user.
     */
    public function getUpcomingAppointments(string $userId, int $days = 7): Collection
    {
        $endDate = Carbon::now()->addDays($days);

        return Appointment::with(['lead', 'scheduledBy', 'scheduledWith'])
            ->where(function ($q) use ($userId) {
                $q->where('scheduled_by', $userId)
                  ->orWhere('scheduled_with', $userId);
            })
            ->where('scheduled_at', '>=', Carbon::now())
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    /**
     * Get appointments by status.
     */
    public function getAppointmentsByStatus(string $status, User $currentUser): Collection
    {
        $query = Appointment::with(['lead', 'scheduledBy', 'scheduledWith'])
            ->where('status', $status);

        $this->applyRoleBasedFiltering($query, $currentUser);

        return $query->orderBy('scheduled_at', 'desc')->get();
    }

    /**
     * Check if user can access appointment.
     */
    public function canUserAccessAppointment(User $user, Appointment $appointment): bool
    {
        // Admin and Group Director can access all appointments
        if ($user->hasRole(['Admin', 'Group Director'])) {
            return true;
        }

        // User can access appointments they scheduled or are scheduled with
        if ($appointment->scheduled_by === $user->id || $appointment->scheduled_with === $user->id) {
            return true;
        }

        // Partner Directors can access appointments within their organization
        if ($user->hasRole('Partner Director')) {
            $appointmentLeadOrganization = $appointment->lead->organization_id ?? null;
            return $user->organization_id === $appointmentLeadOrganization;
        }

        // Sales Managers can access appointments of their team members
        if ($user->hasRole('Sales Manager')) {
            $appointmentLeadOrganization = $appointment->lead->organization_id ?? null;
            
            if ($user->organization_id === $appointmentLeadOrganization) {
                return true;
            }

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

        // Sales Agents can access appointments for leads assigned to them
        if ($user->hasRole('Sales Agent')) {
            return $appointment->lead->assigned_to_id === $user->id;
        }

        // Coordinators can access appointments within their organization
        if ($user->hasRole('Coordinator')) {
            $appointmentLeadOrganization = $appointment->lead->organization_id ?? null;
            return $user->organization_id === $appointmentLeadOrganization;
        }

        return false;
    }

    /**
     * Get appointments within date range.
     */
    public function getAppointmentsInDateRange(string $startDate, string $endDate, User $currentUser): Collection
    {
        $query = Appointment::with(['lead', 'scheduledBy', 'scheduledWith'])
            ->whereBetween('scheduled_at', [$startDate, $endDate]);

        $this->applyRoleBasedFiltering($query, $currentUser);

        return $query->orderBy('scheduled_at', 'asc')->get();
    }

    /**
     * Get appointment conflicts for a user.
     */
    public function getAppointmentConflicts(string $userId, string $scheduledAt, int $duration, ?string $excludeAppointmentId = null): Collection
    {
        $startTime = Carbon::parse($scheduledAt);
        $endTime = $startTime->copy()->addMinutes($duration);

        $query = Appointment::where(function ($q) use ($userId) {
                $q->where('scheduled_by', $userId)
                  ->orWhere('scheduled_with', $userId);
            })
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('scheduled_at', [$startTime, $endTime])
                  ->orWhere(function ($subQ) use ($startTime, $endTime) {
                      $subQ->where('scheduled_at', '<=', $startTime)
                           ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration MINUTE) > ?', [$startTime]);
                  });
            });

        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        return $query->with(['lead', 'scheduledBy', 'scheduledWith'])->get();
    }

    /**
     * Apply role-based filtering to the query.
     */
    private function applyRoleBasedFiltering($query, User $currentUser): void
    {
        if ($currentUser->hasRole(['Admin', 'Group Director'])) {
            // No filtering for admins
            return;
        }

        if ($currentUser->hasRole('Partner Director')) {
            // Partner Directors see appointments within their organization
            $query->whereHas('lead', function($q) use ($currentUser) {
                $q->where('organization_id', $currentUser->organization_id);
            });
        } elseif ($currentUser->hasRole('Sales Manager')) {
            // Sales Managers see appointments of their team members or organization
            $userTeams = $currentUser->teams()->pluck('id');
            $query->where(function($q) use ($currentUser, $userTeams) {
                $q->whereHas('lead', function($leadQuery) use ($currentUser) {
                    $leadQuery->where('organization_id', $currentUser->organization_id);
                })
                ->orWhere('scheduled_by', $currentUser->id)
                ->orWhere('scheduled_with', $currentUser->id)
                ->orWhereHas('scheduledBy.teams', function($teamQuery) use ($userTeams) {
                    $teamQuery->whereIn('team_id', $userTeams);
                })
                ->orWhereHas('scheduledWith.teams', function($teamQuery) use ($userTeams) {
                    $teamQuery->whereIn('team_id', $userTeams);
                });
            });
        } elseif ($currentUser->hasRole('Coordinator')) {
            // Coordinators see appointments within their organization
            $query->whereHas('lead', function($q) use ($currentUser) {
                $q->where('organization_id', $currentUser->organization_id);
            });
        } else {
            // Sales Agents and others see only their own appointments
            $query->where(function($q) use ($currentUser) {
                $q->where('scheduled_by', $currentUser->id)
                  ->orWhere('scheduled_with', $currentUser->id)
                  ->orWhereHas('lead', function($leadQuery) use ($currentUser) {
                      $leadQuery->where('assigned_to_id', $currentUser->id);
                  });
            });
        }
    }

    /**
     * Apply additional filters to the query.
     */
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['lead_id'])) {
            $query->where('lead_id', $filters['lead_id']);
        }

        if (isset($filters['scheduled_by'])) {
            $query->where('scheduled_by', $filters['scheduled_by']);
        }

        if (isset($filters['scheduled_with'])) {
            $query->where('scheduled_with', $filters['scheduled_with']);
        }

        if (isset($filters['start_date'])) {
            $query->where('scheduled_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('scheduled_at', '<=', $filters['end_date']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('lead', function($leadQuery) use ($search) {
                      $leadQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
    }
}
