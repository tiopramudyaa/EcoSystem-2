<?php

namespace App\Services\ObjectFund;

use App\Models\CashAdvance\CashAdvance;
use App\Models\ObjectFund\FundEntry;
use App\Models\ObjectFund\FundPool;
use App\Models\ObjectFund\ObjectFlowSetting;
use App\Models\ObjectFund\OfficeSetting;
use App\Models\Reimbursement\ReimbursementRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dana per Object: sisa, pemeriksaan, pengurangan saat disetujui, dan pengembalian.
 *
 * Aturan (keputusan D1 sampai D4 dan E1 sampai E3):
 *  - Sisa = alokasi dikurangi entri `committed`. Dana BERKURANG SAAT DISETUJUI,
 *    bukan saat dikirim; dokumen yang menunggu hanya ditampilkan sebagai informasi.
 *  - Sisa diperiksa ulang tepat sebelum persetujuan terakhir, karena dua dokumen
 *    yang menunggu bisa memakai sisa yang sama.
 *  - Object tanpa kantong dana (Office nonaktif, atau project/tiket tanpa
 *    alokasi) tidak diperiksa dananya.
 *  - Kebijakan bila melebihi sisa: warn (bawaan, alasan wajib) atau block, per Object.
 *  - Tidak pernah menulis ke `delivery_project_costs`.
 */
class FundService
{
    public const TYPE_BY_COST_CENTER = [
        'branch'  => FundPool::TYPE_OFFICE,
        'project' => FundPool::TYPE_PROJECT,
        'support' => FundPool::TYPE_SUPPORT,
    ];

    /** Jenis Object (office|project|support) dari sebuah baris item, atau null. */
    public function objectTypeOf(array $item): ?string
    {
        return self::TYPE_BY_COST_CENTER[$item['cost_center_type'] ?? ''] ?? null;
    }

    public function refIdOf(array $item): ?int
    {
        $id = match ($this->objectTypeOf($item)) {
            FundPool::TYPE_OFFICE  => $item['branch_id'] ?? null,
            FundPool::TYPE_PROJECT => $item['delivery_project_id'] ?? null,
            FundPool::TYPE_SUPPORT => $item['ticket_id'] ?? null,
            default                => null,
        };

        return $id ? (int) $id : null;
    }

    /**
     * Kantong dana yang berlaku untuk sebuah baris, atau null bila tidak diperiksa.
     */
    public function poolFor(array $item, Carbon $date): ?FundPool
    {
        $type = $this->objectTypeOf($item);
        $ref  = $this->refIdOf($item);

        if ($type === null || $ref === null) {
            return null;
        }

        $category = trim((string) ($item['fund_category'] ?? ''));

        if ($type === FundPool::TYPE_OFFICE) {
            return $this->officePool($ref, (string) ($item['cost_center_label'] ?? ''), $date);
        }

        $base = FundPool::usable()->where('object_type', $type)->where('ref_id', $ref);

        // Kategori yang diminta lebih dulu, lalu kantong umum (kategori kosong).
        return (clone $base)->where('category', $category)->first()
            ?? (clone $base)->where('category', '')->first();
    }

    /** Kantong bulanan Office dibuat dari pengaturan cabang; null bila dana cabang nonaktif. */
    private function officePool(int $branchId, string $label, Carbon $date): ?FundPool
    {
        $setting = OfficeSetting::where('branch_id', $branchId)->first();

        if (!$setting || !$setting->fund_active) {
            return null;
        }

        $period = $date->format('Y-m');

        $pool = FundPool::firstOrCreate(
            ['object_type' => FundPool::TYPE_OFFICE, 'ref_id' => $branchId, 'category' => '', 'period' => $period],
            [
                'ref_label' => $label !== '' ? $label : null,
                'amount'    => $setting->monthly_amount,
                'is_active' => true,
                'status'    => FundPool::STATUS_APPROVED,
            ]
        );

        // Mengubah nominal bulanan memengaruhi bulan berjalan dan mendatang,
        // tidak menulis ulang bulan yang sudah lewat.
        if ($period >= now()->format('Y-m') && (float) $pool->amount !== (float) $setting->monthly_amount) {
            $pool->update(['amount' => $setting->monthly_amount]);
        }

        return $pool;
    }

