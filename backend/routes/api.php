<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Role-filtered list: guests see theirs, hosts see their listings' bookings, admins see all
    Route::get('/bookings', [BookingController::class, 'index']);

    Route::middleware(RoleMiddleware::class . ':Guest')->group(function () {
        Route::get('/listings', [ListingController::class, 'index']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::patch('/bookings/{booking}', [BookingController::class, 'update']); // guest cancels own request
    });

    Route::middleware(RoleMiddleware::class . ':Host')->group(function () {
        Route::post('/bookings/{booking}/approve', [BookingController::class, 'approve']);
        Route::post('/bookings/{booking}/decline', [BookingController::class, 'decline']);
        Route::get('/host/listings', [ListingController::class, 'hostListings']);
        Route::post('/host/listings', [ListingController::class, 'store']);
        Route::put('/host/listings/{listing}', [ListingController::class, 'update']);
        Route::delete('/host/listings/{listing}', [ListingController::class, 'destroy']);
        Route::get('/host/doorlock', [ListingController::class, 'doorlock']);
        Route::post('/host/access/{booking}/remove', [ListingController::class, 'removeAccess']);
        Route::post('/host/doorlock/{listing}/battery-replaced', [ListingController::class, 'replaceBattery']);
    });

    Route::middleware(RoleMiddleware::class . ':Admin')->group(function () {
        Route::get('/admin/metrics', [AdminController::class, 'metrics']);
        Route::get('/admin/users', [AdminController::class, 'users']);
        Route::patch('/admin/bookings/{booking}', [AdminController::class, 'updateBooking']);
        Route::patch('/admin/users/{user}', [AdminController::class, 'toggleUser']);
    });
});
