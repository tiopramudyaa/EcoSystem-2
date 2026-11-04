@extends('dashboard')
@section('title', 'Web Report Management')
@section('page-title', 'Web Report Management')
@section('page-subtitle', 'Reports sent through the public Web Report form (/web-report). Strictly confidential — every time a report is opened it is logged.')

@section('content')
@php
    $statuses = \App\Models\WebReport::statuses();
    $filterForm = 'webReportFilters';
    $filter = 'hr-general.recruitment.components.header-filter';
    $filtered = collect($filters)->contains(fn ($v) => $v !== '');
    $tileTone = ['new' => 'bg-red-50 text-red-600', 'in_review' => 'bg-amber-50 text-amber-600', 'investigating' => 'bg-violet-50 text-violet-600', 'closed' => 'bg-gray-100 text-gray-500'];
    $tileIcon = ['new' => 'inbox', 'in_review' => 'magnifying-glass', 'investigating' => 'user-secret', 'closed' => 'circle-check'];
    $received = ['from' => $filters['received_from'], 'to' => $filters['received_to']];
@endphp
<div class="w-full space-y-6 px-1 lg:px-2">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-all"><i class="fas fa-arrow-left text-xs"></i> Back to Control Center</a>
        <a href="{{ route('web-report.form') }}" target="_blank" rel="noopener" class="text-xs font-semibold text-gray-500 hover:text-gray-800"><i class="fas fa-arrow-up-right-from-square mr-1"></i> Open the public form</a>
    </div>
    @include('partials.accent-remap')
    @if(session('success'))<div class="text-xs bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3"><i class="fas fa-circle-check mr-1"></i> {{ session('success') }}</div>@endif

    {{-- Overview: one tile per status (click to filter) and the reports past their follow-up date --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        @foreach($statuses as $k => $text)
            <a href="{{ route('admin.web-reports.index', ['status' => $k]) }}" class="bg-white rounded-xl border {{ $filters['status'] === $k ? 'border-gray-400' : 'border-gray-200' }} shadow-sm p-4 flex items-center gap-3 hover:border-gray-300 transition-colors">
                <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $tileTone[$k] ?? 'bg-gray-100 text-gray-500' }}"><i class="fas fa-{{ $tileIcon[$k] ?? 'flag' }}"></i></span>
                <span class="min-w-0"><span class="block text-2xl font-bold text-gray-800 leading-none">{{ $counts[$k] ?? 0 }}</span><span class="block text-[11px] text-gray-500 mt-1 truncate">{{ $text }}</span></span>
            </a>
        @endforeach
        <a href="{{ route('admin.web-reports.index', ['due' => 'overdue']) }}" class="bg-white rounded-xl border {{ $overdue ? 'border-red-300' : 'border-gray-200' }} shadow-sm p-4 flex items-center gap-3 hover:border-gray-300 transition-colors">
            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $overdue ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-500' }}"><i class="fas fa-triangle-exclamation"></i></span>
            <span class="min-w-0"><span class="block text-2xl font-bold leading-none {{ $overdue ? 'text-red-600' : 'text-gray-800' }}">{{ $overdue }}</span><span class="block text-[11px] text-gray-500 mt-1">Past the {{ \App\Models\WebReport::followUpDays() }}-day follow-up</span></span>
        </a>
    </div>

    {{-- Every header filter and the rows-per-page choice belong to this form through their `form` attribute. --}}
    <form id="{{ $filterForm }}" method="GET" action="{{ route('admin.web-reports.index') }}" data-filter-form data-unsaved-ignore class="hidden"></form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-gray-800">Web Reports <span class="text-xs font-medium text-gray-400">({{ $reports->total() }}{{ $filtered ? ' of ' . $total : '' }})</span></h3>
                <p class="text-[10px] text-gray-400 mt-0.5">Use the <i class="fas fa-filter text-[9px]"></i> icons in the table header to search &amp; filter. Reports sent from My Compliance are handled in Compliance Management.</p>
            </div>
            <div class="flex items-center gap-2" data-live-region="toolbar">
                @if($filtered)
                    <a href="{{ route('admin.web-reports.index') }}" class="px-3 py-2 bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-800 text-xs font-semibold rounded-lg shadow-sm transition-colors flex items-center gap-1.5 whitespace-nowrap">
                        <i class="fas fa-rotate-left text-[10px]"></i> Reset Filter
                    </a>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider text-[10px] font-bold border-b border-gray-200 select-none">
                    <tr>
                        @include($filter, ['form' => $filterForm, 'name' => 'q', 'label' => 'Code', 'type' => 'search', 'value' => $filters['q'], 'placeholder' => 'WR-XXXX-XXXX…', 'note' => 'Reporters stay anonymous, so reports are found by their code.', 'thClass' => 'min-w-40'])
                        @include($filter, ['form' => $filterForm, 'name' => 'received', 'label' => 'Received', 'type' => 'daterange', 'value' => $received, 'thClass' => 'min-w-36'])
                        @include($filter, ['form' => $filterForm, 'name' => 'due', 'label' => 'Follow-up due', 'type' => 'options', 'value' => $filters['due'], 'options' => ['open' => 'Still open', 'overdue' => 'Past follow-up date'], 'allLabel' => 'Any follow-up', 'thClass' => 'min-w-40'])
                        @include($filter, ['form' => $filterForm, 'name' => 'status', 'label' => 'Status', 'type' => 'options', 'value' => $filters['status'], 'options' => $statuses, 'allLabel' => 'All statuses', 'thClass' => 'min-w-32'])
                        @include($filter, ['form' => $filterForm, 'name' => 'evidence', 'label' => 'Evidence', 'type' => 'options', 'value' => $filters['evidence'], 'options' => ['yes' => 'With evidence', 'no' => 'Without evidence'], 'allLabel' => 'Any', 'thClass' => 'min-w-32'])
                        <th class="px-4 py-3 text-center sticky right-0 bg-gray-50 border-l border-gray-200">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700" data-live-region="rows">
                    @forelse($reports as $r)
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="px-4 py-3 font-mono text-[11px] font-semibold text-gray-800">{{ $r->tracking_code }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $r->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap {{ $r->isOverdue() ? 'text-red-600 font-semibold' : '' }}">{{ $r->isFinal() ? '—' : ($r->due_at?->format('d M Y') ?? '—') }}@if($r->isOverdue()) <i class="fas fa-triangle-exclamation ml-1"></i>@endif</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $r->statusTone() }}">{{ $r->statusLabel() }}</span></td>
                            <td class="px-4 py-3">{!! $r->evidence_path ? '<i class="fas fa-paperclip text-gray-500" title="Has evidence"></i>' : '<span class="text-gray-300">—</span>' !!}</td>
                            <td class="px-4 py-3 text-center sticky right-0 bg-white border-l border-gray-100"><a href="{{ route('admin.web-reports.show', $r->id) }}" class="px-2.5 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-[11px] font-semibold hover:bg-gray-50 whitespace-nowrap">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500"><i class="fas fa-inbox text-2xl text-gray-300 block mb-2"></i>{{ $filtered ? 'No report matches these filters.' : 'No web report has been sent yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div data-live-region="pagination">
            @include('hr-general.recruitment.components.pagination', ['paginator' => $reports, 'form' => $filterForm, 'perPage' => $reports->perPage(), 'perPageOptions' => \App\Http\Controllers\Controller::PAGE_SIZES])
        </div>
    </div>
</div>

@include('hr-general.inventory.components.modal-helpers', ['dates' => true])
@endsection
