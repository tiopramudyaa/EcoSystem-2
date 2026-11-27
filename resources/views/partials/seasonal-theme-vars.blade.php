{{--
    CSS variable tema musiman, dicetak hanya untuk tema efektif (bukan none).
    Memakai $seasonalTheme dari SeasonalThemeComposer (key, meta). Variabel hanya
    berlaku bila <html> membawa data-season yang sama, jadi tema 'none' tampil
    persis seperti sebelumnya.

    Variabel:
      --st-primary-rgb, --st-primary-dark-rgb   triplet "R G B" (dipakai rgb(var(...)))
      --st-accent                               warna aksen (garis aktif sidebar dll)
      --st-login-from, --st-login-mid, --st-login-to   gradient panel login (atas, tengah, bawah)
      --st-button, --st-button-text, --st-button-hover   tombol Sign in (default login_to, teks putih)
      --st-link                                 warna tautan dan label di atas putih (default sama dengan tombol)
      --st-loading-bottom                       warna bawah langit loading screen

    Selain --st-*, di sini juga ditimpa --primary-rgb, --primary-dark-rgb, --primary-color
    dan --primary-surface (didefinisikan di :root dashboard.blade.php) supaya semua yang
    memakai variabel itu (sidebar, .primary-*, tombol, scrollbar) ikut warna musim.
    Penulisan memakai koma ("R, G, B") seperti :root layout, karena rgba(var(--primary-rgb), .3)
    hanya sah dengan koma. Selector :root[data-season] (0,2,0) menang atas :root (0,1,0).
    Tanpa data-season tidak ada yang berubah.
--}}
@php
    $stVarsKey = $seasonalTheme['key'] ?? 'none';
    $stVarsPalette = $seasonalTheme['meta']['palette'] ?? null;
    $stCommaRgb = fn (string $v) => implode(', ', preg_split('/[\s,]+/', trim($v)));
    $stSidebarStyle = ($preferences['sidebar_style'] ?? 'gradient');
@endphp
@if($stVarsKey !== 'none' && is_array($stVarsPalette))
<style>
    :root[data-season="{{ $stVarsKey }}"] {
        --st-primary-rgb: {{ $stVarsPalette['primary_rgb'] }};
        --st-primary-dark-rgb: {{ $stVarsPalette['primary_dark_rgb'] }};
        --st-accent: {{ $stVarsPalette['accent'] }};
        --st-login-from: {{ $stVarsPalette['login_from'] }};
        --st-login-mid: {{ $stVarsPalette['login_mid'] ?? $stVarsPalette['login_to'] }};
        --st-login-to: {{ $stVarsPalette['login_to'] }};
        --st-button: {{ $stVarsPalette['button'] ?? $stVarsPalette['login_to'] }};
        --st-button-text: {{ $stVarsPalette['button_text'] ?? '#ffffff' }};
        --st-button-hover: {{ $stVarsPalette['button_hover'] ?? ($stVarsPalette['button'] ?? $stVarsPalette['login_to']) }};
        --st-link: {{ $stVarsPalette['link'] ?? ($stVarsPalette['button'] ?? $stVarsPalette['login_to']) }};
        --st-loading-bottom: {{ $stVarsPalette['loading_bottom'] ?? ($stVarsPalette['login_mid'] ?? $stVarsPalette['login_to']) }};
        --primary-rgb: {{ $stCommaRgb($stVarsPalette['primary_rgb']) }};
        --primary-dark-rgb: {{ $stCommaRgb($stVarsPalette['primary_dark_rgb']) }};
        --primary-color: rgb({{ $stCommaRgb($stVarsPalette['primary_rgb']) }});
        --primary-surface: {{ $stSidebarStyle === 'gradient'
            ? 'linear-gradient(135deg, rgb(var(--primary-dark-rgb)), rgb(var(--primary-rgb)))'
            : 'rgb(var(--primary-rgb))' }};
    }
</style>
@endif
