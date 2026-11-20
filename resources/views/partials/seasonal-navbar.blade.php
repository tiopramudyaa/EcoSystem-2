{{--
    Hiasan navbar tema musiman: satu lapisan dekoratif selebar header, digambar
    dari ubin SVG statis (public/images/seasonal/navbar/<key>.svg) yang diulang
    horizontal, jadi satu berkas kecil menutupi lebar layar berapa pun.

    Dipasang DI DALAM <header> dashboard (sticky z-40, sudah membentuk stacking
    context sendiri). Aturan lapisan:
      - absolute, kiri dan kanan 0, menempel di tepi bawah header; tidak ikut
        alur layout, jadi tinggi header tidak berubah (tanpa CLS).
      - z-index -1: di atas latar header, di bawah semua kontrol (tombol, search,
        bel, avatar, dropdown) karena itu semua konten normal di dalam header.
      - pointer-events:none dan user-select:none: tidak pernah menangkap klik.
      - menjuntai maksimal 10px di bawah tepi header, di atas padding konten
        (konten minimal p-4 = 16px), tidak menutupi teks atau target klik.
      - disembunyikan saat print dan saat prefers-contrast: more.
      - kedip lampu hanya lewat animasi opacity CSS, dan hanya bila toggle
        Show animations aktif dan prefers-reduced-motion: no-preference.
        Tanpa itu, hiasan statis. Tidak ada JavaScript.
      - tile hanya diminta browser ketika tema aktif (background-image ada di
        <style> yang hanya dicetak untuk tema aktif).

    Params:
      $themeKey        string  key tema efektif (bukan 'none').
      $showAnimations  bool    preferensi Show animations pengguna.
      $dark            bool    header sedang mode gelap (latar --card-bg gelap).
--}}
@php
    $navTrimKey = $themeKey ?? 'none';
    // Tema -> berkas ubin. Lampu (opsional) adalah ubin kedua yang berkedip.
    $navTrimTiles = [
        'natal' => ['natal', true],
        'tahun_baru' => ['tahun_baru', true],
        'ramadan' => ['ramadan', true],
        'lebaran' => ['lebaran', false],
        'imlek' => ['imlek', false],
        'hut_ri' => ['hut_ri', false],
    ];
    $navTrim = $navTrimTiles[$navTrimKey] ?? null;
    $navTrimAnimate = (bool) ($showAnimations ?? true);
    $navTrimDark = (bool) ($dark ?? false);
@endphp
@if($navTrim)
<div class="seasonal-navbar{{ $navTrimDark ? ' seasonal-navbar--dark' : '' }}" aria-hidden="true" data-season-trim="{{ $navTrimKey }}"></div>
@once
<style>
    .seasonal-navbar {
        position: absolute;
        left: 0;
        right: 0;
        bottom: -10px;
        height: 32px;
        z-index: -1;
        pointer-events: none;
        user-select: none;
        -webkit-user-select: none;
        contain: layout paint size;
    }
    .seasonal-navbar::before,
    .seasonal-navbar::after {
        content: '';
        position: absolute;
        inset: 0;
        background-repeat: repeat-x;
        background-position: left top;
        background-size: auto 100%;
    }
    .seasonal-navbar[data-season-trim="{{ $navTrimKey }}"]::before {
        background-image: url('{{ asset('images/seasonal/navbar/' . $navTrim[0] . '.svg') }}?v={{ @filemtime(public_path('images/seasonal/navbar/' . $navTrim[0] . '.svg')) ?: 0 }}');
    }
    @if($navTrim[1])
    .seasonal-navbar[data-season-trim="{{ $navTrimKey }}"]::after {
        background-image: url('{{ asset('images/seasonal/navbar/' . $navTrim[0] . '-lights.svg') }}?v={{ @filemtime(public_path('images/seasonal/navbar/' . $navTrim[0] . '-lights.svg')) ?: 0 }}');
    }
    @endif
    /* Header gelap (#1f2937): sedikit lebih terang supaya hijau tua dan biru tua tetap terbaca. */
    .seasonal-navbar--dark { filter: brightness(1.22) saturate(1.05); }
    /* Hijau janur terlalu neon bila dicerahkan: untuk Lebaran cukup sedikit. */
    .seasonal-navbar--dark[data-season-trim="lebaran"] { filter: brightness(1.05) saturate(.85); }
    /* Layar kecil: ubin diperkecil proporsional, padding bawah header hanya 10px. */
    @media (max-width: 639.98px) {
        .seasonal-navbar { height: 22px; bottom: -7px; }
    }
    @media (max-width: 399.98px) {
        .seasonal-navbar { height: 18px; bottom: -6px; }
    }
    @if($navTrimAnimate && $navTrim[1])
    @media (prefers-reduced-motion: no-preference) {
        .seasonal-navbar::after {
            animation: seasonal-navbar-twinkle 3.2s ease-in-out infinite alternate;
        }
        @keyframes seasonal-navbar-twinkle {
            0%   { opacity: .45; }
            100% { opacity: 1; }
        }
    }
    @endif
    @media print { .seasonal-navbar { display: none; } }
    @media (prefers-contrast: more) { .seasonal-navbar { display: none; } }
</style>
@endonce
@endif
