<?php

use App\Http\Controllers\PlanificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('planification')->group(function () {
    Route::view('dashboard', 'direction.planification')->name('planification.dashboard');
    Route::get('codification/{codification}/preview', [PlanificationController::class, 'preview'])->name('planification.codification.preview');
});
