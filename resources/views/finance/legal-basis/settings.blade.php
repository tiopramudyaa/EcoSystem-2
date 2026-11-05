@extends('dashboard')
@section('title', 'Legal Basis — Settings')
@section('page-title', 'Legal Basis')
@section('page-subtitle', 'How each module uses the legal-basis register: show information, run in Draft mode, or enforce review.')

@php
    $canEdit = $canDo('finance.legal-basis.settings', 'edit');
@endphp

@section('content')
<div class="w-full space-y-6 px-1 lg:px-2">
    @include('partials.accent-remap')
    @include('legal.components.tabs')
    @include('hr-general.recruitment.components.form-errors')
    @if(session('success'))<div class="text-xs bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3"><i class="fas fa-circle-check mr-1"></i> {{ session('success') }}</div>@endif

    {{-- Where Payroll reads its PPh 21 / BPJS regulation rates (one source of truth) --}}
    @php $viaLegal = $ratesSource === 'legal'; @endphp
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="px-5 py-3.5 border-b border-gray-100 flex items-start gap-3">
            <span class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0"><i class="fas fa-scale-balanced"></i></span>
            <div class="flex-1">
                <h3 class="text-sm font-bold text-gray-800">Where Payroll reads its rates</h3>
                <p class="text-[11px] text-gray-500 mt-0.5">One place for the regulation numbers: PPh 21 (progressive rates, occupational cost, PTKP, TER) and BPJS (rates and wage ceilings). Company choices such as the JKK class, which tax year is active and the Payroll on/off switches stay in Payroll Settings.</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold shrink-0 {{ $viaLegal ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $viaLegal ? 'Using Legal Basis' : 'Using Payroll Settings' }}</span>
        </div>
        <form method="POST" action="{{ route('finance.legal-basis.rates-source') }}" class="px-5 py-4 space-y-4">
            @csrf
            <input type="hidden" name="area" value="rates">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="flex items-start gap-3 rounded-lg border {{ !$viaLegal ? 'border-indigo-300 bg-indigo-50/40' : 'border-gray-200' }} p-3 cursor-pointer">
                    <input type="radio" name="source" value="settings" class="mt-1" @checked(!$viaLegal) @disabled(!$canEdit)>
                    <span><span class="block text-sm font-semibold text-gray-800">Payroll Settings <span class="text-[10px] font-bold text-gray-500">(current behaviour)</span></span>
                        <span class="block text-xs text-gray-500 mt-0.5">Rates are typed in Finance › Payroll › Settings (BPJS, PPh 21). Legal Basis is a reference copy.</span></span>
                </label>
                <label class="flex items-start gap-3 rounded-lg border {{ $viaLegal ? 'border-indigo-300 bg-indigo-50/40' : 'border-gray-200' }} p-3 cursor-pointer">
                    <input type="radio" name="source" value="legal" class="mt-1" @checked($viaLegal) @disabled(!$canEdit)>
                    <span><span class="block text-sm font-semibold text-gray-800">Legal Basis <span class="text-[10px] font-bold text-indigo-600">(single source)</span></span>
                        <span class="block text-xs text-gray-500 mt-0.5">Type the regulation numbers once, in the Register. Payroll reads them from there, by effective date. The rate fields in Payroll Settings are then managed here.</span></span>
                </label>
            </div>

            @if($ratesMissing)
                <div class="text-xs bg-amber-50 border border-amber-200 text-amber-900 rounded-lg px-4 py-3">
                    <p class="font-semibold mb-1"><i class="fas fa-triangle-exclamation mr-1"></i> Not ready: these rules have no values Payroll can read yet</p>
                    <ul class="list-disc ml-5 space-y-0.5">@foreach($ratesMissing as $k => $why)<li><code class="text-[11px]">{{ $k }}</code> {{ $why }}</li>@endforeach</ul>
                    <p class="mt-1.5">Open the <a href="{{ route('finance.legal-basis.index') }}" class="underline font-semibold">Register</a> and fill in their Calculation values.</p>
                </div>
            @else
                <div class="text-xs bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3"><i class="fas fa-circle-check mr-1"></i> Every rule Payroll needs has values in the Register.</div>
            @endif

            @if(!$ratesMissing && $ratesDiffs)
                <div class="text-xs bg-amber-50 border border-amber-200 text-amber-900 rounded-lg px-4 py-3">
                    <p class="font-semibold mb-1"><i class="fas fa-not-equal mr-1"></i> {{ count($ratesDiffs) }} difference{{ count($ratesDiffs) === 1 ? '' : 's' }} between Payroll Settings and Legal Basis. Switching would change payroll results.</p>
                    <ul class="list-disc ml-5 space-y-0.5 max-h-40 overflow-y-auto">@foreach($ratesDiffs as $d)<li>{{ $d }}</li>@endforeach</ul>
                    @if(!$viaLegal && $canEdit)
                        <label class="flex items-start gap-2 mt-2 font-semibold"><input type="checkbox" name="confirm_differences" value="1" class="mt-0.5 rounded border-gray-300"> I checked the differences and want Payroll to use the Legal Basis numbers.</label>
                    @endif
                </div>
            @elseif(!$ratesMissing)
                <div class="text-xs bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3"><i class="fas fa-equals mr-1"></i> Payroll Settings and Legal Basis hold the same numbers, so switching does not change any payroll result.</div>
            @endif

            @if($canEdit)<div class="flex justify-end"><button class="px-4 py-2 primary-gradient text-white text-xs font-semibold rounded-lg hover:opacity-90 shadow-sm">Save source</button></div>@endif
        </form>
    </div>

    {{-- Overtime multipliers: its own switch, because a company may not use overtime pay yet --}}
    @php $otLegal = $overtimeSource === 'legal'; @endphp
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="px-5 py-3.5 border-b border-gray-100 flex items-start gap-3">
            <span class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0"><i class="fas fa-clock"></i></span>
            <div class="flex-1">
                <h3 class="text-sm font-bold text-gray-800">Overtime multipliers</h3>
                <p class="text-[11px] text-gray-500 mt-0.5">How much each overtime hour pays (for example 1.5× the first hour on a working day) and the divisor that turns a monthly wage into an hourly wage (173). Switch this on only when your company has decided its overtime rules. Whether overtime is paid at all is still decided in Payroll Settings.</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold shrink-0 {{ $otLegal ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $otLegal ? 'On: reads Legal Basis' : 'Off: built-in standard' }}</span>
        </div>
        <form method="POST" action="{{ route('finance.legal-basis.rates-source') }}" class="px-5 py-4 space-y-4">
            @csrf
            <input type="hidden" name="area" value="overtime">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="flex items-start gap-3 rounded-lg border {{ !$otLegal ? 'border-indigo-300 bg-indigo-50/40' : 'border-gray-200' }} p-3 cursor-pointer">
                    <input type="radio" name="source" value="settings" class="mt-1" @checked(!$otLegal) @disabled(!$canEdit)>
                    <span><span class="block text-sm font-semibold text-gray-800">Off — built-in standard <span class="text-[10px] font-bold text-gray-500">(current behaviour)</span></span>
                        <span class="block text-xs text-gray-500 mt-0.5">The multipliers of PP 35/2021 written into the system: workday 1.5× then 2×, rest day or holiday 2× / 3× / 4×, divisor 173.</span></span>
                </label>
                <label class="flex items-start gap-3 rounded-lg border {{ $otLegal ? 'border-indigo-300 bg-indigo-50/40' : 'border-gray-200' }} p-3 cursor-pointer">
                    <input type="radio" name="source" value="legal" class="mt-1" @checked($otLegal) @disabled(!$canEdit)>
                    <span><span class="block text-sm font-semibold text-gray-800">On — read from Legal Basis <span class="text-[10px] font-bold text-indigo-600">(editable)</span></span>
                        <span class="block text-xs text-gray-500 mt-0.5">Payroll uses the numbers in the Register rule <code class="text-[10px]">overtime.multipliers</code>, so a change of rules needs no developer.</span></span>
                </label>
            </div>

            @if($overtimeMissing)
                <div class="text-xs bg-amber-50 border border-amber-200 text-amber-900 rounded-lg px-4 py-3">
                    <p class="font-semibold mb-1"><i class="fas fa-triangle-exclamation mr-1"></i> Not ready: the overtime rule has no complete values yet</p>
                    <ul class="list-disc ml-5 space-y-0.5">@foreach($overtimeMissing as $k => $why)<li><code class="text-[11px]">{{ $k }}</code> {{ $why }}</li>@endforeach</ul>
                    <p class="mt-1.5">Open the <a href="{{ route('finance.legal-basis.index') }}" class="underline font-semibold">Register</a>, edit <code class="text-[11px]">overtime.multipliers</code> and fill in its Calculation values.</p>
                </div>
            @else
                <div class="text-xs bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3"><i class="fas fa-circle-check mr-1"></i> The overtime rule has complete values in the Register.</div>
            @endif

            @if(!$overtimeMissing && $overtimeDiffs)
                <div class="text-xs bg-amber-50 border border-amber-200 text-amber-900 rounded-lg px-4 py-3">
                    <p class="font-semibold mb-1"><i class="fas fa-not-equal mr-1"></i> {{ count($overtimeDiffs) }} difference{{ count($overtimeDiffs) === 1 ? '' : 's' }} from the built-in standard. Switching on would change overtime pay.</p>
                    <ul class="list-disc ml-5 space-y-0.5">@foreach($overtimeDiffs as $d)<li>{{ $d }}</li>@endforeach</ul>
                    @if(!$otLegal && $canEdit)
                        <label class="flex items-start gap-2 mt-2 font-semibold"><input type="checkbox" name="confirm_differences" value="1" class="mt-0.5 rounded border-gray-300"> I checked the differences and want overtime to use the Legal Basis numbers.</label>
                    @endif
                </div>
            @elseif(!$overtimeMissing)
                <div class="text-xs bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3"><i class="fas fa-equals mr-1"></i> The Register holds the same multipliers as the built-in standard, so switching does not change any overtime pay.</div>
            @endif

            @if($canEdit)<div class="flex justify-end"><button class="px-4 py-2 primary-gradient text-white text-xs font-semibold rounded-lg hover:opacity-90 shadow-sm">Save overtime source</button></div>@endif
        </form>
    </div>

    {{-- Saklar penerapan per modul --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="px-5 py-3.5 border-b border-gray-100 flex items-start gap-3">
            <span class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0"><i class="fas fa-sliders"></i></span>
            <div><h3 class="text-sm font-bold text-gray-800">Where the legal basis applies</h3>
                <p class="text-[11px] text-gray-500 mt-0.5">Switch how each module uses this register. <strong>Show panel</strong> and <strong>Warn</strong> are informational. <strong>Mode</strong>: <em>Draft mode</em> lets the module run now while its rules are not reviewed — every result is flagged <strong>DRAFT</strong>; <em>Enforced</em> makes the module refuse to run (Payroll: switch on / calculate a period; Offboarding: the settlement calculator) while any of its required rules is not marked Reviewed. Leave Enforce off to keep a module behaving exactly as before.</p></div>
        </div>
        <form method="POST" action="{{ route('finance.legal-basis.switches') }}">
            @csrf
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-start gap-3 bg-gray-50/60">
                <input type="hidden" name="legal_display" value="0">
                <label class="flex items-start gap-3 text-sm text-gray-700"><input type="checkbox" name="legal_display" value="1" class="w-4 h-4 mt-0.5 rounded border-gray-300" {{ app(\App\Services\Legal\LegalBasisService::class)->displayEnabled() ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                    <span><span class="font-semibold text-gray-900">Show legal basis information across the app</span>
                        <span class="block text-xs text-gray-500 mt-0.5">When off, the “Legal basis” panels, the amber “not reviewed” warnings and the DRAFT labels are hidden everywhere. The register itself stays here, and a module set to <em>Enforced</em> still refuses to run until its rules are Reviewed.</span></span></label>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500"><tr><th class="px-4 py-3">Module</th><th class="px-4 py-3">Affects</th><th class="px-4 py-3 text-center">Show panel</th><th class="px-4 py-3 text-center">Warn</th><th class="px-4 py-3">Mode</th><th class="px-4 py-3">Rules not reviewed</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @php $affects = ['pph21' => 'Payroll → PPh 21 settings, switching Payroll on, calculating a period', 'bpjs' => 'Payroll → BPJS settings, switching Payroll on, calculating a period', 'overtime' => 'Payroll → overtime rates, switching Payroll on, calculating a period', 'contract' => 'Contract (PKWT term) — information only', 'offboarding' => 'Offboarding → Final Settlement calculator']; @endphp
                        @foreach($switches as $m => $sw)
                            <tr class="align-top">
                                <td class="px-4 py-3 font-semibold text-gray-800 whitespace-nowrap">{{ $modules[$m] ?? $m }}</td>
                                <td class="px-4 py-3 text-[11px] text-gray-500 max-w-xs">{{ $affects[$m] ?? '' }}</td>
                                @foreach(['show_panel', 'warn_banner'] as $f)
                                    <td class="px-4 py-3 text-center"><input type="checkbox" name="sw[{{ $m }}][{{ $f }}]" value="1" class="w-4 h-4 rounded border-gray-300" {{ $sw->$f ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}></td>
                                @endforeach
                                <td class="px-4 py-3">
                                    <select name="sw[{{ $m }}][enforce]" {{ $canEdit && $m !== 'contract' ? '' : 'disabled' }} class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs bg-white" @if($m === 'contract') title="Contract has no feature to enforce yet" @endif>
                                        <option value="" @selected(!$sw->enforce)>Draft mode — runs now, flagged DRAFT</option>
                                        <option value="1" @selected($sw->enforce)>Enforced — blocked until Reviewed</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3 text-[11px] {{ count($pending[$m] ?? []) ? 'text-amber-700' : 'text-green-700' }}">
                                    {{ count($pending[$m] ?? []) ? implode(', ', $pending[$m]) : 'All reviewed' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($canEdit)<div class="px-5 py-3 border-t border-gray-100 flex justify-end"><button class="px-4 py-2 primary-gradient text-white text-xs font-semibold rounded-lg hover:opacity-90 shadow-sm">Save switches</button></div>@endif
        </form>
    </div>

    <p class="text-xs text-gray-500">To accept the review of a module’s rules, open the <a href="{{ route('finance.legal-basis.index') }}" class="underline font-semibold">Register</a> tab and use “Accept review (all)” on that module (needs the Register “Edit” permission).</p>
</div>
@endsection
