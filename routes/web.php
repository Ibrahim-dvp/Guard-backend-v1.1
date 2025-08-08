<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(frontend_url());
});

Route::get('/api', function () {
    return response()->json([
        'message' => 'Guard Backend API',
        'version' => '1.1',
        'status' => 'online',
        'frontend_url' => frontend_url(),
        'api_docs' => env('APP_URL') . '/api/documentation',
        'endpoints' => [
            'auth' => env('APP_URL') . '/api/login',
            'users' => env('APP_URL') . '/api/v1/users',
            'organizations' => env('APP_URL') . '/api/v1/organizations',
            'teams' => env('APP_URL') . '/api/v1/teams',
            'leads' => env('APP_URL') . '/api/v1/leads',
            'appointments' => env('APP_URL') . '/api/v1/appointments',
        ]
    ]);
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'environment' => env('APP_ENV'),
        'database' => 'connected',
        'frontend_url' => frontend_url(),
    ]);
});
