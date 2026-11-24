{{--
    Pratinjau opt-in layar loading musiman (Settings > Appearance dan Control Center > Seasonal Theme).

    Tidak ada yang diunduh saat halaman dimuat: three.js dan modul adegan baru di-import()
    dinamis SETELAH tombol ditekan. Tidak ada query DB, tidak ada panggilan jaringan selain
    aset statis, dan alur login/loading asli tidak disentuh.

    Aturan: maksimal sekitar 6 detik lalu menutup sendiri; tutup dengan Esc, klik, atau tombol
    Close; hanya satu pratinjau pada satu waktu; adegan dibuang (stop RAF + lepas konteks GL)
    saat ditutup dan saat tab tersembunyi. Bila WebGL tidak ada, Save-Data, koneksi 2g/3g, atau
    prefers-reduced-motion, WebGL tidak dijalankan dan overlay hanya menampilkan poster statis.

    Params:
      $picker     string  selector wadah kartu tema (kartu terpilih: .st-card.is-selected[data-season-choice]).
      $globalKey  string  key tema global admin, dipakai untuk pilihan 'default'. Default 'none'.
--}}
@php
    $spPicker = $picker ?? '#seasonalPicker';
    $spGlobal = $globalKey ?? 'none';

    $spThemes = [];
    foreach (\App\Support\SeasonalThemes::catalog() as $spKey => $spMeta) {
        if ($spKey === \App\Support\SeasonalThemes::NONE || empty($spMeta['palette']) || empty($spMeta['scene'])) {
            continue;
        }
        $spPoster = 'images/seasonal/posters/' . $spKey . '.webp';
        $spLabel = __($spMeta['label_key']);
        $spThemes[$spKey] = [
            'scene' => $spMeta['scene'],
            'palette' => $spMeta['palette'],
            'label' => $spLabel,
            'poster' => is_file(public_path($spPoster)) ? asset($spPoster) . '?v=' . filemtime(public_path($spPoster)) : null,
            'alt' => __('ui.seasonal.preview.poster_alt', ['theme' => $spLabel]),
            'dialog' => __('ui.seasonal.preview.dialog_label', ['theme' => $spLabel]),
            'greeting' => isset($spMeta['loading']['title_key']) ? __($spMeta['loading']['title_key']) : '',
            'blessing' => isset($spMeta['loading']['subtitle_key']) ? __($spMeta['loading']['subtitle_key']) : '',
        ];
    }

    // Versi aset sama dengan partials/seasonal-loading: satu import map berversi (mtime terbaru)
    // untuk semua modul, supaya cache busting identik dengan layar loading asli.
    $spFiles = array_merge(
        glob(public_path('js/seasonal/*.js')) ?: [],
        glob(public_path('js/seasonal/scenes/*.js')) ?: [],
        glob(public_path('vendor/three/*.js')) ?: []
    );
    $spVersion = $spFiles ? max(array_map('filemtime', $spFiles)) : 0;
    $spImportMap = [];
    foreach ($spFiles as $spFile) {
        $spPath = '/' . str_replace('\\', '/', ltrim(substr($spFile, strlen(public_path())), '\\/'));
        $spImportMap[$spPath] = $spPath . '?v=' . $spVersion;
    }
    $spStage = is_file(public_path('js/seasonal/stage.js')) ? '/js/seasonal/stage.js?v=' . $spVersion : null;
@endphp
@if(!empty($spThemes))
<div class="seasonal-preview-box mt-3" data-seasonal-preview>
    <button type="button" id="seasonalPreviewBtn"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
            aria-describedby="seasonalPreviewHint">
        <i class="fas fa-play text-[10px]" aria-hidden="true"></i>
        <span>{{ __('ui.seasonal.preview.play') }}</span>
    </button>
    <p id="seasonalPreviewHint" class="mt-1.5 text-xs leading-relaxed text-gray-400" aria-live="polite">{{ __('ui.seasonal.preview.hint') }}</p>
</div>

<div id="seasonalPreviewOverlay" class="sp-overlay" role="dialog" aria-modal="true" hidden>
    <canvas class="sp-canvas" aria-hidden="true"></canvas>
    <img class="sp-poster" alt="" width="640" height="360">
    <div class="sp-bar">
        <div class="sp-text">
            <span class="sp-logo"><img src="{{ asset('images/eclectic_logo_nobg.png') }}" alt="" width="160" height="55" decoding="async"></span>
            <p id="seasonalPreviewTitle" class="sp-title"></p>
            <p class="sp-note"></p>
        </div>
        <button type="button" class="sp-close">
            <i class="fas fa-xmark" aria-hidden="true"></i>
            <span>{{ __('ui.seasonal.preview.close') }}</span>
        </button>
    </div>
