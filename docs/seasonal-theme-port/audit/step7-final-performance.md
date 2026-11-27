# Langkah 7 (gerbang G): audit performa akhir Seasonal Theme

Status: audit read-only, tidak ada berkas repo selain dokumen ini yang diubah. Tanggal data: 2026-11-24 (butir 1 dan ukuran aset, commit 8e2e64fc) dan 2026-11-25 (butir 2 sampai 5, HEAD 9624e2e7; lihat Catatan HEAD). Branch aldy_eco. Kesimpulan ada di Butir 5 di akhir dokumen. Mesin: NVIDIA RTX 5060 Laptop dan Intel UHD (Edge headless, ANGLE D3D11), 60 Hz.
Berkas mentah (di luar repo): scratchpad `perf\results-final.json` (adegan 3D) dan `finalperf\pages.json` (halaman). Dokumen ditulis bertahap per butir.

## Butir 3: ukuran aset dan cache (statis)

| Folder | Berkas | Mentah (byte) | gzip -9 (byte) |
|---|---|---|---|
| public/js/seasonal | 29 | 340.793 | 119.376 |
| public/vendor/three | 3 | 692.851 (three.module.min.js 691.648) | 171.567 (three.module.min.js 170.759) |
| public/images/seasonal | 18 | 372.510 | 202.596 (webp nyaris tidak mengecil) |

- SVG login (mentah / gzip): lebaran 44.383 / 6.038, tahun_baru 43.931 / 9.468, natal 41.656 / 7.845, imlek 41.582 / 9.158, hut_ri 19.959 / 4.572, ramadan 19.311 / 4.148. Anggaran 45 KB mentah dan 4 sampai 10 KB gzip: PASS (terbesar 44.383, di bawah 46.080).
- Poster webp 16 sampai 36 KB, thumb 2 sampai 5 KB (sudah terkompresi, gzip tidak berguna).
- Chunk three.js terbesar saja 170 KB gzip; sisa JS adegan (stage, common, scenes) 119 KB gzip untuk semua 6 tema. Satu tema hanya memuat sebagian (lihat butir 2).
- Header cache: `public/.htaccess` (sudah ada, bukan hasil port ini) memasang `Cache-Control: public, max-age=31536000, immutable` untuk css/js/png/jpg/gif/svg/webp/ico/woff, `mod_expires` 1 tahun, dan `mod_deflate` untuk html/css/js/json/svg/font (webp tidak dikompres, benar). URL modul tema berversi `?v=<filemtime maks>` (seasonal-loading dan seasonal-preview, import map), poster `?v=filemtime`, jadi immutable aman. Hanya berlaku di Apache dengan mod_expires, mod_headers, mod_deflate aktif. Tidak diverifikasi di server produksi (nginx atau CDN akan butuh aturan sendiri). `php -S` pada uji ini tidak mengompres, jadi byte transfer di bawah adalah byte mentah.

## Butir 1: adegan 3D (kondisi akhir, label final)

Harness `perf\run.mjs final`, `--headless=new`, Edge baru per konfigurasi (cache shader dingin), 6 konfigurasi GPU nyata: nv-1280, nv-1920, nv-1920-dpr1.5, intel-1280, intel-1920, intel-1920-dpr1.5, 6 adegan, 3 putaran berturut-turut tanpa reload (putaran 1 dingin) dan satu putaran setelah `preload()`. LEAK=1 (20 putaran, kanvas baru tiap putaran, duration 700 ms, GC dipaksa) aktif di nv-1280 dan intel-1280. Renderer terkonfirmasi: ANGLE NVIDIA RTX 5060 Laptop (D3D11) dan ANGLE Intel UHD (D3D11). Frame dibatasi vsync 60 Hz, sehingga median 16,7 ms adalah lantai pengukuran dan bukan waktu GPU; ketersediaan headroom hanya terbaca lewat CPU `render()` (tidak ada timer query GPU). 4K tidak diuji (maksimum 1920x1080 dpr 1,5 = 2880x1620).

Data run ini lebih berisik daripada run `after2` (variasi cold start antar sesi: longtask run final nv-1280 fireworks 79 ms vs 0 ms di after2; sleigh NVIDIA tetap yang terberat). Frame stabil identik dengan baseline.

### Ringkasan angka (36 kasus: 6 adegan x 6 konfigurasi)

- Median frame 16,7 ms di semua 36 kasus (anggaran median < 20 ms: PASS).
- p95 frame jendela dingin penuh: 16,8 sampai 17,4 ms di 34 kasus, 23,6 ms (nv-1920-dpr1.5 crescent) dan 50,0 ms (intel-1920-dpr1.5 sleigh). Anggaran p95 < 33 ms: 35 dari 36 PASS; intel-1920-dpr1.5 sleigh FAIL (Intel UHD pada 2880x1620 dengan 53 draw call dan 101 ribu segitiga; satu-satunya kasus dengan frame > 33 ms yang berulang di fase steady: 7 frame, steady p95 33,5 ms).
- Frame > 33 ms per jendela 2,6 detik: 1 sampai 9. Di 35 kasus lain semuanya hitch awal (steady > 33 ms: 0 sampai 2); kasus intel-1920-dpr1.5 sleigh 9 total, 7 steady.
- Longtask per putaran 1 dingin: total 64 sampai 740 ms, tunggal terbesar 396 ms (sebelum perbaikan 317 sampai 1254 ms tunggal). Terjadi sebelum frame pertama (poster tampil), bukan di tengah animasi.
- CPU `render()`: median 0,3 sampai 1,2 ms, p95 maks 2,3 ms (proxy untuk anggaran "median di bawah 2,5 ms": PASS pada proxy CPU, tidak terverifikasi sebagai waktu GPU atau 4K).
- Draw call maks 55 (sleigh di Intel; NVIDIA 49), anggaran maks 55: PASS (tepat di batas). Segitiga maks 101.180. Program 13 sampai 20, geometri 22 sampai 39, tekstur 4 sampai 18.
- pixelRatio akhir 1 (dpr 1) dan 1,5 (dpr 1,5): auto-downgrade palsu ke 0,75 yang ada di baseline TIDAK terjadi lagi di 36 dari 36 kasus.
- Waktu ke frame pertama (klik ke rAF pertama, putaran 1 dingin): 227 sampai 1313 ms (NVIDIA 1280: 283 sampai 947; Intel 1280: 253 sampai 409); putaran 3: 128 sampai 407 ms (baseline 111 sampai 440, setelah perbaikan 103 sampai 197 pada run after). Putaran dingin pertama di atas 800 ms: sleigh NVIDIA (947 ms di 1280, 1248 ms di 1920, 1313 ms di 1920 dpr 1,5) dan crescent NVIDIA 1280 (801 ms). Baseline sebelum perbaikan: 351 sampai 1331 ms.

### Tabel per konfigurasi (final)

#### final nv-1280 (1280x720 dpr 1)

