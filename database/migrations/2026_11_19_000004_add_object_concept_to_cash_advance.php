<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konsep Object (Office / Project / Support) untuk Cash Advance dan Cash
 * Advance Report. Seluruhnya PENAMBAHAN, nullable atau berbawaan netral, dan
 * punya down(): dokumen lama tidak berubah. Titik kembali konsep lama:
 * tag git `checkpoint/reimbursement-before-object-fund`.
 *
 *  - object_flow_settings.cash_advance_enabled : saklar terpisah dari Reimbursement,
 *    supaya masing-masing modul dapat diaktifkan sendiri-sendiri.
 *  - cash_advances: tiket (Support), kategori dana, alasan melebihi dana, void.
 *  - cash_advance_approvals / cash_advance_report_approvals.origin: membedakan
 *    langkah dari Object dan langkah Approval Workflow (nomor langkah workflow
 *    digeser sebanyak langkah Object; lihat migrasi Reimbursement 000003).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('object_flow_settings', function (Blueprint $table) {
            $table->boolean('cash_advance_enabled')->default(false)->after('enabled');
        });

        Schema::table('cash_advances', function (Blueprint $table) {
            $table->unsignedBigInteger('charged_ticket_id')->nullable();
            $table->string('fund_category', 60)->nullable();
            $table->string('over_allocation_reason', 500)->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->string('void_reason', 255)->nullable();
        });

        Schema::table('cash_advance_approvals', function (Blueprint $table) {
            $table->string('origin', 10)->default('workflow');
        });

        Schema::table('cash_advance_report_approvals', function (Blueprint $table) {
            $table->string('origin', 10)->default('workflow');
        });
    }

    public function down(): void
    {
        Schema::table('cash_advance_report_approvals', fn (Blueprint $t) => $t->dropColumn('origin'));
        Schema::table('cash_advance_approvals', fn (Blueprint $t) => $t->dropColumn('origin'));

        Schema::table('cash_advances', function (Blueprint $table) {
            $table->dropColumn(['charged_ticket_id', 'fund_category', 'over_allocation_reason', 'voided_at', 'voided_by', 'void_reason']);
        });

        Schema::table('object_flow_settings', fn (Blueprint $t) => $t->dropColumn('cash_advance_enabled'));
    }
};
