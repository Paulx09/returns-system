<?php

namespace App\Services;

use App\Models\ExternalOrderCache;
use Carbon\Carbon;

class ExternalOrderService
{
    /**
     * Busca una orden por número de pedido y DNI.
     */
    public function findOrder(string $orderNumber, string $dni): ?ExternalOrderCache
    {
        return ExternalOrderCache::with('orderItems')
            ->where('order_number', $orderNumber)
            ->where('customer_dni', $dni)
            ->first();
    }

    /**
     * Verifica si una orden está dentro del periodo válido de devolución (ej. 7 días).
     */
    public function isWithinReturnPeriod(ExternalOrderCache $order, int $daysLimit = 7): bool
    {
        return $order->order_date->diffInDays(Carbon::now()) <= $daysLimit;
    }
}
