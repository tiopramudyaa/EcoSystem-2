<?php

namespace App\Http\Controllers\WebReport;

use App\Http\Controllers\Controller;
use App\Models\WebReport;
use App\Services\WebReport\WebReportHandling;
use Illuminate\Http\Request;

/**
 * Control Center → Web Report Management (slug config web-report.slug, URL /admin/web-reports): the inbox of the reports sent through
 * the public form (PublicController). The top bar of every page links here, with the number of new reports, for people who hold the slug.
 *
 * V read the inbox and a report · E change the status, write the message the reporter sees and the internal note.
 * Opening a report and downloading its evidence are written to web_report_events (who + when, never the content).
 * There is no Create (reports only arrive from the public form) and no Delete (a report is closed, not erased).
 */
class ManagementController extends Controller
{
    public function index(Request $request)
    {
        $in = $request->query();
        $date = fn ($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? $v : '';
        $filters = [
            'q' => strtoupper(trim((string) ($in['q'] ?? ''))),
            'status' => array_key_exists($in['status'] ?? '', WebReport::statuses()) ? $in['status'] : '',
            'due' => in_array($in['due'] ?? '', ['open', 'overdue'], true) ? $in['due'] : '',
            'evidence' => in_array($in['evidence'] ?? '', ['yes', 'no'], true) ? $in['evidence'] : '',
            'received_from' => $date($in['received_from'] ?? ''), 'received_to' => $date($in['received_to'] ?? ''),
        ];
        $final = WebReport::finalStatuses();

        $reports = WebReport::web()
            ->when($filters['q'] !== '', fn ($q) => $q->where('tracking_code', 'like', '%' . addcslashes($filters['q'], '%_\\') . '%'))
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['due'] === 'open', fn ($q) => $q->whereNotIn('status', $final))
            ->when($filters['due'] === 'overdue', fn ($q) => $q->whereNotIn('status', $final)->where('due_at', '<', now()))
            ->when($filters['evidence'] === 'yes', fn ($q) => $q->whereNotNull('evidence_path'))
            ->when($filters['evidence'] === 'no', fn ($q) => $q->whereNull('evidence_path'))
            ->when($filters['received_from'] !== '', fn ($q) => $q->whereDate('created_at', '>=', $filters['received_from']))
            ->when($filters['received_to'] !== '', fn ($q) => $q->whereDate('created_at', '<=', $filters['received_to']))
            ->orderByRaw('status IN (' . implode(',', array_fill(0, count($final), '?')) . ')', $final)->orderByDesc('created_at')
            ->paginate($this->pageSize(25))->withQueryString();

        return view('admin.web-report.index', [
            'reports' => $reports,
            'filters' => $filters,
            'counts' => WebReport::web()->selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status'),
            'overdue' => WebReport::web()->whereNotIn('status', $final)->where('due_at', '<', now())->count(),
            'total' => WebReport::web()->count(),
        ]);
    }

    public function show(int $id, WebReportHandling $handling)
    {
        $report = WebReport::web()->findOrFail($id);
        $handling->event($report->id, 'viewed');

        return view('admin.web-report.show', ['report' => $report, 'events' => $handling->events($report)]);
    }

    public function update(Request $request, WebReportHandling $handling, int $id)
    {
        $report = WebReport::web()->findOrFail($id);
        $handling->update($report, $request->validate(WebReportHandling::rules()));

        return redirect()->route('admin.web-reports.show', $report->id)->with('success', 'Report updated.');
    }

    public function evidence(WebReportHandling $handling, int $id)
    {
        return $handling->evidence(WebReport::web()->findOrFail($id));
    }
}
