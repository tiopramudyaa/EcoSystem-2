# Ringkasan port Seasonal Theme (aldy_eco ke eco_new)

Per 25 November 2026. Rincian tiap gerbang ada di folder `audit/`, penyimpangan dari sumber di `DEVIATIONS.md`.

## Status

- Port, fitur tambahan, dan audit selesai di branch `aldy_eco`. Belum di-push.
- `origin/eco_new` maju 4 commit setelah sinkronisasi, hanya mengubah `README.md`. Uji merge: tanpa konflik, tanpa tumpang tindih berkas.
- Tes: 298 tes, 3649 assertion, 0 gagal (`php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit`).
- Panduan: `guide-check` 0 peringatan, 2 galat lama di topik 062 (`control-center.schedule-monitor`), bukan dari tema.
- Diff terhadap `origin/eco_new`: 95 berkas baru, 21 diubah. Tidak ada perubahan di `database`, `config`, `composer`, `routes`, `public/build`, atau `package.json`. Tidak ada migrasi.

## Isi perubahan

1. Tema musiman: Natal, Tahun Baru, Ramadan, Lebaran, Imlek, HUT RI (dashboard, navbar, sidebar, halaman login dan auth, loading screen 3D, pratinjau, Settings, admin Control Center).
2. Loading screen dan pratinjau: ucapan per tema (EN dan ID), logo EC beranimasi, akhir memudar.
3. Performa 3D: kompilasi shader dan unggah tekstur bertahap, renderer dipakai ulang di pratinjau.
4. Settings: warna aksen dikunci saat tema aktif (peringatan dan tombol matikan tema), color picker bebas.
5. Sidebar ringkas (ikon saja) di desktop, tersimpan di browser.
6. Panduan pengguna topik 010, 021, 148 (EN dan ID).

## Berkas salinan dan kait

`verify-port.sh` menandai 7 berkas BEDA, semuanya tercatat di `DEVIATIONS.md`: `stage.js`, `seasonal-loading`, `seasonal-preview`, `seasonal-theme`, `SeasonalThemeSettingsController`, dan panduan 021 (EN, ID). Berkas salinan lain identik dengan `origin/aldy_merge`.

| Berkas kait | Baris tambah / hapus |
|---|---|
| resources/views/settings/index.blade.php | 200 / 57 |
| resources/views/dashboard.blade.php | 118 / 14 |
| resources/views/auth/login.blade.php | 71 / 341 (overlay Natal lama diganti loading generik) |
| app/Models/AppConfig.php | 27 / 2 |
| app/Http/Controllers/AuthController.php | 21 / 0 |
| app/Http/Controllers/SettingsController.php | 13 / 3 |
| app/Http/Middleware/CheckAuthToken.php | 5 / 5 |
| app/Providers/AppServiceProvider.php | 3 / 0 |
| 4 halaman auth lain | masing-masing 2 / 1 |
| resources/views/partials/confirm-modal.blade.php | 2 / 0 |

## Hasil per gerbang

| Gerbang | Hasil | Bukti |
|---|---|---|
| Kait dashboard | PASS | audit/step2-review.md |
| Login 7 kondisi x 4 viewport | 28 dari 28 PASS | audit/step3-screens.md |
| Query dan keamanan | PASS | audit/step6-security-query.md, step7-final-security.md |
| UI dan fungsi | 7 PASS, 2 bug ditemukan dan diperbaiki | audit/step6-ui-function.md |
| Sidebar ringkas dan regresi | PASS | audit/step-f-guardian.md |
| Performa | PASS dengan pengecualian di bawah | audit/step6-perf-baseline.md, step7-final-performance.md |

Query: partial tema 0 query. Login 0 (cache hangat) atau 1 (dingin). Settings 2.

