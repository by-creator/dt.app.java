<?php

use App\Http\Controllers\FacturationApiController;
use App\Http\Controllers\FacturationController;
use App\Http\Controllers\GfaDisplayController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\SuiviStationnementController;
use App\Http\Controllers\TiersUnifyController;
use App\Http\Controllers\UnifyPrintController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('facturation')->group(function () {
    Route::view('dashboard', 'facturation.dashboard')->name('facturation.dashboard');
    Route::get('validations', [FacturationController::class, 'validations'])->name('facturation.validations');
    Route::get('remises', [FacturationController::class, 'remises'])->name('facturation.remises');
    Route::view('unify', 'facturation.unify')->name('facturation.unify');
    Route::get('rapport', [FacturationController::class, 'rapport'])->name('facturation.rapport');
    Route::get('ies', [FacturationController::class, 'ies'])->name('facturation.ies');
    Route::post('ies/lien-acces', [FacturationController::class, 'sendIesAccessLink'])->name('facturation.ies.link');
    Route::post('ies/creation-compte', [FacturationController::class, 'sendIesAccountCreated'])->name('facturation.ies.create');
    Route::post('ies/reset-password', [FacturationController::class, 'sendIesPasswordReset'])->name('facturation.ies.reset');
    Route::get('gfa-admin', [GfaDisplayController::class, 'gfaAdmin'])->name('facturation.gfa-admin');
    Route::post('gfa-admin/wifi-settings', [GfaDisplayController::class, 'saveWifiSettings'])->name('facturation.gfa-admin.wifi-settings');

    Route::prefix('api')->group(function () {
        Route::get('rapports', [RapportController::class, 'index']);
        Route::post('rapports/import', [RapportController::class, 'import']);
        Route::get('rapports/export', [RapportController::class, 'exportExcel']);
        Route::delete('rapports/{suivi}', [RapportController::class, 'destroy']);
        Route::get('suivi-stationnements', [SuiviStationnementController::class, 'index']);
        Route::post('suivi-stationnements/import', [SuiviStationnementController::class, 'import']);
        Route::get('suivi-stationnements/export', [SuiviStationnementController::class, 'exportExcel']);
        Route::delete('suivi-stationnements/{suivi}', [SuiviStationnementController::class, 'destroy']);
        Route::get('rattachements', [FacturationApiController::class, 'listRattachements'])->name('facturation.api.rattachements.index');
        Route::patch('rattachements/{rattachement}/valider', [FacturationApiController::class, 'validateRattachement'])->name('facturation.api.rattachements.validate');
        Route::patch('rattachements/{rattachement}/rejeter', [FacturationApiController::class, 'rejectRattachement'])->name('facturation.api.rattachements.reject');
        Route::get('remises', [FacturationApiController::class, 'listRemises'])->name('facturation.api.remises.index');
        Route::patch('remises/{rattachement}/valider', [FacturationApiController::class, 'validateRemise'])->name('facturation.api.remises.validate');
        Route::patch('remises/{rattachement}/rejeter', [FacturationApiController::class, 'rejectRemise'])->name('facturation.api.remises.reject');
        Route::post('tiers-unify/save', [TiersUnifyController::class, 'save']);
        Route::get('tiers-unify', [TiersUnifyController::class, 'index']);
        Route::get('tiers-unify/export', [TiersUnifyController::class, 'exportCsv']);
        Route::get('tiers-unify/export/xlsx', [TiersUnifyController::class, 'exportExcel']);
        Route::post('tiers-unify/import', [TiersUnifyController::class, 'import']);
        Route::put('tiers-unify/{tiers}', [TiersUnifyController::class, 'update']);
        Route::delete('tiers-unify/{tiers}', [TiersUnifyController::class, 'destroy']);
    });

    Route::post('unify/print/fiche', [UnifyPrintController::class, 'printFiche']);
    Route::post('unify/print/attestation', [UnifyPrintController::class, 'printAttestation']);
});
