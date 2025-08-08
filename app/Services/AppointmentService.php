<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\AppointmentRepositoryInterface;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function __construct(
        private AppointmentRepositoryInterface $appointmentRepository
    ) {}

    /**
     * Get appointments with filtering and pagination.
     */
    public function getAppointments(User $currentUser, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->appointmentRepository->getAppointments($currentUser, $filters, $perPage);
    }

    /**
     * Get appointment by ID.
     */
    public function getAppointmentById(string $id): ?Appointment
    {
        return $this->appointmentRepository->getAppointmentById($id);
    }

    /**
     * Create a new appointment.
     */
    public function createAppointment(array $data, User $scheduledBy): Appointment
    {
        // Validate appointment timing
        $this->validateAppointmentTiming($data, $scheduledBy);

        // Check for conflicts
        $this->checkForConflicts($data);

        $data['scheduled_by'] = $scheduledBy->id;
        $data['status'] = $data['status'] ?? 'scheduled';

        return DB::transaction(function () use ($data) {
            return $this->appointmentRepository->createAppointment($data);
        });
    }

    /**
     * Update an appointment.
     */
    public function updateAppointment(Appointment $appointment, array $data): Appointment
    {
        // If scheduled_at is being updated, validate timing and check conflicts
        if (isset($data['scheduled_at'])) {
            $this->validateAppointmentTiming($data);
            $this->checkForConflicts($data, $appointment->id);
        }

        return DB::transaction(function () use ($appointment, $data) {
            return $this->appointmentRepository->updateAppointment($appointment, $data);
        });
    }

    /**
     * Delete an appointment.
     */
    public function deleteAppointment(Appointment $appointment): bool
    {
        return $this->appointmentRepository->deleteAppointment($appointment);
    }

    /**
     * Reschedule an appointment.
     */
    public function rescheduleAppointment(Appointment $appointment, string $newDateTime, ?string $notes = null): Appointment
    {
        $data = [
            'scheduled_at' => $newDateTime,
            'status' => 'scheduled', // Reset status when rescheduling
        ];

        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        // Validate new timing and check conflicts
        $this->validateAppointmentTiming($data);
        $this->checkForConflicts($data, $appointment->id);

        return $this->updateAppointment($appointment, $data);
    }

    /**
     * Cancel an appointment.
     */
    public function cancelAppointment(Appointment $appointment, ?string $notes = null): Appointment
    {
        $data = ['status' => 'cancelled'];
        
        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        return $this->updateAppointment($appointment, $data);
    }

    /**
     * Mark appointment as completed.
     */
    public function markAppointmentCompleted(Appointment $appointment, ?string $notes = null): Appointment
    {
        $data = ['status' => 'completed'];
        
        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        return $this->updateAppointment($appointment, $data);
    }

    /**
     * Mark appointment as confirmed.
     */
    public function confirmAppointment(Appointment $appointment): Appointment
    {
        return $this->updateAppointment($appointment, ['status' => 'confirmed']);
    }

    /**
     * Mark appointment as no show.
     */
    public function markAppointmentNoShow(Appointment $appointment, ?string $notes = null): Appointment
    {
        $data = ['status' => 'no_show'];
        
        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        return $this->updateAppointment($appointment, $data);
    }

    /**
     * Get user appointments.
     */
    public function getUserAppointments(string $userId, array $filters = []): Collection
    {
        return $this->appointmentRepository->getUserAppointments($userId, $filters);
    }

    /**
     * Get lead appointments.
     */
    public function getLeadAppointments(string $leadId): Collection
    {
        return $this->appointmentRepository->getLeadAppointments($leadId);
    }

    /**
     * Get upcoming appointments for a user.
     */
    public function getUpcomingAppointments(string $userId, int $days = 7): Collection
    {
        return $this->appointmentRepository->getUpcomingAppointments($userId, $days);
    }

    /**
     * Get appointments by status.
     */
    public function getAppointmentsByStatus(string $status, User $currentUser): Collection
    {
        return $this->appointmentRepository->getAppointmentsByStatus($status, $currentUser);
    }

    /**
     * Get appointments within date range.
     */
    public function getAppointmentsInDateRange(string $startDate, string $endDate, User $currentUser): Collection
    {
        return $this->appointmentRepository->getAppointmentsInDateRange($startDate, $endDate, $currentUser);
    }

    /**
     * Get daily schedule for a user.
     */
    public function getDailySchedule(string $userId, string $date): Collection
    {
        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();

        return $this->appointmentRepository->getAppointmentsInDateRange(
            $startOfDay->toDateTimeString(),
            $endOfDay->toDateTimeString(),
            User::find($userId)
        )->where(function($appointment) use ($userId) {
            return $appointment->scheduled_by === $userId || $appointment->scheduled_with === $userId;
        });
    }

    /**
     * Get weekly schedule for a user.
     */
    public function getWeeklySchedule(string $userId, string $weekStartDate): Collection
    {
        $startOfWeek = Carbon::parse($weekStartDate)->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->endOfWeek();

        return $this->appointmentRepository->getAppointmentsInDateRange(
            $startOfWeek->toDateTimeString(),
            $endOfWeek->toDateTimeString(),
            User::find($userId)
        )->where(function($appointment) use ($userId) {
            return $appointment->scheduled_by === $userId || $appointment->scheduled_with === $userId;
        });
    }

    /**
     * Check if user can access appointment.
     */
    public function canUserAccessAppointment(User $user, Appointment $appointment): bool
    {
        return $this->appointmentRepository->canUserAccessAppointment($user, $appointment);
    }

    /**
     * Get appointment statistics for a user.
     */
    public function getAppointmentStatistics(User $user, ?string $startDate = null, ?string $endDate = null): array
    {
        $filters = [];
        
        if ($startDate) {
            $filters['start_date'] = $startDate;
        }
        
        if ($endDate) {
            $filters['end_date'] = $endDate;
        }

        $appointments = $this->appointmentRepository->getAppointments($user, $filters, 1000);

        $statistics = [
            'total' => $appointments->total(),
            'scheduled' => 0,
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'no_show' => 0,
            'upcoming' => 0,
            'overdue' => 0,
        ];

        $now = Carbon::now();

        foreach ($appointments->items() as $appointment) {
            $statistics[$appointment->status]++;
            
            if (in_array($appointment->status, ['scheduled', 'confirmed'])) {
                $scheduledAt = Carbon::parse($appointment->scheduled_at);
                if ($scheduledAt->isFuture()) {
                    $statistics['upcoming']++;
                } elseif ($scheduledAt->isPast()) {
                    $statistics['overdue']++;
                }
            }
        }

        return $statistics;
    }

    /**
     * Validate appointment timing.
     */
    private function validateAppointmentTiming(array $data, ?User $scheduledBy = null): void
    {
        if (!isset($data['scheduled_at'])) {
            return;
        }

        $scheduledAt = Carbon::parse($data['scheduled_at']);

        // Check if appointment is in the past
        if ($scheduledAt->isPast()) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Appointment cannot be scheduled in the past.'
            ]);
        }

        // Check if appointment is too far in the future (optional business rule)
        $maxFutureDate = Carbon::now()->addMonths(6);
        if ($scheduledAt->isAfter($maxFutureDate)) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Appointment cannot be scheduled more than 6 months in advance.'
            ]);
        }

        // Check business hours (optional business rule)
        $hour = $scheduledAt->hour;
        if ($hour < 8 || $hour > 18) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Appointments can only be scheduled between 8 AM and 6 PM.'
            ]);
        }

        // Check weekends (optional business rule)
        if ($scheduledAt->isWeekend()) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Appointments cannot be scheduled on weekends.'
            ]);
        }
    }

    /**
     * Check for appointment conflicts.
     */
    private function checkForConflicts(array $data, ?string $excludeAppointmentId = null): void
    {
        if (!isset($data['scheduled_at']) || !isset($data['scheduled_with'])) {
            return;
        }

        $duration = $data['duration'] ?? 60;
        $scheduledBy = $data['scheduled_by'] ?? null;
        $scheduledWith = $data['scheduled_with'];

        // Check conflicts for scheduled_with user
        $conflicts = $this->appointmentRepository->getAppointmentConflicts(
            $scheduledWith,
            $data['scheduled_at'],
            $duration,
            $excludeAppointmentId
        );

        if ($conflicts->isNotEmpty()) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'The selected time conflicts with an existing appointment for the participant.'
            ]);
        }

        // Check conflicts for scheduled_by user (if different)
        if ($scheduledBy && $scheduledBy !== $scheduledWith) {
            $conflicts = $this->appointmentRepository->getAppointmentConflicts(
                $scheduledBy,
                $data['scheduled_at'],
                $duration,
                $excludeAppointmentId
            );

            if ($conflicts->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'scheduled_at' => 'The selected time conflicts with an existing appointment for you.'
                ]);
            }
        }
    }
}