| Adegan | median / p95 (ms) | steady p95 | max | >33 / >50 | longtask n / ms | klik ke frame 1 (dingin) | render() pertama | CPU render median / p95 | putaran 3: klik ke frame 1 | draw call | segitiga | geom / tex / prog | pixelRatio akhir |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 17.1 | 16.9 | 700 | 5 / 3 | 2 / 491 | 947 | 45 | 0.5 / 1.4 | 188 | 49 | 91056 | 35 / 7 / 19 | 1 |
| fireworks | 16.7 / 16.9 | 16.9 | 67 | 3 / 1 | 1 / 79 | 283 | 46 | 0.4 / 0.8 | 157 | 33 | 51516 | 26 / 4 / 17 | 1 |
| crescent | 16.7 / 17.0 | 16.9 | 317 | 3 / 3 | 3 / 355 | 801 | 92 | 0.6 / 1.0 | 225 | 41 | 64868 | 22 / 12 / 20 | 1 |
| eid | 16.7 / 16.9 | 16.8 | 167 | 3 / 2 | 3 / 293 | 503 | 67 | 0.5 / 1.0 | 298 | 41 | 50187 | 34 / 18 / 19 | 1 |
| lunar | 16.7 / 16.8 | 16.8 | 217 | 3 / 2 | 1 / 80 | 499 | 29 | 0.4 / 0.8 | 201 | 39 | 58314 | 38 / 13 / 19 | 1 |
| flag | 16.7 / 16.8 | 16.8 | 90 | 3 / 3 | 1 / 74 | 307 | 39 | 0.3 / 0.7 | 164 | 40 | 91458 | 39 / 7 / 13 | 1 |

#### final nv-1920 (1920x1080 dpr 1)

| Adegan | median / p95 (ms) | steady p95 | max | >33 / >50 | longtask n / ms | klik ke frame 1 (dingin) | render() pertama | CPU render median / p95 | putaran 3: klik ke frame 1 | draw call | segitiga | geom / tex / prog | pixelRatio akhir |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 16.9 | 16.9 | 567 | 3 / 3 | 3 / 677 | 1248 | 214 | 0.7 / 1.4 | 217 | 49 | 91056 | 35 / 7 / 19 | 1 |
| fireworks | 16.7 / 17.0 | 17.0 | 67 | 2 / 2 | 1 / 76 | 326 | 42 | 0.5 / 1.3 | 181 | 33 | 51136 | 26 / 4 / 17 | 1 |
| crescent | 16.7 / 17.0 | 17.0 | 300 | 3 / 3 | 4 / 409 | 774 | 85 | 0.6 / 1.2 | 296 | 41 | 64868 | 22 / 12 / 20 | 1 |
| eid | 16.7 / 16.9 | 16.9 | 217 | 2 / 2 | 3 / 365 | 523 | 77 | 0.9 / 1.7 | 328 | 41 | 50187 | 34 / 18 / 19 | 1 |
| lunar | 16.7 / 17.1 | 16.9 | 102 | 3 / 3 | 1 / 147 | 746 | 40 | 0.7 / 1.4 | 210 | 39 | 58314 | 38 / 13 / 19 | 1 |
| flag | 16.7 / 17.1 | 17.0 | 50 | 2 / 0 | 1 / 81 | 280 | 29 | 0.7 / 1.4 | 196 | 40 | 91458 | 39 / 7 / 13 | 1 |

#### final nv-1920-dpr1.5 (1920x1080 dpr 1.5)

| Adegan | median / p95 (ms) | steady p95 | max | >33 / >50 | longtask n / ms | klik ke frame 1 (dingin) | render() pertama | CPU render median / p95 | putaran 3: klik ke frame 1 | draw call | segitiga | geom / tex / prog | pixelRatio akhir |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 17.4 | 16.9 | 750 | 3 / 3 | 3 / 740 | 1313 | 242 | 0.7 / 1.3 | 165 | 49 | 91056 | 35 / 7 / 19 | 1.5 |
| fireworks | 16.7 / 17.0 | 16.9 | 67 | 2 / 1 | 1 / 83 | 287 | 29 | 0.6 / 1.1 | 162 | 33 | 51516 | 26 / 4 / 17 | 1.5 |
| crescent | 16.7 / 23.6 | 17.1 | 283 | 5 / 5 | 3 / 302 | 755 | 69 | 0.7 / 1.4 | 207 | 41 | 64868 | 22 / 12 / 20 | 1.5 |
| eid | 16.7 / 17.0 | 16.8 | 247 | 3 / 3 | 3 / 360 | 687 | 85 | 0.7 / 1.3 | 407 | 41 | 50187 | 34 / 18 / 19 | 1.5 |
| lunar | 16.7 / 17.0 | 17.0 | 133 | 1 / 1 | 1 / 143 | 512 | 26 | 0.7 / 1.3 | 201 | 39 | 58314 | 38 / 13 / 19 | 1.5 |
| flag | 16.7 / 16.8 | 16.8 | 83 | 4 / 2 | 1 / 78 | 305 | 30 | 0.4 / 0.7 | 152 | 40 | 91458 | 39 / 7 / 13 | 1.5 |

#### final intel-1280 (1280x720 dpr 1)

| Adegan | median / p95 (ms) | steady p95 | max | >33 / >50 | longtask n / ms | klik ke frame 1 (dingin) | render() pertama | CPU render median / p95 | putaran 3: klik ke frame 1 | draw call | segitiga | geom / tex / prog | pixelRatio akhir |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 16.9 | 16.8 | 84 | 3 / 1 | 2 / 158 | 362 | 64 | 0.5 / 0.8 | 154 | 55 | 101180 | 38 / 7 / 19 | 1 |
| fireworks | 16.7 / 17.1 | 17.0 | 67 | 1 / 1 | 1 / 82 | 270 | 37 | 0.5 / 1.1 | 130 | 33 | 51516 | 26 / 4 / 17 | 1 |
| crescent | 16.7 / 16.9 | 16.8 | 133 | 2 / 2 | 2 / 196 | 380 | 74 | 0.6 / 1.0 | 177 | 41 | 64868 | 22 / 12 / 20 | 1 |
| eid | 16.7 / 16.9 | 16.9 | 138 | 3 / 2 | 3 / 232 | 409 | 60 | 0.5 / 1.3 | 246 | 41 | 50187 | 34 / 18 / 19 | 1 |
| lunar | 16.7 / 16.8 | 16.8 | 69 | 3 / 1 | 1 / 79 | 314 | 33 | 0.4 / 0.7 | 158 | 39 | 58314 | 38 / 13 / 19 | 1 |
| flag | 16.7 / 16.9 | 16.9 | 67 | 3 / 1 | 1 / 64 | 254 | 38 | 0.4 / 1.2 | 143 | 40 | 91458 | 39 / 7 / 13 | 1 |

#### final intel-1920 (1920x1080 dpr 1)