</div>

<style>
    .sp-overlay { position: fixed; inset: 0; z-index: 99998; background: #05070d; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .sp-overlay[hidden] { display: none; }
    .sp-canvas, .sp-poster { position: absolute; inset: 0; width: 100%; height: 100%; }
    .sp-poster { object-fit: cover; }
    .sp-canvas { display: none; }
    .sp-overlay.is-3d .sp-canvas { display: block; }
    .sp-overlay.is-3d .sp-poster { display: none; }
    .sp-bar { position: absolute; left: 0; right: 0; bottom: 0; display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 3rem 1rem calc(1rem + env(safe-area-inset-bottom, 0px)); background: linear-gradient(to top, rgba(0,0,0,.78), rgba(0,0,0,0)); color: #fff; }
    .sp-text { min-width: 0; }
    .sp-title { margin: 0; font-size: 1.25rem; font-weight: 800; line-height: 1.25; }
    .sp-logo { display: block; margin: 0 0 .6rem; line-height: 0; }
    .sp-logo img { display: block; height: 2.25rem; width: auto; filter: drop-shadow(0 2px 8px rgba(0,0,0,.5)); }
    .sp-overlay:not([hidden]) .sp-logo { animation: sp-logo-in .7s cubic-bezier(.2,.8,.2,1) .1s both; }
    @keyframes sp-logo-in { from { opacity: 0; transform: translateY(8px) scale(.96); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { .sp-overlay:not([hidden]) .sp-logo { animation: none; } }
    .sp-note { margin: .25rem 0 0; font-size: .75rem; color: rgba(255,255,255,.85); }
    .sp-note:empty { display: none; }
    .sp-close { flex: none; display: inline-flex; align-items: center; gap: .4rem; border: 1px solid rgba(255,255,255,.6); background: rgba(0,0,0,.55); color: #fff; border-radius: .5rem; padding: .5rem .9rem; font-size: .8125rem; font-weight: 600; cursor: pointer; }
    .sp-close:hover { background: rgba(255,255,255,.18); }
    .sp-close:focus-visible { outline: 3px solid #fff; outline-offset: 2px; }
    .seasonal-preview-box button { color: var(--text-color, #374151); border-color: rgba(107,114,128,.45); background: transparent; }
    .seasonal-preview-box button:hover:not(:disabled) { background: rgba(107,114,128,.12); }
</style>

@if($spStage)
<script type="importmap">@json(['imports' => $spImportMap], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)</script>
@endif
<script>
(function () {
    var DATA = {{ \Illuminate\Support\Js::from($spThemes) }};
    var PICKER = {{ \Illuminate\Support\Js::from($spPicker) }};
    var GLOBAL_KEY = {{ \Illuminate\Support\Js::from($spGlobal) }};
    var STAGE = {{ \Illuminate\Support\Js::from($spStage) }};
    var MAX_MS = 6000, SCENE_MS = 2600;
    var UNAVAILABLE = {{ \Illuminate\Support\Js::from(__('ui.seasonal.preview.unavailable')) }};
    var HINT = {{ \Illuminate\Support\Js::from(__('ui.seasonal.preview.hint')) }};
    var STILL = {{ \Illuminate\Support\Js::from(__('ui.seasonal.preview.still_note')) }};

    var btn = document.getElementById('seasonalPreviewBtn');
    var hint = document.getElementById('seasonalPreviewHint');
    var ov = document.getElementById('seasonalPreviewOverlay');
    if (!btn || !ov) return;
    var canvas = ov.querySelector('canvas'), poster = ov.querySelector('.sp-poster');
    var titleEl = document.getElementById('seasonalPreviewTitle'), noteEl = ov.querySelector('.sp-note');
    var closeBtn = ov.querySelector('.sp-close');

    var token = 0, handle = null, timer = null, returnFocus = null, prevOverflow = '', stageMod = null;

    function selectedKey() {
        var card = document.querySelector(PICKER + ' .st-card.is-selected[data-season-choice]');
        var v = card && card.getAttribute('data-season-choice');
        if (v === 'default') v = GLOBAL_KEY;
        return v && Object.prototype.hasOwnProperty.call(DATA, v) ? v : null;
    }
    function refresh() {
        var k = selectedKey();
        btn.disabled = !k;
        hint.textContent = k ? HINT : UNAVAILABLE;
    }
    // Partial ini dicetak sebelum kartu tema di DOM: delegasi klik di document, dan cek awal setelah DOM siap.
    document.addEventListener('click', function (e) {
        if (e.target.closest && e.target.closest(PICKER)) setTimeout(refresh, 0);
    });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', refresh);
    else refresh();

    // Tanpa WebGL: Save-Data, koneksi 2g/3g, atau reduced motion. Cek WebGL sendiri ada di stage.js.
    function lowPower() {
        var c = navigator.connection || {};
        var slow = !!(c.saveData || /(^|-)(2g|3g)$/.test(c.effectiveType || ''));
        var reduced = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
        return slow || reduced;
    }
    function freshCanvas() {
        var fresh = canvas.cloneNode(false);
        ov.replaceChild(fresh, canvas);
        canvas = fresh;
    }

    // Renderer dipertahankan antar putaran (tanpa kompilasi shader ulang); dilepas di sini.
    function releaseGL() {
        if (stageMod && stageMod.release) { try { stageMod.release(canvas); } catch (e) {} }
    }

    function close() {
        if (ov.hidden) return;
        token++;
        clearTimeout(timer); timer = null;
        if (handle) { try { handle.stop(); } catch (e) {} handle = null; }
        releaseGL();
        ov.classList.remove('is-3d');
        ov.hidden = true;
        document.body.style.overflow = prevOverflow;
        document.removeEventListener('keydown', onKey, true);
        document.removeEventListener('visibilitychange', onVis);
        freshCanvas(); // konteks lama sudah dilepas
        if (returnFocus && returnFocus.focus && document.contains(returnFocus)) returnFocus.focus();
        returnFocus = null;
    }
    function onKey(e) {
        if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(); }
        else if (e.key === 'Tab') { e.preventDefault(); closeBtn.focus(); } // satu-satunya kontrol: fokus terkunci
    }
    function onVis() { if (document.visibilityState === 'hidden') close(); }

    function open() {
        var key = selectedKey();
        if (!key || !ov.hidden) return; // hanya satu pratinjau pada satu waktu
        var d = DATA[key], my = ++token, t0 = Date.now();
        returnFocus = document.activeElement;
        prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        ov.setAttribute('aria-label', d.dialog);
        titleEl.textContent = d.greeting || d.dialog;
        noteEl.textContent = d.blessing || '';
        ov.classList.remove('is-3d');
        if (d.poster) { poster.src = d.poster; poster.alt = d.alt; poster.style.display = ''; }
        else { poster.removeAttribute('src'); poster.style.display = 'none'; }
        ov.hidden = false;
        closeBtn.focus();
        document.addEventListener('keydown', onKey, true);
        document.addEventListener('visibilitychange', onVis);
        timer = setTimeout(close, MAX_MS);

        if (!STAGE || lowPower()) { noteEl.textContent = STILL; return; }
        import(STAGE).then(function (m) {
            stageMod = m;
            if (my !== token) return null;
            if (!m.supportsWebGL()) { noteEl.textContent = STILL; return null; }
            return playLoop(m, d, my, t0);
        }).catch(function () { if (my === token) noteEl.textContent = STILL; });
    }

    // Memainkan adegan; bila sisa waktu cukup, sekali lagi sampai batas MAX_MS. Canvas dan renderer
    // dipakai ulang (reuseRenderer): pemutaran kedua tidak membuat konteks GL baru dan tidak mengompilasi
    // ulang shader. Selama jeda antar putaran frame terakhir tetap tampil (tanpa poster, tanpa layar kosong).
    function playLoop(m, d, my, t0) {
        return m.play(canvas, { scene: d.scene, palette: d.palette, duration: SCENE_MS, reuseRenderer: true }).then(function (h) {
            if (my !== token) { h.stop(); releaseGL(); return; }
            handle = h;
            ov.classList.add('is-3d');
            return h.done.then(function () {
                if (my !== token) return;
                handle = null;
                if (Date.now() - t0 + SCENE_MS + 300 <= MAX_MS) {
                    return playLoop(m, d, my, t0); // canvas tetap tampil (frame terakhir) sampai frame pertama putaran berikut
                }
                releaseGL();
                ov.classList.remove('is-3d'); // poster sampai penutupan otomatis
            });
        });
    }

    btn.addEventListener('click', open);
    ov.addEventListener('click', function () { close(); });
})();
</script>
@endif
