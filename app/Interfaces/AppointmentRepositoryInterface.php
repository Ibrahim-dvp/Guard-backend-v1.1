<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AppointmentRepositoryInterface
{
    /**
     * Get all appointments with filtering and pagination.
     */
    public function getAppointments(User $currentUser, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get appointment by ID with relationships.
     */
    public function getAppointmentById(string $id): ?Appointment;

    /**
     * Create a new appointment.
     */
    public function createAppointment(array $data): Appointment;

    /**
     * Update an appointment.
     */
    public function updateAppointment(Appointment $appointment, array $data): Appointment;

    /**
     * Delete an appointment.
     */
    public function deleteAppointment(Appointment $appointment): bool;

    /**
     * Get appointments for a specific user.
     */
    public function getUserAppointments(string $userId, array $filters = []): Collection;

    /**
     * Get appointments for a specific lead.
     */
    public function getLeadAppointments(string $leadId): Collection;

    /**
     * Get upcoming appointments for a user.
     */
    public function getUpcomingAppointments(string $userId, int $days = 7): Collection;

    /**
     * Get appointments by status.
     */
    public function getAppointmentsByStatus(string $status, User $currentUser): Collection;

    /**
     * Check if user can access appointment.
     */
    public function canUserAccessAppointment(User $user, Appointment $appointment): bool;

    /**
     * Get appointments within date range.
     */
    public function getAppointmentsInDateRange(string $startDate, string $endDate, User $currentUser): Collection;

    /**
     * Get appointment conflicts for a user.
     */
    public function getAppointmentConflicts(string $userId, string $scheduledAt, int $duration, ?string $excludeAppointmentId = null): Collection;
}
