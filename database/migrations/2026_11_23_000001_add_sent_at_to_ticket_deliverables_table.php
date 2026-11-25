<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ticket_deliverables.sent_at: the moment a deliverable was first sent to the customer
 * from the ticket chat room. Set once (never overwritten), so it stays the true send
 * time even if the row is touched later. The SLA Report uses it for "First Resolution"
 * (the first IR or RCA sent) and for "Date of sending Resolution doc./mail".
 *
 * Existing sent rows are backfilled from updated_at, which was the send timestamp
 * until now (a sent deliverable can no longer be edited or deleted).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ticket_deliverables', 'sent_at')) {
            Schema::table('ticket_deliverables', function (Blueprint $table) {
                $table->timestamp('sent_at')->nullable()->after('status');
                $table->index(['ticket_id', 'sent_at']);
            });
        }

        DB::table('ticket_deliverables')
            ->where('status', 'Sent')
            ->whereNull('sent_at')
            ->update(['sent_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('ticket_deliverables', 'sent_at')) {
            Schema::table('ticket_deliverables', function (Blueprint $table) {
                $table->dropIndex(['ticket_id', 'sent_at']);
                $table->dropColumn('sent_at');
            });
        }
    }
};
