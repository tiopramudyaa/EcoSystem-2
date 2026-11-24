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
