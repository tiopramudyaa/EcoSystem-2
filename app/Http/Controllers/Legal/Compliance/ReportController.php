<?php

namespace App\Http\Controllers\Legal\Compliance;

use App\Exports\ComplianceReportsExport;
use App\Http\Controllers\Controller;
use App\Models\WebReport;
use App\Services\Compliance\ComplianceReportRecap;
use App\Services\WebReport\WebReportHandling;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Legal → Compliance Management → Reports tab (slug config compliance.slugs.manage): the compliance reports staff sent from My Compliance
 * (code CL-…), so the people who hold Compliance Management can see who reported and follow each one up. Public Web Reports (WR-…) are
 * NOT here — they are handled in Control Center → Web Report Management.
 *
 * V read the table, a report and export the recap · E change the status and write the message the reporter sees. Opening a report,
 * downloading its evidence and exporting it are logged in the report's access history. Handling is shared with Web Report Management
 * (WebReportHandling); the filters and the export come from ComplianceReportRecap, so a file always equals the screen.
 */
class ReportController extends Controller
{
    public function index(Request $request, ComplianceReportRecap $recap)
    {
        $f = $recap->filters($request);
        $rows = $recap->rows($f);
        $page = max(1, (int) $request->query('page', 1));

        return view('legal.compliance.management.reports', [
            'f' => $f,
            'period' => $recap->period($f),
            'reports' => (new LengthAwarePaginator($rows->forPage($page, $f['per_page'])->values(), $rows->count(), $f['per_page'], $page, ['path' => $request->url()]))->withQueryString(),
            'counts' => WebReport::compliance()->selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status'),
            'overdue' => WebReport::compliance()->whereNotIn('status', WebReport::finalStatuses(WebReport::CHANNEL_COMPLIANCE))->where('due_at', '<', now())->count(),
            'total' => WebReport::compliance()->count(),
        ]);
    }

    /** Recap of the chosen period as Excel (Recap + Reports sheets) or CSV (the list). Same filters as the table. */
    public function export(Request $request, ComplianceReportRecap $recap)
    {
        $f = $recap->filters($request);
        $period = $recap->period($f);
        $rows = $recap->rows($f);
        $csv = $request->query('format') === 'csv';

        $recap->logExport($rows, $period['label']);

        return Excel::download(
            new ComplianceReportsExport($rows, $recap->summary($rows, $period), $period['label'], $csv),
            'compliance-reports-' . Str::slug($period['label']) . '-' . now()->format('Ymd') . ($csv ? '.csv' : '.xlsx'),
            $csv ? ExcelFormat::CSV : ExcelFormat::XLSX
        );
    }

    public function show(WebReportHandling $handling, int $id)
    {
        $report = WebReport::compliance()->findOrFail($id);
        $handling->event($report->id, 'viewed');

        return view('legal.compliance.management.report-detail', ['report' => $report, 'events' => $handling->events($report)]);
    }

    public function update(Request $request, WebReportHandling $handling, int $id)
    {
        $report = WebReport::compliance()->findOrFail($id);
        $handling->update($report, $request->validate(WebReportHandling::rules(WebReport::CHANNEL_COMPLIANCE, $report->status)));

        return redirect()->route('compliance.manage.report', $report->id)->with('success', 'Report updated.');
    }

    public function evidence(WebReportHandling $handling, int $id)
    {
        return $handling->evidence(WebReport::compliance()->findOrFail($id));
    }
}
