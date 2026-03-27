<?php

use App\Http\Controllers\FacturationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('direction')->group(function () {
    Route::view('dashboard', 'direction.dashboard')->name('direction.dashboard');
    Route::view('financiere', 'direction.financiere')->name('direction.financiere');
    Route::view('exploitation', 'direction.exploitation')->name('direction.exploitation');
    Route::get('remises', [FacturationController::class, 'remises'])->name('direction.remises');
});
