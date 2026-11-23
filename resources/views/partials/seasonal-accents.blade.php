{{--
    Aksen dekoratif tema musiman untuk sidebar, header, dan kartu ringkasan di dashboard home (fase 1).
    Hanya CSS: tanpa JavaScript, tanpa query. Hanya CSS tema yang aktif yang dicetak, dan semua aturan
    dibatasi di html[data-season="..."], jadi tanpa tema tampilan persis seperti sebelumnya.

    Daftar putih selector (sesuai markup nyata, bukan selector luas):
      - #sidebar, #sidebarHeader, #sidebar a.nav-link.active
      - #mainContent > header
      - kartu ringkasan: section[aria-label="Company overview"], "Tickets", "Recent activity" > div.rounded-xl

    Aturan: hanya dekorasi (bukan warna teks), tidak menyentuh tabel, form, modal, grafik, warna status atau bahaya,
    print, maupun ekspor. Kartu memakai background-image (tanpa mengubah position atau tinggi); pseudo-element
    selalu absolute di sidebar/header yang memang sudah positioned, jadi tanpa CLS. Tanpa backdrop-filter, tanpa
    filter di wadah besar, tanpa animasi box-shadow. Gerak (hanya hover glint tahun baru) memakai transform dan
    opacity, dan hanya bila Show animations aktif dan prefers-reduced-motion: no-preference.
    z-index: pseudo sidebar/header hanya z-index 1 di dalam stacking context sidebar (z-50) dan header (z-40),
    lapisan bintik salju body z-index -1; tidak bertabrakan dengan dropdown, modal, atau toast.

    Param: $themeKey (tema efektif), $dark (bool), $showAnimations (bool).
--}}
@php
    $acKey = $themeKey ?? 'none';
    $acDark = (bool) ($dark ?? false);
    $acAnimate = (bool) ($showAnimations ?? true);
    $S = 'html[data-season="' . $acKey . '"]';
    $imp = $acDark ? ' !important' : '';
    $cardSel = [
        '#mainContent section[aria-label="Company overview"] > div.rounded-xl',
        '#mainContent section[aria-label="Tickets"] > div.rounded-xl',
        '#mainContent section[aria-label="Recent activity"] > div.rounded-xl',
    ];
    $sel = static fn (array $list, string $suffix = ''): string => implode(",\n    ", array_map(static fn (string $s): string => $S . ' ' . $s . $suffix, $list));
    $cards = $sel($cardSel);
    $hdr = $S . ' #mainContent > header';
    $sbh = $S . ' #sidebarHeader';
    $act = $S . ' #sidebar a.nav-link.active';
    $uri = static fn (string $svg): string => 'url("data:image/svg+xml,' . rawurlencode($svg) . '")';
@endphp
@if(in_array($acKey, ['natal', 'tahun_baru', 'ramadan', 'lebaran', 'imlek', 'hut_ri'], true))
<style>
@switch($acKey)
@case('natal')
@php
    $iceLine = $acDark ? 'rgba(190, 235, 215, .24)' : 'rgba(140, 195, 170, .55)';
    $iceGlow = $acDark ? 'rgba(190, 235, 215, .16)' : 'rgba(200, 238, 222, .5)';
    $iceStroke = $acDark ? '%23d6f2e4' : '%2389bfa4';
    $crystal = 'url("data:image/svg+xml,' . "%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 28 28'%3E%3Cg stroke='{$iceStroke}' stroke-width='.8' stroke-linecap='round' fill='none' opacity='.8'%3E%3Cpath d='M28 0L16 12M28 0L28 14M28 0L14 0M22 6L22 11M22 6L17 6M16 12L16 17M16 12L11 12M25 3L25 6M25 3L21 3'/%3E%3C/g%3E%3C/svg%3E" . '")';
    $spk = $acDark ? 'rgba(255, 255, 255, .22)' : 'rgba(110, 165, 135, .34)';
    $spkSb = 'rgba(255, 255, 255, .2)';
