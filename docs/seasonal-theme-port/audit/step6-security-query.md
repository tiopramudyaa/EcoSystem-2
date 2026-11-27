# Langkah 6: audit QA query dan keamanan (hanya baca)

Cabang aldy_eco, HEAD e17c8b28, pembanding origin/eco_new (9b30f98c). Tes seasonal: 74 tes, 942 assertion, OK.

## Butir 1: Query. PASS

Cara ukur: DB::listen, cache array, SQLite memori (skrip di scratchpad q.php, q2.php, adm.php) dan MySQL baca-saja untuk render Settings.

| Halaman | Tema none | Tiap tema (natal, tahun_baru, ramadan, lebaran, imlek, hut_ri) |
|---|---|---|
| auth.login, forgot-password, two-factor-challenge, cache dingin | 1 query (SELECT app_configs, sekali) | 1 query |
| idem, cache hangat (AppConfig::get ter-cache) | 0 | 0 |
| settings.index (global x preferensi default/none/natal) | 2 (sama untuk semua kombinasi, bukan dari tema) | 2 |
| admin seasonal-theme GET (termasuk layout dashboard, jadwal 20 baris) | 1 (staging_tickets, bawaan layout) | 1, sama |
| Partial seasonal-icons, ui-remap, loading, preview, poster, icon, theme-vars, accents, navbar, theme, login-art | 0 | 0 |

Pembanding: pada origin/eco_new halaman login 0 query dan tema tidak dirender; settings.index di eco_new melempar "Undefined array key label" (bug yang diperbaiki commit 3edab874), jadi tidak ada angka pembanding settings. Selisih login: satu SELECT app_configs per 5 menit (cache) dan tidak bertambah per tema.

Bukti kode:
- app/Models/AppConfig.php:14-21 `Cache::remember('app_config_' . $key, 300, ...)`; set() memanggil `Cache::forget` (baris 38).
- app/Support/GlobalSeasonalTheme.php:291-311 memo per request di container (`$app->bound(self::MEMO)`), jadi `all()` yang dipanggil 4-5 kali = 1 pembacaan.
- SeasonalThemeResolver dan SeasonalThemeComposer::build() hanya memanggil GlobalSeasonalTheme dan session, tidak ada Eloquent/DB.
- SeasonalThemeSettingsController::index() (baris 30-54) tanpa query; update() melakukan 1 SELECT id AppConfig (baris 102) + upsert + 1 insert AuditLog, tidak ada loop yang memicu query. Tidak ada N+1.

## Butir 2: XSS. PASS

- `{!! !!}` di seasonal-accents.blade.php (baris 52-170) hanya merakit selector dari `$acKey`; seluruh blok berada di `@if(in_array($acKey, ['natal','tahun_baru','ramadan','lebaran','imlek','hut_ri'], true))` (baris 41) sehingga nilai di luar whitelist tidak sampai ke CSS. Sisanya konstanta kode (data URI SVG statis).
- seasonal-navbar.blade.php baris 27-31, 58, 68: `{!! $navArt[...] !!}` dan `frostTile()/edgeStarsTile()` berasal dari App\Support\SeasonalNavbarArt (string konstan, tidak ada input pengguna).
- @json dan Js::from (seasonal-loading 136-144, seasonal-preview 100-111, admin 185-199 dan 466-467, login.blade 345) memuat katalog statis, label terjemahan, atau key tervalidasi. importmap di preview memakai flag JSON_HEX_TAG dll.
- Atribut dari data: `data-season="{{ $seasonalTheme['key'] }}"` (escaped, key tervalidasi `SeasonalThemes::isValid`), `src="{{ $globalBgMusicUrl }}"` (dashboard:1708) dan `{{ $slSound }}` (seasonal-loading:68) escaped dan hasil sanitizeSound, palet di `style=` berasal dari katalog statis, `style` login-art dirakit dari array konstan bilangan.
- admin JS `row.innerHTML` (admin:322-332) memakai `escAttr()` untuk label dan tanggal; data baris jadwal sudah disanitasi ulang di GlobalSeasonalTheme::sanitizeSchedule.
- Tidak ditemukan nilai admin bebas (teks) yang masuk ke HTML/JS/CSS tanpa sanitasi.

## Butir 3: Path traversal dan aset. PASS (catatan rendah)

