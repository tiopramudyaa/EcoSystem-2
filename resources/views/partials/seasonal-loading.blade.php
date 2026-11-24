{{--
    Layar loading bertema musiman setelah login sukses. Adegan 3D (three.js) dimainkan
    oleh /js/seasonal/stage.js ke <canvas>; bila WebGL tidak ada, gerak dikurangi, atau
    modul gagal dimuat, tampil fallback statis yang tenang (gradient, ikon vektor, teks).

    Overlay dirender untuk SEMUA tema katalog (selain none) lewat satu blob data
    window.__seasonalThemes, karena tema efektif pengguna baru diketahui dari respons
    login (seasonal_theme), bukan dari tema global halaman. Tidak ada query DB: data
    berasal dari SeasonalThemes::catalog() dan terjemahan.

    Params:
      $loadingId  string  id elemen. Default 'seasonalLoadingOverlay'.

    API JS: window.SeasonalLoading.start(key, go) mengembalikan true bila overlay
    dipakai. `go` (fungsi redirect) dijamin dipanggil sekali; kegagalan apa pun tidak
    pernah menahan pengguna. Audio hanya bila admin memasang musik ($seasonalTheme['soundUrl']).
--}}
@php
    $slId = $loadingId ?? 'seasonalLoadingOverlay';
    $slGlobal = $seasonalTheme ?? ['key' => 'none', 'soundUrl' => null, 'soundVolume' => null];
    // Kunci pemanasan aset: tema yang kemungkinan dipakai loading screen ($warmKey dari halaman login), bukan selalu tema halaman.
    $slGlobalKey = $warmKey ?? ($slGlobal['key'] ?? 'none');

    $slThemes = [];
    foreach (\App\Support\SeasonalThemes::catalog() as $slKey => $slMeta) {
        if ($slKey === \App\Support\SeasonalThemes::NONE || empty($slMeta['palette']) || empty($slMeta['scene'])) {
            continue;
        }
        $slThemes[$slKey] = [
            'scene' => $slMeta['scene'],
            'palette' => $slMeta['palette'],
            'title' => isset($slMeta['loading']['title_key']) ? __($slMeta['loading']['title_key']) : '',
            'subtitle' => isset($slMeta['loading']['subtitle_key']) ? __($slMeta['loading']['subtitle_key']) : '',
        ];
    }

    // Satu versi untuk SEMUA modul 3D = mtime terbaru di public/js/seasonal. Import map di bawah
    // memetakan tiap modul (termasuk impor relatif antar-modul dan three.js) ke URL berversi,
    // sehingga deploy baru tidak pernah memuat campuran modul lama dan baru dari cache browser.
    $slModuleFiles = array_merge(
        glob(public_path('js/seasonal/*.js')) ?: [],
        glob(public_path('js/seasonal/scenes/*.js')) ?: [],
        glob(public_path('vendor/three/*.js')) ?: []
    );
    $slVersion = $slModuleFiles ? max(array_map('filemtime', $slModuleFiles)) : 0;
    $slImportMap = [];
    foreach ($slModuleFiles as $slFile) {
        $slPath = '/' . str_replace('\\', '/', ltrim(substr($slFile, strlen(public_path())), '\\/'));
        $slImportMap[$slPath] = $slPath . '?v=' . $slVersion;
    }
    $slStageFile = public_path('js/seasonal/stage.js');
    $slStageUrl = is_file($slStageFile) ? '/js/seasonal/stage.js?v=' . $slVersion : null;
    $slSound = $slGlobal['soundUrl'] ?? null;
@endphp
@if(!empty($slThemes))
@if(view()->exists('partials.seasonal-icons'))
    @include('partials.seasonal-icons')
@endif
<div id="{{ $slId }}" class="seasonal-loading" role="status" aria-live="polite">
    <canvas class="seasonal-loading__canvas" aria-hidden="true"></canvas>
    <div class="seasonal-loading__content">
        {{-- Logo EC: animasi masuk sekali (CSS transform dan opacity), tanpa perulangan. --}}
        <span class="seasonal-loading__logo"><img src="{{ asset('images/eclectic_logo_nobg.png') }}" alt="Eclectic Consulting" width="160" height="55" decoding="async"></span>
        <svg class="seasonal-loading__icon" aria-hidden="true" focusable="false"><use href=""></use></svg>
        <p class="seasonal-loading__title"></p>
        <p class="seasonal-loading__subtitle"></p>
        <p class="seasonal-loading__status">{{ __('ui.seasonal.loading.preparing') }}</p>
    </div>
    <div class="seasonal-loading__bar" aria-hidden="true"><span></span></div>
    @if($slSound)
        <audio id="seasonalJingle" preload="auto" src="{{ $slSound }}" data-volume="{{ $slGlobal['soundVolume'] ?? 0.5 }}"></audio>
    @endif
</div>

