<?php

namespace Tests\Unit;

use App\Models\Ticket;
use App\Services\Ai\Tools\TableAccess;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Delete Ticket adalah soft delete: baris tiket diberi deleted_at dan datanya tetap ada.
 * Test ini menjaga dua hal yang gampang terlewat saat model/akses AI diubah.
 */
class TicketSoftDeleteTest extends TestCase
{
    public function test_ticket_uses_soft_deletes(): void
    {
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Ticket::class));
    }

    public function test_ai_table_access_hides_deleted_tickets_like_hidden_ones(): void
    {
        $direct = DB::table('ticket');
        TableAccess::excludeHiddenTickets('ticket', $direct);
        $this->assertStringContainsString('"is_hidden" is null', $direct->toSql());
        $this->assertStringContainsString('"deleted_at" is null', $direct->toSql());
    }
}
