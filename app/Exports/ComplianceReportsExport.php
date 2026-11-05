<?php

namespace App\Exports;

use App\Models\WebReport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Recap of the compliance reports of one period — an Excel file with a "Recap" sheet (totals, by status, by month or day) and a
 * "Reports" sheet, or the list alone as CSV. Receives rows and summary ALREADY filtered by ComplianceReportRecap, so the file equals
 * what the screen shows. The story (chronology), contact and internal notes are deliberately NOT exported.
 */
class ComplianceReportsExport implements WithMultipleSheets
{
    public function __construct(private Collection $rows, private array $summary, private string $periodLabel, private bool $listOnly = false) {}

    public function sheets(): array
    {
        $list = new ArraySheet('Reports', ['Code', 'Received', 'Reported by', 'Source', 'About', 'Status', 'Follow-up due', 'Closed on', 'Days open', 'Evidence'], $this->listRows());

        return $this->listOnly ? [$list] : [new ArraySheet('Recap', ['Item', 'Value'], $this->recapRows()), $list];
    }

    private function listRows(): array
    {
        $statuses = WebReport::statuses(WebReport::CHANNEL_COMPLIANCE);

        return $this->rows->map(fn (WebReport $r) => [
            $r->tracking_code,
            $r->created_at->format('Y-m-d H:i'),
            $r->reporter_name ?: 'Anonymous',
            $r->employee_id ? 'Employee' : 'Anonymous',
            $r->reported_name,
            $statuses[$r->status] ?? $r->status,
            $r->isFinal() ? '' : ($r->due_at?->format('Y-m-d') ?? ''),
            $r->closed_at?->format('Y-m-d') ?? '',
            (int) floor($r->created_at->diffInDays($r->closed_at ?? now())),
            $r->evidence_path ? 'Yes' : 'No',
        ])->all();
    }

    private function recapRows(): array
    {
        $s = $this->summary;
        $statuses = WebReport::statuses(WebReport::CHANNEL_COMPLIANCE);
        $rows = [
            ['Compliance reports', ''],
            ['Period', $this->periodLabel],
            ['Exported on', now()->format('Y-m-d H:i')],
            ['', ''],
            ['Total reports', $s['total']],
        ];
        foreach ($s['by_status'] as $k => $n) {
            $rows[] = ['   ' . ($statuses[$k] ?? $k), $n];
        }
        $rows = array_merge($rows, [
            ['Sent by an employee', $s['employee']],
            ['Sent anonymously', $s['anonymous']],
            ['Past the follow-up date', $s['overdue']],
            ['With evidence', $s['evidence']],
            ['Average days to close', $s['avg_days_to_close'] ?? '—'],
            ['', ''],
            ['By ' . strtolower($s['breakdown_by']), 'Reports'],
        ]);
        foreach ($s['breakdown'] as $label => $n) {
            $rows[] = [$label, $n];
        }

        return $rows;
    }
}
