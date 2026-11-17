{{-- Shown on Payroll Settings pages while Payroll reads its regulation rates from Legal Basis (one source of truth). --}}
@if(\App\Services\Legal\PayrollRatesFromLegal::useLegal())
    <div class="text-xs bg-indigo-50 border border-indigo-200 text-indigo-900 rounded-lg px-4 py-3 flex items-start gap-2">
        <i class="fas fa-scale-balanced mt-0.5"></i>
        <p><strong>{{ $what ?? 'These rates' }}</strong> are managed in <strong>Legal Basis</strong>, and Payroll reads them from there. {{ $detail ?? '' }}
            @if($canDo('finance.legal-basis'))<a href="{{ route('finance.legal-basis.index') }}" class="underline font-semibold">Open Legal Basis</a>@endif</p>
    </div>
@endif
