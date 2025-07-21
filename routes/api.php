<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);

Route::group(['prefix' => 'v1', 'as' => 'api.v1.', 'middleware' => 'auth:sanctum'], function () {
    Route::apiResource('users', UserController::class);
});
