<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\VenueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
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
});
