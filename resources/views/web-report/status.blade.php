@extends('web-report.layout')

@section('title', __('web-report.status_title'))

@section('side')
    <span class="eyebrow">{{ __('web-report.eyebrow') }}</span>
    <h1>{{ __('web-report.status_title') }} <em>{{ __('web-report.status_tagline') }}</em></h1>
    <p class="lead">{{ __('web-report.status_sub') }}</p>
@endsection

@section('main')
    <div class="panel-title"><h2>{{ __('web-report.status_title') }}</h2><p>{{ __('web-report.status_sub') }}</p></div>

    <div class="card">
        @if($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('web-report.status') }}" autocomplete="off">@csrf
            <div class="fg"><label for="tracking_code">{{ __('web-report.status_code') }}</label>
                <input type="text" id="tracking_code" name="tracking_code" required maxlength="16" value="{{ old('tracking_code') }}" placeholder="{{ config('web-report.code_prefix') }}-XXXX-XXXX · {{ config('compliance.report.code_prefix') }}-XXXX-XXXX" style="text-transform:uppercase"></div>
            <button class="btn" type="submit">{{ __('web-report.status_check') }}</button>
        </form>

        @if($report)
            <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--g100);font-size:13px">
                <p class="hint">{{ $report->tracking_code }}</p>
                @php
                    // The public form (WR-…) has translated statuses; a compliance report (CL-…) shows the status its admins defined in Compliance Management → Settings.
                    $statusKey = 'web-report.status_' . $report->status;
                    $statusText = $report->channel === \App\Models\WebReport::CHANNEL_WEB && \Illuminate\Support\Facades\Lang::has($statusKey) ? __($statusKey) : $report->statusLabel();
                @endphp
                <p style="font:800 22px 'Plus Jakarta Sans',sans-serif;color:var(--brand);margin:2px 0 14px">{{ $statusText }}</p>
                <p><span class="hint">{{ __('web-report.status_received') }}</span><br>{{ $report->created_at->format('d M Y') }}</p>
                @if(!$report->isFinal() && $report->due_at)<p style="margin-top:10px"><span class="hint">{{ __('web-report.status_due') }}</span><br>{{ $report->due_at->format('d M Y') }}</p>@endif
                <p style="margin-top:10px"><span class="hint">{{ __('web-report.status_message') }}</span><br>{{ $report->public_note ?: __('web-report.status_none') }}</p>
            </div>
        @endif
    </div>

    <p style="margin-top:18px"><a class="link" href="{{ route('web-report.form') }}">← {{ __('web-report.status_back') }}</a></p>
@endsection
