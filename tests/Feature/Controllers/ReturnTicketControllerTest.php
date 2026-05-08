<?php

namespace Tests\Feature\Controllers;

use App\Models\ExternalOrderCache;
use App\Models\OrderItem;
use App\Models\ReturnReason;
use App\Models\ReturnTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReturnTicketControllerTest extends TestCase
{
    use RefreshDatabase;

    private ExternalOrderCache $order;
    private OrderItem $orderItem;
    private ReturnReason $reason;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->order = ExternalOrderCache::factory()->create();
        $this->orderItem = OrderItem::factory()->create(['order_id' => $this->order->order_id]);
        $this->reason = ReturnReason::create([
            'name' => 'DAMAGED',
            'description' => 'Producto Dañado',
        ]);
    }

    public function test_customer_can_view_dashboard_with_order_data(): void
    {
        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->get('/returns/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Returns/Dashboard'));
    }

    public function test_customer_can_submit_return_ticket(): void
    {
        $file = UploadedFile::fake()->image('evidence.jpg', 800, 600)->size(1024);

        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->post('/returns/tickets', [
                'items' => [
                    [
                        'order_item_id' => $this->orderItem->order_item_id,
                        'return_reason_id' => $this->reason->reason_id,
                        'quantity' => 1,
                        'condition' => 'damaged',
                    ]
                ],
                'customer_notes' => 'El producto llegó roto.',
                'evidences' => [$file],
            ]);

        $response->assertRedirect(route('returns.success'));

        // Assert database
        $this->assertDatabaseHas('return_tickets', [
            'order_id' => $this->order->order_id,
            'customer_comment' => 'El producto llegó roto.',
            'current_status' => 'received'
        ]);

        $ticket = ReturnTicket::where('order_id', $this->order->order_id)->first();

        $this->assertDatabaseHas('return_items', [
            'ticket_id' => $ticket->ticket_id,
            'order_item_id' => $this->orderItem->order_item_id,
            'quantity_to_return' => 1,
        ]);

        $this->assertDatabaseHas('evidences', [
            'ticket_id' => $ticket->ticket_id,
            'file_name' => 'evidence.jpg',
            'mime_type' => 'image/jpeg',
        ]);
    }

    public function test_validation_rejects_executable_files(): void
    {
        $file = UploadedFile::fake()->create('malicious.php', 1024, 'application/x-httpd-php');

        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->post('/returns/tickets', [
                'items' => [
                    [
                        'order_item_id' => $this->orderItem->order_item_id,
                        'return_reason_id' => $this->reason->reason_id,
                        'quantity' => 1,
                        'condition' => 'damaged',
                    ]
                ],
                'customer_notes' => 'El producto llegó roto.',
                'evidences' => [$file],
            ]);

        $response->assertSessionHasErrors(['evidences.0']);
        $this->assertEquals(0, ReturnTicket::count());
    }
}
