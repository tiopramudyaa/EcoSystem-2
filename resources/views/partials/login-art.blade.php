{{--
    Ilustrasi vektor panel kiri halaman login untuk tema musiman.

    Params: $themeKey (key katalog, bukan 'none').

    Aturan:
      - Gambar adalah file statis public/images/seasonal/login/<key>.svg yang dimuat lewat CSS
        background hanya untuk tema aktif. Panel kiri disembunyikan (display:none) di bawah 768px,
        sehingga browser tidak mengunduh gambar di layar kecil.
      - Kotak gambar punya aspect-ratio tetap dan berposisi absolut, jadi tidak menggeser tata letak (CLS 0).
      - Scrim gelap di sisi kiri menjaga teks panel (merek, judul, fitur, tautan) tetap terbaca di atas gambar.
      - Gerakan: paling banyak 10 elemen <i> dengan animasi transform dan opacity saja,
        hanya bila pengguna tidak meminta reduced motion. Halaman login tidak punya
        pengaturan Show animations per pengguna.
      - Seluruh blok ini aria-hidden dan pointer-events:none; form login di panel kanan tidak disentuh.
--}}
@php
    $laKeys = ['natal', 'tahun_baru', 'ramadan', 'lebaran', 'imlek', 'hut_ri'];
    $laKey = in_array($themeKey ?? '', $laKeys, true) ? $themeKey : null;
    // Efek ambien per tema. Posisi dalam persen terhadap kotak gambar (viewBox 1000x800).
    // t: snow (jatuh), tw (berkedip), gl (cahaya lentera berdenyut), cf (confetti jatuh), rise (naik).
    $laFx = [
        'natal' => [
            ['snow', 6, 0, 0], ['snow', 17, 3.5, 9], ['snow', 28, 7, 5], ['snow', 41, 1.5, 12], ['snow', 53, 5, 7],
            ['snow', 64, 8.5, 10], ['snow', 76, 2.5, 6], ['snow', 88, 6, 11], ['tw', 70, 15, 0, 28], ['gl', 36, 72, 0, 96],
        ],
        'tahun_baru' => [
            ['tw', 69, 31, 0, 40], ['tw', 47, 24, 1.2, 30], ['tw', 86, 54, 2.1, 26], ['tw', 90, 19, .6, 24],
            ['cf', 20, 0, 0], ['cf', 48, 4, 5], ['cf', 72, 2, 8], ['cf', 90, 6, 3], ['gl', 70, 55, 0, 170],
        ],
        'ramadan' => [
            ['tw', 8, 12, 0, 22], ['tw', 30, 30, 1.4, 20], ['tw', 93, 18, 2.2, 22], ['tw', 83, 21, .8, 30],
            ['gl', 60, 24, 0, 150], ['gl', 69, 37, 1.1, 120], ['gl', 90, 45, 2, 110], ['gl', 40, 25, 1.7, 100], ['gl', 63, 76, 1, 190],
        ],
        'lebaran' => [
            ['tw', 65, 25, 0, 56], ['tw', 87, 21, 1.5, 50], ['tw', 55, 12, 2.4, 40], ['gl', 62, 62, 1.1, 120],
            ['tw', 91, 52, .8, 56], ['gl', 71, 80, 1, 130], ['rise', 74, 92, 0], ['rise', 90, 90, 4],
        ],
        'imlek' => [
            ['gl', 90, 20, 0, 130], ['gl', 83, 28, 1.3, 100], ['tw', 56, 16, .5, 30], ['tw', 24, 40, 2.2, 22],
            ['tw', 71, 38, 1.6, 26], ['tw', 40, 56, 2.4, 22], ['gl', 85, 94, 1.1, 150], ['rise', 62, 90, 0], ['rise', 46, 94, 4],
        ],
        'hut_ri' => [
            ['gl', 66, 72, 0, 220], ['tw', 40, 22, 0, 22], ['tw', 87, 16, 1.4, 22], ['rise', 30, 96, 0], ['rise', 54, 98, 3],
            ['rise', 78, 97, 6], ['tw', 80, 38, 2.2, 28],
        ],
    ];
@endphp
@if($laKey)
<div class="dp-art" aria-hidden="true" style="background-image:url('{{ asset('images/seasonal/login/' . $laKey . '.svg') }}?v={{ @filemtime(public_path('images/seasonal/login/' . $laKey . '.svg')) ?: 0 }}');">
    @foreach(($laFx[$laKey] ?? []) as $i => $fx)
        @php
            $kind = $fx[0];
            $st = 'left:' . $fx[1] . '%;top:' . $fx[2] . '%;--dl:' . ($fx[3] ?? 0) . 's;';
            if (isset($fx[4]) && in_array($kind, ['tw', 'gl'], true)) {
                $st .= '--sz:' . $fx[4] . 'px;';
            }
        @endphp
        <i class="dpa-fx dpa-fx--{{ $kind }}" style="{{ $st }}"></i>
    @endforeach
