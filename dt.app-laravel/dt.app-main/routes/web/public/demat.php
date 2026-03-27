<?php

use App\Http\Controllers\DematFormController;
use Illuminate\Support\Facades\Route;

Route::view('/demat', 'demat.index')->name('demat');
Route::view('/demat/paiement', 'demat.paiement')->name('demat.paiement');
Route::get('/demat/validation', [DematFormController::class, 'validationForm'])->name('demat.validation');
Route::post('/demat/validation', [DematFormController::class, 'submitValidation']);
Route::get('/demat/remise', [DematFormController::class, 'remiseForm'])->name('demat.remise');
Route::post('/demat/remise', [DematFormController::class, 'submitRemise']);
