<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\TicketApiController;
use App\Http\Controllers\Api\V1\CustomerSyncController;
use App\Http\Middleware\CheckTenantApiKey;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Central Ticket System API for 60 Billing Instances (Rate Limited to 120 req/min)
Route::prefix('v1')->middleware([CheckTenantApiKey::class, 'throttle:120,1'])->group(function () {
    Route::post('/tickets', [TicketApiController::class, 'store']);
    Route::get('/tickets', [TicketApiController::class, 'list']);
    Route::get('/tickets/{id}', [TicketApiController::class, 'show']);

    // Customer Synchronization Endpoint (Single & Batch)
    Route::post('/sync-customer', [CustomerSyncController::class, 'sync']);
});

