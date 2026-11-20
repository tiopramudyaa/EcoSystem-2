{{--
    Hiasan navbar tema musiman: dua potongan vektor kecil di pojok kiri dan kanan header
    (bukan rumbai selebar layar). Gambar dihasilkan App\Support\SeasonalNavbarArt dan
    dicetak inline sehingga tiap bagian bisa bergerak sendiri (ayun, kedip, letup, kibar).
    Tidak ada berkas gambar tambahan dan tidak ada JavaScript.

    Dipasang DI DALAM <header> dashboard (sticky z-40, sudah membentuk stacking context).
      - absolute di tepi bawah header, tidak ikut alur layout, tinggi header tidak berubah.
      - z-index -1: di bawah semua kontrol; pointer-events none; tidak menangkap klik.
      - bagian di dalam header hanya di pojok luar (di balik tombol menu dan avatar);
        sepanjang tepi hanya bandul kecil yang menjuntai maksimal 12px di bawah header,
        jadi judul dan subjudul halaman tidak tertutup.
      - gerak hanya lewat transform/opacity CSS dan hanya bila toggle Show animations
        aktif dan prefers-reduced-motion: no-preference. Selain itu statis.
      - disembunyikan saat print dan prefers-contrast: more.

    Params: $themeKey (key tema efektif), $showAnimations (bool), $dark (bool, header gelap).
--}}
@php
    $navArt = \App\Support\SeasonalNavbarArt::pieces($themeKey ?? 'none');
    $navTrimKey = $themeKey ?? 'none';
    $navTrimAnimate = (bool) ($showAnimations ?? true);
    $navTrimDark = (bool) ($dark ?? false);
@endphp
@if($navArt)
<div class="seasonal-navbar{{ $navTrimDark ? ' seasonal-navbar--dark' : '' }}" aria-hidden="true" data-season-trim="{{ $navTrimKey }}">
    <svg class="sn-piece sn-l" viewBox="0 0 190 40" width="190" height="40" focusable="false">{!! $navArt['l'] !!}</svg>
    <svg class="sn-piece sn-r" viewBox="0 0 190 40" width="190" height="40" focusable="false">{!! $navArt['r'] !!}</svg>
    {{-- Lapisan ketiga: elemen kecil dan jarang di dekat tiap pojok luar. --}}
    <svg class="sn-piece sn-fx sn-fl" viewBox="0 0 70 40" width="70" height="40" focusable="false">{!! $navArt['fl'] !!}</svg>
    <svg class="sn-piece sn-fx sn-fr" viewBox="0 0 70 40" width="70" height="40" focusable="false">{!! $navArt['fr'] !!}</svg>
