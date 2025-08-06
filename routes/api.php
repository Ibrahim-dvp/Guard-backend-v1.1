<?php

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

    // Lead specific routes
    Route::post('leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
    Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.updateStatus');

    // Team member management routes
    Route::post('teams/{team}/users', [TeamController::class, 'addUser'])->name('teams.addUser');
    Route::delete('teams/{team}/users/{user}', [TeamController::class, 'removeUser'])->name('teams.removeUser');
    Route::get('teams/{team}/users', [TeamController::class, 'getTeamUsers'])->name('teams.users');
    Route::get('users/{user}/teams', [TeamController::class, 'getUserTeams'])->name('users.teams');
    Route::get('my-teams', [TeamController::class, 'getUserTeams'])->name('my.teams');
});