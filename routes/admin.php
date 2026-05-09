<?php

use App\Http\Controllers\AdminTicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes — Portal de Devoluciones Tai Loy
|--------------------------------------------------------------------------
|
| Todas las rutas bajo /admin requieren autenticación y rol admin/support.
| El middleware 'admin.auth' maneja ambas verificaciones.
|
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'admin.auth'])
    ->group(function () {

        // Redirige /admin → /admin/tickets (punto de entrada)
        Route::get('/', fn () => redirect()->route('admin.tickets.index'));

        // Gestión de tickets
        Route::get('/tickets', [AdminTicketController::class, 'index'])
            ->name('tickets.index');

        Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])
            ->name('tickets.show');

        // Servir evidencias (Privadas)
        Route::get('/evidences/{evidence}', [AdminTicketController::class, 'showEvidence'])
            ->name('evidences.show');

        Route::patch('/tickets/{ticket}/status', [AdminTicketController::class, 'updateStatus'])
            ->name('tickets.update-status');
    });
