<?php

use App\Http\Controllers\AdministrationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('administration', [AdministrationController::class, 'index'])->name('administration.index');
    Route::post('administration/roles', [AdministrationController::class, 'storeRole'])->name('administration.roles.store');
    Route::put('administration/roles/{role}', [AdministrationController::class, 'updateRole'])->name('administration.roles.update');
    Route::delete('administration/roles/{role}', [AdministrationController::class, 'destroyRole'])->name('administration.roles.destroy');
    Route::post('administration/users', [AdministrationController::class, 'storeUser'])->name('administration.users.store');
    Route::put('administration/users/{user}', [AdministrationController::class, 'updateUser'])->name('administration.users.update');
    Route::delete('administration/users/{user}', [AdministrationController::class, 'destroyUser'])->name('administration.users.destroy');
});
