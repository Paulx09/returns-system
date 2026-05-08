<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Verifica que el usuario esté autenticado y tenga rol válido (admin o support).
     *
     * Opcionalmente acepta un parámetro de rol para verificación granular:
     *   middleware('admin.auth:admin') → solo rol admin
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        // Capa 1: ¿Está autenticado?
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Capa 2: ¿Tiene un rol administrativo válido?
        $allowedRoles = ['admin', 'support'];
        if (!in_array(auth()->user()->role, $allowedRoles)) {
            abort(403, 'Acceso no autorizado.');
        }

        // Capa 3 (opcional): ¿Tiene el rol específico requerido?
        if ($role !== null && auth()->user()->role !== $role) {
            abort(403, "Se requiere rol: {$role}.");
        }

        return $next($request);
    }
}