| Adegan | median / p95 (ms) | steady p95 | max | >33 / >50 | longtask n / ms | klik ke frame 1 (dingin) | render() pertama | CPU render median / p95 | putaran 3: klik ke frame 1 | draw call | segitiga | geom / tex / prog | pixelRatio akhir |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 16.8 | 16.8 | 86 | 3 / 3 | 3 / 297 | 536 | 67 | 0.6 / 1.1 | 181 | 53 | 101128 | 36 / 7 / 19 | 1 |
| fireworks | 16.7 / 17.0 | 16.9 | 83 | 1 / 1 | 1 / 91 | 287 | 42 | 0.5 / 1.1 | 148 | 33 | 51516 | 26 / 4 / 17 | 1 |
| crescent | 16.7 / 17.0 | 17.0 | 101 | 3 / 3 | 2 / 192 | 363 | 73 | 0.7 / 1.3 | 195 | 41 | 64868 | 22 / 12 / 20 | 1 |
| eid | 16.7 / 17.4 | 17.0 | 117 | 3 / 3 | 3 / 249 | 418 | 63 | 0.8 / 1.7 | 227 | 41 | 50187 | 34 / 18 / 19 | 1 |
| lunar | 16.7 / 16.9 | 16.9 | 117 | 2 / 2 | 1 / 125 | 331 | 29 | 1.2 / 2.3 | 164 | 39 | 58314 | 38 / 13 / 19 | 1 |
| flag | 16.7 / 17.0 | 17.0 | 50 | 2 / 0 | 1 / 67 | 227 | 37 | 0.7 / 1.2 | 173 | 40 | 91458 | 39 / 7 / 13 | 1 |

#### final intel-1920-dpr1.5 (1920x1080 dpr 1.5)

| Adegan | median / p95 (ms) | steady p95 | max | >33 / >50 | longtask n / ms | klik ke frame 1 (dingin) | render() pertama | CPU render median / p95 | putaran 3: klik ke frame 1 | draw call | segitiga | geom / tex / prog | pixelRatio akhir |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 50.0 | 33.5 | 167 | 9 / 4 | 3 / 326 | 448 | 150 | 0.7 / 1.5 | 157 | 53 | 101128 | 36 / 7 / 19 | 1.5 |
| fireworks | 16.7 / 16.9 | 16.9 | 67 | 3 / 2 | 1 / 82 | 265 | 41 | 0.8 / 1.4 | 128 | 33 | 51516 | 26 / 4 / 17 | 1.5 |
| crescent | 16.7 / 17.2 | 16.9 | 267 | 3 / 2 | 3 / 227 | 358 | 62 | 1.0 / 1.8 | 240 | 41 | 64868 | 22 / 12 / 20 | 1.5 |
| eid | 16.7 / 17.0 | 16.9 | 184 | 3 / 3 | 3 / 325 | 499 | 69 | 1.1 / 1.9 | 250 | 41 | 50187 | 34 / 18 / 19 | 1.5 |
| lunar | 16.7 / 16.9 | 16.9 | 118 | 3 / 1 | 1 / 70 | 335 | 31 | 1.2 / 1.8 | 139 | 39 | 58314 | 38 / 13 / 19 | 1.5 |
| flag | 16.7 / 17.2 | 17.0 | 54 | 1 / 1 | 1 / 73 | 235 | 37 | 0.7 / 1.7 | 158 | 40 | 91458 | 39 / 7 / 13 | 1.5 |

#### Perbandingan nv-1280 (sebelum / setelah perbaikan / final), putaran 1 dingin

| Adegan | longtask ms | frame max ms | frame >33 | klik ke frame 1 ms | render() pertama ms | putaran 3 klik ke frame 1 ms | pixelRatio akhir |
|---|---|---|---|---|---|---|---|
| sleigh | 728 / 475 / 491 | 517 / 433 / 700 | 5 / 3 / 5 | 757 / 662 / 947 | 686 / 33 / 45 | 238 / 104 / 188 | 0.75 / 1 / 1 |
| fireworks | 430 / 0 / 79 | 417 / 34 / 67 | 1 / 1 / 3 | 467 / 179 / 283 | 392 / 28 / 46 | 234 / 108 / 157 | 0.75 / 1 / 1 |
| crescent | 959 / 247 / 355 | 933 / 267 / 317 | 1 / 3 / 3 | 996 / 563 / 801 | 904 / 80 / 92 | 344 / 145 / 225 | 0.75 / 1 / 1 |
| eid | 889 / 168 / 293 | 867 / 117 / 167 | 1 / 2 / 3 | 926 / 337 / 503 | 829 / 44 / 67 | 427 / 198 / 298 | 0.75 / 1 / 1 |
| lunar | 837 / 53 / 80 | 817 / 50 / 217 | 1 / 1 / 3 | 873 / 373 / 499 | 803 / 27 / 29 | 321 / 128 / 201 | 0.75 / 1 / 1 |
| flag | 317 / 0 / 74 | 300 / 50 / 90 | 2 / 1 / 3 | 351 / 213 / 307 | 274 / 31 / 39 | 196 / 117 / 164 | 0.75 / 1 / 1 |

#### Perbandingan intel-1280 (sebelum / setelah perbaikan / final), putaran 1 dingin

| Adegan | longtask ms | frame max ms | frame >33 | klik ke frame 1 ms | render() pertama ms | putaran 3 klik ke frame 1 ms | pixelRatio akhir |
|---|---|---|---|---|---|---|---|
| sleigh | 446 / 55 / 158 | 433 / 50 / 84 | 1 / 1 / 3 | 481 / 223 / 362 | 402 / 44 / 64 | 133 / 112 / 154 | 0.75 / 1 / 1 |
| fireworks | 335 / 0 / 82 | 317 / 33 / 67 | 1 / 0 / 1 | 373 / 170 / 270 | 291 / 25 / 37 | 111 / 103 / 130 | 0.75 / 1 / 1 |
| crescent | 989 / 121 / 196 | 967 / 50 / 133 | 1 / 2 / 2 | 1049 / 247 / 380 | 848 / 53 / 74 | 327 / 139 / 177 | 0.75 / 1 / 1 |
| eid | 1254 / 133 / 232 | 1250 / 83 / 138 | 1 / 2 / 3 | 1331 / 281 / 409 | 1143 / 47 / 60 | 408 / 170 / 246 | 0.75 / 1 / 1 |
| lunar | 1019 / 0 / 79 | 1000 / 34 / 69 | 1 / 1 / 3 | 1074 / 215 / 314 | 957 / 24 / 33 | 306 / 109 / 158 | 0.75 / 1 / 1 |
| flag | 516 / 0 / 64 | 500 / 34 / 67 | 2 / 1 / 3 | 586 / 158 / 254 | 402 / 30 / 38 | 306 / 112 / 143 | 0.75 / 1 / 1 |

#### Uji 20 putaran (duration 700 ms, kanvas baru tiap putaran, GC dipaksa)

Kolom: konteks aktif maks setelah putaran / konteks dibuat total / geom,tex,prog tersisa setelah dispose (maks) / heap putaran 1, 10, 20 (MB) / drift 1 ke 20 / drift terbesar antar rata-rata 5 putaran terakhir vs 5 pertama.

