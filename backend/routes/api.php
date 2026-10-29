<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::patch('/bookings/{booking}', [BookingController::class, 'update']);

    Route::middleware(RoleMiddleware::class . ':Host')->group(function () {
        Route::post('/bookings/{booking}/approve', [BookingController::class, 'approve']);
    });

    Route::middleware(RoleMiddleware::class . ':Admin')->group(function () {
        Route::get('/admin/metrics', [AdminController::class, 'metrics']);
        Route::get('/admin/users', [AdminController::class, 'users']);
        Route::patch('/admin/bookings/{booking}', [AdminController::class, 'updateBooking']);
        Route::patch('/admin/users/{user}', [AdminController::class, 'toggleUser']);
    });
});
