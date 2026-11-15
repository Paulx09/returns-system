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
            ->active()
            ->first();

        $latestTicket = $activeTicket ?? ReturnTicket::where('order_id', $order->order_id)
            ->latest('created_at')
            ->first();

        // Allow viewing an existing ticket even after 7 days; only block first-time expired requests.
        if (!$latestTicket && !$this->orderService->isWithinReturnPeriod($order, 7)) {
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

        if ($latestTicket) {
            return redirect()->route('returns.tracking');
        }

        return redirect()->route('returns.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(['customer_order_id', 'has_active_ticket']);
        $request->session()->regenerate();

        return redirect()->route('returns.start');
    }
}