- soundUrl (GlobalSeasonalTheme.php:261-266) = `'/sounds/' . $sound`, dan `$sound` hanya lolos sanitizeSound (baris 441-454): `basename()`, regex `/^[\w.\-]+\.(mp3|wav|ogg|aac)$/i` (tanpa `/`, `\`, `:`; skema berbahaya mustahil), dan `file_exists(public_path('sounds/...'))`. Disanitasi saat tulis dan baca.
- URL aset login-art: `asset('images/seasonal/login/' . $laKey . '.svg')` dengan `$laKey` dari whitelist `in_array(..., $laKeys, true)`.
- uploadSound (controller 133-161): `required|file|mimes:wav,mp3,ogg,aac|extensions:wav,mp3,ogg,aac|max:3072` (3 MB, cek isi dan ekstensi), nama = `Str::slug(basename) . '_' . time() . '.' . ext` (slug menghapus `../`), tujuan tetap `public_path('sounds')`, sama dengan fitur Notif Sounds. Rute dijaga menu middleware.
- Catatan rendah: nama bukan acak murni (slug + time()); dua unggahan nama sama dalam detik yang sama saling menimpa. Berkas di public/sounds dapat diakses publik (sama seperti suara notifikasi, disengaja).

## Butir 4: CSRF dan otorisasi. PASS

- routes/web.php:292-294: GET, POST, POST upload-sound, masing-masing `->middleware('menu:control-center.seasonal-theme')`; berada di `Route::prefix('admin')` (baris 239) di dalam `Route::middleware(CheckAuthToken::class)->group` (baris 101 sampai 779).
- CSRF: bootstrap/app.php:40 hanya mengecualikan `api/*`; tidak ada VerifyCsrfToken khusus. Form admin memakai `@csrf` (admin:80), unggah fetch memakai header `X-CSRF-TOKEN` dari meta (admin:397).
- Validasi (controller 62-74): theme `Rule::in(SeasonalThemes::keys())`, mode `Rule::in([manual,auto])`, schedule `array|max:20`, `schedule.*.theme` Rule::in tanpa none, tanggal `date_format:Y-m-d` dan `after_or_equal`, `login.theme` Rule::in(inherit + keys), `login.scope` Rule::in(both,page,loading), volume `numeric|min:0|max:100`, sound `nullable|string` (ditegakkan whitelist oleh sanitizeSound). Batas 20 juga di sanitizeSchedule (MAX_SCHEDULE_ENTRIES). Tes: SeasonalThemeSettingsUpdateTest (tema, mode, jadwal none, tanggal, >20, login theme, scope, middleware).
- Role: grep `role` pada SeasonalThemeResolver, GlobalSeasonalTheme, SeasonalThemeComposer, controller: tidak ada kecocokan selain komentar di controller baris 24 ("TIDAK hardcode role id"). ThemeConceptTest::test_resolver_tidak_punya_masukan_role lulus.

## Butir 5: SVG statis. PASS

Dipindai 6 SVG di public/images/seasonal/login/*.svg dan sprite partials/seasonal-icons.blade.php (plus SeasonalNavbarArt untuk http): tanpa `<script>`, `foreignObject`, atribut `on*`, `javascript:`, `<image>`, `@import`, href/xlink:href eksternal (semua `href="#..."`), atau `url(http...)`. Satu-satunya "http" adalah deklarasi xmlns w3.org. Tidak ada SVG tema di public/images (di luar folder seasonal).

## Butir 6: Matriks login. PASS (cakupan tes parsial, kombinasi penuh terverifikasi)

- LoginSeasonalThemeTest (8 tes): data lama, sanitasi, scope both/page/loading dengan login imlek, login none, inherit x tiga scope, composer anonim vs login. ThemeConceptTest: matriks global x preferensi (none/natal/imlek x default/none/ramadan) dan jadwal otomatis.
- Celah: tes tidak menyilang penuh scope x tema login x preferensi (mis. scope both dengan preferensi tema tertentu, scope page dengan preferensi tema).
- Verifikasi tambahan (scratchpad m.php): 3 scope x 3 tema login (inherit, none, imlek) x 5 preferensi (default, none, hut_ri, natal, garbage) = 45 kasus untuk forAuthPage dan forLoading terhadap rumus yang diharapkan: 0 selisih.

## Butir 7: Regresi non-tema. PASS

`git diff origin/eco_new...HEAD --stat`: 105 berkas (87 baru, 18 diubah), 10 commit, aldy_eco sudah memuat seluruh eco_new (HEAD..origin/eco_new = 0). Berkas diubah di luar aset tema: AuthController, SettingsController, CheckAuthToken, AppServiceProvider, dashboard, login, 4 halaman auth, settings/index (semuanya kait bagian 6), SeasonalThemeSettingsController dan Support/* (tema), admin/seasonal-theme-settings (tema), serta app/Models/AppConfig.php (disetujui pemilik). Tambahan baru di luar manifest bagian 6: docs/seasonal-theme.md (dokumentasi), lang/en|id/ui.php (tercantum bagian 6), tests/Feature/Seasonal. Tidak ada perubahan migrasi, composer, config, atau routes/web.php. Diff dashboard hanya kait tema (tag html, fallback preferensi 'default', include partial, CSS html[data-season]).

## Daftar risiko (urut keparahan)

1. Sedang (efek samping approval): AppConfig::get kini men-cache SEMUA key 5 menit di store cache default (app/Models/AppConfig.php:14). Nilai yang diubah lewat jalur selain AppConfig::set (SQL manual, server lain dengan cache file lokal) basi sampai 300 detik; bila CACHE_STORE=database, nilai konfigurasi sensitif ikut tersalin ke tabel cache. Sudah disetujui pemilik, cukup diketahui.
2. Rendah: unggah musik memakai nama slug + time() (bukan acak), tabrakan dalam detik yang sama menimpa berkas; berkas publik di public/sounds.
3. Rendah: `sound` divalidasi `nullable|string` di request dan baru disaring saat sanitize (nilai tidak valid disimpan sebagai null diam-diam, tanpa pesan error).
4. Rendah: tes matriks login tidak menyilang penuh; ditutup verifikasi manual 45 kasus (tidak tersimpan sebagai tes).
5. Info: eco_new asli memiliki bug settings.index (label) yang sudah diperbaiki di aldy_eco.
6. Catatan proses auditor: saat membersihkan git worktree pembanding, junction vendor ikut terhapus isinya; vendor dipulihkan dengan `composer install` dari composer.lock, tes seasonal lulus ulang (74 OK). Berkas tak terlacak docs/seasonal-theme-port/audit/step3-screens.md bukan dari audit ini.
