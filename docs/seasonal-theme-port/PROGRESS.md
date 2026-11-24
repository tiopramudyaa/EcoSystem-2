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
| 2 | Kait dashboard.blade.php (a sampai i) | BELUM | |
| 3 | Kait login.blade.php dan 4 halaman auth, verifikasi 6 tema x 4 viewport | BELUM | |
| 4 | Kait pemilih tema settings/index.blade.php, cek halaman admin, pratinjau | BELUM | |
| 5 | Panduan pengguna 021 dan 148 (en, id), guide-check, tes Guide | BELUM | |
| 6 | QA performa, query dan keamanan, UI dan fungsi (tiap gerbang oleh agent) | BELUM | |
| 7 | Ringkasan port, bandingkan poster, cek git diff --stat origin/eco_new...aldy_eco | BELUM | |

## Catatan dan keputusan terbuka
- /settings error "Undefined array key label" sampai langkah 4 selesai (versi lama settings/index memakai kunci katalog lama). Itu perilaku yang diharapkan di antara langkah 1 dan 4.
- Tes Seasonal menyemai nilai lewat cache AppConfig (key app_config_<key>, bentuk {found,value}). AppConfig.php di eco_new belum memakai cache, jadi 20 dari 74 tes gagal setelah langkah 1b. Berkas ini tidak ada di tabel kait bagian 6. KEPUTUSAN PEMILIK: cache 5 menit DIPASANG (app/Models/AppConfig.php disalin dari aldy_merge).
- Selector section[aria-label="Company overview"|"Tickets"|"Recent activity"] tidak ada di dashboard eco_new. Aksen kartu tidak berefek (aman). #sidebarHeader juga tidak ada (aman).
- Menjalankan tes: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit ... (bukan artisan test).

## Hasil tes terakhir
- Langkah 1b: Unit+Feature Seasonal = 74 tes, 933 assertion, 2 gagal (LoginArtTest, SeasonalAssetsTest: butuh kait login dan settings).
