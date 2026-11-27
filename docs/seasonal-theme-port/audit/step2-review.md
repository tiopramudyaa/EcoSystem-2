# Audit Step 2: kait tema di dashboard.blade.php (HEAD 8ba22b15)

## 1. Hunk diff (git diff HEAD~1 HEAD -- resources/views/dashboard.blade.php)
| # | Baris baru | Isi | Nilai |
|---|---|---|---|
| 1 | 2 | `<html lang="en"@if(($seasonalTheme['key'] ?? 'none') !== 'none') data-season=...@endif>` | PASS (kait tema) |
| 2 | 46-48 | default `seasonal_theme` 'none' -> 'default' | PASS dengan catatan R1 |
| 3 | 58-61 | `$seasonalTheme = $seasonalTheme ?? [...]`, `$effectiveSeasonalTheme = $seasonalTheme['key']` | PASS |
| 4 | 104-106 | `@include('partials.seasonal-theme-vars')` | PASS (partial hanya mencetak bila key != none) |
| 5 | 165-177 | CSS `html[data-season] #sidebar...` | PASS |
| 6 | 804-814 | include seasonal-ui-remap dan seasonal-accents dalam @if | PASS |
| 7 | 817-821 | include seasonal-icons dalam @if | PASS |
| 8 | (hapus) | blok @php effectiveFor di sidebar dipindah ke atas | PASS |
| 9 | 1676-1682 | seasonal-theme placement header diganti seasonal-navbar, tetap dalam @if | PASS |
Tidak ada perubahan markup/JS/query non-tema. Perubahan whitespace: baris kosong `-    ` jadi kosong.

## 2. Tema none
- `<html lang="en">` identik (atribut hanya bila key != none).
- seasonal-theme-vars: @php lalu `@if(key!=='none' && is_array(palette))` sehingga hanya whitespace. 
- ui-remap: hanya bila key != none ATAU routeIs('settings.*'); di Settings dengan none ada ~5 KB CSS inert (semua selektor html[data-season]). Bukan perubahan visual. (R2)
- accents, icons, navbar: dalam @if key != none. PASS (output tambahan hanya whitespace/baris kosong).

## 3. Aliran $seasonalTheme
- AppServiceProvider.php:180 `View::composer(['dashboard','auth.*'], SeasonalThemeComposer::class)`; composer memanggil `$view->with('seasonalTheme', build())`.
- 97 halaman @extends('dashboard'): @extends me-render view 'dashboard' lewat factory sehingga composer terpicu. Tidak ada `view('dashboard')` langsung.
- Fallback: dashboard.blade.php:60 `$seasonalTheme ?? [none]`; build() sendiri catch Throwable -> none.
- Urutan: $effectiveSeasonalTheme didefinisikan baris 61 (head @php), dipakai baris 819, 830, 1676 (sesudahnya). $preferences didefinisikan baris 37 sebelum dipakai di 106/808-813. PASS.
- Catatan: build() untuk sesi tanpa user memakai forAuthPage (tema login admin), bukan active(); dashboard butuh login jadi tidak relevan. (R3)

## 4. CSS
- `html[data-season] #sidebar.primary-gradient/.primary-solid` (dashboard.blade.php:167-177): spesifisitas (1,2,1) mengalahkan `.primary-gradient !important` (0,1,0) bila sama-sama !important; tanpa data-season tidak cocok. PASS.
- seasonal-ui-remap: semua selektor berawalan html[data-season]. seasonal-accents: dalam @if in_array(key) (accents.blade:39). navbar dan icons memakai @once. PASS.

## 5. Query
- GlobalSeasonalTheme::all() dimemo di container (GlobalSeasonalTheme.php:123-148). Pemanggilan all() per request dashboard: composer (effectiveFor->active, soundUrl, soundVolume = 3), dashboard.blade.php:54-55 (2), sehingga >=5 panggilan tapi hanya 1 AppConfig::get('seasonal_theme.global') -> Cache::remember 300 dtk (AppConfig.php:15). CACHE_STORE=database di .env: 1 SELECT tabel cache per request (miss: +1 SELECT app_configs), SUDAH ADA sebelum commit karena soundUrl() dipanggil di HEAD~1. Tambahan baru dari commit ini: 0 query. Bila pref user bukan 'default', all() masih dipanggil lewat soundUrl. PASS.

## 6. JS dan header
- Tidak ada JS yang memilih header/anak pertama header (grep dashboard.blade.php dan public/js: hanya `.ql-header`/`div.header` tak terkait). Partial lama (seasonal-theme placement header) dan baru (seasonal-navbar) sama-sama disisipkan sebagai anak pertama `<header>` hanya saat tema aktif; navbar `position:absolute; z-index:-1; pointer-events:none`, tidak ikut alur (komentar partial). PASS.

## Ringkasan
PASS 1-6. Risiko:
- R1 (sedang): default preferensi 'none' -> 'default' mengubah perilaku sesi tanpa user_preferences: dulu tak pernah tampil tema admin, kini ikut admin. Tema none/admin none identik; ini penyimpangan yang disengaja (sesuai komentar) tetapi melanggar "hanya tema yang berubah" bila ditafsirkan ketat, dan sudah dicocokkan dengan sumber? Perlu konfirmasi pemilik.
- R2 (rendah): ui-remap termuat di settings.* walau none (CSS inert).
- R3 (rendah): build() cabang tanpa user; dan bergantung pada session('user') format array/non-array.
- R4 (rendah): navbar absolute z-index:-1 bergantung header sticky z-40 sebagai stacking context; bila header diubah, hiasan bisa jatuh di bawah latar.
- R5 (info): cache database tetap 1 SELECT per request (pre-existing).
- Diff terhadap origin/aldy_merge tampak besar (4810 baris) karena beda ujung baris/layout, bukan diaudit baris per baris.
