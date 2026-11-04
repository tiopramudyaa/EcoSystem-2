{{-- "Prefer paper or email?": the printable report form and the other contact channels (config/web-report.php → contact).
     Used by the public Web Report form and by My Compliance → Report a Concern. $class = extra classes for the box. --}}
@php $c = config('web-report.contact'); @endphp
<div class="paper {{ $class ?? '' }}" style="margin-top:14px;padding:14px 16px;border:1px solid #E2DFDB;border-radius:12px;background:#fff;font-size:12px;color:#475569;line-height:1.6">
    <strong style="display:block;font-size:13px;color:#0f172a;margin-bottom:2px">{{ __('web-report.paper_title') }}</strong>
    <p>{{ __('web-report.paper_text') }}</p>
    <p style="margin-top:8px"><a href="{{ asset($c['paper_form']) }}" download style="color:#A00000;font-weight:700;text-decoration:none">&#8681; {{ __('web-report.paper_download') }}</a></p>
    <p style="margin-top:8px">
        <a href="mailto:{{ $c['email'] }}" style="color:inherit;font-weight:600">{{ $c['email'] }}</a> ·
        {{ __('web-report.paper_phone') }} <a href="tel:{{ preg_replace('/[^\d+]/', '', $c['phone']) }}" style="color:inherit;font-weight:600">{{ $c['phone'] }}</a>
    </p>
    <p style="margin-top:4px;color:#64748B">{{ $c['address'] }}</p>
    <p style="margin-top:6px;color:#64748B">{{ __('web-report.paper_deadline', ['days' => $c['paper_deadline_days']]) }}</p>
</div>