<style>
    /* Disembunyikan secara default; skrip menambah .is-on lalu .is-visible (fade 300ms).
       Variabel warna diset JS pada elemen ini dari tema terpilih saat tampil. */
    .seasonal-loading {
        display: none;
        opacity: 0;
        transition: opacity .3s ease;
        position: fixed;
        inset: 0;
        z-index: 99999;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        text-align: center;
        overflow: hidden;
        background: linear-gradient(180deg, var(--st-login-from, #1a0000) 0%, var(--st-loading-bottom, #6b0000) 100%);
        font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }
    .seasonal-loading.is-on { display: flex; }
    .seasonal-loading.is-visible { opacity: 1; }
    .seasonal-loading__canvas { position: absolute; inset: 0; width: 100%; height: 100%; display: none; }
    .seasonal-loading.is-3d .seasonal-loading__canvas { display: block; }
    .seasonal-loading__content {
        position: relative; z-index: 2; padding: 0 1.5rem;
        display: flex; flex-direction: column; align-items: center;
    }
    /* Teks 3D di pita bawah dengan scrim gelap: tidak menimpa tokoh utama adegan dan tetap terbaca. */
    .seasonal-loading.is-3d .seasonal-loading__content {
        position: absolute; left: 0; right: 0; bottom: 0; margin: 0; z-index: 3;
        padding: 4.5rem 1.5rem calc(1.75rem + env(safe-area-inset-bottom, 0px));
        background: linear-gradient(to top, rgba(0,0,0,.74) 0%, rgba(0,0,0,.46) 55%, rgba(0,0,0,0) 100%);
        text-shadow: 0 2px 12px rgba(0,0,0,.6);
    }
    .seasonal-loading.is-3d .seasonal-loading__title { margin-top: 0; font-size: 1.625rem; font-weight: 800; line-height: 1.2; }
    .seasonal-loading.is-3d .seasonal-loading__subtitle { font-size: 1rem; color: rgba(255,255,255,.92); }
    .seasonal-loading__icon { width: 3.5rem; height: 3.5rem; color: var(--st-accent, #f2c14e); fill: currentColor; }
    .seasonal-loading.is-3d .seasonal-loading__icon { display: none; }
    .seasonal-loading__title { margin: 1rem 0 0; font-size: 1.0625rem; font-weight: 700; color: #fff; letter-spacing: .02em; }
    .seasonal-loading__subtitle { margin: .35rem 0 0; font-size: .8125rem; color: rgba(255,255,255,.82); }
    .seasonal-loading__subtitle:empty { display: none; }
    .seasonal-loading__status { margin: .9rem 0 0; font-size: .6875rem; letter-spacing: .08em; text-transform: uppercase; color: rgba(255,255,255,.62); }

    /* Logo EC: masuk sekali (opacity dan transform, jalur compositor). Kilau tunggal lewat
       background-position pada area logo saja (mask), dimulai setelah adegan berjalan. Tanpa loop. */
    .seasonal-loading__logo { position: relative; display: block; margin: 0 0 1rem; line-height: 0; }
    .seasonal-loading__logo img { display: block; height: 3.25rem; width: auto; filter: drop-shadow(0 2px 10px rgba(0,0,0,.5)); }
    .seasonal-loading.is-visible .seasonal-loading__logo { animation: seasonal-logo-in .7s cubic-bezier(.2,.8,.2,1) .12s both; }
    .seasonal-loading__logo::after {
        content: ''; position: absolute; inset: 0; opacity: 0; pointer-events: none;
        background: linear-gradient(100deg, transparent 35%, rgba(255,255,255,.75) 50%, transparent 65%) -150% 0 / 250% 100% no-repeat;
        -webkit-mask: url('{{ asset('images/eclectic_logo_nobg.png') }}') left center / contain no-repeat;
        mask: url('{{ asset('images/eclectic_logo_nobg.png') }}') left center / contain no-repeat;
    }
    .seasonal-loading.is-visible .seasonal-loading__logo::after { animation: seasonal-logo-shine 1.1s ease-in-out .95s 1 both; }
    @keyframes seasonal-logo-in { from { opacity: 0; transform: translateY(10px) scale(.96); } to { opacity: 1; transform: none; } }
    @keyframes seasonal-logo-shine { 0% { opacity: 1; background-position: -150% 0; } 100% { opacity: 1; background-position: 250% 0; } }

    /* Fallback statis: teks muncul perlahan dan bar progres pelan. */
    .seasonal-loading.is-static .seasonal-loading__content { animation: seasonal-loading-fade 1s ease both; }
    @keyframes seasonal-loading-fade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .seasonal-loading__bar {
        position: absolute; left: 0; right: 0; bottom: 0; height: 3px;
        background: rgba(255,255,255,.12); overflow: hidden; display: none;
    }
    .seasonal-loading.is-static .seasonal-loading__bar { display: block; }
    .seasonal-loading__bar span {
        display: block; height: 100%; width: 100%;
        background: var(--st-accent, #f2c14e);
        transform-origin: left; transform: scaleX(0);
    }
    .seasonal-loading.is-static.is-visible .seasonal-loading__bar span { animation: seasonal-loading-bar 1.8s linear forwards; }
    @keyframes seasonal-loading-bar { to { transform: scaleX(1); } }
    @media (prefers-reduced-motion: reduce) {
        .seasonal-loading { transition: none; }
        .seasonal-loading.is-static .seasonal-loading__content { animation: none; }
        .seasonal-loading.is-visible .seasonal-loading__logo { animation: none; }
        .seasonal-loading__logo::after { display: none; }
        .seasonal-loading.is-static.is-visible .seasonal-loading__bar span { animation: none; transform: scaleX(1); }
    }
</style>

@if($slStageUrl)
<script type="importmap">@json(['imports' => $slImportMap])</script>
@endif
<script>
    window.__seasonalThemes = @json($slThemes);
    (function () {
        var THEMES = window.__seasonalThemes;
        var STAGE = @json($slStageUrl);
        var GLOBAL_KEY = @json($slGlobalKey);
        var overlayId = @json($slId);
        var stagePromise = null;

        function loadStage() {
            if (!STAGE) return Promise.reject(new Error('stage missing'));
            if (!stagePromise) stagePromise = import(STAGE);
            return stagePromise;
        }

        // Pemanasan aset 3D hanya bila tema global aktif; tema none tidak mengunduh apa pun.
        // Dilewati saat Save-Data aktif atau koneksi 2g/3g agar tidak memboroskan kuota.
        var conn = navigator.connection || {};
        var slow = !!(conn.saveData || /(^|-)(2g|3g)$/.test(conn.effectiveType || ''));
        if (GLOBAL_KEY !== 'none' && THEMES[GLOBAL_KEY] && STAGE && !slow) {
            var warm = function () {
                loadStage().then(function (m) { return m.preload(THEMES[GLOBAL_KEY].scene); }).catch(function () {});
            };
            if (window.requestIdleCallback) window.requestIdleCallback(warm, { timeout: 4000 });
            else setTimeout(warm, 1500);
        }

        function reducedMotion() {
            return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
        }

        function applyTheme(el, key) {
            var t = THEMES[key], p = t.palette;
            el.style.setProperty('--st-login-from', p.login_from);
            el.style.setProperty('--st-login-to', p.login_to);
            el.style.setProperty('--st-loading-bottom', p.loading_bottom || p.login_mid || p.login_to);
            el.style.setProperty('--st-primary-dark-rgb', p.primary_dark_rgb);
            el.style.setProperty('--st-accent', p.accent);
            el.querySelector('.seasonal-loading__title').textContent = t.title;
            el.querySelector('.seasonal-loading__subtitle').textContent = t.subtitle;
            var use = el.querySelector('.seasonal-loading__icon use');
            if (use) use.setAttribute('href', '#st-icon-' + key);
        }

        function playJingle() {
            try {
                var jingle = document.getElementById('seasonalJingle');
                if (!jingle) return;
                var vol = parseFloat(jingle.dataset.volume);
                if (!isNaN(vol)) jingle.volume = Math.min(1, Math.max(0, vol));
                jingle.currentTime = 0;
                var r = jingle.play();
                if (r && r.catch) r.catch(function () {});
            } catch (e) { /* audio tidak boleh mengganggu alur utama */ }
        }

        window.SeasonalLoading = {
            has: function (key) { return Object.prototype.hasOwnProperty.call(THEMES, key); },

            // go: fungsi redirect. Dipanggil tepat sekali, apa pun yang terjadi.
            start: function (key, go) {
                var el = document.getElementById(overlayId);
                if (!el || !this.has(key)) return false;
                var called = false, timer = null;
                function finish() {
                    if (called) return;
                    called = true;
                    clearTimeout(timer);
                    go();
                }
                function at(ms) { clearTimeout(timer); timer = setTimeout(finish, ms); }
                var t0 = Date.now();
                // Batas atas mutlak: redirect tetap terjadi walau semua yang lain macet.
                at(3600);
                try {
                    applyTheme(el, key);
                    el.classList.add('is-on');
                    requestAnimationFrame(function () { el.classList.add('is-visible'); });
                } catch (e) { at(1800); return true; }
                playJingle();

                function fallback() {
                    el.classList.remove('is-3d');
                    el.classList.add('is-static');
                    at(Math.max(0, 1800 - (Date.now() - t0)));
                }
                if (reducedMotion()) { fallback(); return true; }

                loadStage().then(function (m) {
                    if (!m.supportsWebGL()) throw new Error('no webgl');
                    el.classList.add('is-3d');
                    return m.play(el.querySelector('canvas'), { scene: THEMES[key].scene, palette: THEMES[key].palette, duration: 2600 });
                }).then(function (h) {
                    var minDone = false, sceneDone = false;
                    function check() { if (minDone && sceneDone) finish(); }
                    setTimeout(function () { minDone = true; check(); }, Math.max(0, 2200 - (Date.now() - t0)));
                    Promise.resolve(h && h.done).catch(function () {}).then(function () { sceneDone = true; check(); });
                }).catch(function () { fallback(); });
                return true;
            }
        };
    })();
</script>
@endif
