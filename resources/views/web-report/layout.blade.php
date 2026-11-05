<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', __('web-report.page_title')) — Eclectic Consulting</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0 }
        /* Same reds as the login page, which links here */
        :root { --brand: #A00000; --brand-dk: #800000; --brand-lt: #FBEDED; --g50: #F7F5F3; --g100: #EFEDEA; --g200: #E2DFDB; --g300: #CBD5E1; --g400: #94A3B8; --g500: #64748B; --g600: #475569; --g800: #1E293B; --g900: #0F172A }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #fff; color: var(--g800); font-size: 14px; line-height: 1.6 }

        /* ---- half / half ---- */
        .split { display: grid; grid-template-columns: 1fr 1fr; min-height: 100vh }

        .side { position: sticky; top: 0; height: 100vh; overflow: hidden; color: #fff; display: flex; flex-direction: column; justify-content: space-between; padding: 40px 56px;
                background: linear-gradient(155deg, #1A0000 0%, #3D0000 18%, #6B0000 42%, #8B0000 66%, #A00000 100%) }
        .side::before, .side::after { content: ''; position: absolute; border-radius: 50%; border: 1px solid rgba(255,255,255,.08); pointer-events: none }
        .side::before { width: 520px; height: 520px; top: -170px; right: -170px; box-shadow: 0 0 0 70px rgba(255,255,255,.025) }
        .side::after { width: 360px; height: 360px; bottom: -140px; left: -120px }
        .side > * { position: relative; z-index: 1 }
        .side-logo { height: 34px; width: auto; align-self: flex-start }
        .side-body { max-width: 520px }
        .side-foot { font-size: 11px; color: rgba(255,255,255,.45) }

        .eyebrow { display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: rgba(255,255,255,.8); background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18); border-radius: 999px; padding: 4px 14px; margin-bottom: 20px }
        .side h1 { font-weight: 800; font-size: clamp(28px, 3.2vw, 42px); line-height: 1.15; letter-spacing: -.03em; margin-bottom: 14px }
        .side h1 em { font-style: normal; display: block; color: #FFB4B4 }
        .lead { font-size: 14.5px; color: rgba(255,255,255,.72); margin-bottom: 28px }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 26px }
        .stats div { background: rgba(255,255,255,.09); border: 1px solid rgba(255,255,255,.14); border-radius: 12px; padding: 14px 10px; text-align: center }
        .stats b { display: block; font-size: 24px; font-weight: 800; line-height: 1.1 }
        .stats span { font-size: 10px; letter-spacing: .06em; text-transform: uppercase; color: rgba(255,255,255,.6) }
        .perks { display: grid; gap: 10px }
        .perk { display: flex; gap: 12px; align-items: flex-start; padding: 12px 14px; border-radius: 12px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1) }
        .perk svg { flex: none; width: 34px; height: 34px; padding: 8px; border-radius: 9px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.16) }
        .perk strong { display: block; font-size: 13px; font-weight: 700 }
        .perk span { display: block; font-size: 12px; color: rgba(255,255,255,.62); line-height: 1.5 }

        .pane { display: flex; flex-direction: column; min-height: 100vh; background: var(--g50) }
        .pane-top { display: flex; justify-content: flex-end; gap: 8px; padding: 22px 40px }
        .pane-top a { color: var(--g600); text-decoration: none; font-size: 12px; font-weight: 600; background: #fff; border: 1px solid var(--g200); border-radius: 999px; padding: 6px 16px; transition: border-color .15s, color .15s }
        .pane-top a:hover { border-color: var(--brand); color: var(--brand) }
        .pane-body { flex: 1; display: flex; align-items: flex-start; justify-content: center; padding: 8px 32px 36px }
        .pane-in { width: 100%; max-width: 800px }

        /* ---- form pieces ---- */
        .panel-title h2 { font-size: 24px; font-weight: 800; letter-spacing: -.02em; color: var(--g900) }
        .panel-title p { font-size: 13px; color: var(--g500); margin-top: 2px }
        .card { background: #fff; border: 1px solid var(--g200); border-radius: 16px; box-shadow: 0 2px 4px rgba(0,0,0,.03), 0 12px 32px rgba(0,0,0,.06); padding: 28px; margin-top: 20px }
        .fsec { margin-bottom: 22px }
        .fsec-lbl { display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--g600); margin-bottom: 12px }
        .fsec-lbl i { font-style: normal; display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 50%; background: var(--brand-lt); color: var(--brand); font-size: 11px; letter-spacing: 0 }
        .fsec-lbl em { font-style: normal; font-weight: 500; text-transform: none; letter-spacing: 0; color: var(--g400) }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px }
        .fg { margin-bottom: 12px }
        .fg label { display: block; font-size: 12px; font-weight: 600; color: var(--g600); margin-bottom: 5px }
        .fg label small { font-weight: 400; color: var(--g400); font-size: 11px }
        .fg input[type=text], .fg textarea { width: 100%; background: #fff; border: 1.5px solid var(--g200); border-radius: 10px; padding: 11px 14px; font: inherit; font-size: 13.5px; color: var(--g900); outline: none; transition: border-color .15s, box-shadow .15s }
        .fg input::placeholder, .fg textarea::placeholder { color: var(--g400) }
        .fg input[type=text]:focus, .fg textarea:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(160,0,0,.1) }
        .fg textarea { min-height: 130px; resize: vertical }
        .fg input[type=file] { width: 100%; font: inherit; font-size: 12.5px; color: var(--g500); border: 1.5px dashed var(--g300); border-radius: 10px; padding: 10px; background: var(--g50) }
        .fg input[type=file]::file-selector-button { margin-right: 12px; border: 0; border-radius: 8px; padding: 8px 14px; background: var(--brand); color: #fff; font: 600 12px 'Plus Jakarta Sans', sans-serif; cursor: pointer }
        .fg input[type=file]::file-selector-button:hover { background: var(--brand-dk) }
        .hint { font-size: 11px; color: var(--g400); margin-top: 4px }
        .req { color: var(--brand) }
        .btn { width: 100%; height: 48px; background: var(--brand); color: #fff; border: 0; border-radius: 10px; font: 700 14px 'Plus Jakarta Sans', sans-serif; cursor: pointer; box-shadow: 0 4px 14px rgba(160,0,0,.3); transition: background .15s, transform .1s }
        .btn:hover { background: var(--brand-dk) }
        .btn:active { transform: scale(.99) }
        .alert { border-radius: 10px; padding: 12px 14px; font-size: 12.5px; margin-bottom: 18px; background: var(--brand-lt); border: 1px solid rgba(160,0,0,.2); color: var(--brand-dk) }
        .alert ul { padding-left: 16px }
        .hp { position: absolute; left: -9999px; height: 0; overflow: hidden }
        .link { color: var(--brand); text-decoration: none; font-weight: 600; font-size: 12.5px }
        .link:hover { text-decoration: underline }
        .privacy { margin-top: 14px; padding: 11px 14px; background: var(--g50); border: 1px solid var(--g100); border-radius: 10px; font-size: 11.5px; color: var(--g500) }
        .captcha { display: flex; gap: 10px; align-items: center }
        .captcha b { background: var(--g100); border: 1px solid var(--g200); border-radius: 10px; padding: 10px 16px; font-size: 18px; font-weight: 800; letter-spacing: 2px; white-space: nowrap; user-select: none }
        .code { font: 800 26px/1 'Plus Jakarta Sans', sans-serif; letter-spacing: 4px; background: var(--brand-lt); color: var(--brand-dk); border: 1px dashed var(--brand); border-radius: 12px; padding: 20px; text-align: center; margin: 18px 0; user-select: all }
        .done-ic { width: 52px; height: 52px; border-radius: 50%; background: #DCFCE7; color: #16A34A; display: flex; align-items: center; justify-content: center; margin-bottom: 14px }

        /* ---- small screens: the red half becomes a compact banner on top ---- */
        @media (max-width: 960px) {
            .split { grid-template-columns: 1fr }
            .side { position: relative; height: auto; padding: 28px 24px; gap: 22px }
            .side-body .stats, .side-body .perks { display: none }
            .side-body .lead { margin-bottom: 0 }
            .side-foot { display: none }
            .pane { min-height: 0 }
            .pane-top { padding: 16px 20px 0 }
            .pane-body { padding: 8px 20px 40px }
            .card { padding: 20px }
            .row2 { grid-template-columns: 1fr }
        }
    </style>
</head>
<body>
    <div class="split">
        <aside class="side">
            <img class="side-logo" src="{{ asset('images/eclectic_logo_nobg.png') }}" alt="Eclectic Consulting">
            <div class="side-body">@yield('side')</div>
            <p class="side-foot">&copy; {{ date('Y') }} Eclectic Consulting · {{ __('web-report.footer') }}</p>
        </aside>

        <section class="pane">
            <div class="pane-top">
                <a href="mailto:{{ config('web-report.contact.email') }}">{{ config('web-report.contact.email') }}</a>
                <a href="tel:{{ preg_replace('/[^\d+]/', '', config('web-report.contact.phone')) }}">{{ config('web-report.contact.phone') }}</a>
                <a href="{{ route('login') }}">← {{ __('web-report.back_to_login') }}</a>
                <a href="?lang={{ __('web-report.switch_to') }}">{{ __('web-report.switch_label') }}</a>
            </div>
            <div class="pane-body"><div class="pane-in">@yield('main')</div></div>
        </section>
    </div>
</body>
</html>
