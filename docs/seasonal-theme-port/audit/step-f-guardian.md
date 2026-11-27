# Gerbang F: audit penjaga (Tugas A, B, C)

Rentang diff: 7d917c20..HEAD (6289e49e). Auditor read-only. Perubahan server (app, routes, database) pada rentang ini: KOSONG (git diff --stat -- app routes database tidak menghasilkan baris).

## Tugas A: keamanan perubahan

| # | Butir | Hasil | Bukti |
|---|---|---|---|
| A1 | CSS mask url('{{ asset(...) }}') | PASS | resources/views/partials/seasonal-loading.blade.php:125-126. Satu-satunya masukan adalah asset('images/eclectic_logo_nobg.png'), literal konstan, dicetak dengan {{ }} (ter-escape HTML). Tidak ada variabel pengguna. Di seasonal-preview.blade.php tidak ada mask, logo hanya <img src="{{ asset(...) }}"> (baris 71). Catatan: asset() bergantung APP_URL/host permintaan, bukan input pengguna; tanda kutip di host tidak mungkin lolos karena {{ }} meng-escape. |
| A2 | Teks lang baru | PASS | lang/en/ui.php:35-64 dan lang/id/ui.php:35-64: hanya string polos tanpa HTML. Dicetak lewat {{ __('ui.seasonal.loading.preparing') }} (seasonal-loading.blade.php:67). Judul/doa tema masuk lewat @json (seasonal-loading.blade.php:171) lalu textContent (baris 208-209), dan lewat Js::from (seasonal-preview.blade.php:117) lalu textContent (baris 201-202). Pemeriksaan em dash (U+2014) dan en dash (U+2013) pada seluruh baris tambahan non-docs diff: 0 temuan. Karakter ellipsis (U+2026) dipakai, bukan dash. |
| A3 | public/js/seasonal/stage.js | PASS | warmTextures baris 62-81 (hanya traverse adegan lokal, initTexture, setTimeout 50 ms plus rAF), release baris 16-21 (WeakMap, try/catch), reuseRenderer baris 88/119-125. Tidak ada eval, new Function, innerHTML, document.write, fetch, XHR. Import dinamis hanya THREE_URL konstan (baris 8,46) dan './scenes/' + name dengan name dibatasi allowlist SCENES (baris 49) sehingga bukan sumber tak tepercaya. |
| A4 | JS pratinjau | PASS | Data lewat Js::from (seasonal-preview.blade.php:117-124). Tidak ada innerHTML/insertAdjacent di berkas; teks diisi dengan textContent (baris 145, 201, 202, 212, 216, 218). poster.src dari URL asset server. |
| A5 | data-danger di confirm-modal | PASS | resources/views/partials/confirm-modal.blade.php:91: setAttribute('data-danger', '') hanya bila variant === 'danger' (nilai kosong konstan), selain itu removeAttribute. Tidak berasal dari input pengguna. (innerHTML pada baris 87 untuk ICONS adalah kode lama dari tabel konstan, bukan bagian diff ini.) |
| A6 | JS sidebar ringkas | PASS | dashboard.blade.php:108 membaca localStorage dan hanya menerima '1' (=== '1') dalam try/catch. setSidebarRail baris 2094-2113: tidak ada innerHTML; tooltip lewat el.setAttribute('title', tx.textContent.trim()) baris 2109; tulis localStorage hanya '1'/'0' dalam try/catch (baris 2097). Pendengar klik baris ~2125 memakai e.target.closest, tanpa data eksternal. Catatan non-keamanan: baris 2038 (btn.innerHTML tetap, kode lama pin). |

Risiko kecil A: (a) @json (bukan Js::from) di seasonal-loading.blade.php:168-176 dipertahankan dari port sumber; aman untuk nilai saat ini karena dikodekan json_encode default (tag script tidak lolos, slash di-escape). (b) data-danger bergantung pada selector CSS tema yang tidak diaudit di sini (sudah QA di step6-ui-function).

## Tugas B: sidebar ringkas (regresi dan query)

