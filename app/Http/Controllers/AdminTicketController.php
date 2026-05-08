<?php

namespace App\Http\Controllers;

use App\Models\ReturnTicket;
use App\Models\TicketStatusHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminTicketController extends Controller
{
    /**
     * Listado de tickets con filtros opcionales por estado y rango de fechas.
     */
    public function index(Request $request): Response
    {
        $tickets = ReturnTicket::query()
            ->with('order')
            ->when($request->status, fn ($q, $s) => $q->where('current_status', $s))
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Tickets/Index', [
            'tickets'  => $tickets,
            'filters'  => $request->only(['status', 'date_from', 'date_to']),
            'statuses' => ReturnTicket::STATUSES,
        ]);
    }

    /**
     * Detalle completo de un ticket: items, evidencias e historial de estados.
     */
    public function show(ReturnTicket $ticket): Response
    {
        $ticket->load([
            'order',
            'returnItems.reason',
            'evidences',
            'statusHistory' => fn ($q) => $q->orderBy('changed_at', 'desc'),
            'statusHistory.changedBy',
        ]);

        return Inertia::render('Admin/Tickets/Show', [
            'ticket'   => $ticket,
            'statuses' => ReturnTicket::STATUSES,
        ]);
    }

    /**
     * Cambio de estado con registro automático en ticket_status_history.
     *
     * Regla de negocio: solo 'admin' puede cambiar a 'closed'.
     */
    public function updateStatus(Request $request, ReturnTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'new_status' => ['required', Rule::in(ReturnTicket::STATUSES)],
            'comment'    => [
                Rule::requiredIf(
                    in_array($request->new_status, ['rejected', 'more_information_requested'])
                ),
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        // RBAC: solo admin puede cerrar tickets
        if ($validated['new_status'] === 'closed' && auth()->user()->role !== 'admin') {
            abort(403, 'Solo administradores pueden cerrar tickets.');
        }

        DB::transaction(function () use ($ticket, $validated): void {
            $oldStatus = $ticket->current_status;

            $ticket->update(['current_status' => $validated['new_status']]);

            TicketStatusHistory::create([
                'ticket_id'          => $ticket->ticket_id,
                'old_status'         => $oldStatus,
                'new_status'         => $validated['new_status'],
                'changed_by_user_id' => auth()->id(),
                'comment'            => $validated['comment'] ?? null,
            ]);
        });

        return redirect()
            ->route('admin.tickets.show', $ticket->ticket_id)
            ->with('success', 'Estado actualizado correctamente.');
    }
}
