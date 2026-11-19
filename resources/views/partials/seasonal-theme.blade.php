{{--
    Dekorasi tema musiman. Dipakai ulang di empat tempat:
      - sidebar dan header di dashboard.blade.php (tema efektif per user),
      - panel kiri login (auth/login.blade.php) dan overlay loading
        (partials/seasonal-loading.blade.php), keduanya memakai tema global admin.

    Isi dekorasi dibaca dari SeasonalThemes::CATALOG:
      - `decoration` menentukan efek animasi (snow, confetti, stars, sparkle, lantern, flag).
      - `ornaments` adalah nama ornamen vektor (symbol st-orn-NAMA dari
        partials/seasonal-icons.blade.php, sprite harus ada di halaman). Tanpa emoji.

    Dekorasi tidak pernah menutupi konten halaman utama, hanya area yang
    dikurung oleh $placement. Elemen selalu pointer-events:none.

    Gerakan: partikel animasi hanya dirender bila $showAnimations true, dan
    baru tampil bila browser tidak meminta reduced motion (CSS media query).
    Aksen statis tetap tampil tanpa gerakan.

    Params:
      $themeKey        string  key tema efektif (bukan 'default' mentah).
      $showAnimations  bool    gerbang pertama animasi (toggle Settings user).
      $placement       string  'sidebar' | 'header' | 'login-panel' | 'fullpage'
                               sidebar: absolute di dalam aside (fixed, z-50).
                               header: absolute di dalam header (sticky, z-40),
                               hanya 3 aksen statis pudar, tanpa partikel.
                               login-panel: absolute di panel kiri login.
                               fullpage: fixed menutupi viewport, dipakai
                               overlay loading yang tampil singkat.
                               Default 'fullpage'.
--}}
@php
    $seasonalMeta = \App\Support\SeasonalThemes::get($themeKey);
    $seasonalDecoration = $seasonalMeta['decoration'] ?? null;
    $seasonalAccents = $seasonalMeta['ornaments'] ?? [];
    $seasonalPlacement = $placement ?? 'fullpage';
    $seasonalIsHeader = $seasonalPlacement === 'header';
    $seasonalAnimate = (bool) ($showAnimations ?? true);
    // Header: paling banyak 3 aksen pudar. Tempat lain: semua aksen.
    $seasonalShownAccents = $seasonalIsHeader ? array_slice($seasonalAccents, 0, 3) : $seasonalAccents;
    // Overlay loading hanya tampil sekitar 2,5 detik, jadi partikelnya lebih
    // rapat dan cepat dibanding sidebar atau panel login yang dilihat lama.
    $seasonalIsBrief = $seasonalPlacement === 'fullpage';
    $seasonalCompact = $seasonalPlacement === 'sidebar';
    $seasonalConfettiColors = ['#fbbf24', '#f8fafc', '#93c5fd', '#fca5a5', '#86efac'];
