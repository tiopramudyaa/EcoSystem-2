@extends('dashboard')
@section('title', 'Compliance Management')
@section('page-title', 'Compliance Management')
@section('page-subtitle', 'Compliance reports sent by employees from My Compliance — see who reported what and follow each one up. Strictly confidential: opening or exporting a report is logged.')

@section('content')
@php
    use App\Models\WebReport;
    use App\Services\Compliance\ComplianceReportRecap;
    use App\Support\Compliance\ReportPeriod;
    $statuses = WebReport::statuses(WebReport::CHANNEL_COMPLIANCE);
    $filterForm = 'reportFilters';
    $filter = 'hr-general.recruitment.components.header-filter';
    $field = 'w-full border border-gray-200 rounded-lg pl-9 pr-8 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300 appearance-none';
    $inputPlain = 'border border-gray-200 rounded-lg px-3 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300';
    $periodOptions = collect(ReportPeriod::OPTIONS)->map(fn ($o) => $o[0])->all();
    $filtered = $f['q'] !== '' || $f['status'] !== '' || $f['who'] !== '' || $f['due'] !== '' || $f['period'] !== 'all';
    $received = $f['period'] === 'range' ? ['from' => $f['from'], 'to' => $f['to']] : ['from' => '', 'to' => ''];
@endphp
<div class="w-full px-1 lg:px-2">
    @include('legal.compliance.management.components.tabs')
    @include('partials.accent-remap')

    <div class="space-y-5 min-w-0">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            @foreach($statuses as $k => $text)
                <a href="{{ route('compliance.manage.reports', ['status' => $k]) }}" class="bg-white rounded-xl border {{ $f['status'] === $k ? 'border-gray-400' : 'border-gray-200' }} shadow-sm p-4 hover:border-gray-300">
                    <p class="text-2xl font-bold text-gray-800 leading-none">{{ $counts[$k] ?? 0 }}</p><p class="text-[11px] text-gray-500 mt-1">{{ $text }}</p>
                </a>
            @endforeach
            <a href="{{ route('compliance.manage.reports', ['due' => 'overdue']) }}" class="bg-white rounded-xl border {{ $overdue ? 'border-red-300' : 'border-gray-200' }} shadow-sm p-4 hover:border-gray-300">
                <p class="text-2xl font-bold leading-none {{ $overdue ? 'text-red-600' : 'text-gray-800' }}">{{ $overdue }}</p><p class="text-[11px] text-gray-500 mt-1">Past the {{ \App\Models\WebReport::followUpDays(\App\Models\WebReport::CHANNEL_COMPLIANCE) }}-day follow-up</p>
            </a>
        </div>

        {{-- Every header filter and the rows-per-page choice belong to this form through their `form` attribute. --}}
        <form id="{{ $filterForm }}" method="GET" action="{{ route('compliance.manage.reports') }}" data-filter-form data-unsaved-ignore class="hidden"></form>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-gray-800">Compliance Reports</h3>
                    <p class="text-[10px] text-gray-400 mt-0.5">Use the <i class="fas fa-filter text-[9px]"></i> icons in the table header to search &amp; filter.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2" data-live-region="toolbar">
                    @if($period['key'] !== 'all')<span class="px-2.5 py-1.5 rounded-lg bg-gray-50 border border-gray-200 text-[11px] text-gray-600 whitespace-nowrap"><i class="fas fa-calendar mr-1 text-gray-400"></i>{{ $period['label'] }}</span>@endif
                    @if($filtered)
                        <a href="{{ route('compliance.manage.reports') }}" class="px-3 py-2 bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-800 text-xs font-semibold rounded-lg shadow-sm transition-colors flex items-center gap-1.5 whitespace-nowrap">
                            <i class="fas fa-rotate-left text-[10px]"></i> Reset Filter
                        </a>
                    @endif
                    <button type="button" onclick="openReportExport()" class="inline-flex items-center gap-1.5 px-4 py-2 primary-gradient text-white text-xs font-semibold rounded-lg hover:opacity-90 transition-all shadow-sm whitespace-nowrap"><i class="fas fa-file-export text-xs"></i> Export recap</button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider text-[10px] font-bold border-b border-gray-200 select-none">
                        <tr>
                            @include($filter, ['form' => $filterForm, 'name' => 'received', 'label' => 'Received', 'type' => 'daterange', 'value' => $received, 'thClass' => 'min-w-36'])
                            @include($filter, ['form' => $filterForm, 'name' => 'who', 'label' => 'Reported by', 'type' => 'options', 'value' => $f['who'], 'options' => ['employee' => 'Employee', 'anonymous' => 'Anonymous'], 'allLabel' => 'Anyone', 'thClass' => 'min-w-40'])
                            @include($filter, ['form' => $filterForm, 'name' => 'q', 'label' => 'About', 'type' => 'search', 'value' => $f['q'], 'placeholder' => 'Name, subject or code…', 'note' => 'Searches the reporter, the subject and the code.', 'thClass' => 'min-w-48'])
                            @include($filter, ['form' => $filterForm, 'name' => 'status', 'label' => 'Status', 'type' => 'options', 'value' => $f['status'], 'options' => $statuses, 'allLabel' => 'All statuses', 'thClass' => 'min-w-32'])
                            @include($filter, ['form' => $filterForm, 'name' => 'due', 'label' => 'Follow-up due', 'type' => 'options', 'value' => $f['due'], 'options' => ['open' => 'Still open', 'overdue' => 'Past follow-up date'], 'allLabel' => 'Any follow-up', 'thClass' => 'min-w-40'])
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3 text-center sticky right-0 bg-gray-50 border-l border-gray-200">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700" data-live-region="rows">
                        @forelse($reports as $r)
                            <tr class="align-top">
                                <td class="px-4 py-3 whitespace-nowrap">{{ $r->created_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    @if($r->reporter_name)
                                        <p class="font-semibold text-gray-800"><i class="fas fa-user text-gray-300 mr-1"></i>{{ $r->reporter_name }}</p>
                                        <p class="text-[11px] text-gray-400">Employee · My Compliance</p>
                                    @else
                                        <p class="text-gray-500 italic"><i class="fas fa-user-secret text-gray-300 mr-1"></i>Anonymous</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 max-w-xs">{{ \Illuminate\Support\Str::limit($r->reported_name, 70) }}@if($r->evidence_path) <i class="fas fa-paperclip text-gray-400 ml-1" title="Has evidence"></i>@endif</td>
                                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $r->statusTone() }}">{{ $r->statusLabel() }}</span></td>
                                <td class="px-4 py-3 whitespace-nowrap {{ $r->isOverdue() ? 'text-red-600 font-semibold' : '' }}">{{ $r->isFinal() ? '—' : ($r->due_at?->format('d M Y') ?? '—') }}@if($r->isOverdue()) <i class="fas fa-triangle-exclamation ml-1"></i>@endif</td>
                                <td class="px-4 py-3 font-mono text-[11px] text-gray-500">{{ $r->tracking_code }}</td>
                                <td class="px-4 py-3 text-center sticky right-0 bg-white border-l border-gray-100"><a href="{{ route('compliance.manage.report', $r->id) }}" class="px-2.5 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-[11px] font-semibold hover:bg-gray-50 whitespace-nowrap">Follow up</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500"><i class="fas fa-inbox text-2xl text-gray-300 block mb-2"></i>{{ $filtered ? 'No report matches these filters.' : 'No compliance report yet.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div data-live-region="pagination">
                @include('hr-general.recruitment.components.pagination', ['paginator' => $reports, 'form' => $filterForm, 'perPage' => $reports->perPage(), 'perPageOptions' => ComplianceReportRecap::PER_PAGE])
            </div>
        </div>
    </div>
