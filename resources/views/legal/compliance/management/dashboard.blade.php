@extends('dashboard')
@section('title', 'Compliance Management')
@section('page-title', 'Compliance Management')
@section('page-subtitle', 'What My Compliance holds today and what needs attention.')

@section('content')
@php
    $tones = ['blue' => 'bg-blue-50 text-blue-600', 'green' => 'bg-green-50 text-green-600', 'amber' => 'bg-amber-50 text-amber-600', 'sky' => 'bg-sky-50 text-sky-600', 'violet' => 'bg-violet-50 text-violet-600'];
    $tiles = [
        ['Articles', $counts['articles'], 'book-open', 'blue'],
        ['Categories', $counts['categories'], 'folder-tree', 'sky'],
        ['Do & Don\'t rules', $counts['rules'], 'scale-balanced', 'green'],
        ['Critical rules', $counts['alerts'], 'triangle-exclamation', 'amber'],
        ['Training sessions', $counts['sessions'], 'calendar-days', 'violet'],
    ];
@endphp

<div class="w-full px-1 lg:px-2">
    @include('legal.compliance.management.components.tabs')

    <div class="space-y-6 min-w-0">
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            @foreach($tiles as [$label, $n, $icon, $tone])
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $tones[$tone] }}"><i class="fas fa-{{ $icon }}"></i></span>
                    <div class="min-w-0"><p class="text-2xl font-bold text-gray-800 leading-none">{{ number_format($n) }}</p><p class="text-[11px] text-gray-500 mt-1 truncate">{{ $label }}</p></div>
                </div>
            @endforeach
        </div>

        @if($newReports > 0 || $overdueReports > 0)
            <a href="{{ route('compliance.manage.reports', $newReports ? ['status' => 'new'] : []) }}" class="flex items-center justify-between gap-3 text-xs bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 hover:bg-red-100">
                <span><i class="fas fa-flag mr-1"></i> <strong>{{ $newReports }}</strong> new report{{ $newReports === 1 ? '' : 's' }} waiting @if($overdueReports) · <strong>{{ $overdueReports }}</strong> past the follow-up date @endif</span>
                <span class="font-semibold whitespace-nowrap">Follow up <i class="fas fa-arrow-right ml-1 text-[10px]"></i></span>
            </a>
        @endif

        @if($hidden->sum() > 0)
            <div class="text-xs bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-4 py-3">
                <i class="fas fa-eye-slash mr-1"></i> Switched off in the portal:
                {{ $hidden['articles'] }} article(s), {{ $hidden['rules'] }} rule(s), {{ $hidden['categories'] }} categor{{ $hidden['categories'] === 1 ? 'y' : 'ies' }}.
            </div>
        @endif

        <div class="grid lg:grid-cols-3 gap-6 items-start">
            @foreach([
                ['Upcoming training', 'calendar-days', $upcoming, fn ($r) => [$r->title, $r->session_date->format('d M Y')], route('compliance.manage.content', ['section' => 'sessions']), 'No upcoming session.'],
                ['Recently updated articles', 'pen-to-square', $recent, fn ($r) => [$r->title, $r->updated_at->format('d M Y')], route('compliance.manage.content', ['section' => 'articles']), 'No article yet.'],
                ['Most read articles', 'eye', $topViewed, fn ($r) => [$r->title, number_format($r->views) . ' views'], route('compliance.manage.content', ['section' => 'articles']), 'Nothing has been read yet.'],
            ] as [$title, $icon, $list, $line, $link, $empty])
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-{{ $icon }} text-gray-400"></i> {{ $title }}</h3>
                        <a href="{{ $link }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800">Open <i class="fas fa-arrow-right ml-1 text-[10px]"></i></a>
                    </div>
                    @forelse($list as $r)
                        @php [$main, $side] = $line($r); @endphp
                        <div class="px-5 py-3 border-b border-gray-100 last:border-0 flex items-center justify-between gap-3 text-xs">
                            <span class="font-semibold text-gray-800 truncate">{{ $main }}</span><span class="text-gray-400 shrink-0">{{ $side }}</span>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-xs text-gray-500">{{ $empty }}</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