@endphp
    /* Kartu ringkasan: garis tepi hijau es dan sudut beku kecil di kanan atas. */
    {!! $cards !!} {
        border-color: {{ $iceLine }}{{ $imp }};
        background-image: {!! $crystal !!}, radial-gradient(circle at 100% 0, {{ $iceGlow }} 0, transparent 38px);
        background-repeat: no-repeat;
        background-position: right 0 top 0, 0 0;
        background-size: 28px 28px, 100% 100%;
    }
    {!! $hdr !!} { border-bottom-color: {{ $iceLine }}{{ $imp }}; }
    /* Sidebar: garis rambut es di tepi kanan dan bintik salju halus di header sidebar. */
    {!! $S !!} #sidebar::after {
        content: ''; position: absolute; top: 0; right: 0; bottom: 0; width: 1px; z-index: 1; pointer-events: none;
        background: linear-gradient(180deg, transparent, rgba(214, 242, 228, .55) 18%, rgba(214, 242, 228, .55) 82%, transparent);
    }
    {!! $sbh !!} {
        background-image: radial-gradient(circle at 18px 22px, {{ $spkSb }} 0 1px, transparent 1.7px), radial-gradient(circle at 74px 54px, {{ $spkSb }} 0 1.2px, transparent 1.9px), radial-gradient(circle at 128px 12px, {{ $spkSb }} 0 .9px, transparent 1.5px);
        background-size: 150px 70px;
    }
    /* Tutup salju tipis di item menu aktif (tinggi 5px, tidak menutup teks). */
    {!! $act !!}::after {
        content: ''; position: absolute; top: 0; left: 10px; right: 10px; height: 5px; pointer-events: none;
        border-radius: 0 0 4px 4px; opacity: .9;
        background:
            radial-gradient(ellipse 13px 4px at 14% 0, #fff 94%, transparent),
            radial-gradient(ellipse 8px 3px at 38% 0, #fff 94%, transparent),
            radial-gradient(ellipse 16px 4.5px at 64% 0, #fff 94%, transparent),
            radial-gradient(ellipse 9px 3px at 90% 0, #fff 94%, transparent),
            linear-gradient(#fff, #fff) 0 0 / 100% 1.5px no-repeat;
    }
    /* Bintik salju statis di latar halaman: lapisan fixed, tidak bisa diklik, di bawah semua konten. */
    {!! $S !!} body::before {
        content: ''; position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: -1; pointer-events: none; opacity: .45; contain: strict;
        background-image: radial-gradient(circle at 20px 30px, {{ $spk }} 0 1px, transparent 1.7px), radial-gradient(circle at 85px 70px, {{ $spk }} 0 1.3px, transparent 2px), radial-gradient(circle at 140px 15px, {{ $spk }} 0 .9px, transparent 1.5px), radial-gradient(circle at 60px 120px, {{ $spk }} 0 1.1px, transparent 1.8px);
        background-size: 170px 150px;
    }
@break
@case('tahun_baru')
    /* Kartu ringkasan: garis kilau emas di sisi atas. */
    {!! $cards !!} {
        background-image: linear-gradient(90deg, transparent, rgba(224, 177, 58, .9) 50%, transparent);
        background-repeat: no-repeat; background-position: 0 0; background-size: 100% 2px;
    }
    {!! $hdr !!}::after {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1.5px; pointer-events: none; opacity: .85;
        background: linear-gradient(90deg, transparent, #e0b13a 20%, #f6d36a 50%, #e0b13a 80%, transparent);
    }
    {!! $sbh !!} {
        background-image: linear-gradient(90deg, transparent, rgba(246, 211, 106, .7), transparent);
        background-repeat: no-repeat; background-position: 0 100%; background-size: 100% 1px;
    }
    @if($acAnimate)
    @media (prefers-reduced-motion: no-preference) {
        /* Hover glint: kilau emas melintas di kartu ringkasan (hanya transform dan opacity). */
        {!! $S !!} #mainContent section[aria-label="Company overview"] a.group::after {
            content: ''; position: absolute; top: 0; bottom: 0; left: 0; width: 28%; pointer-events: none; opacity: 0;
            background: linear-gradient(100deg, transparent, rgba(246, 211, 106, .3), transparent);
            transition: transform .7s ease, opacity .7s ease;
        }
        {!! $S !!} #mainContent section[aria-label="Company overview"] a.group:hover::after { opacity: 1; transform: translateX(250%); }
    }
    @endif
@break
@case('ramadan')
@php
    $arab = $uri("<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'><g fill='none' stroke='#fff' stroke-opacity='.06' stroke-width='1'><path d='M20 2L26 14L38 20L26 26L20 38L14 26L2 20L14 14Z'/><circle cx='20' cy='20' r='6'/><path d='M0 0L6 6M40 0L34 6M0 40L6 34M40 40L34 34'/></g></svg>");
@endphp
    /* Header sidebar: pola arabesque sangat samar (sekitar 5 persen) dan garis emas tipis di bawahnya. */
    {!! $sbh !!} {
        background-image: linear-gradient(90deg, transparent, rgba(245, 197, 66, .6), transparent), {!! $arab !!};
        background-repeat: no-repeat, repeat; background-position: 0 100%, 0 0; background-size: 100% 1px, 40px 40px;
    }
    {!! $hdr !!}::after {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1.5px; pointer-events: none; opacity: .85;
        background: linear-gradient(90deg, transparent, #d9a21c 20%, #f5c542 50%, #d9a21c 80%, transparent);
    }
@break
@case('lebaran')
@php
    $weave = $uri("<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><g fill='none' stroke='#fff' stroke-opacity='.06' stroke-width='1'><path d='M12 0L24 12L12 24L0 12Z'/><path d='M6 6L18 18M18 6L6 18'/></g></svg>");
@endphp
    /* Header sidebar: anyaman ketupat sangat samar. */
    {!! $sbh !!} {
        background-image: {!! $weave !!};
        background-size: 24px 24px;
    }
    {!! $hdr !!}::after {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1.5px; pointer-events: none; opacity: .85;
        background: linear-gradient(90deg, transparent, #5f8f20 20%, #d4e157 50%, #5f8f20 80%, transparent);
    }
@break
@case('imlek')
@php
    $br = $acDark ? 'rgba(232, 185, 58, .85)' : 'rgba(201, 154, 42, .85)';
    $brl = "linear-gradient({$br}, {$br})";
@endphp
    /* Kartu ringkasan: siku emas di empat sudut (garis 1.5px, panjang 12px, menjorok 6px). */
    {!! $cards !!} {
        background-image: {{ $brl }}, {{ $brl }}, {{ $brl }}, {{ $brl }}, {{ $brl }}, {{ $brl }}, {{ $brl }}, {{ $brl }};
        background-repeat: no-repeat;
        background-position: 6px 6px, 6px 6px, calc(100% - 6px) 6px, calc(100% - 6px) 6px, 6px calc(100% - 6px), 6px calc(100% - 6px), calc(100% - 6px) calc(100% - 6px), calc(100% - 6px) calc(100% - 6px);
        background-size: 12px 1.5px, 1.5px 12px, 12px 1.5px, 1.5px 12px, 12px 1.5px, 1.5px 12px, 12px 1.5px, 1.5px 12px;
    }
    {!! $sbh !!} {
        background-image: linear-gradient(90deg, transparent, rgba(242, 193, 78, .65), transparent);
        background-repeat: no-repeat; background-position: 0 100%; background-size: 100% 1px;
    }
@break
@case('hut_ri')
    /* Header: garis 3px merah putih di sisi atas. */
    {!! $hdr !!}::after {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; pointer-events: none;
        background: linear-gradient(#a80c24 0 2px, #fff 2px);
    }
@break
@endswitch
    /* Tidak dicetak, dan hilang bila pengguna meminta kontras lebih tinggi. */
    @media print, (prefers-contrast: more) {
        {!! $cards !!}, {!! $sbh !!} { background-image: none !important; }
        {!! $hdr !!}::after, {!! $act !!}::after, {!! $S !!} #sidebar::after, {!! $S !!} body::before,
        {!! $S !!} #mainContent section[aria-label="Company overview"] a.group::after { display: none !important; }
    }
</style>
@endif
