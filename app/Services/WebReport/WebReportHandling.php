<?php

namespace App\Services\WebReport;

use App\Models\WebReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Handling a web report: change its status / notes, read its access history and download its evidence. Shared by Web Report Management
 * (Control Center) and Compliance Management → Reports. Every open, change and evidence download is written to web_report_events
 * (who + when, never the content).
 */
class WebReportHandling
{
    public function update(WebReport $report, array $data): void
    {
        $before = $report->status;

        $report->fill($data);
        $report->closed_at = $report->isFinal() ? ($report->closed_at ?? now()) : null;
        $report->save();

        $this->event($report->id, $before === $data['status'] ? 'note' : 'status', $before === $data['status'] ? null : "{$before} → {$data['status']}");
    }

    public function event(int $reportId, string $action, ?string $detail = null): void
    {
        DB::table('web_report_events')->insert([
            'report_id' => $reportId, 'actor_id' => (int) session('user.id') ?: null, 'action' => $action, 'detail' => $detail, 'created_at' => now(),
        ]);
    }

    public function events(WebReport $report)
    {
        return DB::table('web_report_events as e')->leftJoin('employee_basic_data as b', 'b.employee_id', '=', 'e.actor_id')
            ->where('e.report_id', $report->id)->orderByDesc('e.id')
            ->get(['e.action', 'e.detail', 'e.created_at', DB::raw("CONCAT_WS(' ', b.first_name, b.last_name) as actor")]);
    }

    /** The evidence file as a download response (404 when there is none); the download is logged. */
    public function evidence(WebReport $report)
    {
        $disk = Storage::disk(config('web-report.disk'));
        abort_unless($report->evidence_path && $disk->exists($report->evidence_path), 404);

        $this->event($report->id, 'evidence_viewed');

        return $disk->download($report->evidence_path, $report->evidence_name ?: basename($report->evidence_path));
    }

    /** The status must be one the channel offers (for a report that already has an inactive status, that one too). */
    public static function rules(string $channel = WebReport::CHANNEL_WEB, ?string $current = null): array
    {
        return [
            'status' => 'required|in:' . implode(',', array_keys(WebReport::choices($channel, $current))),
            'public_note' => 'nullable|string|max:2000',
            'internal_note' => 'nullable|string|max:10000',
        ];
    }
}
