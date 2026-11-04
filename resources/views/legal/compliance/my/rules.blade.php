@extends('legal.compliance.my.layout')
@section('my-content')
<div class="grid sm:grid-cols-2 gap-4">
    <div class="cp-card px-5 py-4 flex items-center gap-3"><span class="w-9 h-9 rounded-lg bg-green-50 text-green-700 flex items-center justify-center"><i class="fas fa-check"></i></span>
        <div><p class="text-sm font-semibold text-gray-800">{{ $dos->count() }} compliance rules</p><p class="text-xs text-gray-500">Actions that protect you and the company</p></div></div>
    <div class="cp-card px-5 py-4 flex items-center gap-3"><span class="w-9 h-9 rounded-lg bg-red-50 text-red-700 flex items-center justify-center"><i class="fas fa-ban"></i></span>
        <div><p class="text-sm font-semibold text-gray-800">{{ $donts->count() }} violation rules</p><p class="text-xs text-gray-500">Actions that lead to disciplinary action</p></div></div>
</div>
<div class="grid lg:grid-cols-2 gap-6 items-start">
    @foreach([['dos', 'You should do', 'fa-check', 'bg-green-50 text-green-800 border-green-100', 'bg-green-50 text-green-700', $dos], ['donts', 'You must not do', 'fa-xmark', 'bg-red-50 text-red-800 border-red-100', 'bg-red-50 text-red-700', $donts]] as [$id, $head, $mark, $headTone, $bullet, $list])
        <div class="cp-card overflow-hidden">
            <p class="px-5 py-3 text-sm font-semibold border-b {{ $headTone }}"><i class="fas {{ $mark }} mr-2"></i>{{ $head }}</p>
            @forelse($list as $r)
                <div class="px-5 py-4 border-b border-gray-100 last:border-0 flex gap-3">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] shrink-0 {{ $bullet }}"><i class="fas {{ $mark }}"></i></span>
                    <div class="min-w-0"><p class="text-sm font-semibold text-gray-800">{{ $r->title }}</p><p class="text-xs text-gray-600 mt-1">{{ $r->description }}</p>
                        <div class="mt-2 flex items-center gap-2">@if($id === 'donts')<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase {{ \App\Models\Compliance\ComplianceOption::badge('rule_severity', $r->severity) }}">{{ \App\Models\Compliance\ComplianceOption::label('rule_severity', $r->severity) }}</span>@endif
                            @if($r->category)<span class="text-[11px] text-gray-400">{{ $r->category->name }}</span>@endif</div></div>
                </div>
            @empty<p class="px-5 py-8 text-center text-xs text-gray-500">Nothing here yet.</p>@endforelse
        </div>
    @endforeach
</div>
@endsection
