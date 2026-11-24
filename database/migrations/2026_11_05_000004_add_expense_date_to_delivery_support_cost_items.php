<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanggal expense (kalender) + period "YYYY-MM" turunannya untuk expense
 * detail Plan Cost Delivery Support, dipakai report Monthly Cost vs TOP
 * Invoiced. Expense lama dibiarkan kosong → baris "belum dirinci".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_support_cost_items', function (Blueprint $t) {
            if (!Schema::hasColumn('delivery_support_cost_items', 'expense_date')) {
                $t->date('expense_date')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('delivery_support_cost_items', 'period')) {
                $t->string('period', 7)->nullable()->after('expense_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_support_cost_items', function (Blueprint $t) {
            $t->dropColumn(['expense_date', 'period']);
        });
    }
};
