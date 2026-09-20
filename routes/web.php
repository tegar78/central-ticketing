<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketWebController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Ticket routes (store must be before {id} to avoid route conflict)
    Route::post('/tickets', [TicketWebController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{id}', [TicketWebController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{id}/assign', [TicketWebController::class, 'assignTechnician'])->name('tickets.assign');
    Route::post('/tickets/{id}/status', [TicketWebController::class, 'updateStatus'])->name('tickets.updateStatus');

    // Customer Data Routes
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers/sync-billing', [CustomerController::class, 'syncBilling'])->name('customers.syncBilling');
    Route::get('/billing-instances/{id}/customers', [CustomerController::class, 'getBillingCustomers'])->name('billing.customers');

    // User Management Routes
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
});