@endphp
@if($seasonalDecoration || count($seasonalAccents))
<div class="seasonal-decoration seasonal-decoration--{{ $seasonalPlacement }}" aria-hidden="true">
    @if(!$seasonalIsHeader && $seasonalAnimate)
        @switch($seasonalDecoration)
            @case('snow')
                @for ($i = 0; $i < ($seasonalIsBrief ? 35 : ($seasonalCompact ? 14 : 20)); $i++)
                    @php
                        $flakeDelay = $seasonalIsBrief ? (rand(0, 600) / 1000) : (rand(0, 12000) / 1000);
                        $flakeDuration = $seasonalIsBrief ? (rand(18, 30) / 10) : rand(9, 18);
                    @endphp
                    <svg class="seasonal-particle seasonal-snowflake" aria-hidden="true" focusable="false"
                       style="left:{{ rand(0, 100) }}%; animation-delay:{{ $flakeDelay }}s; --seasonal-fall-duration:{{ $flakeDuration }}s; width:{{ rand(8, 16) }}px; height:{{ rand(8, 16) }}px; opacity:{{ rand(25, 55) / 100 }};"><use href="#st-orn-snowflake"/></svg>
                @endfor
                @break

            @case('confetti')
                @for ($i = 0; $i < ($seasonalIsBrief ? 40 : ($seasonalCompact ? 14 : 22)); $i++)
                    @php
                        $confettiDelay = $seasonalIsBrief ? (rand(0, 800) / 1000) : (rand(0, 10000) / 1000);
                        $confettiDuration = $seasonalIsBrief ? (rand(18, 30) / 10) : rand(8, 15);
                        $confettiSize = rand(4, 8);
                        $confettiRound = $i % 3 === 0;
                    @endphp
                    <span class="seasonal-particle seasonal-confetti"
                       style="left:{{ rand(0, 100) }}%; width:{{ $confettiSize }}px; height:{{ $confettiRound ? $confettiSize : $confettiSize * 2 }}px; border-radius:{{ $confettiRound ? '50%' : '1px' }}; background:{{ $seasonalConfettiColors[$i % count($seasonalConfettiColors)] }}; animation-delay:{{ $confettiDelay }}s; --seasonal-fall-duration:{{ $confettiDuration }}s; opacity:{{ rand(45, 80) / 100 }};"></span>
                @endfor
                @break

            @case('stars')
                @for ($i = 0; $i < ($seasonalIsBrief ? 22 : ($seasonalCompact ? 10 : 16)); $i++)
                    <svg class="seasonal-particle seasonal-star" aria-hidden="true" focusable="false"
                       style="left:{{ rand(2, 96) }}%; top:{{ rand(2, 96) }}%; width:{{ rand(8, 16) }}px; height:{{ rand(8, 16) }}px; animation-delay:{{ rand(0, 3000) / 1000 }}s; --seasonal-twinkle-duration:{{ rand(25, 50) / 10 }}s;"><use href="#st-orn-{{ $i % 3 === 0 ? 'star' : 'spark' }}"/></svg>
                @endfor
                @break

            @case('sparkle')
                @for ($i = 0; $i < ($seasonalIsBrief ? 22 : ($seasonalCompact ? 10 : 16)); $i++)
                    <svg class="seasonal-particle seasonal-sparkle" aria-hidden="true" focusable="false"
                       style="left:{{ rand(2, 96) }}%; top:{{ rand(2, 96) }}%; width:{{ rand(8, 16) }}px; height:{{ rand(8, 16) }}px; animation-delay:{{ rand(0, 3000) / 1000 }}s; --seasonal-twinkle-duration:{{ rand(22, 44) / 10 }}s;"><use href="#st-orn-spark"/></svg>
                @endfor
                @break

            @case('flag')
                @for ($i = 0; $i < ($seasonalIsBrief ? 22 : ($seasonalCompact ? 10 : 16)); $i++)
                    @php
                        $dotSize = rand(5, 10);
                    @endphp
                    <span class="seasonal-particle seasonal-flag-dot"
                       style="left:{{ rand(0, 100) }}%; width:{{ $dotSize }}px; height:{{ $dotSize }}px; background:{{ $i % 2 === 0 ? '#dc2626' : '#f8fafc' }}; animation-delay:{{ $seasonalIsBrief ? rand(0, 600) / 1000 : rand(0, 12000) / 1000 }}s; --seasonal-rise-duration:{{ $seasonalIsBrief ? rand(20, 30) / 10 : rand(10, 18) }}s;"></span>
                @endfor
                @break
        @endswitch
    @endif

    @if(count($seasonalShownAccents))
        {{-- Aksen statis, opasitas rendah supaya terbaca sebagai latar dan tidak menutupi teks. --}}
        @foreach($seasonalShownAccents as $j => $ornName)
            @php
                $accentCount = count($seasonalShownAccents);
                if ($seasonalIsHeader) {
                    // Rapat di sisi kanan-tengah, jauh dari judul halaman di kiri.
                    $accentTop = [24, 46, 30][$j % 3];
                    $accentSide = 'right:' . (18 + $j * 9) . '%;';
                    $accentSize = rand(16, 20);
                    $accentOpacity = rand(14, 24) / 100;
                } else {
                    $accentTop = 8 + ($j * (84 / max(1, $accentCount - 1)));
                    $accentSide = $j % 2 === 0 ? 'left:6%;' : 'right:6%;';
                    $accentSize = $seasonalPlacement === 'sidebar' ? rand(20, 30) : rand(28, 44);
                    $accentOpacity = rand(30, 50) / 100;
                }
                // Gerakan lembut hanya di luar header dan hanya bila animasi aktif.
                $accentMotion = '';
                if ($seasonalAnimate && !$seasonalIsHeader && in_array($ornName, ['lantern', 'bell'], true)) {
                    $accentMotion = 'seasonal-accent--sway';
                }
            @endphp
            <svg class="seasonal-accent {{ $accentMotion }}" aria-hidden="true" focusable="false" style="top:{{ $accentTop }}%; {{ $accentSide }} width:{{ $accentSize }}px; height:{{ $accentSize }}px; opacity:{{ $accentOpacity }};"><use href="#st-orn-{{ $ornName }}"/></svg>
        @endforeach
    @endif
</div>

