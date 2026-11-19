{{--
    Sprite SVG tema musiman (inline, tersembunyi). Satu kali per halaman (@once).

    Dua keluarga symbol, semuanya viewBox 0 0 64 64:
      st-icon-KEY   ikon utama tema (natal, tahun_baru, ramadan, lebaran, imlek, hut_ri),
                    dipakai kartu pilihan tema.
      st-orn-NAMA   ornamen untuk dekorasi (nama = SeasonalThemes::CATALOG[*]['ornaments']).

    Pewarnaan duotone: bentuk utama memakai currentColor (diatur lewat `color` pada
    elemen <svg class="st-icon">), nada aksen memakai var(--st-accent, #f2c14e).
    Render lewat partial partials/seasonal-icon.blade.php.
--}}
@once
<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute;width:0;height:0;overflow:hidden" aria-hidden="true" focusable="false">
    <style>
        .st-a { fill: var(--st-accent, #f2c14e); }
        .st-as { fill: none; stroke: var(--st-accent, #f2c14e); stroke-linecap: round; stroke-linejoin: round; }
        .st-c { fill: currentColor; }
        .st-cs { fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; }
    </style>
    <defs>
        <path id="st-p-star5" d="M0 -10 L2.35 -3.24 L9.51 -3.09 L3.8 1.24 L5.88 8.09 L0 4 L-5.88 8.09 L-3.8 1.24 L-9.51 -3.09 L-2.35 -3.24 Z"/>
        <path id="st-p-spark4" d="M0 -10 C1 -3 3 -1 10 0 C3 1 1 3 0 10 C-1 3 -3 1 -10 0 C-3 -1 -1 -3 0 -10 Z"/>
    </defs>

    {{-- ===================== IKON UTAMA ===================== --}}

    {{-- Natal: pohon cemara bertingkat, bintang, bola hias --}}
    <symbol id="st-icon-natal" viewBox="0 0 64 64">
        <rect class="st-a" x="28" y="52" width="8" height="8" rx="1.5" opacity=".75"/>
        <path class="st-c" d="M32 12 L44 29 H20 Z"/>
        <path class="st-c" d="M32 21 L48 41 H16 Z"/>
        <path class="st-c" d="M32 31 L53 53 H11 Z"/>
        <path d="M32 12 L44 29 H32 Z M32 21 L48 41 H32 Z M32 31 L53 53 H32 Z" fill="#fff" opacity=".12"/>
        <path class="st-as" stroke-width="1.6" d="M22 31 Q32 35 42 31 M18 43 Q32 48 46 43" opacity=".9"/>
        <circle class="st-a" cx="26" cy="38" r="2.4"/>
        <circle class="st-a" cx="38" cy="47" r="2.6"/>
        <circle class="st-a" cx="24" cy="49" r="2"/>
        <circle class="st-a" cx="36" cy="28" r="1.8"/>
        <use href="#st-p-star5" class="st-a" transform="translate(32 9) scale(.78)"/>
    </symbol>

    {{-- Tahun Baru: ledakan kembang api dan percikan --}}
    <symbol id="st-icon-tahun_baru" viewBox="0 0 64 64">
        <g class="st-cs" stroke-width="3">
            <path d="M28 30 V8 M28 30 V52 M28 30 H6 M28 30 H50 M28 30 L12.4 14.4 M28 30 L43.6 14.4 M28 30 L12.4 45.6 M28 30 L43.6 45.6"/>
        </g>
        <circle class="st-a" cx="28" cy="6" r="2.6"/>
        <circle class="st-a" cx="28" cy="54" r="2.6"/>
        <circle class="st-a" cx="4" cy="30" r="2.6"/>
        <circle class="st-a" cx="52" cy="30" r="2.6"/>
        <circle class="st-a" cx="11" cy="13" r="2.2"/>
        <circle class="st-a" cx="45" cy="13" r="2.2"/>
        <circle class="st-a" cx="11" cy="47" r="2.2"/>
        <circle class="st-a" cx="45" cy="47" r="2.2"/>
        <circle class="st-c" cx="28" cy="30" r="5"/>
        <circle class="st-a" cx="28" cy="30" r="2.3"/>
        <g class="st-as" stroke-width="2.4">
            <path d="M54 44 V56 M48 50 H60 M49.8 45.8 L58.2 54.2 M58.2 45.8 L49.8 54.2"/>
        </g>
        <use href="#st-p-spark4" class="st-a" transform="translate(54 12) scale(.5)"/>
    </symbol>

    {{-- Ramadan: bulan sabit, bintang, dan lampu fanous menggantung --}}
    <symbol id="st-icon-ramadan" viewBox="0 0 64 64">
        <path class="st-a" d="M34 12 A21 21 0 1 0 34 56 A26 26 0 0 1 34 12 Z"/>
        <use href="#st-p-star5" class="st-a" transform="translate(40 44) scale(.5) rotate(12)"/>
        <g>
            <path class="st-cs" stroke-width="1.6" d="M50 2 V10"/>
            <circle class="st-cs" stroke-width="1.6" cx="50" cy="11.5" r="2"/>
            <path class="st-c" d="M44.5 21 C44.5 17 50 14 50 14 C50 14 55.5 17 55.5 21 Z"/>
            <path class="st-c" d="M45 21 H55 C56 27 56 31 55 36 H45 C44 31 44 27 45 21 Z"/>
            <path class="st-a" d="M47.6 34 V26.5 C47.6 23.5 52.4 23.5 52.4 26.5 V34 Z"/>
            <path fill="currentColor" d="M44.5 36 H55.5 L53.6 41.5 H46.4 Z"/>
            <circle class="st-a" cx="50" cy="44" r="1.6"/>
        </g>
    </symbol>

    {{-- Lebaran: ketupat anyaman dan siluet kubah masjid --}}
    <symbol id="st-icon-lebaran" viewBox="0 0 64 64">
        <g class="st-c" opacity=".7">
            <path d="M30 58 V41 C30 31 36 26 44 26 C52 26 58 31 58 41 V58 Z"/>
            <rect x="57" y="30" width="4" height="28" rx="1"/>
            <path d="M57 30 L59 25 L61 30 Z"/>
            <rect x="43.3" y="14" width="1.4" height="12"/>
        </g>
        <circle class="st-a" cx="44" cy="13" r="2.4"/>
        <circle fill="#000" opacity=".22" cx="44.9" cy="12.4" r="2"/>
        <path class="st-a" d="M36 58 V50 C36 46 40 46 40 50 V58 Z M48 58 V50 C48 46 52 46 52 50 V58 Z" opacity=".85"/>
        <g>
            <path class="st-as" stroke-width="1.6" d="M22 4 V10"/>
            <path class="st-c" d="M22 10 L38 30 L22 50 L6 30 Z"/>
            <g class="st-as" stroke-width="1.5" fill="none">
                <path d="M22 10 L38 30 L22 50 L6 30 Z"/>
                <path d="M14 20 L30 40 M22 10 L38 30 M6 30 L22 50 M30 20 L14 40 M22 10 L6 30 M38 30 L22 50"/>
                <path d="M18 15 L34 35 M26 15 L10 35 M10 25 L26 45 M34 25 L18 45"/>
            </g>
            <path class="st-as" stroke-width="1.6" d="M18 52 L16 61 M22 50 V62 M26 52 L28 61"/>
        </g>
    </symbol>

    {{-- Imlek: lampion merah dengan jumbai --}}
    <symbol id="st-icon-imlek" viewBox="0 0 64 64">
        <path class="st-as" stroke-width="2" d="M32 2 V9"/>
        <rect class="st-a" x="23" y="9" width="18" height="5" rx="1.6"/>
        <ellipse class="st-c" cx="32" cy="30" rx="21" ry="17"/>
        <path d="M32 13 C48 21 48 39 32 47 C38 40 38 20 32 13 Z" fill="#000" opacity=".18"/>
        <g class="st-as" stroke-width="1.5" opacity=".9">
            <path d="M32 13 C24 20 24 40 32 47 C40 40 40 20 32 13 Z"/>
            <path d="M32 13 C16 21 16 39 32 47 M32 13 C48 21 48 39 32 47"/>
        </g>
        <path d="M14 22 C16 17 20 14 24 12" fill="none" stroke="#fff" stroke-opacity=".3" stroke-width="2" stroke-linecap="round"/>
        <rect class="st-a" x="23" y="46" width="18" height="5" rx="1.6"/>
        <path class="st-as" stroke-width="2" d="M32 51 V56"/>
        <circle class="st-a" cx="32" cy="57" r="2.4"/>
        <path class="st-as" stroke-width="1.8" d="M28.5 59 L27 63 M32 59.5 V63.5 M35.5 59 L37 63"/>
    </symbol>

    {{-- HUT RI: bendera berkibar pada tiang --}}
    <symbol id="st-icon-hut_ri" viewBox="0 0 64 64">
        <circle class="st-a" cx="14" cy="6" r="3.2"/>
        <rect class="st-c" x="12.5" y="8" width="3" height="52" rx="1.5" opacity=".85"/>
        <path class="st-a" d="M10 58 H20 L18 62 H12 Z" opacity=".8"/>
        <path class="st-c" d="M15.5 10 C23 6 29 14 37 10 C45 6 50 8 55 10 V28 H15.5 Z"/>
        <path class="st-a" d="M15.5 28 H55 V46 C50 44 45 42 37 46 C29 50 23 42 15.5 46 Z"/>
        <path d="M15.5 10 C23 6 29 14 37 10 C45 6 50 8 55 10 V16 C50 14 45 12 37 16 C29 20 23 12 15.5 16 Z" fill="#fff" opacity=".14"/>
    </symbol>

    {{-- ===================== ORNAMEN ===================== --}}

    <symbol id="st-orn-snowflake" viewBox="0 0 64 64">
        <g class="st-cs" stroke-width="3">
            <path id="st-snow-arm" d="M32 6 V58 M32 14 L25 9 M32 14 L39 9 M32 50 L25 55 M32 50 L39 55"/>
            <use href="#st-snow-arm" transform="rotate(60 32 32)"/>
            <use href="#st-snow-arm" transform="rotate(120 32 32)"/>
        </g>
        <circle class="st-a" cx="32" cy="32" r="3.4"/>
    </symbol>

    <symbol id="st-orn-bauble" viewBox="0 0 64 64">
        <path class="st-cs" stroke-width="2.4" d="M32 3 V12"/>
        <rect class="st-a" x="26" y="11" width="12" height="7" rx="2"/>
        <circle class="st-c" cx="32" cy="38" r="21"/>
        <path class="st-as" stroke-width="3" d="M13 33 Q32 44 51 33 M14 44 Q32 55 50 44" opacity=".9"/>
        <path d="M20 26 C22 21 26 18 30 17" fill="none" stroke="#fff" stroke-opacity=".4" stroke-width="3" stroke-linecap="round"/>
    </symbol>

    <symbol id="st-orn-star" viewBox="0 0 64 64">
        <use href="#st-p-star5" class="st-c" transform="translate(32 33) scale(3)"/>
        <use href="#st-p-star5" class="st-a" transform="translate(32 33) scale(1.7)"/>
    </symbol>

    <symbol id="st-orn-bell" viewBox="0 0 64 64">
        <path class="st-as" stroke-width="3" d="M32 5 C28 5 26 8 28 11 M32 5 C36 5 38 8 36 11"/>
        <path class="st-c" d="M32 11 C21 11 17 22 17 33 C17 41 13 44 10 48 H54 C51 44 47 41 47 33 C47 22 43 11 32 11 Z"/>
        <path class="st-as" stroke-width="2.6" d="M18 40 H46" opacity=".9"/>
        <path d="M24 20 C22 25 22 30 22 34" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width="3" stroke-linecap="round"/>
        <circle class="st-a" cx="32" cy="53" r="5.5"/>
    </symbol>

    <symbol id="st-orn-firework" viewBox="0 0 64 64">
        <g class="st-cs" stroke-width="3">
            <path d="M32 32 V8 M32 32 V56 M32 32 H8 M32 32 H56 M32 32 L15 15 M32 32 L49 15 M32 32 L15 49 M32 32 L49 49"/>
        </g>
        <g class="st-a">
            <circle cx="32" cy="6" r="3"/><circle cx="32" cy="58" r="3"/>
            <circle cx="6" cy="32" r="3"/><circle cx="58" cy="32" r="3"/>
            <circle cx="13" cy="13" r="2.4"/><circle cx="51" cy="13" r="2.4"/>
            <circle cx="13" cy="51" r="2.4"/><circle cx="51" cy="51" r="2.4"/>
        </g>
        <circle class="st-a" cx="32" cy="32" r="4"/>
    </symbol>

    <symbol id="st-orn-spark" viewBox="0 0 64 64">
        <use href="#st-p-spark4" class="st-c" transform="translate(30 34) scale(2.7)"/>
        <use href="#st-p-spark4" class="st-a" transform="translate(50 12) scale(1)"/>
        <use href="#st-p-spark4" class="st-a" transform="translate(13 12) scale(.7)"/>
    </symbol>

    <symbol id="st-orn-glass" viewBox="0 0 64 64">
        <path class="st-c" d="M18 8 H46 C46 24 40 33 32 34 C24 33 18 24 18 8 Z"/>
        <path class="st-a" d="M20 15 H44 C43 24 39 30 32 31 C25 30 21 24 20 15 Z" opacity=".85"/>
        <rect class="st-c" x="30" y="34" width="4" height="16"/>
        <path class="st-c" d="M20 58 C20 52 26 50 32 50 C38 50 44 52 44 58 Z"/>
        <circle fill="#fff" opacity=".7" cx="29" cy="24" r="1.6"/><circle fill="#fff" opacity=".7" cx="35" cy="19" r="1.3"/><circle fill="#fff" opacity=".7" cx="34" cy="27" r="1"/>
    </symbol>

    <symbol id="st-orn-crescent" viewBox="0 0 64 64">
        <path class="st-c" d="M36 8 A24 24 0 1 0 36 58 A29 29 0 0 1 36 8 Z"/>
        <use href="#st-p-star5" class="st-a" transform="translate(46 33) scale(.8) rotate(12)"/>
    </symbol>

    <symbol id="st-orn-fanous" viewBox="0 0 64 64">
        <path class="st-cs" stroke-width="2.4" d="M32 2 V9"/>
        <circle class="st-cs" stroke-width="2.4" cx="32" cy="11" r="3"/>
        <path class="st-c" d="M22 24 C22 18 32 14 32 14 C32 14 42 18 42 24 Z"/>
        <path class="st-c" d="M22.5 24 H41.5 C43.5 33 43.5 40 41.5 47 H22.5 C20.5 40 20.5 33 22.5 24 Z"/>
        <path class="st-a" d="M27 45 V33 C27 27 37 27 37 33 V45 Z"/>
        <path class="st-c" d="M21 47 H43 L39 56 H25 Z"/>
        <circle class="st-a" cx="32" cy="59" r="2.6"/>
    </symbol>

    <symbol id="st-orn-ketupat" viewBox="0 0 64 64">
        <path class="st-as" stroke-width="2.4" d="M32 3 V10"/>
        <path class="st-c" d="M32 10 L54 34 L32 58 L10 34 Z"/>
        <g class="st-as" stroke-width="2" fill="none">
            <path d="M21 22 L43 46 M43 22 L21 46 M32 10 L54 34 M32 10 L10 34 M32 58 L54 34 M32 58 L10 34"/>
            <path d="M26.5 16 L48.5 40 M37.5 16 L15.5 40"/>
        </g>
        <circle class="st-a" cx="32" cy="34" r="3"/>
    </symbol>

    <symbol id="st-orn-mosque" viewBox="0 0 64 64">
        <rect class="st-c" x="6" y="22" width="6" height="36" rx="1.5"/>
        <path class="st-c" d="M6 22 L9 14 L12 22 Z"/>
        <rect class="st-c" x="52" y="22" width="6" height="36" rx="1.5"/>
        <path class="st-c" d="M52 22 L55 14 L58 22 Z"/>
        <path class="st-c" d="M16 58 V40 C16 28 24 22 32 22 C40 22 48 28 48 40 V58 Z"/>
        <rect class="st-c" x="31.2" y="10" width="1.6" height="12"/>
        <circle class="st-a" cx="32" cy="9" r="3"/>
        <path class="st-a" d="M26 58 V46 C26 41 38 41 38 46 V58 Z"/>
    </symbol>

    <symbol id="st-orn-star8" viewBox="0 0 64 64">
        <rect class="st-c" x="12" y="12" width="40" height="40" rx="2"/>
        <rect class="st-a" x="12" y="12" width="40" height="40" rx="2" transform="rotate(45 32 32)"/>
        <rect class="st-c" x="22" y="22" width="20" height="20" rx="1" transform="rotate(45 32 32)" opacity=".92"/>
        <circle class="st-a" cx="32" cy="32" r="4.4"/>
    </symbol>

    <symbol id="st-orn-lantern" viewBox="0 0 64 64">
        <path class="st-cs" stroke-width="2.4" d="M32 2 V8"/>
        <rect class="st-a" x="23" y="8" width="18" height="5" rx="1.6"/>
        <ellipse class="st-c" cx="32" cy="29" rx="20" ry="16"/>
        <g class="st-as" stroke-width="1.6" opacity=".9">
            <path d="M32 13 C25 20 25 38 32 45 C39 38 39 20 32 13 Z M32 13 C17 20 17 38 32 45 M32 13 C47 20 47 38 32 45"/>
        </g>
        <rect class="st-a" x="23" y="44" width="18" height="5" rx="1.6"/>
        <path class="st-as" stroke-width="2" d="M32 49 V55"/>
        <circle class="st-a" cx="32" cy="56" r="2.4"/>
        <path class="st-as" stroke-width="1.8" d="M28.5 58 L27 62 M32 58.5 V62.5 M35.5 58 L37 62"/>
    </symbol>

    <symbol id="st-orn-coin" viewBox="0 0 64 64">
        <circle class="st-a" cx="32" cy="32" r="26"/>
        <circle class="st-cs" stroke-width="2.4" cx="32" cy="32" r="21" opacity=".55"/>
        <rect class="st-c" x="23" y="23" width="18" height="18" rx="2"/>
        <rect fill="#fff" opacity=".18" x="23" y="23" width="18" height="6" rx="2"/>
        <path class="st-cs" stroke-width="2" d="M32 8 V13 M32 51 V56 M8 32 H13 M51 32 H56" opacity=".5"/>
    </symbol>

    <symbol id="st-orn-fu" viewBox="0 0 64 64">
        <rect class="st-c" x="12" y="12" width="40" height="40" rx="3" transform="rotate(45 32 32)"/>
        <rect class="st-as" stroke-width="2" x="17" y="17" width="30" height="30" rx="2" transform="rotate(45 32 32)" opacity=".9"/>
        <path class="st-as" stroke-width="3.4" d="M20 24 H44 M32 18 V46 M22 32 H42 M26 42 H38"/>
        <circle class="st-a" cx="32" cy="32" r="3"/>
        <path class="st-as" stroke-width="2" d="M32 2 V6 M32 58 V62" opacity=".8"/>
    </symbol>

    <symbol id="st-orn-cloud" viewBox="0 0 64 64">
        <path class="st-c" d="M12 42 C6 42 4 35 9 32 C8 26 15 22 20 26 C22 18 33 16 38 22 C42 18 52 20 52 28 C58 28 60 38 54 41 C52 46 44 46 42 42 Z"/>
        <path class="st-as" stroke-width="2.4" d="M18 34 C18 30 24 29 25 33 C26 36 21 38 20 35 M32 31 C32 27 39 27 39 32 C39 36 34 36 34 33 M44 36 C44 33 49 33 49 36"/>
        <path class="st-as" stroke-width="2.6" d="M12 52 C20 56 28 48 36 52 S50 54 56 50" opacity=".85"/>
    </symbol>

    <symbol id="st-orn-flag" viewBox="0 0 64 64">
        <rect class="st-c" x="10" y="6" width="4" height="54" rx="2" opacity=".85"/>
        <circle class="st-a" cx="12" cy="6" r="3.4"/>
        <path class="st-c" d="M14 9 C22 5 30 13 38 9 C46 5 52 8 58 10 V28 H14 Z"/>
        <path class="st-a" d="M14 28 H58 V46 C52 44 46 41 38 45 C30 49 22 41 14 45 Z"/>
    </symbol>

    <symbol id="st-orn-bamboo" viewBox="0 0 64 64">
        <g class="st-c">
            <rect x="14" y="4" width="9" height="17" rx="2"/><rect x="14" y="23" width="9" height="17" rx="2"/><rect x="14" y="42" width="9" height="18" rx="2"/>
            <rect x="38" y="10" width="9" height="15" rx="2" opacity=".85"/><rect x="38" y="27" width="9" height="15" rx="2" opacity=".85"/><rect x="38" y="44" width="9" height="16" rx="2" opacity=".85"/>
        </g>
        <g class="st-a">
            <rect x="12" y="20" width="13" height="3" rx="1.5"/><rect x="12" y="39" width="13" height="3" rx="1.5"/>
            <rect x="36" y="25" width="13" height="3" rx="1.5"/><rect x="36" y="42" width="13" height="3" rx="1.5"/>
            <path d="M23 24 C32 17 40 17 48 12 C42 22 34 24 23 24 Z"/>
            <path d="M47 28 C52 25 56 25 60 21 C57 29 52 30 47 28 Z"/>
        </g>
    </symbol>

    <symbol id="st-orn-ribbon" viewBox="0 0 64 64">
        <path class="st-c" d="M26 30 L8 18 V44 L26 36 Z" opacity=".9"/>
        <path class="st-c" d="M38 30 L56 18 V44 L38 36 Z" opacity=".9"/>
        <path class="st-c" d="M26 36 L16 60 L24 56 L29 62 L34 38 Z" opacity=".75"/>
        <path class="st-c" d="M38 36 L48 60 L40 56 L35 62 L30 38 Z" opacity=".75"/>
        <rect class="st-a" x="25" y="23" width="14" height="16" rx="4"/>
        <path class="st-cs" stroke-width="1.6" d="M29 26 V36 M35 26 V36" opacity=".4"/>
    </symbol>
</svg>
@endonce
