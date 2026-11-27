# Progres port Seasonal Theme ke aldy_eco

Berkas ini adalah catatan kerja agar pekerjaan bisa dilanjutkan setelah sesi atau token habis.
Perbarui setelah setiap langkah selesai. Sumber: origin/aldy_merge. Tujuan akhir: aldy_eco di-merge ke eco_new.

## Aturan kerja (dari pemilik proyek)
- Ubah hanya tema. Bentuk dashboard, layout, query, dan menu lain tidak boleh berubah.
- Tema menyesuaikan layout yang ada (dashboard, navbar, halaman login, loading screen).
- Jangan menambah query yang memperlambat. Partial tema harus 0 query.
- Jangan menimpa dashboard.blade.php, login.blade.php, settings/index.blade.php, routes/web.php dari aldy_merge. Hanya tambah baris kait.
- Tanpa em dash atau en dash pada teks yang ditulis (langkah 1 dan 5 menegaskan ini).
- Jangan menyentuh atau membuat migrasi.
- Tema none harus identik byte per byte dengan sebelum tema dipasang.

## Roadmap

| # | Langkah | Status | Commit |
|---|---|---|---|
| 0 | Sinkron aldy_eco dengan eco_new (merge, 0 konflik) | SELESAI | cb765c92 |
| 1a | Salin 77 berkas baru, timpa 6 berkas tema, verify-port OK 83, lang minimal 87 kunci | SELESAI | 7627f493 |
| 1b | Kait backend + cache AppConfig (keputusan pemilik: dipasang). Tes Seasonal 74, gagal 2 (menunggu kait login dan settings) | SELESAI | bf73d6b7 dan commit AppConfig |
| 2 | Kait dashboard.blade.php (a sampai i). Partial tema: none = 0 byte, 6 tema = 0 query. Audit agent: lihat audit/step2-review.md | SELESAI, audit PASS 6 poin (risiko R1 sampai R5 di audit/step2-review.md) | lihat git log |
| 3 | Kait login (brand vars, loading generik, login-art, dp-ambient, cabang sukses) + 4 halaman auth. Render 6 tema + none: 0 query, 5 halaman OK. Tes Seasonal 74 lulus, suite penuh 298 lulus. Tangkapan layar 4 viewport: lihat audit/step3-screens.md | SELESAI, QA visual 28 dari 28 PASS (7 kondisi x 4 viewport) | lihat git log |
| 4 | Kait pemilih tema settings/index. /settings render OK, 2 query. Dialog Play, Esc, fokus, jaringan lazy, reduced motion: terverifikasi di QA UI (audit/step6-ui-function.md) | SELESAI | 3edab874 |
| 5 | Panduan 021 dan 148 (en, id) sudah berpasangan, front matter dan requires sesuai MenuSeeder (control-center.seasonal-theme). Topik 020 navigasi, 030, 070 tidak ada di eco_new, jadi tidak diubah. guide-check: 0 galat dari tema, 2 galat bawaan di 062 (control-center.schedule-monitor, bukan bagian tema), 0 peringatan. Tes Guide 42 lulus | SELESAI | lihat git log |
| 6 | QA: (a) query dan keamanan PASS 7 butir; (b) UI dan fungsi PASS 7 butir, B1 dan B2 diperbaiki; (c) performa: baseline dan sesudah perbaikan tercatat | SELESAI | lihat audit/*.md |
| 7 | Ringkasan port di RINGKASAN-PORT.md, 6 loading screen dibandingkan dengan poster (cocok), diff hanya tema, kait, dan fitur yang diminta pemilik | SELESAI | lihat git log |

## Catatan dan keputusan terbuka
- /settings error "Undefined array key label" sampai langkah 4 selesai (versi lama settings/index memakai kunci katalog lama). Itu perilaku yang diharapkan di antara langkah 1 dan 4.
- Tes Seasonal menyemai nilai lewat cache AppConfig (key app_config_<key>, bentuk {found,value}). AppConfig.php di eco_new belum memakai cache, jadi 20 dari 74 tes gagal setelah langkah 1b. Berkas ini tidak ada di tabel kait bagian 6. KEPUTUSAN PEMILIK: cache 5 menit DIPASANG (app/Models/AppConfig.php disalin dari aldy_merge).
- Selector section[aria-label="Company overview"|"Tickets"|"Recent activity"] tidak ada di dashboard eco_new. Aksen kartu tidak berefek (aman). #sidebarHeader juga tidak ada (aman).
- Menjalankan tes: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit ... (bukan artisan test).

## Hasil tes terakhir
- Langkah 1b: Unit+Feature Seasonal = 74 tes, 933 assertion, 2 gagal (LoginArtTest, SeasonalAssetsTest: butuh kait login dan settings).
- Langkah 3 dan 4: Unit+Feature Seasonal = 74 tes, 942 assertion, 0 gagal. Suite penuh (vendor/bin/phpunit dengan sqlite) = 298 tes, 3649 assertion, 0 gagal.
- Catatan langkah 3: overlay Natal lama di login (kereta luncur, bingkai es, jingle-bells) digantikan seasonal-loading generik seperti referensi. Fitur non-tema di login aldy_merge (tombol Report, penanganan autofill, penjaga kirim ganda) TIDAK dibawa karena bukan bagian tema.
- Langkah 3 QA visual (audit/step3-screens.md): 28 dari 28 PASS. Catatan minor: di 390 px tema aktif tetap memuat JS stage dan three.js (sekitar 690 KB) lewat pemanasan idle untuk loading pasca login (perilaku referensi, bukan gambar ilustrasi; bisa dikondisikan matchMedia min-width 768 bila ingin nol aset 3D di ponsel). Ilustrasi login adalah SVG datar, bukan render 3D seperti poster.
- Langkah 6a (query dan keamanan): 7 butir PASS. Login, forgot-password, 2FA: 1 query cache dingin, 0 hangat. Settings: 2 query (bukan dari tema). 11 partial tema dan login-art: 0 query. Risiko: cache AppConfig 5 menit berlaku semua kunci (disetujui pemilik), upload musik memakai slug plus time() (bukan acak), nilai sound tak valid jadi null tanpa pesan. Diff origin/eco_new...HEAD: 105 berkas (87 baru, 18 diubah), tanpa migrasi, composer, config, atau routes/web.php.
- INSIDEN: agent audit menghapus isi vendor lewat git worktree remove --force (worktree memakai junction ke vendor), lalu memulihkan dengan composer install dari composer.lock. Diverifikasi: composer.json dan composer.lock tidak berubah, suite penuh 298 tes lulus. Aturan: jangan membuat worktree yang meng-junction vendor.
- PERBAIKAN Settings kosong (24 Nov): public/build (Tailwind, di-commit, dibangun 2026-09-28) tidak memuat kelas sm:w-48, sm:px-8, sm:gap-8, sm:items-end, basis-44, accent-gray-700, sm:grid-cols-[1fr_9.5rem_9.5rem_auto]. Di Settings, kolom kiri melebar penuh dan mendorong kartu keluar layar. Perbaikan: pembungkus blok pemilih memakai kelas asli eco_new (px-8, w-48, gap-8) dan picker flex-wrap justify-end; halaman admin tetap identik dengan sumber, 4 utilitas yang hilang ditambahkan sebagai CSS kecil di blok CSS tema dashboard.blade.php. Diverifikasi dengan tangkapan layar Edge (Settings 6 tema, admin jadwal 4 kolom). ATURAN: jangan memakai kelas Tailwind baru di berkas tema tanpa memeriksa public/build, atau jalankan npm run build.

## Rencana lanjutan (24 Nov, permintaan pemilik)
| # | Langkah | Status |
|---|---|---|
| A | Ucapan per tema EN dan ID + logo EC beranimasi di loading screen dan pratinjau (CSS saja, sekali jalan, mati saat reduced motion). Lihat DEVIATIONS.md | SELESAI (commit 466930ca) |
| B | Baseline performa 6 adegan (NVIDIA, Intel, SwiftShader): audit/step6-perf-baseline.md | SELESAI |
| C | Perbaikan tersendat: compileAsync, unggah tekstur bertahap (warmTextures), renderer dipakai ulang di pratinjau, sampel downgrade tahan hitch. powerPreference TIDAK dipakai (Intel bukan penyebab) | SELESAI (69d00f68) |
| D | Ukur ulang: NVIDIA dan Intel 1280, frame stabil median 16,7 ms p95 sekitar 17 ms; longtask turun (lunar 837 ke 53 ms, crescent 959 ke 247 ms, sleigh 728 ke 475 ms dingin); render pertama 686 ke 33 ms (sleigh); tidak ada downgrade palsu. Sisa: sleigh NVIDIA dingin 475 ms sebelum frame pertama | SELESAI |
| E | QA UI dan fungsi (audit/step6-ui-function.md): 7 butir PASS, butir 8 menemukan B2 (tombol OK dialog danger berubah warna tema), DIPERBAIKI dengan kait data-danger di confirm-modal. B1 (kedip antar putaran pratinjau) DIPERBAIKI (canvas tidak dikosongkan dan tidak di-fade ulang), terverifikasi tangkapan layar berurutan. Kosmetik terbuka: K1 tinggi kartu pemilih tidak seragam, K2 kontras logo HUT RI 360 px, K3 HTML login none tidak byte identik (tampilan identik) | SELESAI |
| F | Audit keamanan ulang dan regresi sidebar oleh agent penjaga (audit/step-f-guardian.md): A 6 dari 6, B 7 dari 7, C lengkap PASS. Catatan: risiko 4 (klik grup saat ringkas menimpa pilihan tersimpan) diperbaiki menjadi pelebaran sementara | SELESAI |
| G | Audit akhir keamanan, query, regresi, performa: SELESAI, temuan diperbaiki (audit/step7-*.md). Ringkasan di RINGKASAN-PORT.md. Sisa: uji manual pemilik, merge origin/eco_new (4 commit README), push, PR | SELESAI |

- 24 Nov (arahan pemilik): ucapan di pratinjau dipindah ke tengah bawah (lebih besar, muncul bertahap logo lalu judul lalu doa), tombol Close ke kanan atas. Akhir tuntas: loading screen memudar 300 ms sebelum pindah halaman, pratinjau memudar lalu menutup sendiri (tidak menggantung di poster atau frame terakhir). Terverifikasi tangkapan layar.
- 24 Nov (arahan pemilik): sidebar ringkas (collapse ke ikon) dipasang, diuji Edge: 256 px ke 80 px, margin konten ikut berubah, tersimpan setelah reload (localStorage), klik grup menu melebarkan lagi, tanpa overflow horizontal. Tombol hamburger lama (sembunyikan penuh) tidak diubah. Panduan 010 (en, id) diperbarui. Tes 298 lulus.
- 24 Nov (arahan pemilik): Accent color dikunci saat tema musiman aktif (efektif: pilihan sendiri atau Default yang mengikuti tema global) dengan kotak peringatan, toast, dan tombol Turn off seasonal theme; color picker bebas ditambahkan; petunjuk warna terlalu terang. Diuji Edge (terkunci: klik swatch dan picker tidak mengubah warna; Turn off membuka kunci; picker mengubah --primary-color; pilih tema lagi mengunci ulang). Render Settings tetap 2 query. Panduan 021 (en, id) diperbarui, guide-check 0 peringatan dan 2 galat lama (062). Tes 298 lulus.
