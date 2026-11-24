# Penyimpangan sengaja dari origin/aldy_merge

verify-port.sh membandingkan berkas tema dengan sumber. Berkas berikut SENGAJA berbeda setelah port, atas permintaan pemilik.
Bila verify-port menandai berkas ini, itu diharapkan. Berkas lain harus tetap identik.

| Berkas | Perubahan | Alasan |
|---|---|---|
| resources/views/partials/seasonal-loading.blade.php | Logo EC beranimasi (masuk sekali dan satu kilau), ucapan sebagai judul, baris status "Preparing" | Permintaan pemilik 24 Nov: ucapan profesional dan logo di loading screen |
| resources/views/partials/seasonal-preview.blade.php | Pratinjau menampilkan ucapan dan logo yang sama; aria-label menggantikan aria-labelledby | Konsisten dengan loading screen asli |
| lang/en/ui.php, lang/id/ui.php | Teks seasonal.loading.* (judul = ucapan, subjudul = doa, kunci baru preparing) | Ucapan baru EN dan ID |

Kait di berkas bersama (bukan salinan): dashboard.blade.php, auth/*.blade.php, settings/index.blade.php (tata letak memakai kelas yang ada di public/build), AuthController, SettingsController, CheckAuthToken, AppServiceProvider, AppConfig.php.

| public/js/seasonal/stage.js | compileAsync, warmTextures (unggah tekstur bertahap), reuseRenderer dan release(), sampel downgrade lebih tahan hitch | Perbaikan tersendat (data di audit/step6-perf-baseline.md) |
| resources/views/partials/seasonal-preview.blade.php | Loop pratinjau memakai ulang renderer dan canvas, poster tampil di jeda, renderer dilepas saat ditutup | Hitch loop kedua |
| resources/views/partials/confirm-modal.blade.php | Satu baris kait: atribut data-danger pada tombol OK varian danger (disalin dari aldy_merge). Fitur data-confirm form di aldy_merge TIDAK dibawa | Remap tema musiman membiarkan tombol destruktif tetap merah. Tanpa baris ini tombol OK dialog danger menjadi hijau tema (bug B2 dari QA) |
| resources/views/dashboard.blade.php (tambahan di luar kait tema) | Sidebar ringkas (ikon saja) khusus desktop: tombol panah di tepi sidebar, status di localStorage ec_sidebar_rail, CSS html[data-sb-rail], JS setSidebarRail dan toggleSidebarRail. Atribut data-rail pada aside (off bila halaman memakai section sidebar-nav). Tombol hamburger (sembunyikan penuh) tidak diubah | Permintaan pemilik 24 Nov: sidebar bisa collapse dan layout konten menyesuaikan. Murni CSS dan JS, tanpa query dan tanpa perubahan server |
| resources/guide/{en,id}/010-home-dashboard.md | Langkah 8 dan pertanyaan umum tentang sidebar ringkas | Aturan CLAUDE.md: fitur terlihat pengguna wajib ada panduan |
| resources/views/settings/index.blade.php (blok Accent color) | Color picker bebas (input type color, swatch terakhir), kunci pilihan warna selama tema musiman aktif (efektif) dengan kotak peringatan, toast peringatan saat dicoba, tombol Turn off seasonal theme, petunjuk warna terlalu terang | Permintaan pemilik 24 Nov. Murni sisi klien; server tetap memvalidasi regex #rrggbb; tanpa query tambahan (render Settings tetap 2 query) |
| lang/en/ui.php, lang/id/ui.php | Kunci seasonal.accent_* (6 kunci) | Teks peringatan warna |
