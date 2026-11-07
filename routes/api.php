<?php

use App\Http\Controllers\Api\TripStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/trips/{trip}/status', TripStatusController::class)->name('api.trips.status.update');
});
