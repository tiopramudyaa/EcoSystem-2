@extends('dashboard')
@section('title', 'External Contract')
@section('page-title', 'External Contract')
@section('page-subtitle', 'Agreements of external consultants at a glance.')

@section('content')
@php
    $tones = ['blue' => 'bg-blue-50 text-blue-600', 'green' => 'bg-green-50 text-green-600', 'amber' => 'bg-amber-50 text-amber-600', 'sky' => 'bg-sky-50 text-sky-600'];
@endphp
<div class="w-full space-y-6 px-1 lg:px-2">
    @include('hr-general.external.contracts.components.tabs')
    @include('partials.accent-remap')

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['All contracts', $summary['total'], 'file-contract', 'blue'],
            ['Active', $summary['active'], 'circle-check', 'green'],
            ['Draft', $summary['draft'], 'pen-to-square', 'amber'],
            ['Using a template', $summary['templated'], 'file-lines', 'sky'],
        ] as [$label, $count, $icon, $tone])
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $tones[$tone] }}"><i class="fas fa-{{ $icon }}"></i></span>
                <div class="min-w-0"><p class="text-2xl font-bold text-gray-800 leading-none">{{ number_format($count) }}</p><p class="text-[11px] text-gray-500 mt-1 truncate">{{ $label }}</p></div>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between gap-3">
            <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-hourglass-half text-gray-400"></i> Ending in {{ $endingDays }} days</h3>
            <a href="{{ route('general.external.contracts.index', ['status' => 'active']) }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800">All active <i class="fas fa-arrow-right ml-1 text-[10px]"></i></a>
        </div>
        @forelse($ending as $c)
            <a href="{{ route('general.external.contracts.document', $c->contract_id) }}" class="px-5 py-3 border-b border-gray-100 last:border-0 flex items-center justify-between gap-3 text-xs hover:bg-gray-50">
                <span class="min-w-0"><span class="font-semibold text-gray-800">{{ $c->name }}</span> <span class="text-gray-400">· {{ $c->contract_number ?: $c->contract_name }}</span></span>
                <span class="text-amber-700 font-semibold shrink-0">{{ \Illuminate\Support\Carbon::parse($c->end_date)->format('d M Y') }}</span>
            </a>
        @empty
            <p class="px-5 py-8 text-center text-xs text-gray-500">No external contract ends soon.</p>
        @endforelse
    </div>
</div>
@endsection