    /**
     * @return array{allocation: float, committed: float, available: float, pending: float}
     */
    public function balance(FundPool $pool): array
    {
        $committed = (float) FundEntry::where('pool_id', $pool->id)
            ->where('status', FundEntry::STATUS_COMMITTED)
            ->sum('amount');

        $allocation = (float) $pool->amount;

        return [
            'allocation' => $allocation,
            'committed'  => $committed,
            'available'  => round($allocation - $committed, 2),
            'pending'    => $this->pending($pool),
        ];
    }

    /** Jumlah dokumen yang masih menunggu untuk kantong ini. Hanya informasi, tidak mengurangi sisa. */
    private function pending(FundPool $pool): float
    {
        $query = DB::table('reimbursement_items as i')
            ->join('reimbursement_requests as r', 'r.id', '=', 'i.reimbursement_request_id')
            ->whereNull('r.deleted_at')
            ->whereIn('r.status', ReimbursementRequest::OPEN_STATUSES);

        switch ($pool->object_type) {
            case FundPool::TYPE_OFFICE:
                $query->where('i.cost_center_type', 'branch')->where('i.branch_id', $pool->ref_id);
                if ($pool->period !== '') {
                    $query->whereBetween('r.request_date', [
                        Carbon::createFromFormat('Y-m', $pool->period)->startOfMonth()->toDateString(),
                        Carbon::createFromFormat('Y-m', $pool->period)->endOfMonth()->toDateString(),
                    ]);
                }
                break;
            case FundPool::TYPE_PROJECT:
                $query->where('i.cost_center_type', 'project')->where('i.delivery_project_id', $pool->ref_id)
                    ->whereRaw("COALESCE(i.fund_category, '') = ?", [$pool->category]);
                break;
            case FundPool::TYPE_SUPPORT:
                $query->where('i.cost_center_type', 'support')->where('i.ticket_id', $pool->ref_id)
                    ->whereRaw("COALESCE(i.fund_category, '') = ?", [$pool->category]);
                break;
        }

        $total = (float) $query->sum('i.amount');

        // Cash Advance yang masih menunggu juga ditampilkan sebagai informasi.
        $advances = DB::table('cash_advances as c')
            ->whereNull('c.deleted_at')
            ->whereIn('c.status', [CashAdvance::STATUS_SUBMITTED, CashAdvance::STATUS_IN_REVIEW]);

        switch ($pool->object_type) {
            case FundPool::TYPE_OFFICE:
                $advances->where('c.cost_center_type', 'branch')->where('c.charged_branch_id', $pool->ref_id);
                if ($pool->period !== '') {
                    $advances->whereBetween('c.request_date', [
                        Carbon::createFromFormat('Y-m', $pool->period)->startOfMonth()->toDateString(),
                        Carbon::createFromFormat('Y-m', $pool->period)->endOfMonth()->toDateString(),
                    ]);
                }
                break;
            case FundPool::TYPE_PROJECT:
                $advances->where('c.cost_center_type', 'project')->where('c.charged_project_id', $pool->ref_id)
                    ->whereRaw("COALESCE(c.fund_category, '') = ?", [$pool->category]);
                break;
            case FundPool::TYPE_SUPPORT:
                $advances->where('c.cost_center_type', 'support')->where('c.charged_ticket_id', $pool->ref_id)
                    ->whereRaw("COALESCE(c.fund_category, '') = ?", [$pool->category]);
                break;
        }

        return $total + (float) $advances->sum('c.amount');
    }

