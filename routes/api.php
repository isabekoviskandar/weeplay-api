<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\VenueSlotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('category')->group(function () {
            Route::get('index', [CategoryController::class, 'index']);
            Route::post('create', [CategoryController::class, 'create']);
            Route::put('update/{id}', [CategoryController::class, 'update']);
            Route::delete('delete/{id}', [CategoryController::class, 'destroy']);
        });

        Route::prefix('vanue')->group(function () {
            Route::get('index', [VenueController::class, 'index']);
            Route::get('get-by-category', [VenueController::class, 'getVenueByCategory']);
            Route::get('get-by-user', [VenueController::class, 'getVenueByUser']);
            Route::post('create', [VenueController::class, 'create']);
            Route::put('update/{id}', [VenueController::class, 'update']);
            Route::delete('delete/{id}', [VenueController::class, 'destroy']);
        });

        Route::prefix('slot')->group(function () {
            Route::get('index', [VenueSlotController::class, 'index']);
            Route::get('get-by-venue', [VenueSlotController::class, 'getSlotsByVenue']);
            Route::get('get-by-category', [VenueSlotController::class, 'getSlotsByCategory']);
            Route::get('get-by-user', [VenueSlotController::class, 'getSlotsByUser']);
            Route::post('create', [VenueSlotController::class, 'create']);
        });

        Route::prefix('booking')->group(function () {
            Route::get('get-by-user', [BookingController::class, 'getBookingByUser']);
            Route::post('create', [BookingController::class, 'create']);
        });

        Route::prefix('user')->group(function () {
            Route::get('me', [UserController::class, 'me']);
            Route::put('update-profile', [UserController::class, 'updateProfile']);
            Route::get('get-venues', [UserController::class, 'getUserVenues']);
            Route::get('get-slots', [UserController::class, 'getUserSlots']);
            Route::get('get-bookings', [UserController::class, 'getUserBookings']);
        });
    });
});
