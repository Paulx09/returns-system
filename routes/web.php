<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

use App\Http\Controllers\CustomerAuthController;

Route::prefix('returns')->group(function () {
    Route::get('/start', [CustomerAuthController::class, 'create'])->name('returns.start');
    Route::post('/login', [CustomerAuthController::class, 'store'])
        ->middleware('throttle:5,1') // Rate limiting: 5 requests per minute
        ->name('returns.login');
        
    // Placeholder for Customer dashboard (Sprint 3)
    Route::middleware('customer.auth')->group(function () {
        Route::get('/dashboard', function () {
            return 'Customer Dashboard';
        })->name('returns.dashboard');
    });
});
