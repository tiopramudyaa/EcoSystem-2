# Gerbang G: audit akhir (keamanan, query, regresi) READ-ONLY

Cabang aldy_eco, HEAD 8e2e64fc, pembanding origin/eco_new (merge-base 9b30f98c, HEAD..origin/eco_new = 0 commit, 21 commit di depan). Ditulis bertahap per butir.

## Butir 1: Cakupan diff. PASS

`git diff --stat origin/eco_new...HEAD`: 114 berkas, 11735 penambahan, 647 penghapusan (93 berkas baru, 21 diubah, 0 dihapus).

Berkas BARU (93): 15 docs (14 di docs/seasonal-theme-port: catatan, audit, skrip; plus docs/seasonal-theme.md), 29 public/js/seasonal, 3 public/vendor/three, 18 public/images/seasonal, 10 resources/views/partials, 2 panduan 021 dan 148 (x en, id = 4), 2 lang (lang/en/ui.php, lang/id/ui.php belum ada di eco_new, grup seasonal), 1 app/Support/SeasonalNavbarArt.php, 1 app/View/Composers/SeasonalThemeComposer.php, 10 tes (9 Unit/Seasonal, 1 Feature/Seasonal). Semua yang di luar docs/seasonal-theme-port ada di manifest-new-files.txt kecuali lang/en|id/ui.php (tercantum di PORT bagian 6 dan DEVIATIONS.md).