| Config | Adegan | ctx aktif maks | ctx total | sisa geom/tex/prog | heap 1 / 10 / 20 | drift (MB) | rata 16-20 minus rata 1-5 (MB) |
|---|---|---|---|---|---|---|---|
| nv-1280 | sleigh | 0 | 21 | 0/0/0 | 6.09 / 8.41 / 9.33 | 3.24 | 1.62 |
| nv-1280 | fireworks | 0 | 21 | 0/0/0 | 5.41 / 7.17 / 8.15 | 2.74 | 1.43 |
| nv-1280 | crescent | 0 | 21 | 0/0/0 | 9.13 / 14.06 / 14.68 | 5.55 | 2.00 |
| nv-1280 | eid | 0 | 21 | 0/0/0 | 11.38 / 15.66 / 16.25 | 4.87 | 1.84 |
| nv-1280 | lunar | 0 | 21 | 0/0/0 | 5.41 / 7.40 / 8.56 | 3.15 | 1.80 |
| nv-1280 | flag | 0 | 21 | 0/0/0 | 5.86 / 8.31 / 9.00 | 3.14 | 0.87 |
| intel-1280 | sleigh | 0 | 21 | 0/0/0 | 6.09 / 8.41 / 9.32 | 3.23 | 1.50 |
| intel-1280 | fireworks | 0 | 21 | 0/0/0 | 5.41 / 7.17 / 8.15 | 2.74 | 1.48 |
| intel-1280 | crescent | 0 | 21 | 0/0/0 | 9.13 / 14.06 / 14.68 | 5.55 | 2.04 |
| intel-1280 | eid | 0 | 21 | 0/0/0 | 11.38 / 15.66 / 16.25 | 4.87 | 1.84 |
| intel-1280 | lunar | 0 | 21 | 0/0/0 | 5.36 / 7.40 / 8.56 | 3.20 | 1.81 |
| intel-1280 | flag | 0 | 21 | 0/0/0 | 5.86 / 8.31 / 9.00 | 3.14 | 1.13 |

#### Agregat final (6 adegan, semua config GPU nyata)

- Median frame: semua 36 kasus 16.7 sampai 16.7 ms.
- p95 frame (jendela dingin penuh): maks 50.0 ms; kasus p95 > 33: intel-1920-dpr1.5/sleigh 50.0.
- p95 steady (tanpa 10 frame awal): maks 33.5 ms.
- Frame >33 ms per jendela 2,6 detik: 1 sampai 9; steady >33: maks 7.
- Longtask total ms: 64 sampai 740; longtask tunggal terbesar: 396 ms.
- CPU render() median 0.30000001192092896 sampai 1.2000000178813934 ms, p95 maks 2.300000011920929 ms.
- Draw call maks 55; segitiga maks 101180.
- pixelRatio akhir: nv-1280:1 nv-1920:1 nv-1920-dpr1.5:1.5 intel-1280:1 intel-1920:1 intel-1920-dpr1.5:1.5

Catatan kebocoran: konteks aktif 0 setelah tiap putaran, 21 konteks dibuat total (20 putaran + 1 probe) dan semuanya terlepas; setelah dispose geometri, tekstur, dan program tersisa 0/0/0 di 12 dari 12 kasus (anggaran "tanpa kebocoran buffer, program, VAO, shader dalam 20 putaran": PASS pada tingkat `renderer.info` dan konteks; VAO dan shader mentah tidak dihitung terpisah, terbebas bersama konteks yang hilang).

Heap JS (anggaran tumbuh < 1,5 MB dalam 20 putaran): heap putaran 1 ke 20 naik 2,7 sampai 5,6 MB (termasuk pemanasan JIT dan cache modul pada 5 sampai 10 putaran pertama); putaran 10 ke 20 naik 0,6 sampai 1,2 MB; regresi linear putaran 6 sampai 20 menunjukkan sekitar 0,07 sampai 0,13 MB per putaran (1,4 sampai 2,6 MB per 20 putaran). Hasil: FAIL tipis terhadap angka 1,5 MB bila dihitung dari putaran 1 atau dari tren linear, PASS bila dihitung setelah pemanasan (10 ke 20, maks 1,16 MB). Sama dengan baseline (naik +2 sampai +8 MB, tidak ada regresi), heap bergerigi dan bukan eksponensial, dan tidak ada objek GL yang tersisa. Disimpulkan bukan kebocoran GL; sisa drift kecil dan belum bisa dibedakan dari pertumbuhan heap normal tanpa snapshot heap.

## Catatan HEAD (lanjutan 25 Nov)

Butir 1 dan ukuran aset diukur pada 8e2e64fc. HEAD sekarang 9624e2e7. Yang berubah di `app/` hanya `AppConfig::get` (kunci `security.*` tidak di-cache) dan cast `(array)` jadwal di controller admin; sisanya dokumen. HTML dirender ulang dari 9624e2e7 dan dibandingkan dengan HTML yang dipakai pengukuran halaman: 7 halaman login identik, 7 halaman Settings hanya berbeda pada posisi dan ukuran partikel yang diacak tiap render serta tanggal hari ini. Hasil `pages.json` (14 login, 14 Settings, 9 scroll, 3 rail; lengkap dan valid) tetap berlaku. Pengukuran yang meragukan diulang (`re.json`): heap setelah GC, scroll 3 kali ulang, status rail, kontrol positif pemuatan lazy, dan toggle rail.

Query: render Settings kini 7 query (sebelumnya tercatat 2): 6 `select ... app_configs where key = 'security.microsoft_login'` dan 1 hitungan `staging_tickets`. Daftarnya identik untuk `none` dan natal, jadi tambahan dari tema tetap 0. Enam query itu akibat 9624e2e7 yang sengaja tidak meng-cache kunci keamanan, sama dengan perilaku sebelum port (AppConfig tanpa cache di cb765c92). Render login: 0 query pada 7 halaman (cache tema hangat). Cache disetel ke driver array; tidak ada penulisan database.

## Butir 2: halaman

Metode: HTML hasil render dilayani `php -S` di port 8094, Edge headless (d3d11), cache HTTP dimatikan, host eksternal (Google Fonts) diblokir dan tidak dihitung (1 permintaan per halaman). 1280x800, serta lebar efektif 390x844 lewat `Emulation.setDeviceMetricsOverride` (dpr 1, mobile false). Byte adalah `encodedDataLength` tanpa kompresi. Login ditunggu 4,5 detik setelah load agar pemanasan idle ikut terhitung. Halaman Settings memakai layout dashboard, jadi mewakili sidebar dan navbar; halaman home dashboard tidak dirender (butuh data database).

### Login: bobot transfer

| Tema | 1280: request / KB | aset tema 1280: n / KB mentah (gzip hitung) | gambar 1280 | 390: request / KB | aset tema 390: n / KB mentah (gzip hitung) | gambar 390 |
|---|---|---|---|---|---|---|
| none | 3 / 195 | 0 / 0 (0) | 1 (logo) | 2 / 191 | 0 / 0 (0) | 1 (logo) |
| natal | 10 / 985 | 8 / 787 (200) | 2 | 9 / 944 | 7 / 746 (192) | 1 (logo) |
| tahun_baru | 11 / 988 | 9 / 790 (202) | 2 | 10 / 945 | 8 / 747 (193) | 1 (logo) |
| imlek | 11 / 1011 | 9 / 813 (209) | 2 | 10 / 970 | 8 / 772 (200) | 1 (logo) |
| ramadan | 11 / 969 | 9 / 771 (199) | 2 | 10 / 950 | 8 / 752 (195) | 1 (logo) |
| lebaran | 13 / 1041 | 11 / 843 (216) | 2 | 12 / 997 | 10 / 799 (210) | 1 (logo) |
| hut_ri | 10 / 975 | 8 / 777 (201) | 2 | 9 / 955 | 7 / 757 (197) | 1 (logo) |

