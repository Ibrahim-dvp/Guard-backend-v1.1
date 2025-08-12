<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\SuccessResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentService $appointmentService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);
        
        $filters = $request->only([
            'status', 'lead_id', 'scheduled_by', 
            'start_date', 'end_date', 'search'
        ]);
        
        $perPage = $request->get('per_page', 15);
        $appointments = $this->appointmentService->getAppointments($request->user(), $filters, $perPage);
        
        return new SuccessResource([
            'appointments' => AppointmentResource::collection($appointments->items()),
            'pagination' => [
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
                'per_page' => $appointments->perPage(),
                'total' => $appointments->total(),
                'from' => $appointments->firstItem(),
                'to' => $appointments->lastItem(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAppointmentRequest $request)
    {
        $this->authorize('create', Appointment::class);
        
        $appointment = $this->appointmentService->createAppointment(
            $request->validated(),
            $request->user()
        );
        
        return new SuccessResource(
            new AppointmentResource($appointment),
            'Appointment created successfully.',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);
        
        $appointment = $this->appointmentService->getAppointmentById($appointment->id);
        
        return new SuccessResource(new AppointmentResource($appointment));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        
        $updatedAppointment = $this->appointmentService->updateAppointment(
            $appointment,
            $request->validated()
        );
        
        return new SuccessResource(
            new AppointmentResource($updatedAppointment),
            'Appointment updated successfully.'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);
        
        $this->appointmentService->deleteAppointment($appointment);
        
        return new SuccessResource(null, 'Appointment deleted successfully.');
    }

    /**
     * Reschedule an appointment.
     */
    public function reschedule(Request $request, Appointment $appointment)
    {
        $this->authorize('reschedule', $appointment);
        
        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $updatedAppointment = $this->appointmentService->rescheduleAppointment(
            $appointment,
            $validated['scheduled_at'],
            $validated['notes'] ?? null
        );
        
        return new SuccessResource(
            new AppointmentResource($updatedAppointment),
            'Appointment rescheduled successfully.'
        );
    }

    /**
     * Cancel an appointment.
     */
    public function cancel(Request $request, Appointment $appointment)
    {
        $this->authorize('cancel', $appointment);
        
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $updatedAppointment = $this->appointmentService->cancelAppointment(
            $appointment,
            $validated['notes'] ?? null
        );
        
        return new SuccessResource(
            new AppointmentResource($updatedAppointment),
            'Appointment cancelled successfully.'
        );
    }

    /**
     * Mark appointment as completed.
     */
    public function markCompleted(Request $request, Appointment $appointment)
    {
        $this->authorize('markCompleted', $appointment);
        
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $updatedAppointment = $this->appointmentService->markAppointmentCompleted(
            $appointment,
            $validated['notes'] ?? null
        );
        
        return new SuccessResource(
            new AppointmentResource($updatedAppointment),
            'Appointment marked as completed.'
        );
    }

    /**
     * Confirm an appointment.
     */
    public function confirm(Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        
        $updatedAppointment = $this->appointmentService->confirmAppointment($appointment);
        
        return new SuccessResource(
            new AppointmentResource($updatedAppointment),
            'Appointment confirmed successfully.'
        );
    }

    /**
     * Mark appointment as no show.
     */
    public function markNoShow(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $updatedAppointment = $this->appointmentService->markAppointmentNoShow(
            $appointment,
            $validated['notes'] ?? null
        );
        
        return new SuccessResource(
            new AppointmentResource($updatedAppointment),
            'Appointment marked as no show.'
        );
    }

    /**
     * Update appointment status.
     */
    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        
        $validatedData = $request->validated();
        $updatedAppointment = $this->appointmentService->updateAppointmentStatus(
            $appointment,
            $validatedData['status'],
            $validatedData['reason'] ?? null,
            $validatedData['notes'] ?? null
        );
        
        return new SuccessResource(
            new AppointmentResource($updatedAppointment),
            'Appointment status updated successfully.'
        );
    }

    /**
     * Get upcoming appointments for the authenticated user.
     */
    public function upcoming(Request $request)
    {
        $days = $request->get('days', 7);
        $appointments = $this->appointmentService->getUpcomingAppointments($request->user()->id, $days);
        
        return new SuccessResource(AppointmentResource::collection($appointments));
    }

    /**
     * Get appointments by status.
     */
    public function byStatus(Request $request, string $status)
    {
        $this->authorize('viewAny', Appointment::class);
        
        $appointments = $this->appointmentService->getAppointmentsByStatus($status, $request->user());
        
        return new SuccessResource(AppointmentResource::collection($appointments));
    }

    /**
     * Get appointment statistics.
     */
    public function statistics(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);
        
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        
        $statistics = $this->appointmentService->getAppointmentStatistics(
            $request->user(),
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );
        
        return new SuccessResource($statistics);
    }

    /**
     * Get daily schedule.
     */
    public function dailySchedule(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'user_id' => 'nullable|uuid|exists:users,id',
        ]);
        
        $userId = $validated['user_id'] ?? $request->user()->id;
        
        // Check if user can view the schedule
        if ($userId !== $request->user()->id) {
            $this->authorize('viewAny', Appointment::class);
        }
        
        $appointments = $this->appointmentService->getDailySchedule($userId, $validated['date']);
        
        return new SuccessResource(AppointmentResource::collection($appointments));
    }

    /**
     * Get weekly schedule.
     */
    public function weeklySchedule(Request $request)
    {
        $validated = $request->validate([
            'week_start' => 'required|date',
            'user_id' => 'nullable|uuid|exists:users,id',
        ]);
        
        $userId = $validated['user_id'] ?? $request->user()->id;
        
        // Check if user can view the schedule
        if ($userId !== $request->user()->id) {
            $this->authorize('viewAny', Appointment::class);
        }
        
        $appointments = $this->appointmentService->getWeeklySchedule($userId, $validated['week_start']);
        
        return new SuccessResource(AppointmentResource::collection($appointments));
    }
}
