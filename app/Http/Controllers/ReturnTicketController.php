<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use App\Models\ExternalOrderCache;
use App\Models\ReturnItem;
use App\Models\ReturnReason;
use App\Models\ReturnTicket;
use App\Models\TicketStatusHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReturnTicketController extends Controller
{
    public function dashboard(Request $request): Response|RedirectResponse
    {
        $orderId = $request->session()->get('customer_order_id');

        // Si ya cuenta con un ticket activo en sesión, redirigir a la vista de seguimiento
        if ($request->session()->get('has_active_ticket', false)) {
            return redirect()->route('returns.tracking');
        }

        $order = ExternalOrderCache::with('orderItems')->findOrFail($orderId);
        $reasons = ReturnReason::all();

        return Inertia::render('Returns/Dashboard', [
            'order' => $order,
            'reasons' => $reasons,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $orderId = $request->session()->get('customer_order_id');

        // Validar que no exista un ticket activo
        $hasActiveTicket = ReturnTicket::where('order_id', $orderId)
            ->where('current_status', '!=', 'closed')
            ->exists();

        if ($hasActiveTicket) {
            $request->session()->put('has_active_ticket', true);
            return redirect()->route('returns.tracking')
                ->with('error', 'Ya existe una solicitud de devolución en trámite para este pedido.');
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|uuid|exists:order_items,order_item_id',
            'items.*.return_reason_id' => 'required|uuid|exists:return_reasons,reason_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.condition' => 'required|string|in:sealed,opened,damaged',
            'customer_notes' => 'nullable|string|max:1000',
            // Security: limit to 5MB, only images and pdfs
            'evidences' => 'required|array|min:1|max:5',
            'evidences.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
        
        DB::beginTransaction();
        try {
            // 1. Create Ticket
            $ticket = ReturnTicket::create([
                'order_id' => $orderId,
                'tracking_code' => 'RET-' . strtoupper(Str::random(8)),
                'current_status' => 'received',
                'customer_comment' => htmlspecialchars($request->input('customer_notes')), // Prevent XSS
            ]);

            TicketStatusHistory::create([
                'ticket_id'          => $ticket->ticket_id,
                'old_status'         => null,
                'new_status'         => 'received',
                'changed_by_user_id' => null,
                'comment'            => 'Solicitud de devolución registrada en el portal.',
            ]);

            // 2. Add Items
            foreach ($request->input('items') as $itemData) {
                ReturnItem::create([
                    'ticket_id' => $ticket->ticket_id,
                    'order_item_id' => $itemData['order_item_id'],
                    'reason_id' => $itemData['return_reason_id'],
                    'quantity_to_return' => $itemData['quantity'],
                    'condition' => $itemData['condition'],
                ]);
            }

            // 3. Upload Evidences
            $storedPaths = [];
            if ($request->hasFile('evidences')) {
                /** @var array<UploadedFile> $files */
                $files = $request->file('evidences');
                foreach ($files as $file) {
                    $path = $file->store('evidences', 'local');
                    if (is_string($path)) {
                        $storedPaths[] = $path;

                        Evidence::create([
                            'ticket_id' => $ticket->ticket_id,
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                            'mime_type' => $file->getClientMimeType(),
                            'file_size' => $file->getSize(),
                        ]);
                    }
                }
            }

            DB::commit();

            $request->session()->put('has_active_ticket', true);

            return redirect()->route('returns.success')->with('tracking_code', $ticket->tracking_code);

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }

    public function success(Request $request): Response
    {
        $orderId = $request->session()->get('customer_order_id');
        $trackingCode = session('tracking_code');

        if (!$trackingCode && $orderId) {
            $latestTicket = ReturnTicket::where('order_id', $orderId)->latest('created_at')->first();
            $trackingCode = $latestTicket?->tracking_code;
        }

        return Inertia::render('Returns/Success', [
            'trackingCode' => $trackingCode,
        ]);
    }

    public function tracking(Request $request): Response|RedirectResponse
    {
        $orderId = $request->session()->get('customer_order_id');
        $order = ExternalOrderCache::findOrFail($orderId);

        $ticket = ReturnTicket::where('order_id', $orderId)
            ->with([
                'order',
                'returnItems.orderItem',
                'returnItems.reason',
                'evidences',
                'statusHistory' => fn ($q) => $q->orderBy('changed_at', 'desc'),
            ])
            ->latest('created_at')
            ->first();

        if (!$ticket) {
            return redirect()->route('returns.dashboard');
        }

        return Inertia::render('Returns/Tracking', [
            'ticket' => $ticket,
            'order'  => $order,
        ]); 
    }

    /**
     * Permite al cliente visualizar o descargar una evidencia de su ticket.
     */
    public function showEvidence(Request $request, Evidence $evidence): BinaryFileResponse
    {
        $orderId = $request->session()->get('customer_order_id');
        /** @var ReturnTicket|null $ticket */
        $ticket = $evidence->ticket;

        if (!$ticket || $ticket->order_id !== $orderId) {
            abort(403, 'No tienes autorización para acceder a esta evidencia.');
        }

        if (!Storage::disk('local')->exists($evidence->file_path)) {
            abort(404, 'La evidencia solicitada no existe.');
        }

        $absolutePath = Storage::disk('local')->path($evidence->file_path);

        return response()->file($absolutePath, [
            'Content-Type' => $evidence->mime_type,
            'Content-Disposition' => 'inline; filename="' . $evidence->file_name . '"'
        ]);
    }

    /**
     * Permite al cliente adjuntar evidencias adicionales cuando el ticket está en 'more_information_requested'.
     */
    public function uploadAdditionalEvidence(Request $request, ReturnTicket $ticket): RedirectResponse
    {
        $orderId = $request->session()->get('customer_order_id');

        if ($ticket->order_id !== $orderId) {
            abort(403, 'No tienes autorización para modificar este ticket.');
        }

        if ($ticket->current_status !== 'more_information_requested') {
            return redirect()->route('returns.tracking')
                ->with('error', 'Solo puedes adjuntar información cuando el ticket se encuentra en estado Información Solicitada.');
        }

        $request->validate([
            'evidences' => 'required|array|min:1|max:5',
            'evidences.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'customer_notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        $storedPaths = [];
        try {
            // Guardar nuevas evidencias
            /** @var array<UploadedFile> $files */
            $files = $request->file('evidences');
            foreach ($files as $file) {
                $path = $file->store('evidences', 'local');
                if (is_string($path)) {
                    $storedPaths[] = $path;

                    Evidence::create([
                        'ticket_id' => $ticket->ticket_id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            // Transicionar estado a under_review
            $oldStatus = $ticket->current_status;
            $ticket->update(['current_status' => 'under_review']);

            $commentText = 'El cliente adjuntó nueva evidencia solicitada.';
            if ($request->filled('customer_notes')) {
                $commentText .= ' Comentario: ' . htmlspecialchars($request->input('customer_notes'));
            }

            TicketStatusHistory::create([
                'ticket_id'          => $ticket->ticket_id,
                'old_status'         => $oldStatus,
                'new_status'         => 'under_review',
                'changed_by_user_id' => null,
                'comment'            => $commentText,
            ]);

            DB::commit();

            return redirect()->route('returns.tracking')
                ->with('success', 'Información y evidencias adicionales enviadas correctamente. Tu caso ha retornado a revisión.');

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }
}
