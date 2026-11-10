<?php

namespace App\Services;

use App\Models\DeliveryProject;
use App\Models\DeliveryProjectCost;
use App\Models\DeliverySupport;
use App\Models\DeliverySupportCost;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Report "Monthly Cost vs TOP Invoiced" sebuah Delivery Project — satu baris
 * per bulan:
 *
 *   Release Cost  = jadwal release bulanan (delivery_project_cost_release_schedules)
 *   Actual Cost   = expense detail Plan Cost per period
 *   TOP Invoiced  = termin TOP berstatus Invoiced / Paid, dikelompokkan per
 *                   bulan Submit Invoice Date
 *   Selisih       = TOP Invoiced − Actual  (Release tidak ikut dihitung)
 *   Kumulatif     = jumlah berjalan Selisih s.d. bulan tsb.
 *
 * Yang tidak punya bulan (release belum dijadwalkan, expense lama tanpa
 * period, termin tanpa Submit Invoice Date) masuk baris "unscheduled" di
 * bawah bulan terakhir, sehingga baris Total selalu = jumlah seluruh data.
 *
 * Hanya cost "leaf" yang dihitung — sub-item, atau parent tanpa sub-item —
 * sama seperti tabel Plan Cost (nilai parent ber-sub-item = agregat anaknya).
 * Dipakai bersama oleh endpoint JSON dan export Excel supaya angkanya identik.
 *
 * Berlaku juga untuk Delivery Support: struktur Plan Cost & TOP-nya mirror
 * project (DeliverySupportCost + SupportTopPlan), hanya tabelnya yang beda.
 */
class ProjectMonthlyCostReport
{
    /** Status termin yang dianggap sudah ditagih ke customer. */
    public const INVOICED_STATUSES = ['Invoiced', 'Paid'];

    private ?Collection $leaves = null;

    public function __construct(private DeliveryProject|DeliverySupport $project)
    {
    }

    public function build(): array
    {
        $release = [];
        $actual  = [];
        $top     = [];

        $totalRelease     = 0.0;
        $scheduledRelease = 0.0;
        $actualNoPeriod   = 0.0;
        $topNoDate        = 0.0;

        foreach ($this->leaves() as $leaf) {
            $totalRelease += (float) ($leaf->release_amount ?? 0);

            foreach ($leaf->releaseSchedules as $s) {
                $release[$s->period] = ($release[$s->period] ?? 0) + (float) $s->amount;
                $scheduledRelease   += (float) $s->amount;
            }

            foreach ($leaf->items as $item) {
                if ($item->period) {
                    $actual[$item->period] = ($actual[$item->period] ?? 0) + (float) $item->amount;
                } else {
                    $actualNoPeriod += (float) $item->amount;
                }
            }
        }

        foreach ($this->invoicedTerms() as $term) {
            if ($term['date']) {
                $p = $term['date']->format('Y-m');
                $top[$p] = ($top[$p] ?? 0) + $term['amount'];
            } else {
                $topNoDate += $term['amount'];
            }
        }

        $months     = [];
        $cumulative = 0.0;
        foreach ($this->periodRange(array_keys($release + $actual + $top)) as $p) {
            $diff        = ($top[$p] ?? 0) - ($actual[$p] ?? 0);
            $cumulative += $diff;
            $months[] = [
                'period'     => $p,
                'release'    => $release[$p] ?? null,
                'actual'     => $actual[$p] ?? null,
                'top'        => $top[$p] ?? null,
                'diff'       => $diff,
                'cumulative' => $cumulative,
            ];
        }

        $unscheduledRelease = $totalRelease - $scheduledRelease;
        $unscheduledDiff    = $topNoDate - $actualNoPeriod;
        $unscheduled = [
            'release'    => abs($unscheduledRelease) > 0.004 ? $unscheduledRelease : null,
            'actual'     => $actualNoPeriod > 0 ? $actualNoPeriod : null,
            'top'        => $topNoDate > 0 ? $topNoDate : null,
            'diff'       => $unscheduledDiff,
            'cumulative' => $cumulative + $unscheduledDiff,
        ];

        $totalActual = array_sum($actual) + $actualNoPeriod;
        $totalTop    = array_sum($top) + $topNoDate;

        return [
            'months'      => $months,
            'unscheduled' => $unscheduled,
            // Baris unscheduled ditampilkan hanya bila ada isinya.
            'has_unscheduled' => $unscheduled['release'] !== null
                || $unscheduled['actual'] !== null
                || $unscheduled['top'] !== null,
            'totals' => [
                'release' => $totalRelease,
                'actual'  => $totalActual,
                'top'     => $totalTop,
                'diff'    => $totalTop - $totalActual,
            ],
        ];
    }