- Ilustrasi login (SVG) TIDAK diminta di 390 px pada keenam tema: PASS. Di bawah 768 px satu-satunya gambar adalah logo bawaan (108 KB PNG, juga ada di `none`).
- Modul 3D (three.js 691.807 byte plus modul adegan) tetap diunduh di 390 px lewat pemanasan idle. Ini sesuai bunyi anggaran, tetapi 746 sampai 799 KB mentah di ponsel.
- HTML login: `none` 84,6 KB (gzip 19,7 KB); tema aktif 91,6 sampai 91,9 KB (gzip +1,6 sampai +1,7 KB).
- HTML login `none` dibandingkan view sebelum port (cb765c92 diekstrak dengan `git archive` ke scratchpad lalu dirender dengan kode aplikasi sekarang): 52,7 KB menjadi 84,6 KB, yaitu +31,9 KB mentah atau +7,3 KB gzip. Isinya terutama `partials.seasonal-loading` (CSS 6,6 KB, import map 2,7 KB, data tema `window.__seasonalThemes` 7,3 KB, markup) yang disisipkan juga saat `none`. Tidak ada permintaan jaringan tambahan. Ini bertentangan dengan bunyi harfiah "Tema none: 0 byte tambahan dari partial tema".

### Login: CLS, long task, style, animasi, heap (1280 / 390)

| Tema | CLS | long task | recalc saat load: n / ms (1280) | layout saat load: n / ms (1280) | getAnimations: total (tak hingga) 1280 / 390 | idle 1,5 s recalc 1280 / 390 | heap JS setelah GC, 1280 (MB) |
|---|---|---|---|---|---|---|---|
| none | 0 / 0 | 0 / 0 | 52 / 15,4 | 1 / 26,8 | 41 (28) / 6 (0) | 1 / 0 | 0,54 |
| natal | 0 / 0 | 0 / 0 | 62 / 12,0 | 1 / 19,5 | 23 (10) / 6 (0) | 6 / 0 | 1,42 |
| tahun_baru | 0 / 0 | 0 / 0 | 79 / 12,9 | 1 / 18,7 | 22 (9) / 6 (0) | 3 / 0 | 1,42 |
| imlek | 0 / 0 | 0 / 0 | 85 / 14,3 | 1 / 19,4 | 22 (9) / 6 (0) | 0 / 0 | 1,42 |
| ramadan | 0 / 0 | 0 / 0 | 84 / 14,7 | 1 / 27,3 | 22 (9) / 6 (0) | 0 / 0 | 1,42 |
| lebaran | 0 / 0 | 0 / 0 | 77 / 15,5 | 1 / 27,4 | 21 (8) / 6 (0) | 0 / 0 | 1,44 |
| hut_ri | 0 / 0 | 0 / 0 | 74 / 14,3 | 1 / 28,1 | 20 (7) / 6 (0) | 3 / 0 | 1,43 |

Halaman login tema aktif tidak lebih berat daripada `none` untuk style dan layout, dan animasi tak hingganya lebih sedikit.

### Settings (layout dashboard)

Semua tema, termasuk `none`: 18 request pada 1280 (16 pada 390 untuk tema aktif karena 2 thumbnail ditunda; `none` 390 tetap 18; dugaan: halaman `none` lebih pendek sehingga ambang lazy berbeda, tidak dipastikan), total 1549 sampai 1583 KB mentah, three.js 0 request, JS tema 0 request. Satu-satunya aset tema adalah thumbnail poster pemilih tema: 6 berkas 18,2 KB pada 1280 (4 berkas 11 KB pada 390 untuk tema aktif). Thumbnail ini bagian fitur pemilih di Settings, bukan partial dekorasi, dan ada juga saat `none`.

| Tema | HTML KB (gzip) | CLS 1280 / 390 | long task (ms) 1280 / 390 | load: recalc n / ms, layout n / ms (1280) | animasi berjalan tak hingga 1280 / 390 | idle 3 s: recalc dan layout n (recalc ms, layout ms) 1280 | idle 390 (n) | heap setelah GC (MB) |
|---|---|---|---|---|---|---|---|---|
| none | 287,5 (50,8) | 0,0002 / 0 | 0 / 54 | 10 / 27,7 dan 9 / 45,8 | 0 / 0 | 0 | 0 | 0,70 |
| natal | 321,5 (57,8) | 0 / 0 | 60 / 0 | 21 / 29,6 dan 20 / 44,6 | 30 / 22 | 14 (3,33; 0,56) | 16 | 0,70 |
| tahun_baru | 318,3 (56,4) | 0 / 0 | 0 / 0 | 23 / 19,2 dan 21 / 30,1 | 35 / 25 | 17 (3,97; 1,03) | 18 | 0,70 |
| imlek | 313,0 (55,5) | 0 / 0 | 0 / 60 | 16 / 15,9 dan 13 / 30,4 | 20 / 11 | 0 | 0 | 0,70 |
| ramadan | 313,4 (56,5) | 0 / 0 | 0 / 62 | 31 / 30,8 dan 29 / 44,5 | 29 / 21 | 0 | 0 | 0,70 |
| lebaran | 312,1 (55,8) | 0 / 0 | 60 / 0 | 41 / 28,7 dan 40 / 48,0 | 25 / 18 | 0 | 0 | 0,70 |
| hut_ri | 311,0 (55,3) | 0 / 0 | 0 / 58 | 17 / 16,9 dan 16 / 32,8 | 29 / 20 | 16 (3,26; 0,66) | 14 | 0,70 |

