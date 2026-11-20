<?php

namespace App\Services\ObjectFund;

use App\Models\ObjectFund\FundPool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Membaca anggaran yang SUDAH ada di rencana biaya sebuah project (tab Plan Cost,
 * tabel delivery_project_costs) untuk dipakai sebagai alokasi dana reimbursement.
 *
 * Hanya MEMBACA. Tabel itu tidak pernah ditulis dari sini: nilai `actual_amount`-nya dihitung
 * ulang otomatis dari catatan pengeluaran, sehingga tidak aman ditulis fitur lain. Pemakaian
 * dana tetap dicatat di object_fund_entries.
 *
 * Nilai sebuah baris: `release_amount` bila terisi (uang yang sudah dirilis, batas paling aman),
 * selain itu `budget`.
 */
class ProjectBudgetReader
{
    /**
     * Baris rencana biaya yang dapat dipilih: baris daun, bukan induk (100 INDIRECT, 200 DIRECT).
     *
     * @return Collection<int, object>
     */
    public function leafCosts(int $projectId): Collection
    {
        $parents = DB::table('delivery_project_costs')
            ->where('delivery_projects_id', $projectId)
            ->whereNotNull('parent_id')
            ->pluck('parent_id')
            ->all();

        return DB::table('delivery_project_costs')
            ->where('delivery_projects_id', $projectId)
            ->whereNotNull('parent_id')
            ->whereNotIn('id', $parents === [] ? [0] : $parents)
            ->orderBy('cost_type')->orderBy('order_sequence')->orderBy('id')
            ->get(['id', 'code', 'name', 'cost_type', 'budget', 'release_amount'])
            ->map(function ($r) {
                $r->budget         = (float) $r->budget;
                $r->release_amount = (float) $r->release_amount;
                $r->amount         = $r->release_amount > 0 ? $r->release_amount : $r->budget;

                return $r;
            });
    }

    /**
     * Alokasi yang diikuti sebuah kantong dana, atau null bila tidak dapat ditentukan (baris hilang
     * atau nilainya kosong). Pemanggil lalu memakai nilai terakhir yang tersimpan.
     */
    public function amountFor(FundPool $pool): ?float
    {
        if ($pool->source !== FundPool::SOURCE_PROJECT_PLAN || $pool->object_type !== FundPool::TYPE_PROJECT) {
            return null;
        }

        $rows = $this->leafCosts((int) $pool->ref_id);

        if ($pool->source_cost_id) {
            $row = $rows->firstWhere('id', (int) $pool->source_cost_id);

            return $row && $row->amount > 0 ? (float) $row->amount : null;
        }

        // Tanpa baris yang dipilih: cocokkan nama kategori (huruf besar/kecil dan spasi tidak dibedakan).
        $wanted = mb_strtolower(trim((string) $pool->category));

        if ($wanted === '') {
            return null;
        }

        $sum = (float) $rows->filter(fn ($r) => mb_strtolower(trim((string) $r->name)) === $wanted)->sum('amount');

        return $sum > 0 ? $sum : null;
    }
}