Performa 3D (36 kombinasi, NVIDIA dan Intel): median 16,7 ms di semua kasus. p95 di bawah 33 ms pada 35 kasus. Tidak ada objek WebGL tersisa setelah 20 putaran. Longtask tunggal maksimum 396 ms (sebelum perbaikan sampai 1254 ms), terjadi sebelum frame pertama. Halaman: CLS 0 (satu kasus 0,0002), scroll dengan tema aktif tanpa frame di atas 33 ms.

## Anggaran yang belum terpenuhi

| Butir | Keadaan |
|---|---|
| p95 frame di Intel UHD 1920x1080 skala 1,5, adegan Natal | 50 ms (steady 33,5 ms) |
| Tema none tanpa byte tambahan | HTML login bertambah 31,9 KB (7,3 KB gzip) karena layar loading generik dirender selalu. Tidak ada aset tema yang diunduh |
| Tanpa recalc dan layout saat diam | Natal, Tahun Baru, HUT RI: 14 sampai 18 per 3 detik dari animasi partikel |
| Pertumbuhan heap di bawah 1,5 MB per 20 putaran | Belum konklusif: 0,6 sampai 1,2 MB setelah pemanasan, 2,7 sampai 5,6 MB dari putaran pertama |
| Klik ke frame pertama saat cache dingin | 227 sampai 1313 ms, terberat Natal di NVIDIA; angka bervariasi antar sesi |

## Tidak dapat diverifikasi

4K, waktu GPU dan paint, ponsel asli, Safari, Firefox, kompresi dan header cache produksi, jaringan lambat nyata, dashboard Home dengan data asli, kontras numerik. Aturan `prefers-contrast` untuk ornamen sidebar ditambahkan setelah audit dan belum diukur ulang di browser. Semua uji browser memakai HTML yang dirender tanpa database; alur login sampai dashboard dengan akun nyata perlu diuji manual.

## Hal yang perlu diketahui sebelum rilis

- Cache `AppConfig` 5 menit berlaku untuk kunci selain `security.*`. Pada lebih dari satu instance dengan cache lokal, tema global dan pengaturan AI bisa terlambat sampai 5 menit di instance lain.
- Overlay Natal lama di login (kereta luncur, bingkai es, jingle-bells) digantikan loading screen generik.
- Pengguna yang belum pernah menyimpan preferensi sekarang mengikuti tema admin (dulu tidak).
- Pilihan sidebar ringkas disimpan per browser. Label tombolnya berbahasa Inggris.
- Perubahan di luar tema atas permintaan pemilik: sidebar ringkas, color picker, kunci warna aksen.
- Di 390 px, modul 3D tetap diunduh saat idle di halaman login bertema (ilustrasi tidak diunduh).
- Settings memakai kelas non-responsif karena `public/build` tidak memuat sebagian kelas `sm:`. Layar sangat sempit belum diuji.
- Logo EC berwarna abu-abu tampak redup di atas sebagian adegan 3D.

## Langkah merge dan deploy

1. `git fetch origin`, lalu `git merge origin/eco_new` di `aldy_eco`, jalankan suite tes.
2. Uji manual di server lokal dengan akun nyata: login, Settings (tema, Off, warna), admin Seasonal Theme (manual, jadwal, musik), sidebar ringkas, dialog hapus.
3. Push `aldy_eco`, buat PR ke `eco_new`.
4. Setelah deploy: `php artisan view:clear` dan `php artisan cache:clear`.
5. Pastikan `public/js/seasonal`, `public/vendor/three`, `public/images/seasonal` ikut terkirim, dan kompresi serta header cache aktif (`curl -I --compressed`).
6. `npm run build` bukan syarat tema. Bila pipeline membangunnya ulang, periksa juga halaman lain.
7. Bila ada CSP di infrastruktur, izinkan skrip inline, import map, dan `img-src data:`.

## Mundur

Tanpa deploy: Control Center, Seasonal Theme, atur tema global `Off` dan tema login `inherit`. Perubahan lain (sidebar ringkas, color picker, tampilan login) hanya hilang dengan `git revert` merge commit. Tidak ada perubahan database yang perlu dibatalkan.
