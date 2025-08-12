<?php

use Illuminate\Support\Facades\Route;

// Main redirect - when someone visits your API URL, redirect to frontend
Route::get('/', function () {
    return redirect(frontend_url());
});

// Redirect common frontend routes to frontend with preserved paths
Route::get('/login', function () {
    return redirect(frontend_url('/login'));
});

Route::get('/dashboard', function () {
    return redirect(frontend_url('/dashboard'));
});

Route::get('/leads', function () {
    return redirect(frontend_url('/leads'));
});

Route::get('/users', function () {
    return redirect(frontend_url('/users'));
});

// API info endpoint - shows API information
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

// Health check endpoint - for monitoring
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'environment' => env('APP_ENV'),
        'database' => 'connected',
        'frontend_url' => frontend_url(),
    ]);
});

// Catch-all route for any other web requests - redirect to frontend
Route::fallback(function () {
    return redirect(frontend_url());
});
