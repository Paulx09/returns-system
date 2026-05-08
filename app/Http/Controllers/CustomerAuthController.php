<?php

namespace App\Http\Controllers;

use App\Services\ExternalOrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CustomerAuthController extends Controller
{
    private ExternalOrderService $orderService;

    public function __construct(ExternalOrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function create()
    {
        return Inertia::render('Returns/Start');
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string',
            'customer_dni' => 'required|string',
        ]);

        $order = $this->orderService->findOrder(
            $request->input('order_number'),
            $request->input('customer_dni')
        );

        if (!$order) {
            throw ValidationException::withMessages([
                'login' => 'Los datos ingresados no coinciden con ningún pedido registrado.',
            ]);
        }

        if (!$this->orderService->isWithinReturnPeriod($order, 7)) {
            throw ValidationException::withMessages([
                'login' => 'El plazo máximo de 7 días para devoluciones ha vencido para este pedido.',
            ]);
        }

        $request->session()->put('customer_order_id', $order->order_id);
        
        // Prevent session fixation
        $request->session()->regenerate();

        return redirect()->route('returns.dashboard');
    }

    public function destroy(Request $request)
    {
        $request->session()->forget('customer_order_id');
        $request->session()->regenerate();

        return redirect()->route('returns.start');
    }
}