- CLS dalam toleransi 0,01: PASS di 28 dari 28 kasus halaman (login dan Settings).
- Long task Settings hanya tunggal 54 sampai 62 ms saat load, muncul pada `none` maupun tema: derau, bukan akibat tema.
- Tambahan HTML tema terhadap `none`: +23,5 sampai +34,0 KB mentah, +4,5 sampai +7,0 KB gzip.
- Heap Settings 0,70 MB identik di 7 kasus, sudah diulang dengan GC paksa (`Runtime.getHeapUsage`): angka benar (hanya heap JS, DOM dan GPU tidak termasuk).
- Animasi: hiasan navbar 15 sampai 21 animasi dalam 4 potongan, maksimum 7 per potongan (anggaran 8: PASS). Ukuran markup potongan navbar 0,6 sampai 5,7 KB (anggaran 14 KB: PASS). Sisanya dekorasi sidebar: natal dan tahun_baru 14, ramadan, lebaran, hut_ri 10, imlek 1.
- CSS `seasonal-accents` per tema (keluaran partial penuh): natal 4.161, tahun_baru 2.744, imlek 2.427, ramadan 2.039, lebaran 1.711, hut_ri 1.157 byte (anggaran sekitar 4,2 KB: PASS).
- Idle TIDAK nol pada natal, tahun_baru, dan hut_ri: sekitar 5 style recalc plus 5 layout per detik (sekitar 1,1 sampai 1,3 ms recalc per detik, di bawah 0,2 persen main thread). Eksperimen jeda per nama animasi (natal): menjeda `seasonal-snow-fall` (14 partikel sidebar, `seasonal-theme.blade.php`) menurunkan recalc per 2 detik dari 10 menjadi 2, dan sisanya hilang setelah animasi navbar (`sn-*`) ikut dijeda. Partial `seasonal-accents` sendiri tidak punya animasi idle. Keyframes yang terbaca hanya transform dan opacity; mengapa Chrome tetap menjadwalkan recalc tidak dipastikan (dugaan: elemen SVG dengan `filter: drop-shadow`, belum dibuktikan). Paint idle tidak diukur.

### Biaya scroll (gulir otomatis bolak-balik 3 detik, 1280x800)

Rentang dari 3 ulangan (`re.json`). Rail dibuktikan aktif (`data-sb-rail=1`, sidebar 80 px, margin konten 80 px), overflow horizontal 0 di semua kasus.

| Kasus | median / p95 frame (ms) | frame max (ms) | frame > 33 ms | layout = recalc per 180 frame | recalc ms / layout ms per 3 s | total task ms per 3 s |
|---|---|---|---|---|---|---|
| none | 16,6 sampai 16,7 / 16,9 sampai 17,1 | 17,2 sampai 17,6 | 0 | 0 | 0 / 0 | 50 sampai 73 |
| natal (salju) | 16,7 / 16,9 sampai 17,1 | 17,3 sampai 17,9 | 0 | 182 | 53 sampai 62 / 15 sampai 17 | 196 sampai 227 |
| hut_ri | 16,7 / 17,0 | 17,4 | 0 | 181 sampai 182 | 32 sampai 62 / 10 sampai 19 | 123 sampai 235 |
| none + rail | 16,7 / 16,8 sampai 17,1 | 17,3 sampai 33,3 | 0 | 0 | 0 / 0 | 44 sampai 72 |
| natal + rail | 16,6 sampai 16,7 / 16,8 sampai 17,1 | 17,4 sampai 17,6 | 0 | 182 | 41 sampai 62 / 12 sampai 18 | 145 sampai 225 |

- Tidak ada penurunan frame akibat tema atau rail pada mesin ini: PASS.
- Biaya nyata: pada tema aktif ada 1 recalc dan 1 layout per frame selama scroll (sekitar 0,4 ms per frame), sedangkan `none` 0. Menjeda semua animasi membuatnya kembali 0 (diuji natal dan imlek), jadi sumbernya animasi dekorasi yang berjalan, bukan handler scroll. Scroll digerakkan lewat `scrollTop` dari JS, jadi biaya pada scroll roda atau sentuh asli bisa berbeda.
- Pada run tunggal pertama (`pages.json`, 7 tema plus 2 rail) p95 16,9 sampai 17,1 ms; frame lambat sporadis di `none` (1), `none` + rail (3, maks 125 ms), dan hut_ri (3, maks 50 ms) tidak muncul lagi pada 3 ulangan: derau.
- Halaman tema lebih tinggi daripada `none` (jarak gulir 556 banding 347 px).

## Butir 3 (lanjutan): pemuatan lazy saat runtime

Ukuran folder dan header cache ada di bagian "Butir 3: ukuran aset dan cache (statis)" di atas.

### Sebelum dan sesudah Play

| Halaman | three.js sebelum Play (4,5 s setelah load) | JS tema sebelum Play | Setelah klik Play (2,5 s) | Catatan |
|---|---|---|---|---|
| Settings none | 0 byte | 0 | tombol Play nonaktif | hanya 6 thumbnail 18,2 KB |
| Settings natal | 0 byte | 0 | three.js 691.807 byte; aset tema total 764,5 KB mentah (termasuk thumbnail) | kontrol positif |
| Settings imlek | 0 byte | 0 | three.js 691.807 byte; total 790,5 KB | kontrol positif |
| Admin Seasonal Theme (natal) | 0 byte | 0 | three.js 691.807 byte; total 764,5 KB | HTML admin dirender dari HEAD 9624e2e7 |
| Settings, 7 tema x 2 lebar | 0 byte (14 dari 14) | 0 | tidak diklik | `pages.json` |

Anggaran "three.js tidak diunduh di Settings, admin, atau dashboard sampai Play": PASS untuk Settings dan admin. Halaman home dashboard tidak dirender; layoutnya sama dengan layout yang dipakai Settings (0 permintaan), jadi disimpulkan dari situ, tidak diuji langsung.

### Pemanasan idle di login (natal, 1280)

| Kondisi `navigator.connection` (ditimpa sebelum skrip halaman) | three.js | aset tema | Waktu (ms sejak navigasi, localhost) |
|---|---|---|---|
| normal | 691.807 byte | 8 berkas, 787 KB | DOMContentLoaded 13, load 32; stage.js 37 sampai 39; three.js 40 sampai 44 |
| saveData true | 0 | 1 berkas (SVG ilustrasi, 41 KB) | tidak ada permintaan JS |
| effectiveType 2g | 0 | 1 berkas (SVG) | sama |
| effectiveType 3g | 0 | 1 berkas (SVG) | sama |

- PASS: pemanasan hanya saat tema loading aktif, dilewati pada Save-Data, 2g, 3g; tema `none` 0 permintaan JS atau gambar tema (3 request pada 1280, 2 pada 390).
- `requestIdleCallback` (timeout 4 s) jalan segera setelah load karena main thread menganggur. Pada jaringan lambat yang tidak dilaporkan sebagai 2g atau 3g, unduhan ini akan bersaing dengan sumber daya lain setelah load; ia tidak memblokir render awal.
- `navigator.connection` hanya ada di browser berbasis Chromium; di Safari dan Firefox pengecualian Save-Data dan 2g/3g tidak berlaku (pemanasan selalu jalan).

### Perkiraan transfer dengan kompresi (dihitung, bukan diukur dari server)

Aset tema yang benar-benar diminta login (daftar dari `pages.json`, gzip level 6): 1280 px 199 sampai 216 KB (mentah 771 sampai 843 KB); 390 px 192 sampai 210 KB. three.js sendiri sekitar 170 KB gzip, jadi lebih dari 80 persennya. Berlaku hanya bila server produksi mengompres JS dan SVG dan menghormati header cache. Logo PNG bawaan 108 KB tidak berubah oleh gzip.

## Butir 4: transisi sidebar ringkas (`.sidebar-transition`)

CSS terukur (sidebar dan `#mainContent`): `transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1)`. Lebar 256 ke 80 px, margin konten ikut. 4 toggle berturut-turut (collapse, expand, collapse, expand) lewat klik `#sidebarRailToggle`, jendela ukur 900 ms. Selama jendela ukur harness tidak membaca layout (durasi dari `transitionend`, ukuran dibaca sesudahnya).

