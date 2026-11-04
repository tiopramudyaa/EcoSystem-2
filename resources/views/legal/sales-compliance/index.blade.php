@extends('dashboard')
@section('title', 'Sales Compliance Approval')
@section('page-title', 'Sales Compliance Approval')
@section('page-subtitle', 'Every sales deal passes a compliance review — price, hospitality and anti-bribery declarations — before it is signed.')

@section('content')
@php
    $tabs = [
        '' => ['All', $counts->sum()],
        'draft' => ['Draft', $counts['draft'] ?? 0],
        'in_review' => ['In Review', $counts['in_review'] ?? 0],
        'approved' => ['Approved', $counts['approved'] ?? 0],
        'rejected' => ['Rejected', $counts['rejected'] ?? 0],
        'cancelled' => ['Cancelled', $counts['cancelled'] ?? 0],
    ];
@endphp

<div class="w-full px-1 lg:px-2">
    @include('legal.sales-compliance._tabs')

    @if(count($mine))
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <i class="fas fa-bell mr-1"></i> {{ count($mine) }} request{{ count($mine) > 1 ? 's are' : ' is' }} waiting for your decision.
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="px-5 py-3.5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-1.5">
                @foreach($tabs as $key => [$label, $n])
                    <a href="{{ route('legal.sales-compliance.index', array_filter(['status' => $key, 'q' => $search])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold border {{ $status === $key ? 'primary-gradient text-white border-transparent' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                        {{ $label }} <span class="opacity-70">{{ $n }}</span>
                    </a>
                @endforeach
            </div>
            <div class="flex items-center gap-2">
                <form method="GET" class="flex items-center gap-2">
                    @if($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif
                    <input type="search" name="q" value="{{ $search }}" placeholder="Number, project or customer" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm w-56 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <button class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50">Search</button>
                </form>
                @if($canCreate)
                    <a href="{{ route('legal.sales-compliance.create') }}" class="px-3.5 py-1.5 rounded-lg primary-gradient hover:opacity-90 text-white text-sm font-semibold">New Request</a>
                @endif
            </div>
        </div>

        @unless($seesAll)
            <p class="px-5 pt-3 text-xs text-gray-400">You see the requests you created. People who decide on requests see all of them.</p>
        @endunless

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b border-gray-100">
                        <th class="px-5 py-2.5 font-semibold">Number</th>
                        <th class="px-3 py-2.5 font-semibold">Project / Customer</th>
                        <th class="px-3 py-2.5 font-semibold">Sales</th>
                        <th class="px-3 py-2.5 font-semibold text-right">Final price</th>
                        <th class="px-3 py-2.5 font-semibold">Status</th>
                        <th class="px-3 py-2.5 font-semibold">Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium whitespace-nowrap">
                                <a href="{{ route('legal.sales-compliance.show', $row) }}" class="primary-text hover:underline">{{ $row->request_no }}</a>
                                @if(in_array($row->id, $mine, true))<span class="ml-1.5 text-[10px] font-bold text-amber-700 bg-amber-100 rounded px-1.5 py-0.5">YOUR TURN</span>@endif
                            </td>
                            <td class="px-3 py-3">
                                <p class="text-gray-900">{{ $row->project_name }}</p>
                                <p class="text-xs text-gray-500">{{ $row->customer_name }}</p>
                            </td>
                            <td class="px-3 py-3 text-gray-700">{{ $row->employee?->fullNameLabel() ?? '—' }}</td>
                            <td class="px-3 py-3 text-right text-gray-700 whitespace-nowrap">{{ $row->final_price !== null ? number_format((float) $row->final_price, 0, '.', ',') : '—' }}</td>
                            <td class="px-3 py-3">@include('legal.workflow._status', ['status' => $row->status, 'label' => $row->statusLabel()])</td>
                            <td class="px-3 py-3 text-gray-500 whitespace-nowrap">{{ $row->updated_at?->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-gray-100">{{ $rows->links() }}</div>
    </div>
</div>
@endsection