</div>

{{-- Export recap: choose the period (a day, a month, a year, a range or a preset), the status and the file type. --}}
<div id="reportExportModal" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 items-center justify-center p-4" style="display: none;" onclick="if(event.target===this)closeReportExport()">
    <form method="GET" action="{{ route('compliance.manage.reports-export') }}" id="reportExportForm" class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[92vh] overflow-y-auto" data-unsaved-ignore>
        <input type="hidden" name="q" value="{{ $f['q'] }}"><input type="hidden" name="who" value="{{ $f['who'] }}"><input type="hidden" name="due" value="{{ $f['due'] }}">
        <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-3">
            <div><h3 class="text-sm font-bold text-gray-800"><i class="fas fa-file-export text-gray-400 mr-1"></i> Export recap</h3><p class="text-[11px] text-gray-500 mt-0.5">Totals by status and by month or day, plus the list. The story and contact details are not exported.</p></div>
            <button type="button" onclick="closeReportExport()" class="text-gray-400 hover:text-gray-600" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="px-5 py-4 space-y-4">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1">Period</label>
                <div class="relative">
                    <i class="fas fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    <select name="period" class="{{ $field }}" data-period-select>
                        @foreach($periodOptions as $val => $text)<option value="{{ $val }}" @selected($f['period'] === $val)>{{ $text }}</option>@endforeach
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-[9px] pointer-events-none"></i>
                </div>
                <div class="flex flex-wrap items-center gap-2 mt-2">@include('legal.compliance.management.components.period-inputs', ['f' => $f, 'inputPlain' => $inputPlain])</div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1">Status</label>
                    <div class="relative"><i class="fas fa-flag absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        <select name="status" class="{{ $field }}"><option value="">All statuses</option>@foreach($statuses as $val => $text)<option value="{{ $val }}" @selected($f['status'] === $val)>{{ $text }}</option>@endforeach</select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-[9px] pointer-events-none"></i></div></div>
                <div><label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1">File</label>
                    <div class="relative"><i class="fas fa-file-excel absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        <select name="format" class="{{ $field }}"><option value="xlsx">Excel — recap + list</option><option value="csv">CSV — list only</option></select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-[9px] pointer-events-none"></i></div></div>
            </div>
            @if($f['q'] !== '' || $f['who'] !== '' || $f['due'] !== '')
                <p class="text-[11px] text-gray-500 bg-gray-50 rounded-lg px-3 py-2"><i class="fas fa-circle-info mr-1"></i> Your current search and filters (reporter, follow-up) are kept in the export.</p>
            @endif
        </div>
        <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-2">
            <button type="button" onclick="closeReportExport()" class="px-4 py-2 text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
            <button class="px-4 py-2 primary-gradient text-white text-xs font-semibold rounded-lg hover:opacity-90"><i class="fas fa-download mr-1"></i> Download</button>
        </div>
    </form>
