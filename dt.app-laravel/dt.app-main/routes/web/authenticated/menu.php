<?php

use App\Http\Controllers\MenuController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('menu', [MenuController::class, 'index'])->name('menu.index');
    Route::get('menu/direction-generale', [MenuController::class, 'section'])->defaults('section', 'direction-generale')->name('menu.direction-generale');
    Route::get('menu/direction-financiere', [MenuController::class, 'section'])->defaults('section', 'direction-financiere')->name('menu.direction-financiere');
    Route::get('menu/direction-exploitation', [MenuController::class, 'section'])->defaults('section', 'direction-exploitation')->name('menu.direction-exploitation');
    Route::get('menu/facturation', [MenuController::class, 'section'])->defaults('section', 'facturation')->name('menu.facturation');
    Route::get('menu/planification', [MenuController::class, 'section'])->defaults('section', 'planification')->name('menu.planification');
});
