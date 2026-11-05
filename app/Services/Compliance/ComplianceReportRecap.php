<?php

namespace App\Services\Compliance;

use App\Models\WebReport;
use App\Support\Compliance\ReportPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What the Compliance Management → Reports table shows and what its export contains — ONE definition of the filters, so the file always
 * matches the screen. Only compliance reports (channel = compliance, code CL-…); public Web Reports (WR-…) are never included.
 *
 * Name, subject and contact are stored encrypted, so they cannot be searched in SQL: status, reporter type, follow-up and period are
 * filtered in the database, the text search runs over the (decrypted) rows that remain. Fine for the volume of a compliance inbox.
 */
class ComplianceReportRecap
{
    public const PER_PAGE = [10, 20, 50, 100];

    /** Normalised filters from a request (anything unknown becomes the "no filter" value). */
    public function filters(Request $request): array
    {
        $in = $request->query();
        $perPage = (int) ($in['per_page'] ?? config('web-report.per_page'));
        // The Received column filter sends received_from / received_to: that is a date range.
        $received = ($in['received_from'] ?? '') !== '' || ($in['received_to'] ?? '') !== '';

        return [
            'q' => trim((string) ($in['q'] ?? '')),
            'status' => array_key_exists($in['status'] ?? '', WebReport::statuses(WebReport::CHANNEL_COMPLIANCE)) ? $in['status'] : '',
            'who' => in_array($in['who'] ?? '', ['employee', 'anonymous'], true) ? $in['who'] : '',
            'due' => in_array($in['due'] ?? '', ['open', 'overdue'], true) ? $in['due'] : '',
            'per_page' => in_array($perPage, self::PER_PAGE, true) ? $perPage : (int) config('web-report.per_page'),
            'period' => $received ? 'range' : (array_key_exists($in['period'] ?? '', ReportPeriod::OPTIONS) ? $in['period'] : 'all'),
            'date' => (string) ($in['date'] ?? ''), 'month' => (string) ($in['month'] ?? ''), 'year' => (string) ($in['year'] ?? ''),
            'from' => (string) ($received ? ($in['received_from'] ?? '') : ($in['from'] ?? '')), 'to' => (string) ($received ? ($in['received_to'] ?? '') : ($in['to'] ?? '')),
        ];
    }

    /** @return array{key: string, from: ?\Illuminate\Support\Carbon, to: ?\Illuminate\Support\Carbon, label: string} */
    public function period(array $filters): array
    {
        return ReportPeriod::resolve($filters);
    }

    public function query(array $f): Builder
    {
        $period = $this->period($f);
        $final = WebReport::finalStatuses(WebReport::CHANNEL_COMPLIANCE);

        return WebReport::compliance()
            ->when($f['status'] !== '', fn ($q) => $q->where('status', $f['status']))
            ->when($f['who'] === 'employee', fn ($q) => $q->whereNotNull('employee_id'))
            ->when($f['who'] === 'anonymous', fn ($q) => $q->whereNull('employee_id'))
            ->when($f['due'] === 'open', fn ($q) => $q->whereNotIn('status', $final))
            ->when($f['due'] === 'overdue', fn ($q) => $q->whereNotIn('status', $final)->where('due_at', '<', now()))
            ->when($period['from'], fn ($q) => $q->where('created_at', '>=', $period['from']))
            ->when($period['to'], fn ($q) => $q->where('created_at', '<=', $period['to']));
    }

    /** Every report that matches, open ones first, newest first; the text search is applied last. */
    public function rows(array $f): Collection
    {
        $final = WebReport::finalStatuses(WebReport::CHANNEL_COMPLIANCE) ?: ['-'];
        $rows = $this->query($f)->orderByRaw('status IN (' . implode(',', array_fill(0, count($final), '?')) . ')', $final)->orderByDesc('created_at')->get();

        if ($f['q'] === '') {
            return $rows;
        }

        return $rows->filter(fn (WebReport $r) => collect([$r->tracking_code, $r->reporter_name, $r->reported_name])
            ->contains(fn ($v) => $v !== null && mb_stripos((string) $v, $f['q']) !== false))->values();
    }

    /** Counts for the recap sheet and for the tiles above the table. */
    public function summary(Collection $rows, array $period): array
    {
        $closed = $rows->filter(fn (WebReport $r) => $r->isFinal() && $r->closed_at);
        $span = $period['from'] && $period['to'] ? $period['from']->diffInDays($period['to']) : null;
        $byMonth = $span === null || $span > 62;

        return [
            'total' => $rows->count(),
            'by_status' => collect(WebReport::statuses(WebReport::CHANNEL_COMPLIANCE))->map(fn ($label, $k) => $rows->where('status', $k)->count())->all(),
            'employee' => $rows->whereNotNull('employee_id')->count(),
            'anonymous' => $rows->whereNull('employee_id')->count(),
            'overdue' => $rows->filter(fn (WebReport $r) => $r->isOverdue())->count(),
            'evidence' => $rows->filter(fn ($r) => $r->evidence_path)->count(),
            'avg_days_to_close' => $closed->isEmpty() ? null : round($closed->avg(fn ($r) => $r->created_at->diffInHours($r->closed_at) / 24), 1),
            'breakdown_by' => $byMonth ? 'Month' : 'Day',
            'breakdown' => $rows->sortBy('created_at')->groupBy(fn ($r) => $r->created_at->format($byMonth ? 'Y-m' : 'Y-m-d'))->map->count()->all(),
        ];
    }

    /** Each exported report gets a line in its access history (who + when + which period), like opening it does. */
    public function logExport(Collection $rows, string $periodLabel): void
    {
        $actor = (int) session('user.id') ?: null;

        $rows->pluck('id')->chunk(500)->each(fn ($ids) => DB::table('web_report_events')->insert(
            $ids->map(fn ($id) => ['report_id' => $id, 'actor_id' => $actor, 'action' => 'exported', 'detail' => $periodLabel, 'created_at' => now()])->all()
        ));
    }
}
