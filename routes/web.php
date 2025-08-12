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
            'auth' => env('APP_URL') . '/api/auth/login',
            'users' => env('APP_URL') . '/api/users',
            'organizations' => env('APP_URL') . '/api/organizations',
            'leads' => env('APP_URL') . '/api/leads',
            'appointments' => env('APP_URL') . '/api/appointments',
            'dashboard' => env('APP_URL') . '/api/dashboard/stats',
            'settings' => env('APP_URL') . '/api/settings',
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