</div>
<div class="dp-scrim" aria-hidden="true"></div>
<style>
    /* Ilustrasi: kanan-bawah panel, rasio 5:4 sama dengan viewBox SVG. */
    #deco-panel .dp-art {
        position: absolute; right: -1%; bottom: 0; z-index: 0; pointer-events: none;
        height: 76%; aspect-ratio: 5 / 4; max-width: none;
        background-repeat: no-repeat; background-position: right bottom; background-size: 100% 100%;
        -webkit-mask-image: linear-gradient(to bottom, transparent 0, #000 11%), linear-gradient(to right, transparent 0, #000 16%);
        -webkit-mask-composite: source-in;
                mask-image: linear-gradient(to bottom, transparent 0, #000 11%), linear-gradient(to right, transparent 0, #000 16%);
                mask-composite: intersect;
    }
    /* Scrim: menggelapkan gambar di bawah teks (kolom kiri) dan menyatu ke gradien panel di bawah. */
    #deco-panel .dp-scrim {
        position: absolute; inset: 0; z-index: 1; pointer-events: none;
        background:
            linear-gradient(90deg, rgba(0,0,0,.72) 0, rgba(0,0,0,.64) 440px, rgba(0,0,0,.46) 540px, rgba(0,0,0,0) 720px);
        background:
            linear-gradient(90deg,
                color-mix(in srgb, var(--deco-from) 86%, transparent) 0,
                color-mix(in srgb, var(--deco-from) 74%, transparent) 440px,
                color-mix(in srgb, var(--deco-from) 52%, transparent) 540px,
                transparent 720px);
    }
    /* Tombol Sign in bertulisan gelap (tema dengan tombol terang): spinner ikut gelap. */
    :root[data-season] #submitBtn div[style*="spin"] { border-top-color: var(--btn-fg, #fff) !important; }

    /* Elemen dekorasi polos bawaan (orb, cincin, pil kaca) disembunyikan saat ada tema: gambar menggantikannya. */
    :root[data-season] #deco-panel .dp-ambient { display: none; }
    /* Teks panel di atas gambar: naikkan opasitas yang sebelumnya terlalu pucat. */
    :root[data-season] #deco-panel .dp-sub { color: rgba(255,255,255,.88); }
    :root[data-season] #deco-panel .dp-feature p:last-child { color: rgba(255,255,255,.84) !important; }
    :root[data-season] #deco-panel .dp-notice { background: rgba(0,0,0,.42); }
    :root[data-season] #deco-panel .dp-notice span { color: rgba(255,255,255,.82) !important; }
    :root[data-season] #deco-panel .dp-notice svg { color: rgba(255,255,255,.7) !important; }

    /* Gerakan ambien: hanya transform dan opacity, hanya tanpa reduced motion. */
    #deco-panel .dpa-fx { display: none; position: absolute; pointer-events: none; will-change: transform, opacity; }
    @media (prefers-reduced-motion: no-preference) {
        #deco-panel .dpa-fx { display: block; }
        .dpa-fx--snow {
            width: 6px; height: 6px; border-radius: 50%; background: #fff; opacity: 0;
            margin-top: -2%;
            animation: dpa-snow 11s linear infinite; animation-delay: var(--dl, 0s);
        }
        .dpa-fx--snow:nth-child(3n) { width: 4px; height: 4px; animation-duration: 14s; }
        .dpa-fx--snow:nth-child(3n+1) { width: 8px; height: 8px; animation-duration: 9s; filter: blur(.5px); }
        .dpa-fx--cf {
            width: 5px; height: 9px; border-radius: 1px; background: #f3dc8e; opacity: 0;
            animation: dpa-conf 9s linear infinite; animation-delay: var(--dl, 0s);
        }
        .dpa-fx--cf:nth-child(2n) { background: #fff6dc; width: 4px; height: 7px; animation-duration: 12s; }
        .dpa-fx--tw {
            width: var(--sz, 24px); height: var(--sz, 24px); margin: calc(var(--sz, 24px) / -2) 0 0 calc(var(--sz, 24px) / -2);
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,244,205,.95) 0%, rgba(255,244,205,.35) 22%, rgba(255,244,205,0) 62%);
            opacity: .15; animation: dpa-tw 3.8s ease-in-out infinite; animation-delay: var(--dl, 0s);
        }
        .dpa-fx--gl {
            width: var(--sz, 100px); height: var(--sz, 100px); margin: calc(var(--sz, 100px) / -2) 0 0 calc(var(--sz, 100px) / -2);
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,196,96,.5) 0%, rgba(255,196,96,.16) 40%, rgba(255,196,96,0) 70%);
            opacity: .45; animation: dpa-gl 5.2s ease-in-out infinite; animation-delay: var(--dl, 0s);
        }
        .dpa-fx--rise {
            width: 5px; height: 5px; border-radius: 50%; background: #ffe9a8; opacity: 0;
            animation: dpa-rise 12s ease-in infinite; animation-delay: var(--dl, 0s);
        }
        @keyframes dpa-snow {
            0%   { transform: translate3d(0, 0, 0); opacity: 0; }
            8%   { opacity: .85; }
            90%  { opacity: .7; }
            100% { transform: translate3d(26px, 66vh, 0); opacity: 0; }
        }
        @keyframes dpa-conf {
            0%   { transform: translate3d(0, 0, 0) rotate(0deg); opacity: 0; }
            10%  { opacity: .8; }
            100% { transform: translate3d(-24px, 62vh, 0) rotate(420deg); opacity: 0; }
        }
        @keyframes dpa-tw {
            0%, 100% { opacity: .1; transform: scale(.7); }
            50%      { opacity: .85; transform: scale(1.15); }
        }
        @keyframes dpa-gl {
            0%, 100% { opacity: .3; transform: scale(.92); }
            50%      { opacity: .75; transform: scale(1.08); }
        }
        @keyframes dpa-rise {
            0%   { transform: translate3d(0, 0, 0); opacity: 0; }
            15%  { opacity: .75; }
            100% { transform: translate3d(14px, -44vh, 0); opacity: 0; }
        }
    }
</style>
@endif
