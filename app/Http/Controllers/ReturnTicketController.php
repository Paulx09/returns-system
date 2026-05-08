<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use App\Models\ExternalOrderCache;
use App\Models\ReturnItem;
use App\Models\ReturnReason;
use App\Models\ReturnTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ReturnTicketController extends Controller
{
    public function dashboard(Request $request)
    {
        $orderId = $request->session()->get('customer_order_id');
        $order = ExternalOrderCache::with('orderItems')->findOrFail($orderId);
        $reasons = ReturnReason::all();

        return Inertia::render('Returns/Dashboard', [
            'order' => $order,
            'reasons' => $reasons,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|uuid|exists:order_items,order_item_id',
            'items.*.return_reason_id' => 'required|uuid|exists:return_reasons,reason_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.condition' => 'required|string',
            'customer_notes' => 'nullable|string|max:1000',
            // Security: limit to 5MB, only images and pdfs
            'evidences' => 'required|array|min:1|max:5',
            'evidences.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $orderId = $request->session()->get('customer_order_id');
        
        DB::beginTransaction();
        try {
            // 1. Create Ticket
            $ticket = ReturnTicket::create([
                'order_id' => $orderId,
                'tracking_code' => 'RET-' . strtoupper(Str::random(8)),
                'current_status' => 'received',
                'customer_comment' => htmlspecialchars($request->input('customer_notes')), // Prevent XSS
            ]);

            // 2. Add Items
            foreach ($request->input('items') as $itemData) {
                ReturnItem::create([
                    'ticket_id' => $ticket->ticket_id,
                    'order_item_id' => $itemData['order_item_id'],
                    'reason_id' => $itemData['return_reason_id'],
                    'quantity_to_return' => $itemData['quantity'],
                ]);
            }

            // 3. Upload Evidences
            if ($request->hasFile('evidences')) {
                foreach ($request->file('evidences') as $file) {
                    $path = $file->store('evidences', 'local'); // Saves to storage/app/evidences

                    Evidence::create([
                        'ticket_id' => $ticket->ticket_id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('returns.success')->with('tracking_code', $ticket->tracking_code);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function success(Request $request)
    {
        return Inertia::render('Returns/Success', [
            'trackingCode' => session('tracking_code')
        ]);
    }
}
