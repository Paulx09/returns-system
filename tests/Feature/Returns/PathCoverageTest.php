<?php

namespace Tests\Feature\Returns;

use App\Models\ExternalOrderCache;
use App\Models\ReturnTicket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Path matrix for the implemented return-ticket lifecycle.
 *
 * Decision nodes:
 * P1: admin middleware authenticated? yes/no.
 * P2: authenticated role allowed? admin/support/other.
 * P3: status payload valid? yes/no.
 * P4: rejected or more-information status has a comment? yes/no.
 * P5: closing requested by an admin? yes/no.
 * P6: transactional transition succeeds and records history.
 *
 * Independent executable paths:
 * 1. Unauthenticated -> redirect to login.
 * 2. Invalid administrative role -> 403.
 * 3. Valid admin/support request -> transition and history.
 * 4. Rejected without comment -> validation failure, no transition.
 * 5. More-information request without comment -> validation failure, no transition.
 * 6. Support closing -> 403, no transition.
 * 7. Admin closing -> transition and history.
 * 8. Invalid status -> validation failure, no transition.
 *
 * The repository currently has no public inspection, credit-note, or inventory
 * action. Those lifecycle segments cannot be covered without inventing a contract.
 */
class PathCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_path_stops_at_admin_authentication_guard(): void
    {
        // Arrange
        $ticket = $this->createTicket();

        // Act
        $response = $this->patch($this->statusUrl($ticket), [
            'new_status' => 'approved',
        ]);

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_non_administrative_role_stops_at_role_guard(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->make(['role' => 'customer']);
        $ticket = $this->createTicket();

        // Act
        $response = $this->actingAs($user)->patch($this->statusUrl($ticket), [
            'new_status' => 'approved',
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertSame('received', $ticket->fresh()->current_status);
        $this->assertDatabaseCount('ticket_status_history', 0);
    }

    #[DataProvider('successfulTransitionProvider')]
    public function test_allowed_transition_updates_ticket_and_records_history(
        string $role,
        string $newStatus,
        ?string $comment,
    ): void {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['role' => $role]);
        $ticket = $this->createTicket();

        // Act
        $response = $this->actingAs($user)->patch($this->statusUrl($ticket), [
            'new_status' => $newStatus,
            'comment' => $comment,
        ]);

        // Assert
        $response->assertRedirect('/admin/tickets/' . $ticket->ticket_id);
        $this->assertSame($newStatus, $ticket->fresh()->current_status);
        $this->assertDatabaseHas('ticket_status_history', [
            'ticket_id' => $ticket->ticket_id,
            'old_status' => 'received',
            'new_status' => $newStatus,
            'comment' => $comment,
        ]);
    }

    /**
     * @return array<string, array{string, string, ?string}>
     */
    public static function successfulTransitionProvider(): array
    {
        return [
            'support begins inspection' => ['support', 'under_review', null],
            'admin approves inspected item' => ['admin', 'approved', null],
            'admin rejects failed inspection' => ['admin', 'rejected', 'Producto no apto.'],
            'admin requests more information' => ['admin', 'more_information_requested', 'Falta evidencia.'],
        ];
    }

    #[DataProvider('commentRequiredStatusProvider')]
    public function test_comment_required_guard_keeps_ticket_unchanged(string $newStatus): void
    {
        // Arrange
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = $this->createTicket();

        // Act
        $response = $this->actingAs($admin)->patch($this->statusUrl($ticket), [
            'new_status' => $newStatus,
        ]);

        // Assert
        $response->assertSessionHasErrors(['comment']);
        $this->assertSame('received', $ticket->fresh()->current_status);
        $this->assertDatabaseCount('ticket_status_history', 0);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function commentRequiredStatusProvider(): array
    {
        return [
            'rejected' => ['rejected'],
            'more information requested' => ['more_information_requested'],
        ];
    }

    public function test_support_cannot_close_ticket(): void
    {
        // Arrange
        /** @var User $support */
        $support = User::factory()->create(['role' => 'support']);
        $ticket = $this->createTicket();

        // Act
        $response = $this->actingAs($support)->patch($this->statusUrl($ticket), [
            'new_status' => 'closed',
            'comment' => 'Cierre solicitado.',
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertSame('received', $ticket->fresh()->current_status);
        $this->assertDatabaseCount('ticket_status_history', 0);
    }

    public function test_admin_can_close_ticket_after_validating_the_guard(): void
    {
        // Arrange
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = $this->createTicket();

        // Act
        $response = $this->actingAs($admin)->patch($this->statusUrl($ticket), [
            'new_status' => 'closed',
            'comment' => 'Caso resuelto.',
        ]);

        // Assert
        $response->assertRedirect('/admin/tickets/' . $ticket->ticket_id);
        $this->assertSame('closed', $ticket->fresh()->current_status);
        $this->assertDatabaseHas('ticket_status_history', [
            'ticket_id' => $ticket->ticket_id,
            'new_status' => 'closed',
            'comment' => 'Caso resuelto.',
        ]);
    }

    public function test_invalid_status_stops_at_request_validation(): void
    {
        // Arrange
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = $this->createTicket();

        // Act
        $response = $this->actingAs($admin)->patch($this->statusUrl($ticket), [
            'new_status' => 'inspection_failed',
        ]);

        // Assert
        $response->assertSessionHasErrors(['new_status']);
        $this->assertSame('received', $ticket->fresh()->current_status);
        $this->assertDatabaseCount('ticket_status_history', 0);
    }

    private function createTicket(): ReturnTicket
    {
        $order = ExternalOrderCache::factory()->create();

        return ReturnTicket::factory()->create([
            'order_id' => $order->order_id,
            'current_status' => 'received',
        ]);
    }

    private function statusUrl(ReturnTicket $ticket): string
    {
        return '/admin/tickets/' . $ticket->ticket_id . '/status';
    }
}