Metode: HTML dirender dari Blade (skrip render di scratchpad guard, DB::listen), disajikan lewat php -S 127.0.0.1:8140 (port 8140, dihentikan lewat PID setelah selesai), diuji di Edge headless via CDP. Tangkapan layar (benar-benar dilihat): Settings natal rail 1280, Settings normal dan rail 1024, admin Seasonal Theme natal rail dan normal 1280, fokus tombol (zoom). Seluruh 24 kombinasi (3 halaman x 4 lebar x rail/normal) diukur.

| # | Butir | Hasil | Bukti |
|---|---|---|---|
| B1 | Tanpa perubahan server; query sama | PASS | git diff --stat HEAD~3..HEAD dan 7d917c20..HEAD pada app, routes, database: kosong. Render Settings dengan dashboard.blade.php dari HEAD~1 (sebelum) vs sekarang (sesudah): 2 query vs 2 query (tema none dan natal). Admin Seasonal Theme: 1 query. Halaman sidebar-nav: 0 query. |
| B2a | Hamburger desktop (toggleSidebar) | PASS | 1280 mode normal: sidebar 256 dan konten x=256 menjadi sidebar 0 dan konten x=0 (w-0, ml-0), lalu kembali 256. Rail lalu hamburger sembunyikan: sidebar 0, konten 0; buka lagi: kembali ke 80 (rail dipulihkan), konten x=80. Rail dimatikan lalu hamburger: normal. Tombol rail ikut tersembunyi saat w-0 (overflow-hidden). |
| B2b | Drawer mobile (<1024) | PASS | Lebar 800 dengan ec_sidebar_rail=1: tombol rail display none, sidebar 256 px saat terbuka (x=0), overlay tampil; Esc menutup (x=-256), klik overlay menutup, closeSidebar menutup. Mode ringkas tidak berlaku. |
| B2c | Dropdown grup, pencarian, pin | PASS | toggleMasterDropdown membuka grup (kelas hidden hilang). filterSidebarMenu('ticket'): 18 dari 71 tautan terlihat; dikosongkan kembali. Tombol pin: kelas pinned muncul. |
| B3 | @section('sidebar-nav') memakai data-rail=off | PASS | Hanya resources/views/ticket/show.blade.php (baris 38) yang memakai sidebar-nav (grep). Render view uji sederhana yang extends dashboard dengan sidebar-nav dan ec_sidebar_rail=1: aside data-rail="off", tombol rail display none, sidebar 256 px, konten x=256 (bukan 80), 0 tooltip data-rail-title. Atribut html data-sb-rail tetap "1" tetapi semua selektor CSS dijaga #sidebar[data-rail="on"]. Halaman ticket/show asli tidak dirender (butuh data tiket); logika sama. |
| B4 | Tata letak 1024/1280/1440/1920 | PASS | Semua 24 kombinasi: scrollWidth sama dengan clientWidth (tanpa overflow horizontal); konten mengisi sisa lebar (rail: x=80, lebar 934/1190/1350/1830; normal: x=256). Satu-satunya elemen fixed atau sticky di luar sidebar adalah header sticky (z-40) di kolom konten (x=80 atau 256), tidak menimpa konten. Catatan pra-ada (bukan dari rail): di 1024 mode normal, kartu Auto pada baris Theme Settings terpotong di tepi kanan; dengan rail lega. Baris itu tidak diubah oleh diff F. |
| B5 | Tema natal pada rail | PASS | Dekorasi aksen sidebar disembunyikan, ikon menu tetap terlihat (tangkapan layar), logo menjadi monogram, tombol panah terbaca. Kosmetik: karangan natal di bawah header berujung kiri dekat tombol panah (x=80, y=96), tidak menutupi fungsi. |
| B6 | Keyboard | PASS | Tab pertama langsung memfokus #sidebarRailToggle. Enter: aria-pressed false menjadi true, label "Collapse sidebar" menjadi "Expand sidebar", title ikut. Space: kembali ke false dan "Collapse sidebar". |
| B7 | Fokus dan kontras | PASS (catatan) | Outline 2px putih plus ring rgba(0,0,0,.4) 4px (dashboard.blade.php:199). Sisi sidebar gelap: kontras tinggi; sisi konten putih: ring abu sekitar 2,85:1 sendiri (sedikit di bawah 3:1), kombinasi putih plus gelap tetap jelas pada tangkapan zoom. Ikon #374151 pada putih sekitar 10:1. Tombol 24x24 px (tepat batas WCAG 2.5.8). |

