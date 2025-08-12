<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentication routes
Route::post('/auth/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('logout');
});

// Main API routes expected by frontend
Route::middleware('auth:sanctum')->group(function () {
    // User Management
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    
    // Organization Management  
    Route::apiResource('organizations', OrganizationController::class);
    Route::patch('organizations/{organization}/toggle-status', [OrganizationController::class, 'toggleStatus']);
    
    //team Management
    Route::apiResource('teams', TeamController::class);
    Route::post('teams/{team}/users', [TeamController::class, 'addUser']);
    Route::delete('teams/{team}/users/{user}', [TeamController::class, 'removeUser']);
    Route::get('teams/{team}/users', [TeamController::class, 'getTeamUsers']);
    Route::get('users/{user}/teams', [TeamController::class, 'getUserTeams']);
    Route::get('my-teams', [TeamController::class, 'getUserTeams']);
    
    // Lead Management
    Route::apiResource('leads', LeadController::class);
    Route::post('leads/{lead}/assign', [LeadController::class, 'assign']);
    Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus']);
    Route::get('leads/status/{status}', [LeadController::class, 'getByStatus']);
    
    // Appointment Management
    Route::apiResource('appointments', AppointmentController::class);
    Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
    Route::patch('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule']);
    Route::get('appointments/upcoming', [AppointmentController::class, 'upcoming']);
    Route::get('appointments/status/{status}', [AppointmentController::class, 'byStatus']);
    Route::get('appointments/statistics', [AppointmentController::class, 'statistics']);
    Route::get('appointments/schedule/daily', [AppointmentController::class, 'dailySchedule']);
    Route::get('appointments/schedule/weekly', [AppointmentController::class, 'weeklySchedule']);
    
    // Dashboard & Analytics
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::delete('dashboard/cache', [DashboardController::class, 'clearCache']); // Add cache clearing
    
    // Settings
    Route::get('settings', [SettingsController::class, 'index']);
    Route::put('settings', [SettingsController::class, 'update']);
    Route::post('settings/reset', [SettingsController::class, 'reset']);
    
    // Team performance and specific user endpoints
    Route::get('performance/team/{managerId}', [DashboardController::class, 'teamPerformance']);
    Route::get('users/team/{managerId}', [UserController::class, 'getTeamMembers']);
    Route::get('users/managers/capacity', [UserController::class, 'getManagersWithCapacity']);
});

// Keep V1 routes for backward compatibility
// Route::group(['prefix' => 'v1', 'as' => 'api.v1.', 'middleware' => 'auth:sanctum'], function () {
//     Route::apiResource('users', UserController::class);
//     Route::apiResource('organizations', OrganizationController::class);
//     Route::apiResource('leads', LeadController::class);
//     Route::apiResource('teams', TeamController::class);
//     Route::apiResource('appointments', AppointmentController::class);

//     // Lead specific routes
//     Route::post('leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
//     Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.updateStatus');

//     // Team member management routes
//     Route::post('teams/{team}/users', [TeamController::class, 'addUser'])->name('teams.addUser');
//     Route::delete('teams/{team}/users/{user}', [TeamController::class, 'removeUser'])->name('teams.removeUser');
//     Route::get('teams/{team}/users', [TeamController::class, 'getTeamUsers'])->name('teams.users');
//     Route::get('users/{user}/teams', [TeamController::class, 'getUserTeams'])->name('users.teams');
//     Route::get('my-teams', [TeamController::class, 'getUserTeams'])->name('my.teams');
    
//     // Organization-based team routes
//     Route::get('organizations/{organizationId}/teams', [TeamController::class, 'getByOrganization'])->name('organizations.teams');

//     // Appointment specific routes
//     Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.updateStatus');
//     Route::patch('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
//     Route::get('appointments/upcoming', [AppointmentController::class, 'upcoming'])->name('appointments.upcoming');
//     Route::get('appointments/status/{status}', [AppointmentController::class, 'byStatus'])->name('appointments.byStatus');
//     Route::get('appointments/statistics', [AppointmentController::class, 'statistics'])->name('appointments.statistics');
//     Route::get('appointments/schedule/daily', [AppointmentController::class, 'dailySchedule'])->name('appointments.dailySchedule');
//     Route::get('appointments/schedule/weekly', [AppointmentController::class, 'weeklySchedule'])->name('appointments.weeklySchedule');
// });