<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketWebController;
use App\Http\Controllers\TicketExportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\OdpController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Ticket Export Routes (must be before /tickets/{id} to avoid conflict)
    Route::get('/tickets/export/csv', [TicketExportController::class, 'exportCsv'])->name('tickets.export.csv');
    Route::get('/tickets/export/excel', [TicketExportController::class, 'exportExcel'])->name('tickets.export.excel');
    Route::get('/tickets/export/pdf', [TicketExportController::class, 'exportPdf'])->name('tickets.export.pdf');

    // Ticket routes (store & index must be before {id} to avoid route conflict)
    Route::get('/tickets', [TicketWebController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [TicketWebController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{id}', [TicketWebController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{id}/assign', [TicketWebController::class, 'assignTechnician'])->name('tickets.assign');
    Route::post('/tickets/{id}/status', [TicketWebController::class, 'updateStatus'])->name('tickets.updateStatus');

    // Customer Data Routes
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/live-search', [CustomerController::class, 'liveSearch'])->name('customers.liveSearch');
    Route::post('/customers/sync-billing', [CustomerController::class, 'syncBilling'])->name('customers.syncBilling')->middleware('throttle:10,1');
    Route::get('/billing-instances/{id}/customers', [CustomerController::class, 'getBillingCustomers'])->name('billing.customers');
    Route::post('/customers/{customer}/ping', [CustomerController::class, 'ping'])->name('customers.ping')->middleware('throttle:30,1');
    Route::put('/customers/{customer}/network', [CustomerController::class, 'updateNetworkInfo'])->name('customers.updateNetwork');
    Route::post('/customers/{id}/coordinates', [MapController::class, 'updateCoordinates'])->name('customers.updateCoordinates');

    // Maps Location Pelanggan (Synchronized from Billtest)
    Route::get('/maps', [MapController::class, 'index'])->name('maps.index');

    // Maps & Management ODP (Optical Distribution Point)
    Route::get('/maps/odp', [OdpController::class, 'index'])->name('odp.index');
    Route::post('/maps/odp', [OdpController::class, 'store'])->name('odp.store');
    Route::post('/maps/odp/sync', [OdpController::class, 'syncBillingOdps'])->name('odp.sync')->middleware('throttle:10,1');
    Route::get('/maps/odp/{id}', [OdpController::class, 'show'])->whereNumber('id')->name('odp.show');
    Route::post('/maps/odp/{id}', [OdpController::class, 'update'])->whereNumber('id')->name('odp.update');
    Route::delete('/maps/odp/{id}', [OdpController::class, 'destroy'])->whereNumber('id')->name('odp.destroy');
    Route::post('/maps/odp/{id}/photo', [OdpController::class, 'uploadPhoto'])->whereNumber('id')->name('odp.uploadPhoto')->middleware('throttle:15,1');
    Route::get('/maps/odp/{id}/ports', [OdpController::class, 'ports'])->whereNumber('id')->name('odp.ports');
    Route::get('/maps/odp/{id}/search-customers', [OdpController::class, 'searchAvailableCustomers'])->whereNumber('id')->name('odp.searchCustomers');
    Route::post('/maps/odp/{id}/assign-port', [OdpController::class, 'assignPort'])->whereNumber('id')->name('odp.assignPort');
    Route::post('/maps/odp/{id}/detach-port', [OdpController::class, 'detachPort'])->whereNumber('id')->name('odp.detachPort');

    // System Activity Logs
    Route::get('/activities', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activities.index');
    Route::get('/logs', function () { return redirect()->route('activities.index'); });

    // User Management & System Routes (Strictly Restricted to Admin Role)
    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

        // Database Backup Routes
        Route::get('/backups', [\App\Http\Controllers\BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [\App\Http\Controllers\BackupController::class, 'create'])->name('backups.create')->middleware('throttle:5,1');
        Route::get('/backups/{filename}/download', [\App\Http\Controllers\BackupController::class, 'download'])->name('backups.download');
        Route::delete('/backups/{filename}', [\App\Http\Controllers\BackupController::class, 'destroy'])->name('backups.destroy');
        Route::post('/backups/clean', [\App\Http\Controllers\BackupController::class, 'clean'])->name('backups.clean');
    });
});

