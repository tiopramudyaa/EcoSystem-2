@extends('web-report.layout')

@php
    // Feather-style icon paths for the four guarantees (g1…g4 of lang/*/web-report.php)
    $perkIcons = [
        1 => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        2 => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        3 => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        4 => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
    ];
@endphp

@section('side')
    <span class="eyebrow">{{ __('web-report.eyebrow') }}</span>
    <h1>{{ __('web-report.h1_a') }} <em>{{ __('web-report.h1_b') }}</em></h1>
    <p class="lead">{{ __('web-report.hero') }}</p>

    <div class="stats">
        <div><b>100%</b><span>{{ __('web-report.stat_confidential') }}</span></div>
        <div><b>{{ \App\Models\WebReport::followUpDays() }}</b><span>{{ __('web-report.stat_days') }}</span></div>
        <div><b>0</b><span>{{ __('web-report.stat_tolerance') }}</span></div>
    </div>

    <div class="perks">
        @foreach($perkIcons as $n => $paths)
            <div class="perk">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths !!}</svg>
                <div><strong>{{ __("web-report.g{$n}_title") }}</strong><span>{{ __("web-report.g{$n}_desc") }}</span></div>
            </div>
        @endforeach
    </div>
@endsection

@section('main')
    @if($code)
        <div class="card">
            <div class="done-ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></div>
            <div class="panel-title"><h2>{{ __('web-report.done_title') }}</h2></div>
            <p style="margin-top:10px;color:var(--g600)">{{ __('web-report.done_text') }}</p>
            <p class="hint" style="margin-top:16px">{{ __('web-report.done_code') }}</p>
            <div class="code">{{ $code }}</div>
            <p><a class="link" href="{{ route('web-report.status') }}">{{ __('web-report.status_link') }} →</a> &nbsp;·&nbsp; <a class="link" href="{{ route('web-report.form') }}">{{ __('web-report.done_again') }}</a></p>
        </div>
    @else
        <div class="panel-title"><h2>{{ __('web-report.form_title') }}</h2><p>{{ __('web-report.form_sub') }}</p></div>

        <div class="card">
            @if($errors->any())<div class="alert"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            <form id="webReportForm" method="POST" action="{{ route('web-report.submit') }}" enctype="multipart/form-data" autocomplete="off">@csrf
                <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                <div class="fsec">
                    <p class="fsec-lbl"><i>1</i> {{ __('web-report.sec1') }} <em>{{ __('web-report.optional') }}</em></p>
                    <div class="row2">
                        <div class="fg"><label for="reporter_name">{{ __('web-report.f_name') }} <small>{{ __('web-report.f_name_hint') }}</small></label>
                            <input type="text" id="reporter_name" name="reporter_name" maxlength="100" value="{{ old('reporter_name') }}" placeholder="{{ __('web-report.f_name_ph') }}"></div>
                        <div class="fg"><label for="reporter_contact">{{ __('web-report.f_contact') }} <small>{{ __('web-report.f_contact_hint') }}</small></label>
                            <input type="text" id="reporter_contact" name="reporter_contact" maxlength="150" value="{{ old('reporter_contact') }}" placeholder="{{ __('web-report.f_contact_ph') }}"></div>
                    </div>
                </div>

                <div class="fsec">
                    <p class="fsec-lbl"><i>2</i> {{ __('web-report.sec2') }}</p>
                    <div class="fg"><label for="reported_name">{{ __('web-report.f_reported') }} <span class="req">*</span></label>
                        <input type="text" id="reported_name" name="reported_name" required maxlength="150" value="{{ old('reported_name') }}" placeholder="{{ __('web-report.f_reported_ph') }}"></div>
                    <div class="fg"><label for="chronology">{{ __('web-report.f_chrono') }} <span class="req">*</span></label>
                        <textarea id="chronology" name="chronology" required minlength="{{ config('web-report.chronology_min') }}" maxlength="{{ config('web-report.chronology_max') }}" placeholder="{{ __('web-report.f_chrono_ph') }}">{{ old('chronology') }}</textarea></div>
                </div>

                <div class="fsec">
                    <p class="fsec-lbl"><i>3</i> {{ __('web-report.sec3') }} <em>{{ __('web-report.optional') }}</em></p>
                    <div class="fg"><input type="file" name="evidence" aria-label="{{ __('web-report.f_file') }}"><p class="hint">{{ __('web-report.f_file_hint') }}</p></div>
                </div>

                <div class="fsec">
                    <p class="fsec-lbl"><i>4</i> {{ __('web-report.sec4') }}</p>
                    <div class="fg"><label for="captcha">{{ __('web-report.f_captcha') }} <span class="req">*</span></label>
                        <div class="captcha"><b>{{ $captcha }} =</b><input type="text" id="captcha" name="captcha" required inputmode="numeric" maxlength="3" placeholder="{{ __('web-report.f_captcha_ph') }}"></div></div>
                </div>

                <button class="btn" type="submit">{{ __('web-report.submit') }}</button>
                <p class="privacy">{{ __('web-report.privacy') }}</p>
                @include('web-report.components.paper-form')
            </form>
        </div>
        @include('web-report.components.confirm-submit', ['form' => 'webReportForm'])

        <p style="margin-top:18px"><a class="link" href="{{ route('web-report.status') }}">{{ __('web-report.status_link') }} →</a></p>
    @endif
@endsection
