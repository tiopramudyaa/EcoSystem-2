@extends('legal.compliance.my.layout')
@section('my-content')
@php
    $field = 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300 bg-white';
    $lbl = 'block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1';
@endphp
@if(session('success'))<div class="text-xs bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3"><i class="fas fa-circle-check mr-1"></i> {{ session('success') }}</div>@endif
<div class="cp-card p-4 flex flex-wrap items-center gap-4 cp-tint">
    <span class="w-10 h-10 rounded-lg bg-white/70 flex items-center justify-center text-lg shrink-0"><i class="fas fa-file-arrow-down"></i></span>
    <div class="flex-1 min-w-[16rem]">
        <p class="text-sm font-semibold text-gray-900">Rather not type it online? Report on paper.</p>
        <p class="text-xs text-gray-600 mt-0.5">Download the bribery &amp; corruption report form, fill it in privately and hand it to the Compliance Committee or send it to {{ config('web-report.contact.email') }}. Nothing is recorded about who downloads it.</p>
    </div>
    <a href="{{ asset(config('web-report.contact.paper_form')) }}" download class="inline-flex items-center gap-2 px-4 py-2 primary-gradient text-white text-xs font-semibold rounded-lg hover:opacity-90 shrink-0"><i class="fas fa-download"></i> Download form (PDF)</a>
</div>
@if($code)
    <div class="cp-card p-5 text-center">
        <p class="cp-eyebrow">Your tracking code — shown only once</p>
        <p class="text-2xl font-extrabold tracking-widest cp-accent mt-2 select-all">{{ $code }}</p>
        <p class="text-xs text-gray-500 mt-2">You sent this report anonymously, so it is not listed below. Keep the code: it is the only way to follow it, at <a href="{{ route('web-report.status') }}" class="cp-accent underline" target="_blank" rel="noopener">the Web Report status page</a>.</p>
    </div>
@endif

<div class="lg:grid lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:gap-6 items-start space-y-6 lg:space-y-0">
    <form id="complianceReportForm" method="POST" action="{{ route('compliance.report.store') }}" enctype="multipart/form-data" class="cp-card p-5 sm:p-6 space-y-4" data-unsaved-ignore>@csrf
        <div><h3 class="text-base font-semibold text-gray-900">Report a concern</h3>
            <p class="text-xs text-gray-500 mt-1">Saw something that breaks the rules — ethics, pricing, SAP, conflicts of interest? Tell us. Only the authorised administrators can read it, and you will not be retaliated against.</p></div>
        @if($errors->any())<div class="text-xs bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3"><ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
        <div><label class="{{ $lbl }}" for="rp_about">Who or what is it about <span class="text-red-500">*</span></label>
            <input id="rp_about" name="reported_name" value="{{ old('reported_name') }}" maxlength="150" required placeholder="A person, a team, a process or a document" class="{{ $field }}"></div>
        <div><label class="{{ $lbl }}" for="rp_what">What happened <span class="text-red-500">*</span></label>
            <textarea id="rp_what" name="chronology" rows="7" required minlength="{{ config('web-report.chronology_min') }}" placeholder="When, where, what happened and who was involved…" class="{{ $field }}">{{ old('chronology') }}</textarea></div>
        <div><label class="{{ $lbl }}" for="rp_file">Supporting evidence <span class="font-normal normal-case text-gray-400">— optional, max {{ intdiv(config('web-report.evidence_max_kb'), 1024) }} MB</span></label>
            <input id="rp_file" type="file" name="evidence" class="{{ $field }} text-xs"></div>
        <label class="flex items-start gap-2 text-sm text-gray-700 cp-tint rounded-lg p-3"><input type="checkbox" name="anonymous" value="1" class="rounded border-gray-300 mt-0.5" @checked(old('anonymous'))>
            <span><span class="font-semibold">Send anonymously</span><span class="block text-xs text-gray-500">Your name is not stored with the report and it will not appear in “My reports”. You get a tracking code instead.</span></span></label>
        <button class="w-full px-4 py-2.5 primary-gradient text-white text-sm font-semibold rounded-lg hover:opacity-90"><i class="fas fa-paper-plane mr-1"></i> Send report</button>
    </form>
    @include('web-report.components.confirm-submit', ['form' => 'complianceReportForm', 'sender' => session('user.name')])

    <div class="space-y-3 min-w-0">
        @include('web-report.components.paper-form', ['class' => 'cp-card'])
        <h3 class="cp-section-title">My reports</h3>
        @forelse($mine as $r)
            <div class="cp-card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0"><p class="text-sm font-semibold text-gray-800 truncate">{{ $r->reported_name }}</p><p class="text-[11px] text-gray-400 mt-0.5">{{ $r->tracking_code }} · sent {{ $r->created_at->format('d M Y') }}</p></div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase shrink-0 {{ $r->statusTone() }}">{{ $r->statusLabel() }}</span>
                </div>
                <p class="text-xs text-gray-600 mt-2 line-clamp-2">{{ \Illuminate\Support\Str::limit($r->chronology, 160) }}</p>
                @if(!$r->isFinal() && $r->due_at)<p class="text-[11px] text-gray-400 mt-2">Follow-up expected by {{ $r->due_at->format('d M Y') }}</p>@endif
                @if($r->public_note)<div class="mt-3 rounded-lg cp-tint p-3 text-xs"><p class="cp-eyebrow mb-0.5">Message from the administrator</p>{{ $r->public_note }}</div>@endif
            </div>
        @empty
            <div class="cp-card p-8 text-center text-sm text-gray-500">You have not sent a report yet.</div>
        @endforelse
    </div>
</div>
@endsection
