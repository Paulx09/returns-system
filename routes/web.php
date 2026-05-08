<?php

use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReturnTicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('returns.start');
});

Route::prefix('returns')->name('returns.')->group(function () {

    // Acceso público
    Route::get('/start', [CustomerAuthController::class, 'create'])->name('start');
    Route::post('/login', [CustomerAuthController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login');
    Route::post('/logout', [CustomerAuthController::class, 'destroy'])->name('logout');

    // Rutas protegidas
    Route::middleware('customer.auth')->group(function () {
        Route::get('/dashboard', [ReturnTicketController::class, 'dashboard'])->name('dashboard');
        Route::post('/tickets', [ReturnTicketController::class, 'store'])->name('tickets.store');
        Route::get('/success', [ReturnTicketController::class, 'success'])->name('success');
    });
});

// Perfil de usuario administrativo (Laravel Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
