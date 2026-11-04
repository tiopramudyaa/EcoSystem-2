@extends('legal.compliance.my.layout')
@section('my-content')
<div class="rounded-xl p-6 sm:p-7 primary-gradient text-white">
    <p class="text-[11px] font-semibold tracking-wider uppercase text-white/70">Compliance programme</p>
    <h2 class="text-2xl font-bold mt-1.5">Know the rules. Work with integrity.</h2>
    <p class="text-sm text-white/80 mt-1.5 max-w-xl">Your reference for ethics, pricing, SAP and reporting — two clicks to any policy.</p>
    <div class="flex flex-wrap gap-2 mt-4">
        <a href="{{ route('compliance.report') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white text-xs font-semibold cp-accent hover:bg-white/90"><i class="fas fa-flag"></i> Report a concern</a>
        <a href="{{ asset(config('web-report.contact.paper_form')) }}" download class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-white/40 text-xs font-semibold text-white hover:bg-white/10" title="Fill it in privately and hand it to the Compliance Committee, or email it"><i class="fas fa-file-arrow-down"></i> Download bribery &amp; corruption report form</a>
    </div>
    <div class="grid grid-cols-3 gap-3 mt-5 max-w-xl">
        @foreach([['Knowledge articles', $stats['articles']], ['Compliance rules', $stats['rules']], ['Sessions done', $stats['done'] . '/' . $stats['sessions']]] as [$text, $n])
            <div class="rounded-lg bg-white/15 px-4 py-3"><p class="text-xl font-bold">{{ $n }}</p><p class="text-[11px] text-white/75">{{ $text }}</p></div>
        @endforeach
    </div>
</div>

@if($alerts->isNotEmpty())
    <div><h3 class="cp-section-title">Critical compliance rules</h3><p class="text-xs text-gray-500 mt-0.5 mb-3">Non-negotiable. Zero tolerance. Always in effect.</p>
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
            @foreach($alerts as $a)
                @php $tone = \App\Models\Compliance\ComplianceOption::tone('alert_color', $a->color_type); @endphp
                <div class="rounded-lg border-l-4 p-4 flex gap-3 {{ \App\Support\Compliance\Tones::CARD[$tone] }}">
                    <span class="text-lg leading-none">{{ $a->icon }}</span>
                    <div><p class="text-xs font-semibold {{ \App\Support\Compliance\Tones::CARD_TEXT[$tone] }}">{{ $a->title }}</p><p class="text-xs text-gray-600 mt-1">{{ $a->description }}</p></div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if($categories->isNotEmpty())
    <div><h3 class="cp-section-title mb-3">Browse by category</h3>
        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach($categories as $c)
                <a href="{{ route('compliance.knowledge', ['cat' => $c->slug]) }}" class="cp-card cp-link p-5 block">
                    <span class="w-10 h-10 rounded-lg flex items-center justify-center text-xl cp-tint">{{ $c->icon }}</span>
                    <p class="text-sm font-semibold text-gray-800 mt-3">{{ $c->name }}</p><p class="text-xs text-gray-500 mt-1">{{ $c->description }}</p>
                    <p class="text-[11px] font-medium text-gray-400 mt-3">{{ $c->articles_count }} article{{ $c->articles_count === 1 ? '' : 's' }}</p>
                </a>
            @endforeach
        </div>
    </div>
@endif

@if($featured->isNotEmpty())
    <div><div class="flex justify-between items-center mb-3"><h3 class="cp-section-title">Featured articles</h3><a href="{{ route('compliance.knowledge') }}" class="text-xs font-semibold cp-accent hover:underline">View all →</a></div>
        <div class="space-y-3">@foreach($featured as $a)@include('legal.compliance.my.components.article-row', ['a' => $a])@endforeach</div></div>
@endif

@if($upcoming->isNotEmpty())
    <div><div class="flex justify-between items-center mb-3"><h3 class="cp-section-title">Upcoming training</h3><a href="{{ route('compliance.training') }}" class="text-xs font-semibold cp-accent hover:underline">Full curriculum →</a></div>
        <div class="space-y-3">@foreach($upcoming as $s)@include('legal.compliance.my.components.session-row', ['s' => $s])@endforeach</div></div>
@endif
@include('legal.compliance.my.components.session-modal')
@endsection
