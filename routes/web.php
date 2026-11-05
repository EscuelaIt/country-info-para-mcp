<?php

use App\Http\Controllers\CountryController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/countries/{country}', [CountryController::class, 'show'])->name('countries.show');

Route::view('/panel', 'panel')->middleware('auth')->name('panel');
