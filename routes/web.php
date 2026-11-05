<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PanelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/countries/{country}', [CountryController::class, 'show'])->name('countries.show');

Route::middleware('auth')->group(function () {
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');
    Route::post('/panel/token', [ApiTokenController::class, 'store'])->name('panel.token.store');
    Route::delete('/panel/token', [ApiTokenController::class, 'destroy'])->name('panel.token.destroy');
});