| Kasus | layout per toggle | recalc per toggle | frame dalam 900 ms | durasi (ms) | frame max (ms) | frame > 33 ms | layout ms / recalc ms per toggle |
|---|---|---|---|---|---|---|---|
| none | 18 sampai 19 | 18 sampai 19 | 53 sampai 54 | 290 sampai 308 | 23 sampai 40 | 3 dari 4 toggle punya 1 | 3,7 sampai 9,3 / 6,5 sampai 9,2 |
| natal | 54 sampai 56 | 54 sampai 56 | 53 sampai 55 | 304 sampai 332 | 17 sampai 33 | 0 | 5,2 sampai 9,1 / 12,9 sampai 25,2 |
| natal, animasi dijeda | 18 sampai 19 | 18 sampai 19 | 53 sampai 54 | 290 sampai 308 | 26 sampai 39 | 3 dari 4 punya 1 | 4,7 sampai 9,3 / 6,6 sampai 8,7 |
| imlek | 55 sampai 56 | 55 sampai 56 | 54 sampai 55 | 314 sampai 331 | 17 sampai 32 | 0 | 4,3 sampai 10,6 / 9,7 sampai 27,8 |
| imlek, animasi dijeda | 19 sampai 20 | 19 (collapse), 53 sampai 55 (expand) | 53 sampai 54 | 303 sampai 317 | 18 sampai 35 | 1 dari 4 punya 1 | 3,5 sampai 9,0 / 5,0 sampai 9,1 |

- Tidak ada layout thrash: layout tidak pernah melebihi 1 per frame. Tanpa animasi dekorasi, 18 sampai 20 layout sama dengan jumlah frame transisi 0,3 detik pada 60 Hz. Angka 54 sampai 56 pada tema aktif adalah 1 per frame di seluruh jendela 900 ms, akibat animasi dekorasi (pola yang sama dengan scroll). PASS.
- Handler klik 0,3 sampai 1,0 ms (`setSidebarRail` hanya menulis atribut dan membaca `textContent`, tidak membaca geometri).
- Sesudah tiap toggle: margin konten 80 atau 256 px sesuai status, overflow horizontal 0. Durasi 290 sampai 332 ms (target 300).
- Satu frame 33 sampai 40 ms kadang muncul pada transisi, juga pada `none`, jadi bukan dari tema. Run pertama (`pages.json`) hasilnya sama (none 18 sampai 20, tema 55 sampai 56).
- Catatan desain: transisi menganimasikan `width` dan `margin-left` (properti layout) dengan `transition: all`. Pada halaman Settings biayanya kecil (total 4 sampai 11 ms layout per toggle); halaman dengan tabel besar akan lebih mahal. `.sidebar-transition` sudah ada sebelum rail.
- `.sidebar-transition` sudah ada sebelum port (cb765c92, dashboard.blade.php baris 127 sampai 129); durasinya 0 detik bila preferensi Show animations dimatikan.

## Butir 5: kesimpulan

Ringkas: performa saat berjalan sehat pada GPU nyata (frame, CLS, scroll, transisi rail, lazy load). Ada empat penyimpangan dari bunyi anggaran. Menurut pengukuran ini tidak ada yang menurunkan frame pada mesin uji, tetapi perlu diputuskan: perbaiki atau terima dan ubah bunyi anggaran. Satu kasus 3D (Intel UHD, 2880x1620, sleigh) juga di atas p95 33 ms.

### PASS/FAIL terhadap anggaran bagian 5

| Anggaran | Hasil | Bukti |
|---|---|---|
| Query tambahan 0 | PASS | Login 0 query di 7 kondisi (cache hangat; cache dingin 1 baris tema menurut langkah 6a). Settings 7 query, daftar identik `none` dan natal. Admin tidak dihitung ulang. |
| CLS 0 (toleransi 0,01) | PASS | 28 dari 28 kasus halaman: 0, kecuali Settings none 1280 = 0,0002. |
| Tema none: tidak ada permintaan gambar atau JS tema | PASS | Login none 0 aset tema. Settings none 0 JS tema, hanya thumbnail pemilih (bagian fitur). |
| Tema none: 0 byte tambahan dari partial tema | FAIL (harfiah) | HTML login none +31,9 KB mentah, +7,3 KB gzip dibanding sebelum port. Di route Settings `seasonal-ui-remap` (5.126 byte) sengaja disisipkan juga saat none; di halaman dashboard lain partial itu dilindungi `@if` (dari kode). |
| three.js tidak diunduh sebelum Play (Settings, admin, dashboard) | PASS | 0 byte di 14 kasus Settings dan admin natal; 691.807 byte hanya setelah Play. Home dashboard tidak diuji langsung. |
| Login: hanya pemanasan idle bila tema loading aktif, dilewati Save-Data dan 2g/3g | PASS | Normal 691.807 byte; saveData, 2g, 3g: 0. None: 0. Hanya diuji di Chromium. |
| Navbar: maks 14 KB per potongan | PASS | 0,6 sampai 5,7 KB. |
| Navbar: maks 8 elemen bergerak per potongan | PASS | Maks 7. |
| Aksen dashboard: CSS maks sekitar 4,2 KB per tema | PASS | 1.157 sampai 4.161 byte. |
| Aksen: nol layout, style recalc, paint saat idle | FAIL untuk dashboard utuh (natal, tahun_baru, hut_ri); partial aksen sendiri tanpa animasi idle | 14 sampai 18 recalc dan layout per 3 s (sekitar 3,3 sampai 4 ms recalc). imlek, ramadan, lebaran, none: 0. Sumber: partikel sidebar dan navbar. Paint tidak diukur. |
| 3D: median di bawah 2,5 ms sampai 4K | PASS pada proxy, tidak terverifikasi penuh | CPU render() median 0,3 sampai 1,2 ms (p95 maks 2,3). Tanpa timer GPU; maksimum 2880x1620. |
| 3D: frame (acuan baseline: median < 20, p95 < 33 ms) | PASS 35 dari 36; FAIL 1 | intel-1920-dpr1.5 sleigh p95 50 ms (steady 33,5 ms). Median 16,7 ms di semua kasus. |
| 3D: draw call maks 55 | PASS (di batas) | Maks 55 (sleigh, Intel). |
| 3D: tanpa kebocoran dalam 20 putaran | PASS | Konteks aktif 0; geometri, tekstur, program 0/0/0 setelah dispose di 12 dari 12 kasus. VAO dan shader tidak dihitung terpisah. |
| Heap JS tumbuh < 1,5 MB dalam 20 putaran | FAIL tipis, belum konklusif | Putaran 1 ke 20: +2,7 sampai +5,6 MB; putaran 10 ke 20: +0,6 sampai +1,2 MB; tren 0,07 sampai 0,13 MB per putaran. Tidak lebih buruk dari baseline. |
| SVG login maks 45 KB mentah, 4 sampai 10 KB gzip | PASS | 19,3 sampai 44,4 KB; gzip 4,1 sampai 9,5 KB. |
| Kontras teks menu aktif dan panel login minimal 4,5:1 | TIDAK DIVERIFIKASI numerik | Langkah 3 hanya penilaian visual. |
| Tombol bahaya tetap merah di 6 tema | PASS untuk tombol OK dialog danger; hapus dan reject tidak diukur ulang | 6 dari 6: `data-danger` ada, kelas `bg-red-700`, warna `oklch(0.505 0.213 27.518)` (merah Tailwind build ini, bukan nilai rgb yang ditulis anggaran), tidak diubah tema. |
| Dialog pratinjau bisa ditutup dengan keyboard | PASS (dari langkah 6b, tidak diulang) | Esc menutup, fokus kembali ke Play. |
| Reduced motion menampilkan gambar diam | PASS | Langkah 6b (poster, tanpa 3D); diulang di sini: 0 animasi tak hingga berjalan di Settings pada 6 tema. |
| `prefers-contrast: more` menyembunyikan ornamen | FAIL (sebagian) | Navbar dan CSS aksen tersembunyi. Partikel sidebar (`.seasonal-decoration--sidebar`) dan ornamen `.seasonal-accent` tetap tampil (natal: 4 dari 5 elemen hitungan masih tampil; imlek sama polanya). `seasonal-theme.blade.php` tidak punya aturan prefers-contrast. |

