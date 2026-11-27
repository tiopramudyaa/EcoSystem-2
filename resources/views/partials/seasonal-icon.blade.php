{{--
    Satu ikon dari sprite partials/seasonal-icons.blade.php (sprite harus sudah
    ada di halaman).

    Params:
      $name   string  'icon-natal', 'orn-bell', dst (id symbol tanpa awalan "st-").
      $class  string  kelas tambahan (opsional).
      $size   int|string  sisi dalam px atau nilai CSS (opsional, default ikut CSS .st-icon).
      $theme  string  key tema (opsional). Bila diisi, ikon diwarnai untuk latar GELAP
                      tema itu: warna utama dari peta di bawah, aksen dari palet katalog.
--}}
@php
    $stIconSize = isset($size) ? (is_numeric($size) ? $size . 'px' : $size) : null;
    // Warna utama ikon di atas ubin gelap (lihat partial kartu pilihan tema).
    $stIconTints = [
        'natal' => '#22c55e', 'tahun_baru' => '#e2e8f0', 'ramadan' => '#c7d2fe',
        'lebaran' => '#6ee7b7', 'imlek' => '#ef4444', 'hut_ri' => '#ef4444',
    ];
    $stIconStyle = [];
    if ($stIconSize) {
        $stIconStyle[] = "width:{$stIconSize};height:{$stIconSize}";
    }
    if (!empty($theme) && isset($stIconTints[$theme])) {
        $stIconStyle[] = 'color:' . $stIconTints[$theme];
        $stIconAccent = \App\Support\SeasonalThemes::get($theme)['palette']['accent'] ?? null;
        if ($stIconAccent) {
            $stIconStyle[] = '--st-accent:' . $stIconAccent;
        }
    }
@endphp
<svg class="st-icon {{ $class ?? '' }}" @if($stIconStyle) style="{{ implode(';', $stIconStyle) }}" @endif aria-hidden="true" focusable="false"><use href="#st-{{ $name }}"/></svg>
@once
<style>
    .st-icon { width: 1.5em; height: 1.5em; display: inline-block; flex-shrink: 0; vertical-align: middle; overflow: visible; }
    /* Ubin kartu pilihan tema: latar gelap bergradien dari palet login tema. */
    .st-tile { display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: .5rem; }
</style>
@endonce