</div>
@once
<style>
    .seasonal-navbar {
        position: absolute; left: 0; right: 0; bottom: -14px; height: 48px;
        z-index: -1; pointer-events: none; user-select: none; -webkit-user-select: none;
        contain: layout paint size;
    }
    .seasonal-navbar .sn-piece { position: absolute; bottom: 0; height: 48px; width: 228px; overflow: visible; }
    .seasonal-navbar .sn-l, .seasonal-navbar .sn-fl { left: 0; }
    .seasonal-navbar .sn-r, .seasonal-navbar .sn-fr { right: 0; }
    .seasonal-navbar .sn-fx { width: 84px; }
    /* --sn-b: tinggi bagian layer di bawah tepi header (14px di desktop). Garis tepi dan es memakainya. */
    .seasonal-navbar { --sn-b: 14px; }
    /* Garis tepi 2px di dalam tepi bawah header, memudar di kedua ujung. Statis. */
    .seasonal-navbar::before {
        content: ''; position: absolute; left: 0; right: 0; bottom: var(--sn-b); height: 2px; opacity: .9;
        -webkit-mask-image: linear-gradient(90deg, transparent, #000 7%, #000 93%, transparent);
        mask-image: linear-gradient(90deg, transparent, #000 7%, #000 93%, transparent);
    }
    @switch($navTrimKey)
        @case('natal')
    .seasonal-navbar::before { background: linear-gradient(90deg, #c8102e 0 34%, #1f6b3a 34% 67%, #d9a21c 67%); }
    {{-- Es halus tidak beraturan di bawah tepi header, tinggi maksimal 6px, samar. --}}
    .seasonal-navbar::after {
        content: ''; position: absolute; left: 0; right: 0; bottom: calc(var(--sn-b) - 6px); height: 6px; opacity: .5;
        background: url("{!! \App\Support\SeasonalNavbarArt::frostTile() !!}") 0 0 / 480px 6px repeat-x;
    }
            @break
        @case('tahun_baru')
    .seasonal-navbar::before { background: linear-gradient(90deg, #e0b13a, #f6d36a 50%, #e0b13a); }
            @break
        @case('ramadan')
    .seasonal-navbar::before { background: linear-gradient(90deg, #d9a21c, #f5c542 50%, #d9a21c); }
    .seasonal-navbar::after {
        content: ''; position: absolute; left: 0; right: 0; bottom: calc(var(--sn-b) + 2px); height: 6px; opacity: .8;
        background: url("{!! \App\Support\SeasonalNavbarArt::edgeStarsTile() !!}") 0 0 / 96px 6px repeat-x;
        -webkit-mask-image: linear-gradient(90deg, transparent, #000 12%, #000 88%, transparent);
        mask-image: linear-gradient(90deg, transparent, #000 12%, #000 88%, transparent);
    }
            @break
        @case('lebaran')
    .seasonal-navbar::before { background: linear-gradient(90deg, #5f8f20, #aed581 50%, #5f8f20); }
            @break
        @case('imlek')
    .seasonal-navbar::before { background: linear-gradient(90deg, #d4202a 0 40%, #f2c14e 40% 60%, #d4202a 60%); }
            @break
        @case('hut_ri')
    .seasonal-navbar::before { background: linear-gradient(#d6202c 0 1px, #dcdcdc 1px); }
            @break
    @endswitch
    .seasonal-navbar--dark { filter: brightness(1.22) saturate(1.05); }
    .seasonal-navbar--dark[data-season-trim="lebaran"] { filter: brightness(1.05) saturate(.85); }
    .seasonal-navbar--dark[data-season-trim="natal"]::after { opacity: .3; }
    @media (max-width: 639.98px) {
        .seasonal-navbar { height: 36px; bottom: -11px; --sn-b: 11px; }
        .seasonal-navbar .sn-piece { height: 36px; width: 171px; }
        .seasonal-navbar .sn-r, .seasonal-navbar .sn-fr { display: none; }
        .seasonal-navbar .sn-fx { width: 63px; }
    }
    @media (max-width: 399.98px) {
        .seasonal-navbar { height: 30px; bottom: -9px; --sn-b: 9px; }
        .seasonal-navbar .sn-fx { width: 53px; }
        .seasonal-navbar .sn-piece { height: 30px; width: 142px; }
    }
    @if($navTrimAnimate)
    @media (prefers-reduced-motion: no-preference) {
        .seasonal-navbar .sn-sway { animation: sn-sway var(--d, 4s) ease-in-out var(--o, 0s) infinite alternate; }
        .seasonal-navbar .sn-twinkle { animation: sn-twinkle var(--d, 3s) ease-in-out var(--o, 0s) infinite alternate; }
        .seasonal-navbar .sn-pop { animation: sn-pop var(--d, 4s) ease-out var(--o, 0s) infinite; }
        .seasonal-navbar .sn-wave { animation: sn-wave var(--d, 3s) ease-in-out var(--o, 0s) infinite alternate; }
        .seasonal-navbar .sn-drift { animation: sn-drift var(--d, 5s) ease-in-out var(--o, 0s) infinite alternate; }
        .seasonal-navbar .sn-fall { animation: sn-fall var(--d, 5s) ease-in-out var(--o, 0s) infinite alternate; }
        .seasonal-navbar .sn-breathe { animation: sn-breathe var(--d, 5s) ease-in-out infinite alternate; }
        @keyframes sn-sway { from { transform: rotate(calc(var(--a, 3.5deg) * -1)); } to { transform: rotate(var(--a, 3.5deg)); } }
        @keyframes sn-twinkle { from { opacity: .35; transform: scale(.82); } to { opacity: 1; transform: scale(1.08); } }
        @keyframes sn-pop { 0% { opacity: 0; transform: scale(.25); } 18% { opacity: 1; } 55% { opacity: 1; transform: scale(1); } 100% { opacity: 0; transform: scale(1.18); } }
        @keyframes sn-wave { from { transform: skewY(-5deg) scaleX(.97); } to { transform: skewY(5deg) scaleX(1.03); } }
        @keyframes sn-drift { from { opacity: .55; transform: translate(-1px, -1.4px); } to { opacity: 1; transform: translate(1.2px, 1.8px); } }
        @keyframes sn-fall { from { opacity: .45; transform: translate(-1px, -1px) rotate(-14deg); } to { opacity: 1; transform: translate(1.4px, 2.4px) rotate(18deg); } }
        @keyframes sn-breathe { from { opacity: .5; transform: scale(.94); } to { opacity: 1; transform: scale(1.08); } }
    }
    @endif
    @media print { .seasonal-navbar { display: none; } }
    @media (prefers-contrast: more) { .seasonal-navbar { display: none; } }
</style>
@endonce
@endif
