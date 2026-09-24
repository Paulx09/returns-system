<?php

namespace Tests\Feature\Returns;

use App\Models\ExternalOrderCache;
use App\Models\OrderItem;
use App\Models\ReturnReason;
use App\Models\ReturnTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReturnTicketSubmissionTest extends TestCase
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
        $this->orderItem = OrderItem::factory()->create([
            'order_id' => $this->order->order_id,
            'quantity' => 2,
        ]);
        $this->reason = ReturnReason::factory()->create();
    }

    public function test_customer_can_submit_a_ticket_with_a_reason_and_evidence(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('damaged-product.jpg');
        $payload = $this->ticketPayload($file);

        // Act
        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->post(route('returns.tickets.store'), $payload);

        // Assert
        $response->assertRedirect(route('returns.success'));
        $this->assertDatabaseHas('return_tickets', [
            'order_id' => $this->order->order_id,
            'current_status' => 'received',
            'customer_comment' => 'El producto llegó dañado.',
        ]);
        $this->assertDatabaseHas('return_items', [
            'order_item_id' => $this->orderItem->order_item_id,
            'reason_id' => $this->reason->reason_id,
            'quantity_to_return' => 1,
        ]);
        $this->assertDatabaseHas('evidences', [
            'file_name' => 'damaged-product.jpg',
            'mime_type' => 'image/jpeg',
        ]);
    }

    public function test_ticket_requires_a_registered_return_reason(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('evidence.jpg');
        $payload = $this->ticketPayload($file);
        $payload['items'][0]['return_reason_id'] = fake()->uuid();

        // Act
        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->post(route('returns.tickets.store'), $payload);

        // Assert
        $response->assertSessionHasErrors(['items.0.return_reason_id']);
        $this->assertSame(0, ReturnTicket::count());
    }

    public function test_ticket_rejects_an_invalid_evidence_file(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('payload.php', 10, 'application/x-httpd-php');
        $payload = $this->ticketPayload($file);

        // Act
        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->post(route('returns.tickets.store'), $payload);

        // Assert
        $response->assertSessionHasErrors(['evidences.0']);
        $this->assertSame(0, ReturnTicket::count());
    }

    public function test_ticket_payload_is_rejected_when_a_later_item_is_invalid(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('evidence.jpg');
        $payload = $this->ticketPayload($file);
        $payload['items'][] = [
            'order_item_id' => fake()->uuid(),
            'return_reason_id' => $this->reason->reason_id,
            'quantity' => 1,
            'condition' => 'damaged',
        ];

        // Act
        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->post(route('returns.tickets.store'), $payload);

        // Assert
        $response->assertSessionHasErrors(['items.1.order_item_id']);
        $this->assertSame(0, ReturnTicket::count());
    }

    private function ticketPayload(UploadedFile $file): array
    {
        return [
            'items' => [[
                'order_item_id' => $this->orderItem->order_item_id,
                'return_reason_id' => $this->reason->reason_id,
                'quantity' => 1,
                'condition' => 'damaged',
            ]],
            'customer_notes' => 'El producto llegó dañado.',
            'evidences' => [$file],
        ];
    }
}