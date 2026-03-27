<?php

use App\Http\Controllers\GfaApiController;
use App\Http\Controllers\GfaDisplayController;
use Illuminate\Support\Facades\Route;

Route::view('/gfa/guichet', 'facturation.public-guichet-gfa')->name('facturation.guichet-gfa.public');
Route::get('/gfa/admin', [GfaDisplayController::class, 'display'])->name('facturation.gfa-admin.public');
Route::get('/gfa/display', [GfaDisplayController::class, 'display']);
Route::get('/gfa/ticket/go', [GfaDisplayController::class, 'ticketGo'])->name('gfa.ticket.go');
Route::get('/gfa/ticket', [GfaDisplayController::class, 'ticket'])->name('gfa.ticket');

Route::prefix('gfa/api')->group(function () {
    Route::get('services', [GfaApiController::class, 'getServices']);
    Route::post('services', [GfaApiController::class, 'createService']);
    Route::put('services/{service}', [GfaApiController::class, 'updateService']);
    Route::delete('services/{service}', [GfaApiController::class, 'deleteService']);
    Route::get('guichets', [GfaApiController::class, 'getGuichets']);
    Route::post('guichets', [GfaApiController::class, 'createGuichet']);
    Route::put('guichets/{guichet}', [GfaApiController::class, 'updateGuichet']);
    Route::delete('guichets/{guichet}', [GfaApiController::class, 'deleteGuichet']);
    Route::get('agents', [GfaApiController::class, 'getAgents']);
    Route::post('agents', [GfaApiController::class, 'createAgent']);
    Route::put('agents/{agent}', [GfaApiController::class, 'updateAgent']);
    Route::delete('agents/{agent}', [GfaApiController::class, 'deleteAgent']);
    Route::get('guichet/{guichetId}/info', [GfaApiController::class, 'getGuichetInfo']);
    Route::get('guichet/{guichetId}/waiting', [GfaApiController::class, 'getWaitingForGuichet']);
    Route::get('guichet/{guichetId}/current', [GfaApiController::class, 'getCurrentForGuichet']);
    Route::post('guichet/call-next', [GfaApiController::class, 'callNextForGuichet']);
    Route::post('guichet/recall', [GfaApiController::class, 'recallTicket']);
    Route::patch('guichet/ticket/{id}/termine', [GfaApiController::class, 'termineTicket']);
    Route::patch('guichet/ticket/{id}/incomplet', [GfaApiController::class, 'incompletTicket']);
    Route::patch('guichet/ticket/{id}/absent', [GfaApiController::class, 'absentTicket']);
    Route::get('stats', [GfaApiController::class, 'getStats']);
    Route::get('tickets', [GfaApiController::class, 'listTickets']);
    Route::get('tickets/export', [GfaApiController::class, 'exportTickets']);
    Route::delete('tickets/truncate', [GfaApiController::class, 'truncateTickets']);
    Route::get('scan-token', [GfaApiController::class, 'generateScanToken']);
    Route::post('tickets', [GfaApiController::class, 'createTicketPublic']);
});
