<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;




Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);

Route::group(['prefix' => 'v1', 'as' => 'api.v1.', 'middleware' => 'auth:sanctum'], function () {
    Route::apiResource('users', UserController::class);
    Route::apiResource('organizations', OrganizationController::class);
    Route::apiResource('leads', LeadController::class);
    Route::apiResource('teams', TeamController::class);
    Route::apiResource('appointments', AppointmentController::class);

    // Lead specific routes
    Route::post('leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
    Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.updateStatus');

    // Team member management routes
    Route::post('teams/{team}/users', [TeamController::class, 'addUser'])->name('teams.addUser');
    Route::delete('teams/{team}/users/{user}', [TeamController::class, 'removeUser'])->name('teams.removeUser');
    Route::get('teams/{team}/users', [TeamController::class, 'getTeamUsers'])->name('teams.users');
    Route::get('users/{user}/teams', [TeamController::class, 'getUserTeams'])->name('users.teams');
    Route::get('my-teams', [TeamController::class, 'getUserTeams'])->name('my.teams');
    
    // Organization-based team routes
    Route::get('organizations/{organizationId}/teams', [TeamController::class, 'getByOrganization'])->name('organizations.teams');

    // Appointment specific routes
    Route::patch('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::patch('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::patch('appointments/{appointment}/complete', [AppointmentController::class, 'markCompleted'])->name('appointments.complete');
    Route::patch('appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
    Route::patch('appointments/{appointment}/no-show', [AppointmentController::class, 'markNoShow'])->name('appointments.noShow');
    Route::get('appointments/upcoming', [AppointmentController::class, 'upcoming'])->name('appointments.upcoming');
    Route::get('appointments/status/{status}', [AppointmentController::class, 'byStatus'])->name('appointments.byStatus');
    Route::get('appointments/statistics', [AppointmentController::class, 'statistics'])->name('appointments.statistics');
    Route::get('appointments/schedule/daily', [AppointmentController::class, 'dailySchedule'])->name('appointments.dailySchedule');
    Route::get('appointments/schedule/weekly', [AppointmentController::class, 'weeklySchedule'])->name('appointments.weeklySchedule');
});