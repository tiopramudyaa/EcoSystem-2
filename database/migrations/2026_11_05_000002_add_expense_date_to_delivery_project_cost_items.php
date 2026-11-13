<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanggal expense dipilih lewat kalender dan disimpan utuh; kolom period
 * ("YYYY-MM") tetap ada dan diturunkan dari tanggal ini (dipakai filter,
 * subtotal per bulan, dan report Monthly Cost).
 *
 * Nullable: expense lama hanya punya period (atau tidak sama sekali) dan
 * sengaja TIDAK di-backfill — tanggal persisnya memang tidak diketahui.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('delivery_project_cost_items') || Schema::hasColumn('delivery_project_cost_items', 'expense_date')) {
            return;
        }

        Schema::table('delivery_project_cost_items', function (Blueprint $t) {
            $t->date('expense_date')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('delivery_project_cost_items') || !Schema::hasColumn('delivery_project_cost_items', 'expense_date')) {
            return;
        }

        Schema::table('delivery_project_cost_items', function (Blueprint $t) {
            $t->dropColumn('expense_date');
        });
    }
};
