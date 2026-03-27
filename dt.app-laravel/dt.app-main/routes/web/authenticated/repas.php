<?php

use App\Http\Controllers\RepasController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('repas', [RepasController::class, 'index'])->name('repas.index');
    Route::post('repas/menu-du-jour', [RepasController::class, 'sendMenuDuJour'])->name('repas.menu-du-jour');
});