@once
<style>
    /* pointer-events:none supaya tidak pernah menghalangi klik.
       'sidebar' dan 'header': z-index negatif, mengandalkan <aside> dan
       <header> yang sudah punya z-50 dan z-40 (stacking context sendiri),
       sehingga lapisan ini tetap di belakang label menu dan tombol tanpa
       bocor ke belakang halaman. 'fullpage': fixed menutupi viewport. */
    .seasonal-decoration--sidebar,
    .seasonal-decoration--header {
        position: absolute;
        inset: 0;
        pointer-events: none;
        overflow: hidden;
        z-index: -1;
    }
    /* #deco-panel di login tidak membentuk stacking context, jadi dipakai
       z-index:0. Wrapper konten asli di panel itu z-index:10. */
    .seasonal-decoration--login-panel {
        position: absolute;
        inset: 0;
        pointer-events: none;
        overflow: hidden;
        z-index: 0;
    }
    .seasonal-decoration--fullpage {
        position: fixed;
        inset: 0;
        pointer-events: none;
        overflow: hidden;
        z-index: 5;
    }
    .seasonal-decoration .seasonal-accent {
        position: absolute;
        color: #fff;
        overflow: visible;
        filter: drop-shadow(0 1px 1px rgba(0,0,0,0.15));
    }
    /* Header putih: ornamen memakai warna utama tema supaya tetap terbaca. */
    .seasonal-decoration--header .seasonal-accent {
        color: rgb(var(--st-primary-rgb, 107 114 128));
        filter: none;
    }
    /* Partikel disembunyikan sampai browser memastikan gerakan boleh dipakai. */
    .seasonal-decoration .seasonal-particle {
        display: none;
        position: absolute;
        pointer-events: none;
    }
    .seasonal-decoration .seasonal-snowflake {
        top: -8%;
        color: #dbeafe;
        overflow: visible;
        filter: drop-shadow(0 1px 1px rgba(0,0,0,0.18));
    }
    .seasonal-decoration .seasonal-confetti { top: -6%; }
    .seasonal-decoration .seasonal-star,
    .seasonal-decoration .seasonal-sparkle {
        color: var(--st-accent, #fcd34d);
        overflow: visible;
        opacity: .2;
    }
    .seasonal-decoration .seasonal-flag-dot {
        bottom: -4%;
        border-radius: 50%;
        opacity: 0;
    }
    @media (prefers-reduced-motion: no-preference) {
        .seasonal-decoration .seasonal-particle { display: block; }
        .seasonal-decoration .seasonal-snowflake {
            animation: seasonal-snow-fall var(--seasonal-fall-duration, 12s) linear infinite;
        }
        .seasonal-decoration .seasonal-confetti {
            animation: seasonal-confetti-fall var(--seasonal-fall-duration, 10s) linear infinite;
        }
        .seasonal-decoration .seasonal-star,
        .seasonal-decoration .seasonal-sparkle {
            animation: seasonal-star-twinkle var(--seasonal-twinkle-duration, 3.5s) ease-in-out infinite;
        }
        .seasonal-decoration .seasonal-flag-dot {
            animation: seasonal-dot-rise var(--seasonal-rise-duration, 14s) ease-in infinite;
        }
        @keyframes seasonal-snow-fall {
            0%   { transform: translateY(-10vh) translateX(0) rotate(0deg); }
            100% { transform: translateY(110vh) translateX(20px) rotate(360deg); }
        }
        @keyframes seasonal-confetti-fall {
            0%   { transform: translateY(-10vh) translateX(0) rotate(0deg); }
            50%  { transform: translateY(55vh) translateX(-14px) rotate(220deg); }
            100% { transform: translateY(110vh) translateX(10px) rotate(480deg); }
        }
        @keyframes seasonal-star-twinkle {
            0%, 100% { opacity: .12; transform: scale(.8); }
            50%      { opacity: .7;  transform: scale(1.15); }
        }
        @keyframes seasonal-dot-rise {
            0%   { transform: translateY(0); opacity: 0; }
            15%  { opacity: .45; }
            85%  { opacity: .3; }
            100% { transform: translateY(-105vh); opacity: 0; }
        }
        /* Lentera dan lonceng berayun pelan dari titik gantung. */
        .seasonal-decoration .seasonal-accent--sway {
            transform-origin: 50% 0;
            animation: seasonal-lantern-sway 5s ease-in-out infinite;
        }
        @keyframes seasonal-lantern-sway {
            0%, 100% { transform: rotate(-5deg); }
            50%      { transform: rotate(5deg); }
        }
    }
</style>
@endonce
@endif
