<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom tambahan untuk Reimbursement dengan Object dan dana. Semuanya nullable
 * dan tidak mengubah kolom yang sudah ada. Dokumen lama tetap terbaca persis
 * seperti sebelumnya (kolom baru kosong).
 *
 * `reimbursement_items.cost_center_type` sudah ada (branch | project). Nilai
 * `support` hanyalah string baru di kolom yang sama, jadi tidak perlu ALTER.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursement_items', function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_id')->nullable()->after('delivery_project_id');
            $table->string('fund_category', 60)->nullable()->after('cost_center_label');
        });

        Schema::table('reimbursement_requests', function (Blueprint $table) {
            // Pembatalan oleh Finance setelah disetujui: dana dikembalikan.
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->string('void_reason', 255)->nullable();
            // Alasan pemohon bila nominal melebihi sisa dana (kebijakan Peringatan).
            $table->string('over_allocation_reason', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reimbursement_requests', function (Blueprint $table) {
            $table->dropColumn(['voided_at', 'voided_by', 'void_reason', 'over_allocation_reason']);
        });

        Schema::table('reimbursement_items', function (Blueprint $table) {
            $table->dropColumn(['ticket_id', 'fund_category']);
        });
    }
};
