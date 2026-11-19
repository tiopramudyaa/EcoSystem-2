<?php

namespace App\Services\ObjectFund;

use App\Models\ObjectFund\FundEntry;
use App\Models\ObjectFund\FundPool;
use App\Models\ObjectFund\ObjectFlowSetting;
use App\Models\ObjectFund\OfficeSetting;
use App\Models\Reimbursement\ReimbursementRequest;
use Illuminate\Support\Carbon;
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

        return (float) $query->sum('i.amount');
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

    /** Kembalikan dana sebuah dokumen (void oleh Finance). */
    public function release(ReimbursementRequest $request, int $actorId, string $reason): int
    {
        return FundEntry::where('document_type', 'reimbursement')
            ->where('document_id', $request->id)
            ->where('status', FundEntry::STATUS_COMMITTED)
            ->update([
                'status'      => FundEntry::STATUS_VOIDED,
                'voided_at'   => now(),
                'voided_by'   => $actorId,
                'void_reason' => mb_substr($reason, 0, 255),
            ]);
    }
}
