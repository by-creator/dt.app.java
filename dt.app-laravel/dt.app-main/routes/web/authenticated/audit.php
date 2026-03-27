<?php

use App\Http\Controllers\AuditController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('audit/export/csv', [AuditController::class, 'exportCsv'])->name('audit.export.csv');
    Route::get('audit/export/pdf', [AuditController::class, 'exportPdf'])->name('audit.export.pdf');
});