Catatan perilaku (bukan cacat): klik tombol grup menu saat ringkas memanggil setSidebarRail(false) dengan persist bawaan (dashboard.blade.php:2125), sehingga preferensi tersimpan menjadi '0'. Pengguna yang ingin tetap ringkas harus menekan panah lagi.

## Tugas C: cek regresi dasar (penjaga)

| # | Butir | Hasil | Bukti |
|---|---|---|---|
| C1 | verify-port.sh | PASS | 3 BEDA: public/js/seasonal/stage.js, partials/seasonal-loading.blade.php, partials/seasonal-preview.blade.php; ketiganya tercantum di DEVIATIONS.md. Tidak ada HILANG. Keluaran "GAGAL: 3 masalah" berarti 3 deviasi sah. |
| C2 | Tes penuh | PASS | Tests: 298, Assertions: 3649, PHPUnit Deprecations: 5, 0 gagal (dijalankan pada working tree yang sedang diedit pekerjaan lain, lihat C6). |
| C3 | guide-check | PASS | php docs/HRGA/tools/guide-check.php: 88 pasang, galat 2 (hanya en dan id 062-control-center-security: requires control-center.schedule-monitor), peringatan 0. |
| C4 | route:list | PASS | 1001 rute (JSON); diff F tidak menyentuh routes. |
| C5 | view:cache lalu view:clear | PASS | Keduanya sukses tanpa galat. |
| C6 | git status bersih | CATATAN | Working tree TIDAK bersih saat audit: perubahan belum di-commit milik pekerjaan lain (bukan penjaga): lang/en/ui.php, lang/id/ui.php (kunci accent_*), resources/views/settings/index.blade.php (penguncian warna aksen), resources/guide/{en,id}/021-my-seasonal-theme.md. Selain itu hanya audit/step-f-guardian.md (baru). Di luar rentang A; perlu diaudit sendiri sebelum commit. |
| C7 | Tabel roadmap vs kenyataan | CATATAN | Ketidakcocokan: (1) Baris 6: "(b) UI dan fungsi di browser BELUM" padahal audit/step6-ui-function.md ada dan baris E SELESAI. (2) Baris 4: "menunggu QA browser langkah 6" basi. (3) Baris B BERJALAN, C MENUNGGU B, D MENUNGGU C: audit/step6-perf-baseline.md sudah memuat tabel before dan after dan perbaikan stage.js sudah di-commit (69d00f68), jadi B dan C selesai; D belum punya kesimpulan PASS/FAIL tertulis di PROGRESS. (4) Hasil tes 298 cocok. Hash cb765c92, 7627f493, bf73d6b7 ada di repositori. |

## Daftar risiko (urut keparahan)

1. SEDANG: Working tree berisi perubahan belum di-commit milik pekerjaan lain (settings/index penguncian aksen, lang, panduan 021). Tidak diaudit di F; periksa sebelum commit (query, escaping teks lang, tema none byte identik, tanpa em dash).
2. RENDAH-SEDANG: Settings memakai kelas non-responsif px-8, w-48, gap-8 (tanpa sm:) demi kelas yang ada di public/build. Di layar sempit (<640 px) kolom kiri 192 px menekan picker. Disarankan npm run build lalu kelas responsif. Tidak diuji di 390 px di sini.
3. RENDAH: Di 1024 mode normal kartu Theme Auto terpotong di Settings (pra-ada).
4. RENDAH: Klik grup menu saat ringkas menimpa preferensi tersimpan menjadi tidak ringkas.
5. RENDAH: Ring fokus sisi putih sekitar 2,85:1; tombol 24x24 px (batas minimum).
6. RENDAH: Karangan natal dekat tombol panah (kosmetik); status roadmap PROGRESS.md basi (C7).
7. INFO: @json (bukan Js::from) di seasonal-loading.blade.php:168-176 aman untuk data saat ini.
