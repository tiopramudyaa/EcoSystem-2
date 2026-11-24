# Langkah 7 (gerbang G): audit performa akhir Seasonal Theme

Status: audit read-only, tidak ada berkas repo selain dokumen ini yang diubah. Tanggal data: 2026-11-24. Branch aldy_eco, commit 8e2e64fc. Mesin: NVIDIA RTX 5060 Laptop dan Intel UHD (Edge headless, ANGLE D3D11), 60 Hz.
Berkas mentah (di luar repo): scratchpad `perf\results-final.json` (adegan 3D) dan `finalperf\pages.json` (halaman). Dokumen ditulis bertahap per butir.

## Butir 3: ukuran aset dan cache (statis)

| Folder | Berkas | Mentah (byte) | gzip -9 (byte) |
|---|---|---|---|
| public/js/seasonal | 29 | 340.793 | 119.376 |
| public/vendor/three | 3 | 692.851 (three.module.min.js 691.648) | 171.567 (three.module.min.js 170.759) |
| public/images/seasonal | 18 | 372.510 | 202.596 (webp nyaris tidak mengecil) |

- SVG login (mentah / gzip): lebaran 44.383 / 6.038, tahun_baru 43.931 / 9.468, natal 41.656 / 7.845, imlek 41.582 / 9.158, hut_ri 19.959 / 4.572, ramadan 19.311 / 4.148. Anggaran 45 KB mentah dan 4 sampai 10 KB gzip: PASS (terbesar 44.383, di bawah 46.080).
- Poster webp 16 sampai 36 KB, thumb 2 sampai 5 KB (sudah terkompresi, gzip tidak berguna).
- Chunk three.js terbesar saja 170 KB gzip; sisa JS adegan (stage, common, scenes) 119 KB gzip untuk semua 6 tema. Satu tema hanya memuat sebagian (lihat butir 1 halaman).
- Header cache: `public/.htaccess` (sudah ada, bukan hasil port ini) memasang `Cache-Control: public, max-age=31536000, immutable` untuk css/js/png/jpg/gif/svg/webp/ico/woff, `mod_expires` 1 tahun, dan `mod_deflate` untuk html/css/js/json/svg/font (webp tidak dikompres, benar). URL modul tema berversi `?v=<filemtime maks>` (seasonal-loading dan seasonal-preview, import map), poster `?v=filemtime`, jadi immutable aman. Hanya berlaku di Apache dengan mod_expires, mod_headers, mod_deflate aktif. Tidak diverifikasi di server produksi (nginx atau CDN akan butuh aturan sendiri). `php -S` pada uji ini tidak mengompres, jadi byte transfer di bawah adalah byte mentah.