    /**
     * Rekap jadwal release per cost leaf (sheet "Release Schedule" export).
     *
     * @return array<int,array{cost_type:string,cost_code:?string,cost_name:string,total_release:float,scheduled:float,unscheduled:float,schedules:array}>
     */
    public function releaseSchedules(): array
    {
        return $this->leaves()->map(function (DeliveryProjectCost|DeliverySupportCost $leaf) {
            $scheduled = (float) $leaf->releaseSchedules->sum('amount');
            $total     = (float) ($leaf->release_amount ?? 0);

            return [
                'cost_type'     => $leaf->cost_type,
                'cost_code'     => $leaf->code,
                'cost_name'     => $leaf->name,
                'total_release' => $total,
                'scheduled'     => $scheduled,
                'unscheduled'   => $total - $scheduled,
                'schedules'     => $leaf->releaseSchedules->map(fn ($s) => [
                    'date'   => $s->schedule_date,
                    'period' => $s->period,
                    'amount' => (float) $s->amount,
                    'notes'  => $s->notes,
                ])->all(),
            ];
        })->values()->all();
    }

    /** Cost leaf berurutan sesuai tabel Plan Cost, lengkap dengan expense & jadwal release. */
    private function leaves(): Collection
    {
        if ($this->leaves !== null) {
            return $this->leaves;
        }

        $query = $this->project instanceof DeliverySupport
            ? DeliverySupportCost::where('delivery_support_id', $this->project->id)
            : DeliveryProjectCost::where('delivery_projects_id', $this->project->id);

        $parents = $query
            ->whereNull('parent_id')
            ->with(['items', 'releaseSchedules', 'children.items', 'children.releaseSchedules'])
            ->orderBy('order_sequence')
            ->get();

        return $this->leaves = $parents->flatMap(
            fn ($p) => $p->children->isNotEmpty() ? $p->children : collect([$p])
        )->values();
    }

    /** @return array<int,array{date:?Carbon,amount:float}> */
    private function invoicedTerms(): array
    {
        $plan    = $this->project instanceof DeliverySupport
            ? new SupportTopPlan($this->project)
            : new ProjectTopPlan($this->project);
        $revenue = $plan->revenue();

        return $plan->terms()
            ->filter(fn ($t) => in_array($t->status, self::INVOICED_STATUSES, true))
            ->map(fn ($t) => [
                'date'   => $t->submit_invoice_date,
                'amount' => $t->effectiveAmount($revenue),
            ])
            ->values()
            ->all();
    }

    /**
     * Semua bulan dari period terkecil s.d. terbesar tanpa celah, supaya bulan
     * tanpa transaksi tetap tampil dan kumulatifnya terbaca berurutan.
     *
     * @param  array<int,string> $periods "YYYY-MM"
     * @return array<int,string>
     */
    private function periodRange(array $periods): array
    {
        if (!$periods) {
            return [];
        }

        sort($periods);
        $cursor = Carbon::createFromFormat('!Y-m', $periods[0]);
        $end    = Carbon::createFromFormat('!Y-m', end($periods));

        $range = [];
        while ($cursor->lte($end)) {
            $range[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return $range;
    }
}
