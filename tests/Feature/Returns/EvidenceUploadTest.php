<?php

namespace Tests\Feature\Returns;

use App\Models\Evidence;
use App\Models\ExternalOrderCache;
use App\Models\OrderItem;
use App\Models\ReturnItem;
use App\Models\ReturnReason;
use App\Models\ReturnTicket;
use App\Models\TicketStatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EvidenceUploadTest extends TestCase
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
            'quantity' => 5,
        ]);
        $this->reason = ReturnReason::factory()->create();
    }

    #[DataProvider('validConditionProvider')]
    public function test_item_condition_accepts_valid_states(string $condition): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('evidence.jpg');
        $payload = $this->createTicketPayload([
            'condition' => $condition,
        ], [$file]);

        // Act
        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->post(route('returns.tickets.store'), $payload);

        // Assert
        $response->assertRedirect(route('returns.success'));
        $this->assertDatabaseHas('return_items', [
            'order_item_id' => $this->orderItem->order_item_id,
            'condition' => $condition,
        ]);
        $this->assertDatabaseHas('return_tickets', [
            'order_id' => $this->order->order_id,
            'current_status' => 'received',
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validConditionProvider(): array
    {
        return [
            'condition: sealed' => ['sealed'],
            'condition: opened' => ['opened'],
            'condition: damaged' => ['damaged'],
        ];
    }

    #[DataProvider('invalidConditionProvider')]
    public function test_item_condition_rejects_invalid_and_boundary_values(string $invalidCondition): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('evidence.jpg');
        $payload = $this->createTicketPayload([
            'condition' => $invalidCondition,
        ], [$file]);

        // Act
        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->post(route('returns.tickets.store'), $payload);

        // Assert
        $response->assertSessionHasErrors(['items.0.condition']);

        // Invariants check: clean database & storage
        $this->assertSame(0, ReturnTicket::count());
        $this->assertSame(0, ReturnItem::count());
        $this->assertSame(0, Evidence::count());
        $this->assertCount(0, Storage::disk('local')->allFiles('evidences'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidConditionProvider(): array
    {
        return [
            'out of range: destroyed' => ['destroyed'],
            'out of range: used' => ['used'],
            'out of range: broken_seal' => ['broken_seal'],
            'numeric string: 123' => ['123'],
            'empty string' => [''],
        ];
    }

    #[DataProvider('initialEvidenceQuotaBoundaryProvider')]
    public function test_initial_evidence_quota_boundaries(
        array $evidences,
        bool $shouldSucceed,
        int $expectedEvidenceCount,
    ): void {
        // Arrange
        $payload = [
            'items' => [[
                'order_item_id' => $this->orderItem->order_item_id,
                'return_reason_id' => $this->reason->reason_id,
                'quantity' => 1,
                'condition' => 'damaged',
            ]],
            'customer_notes' => 'Prueba de cuotas de evidencia.',
        ];

        if ($evidences !== ['__OMIT__']) {
            $payload['evidences'] = $evidences;
        }

        // Act
        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->post(route('returns.tickets.store'), $payload);

        // Assert
        if ($shouldSucceed) {
            $response->assertRedirect(route('returns.success'));
            $this->assertSame(1, ReturnTicket::count());
            $this->assertSame($expectedEvidenceCount, Evidence::count());
            $this->assertCount($expectedEvidenceCount, Storage::disk('local')->allFiles('evidences'));
        } else {
            $response->assertSessionHasErrors(['evidences']);
            // Invariants: state clean
            $this->assertSame(0, ReturnTicket::count());
            $this->assertSame(0, ReturnItem::count());
            $this->assertSame(0, Evidence::count());
            $this->assertCount(0, Storage::disk('local')->allFiles('evidences'));
        }
    }

    /**
     * @return array<string, array{array<mixed>, bool, int}>
     */
    public static function initialEvidenceQuotaBoundaryProvider(): array
    {
        return [
            '0 files (missing key)' => [['__OMIT__'], false, 0],
            '0 files (empty array)' => [[], false, 0],
            'Lower valid boundary (1 file)' => [
                [UploadedFile::fake()->image('evidence1.jpg')],
                true,
                1,
            ],
            'Upper valid boundary (5 files)' => [
                [
                    UploadedFile::fake()->image('ev1.jpg'),
                    UploadedFile::fake()->image('ev2.png'),
                    UploadedFile::fake()->image('ev3.jpeg'),
                    UploadedFile::fake()->create('ev4.pdf', 100, 'application/pdf'),
                    UploadedFile::fake()->image('ev5.jpg'),
                ],
                true,
                5,
            ],
            'Exceeding upper boundary (6 files)' => [
                [
                    UploadedFile::fake()->image('ev1.jpg'),
                    UploadedFile::fake()->image('ev2.jpg'),
                    UploadedFile::fake()->image('ev3.jpg'),
                    UploadedFile::fake()->image('ev4.jpg'),
                    UploadedFile::fake()->image('ev5.jpg'),
                    UploadedFile::fake()->image('ev6.jpg'),
                ],
                false,
                0,
            ],
        ];
    }

    #[DataProvider('fileSizeBoundaryProvider')]
    public function test_initial_evidence_file_size_boundary(
        int $fileSizeInKb,
        bool $shouldSucceed,
    ): void {
        // Arrange
        $file = UploadedFile::fake()->create('large-file.jpg', $fileSizeInKb, 'image/jpeg');
        $payload = $this->createTicketPayload(['condition' => 'opened'], [$file]);

        // Act
        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->post(route('returns.tickets.store'), $payload);

        // Assert
        if ($shouldSucceed) {
            $response->assertRedirect(route('returns.success'));
            $this->assertSame(1, Evidence::count());
            $this->assertCount(1, Storage::disk('local')->allFiles('evidences'));
        } else {
            $response->assertSessionHasErrors(['evidences.0']);
            // Invariants: 0 side-effects
            $this->assertSame(0, ReturnTicket::count());
            $this->assertSame(0, Evidence::count());
            $this->assertCount(0, Storage::disk('local')->allFiles('evidences'));
        }
    }

    /**
     * @return array<string, array{int, bool}>
     */
    public static function fileSizeBoundaryProvider(): array
    {
        return [
            'Max allowed size boundary (5120 KB)' => [5120, true],
            'Exceeding size boundary (5121 KB)' => [5121, false],
        ];
    }

    #[DataProvider('maliciousFileProvider')]
    public function test_initial_evidence_rejects_malicious_mimes_and_extension_spoofing(
        string $filename,
        int $sizeKb,
        string $mimeType,
    ): void {
        // Arrange
        $file = UploadedFile::fake()->create($filename, $sizeKb, $mimeType);
        $payload = $this->createTicketPayload(['condition' => 'damaged'], [$file]);

        // Act
        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->post(route('returns.tickets.store'), $payload);

        // Assert
        $response->assertSessionHasErrors(['evidences.0']);

        // Invariants: zero side effects
        $this->assertSame(0, ReturnTicket::count());
        $this->assertSame(0, Evidence::count());
        $this->assertCount(0, Storage::disk('local')->allFiles('evidences'));
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function maliciousFileProvider(): array
    {
        return [
            'Executable file (.exe)' => ['malware.exe', 100, 'application/x-msdownload'],
            'Shell script (.sh)' => ['script.sh', 10, 'text/x-shellscript'],
            'PHP script (.php)' => ['payload.php', 10, 'application/x-httpd-php'],
            'Spoofed extension (.exe as image/jpeg)' => ['photo.jpeg', 100, 'application/x-msdownload'],
            'Spoofed extension (.php as application/pdf)' => ['document.pdf', 100, 'application/x-httpd-php'],
        ];
    }

    public function test_additional_evidence_authorization_guard_forbids_mismatched_order_session(): void
    {
        // Arrange: ticket pertenece a la orden A, sesión pertenece a la orden B
        $otherOrder = ExternalOrderCache::factory()->create();
        $ticket = ReturnTicket::create([
            'order_id' => $this->order->order_id,
            'tracking_code' => 'RET-AUTHGUARD',
            'current_status' => 'more_information_requested',
        ]);

        $file = UploadedFile::fake()->image('extra.jpg');

        // Act: cliente con sesión order_id = $otherOrder intenta subir al ticket de $this->order
        $response = $this->withSession(['customer_order_id' => $otherOrder->order_id])
            ->post(route('returns.tickets.evidence', $ticket->ticket_id), [
                'evidences' => [$file],
            ]);

        // Assert: 403 Forbidden
        $response->assertForbidden();

        // Invariants: no side-effects in storage or DB
        $this->assertSame('more_information_requested', $ticket->fresh()->current_status);
        $this->assertSame(0, Evidence::count());
        $this->assertSame(0, TicketStatusHistory::count());
        $this->assertCount(0, Storage::disk('local')->allFiles('evidences'));
    }

    #[DataProvider('nonUploadableStatusesProvider')]
    public function test_additional_evidence_rejects_upload_in_non_uploadable_statuses(string $status): void
    {
        // Arrange: ticket en estado no autorizable para subida adicional
        $ticket = ReturnTicket::create([
            'order_id' => $this->order->order_id,
            'tracking_code' => 'RET-STATUSGUARD',
            'current_status' => $status,
        ]);

        $file = UploadedFile::fake()->image('extra.jpg');

        // Act
        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->post(route('returns.tickets.evidence', $ticket->ticket_id), [
                'evidences' => [$file],
                'customer_notes' => 'Intento de subida en estado inválido',
            ]);

        // Assert: Redirige a tracking con error y mantiene el estado intacto
        $response->assertRedirect(route('returns.tracking'));
        $response->assertSessionHas('error');
        $this->assertSame($status, $ticket->fresh()->current_status);

        // Invariants: 0 side effects
        $this->assertSame(0, Evidence::count());
        $this->assertSame(0, TicketStatusHistory::count());
        $this->assertCount(0, Storage::disk('local')->allFiles('evidences'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonUploadableStatusesProvider(): array
    {
        return [
            'status: received' => ['received'],
            'status: under_review' => ['under_review'],
            'status: approved' => ['approved'],
            'status: rejected' => ['rejected'],
            'status: closed' => ['closed'],
        ];
    }

    #[DataProvider('additionalEvidenceValidBoundaryProvider')]
    public function test_additional_evidence_valid_upload_boundaries_and_status_transition(
        int $fileCount,
    ): void {
        // Arrange: ticket en 'more_information_requested'
        $ticket = ReturnTicket::create([
            'order_id' => $this->order->order_id,
            'tracking_code' => 'RET-MOREINFO',
            'current_status' => 'more_information_requested',
        ]);

        $files = [];
        for ($i = 1; $i <= $fileCount; $i++) {
            $files[] = UploadedFile::fake()->image("evidence_{$i}.jpg");
        }

        // Act
        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->post(route('returns.tickets.evidence', $ticket->ticket_id), [
                'evidences' => $files,
                'customer_notes' => 'Fotos adicionales adjuntadas.',
            ]);

        // Assert
        $response->assertRedirect(route('returns.tracking'));
        $response->assertSessionHas('success');

        // Estado cambia a under_review
        $this->assertSame('under_review', $ticket->fresh()->current_status);

        // Persistencia en DB y Storage
        $this->assertSame($fileCount, Evidence::where('ticket_id', $ticket->ticket_id)->count());
        $this->assertCount($fileCount, Storage::disk('local')->allFiles('evidences'));

        // Historial registrado
        $this->assertDatabaseHas('ticket_status_history', [
            'ticket_id' => $ticket->ticket_id,
            'old_status' => 'more_information_requested',
            'new_status' => 'under_review',
        ]);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function additionalEvidenceValidBoundaryProvider(): array
    {
        return [
            'Lower boundary (1 file)' => [1],
            'Upper boundary (5 files)' => [5],
        ];
    }

    #[DataProvider('additionalEvidenceInvalidBoundaryProvider')]
    public function test_additional_evidence_invalid_upload_maintains_status_and_clean_state(
        array $files,
        string $expectedErrorField,
    ): void {
        // Arrange
        $ticket = ReturnTicket::create([
            'order_id' => $this->order->order_id,
            'tracking_code' => 'RET-MOREINFO-INVALID',
            'current_status' => 'more_information_requested',
        ]);

        // Act
        $response = $this->withSession(['customer_order_id' => $this->order->order_id])
            ->post(route('returns.tickets.evidence', $ticket->ticket_id), [
                'evidences' => $files,
            ]);

        // Assert
        $response->assertSessionHasErrors([$expectedErrorField]);

        // Invariants: El estado NUNCA debe mutar a 'under_review', 0 nuevos registros
        $this->assertSame('more_information_requested', $ticket->fresh()->current_status);
        $this->assertSame(0, Evidence::count());
        $this->assertSame(0, TicketStatusHistory::count());
        $this->assertCount(0, Storage::disk('local')->allFiles('evidences'));
    }

    /**
     * @return array<string, array{array<UploadedFile>, string}>
     */
    public static function additionalEvidenceInvalidBoundaryProvider(): array
    {
        return [
            'Exceeding quota (6 files)' => [
                [
                    UploadedFile::fake()->image('1.jpg'),
                    UploadedFile::fake()->image('2.jpg'),
                    UploadedFile::fake()->image('3.jpg'),
                    UploadedFile::fake()->image('4.jpg'),
                    UploadedFile::fake()->image('5.jpg'),
                    UploadedFile::fake()->image('6.jpg'),
                ],
                'evidences',
            ],
            'Exceeding file size (5121 KB)' => [
                [UploadedFile::fake()->create('big.jpg', 5121, 'image/jpeg')],
                'evidences.0',
            ],
            'Malicious extension (.exe)' => [
                [UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload')],
                'evidences.0',
            ],
        ];
    }

    public function test_additional_evidence_transaction_rollback_preserves_invariants_on_exception(): void
    {
        $this->withoutExceptionHandling();

        // Arrange
        $ticket = ReturnTicket::create([
            'order_id' => $this->order->order_id,
            'tracking_code' => 'RET-ROLLBACK',
            'current_status' => 'more_information_requested',
        ]);

        $file = UploadedFile::fake()->image('valid.jpg');

        // Inducir fallo durante la transacción escuchando la inserción en BD
        DB::listen(function ($query) {
            if (str_contains(strtolower($query->sql), 'insert')) {
                throw new \RuntimeException('Database transaction induced failure');
            }
        });

        // Act & Assert
        try {
            $this->withSession(['customer_order_id' => $this->order->order_id])
                ->post(route('returns.tickets.evidence', $ticket->ticket_id), [
                    'evidences' => [$file],
                    'customer_notes' => 'Notas del cliente',
                ]);
            $this->fail('La excepción inducida debería haber sido lanzada.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Database transaction induced failure', $e->getMessage());
        }

        // Invariants: DB rolled back completely, status is STILL 'more_information_requested'
        $this->assertSame('more_information_requested', $ticket->fresh()->current_status);
        $this->assertSame(0, Evidence::count());
        $this->assertSame(0, TicketStatusHistory::count());
        $this->assertCount(0, Storage::disk('local')->allFiles('evidences'));
    }

    private function createTicketPayload(array $itemOverrides = [], array $evidences = []): array
    {
        return [
            'items' => [array_merge([
                'order_item_id' => $this->orderItem->order_item_id,
                'return_reason_id' => $this->reason->reason_id,
                'quantity' => 1,
                'condition' => 'damaged',
            ], $itemOverrides)],
            'customer_notes' => 'Notas sobre la condición del producto.',
            'evidences' => $evidences,
        ];
    }
}
