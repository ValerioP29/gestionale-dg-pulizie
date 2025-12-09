<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Mobile\WorkSessionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['guest', 'throttle:5,1']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('throttle:60,1');

    Route::get('/me', [AuthController::class, 'me'])
        ->middleware('throttle:60,1');

    Route::get('/mobile/work-sessions/current', [WorkSessionController::class, 'current'])
        ->middleware('throttle:60,1');

    Route::post('/mobile/work-sessions/punch', [WorkSessionController::class, 'punch'])
        ->middleware('throttle:60,1');
});