    /**
     * Periksa sebuah dokumen BARU (saat dikirim) terhadap sisa dana.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{blocked: ?string, warnings: array<int, array<string, mixed>>, needs_reason: bool, checked: array<int, array<string, mixed>>}
     */
    public function evaluate(array $items, Carbon $date, ObjectFlowSetting $settings): array
    {
        $demand = $this->demandByPool($items, $date);

        $blocked  = null;
        $warnings = [];
        $checked  = [];

        foreach ($demand as $row) {
            /** @var FundPool $pool */
            $pool    = $row['pool'];
            $balance = $this->balance($pool);
            $over    = round($row['amount'] - $balance['available'], 2);

            $checked[] = [
                'pool'      => $pool->label(),
                'available' => $balance['available'],
                'requested' => $row['amount'],
                'over'      => max(0.0, $over),
            ];

            if ($over <= 0) {
                continue;
            }

            if ($settings->policyFor($pool->object_type) === ObjectFlowSetting::POLICY_BLOCK) {
                $blocked ??= 'The amount for ' . $pool->label() . ' exceeds the remaining fund by '
                    . ReimbursementRequest::formatRupiah($over) . '. Remaining: '
                    . ReimbursementRequest::formatRupiah(max(0, $balance['available']))
                    . '. Reduce the amount or ask for a larger allocation.';
            } else {
                $warnings[] = [
                    'pool'      => $pool->label(),
                    'over'      => $over,
                    'available' => $balance['available'],
                ];
            }
        }

        return [
            'blocked'      => $blocked,
            'warnings'     => $warnings,
            'needs_reason' => $warnings !== [],
            'checked'      => $checked,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{pool: FundPool, amount: float}>
     */
    private function demandByPool(array $items, Carbon $date): array
    {
        $demand = [];

        foreach ($items as $item) {
            $pool = $this->poolFor($item, $date);

            if (!$pool) {
                continue;
            }

            $demand[$pool->id] ??= ['pool' => $pool, 'amount' => 0.0];
            $demand[$pool->id]['amount'] += (float) ($item['amount'] ?? 0);
        }

        return array_values($demand);
    }

    /**
     * Pemeriksaan ulang tepat sebelum persetujuan terakhir. Mengembalikan pesan
     * bila kebijakan Blokir dan sisa kini tidak cukup, selain itu null.
     */
    public function recheckForApproval(ReimbursementRequest $request, ObjectFlowSetting $settings): ?string
    {
        if ($this->alreadyCommitted($request)) {
            return null;
        }

        $items  = $request->items->map(fn ($i) => $i->toArray())->all();
        $result = $this->evaluate($items, Carbon::parse($request->request_date), $settings);

        return $result['blocked']
            ? 'The remaining fund changed since this document was submitted. ' . $result['blocked']
            : null;
    }

    private function alreadyCommitted(ReimbursementRequest $request): bool
    {
        return FundEntry::where('document_type', 'reimbursement')
            ->where('document_id', $request->id)
            ->where('status', FundEntry::STATUS_COMMITTED)
            ->exists();
    }

    /**
     * Kurangi dana saat dokumen disetujui sepenuhnya. Idempoten: pemanggilan kedua
     * untuk dokumen yang sama tidak menggandakan entri.
     *
     * @return int jumlah entri yang ditulis
     */
    public function commit(ReimbursementRequest $request, int $actorId): int
    {
        if ($this->alreadyCommitted($request)) {
            return 0;
        }

        $written = 0;
        $date    = Carbon::parse($request->request_date);

        foreach ($request->items as $item) {
            $pool = $this->poolFor($item->toArray(), $date);

            if (!$pool) {
                continue;
            }

            $available = $this->balance($pool)['available'];
            $amount    = (float) $item->amount;

            FundEntry::create([
                'pool_id'         => $pool->id,
                'document_type'   => 'reimbursement',
                'document_id'     => $request->id,
                'item_id'         => $item->id,
                'amount'          => $amount,
                'status'          => FundEntry::STATUS_COMMITTED,
                'over_allocation' => $amount > $available,
                'created_by'      => $actorId,
            ]);

            $written++;
        }

        return $written;
    }

    /** Kembalikan dana sebuah dokumen Reimbursement (void oleh Finance). */
    public function release(ReimbursementRequest $request, int $actorId, string $reason): int
    {
        return $this->releaseDocument('reimbursement', (int) $request->id, $actorId, $reason);
    }

    /** Kembalikan dana dokumen modul apa pun: semua entri `committed` menjadi `voided`. */
    public function releaseDocument(string $documentType, int $documentId, int $actorId, string $reason): int
    {
        return FundEntry::where('document_type', $documentType)
            ->where('document_id', $documentId)
            ->where('status', FundEntry::STATUS_COMMITTED)
            ->update([
                'status'      => FundEntry::STATUS_VOIDED,
                'voided_at'   => now(),
                'voided_by'   => $actorId,
                'void_reason' => mb_substr($reason, 0, 255),
            ]);
    }

    // ── Cash Advance ────────────────────────────────────────────────────────
    //
    // Satu Cash Advance = satu Object = satu entri dana. Dana dikurangi saat CA
    // disetujui sebesar nominal uang muka. Setelah Cash Advance Report disetujui
    // dan CA berstatus settled, entrinya disesuaikan menjadi nominal yang
    // sungguh dibelanjakan (reported), sehingga dana mencerminkan pemakaian nyata.

    /** CA sebagai baris item agar memakai pemeriksa dan pencari kantong yang sama. */
    public function itemOfCashAdvance(CashAdvance $advance, ?float $amount = null): array
    {
        return [
            'cost_center_type'    => $advance->cost_center_type,
            'branch_id'           => $advance->charged_branch_id,
            'delivery_project_id' => $advance->charged_project_id,
            'ticket_id'           => $advance->charged_ticket_id,
            'fund_category'       => $advance->fund_category,
            'cost_center_label'   => $advance->charged_to_label,
            'amount'              => $amount ?? (float) $advance->amount,
        ];
    }

    private function advanceCommitted(CashAdvance $advance): bool
    {
        return FundEntry::where('document_type', 'cash_advance')
            ->where('document_id', $advance->id)
            ->where('status', FundEntry::STATUS_COMMITTED)
            ->exists();
    }

    /** Pemeriksaan ulang sisa tepat sebelum persetujuan terakhir sebuah CA. */
    public function recheckAdvanceForApproval(CashAdvance $advance, ObjectFlowSetting $settings): ?string
    {
        if ($this->advanceCommitted($advance)) {
            return null;
        }

        $result = $this->evaluate([$this->itemOfCashAdvance($advance)], Carbon::parse($advance->request_date), $settings);

        return $result['blocked']
            ? 'The remaining fund changed since this cash advance was submitted. ' . $result['blocked']
            : null;
    }

    /** Kurangi dana saat CA disetujui sepenuhnya. Idempoten. */
    public function commitAdvance(CashAdvance $advance, int $actorId): int
    {
        if ($this->advanceCommitted($advance)) {
            return 0;
        }

        $item = $this->itemOfCashAdvance($advance);
        $pool = $this->poolFor($item, Carbon::parse($advance->request_date));

        if (!$pool) {
            return 0;
        }

        $available = $this->balance($pool)['available'];

        FundEntry::create([
            'pool_id'         => $pool->id,
            'document_type'   => 'cash_advance',
            'document_id'     => $advance->id,
            'item_id'         => null,
            'amount'          => (float) $advance->amount,
            'status'          => FundEntry::STATUS_COMMITTED,
            'over_allocation' => (float) $advance->amount > $available,
            'created_by'      => $actorId,
        ]);

        return 1;
    }

    /**
     * Samakan entri dana CA dengan keadaan terbaru: nominal uang muka selama belum
     * settled, nominal yang dilaporkan sesudah settled. Idempoten; dipanggil setiap
     * kali Cash Advance Report berubah.
     */
    public function syncAdvanceEntry(CashAdvance $advance): void
    {
        $entry = FundEntry::where('document_type', 'cash_advance')
            ->where('document_id', $advance->id)
            ->where('status', FundEntry::STATUS_COMMITTED)
            ->first();

        if (!$entry) {
            return;
        }

        $settled = $advance->settlement_status === CashAdvance::SETTLE_SETTLED && $advance->reported_amount !== null;
        $target  = $settled ? (float) $advance->reported_amount : (float) $advance->amount;

        if (abs((float) $entry->amount - $target) < 0.005) {
            return;
        }

        $entry->update(['amount' => $target]);

        // Tanda "melebihi alokasi" dihitung ulang terhadap sisa tanpa entri ini.
        $remaining = $this->balance($entry->pool)['available'];
        $entry->update(['over_allocation' => $remaining < 0]);
    }
}
