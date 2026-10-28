<?php

namespace App\Http\Controllers;

use App\Models\ReturnTicket;
use App\Services\ExternalOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAuthController extends Controller
{
    private ExternalOrderService $orderService;

    public function __construct(ExternalOrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function create(): Response
    {
        return Inertia::render('Returns/Start');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'order_number' => 'required|string',
            'customer_dni' => 'required|digits:8',
        ], [
            'order_number.required' => 'El número de pedido es obligatorio.',
            'customer_dni.required' => 'El DNI es obligatorio.',
            'customer_dni.digits'   => 'El DNI debe contener exactamente 8 dígitos numéricos.',
        ]);

        $order = $this->orderService->findOrder(
            trim((string) $request->input('order_number')),
            trim((string) $request->input('customer_dni'))
        );

        if (!$order) {
            throw ValidationException::withMessages([
                'login' => 'Los datos ingresados no coinciden con ningún pedido registrado.',
            ]);
        }

        $activeTicket = ReturnTicket::where('order_id', $order->order_id)
            ->where('current_status', '!=', 'closed')
            ->first();

        if (!$activeTicket && !$this->orderService->isWithinReturnPeriod($order, 7)) {
            throw ValidationException::withMessages([
                'login' => 'El plazo máximo de 7 días para devoluciones ha vencido para este pedido.',
            ]);
        }

        $request->session()->put('customer_order_id', $order->order_id);
        $request->session()->put('has_active_ticket', (bool) $activeTicket);
        
        // Prevent session fixation
        $request->session()->regenerate();

        if ($activeTicket) {
            return redirect()->route('returns.tracking')
                ->with('info', 'Este pedido ya cuenta con una solicitud de devolución en trámite.');
        }

        return redirect()->route('returns.dashboard');
    }

    public function trackByCode(Request $request): RedirectResponse
    {
        $request->validate([
            'tracking_code' => 'required|string',
            'customer_dni'  => 'required|digits:8',
        ], [
            'tracking_code.required' => 'El código de seguimiento es obligatorio.',
            'customer_dni.required'  => 'El DNI es obligatorio.',
            'customer_dni.digits'    => 'El DNI debe contener exactamente 8 dígitos numéricos.',
        ]);

        $ticket = ReturnTicket::with('order')
            ->where('tracking_code', trim($request->input('tracking_code')))
            ->first();

        if (!$ticket || !$ticket->order || $ticket->order->customer_dni !== trim($request->input('customer_dni'))) {
            throw ValidationException::withMessages([
                'tracking' => 'El código de seguimiento o documento de identidad no coinciden con ningún registro.',
            ]);
        }

        $request->session()->put('customer_order_id', $ticket->order_id);
        $request->session()->put('has_active_ticket', $ticket->current_status !== 'closed');
        $request->session()->regenerate();

        return redirect()->route('returns.tracking');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(['customer_order_id', 'has_active_ticket']);
        $request->session()->regenerate();

        return redirect()->route('returns.start');
    }
}