### Tambahan (di luar tabel anggaran)

| Hal | Hasil |
|---|---|
| Scroll Settings (natal salju, rail) | PASS: p95 16,8 sampai 17,1 ms, 0 frame > 33 ms pada 3 ulangan; biaya tambahan 1 recalc dan 1 layout per frame |
| Transisi rail | PASS: tanpa thrash (maks 1 layout per frame), 290 sampai 332 ms, tanpa overflow |
| Downgrade resolusi palsu (keluhan baseline) | Tidak terjadi di 36 dari 36 kasus |
| Hitch awal 3D | Membaik tetapi belum hilang: longtask tunggal maks 396 ms (dulu sampai 1254 ms); sleigh NVIDIA dingin 0,95 sampai 1,3 s sampai frame pertama; angka final lebih tinggi daripada run `after2` |

### Yang tidak bisa diverifikasi

- 4K, waktu GPU sebenarnya, dan biaya paint atau compositing.
- Ponsel nyata, dpr 1,25, mode hemat daya, Safari dan Firefox; mode berjendela untuk run final 3D.
- Server produksi: kompresi, header cache, HTTP/2, jaringan lambat nyata. Byte di sini tanpa kompresi di localhost; angka gzip dihitung.
- Home dashboard dengan data nyata (butuh database) dan biaya scroll atau rail pada halaman bertabel besar.
- Scroll dengan roda atau sentuh asli.
- Kontras numerik, warna tombol hapus dan reject setelah perbaikan B2.
- Waktu pemanasan idle pada CPU atau jaringan lambat (hanya localhost).
- Penyebab drift heap (tidak ada snapshot heap) dan penyebab pasti recalc idle.
- Overhead HTML `none` di Settings dan dashboard terhadap sebelum port (view Settings lama gagal dirender dengan katalog baru); hanya login yang dibandingkan.

### Rekomendasi (urut prioritas)

1. `prefers-contrast: more`: tambahkan aturan sembunyi untuk `.seasonal-decoration` di `seasonal-theme.blade.php`. Perubahan kecil dan menutup satu FAIL aksesibilitas.
2. Deploy: pastikan Apache mengaktifkan `mod_deflate`, `mod_expires`, `mod_headers`, lalu cek dengan `curl -I --compressed` pada `three.module.min.js` dan satu SVG (harus ada `Content-Encoding` dan `Cache-Control ... immutable`). Untuk nginx atau CDN buat aturan setara. Tanpa kompresi, unduhan login tema sekitar 0,8 MB, bukan sekitar 0,2 MB. Karena `?v=` memakai filemtime, banyak server harus punya mtime seragam agar cache tidak terbelah.
3. `npm run build`: `public/build` tertanggal 2026-09-28 (sebelum port). Port tidak bergantung padanya karena kelas yang hilang sudah diatasi dengan kelas lama dan CSS kecil, jadi bukan syarat performa. Tetap disarankan di langkah deploy bila pipeline memang membangun aset; karena mengubah CSS seluruh aplikasi, uji visual juga halaman non tema. Lalu `php artisan view:clear`.
4. Ponsel: pertimbangkan melewati pemanasan three.js di bawah 768 px (`matchMedia`) atau menundanya sampai pengguna mulai mengisi formulir (sekitar 750 KB mentah, 190 sampai 210 KB gzip). Perlu keputusan pemilik karena loading pertama jadi lebih lambat.
5. Idle dan scroll: bila anggaran "nol" ingin dipenuhi, selidiki partikel salju sidebar (coba tanpa `filter: drop-shadow`, kurangi 14 partikel) dan ukur ulang dengan `finalperf\exp2.mjs`. Bila tidak, ubah bunyi anggaran: biayanya di bawah 0,2 persen main thread saat idle.
6. Tema none: putuskan apakah tambahan 7,3 KB gzip di login diterima (layar loading generik membutuhkannya) dan revisi bunyi anggaran, atau cetak data `__seasonalThemes` dan import map hanya bila diperlukan. Preferensi per pengguna dapat berbeda dari tema global, jadi uji kasus itu sebelum mengubah.
7. 3D: untuk Intel pada kanvas besar, pertimbangkan batas pixel ratio atau jumlah piksel pada sleigh; kecilkan atau pecah tekstur sleigh untuk hitch dingin.
8. Heap: ambil dua snapshot heap (putaran 10 dan 40) untuk memastikan drift bukan retensi.
9. Di luar tema: Settings menjalankan 6 query identik `security.microsoft_login`; memo per request akan menghilangkannya tanpa melanggar tujuan "langsung berlaku" dari 9624e2e7.

### Cara mengulang pengukuran halaman

Dari folder scratchpad `finalperf`: render HTML ke `html\` (skrip `render.php`, `rlogin.php`, `radm.php`, ganti host ke 127.0.0.1:8094), lalu `node pages.mjs all` (hasil `pages.json`) dan `node re.mjs all` (hasil `re.json`); `chk.mjs` untuk tombol bahaya dan prefers-contrast. Skrip menjalankan dan menghentikan `php -S` port 8094 dan Edge lewat PID. Port 8000 tidak disentuh, tidak ada penulisan database, tidak ada git worktree.

## Tindak lanjut setelah audit (25 Nov)

- Query berulang security.microsoft_login (6 kali per render Settings pada 9624e2e7): DIPERBAIKI dengan memo per request di AppConfig. Diukur ulang dengan APP_ENV=testing: Settings 2 query (1 security.microsoft_login, 1 hitung staging_tickets bawaan layout) untuk none dan natal.
- prefers-contrast: more dan cetak: aturan ditambahkan di seasonal-theme.blade.php. Belum diukur ulang di browser.
- Suite penuh sesudah perbaikan: 298 tes, 3649 assertion, 0 gagal.