</div>

@include('hr-general.inventory.components.modal-helpers', ['dates' => true])

@push('scripts')
<script>
(function () {
    // The export dialog shows only the inputs its period needs: a day, a month, a year or a range.
    const need = { day: 'date', month: 'month', year: 'year', range: 'range' };
    document.querySelectorAll('[data-period-select]').forEach(select => {
        const form = select.form;
        const sync = () => form.querySelectorAll('[data-when]').forEach(g => { g.style.display = g.dataset.when === need[select.value] ? 'flex' : 'none'; });
        select.addEventListener('change', sync);
        sync();
    });

    // A month is chosen with a month and a year dropdown; the form sends them as YYYY-MM.
    document.querySelectorAll('[data-when="month"]').forEach(g => {
        const m = g.querySelector('[data-month-part="m"]'), y = g.querySelector('[data-month-part="y"]'), h = g.querySelector('input[name="month"]');
        const update = () => { h.value = y.value + '-' + m.value; };
        m.addEventListener('change', update); y.addEventListener('change', update); update();
    });

    const modal = document.getElementById('reportExportModal');
    window.openReportExport = () => { modal.style.display = 'flex'; modal.classList.remove('hidden'); };
    window.closeReportExport = () => { modal.style.display = 'none'; modal.classList.add('hidden'); };
})();
</script>
@endpush
@endsection