Berkas DIUBAH (21), diklasifikasikan:
- 6 berkas "replace" tema (manifest-replace-files.txt): SeasonalThemeSettingsController, GlobalSeasonalTheme, SeasonalThemeResolver, SeasonalThemes, admin/seasonal-theme-settings.blade.php, partials/seasonal-theme.blade.php.
- 11 kait PORT bagian 6, jumlah baris diff (tambah/hapus): AuthController 21/0, SettingsController 13/3, CheckAuthToken 5/5, AppServiceProvider 3/0, dashboard.blade.php 118/14, settings/index.blade.php 200/57, login.blade.php 71/341 (341 hapusan = overlay Natal lama diganti SeasonalLoading generik, lihat butir 5), forgot-password 2/1, change-password 2/1, check-email 2/1, two-factor-challenge 2/1.
- Pengecualian tercatat di DEVIATIONS.md: app/Models/AppConfig.php 9/2 (cache 5 menit, keputusan pemilik), partials/confirm-modal.blade.php 2/0 (data-danger), resources/guide/{en,id}/010-home-dashboard.md 4/1 masing-masing, lang/*/ui.php 140/0 masing-masing, sidebar ringkas dan blok Accent color (di dalam dashboard dan settings/index).

Pemeriksaan negatif (`git diff --name-only origin/eco_new...HEAD | grep -E '^(database|config|composer|routes|package|public/build|bootstrap|\.env)'`): 0 baris. Tidak ada migrasi, composer.json/lock, config, routes/web.php, public/build.

Berkas di luar daftar: TIDAK ADA yang tidak tercatat.

## Butir 2: Keamanan penuh. PASS (3 catatan rendah, 1 catatan infrastruktur)

Metode: baca kode, render Blade dengan data jahat (skrip scratchpad/final/xss.php: 4 variasi pengaturan global jahat x 3 variasi preferensi x 4 halaman = 48 render; DB::listen dan cache array, tanpa tulis DB), reflection ke SettingsController::sanitizePreferences (scratchpad/final/san.php), route:list.

XSS dan injeksi:
- Render jahat: nilai theme, sound, volume, mode, jadwal, login.theme, login.scope berisi `XSSMARK"><script>alert(1)</script>`, `../../.env`, `/etc/passwd.mp3`, `javascript:alert(1).mp3`, jadwal 50 entri, jadwal string. Hasil 48 render pada login, forgot-password, settings.index, admin Seasonal Theme: 0 galat, `<script>alert` tidak muncul di satu pun, `../../.env`, `/etc/passwd`, `javascript:alert` muncul 0 kali. Satu-satunya kemunculan penanda adalah pada Settings dengan PREFERENSI jahat di session (seasonal_theme berisi penanda, primary_color berisi `"><script>`): masuk lewat `let S = @json($preferences)` dan terhex-escape (`"><script>...<\/script>`), dan `--primary-color: #ff0000&quot;&gt;&lt;script&gt;...` (dashboard.blade.php:123 ter-escape). Aman.
- Blade `@json` memakai opsi bawaan 15 (JSON_HEX_TAG|AMP|APOS|QUOT, vendor/laravel/framework/.../CompilesJson.php:24), jadi `</script>` tidak bisa lolos di seasonal-loading.blade.php:168-176 dan admin:280-294, settings:851,886,980-981. seasonal-preview.blade.php:113-124 memakai Js::from dan flag hex eksplisit.
- Semua `{!! !!}` pada partial tema (seasonal-accents:52-170, seasonal-navbar:27-31,58,68) berasal dari konstanta kode (SeasonalNavbarArt, selector dari kunci tema yang dijaga `in_array($acKey, [...], true)` di accents:41). Tidak ada masukan pengguna.
- settings/index blok Accent color: `style="background:{{ $hex }}"` (hex dari tabel konstan $acPresets, baris 104-121); `$acSaved` divalidasi ulang `preg_match('/^#[0-9a-f]{6}$/')` sebelum dipakai di `style` dan `value` (baris 106-107, 128-129); semuanya dicetak lewat `{{ }}`. onclick `selectColor('{{ $hex }}')` memakai konstanta. Tidak ada atribut event yang berisi nilai preferensi.
- admin JS `row.innerHTML` (admin:322-332) membungkus semua nilai dengan escAttr (& " <), label dari katalog server, dan opsi sound dari nama berkas di public/sounds dicetak `{{ $file }}` (admin:180). dashboard: sidebar ringkas membaca localStorage `ec_sidebar_rail` hanya jika `=== '1'` dalam try/catch (dashboard.blade.php diff baris +315), menulis hanya '1'/'0'. Tidak ada innerHTML, eval, new Function, document.write di public/js/seasonal/* (grep 0).
- confirm-modal.blade.php:90-91: `data-danger` kosong konstan, bukan dari input.
- SVG statis: 6 SVG login, sprite seasonal-icons, login-art, SeasonalNavbarArt: grep `<script|foreignObject|on*=|javascript:|<image|@import|href=http|url(http` = 0 temuan; satu-satunya "http" adalah xmlns w3.org.

Sanitasi preferensi (reflection, tanpa DB):
- seasonal_theme: lolos hanya default, none, natal, tahun_baru, ramadan, lebaran, imlek, hut_ri. Dibuang: `<script>`, `NATAL`, `natal ` (spasi), `../x`, `['natal']`, `0`, `true`, `inherit`, `auto`. userChoices() = 8 nilai.
- primary_color: `#ff0000` dan `#FF00AA` lolos (huruf kecil), dibuang: `#abc`, `red`, `javascript:alert(1)`, `#ff0000;}</style><script>...`, `url(http://x)`, `#aabbcc"onmouseover="x`, array, 123, null, `#gggggg`, `expression(1)`, ` #aabbcc`. CATATAN RENDAH N1: `"#aabbcc\n"` (trailing newline) LOLOS dan tersimpan apa adanya karena regex `/^#[0-9a-fA-F]{6}$/` tanpa modifier D (SettingsController.php:305). Baris ini bukan bagian diff (sudah ada di eco_new). Dampak: nol (hanya karakter newline di nilai CSS/atribut yang ter-escape; settings/index membuangnya lewat regex yang sama tetapi `$` juga menerima, nilai tetap di dalam atribut ter-escape). Rekomendasi opsional: pakai `\z` atau modifier `D`.
- theme, sidebar_style: nilai di luar whitelist dibuang.

CSRF dan otorisasi: route:list menunjukkan tiga rute admin/seasonal-theme (GET, POST, POST upload-sound) berantai `web` (CSRF aktif; bootstrap/app.php:40 hanya mengecualikan `api/*`), `CheckAuthToken`, `CheckMenuAccess:control-center.seasonal-theme`. CheckMenuAccess menolak non-employee dan yang tak punya izin menu (redirect atau 403 JSON). routes/web.php:292-294 tidak berubah dari eco_new (rute sudah ada).

Validasi dan batas: SeasonalThemeSettingsController.php:62-74: theme Rule::in(keys), mode Rule::in([manual,auto]), schedule array max:20, schedule.*.theme Rule::in(tanpa none), tanggal date_format:Y-m-d dan after_or_equal, login.theme dan login.scope Rule::in, volume numeric 0..100. Ditegakkan ulang saat baca/tulis oleh GlobalSeasonalTheme (50 entri jadwal jahat dirender dengan 252 KB halaman admin, tanpa galat). CATATAN RENDAH N2: `schedule=` kosong (ConvertEmptyStringsToNull menjadi null) lolos `nullable` lalu `array_values($request->input('schedule', []))` (baris 88) melempar TypeError (500) sebelum menyimpan. Hanya lewat request buatan oleh admin berizin, tidak ada tulis data. Form asli tidak mengirim kunci kosong. Perbaikan 1 baris: `(array) $request->input('schedule', [])`. Berkas replace dari sumber, jadi sama dengan aldy_merge.

Unggah musik: `required|file|mimes:wav,mp3,ogg,aac|extensions:wav,mp3,ogg,aac|max:3072` (3 MB; cek isi dan ekstensi), nama = Str::slug(nama asli) + `_` + time() + `.` + ekstensi tervalidasi, tujuan tetap public_path('sounds') (SeasonalThemeSettingsController.php:133-161). Path traversal URL musik: soundUrl = '/sounds/' . sanitizeSound(); render jahat `../../.env`, `/etc/passwd.mp3`, `javascript:alert(1).mp3` menghasilkan 0 kemunculan di output (dibuang karena regex nama dan file_exists). Catatan rendah N3: nama slug + time() bukan acak (tabrakan dalam detik sama menimpa); berkas publik (sama dengan Notif Sounds).

Header keamanan dan CSP: grep `Content-Security-Policy|csp|script-src|nonce|http-equiv|add_header|Header set` di app/, bootstrap/, config/, routes/, resources/views, public/.htaccess, docker/: tidak ada kebijakan CSP di aplikasi (satu-satunya `Permissions-Policy` di AuthController.php:157 untuk client hints, tidak terkait script). public/.htaccess hanya mengatur Cache-Control immutable 1 tahun untuk aset statis. Maka import map inline (`<script type="importmap">`), skrip inline, dan CSS mask/data URI tidak diblok oleh apa pun di repo: PASS. Catatan infrastruktur N4 (bukan FAIL): bila lapisan di luar repo (ALB, CloudFront, Nginx; bootstrap/app.php:26-37 menunjukkan app di belakang ALB) menambahkan CSP `script-src` tanpa `'unsafe-inline'`, seluruh dashboard yang memang penuh skrip inline sudah rusak lebih dulu; tema menambah import map inline dan modul dari `/js/seasonal` dan `/vendor/three` (same origin) serta `img-src data:` untuk SVG data URI (tekstur kanvas dibuat lewat canvas, tanpa Worker, Blob, fetch, atau eval di public/js/seasonal: grep 0). Cache aset: modul tema diberi versi `?v=filemtime terbesar` lewat import map (seasonal-loading.blade.php:40-52), jadi header immutable 1 tahun aman setelah deploy.

## Butir 3: Query dan performa server. PASS (1 risiko sedang: cache AppConfig semua kunci)

Metode: skrip rlogin.php, render.php, adm.php (Temp) dan scratchpad/final/cold.php, render_sql.php dengan DB::listen, cache array, session array, MySQL lokal hanya SELECT (tidak ada tulis).

| Halaman | Tema none | Tiap tema (natal, tahun_baru, ramadan, lebaran, imlek, hut_ri) |
|---|---|---|
| login, forgot-password, change-password, check-email, two-factor-challenge, cache hangat | 0 | 0 (5 halaman x 7 kondisi = 35 render, semua q=0) |
| login, cache dingin (SQL: `select * from app_configs where key = ? limit 1`) | 1 | 1; halaman berikutnya dalam 5 menit 0 |
| settings.index (global x preferensi default/none, 14 kombinasi) | 2 | 2 (SQL: 1 app_configs untuk pengaturan Microsoft login, bukan tema, plus `count(*) staging_tickets` bawaan layout dashboard) |
| admin Seasonal Theme GET (manual dan jadwal 20 baris, 14 kombinasi) | 1 (staging_tickets, layout) | 1 (angka kumulatif di skrip adm.php muncul 1..14 karena listener menumpuk, bukan query bertambah) |

- Pembanding eco_new: login eco_new sudah memanggil `GlobalSeasonalTheme::active()` (diff login.blade.php baris `-$loginSeasonalTheme = ...`) lewat AppConfig::get tanpa cache = 1 SELECT per kunjungan login. Sekarang 1 SELECT per 5 menit, jadi lebih sedikit. Settings di eco_new gagal render (bug "Undefined array key label", diperbaiki di port), jadi tidak ada angka pembanding; query tema di Settings = 0 saat cache hangat, 1 SELECT app_configs bila dingin.
- Partial tema 0 query: grep `DB::|::where|::find|->first(|->get()|Auth::|Cache::|AppConfig|Model` pada resources/views/partials/seasonal-*.blade.php, login-art.blade.php, app/Support/Seasonal*.php, SeasonalThemeComposer.php = 0 temuan. Satu-satunya pembaca AppConfig adalah GlobalSeasonalTheme::all() (GlobalSeasonalTheme.php:137), di-memo per request lewat container (baris 130-133), dibuang di save() sebelum dan sesudah tulis (baris 172-179). Tidak ada query di loop.
- I/O file per render (bukan DB): seasonal-loading.blade.php:40-52 melakukan 3 glob plus filemtime untuk membuat versi import map (sekitar 60 stat). Hanya saat tema aktif (none = 0 byte). Catatan rendah N5: ukuran kecil, opcache stat cache menutupi; opsional di-cache kalau mau.
- AppConfig::get kini memakai Cache::remember 300 detik untuk SEMUA kunci (AppConfig.php:14-21), set() memanggil Cache::forget (baris 38), setJson lewat set.

Pemakai AppConfig::get/getJson di app/ (5 kunci, 5 pemakai):
| Kunci | Pembaca | Penulis | Perlu langsung berlaku? |
|---|---|---|---|
| seasonal_theme.global | GlobalSeasonalTheme.php:137 | GlobalSeasonalTheme::save (baris 174) via setJson | Sedang: tema berubah dalam 5 menit pada instance lain |
| security.microsoft_login | MicrosoftLoginSettings.php:34 | MicrosoftLoginSettings.php:161 via setJson | Ya (keamanan login). client_secret tersimpan terenkripsi (Crypt::encryptString), jadi isi cache tidak membuka secret |
| security.two_factor_enforcement | TwoFactorEnforcementSettings.php:34 | baris 59 via setJson | Ya (keamanan: peran yang wajib 2FA) |
| ai.models | AiModelSettings.php:336 | baris 359 via setJson | Tidak mendesak |
| ai.balance | AiUsageController.php:110,144 | baris 119 via setJson | Tidak mendesak (read-modify-write dalam satu request, baca bisa 5 menit basi: risiko saldo ditimpa nilai lama bila dua admin menyimpan dalam 5 menit di instance berbeda; sangat jarang) |

Penulis yang memintas cache: grep `AppConfig::(updateOrCreate|create|insert|upsert|firstOrCreate|updateOrInsert|destroy|truncate)|DB::table('app_configs')|new AppConfig` di app/, routes/, database/ (migrasi dan seeder), resources/: 0 temuan. Satu-satunya `AppConfig::where(...)->value('id')` (SeasonalThemeSettingsController.php:102, AiSettingsController:80, AiUsageController:121, MicrosoftAuthController:410, TwoFactorEnforcementSettingsController:53) hanya membaca id untuk audit log. Tes menulis lewat AppConfig::setJson atau where (tests/Unit/MicrosoftLoginTest.php:116,165), bukan produksi. Hasil: PASS, semua penulisan produksi lewat AppConfig::set atau setJson.

Risiko R-A (SEDANG): invalidasi hanya di store cache yang dipakai server yang menyimpan. Bila produksi memakai lebih dari satu instance dengan store lokal (CACHE_STORE=file seperti `.env.example:64`, array, atau per-container; app berjalan di belakang ALB per bootstrap/app.php:26-37), perubahan penegakan 2FA dan login Microsoft di instance lain tertunda sampai 300 detik. Store `database` (default config/cache.php:18; tabel cache ada di migrasi 0001_01_01_000001) atau redis bersama tidak terdampak. Edit manual lewat SQL juga tertunda 300 detik (jalankan `php artisan cache:clear` atau tunggu). Disetujui pemilik untuk semua kunci; rekomendasi: bila ada lebih dari satu instance, pakai store bersama, atau kecualikan kunci `security.*` dari cache.

## Butir 4: Regresi non-tema. PASS (satu catatan rendah)

Metode: render HTML (skrip render.php, rlogin.php, render_pc.php) untuk tema none dan tiap tema, hitung aset dan fungsi. Tangkapan layar dan CDP browser tidak diulang di gerbang ini; hasil browser mengacu audit/step6-ui-function.md (7 butir PASS, B1 dan B2 diperbaiki) dan audit/step-f-guardian.md (A 6/6, B 7/7, C).

- Login tema none (global none): 84627 byte, 0 referensi `images/seasonal/login/`, 0 poster, 0 `scenes/`, 0 `<link rel=modulepreload|preload>`, 0 `<script src>` atau `type=module`, tanpa atribut `data-season` pada `<html>`. Yang ada hanya skrip inline seasonal-loading (import map JSON dan fungsi SeasonalLoading) karena pengguna bisa memilih tema sendiri sementara global none (data.seasonal_theme dari respons login); `window.__seasonalGlobalKey = 'none'` dan pemanasan (`warm`) hanya jalan bila tema global bukan none. Tidak ada permintaan jaringan three.js atau adegan di login none. Tema aktif: 91872 byte dan satu SVG login. Tema aktif menambah sekitar 7 KB HTML dibanding none; HTML none tidak byte identik dengan eco_new karena partial loading inline dan CSS variabel (disengaja, tercatat K3 di PROGRESS).
- Settings tema none: 293985 byte. Aset tema: 6 thumbnail poster `images/seasonal/posters/thumb/*.webp` (6 `loading="lazy"`) untuk kartu pemilih, sprite seasonal-icons, dan seasonal-preview (import map inline, tanpa unduh three sampai Play). Pengecualian yang disengaja (pemilih tema), bukan three.js atau SVG tema. seasonal-ui-remap dimuat saat tema aktif atau di rute settings.* (dashboard.blade.php:845, pengecualian disengaja karena pratinjau memasang data-season lewat JS); di halaman lain tema none tidak memuatnya.
- Dashboard layout tema none: 2 query (sama dengan tema aktif), tidak ada `data-season` pada `<html>`; tambahan hanya tombol `#sidebarRailToggle`, atribut `data-rail`, dan CSS sidebar ringkas (disengaja).
- Fitur lama (hitungan pemanggil di HTML Settings): selectTheme 4, selectSidebarStyle 3, selectFontSize 4, saveSettings() 5, resetSettings() 2 (konfirmasi `showConfirm(..., 'danger')`, POST /settings/reset lalu reload), toggleSidebar 4, closeSidebar 4, `#sidebarOverlay`, `#sidebar`, toggleMasterDropdown 2, filterSidebarMenu 2. Diff dashboard hanya menghapus 14 baris: tag `<html>`, fallback `'none'` menjadi `'default'`, blok resolusi lama dan dua pemanggilan seasonal-theme lama. Markup menu, hamburger (toggleSidebar), dan drawer mobile tidak dihapus atau diubah. Tombol panah rail adalah tambahan (`aside[data-rail]`, dimatikan di halaman `@section('sidebar-nav')`, ticket/show.blade.php:38).
- Tombol berbahaya merah: seasonal-ui-remap.blade.php:32-79 menolak `[data-danger]`, `[onclick*=elete|eject|evoke]`, `.uppercase`, kelas red-5/6; confirm-modal.blade.php:90-91 memberi `data-danger` pada tombol OK varian danger. Bukti visual: step6-ui-function butir 8 (B2 diperbaiki) dan step-f B5. Tidak diukur ulang di gerbang ini.
- Preferensi primary_color lama di luar swatch (render_pc.php, 8 kasus): `#123456` dan `#ABCDEF`: kotak custom `is-selected`, `style="background:#123456"`, input color `value="#abcdef"` (huruf kecil), tanpa cincin swatch. `#c62828` dan `#991b1b`: cincin satu swatch, tanpa custom. Nilai jahat `javascript:x`: jatuh ke `#991b1b`. Terkunci (`px-8 py-6 is-locked`, kotak peringatan tampil) hanya bila tema efektif aktif: global natal + pref default = terkunci, global none + pref natal = terkunci, pref none (global natal) = tidak, global none + pref default = tidak. Render Settings tetap 2 query.
- CheckAuthToken.php:94-105: pengguna tanpa preferensi tersimpan kini mendapat DEFAULT_PREFERENCES di session (sebelumnya kosong). Konsumen: dashboard (fallback array identik) dan `session('user_preferences.language')` di UserGuideController.php:68 ('en' sama dengan default_locale config/user_guide.php:12). Dampak fungsional nol (info).
- Catatan rendah N6: teks tombol rail ("Collapse sidebar") dan atribut aria-label-nya bukan lewat lang. Tanpa dampak keamanan.

## Butir 5: Verifikasi kait terhadap sumber (origin/aldy_merge). PASS dengan 1 catatan dokumentasi

Metode: semua baris tambahan tiap berkas kait (`git diff -U0 origin/eco_new...HEAD`) dibandingkan sebagai himpunan dengan `git show origin/aldy_merge:<berkas>` (spasi akhir diabaikan).

| Berkas | Tambah/Hapus | Baris tak ada di sumber | Penilaian |
|---|---|---|---|
| AuthController.php | 21/0 | 1 | Hanya komentar docblock; kode identik |
| SettingsController.php | 13/3 | 0 | Identik |
| CheckAuthToken.php | 5/5 | 0 | Identik |
| AppServiceProvider.php | 3/0 | 0 | Identik (satu View::composer) |
| AppConfig.php | 9/2 | 0 | Identik; disetujui pemilik (DEVIATIONS) |
| auth/forgot-password, change-password, check-email, two-factor-challenge | 2/1 masing-masing | 0 | Identik (data-season dan include vars); `lang="en"` eco_new dipertahankan |
| auth/login.blade.php | 71/341 | 1 | Satu baris hanya beda indentasi `{{-- Orbs --}}`. 341 hapusan = overlay Natal lama (kereta luncur SVG, frost border, jingle, CSS) dan fungsi showSeasonalLoadingScreen, diganti SeasonalLoading.start (login.blade.php:412-436 dan cabang sukses). Tanpa tema tetap toast dan 1000 ms. Fitur non-tema login aldy_merge (Report, autofill, anti kirim ganda) tidak dibawa |
| partials/confirm-modal.blade.php | 2/0 | 0 | Identik; tercatat DEVIATIONS |
| dashboard.blade.php | 118/14 | 72 | Kait tema memakai include yang sama dengan sumber (vars baris 106, ui-remap 846, accents 850, icons 857, theme sidebar 868, navbar 1716) dan html tag sama; baris lain = sidebar ringkas, 4 utilitas Tailwind yang tak ada di public/build; tercatat DEVIATIONS |
| settings/index.blade.php | 200/57 | 98 | Pemilih tema memakai kelas yang ada di public/build, blok Accent color baru; tercatat DEVIATIONS |

Catatan dokumentasi D1: verify-port.sh melaporkan BEDA untuk resources/guide/en/021-my-seasonal-theme.md dan resources/guide/id/021-my-seasonal-theme.md (diubah commit 84246d07 untuk penguncian warna aksen). Kedua berkas TIDAK tercantum di DEVIATIONS.md (hanya panduan 010 yang tercantum).

## Butir 6: Perintah verifikasi. FAIL ringan (hanya dokumentasi D1)

| Perintah | Hasil |
|---|---|
| `php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit` | OK, Tests: 298, Assertions: 3649, PHPUnit Deprecations: 5, 0 gagal, exit 0 (12,8 detik) |
| `php docs/HRGA/tools/guide-check.php` | 88 pasang (176 berkas), galat 2 (en dan id 062-control-center-security: requires `control-center.schedule-monitor` tidak ada di tabel menu, galat lama), peringatan 0 |
| `php artisan route:list --json` | 1001 rute; tiga rute admin/seasonal-theme berantai web, CheckAuthToken, CheckMenuAccess:control-center.seasonal-theme |
| `php artisan view:cache` lalu `view:clear` | keduanya sukses (exit 0), storage/framework/views kosong sesudahnya |
| `bash docs/seasonal-theme-port/verify-port.sh` | GAGAL: 5 BEDA, 0 HILANG: stage.js, seasonal-loading.blade.php, seasonal-preview.blade.php (ketiganya di DEVIATIONS.md), serta guide 021 en dan id (TIDAK di DEVIATIONS.md = D1) |

Berkas tak terlacak saat audit: hanya audit/step7-final-security.md (ini) dan audit/step7-final-performance.md (audit paralel). Berkas terlacak bersih.

## Ringkasan PASS/FAIL

| # | Butir | Hasil |
|---|---|---|
| 1 | Cakupan diff (114 berkas, 0 migrasi/composer/config/routes/public/build, semua perubahan tercatat) | PASS |
| 2 | Keamanan penuh (XSS 48 render jahat, CSRF, otorisasi, unggah, traversal, sanitasi via reflection, SVG, CSP) | PASS (N1 sampai N4 rendah) |
| 3 | Query dan performa server (login 0 hangat/1 dingin, Settings 2, admin 1, partial 0, semua penulis lewat AppConfig::set) | PASS (risiko R-A sedang) |
| 4 | Regresi non-tema | PASS |
| 5 | Kait identik dengan sumber | PASS (D1 dokumentasi) |
| 6 | Tes 298/3649, guide-check 2 galat lama 0 peringatan, route:list 1001, view:cache/clear OK, verify-port | FAIL ringan: verify-port BEDA 5 (2 berkas panduan 021 belum di DEVIATIONS.md) |

## Risiko berurut keparahan

1. SEDANG R-A: cache AppConfig 300 detik untuk semua kunci, termasuk `security.two_factor_enforcement` dan `security.microsoft_login`. Dengan lebih dari satu instance dan store cache lokal (file/array), perubahan di satu instance baru berlaku di instance lain sampai 5 menit; edit SQL manual juga tertunda. Rekomendasi: pastikan produksi memakai store bersama (database/redis) atau kecualikan kunci `security.*` dari Cache::remember; catat di catatan rilis (`php artisan cache:clear` setelah edit SQL).
2. RENDAH D1: DEVIATIONS.md belum mencantumkan resources/guide/{en,id}/021-my-seasonal-theme.md (diubah 84246d07) sehingga verify-port.sh gagal 5, bukan 3 yang tercatat. Rekomendasi: tambah satu baris di DEVIATIONS.md (dan opsional regenerasi blob-ids di branch ini) sebelum merge.
3. RENDAH N2: `schedule=` kosong pada POST admin menyebabkan TypeError 500 (SeasonalThemeSettingsController.php:88), tanpa tulis data, hanya request buatan oleh admin berizin. Perbaikan: `(array) $request->input('schedule', [])`.
4. RENDAH N1: regex primary_color tanpa `D`/`\z` menerima `#aabbcc\n` (SettingsController.php:305, sudah ada di eco_new); tanpa dampak (nilai ter-escape).
5. RENDAH N3: nama berkas musik slug + time(), publik di public/sounds (sama dengan Notif Sounds).
6. INFO N4: tidak ada CSP di repo; bila CSP ditambah di ALB/CloudFront, harus mengizinkan skrip inline dan import map (dashboard sudah bergantung pada inline) dan `img-src data:`.
7. INFO N5: seasonal-loading melakukan 3 glob dan sekitar 60 filemtime per render saat tema aktif (0 DB).
8. INFO N6: label tombol rail berbahasa Inggris tetap. Kosmetik terbuka K1 sampai K3, risiko 2 dan 3 dari step-f (Settings tanpa kelas responsif karena public/build tidak memuat kelas sm:; disarankan `npm run build` saat deploy) tetap berlaku. Baris G di PROGRESS.md masih BELUM.

Catatan proses: tidak ada tulis DB, tidak ada worktree, tidak ada server atau proses yang dimulai (semua lewat skrip CLI php), tidak ada port dipakai. Berkas sementara hanya di scratchpad/final (san.php, xss.php, cold.php, render_sql.php, render_pc.php, out/, phpunit.out, sec456.md).
