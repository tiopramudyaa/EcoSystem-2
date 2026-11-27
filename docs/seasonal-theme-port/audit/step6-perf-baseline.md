# Langkah 6: baseline performa loading screen 3D (sebelum perbaikan)

Status: QA read-only, tidak ada berkas repo selain dokumen ini yang diubah. Tanggal data: 2026-11-24.

## Cara menjalankan ulang harness

Harness ada di luar repo (scratchpad sesi), folder `perf`:
`C:\Users\Reinaldy\AppData\Local\Temp\claude\2a95a53948def2d1\e6d05084-cee9-45e6-a0fe-9204d13c6e1c\scratchpad\perf`

Berkas: `run.mjs` (pengendali CDP + server), `router.php` (router statis), `harness.html` (halaman uji), `palettes.json` (palet dari `SeasonalThemes::get($key)['palette']`, dibuat oleh `pal.php`), `summ.mjs` (ringkasan konsol), `gen.mjs` (pembuat tabel dokumen ini).

Satu perintah (dari folder `perf`, Node 24, tanpa npm install):

    node run.mjs before          # hasil -> results-before.json
    node run.mjs after           # setelah perbaikan -> results-after.json
    node summ.mjs results-after.json

Env opsional: `CONFIGS=nv-1280,intel-1280` (filter: nv-1280, nv-1920, nv-1920-dpr1.5, intel-1280, intel-1920, intel-1920-dpr1.5, def-1280, swift-1280), `SCENES=sleigh,lunar`, `LEAK=0` (lewati uji 20 putaran), `HEADLESS=0` (jendela Edge nyata), `ROUNDS=20`, `REPO=<path repo>`. Satu run penuh kira-kira 25 menit (uji kebocoran dan SwiftShader paling lama). Server `php -S 127.0.0.1:8097 -t public router.php` dan Edge (port CDP 9347) dijalankan dan dihentikan lewat PID oleh `run.mjs`; port 8000 tidak dipakai.

Cara kerja: `router.php` melayani `/__perf/harness.html` dan menambal `three.module.min.js` secara dinamis (membungkus `WebGLRenderer` agar `renderer.info`, waktu `render()` pertama, dan konteks GL terekam; berkas di repo tidak berubah). Per adegan, `run.mjs` membuka halaman baru, mengklik tombol lewat CDP `Input.dispatchMouseEvent` (3 putaran berturut-turut tanpa reload, putaran 1 dingin: three.js dan modul adegan belum dimuat), lalu halaman baru dengan `preload()` dahulu, lalu (hanya 1280x720) 20 putaran dengan kanvas baru tiap putaran seperti `seasonal-preview.blade.php` (duration 700 ms, `gc()` dipaksa, hitung konteks aktif dan heap). Tiap putaran memakai `duration: 2600` (sama dengan aplikasi) dan jendela rekam 2,6 detik.

Metrik: delta rAF (median, p95, max, jumlah >33 dan >50 ms), longtask (PerformanceObserver), play() resolve (termasuk frame pertama sinkron), klik ke frame pertama (rAF pertama setelah play() resolve, dari `event.timeStamp` klik), waktu `render()` pertama (kompilasi shader), jeda konstruktor renderer ke `render()` pertama (create() + setup), `renderer.info` (draw call, segitiga, geometri, tekstur, program), pixel ratio akhir.

Mode GPU (flag Edge) dan renderer yang terkonfirmasi lewat `WEBGL_debug_renderer_info`:

| Mode | Flag | Renderer terkonfirmasi |
|---|---|---|
| nvidia | `--use-angle=d3d11 --enable-gpu --force_high_performance_gpu` | ANGLE (NVIDIA GeForce RTX 5060 Laptop GPU, Direct3D11) |
| intel | `--use-angle=d3d11 --enable-gpu --force_low_power_gpu` | ANGLE (Intel(R) UHD Graphics, Direct3D11) |
| default | tanpa flag GPU | ANGLE (Intel UHD Graphics, Direct3D11): Edge bawaan di mesin ini memakai Intel |
| swift | `--use-angle=swiftshader --enable-unsafe-swiftshader` | ANGLE (Google, SwiftShader, Vulkan): CPU, hanya pembanding |

Mesin: hardwareConcurrency 32, deviceMemory 32, saveData false, jadi `qualityTier()` = high (antialias aktif, pixel ratio min(dpr, 1.5)). Viewport 1280x720 dan 1920x1080 pada dpr 1; tambahan 1920x1080 dpr 1.5. Data utama dari `--headless=new` (`results-before.json`); verifikasi jendela nyata (`HEADLESS=0`) untuk nv-1280, intel-1280, def-1280 di `results-before-windowed.json` dengan hasil setara. Frame steady 16,7 ms (60 Hz vsync), jadi median 16,7 adalah batas atas yang bisa diukur; headroom terlihat dari CPU `render()` median 0,4 sampai 1,0 ms.

Anggaran: median < 20 ms dan p95 < 33 ms di GPU nyata. "PASS cold" memakai seluruh jendela 2,6 detik putaran 1 (termasuk hitch awal); "PASS steady" membuang 10 frame pertama. Catatan: p95 dari sekitar 155 frame tidak melihat satu frame beku, jadi baca juga kolom frame max dan longtask.

## Ringkasan temuan

1. Frame steady sehat: di NVIDIA, Intel, dan Edge bawaan, semua 6 adegan median 16,7 ms dan p95 sekitar 17 ms setelah frame awal, di 1280, 1920, dan 1920 dpr 1,5. Penggambaran bukan penyebab utama tersendat. Hanya SwiftShader (tanpa GPU) gagal parah (median 67 sampai 117 ms), relevan hanya untuk perangkat tanpa akselerasi.
2. Sumber tersendat utama: kompilasi shader sinkron pada `render()` pertama, satu longtask 317 sampai 1030 ms (NVIDIA 1280: sleigh 728 ms, crescent 959, eid 889, lunar 837; Intel 1280: 335 sampai 1254 ms, bervariasi antar run). Main thread beku dan tampilan tidak bergerak sebelum frame pertama: klik ke frame pertama 350 sampai 1330 ms (dingin).
3. Hitch ulang di setiap play(): putaran 2 dan 3 (modul sudah dimuat, tanpa reload) masih 110 sampai 440 ms sampai frame pertama, dan frame ke-2 menyisakan hitch 100 sampai 320 ms. `seasonal-preview` memutar adegan dua kali (playLoop, MAX_MS 6000), jadi pengguna merasakan beku kedua sekitar detik 2,6 saat konteks GL dan program shader dibuat ulang.
4. Auto-downgrade keliru di `stage.js` (rata-rata 10 frame pertama > 40 ms) tertipu hitch kompilasi. Pada run headless dpr 1 ia menurunkan pixel ratio ke 0,75 di GPU nyata (RTX 5060, nv-1280: crescent, eid, lunar di ketiga putaran; sleigh, fireworks, flag di putaran 1), jadi gambar lebih buram tanpa alasan. Pada run berjendela tidak terpicu (hitch jatuh di frame yang dikecualikan sampel), jadi bergantung waktu.
5. Konteks WebGL dan memori sehat: setelah 20 putaran konteks aktif 0 (21 dibuat total termasuk satu konteks probe), `renderer.info` setelah dispose 0 geometri, 0 tekstur, 0 program, heap bergerigi (naik turun setelah GC) tanpa tren linear, selisih +2 sampai +8 MB selama 20 putaran. Tidak dianggap kebocoran.
6. Pemilihan GPU: Edge bawaan di mesin ini memakai Intel UHD, tetapi hasilnya setara NVIDIA untuk beban ini. GPU Intel bukan penyebab. Pada Intel, `create()` terasa lebih berat di beberapa adegan (96 sampai 127 ms untuk crescent, eid, flag di 1280 headless; 44 sampai 51 ms berjendela).

## Dugaan sumber tersendat (berdasarkan data, urut kontribusi)

1. Kompilasi dan link shader sinkron di `renderer.render()` pertama (13 sampai 20 program per adegan, 22 sampai 39 geometri, 4 sampai 18 tekstur). Bukti: waktu `render()` pertama 274 sampai 986 ms (NVIDIA) hampir seluruh play() resolve, sedangkan `create()` hanya 25 sampai 53 ms. Arah perbaikan: `renderer.compileAsync(scene, camera)` (atau KHR_parallel_shader_compile) sebelum frame pertama sambil menampilkan poster, kurangi varian material dan program, warm-up di `preload()`.
2. Pembuatan ulang konteks dan program tiap `play()` (putaran 2 dan 3 tetap 110 sampai 440 ms, hitch frame ke-2 100 sampai 320 ms). Arah: pakai ulang renderer dan kanvas untuk loop kedua, atau jangan `forceContextLoss` antar putaran dalam satu pratinjau.
3. `create()` sinkron (geometri prosedural): 25 sampai 55 ms di NVIDIA, sampai 127 ms di Intel. Kecil dibanding no. 1 tetapi menambah beku.
4. Auto-downgrade salah picu (temuan 4): sampel perlu mengabaikan frame dengan dt besar atau mulai setelah beberapa frame.
5. Tidak terbukti: pemilihan GPU Intel, kebocoran konteks, beban frame steady, resolusi (1920 dan dpr 1,5 setara 1280).

## Tabel hasil (putaran 1 dingin kecuali dinyatakan lain)

Kolom: median/p95 delta rAF; frame max; frame >33/>50 ms; longtask jumlah/total ms; play() resolve dan klik ke frame pertama (ms); waktu render() pertama (ms); create() = jeda konstruktor renderer ke render pertama (ms).

#### nv-1280 (ANGLE (NVIDIA, NVIDIA GeForce RTX 5060 Laptop GPU (0x00002D59) Direct3D11 vs_5_0 ps_5_0, D3D11)) viewport 1280x720 dpr 1

| Adegan | median / p95 (ms) | frame max (ms) | frame >33 / >50 | longtask n / ms | play() resolve | klik ke frame 1 | render pertama (ms) | create() (ms) | draw call | segitiga | PASS cold | PASS steady | play ke frame 1, run 3 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 33.2 | 517 | 5 / 4 | 1 / 728 | 755 | 757 | 686 | 35 | 49 | 91056 | FAIL | PASS | 237 ms |
| fireworks | 16.7 / 17.0 | 417 | 1 / 1 | 1 / 430 | 465 | 467 | 392 | 30 | 33 | 51136 | PASS | PASS | 234 ms |
| crescent | 16.7 / 17.0 | 933 | 1 / 1 | 1 / 959 | 994 | 996 | 904 | 47 | 41 | 64868 | PASS | PASS | 344 ms |
| eid | 16.7 / 16.9 | 867 | 1 / 1 | 1 / 889 | 924 | 926 | 829 | 53 | 41 | 50187 | PASS | PASS | 427 ms |
| lunar | 16.7 / 16.8 | 817 | 1 / 1 | 1 / 837 | 870 | 873 | 803 | 26 | 39 | 58314 | PASS | PASS | 320 ms |
| flag | 16.7 / 17.1 | 300 | 2 / 2 | 1 / 317 | 349 | 351 | 274 | 31 | 40 | 91458 | PASS | PASS | 196 ms |

#### intel-1280 (ANGLE (Intel, Intel(R) UHD Graphics (0x0000A788) Direct3D11 vs_5_0 ps_5_0, D3D11)) viewport 1280x720 dpr 1

| Adegan | median / p95 (ms) | frame max (ms) | frame >33 / >50 | longtask n / ms | play() resolve | klik ke frame 1 | render pertama (ms) | create() (ms) | draw call | segitiga | PASS cold | PASS steady | play ke frame 1, run 3 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 16.9 | 433 | 1 / 1 | 1 / 446 | 478 | 481 | 402 | 37 | 53 | 101128 | PASS | PASS | 133 ms |
| fireworks | 16.7 / 16.9 | 317 | 1 / 1 | 1 / 335 | 372 | 373 | 291 | 35 | 33 | 51136 | PASS | PASS | 111 ms |
| crescent | 16.7 / 17.2 | 967 | 1 / 1 | 1 / 989 | 1046 | 1049 | 848 | 127 | 41 | 64868 | PASS | PASS | 327 ms |
| eid | 16.7 / 17.3 | 1250 | 1 / 1 | 1 / 1254 | 1327 | 1331 | 1143 | 96 | 41 | 50187 | PASS | PASS | 407 ms |
| lunar | 16.7 / 17.9 | 1000 | 1 / 1 | 1 / 1019 | 1069 | 1074 | 957 | 46 | 39 | 58314 | PASS | PASS | 306 ms |
| flag | 16.7 / 16.9 | 500 | 2 / 1 | 1 / 516 | 584 | 586 | 402 | 97 | 40 | 91458 | PASS | PASS | 306 ms |

#### nv-1920 (ANGLE (NVIDIA, NVIDIA GeForce RTX 5060 Laptop GPU (0x00002D59) Direct3D11 vs_5_0 ps_5_0, D3D11)) viewport 1920x1080 dpr 1

| Adegan | median / p95 (ms) | frame max (ms) | frame >33 / >50 | longtask n / ms | play() resolve | klik ke frame 1 | render pertama (ms) | create() (ms) | draw call | segitiga | PASS cold | PASS steady | play ke frame 1, run 3 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 16.8 | 1017 | 2 / 2 | 1 / 1030 | 1057 | 1060 | 987 | 36 | 49 | 91056 | PASS | PASS | 259 ms |
| fireworks | 16.7 / 17.2 | 417 | 2 / 2 | 1 / 441 | 471 | 473 | 403 | 31 | 33 | 51136 | PASS | PASS | 239 ms |
| crescent | 16.7 / 17.1 | 950 | 1 / 1 | 1 / 962 | 997 | 999 | 910 | 45 | 41 | 64868 | PASS | PASS | 326 ms |
| eid | 16.7 / 16.9 | 867 | 1 / 1 | 1 / 891 | 925 | 928 | 831 | 52 | 41 | 50187 | PASS | PASS | 440 ms |
| lunar | 16.7 / 17.0 | 800 | 1 / 1 | 1 / 824 | 860 | 863 | 791 | 25 | 39 | 58314 | PASS | PASS | 331 ms |
| flag | 16.7 / 17.0 | 317 | 1 / 1 | 1 / 332 | 368 | 369 | 291 | 33 | 40 | 91458 | PASS | PASS | 209 ms |

#### intel-1920 (ANGLE (Intel, Intel(R) UHD Graphics (0x0000A788) Direct3D11 vs_5_0 ps_5_0, D3D11)) viewport 1920x1080 dpr 1

| Adegan | median / p95 (ms) | frame max (ms) | frame >33 / >50 | longtask n / ms | play() resolve | klik ke frame 1 | render pertama (ms) | create() (ms) | draw call | segitiga | PASS cold | PASS steady | play ke frame 1, run 3 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 16.9 | 433 | 1 / 1 | 1 / 451 | 484 | 486 | 407 | 37 | 53 | 101128 | PASS | PASS | 152 ms |
| fireworks | 16.7 / 17.0 | 300 | 1 / 1 | 1 / 327 | 359 | 361 | 289 | 31 | 33 | 51136 | PASS | PASS | 125 ms |
| crescent | 16.7 / 17.0 | 517 | 1 / 1 | 1 / 529 | 565 | 567 | 475 | 46 | 41 | 64868 | PASS | PASS | 178 ms |
| eid | 16.7 / 17.0 | 650 | 1 / 1 | 1 / 671 | 704 | 707 | 612 | 52 | 41 | 50187 | PASS | PASS | 217 ms |
| lunar | 16.7 / 17.1 | 517 | 1 / 1 | 1 / 540 | 574 | 577 | 508 | 25 | 39 | 58314 | PASS | PASS | 163 ms |
| flag | 16.7 / 17.0 | 217 | 1 / 1 | 1 / 233 | 268 | 269 | 194 | 32 | 40 | 91458 | PASS | PASS | 125 ms |

#### nv-1920-dpr1.5 (ANGLE (NVIDIA, NVIDIA GeForce RTX 5060 Laptop GPU (0x00002D59) Direct3D11 vs_5_0 ps_5_0, D3D11)) viewport 1920x1080 dpr 1.5

| Adegan | median / p95 (ms) | frame max (ms) | frame >33 / >50 | longtask n / ms | play() resolve | klik ke frame 1 | render pertama (ms) | create() (ms) | draw call | segitiga | PASS cold | PASS steady | play ke frame 1, run 3 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 33.4 | 750 | 5 / 4 | 1 / 765 | 794 | 796 | 720 | 38 | 49 | 91056 | FAIL | PASS | 236 ms |
| fireworks | 16.7 / 17.0 | 400 | 1 / 1 | 1 / 425 | 448 | 450 | 383 | 34 | 33 | 51136 | PASS | PASS | 229 ms |
| crescent | 16.7 / 17.0 | 900 | 1 / 1 | 1 / 915 | 938 | 940 | 859 | 49 | 41 | 64868 | PASS | PASS | 293 ms |
| eid | 16.7 / 17.1 | 850 | 1 / 1 | 1 / 872 | 896 | 900 | 812 | 53 | 41 | 50187 | PASS | PASS | 428 ms |
| lunar | 16.7 / 17.0 | 800 | 1 / 1 | 1 / 815 | 837 | 839 | 781 | 27 | 39 | 58314 | PASS | PASS | 310 ms |
| flag | 16.7 / 17.2 | 300 | 1 / 1 | 1 / 323 | 352 | 354 | 282 | 34 | 40 | 91458 | PASS | PASS | 182 ms |

#### intel-1920-dpr1.5 (ANGLE (Intel, Intel(R) UHD Graphics (0x0000A788) Direct3D11 vs_5_0 ps_5_0, D3D11)) viewport 1920x1080 dpr 1.5

| Adegan | median / p95 (ms) | frame max (ms) | frame >33 / >50 | longtask n / ms | play() resolve | klik ke frame 1 | render pertama (ms) | create() (ms) | draw call | segitiga | PASS cold | PASS steady | play ke frame 1, run 3 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 17.0 | 400 | 1 / 1 | 1 / 414 | 447 | 450 | 366 | 41 | 55 | 101180 | PASS | PASS | 162 ms |
| fireworks | 16.7 / 17.0 | 300 | 1 / 1 | 1 / 327 | 347 | 349 | 286 | 35 | 33 | 51136 | PASS | PASS | 118 ms |
| crescent | 16.7 / 17.0 | 517 | 1 / 1 | 1 / 538 | 571 | 573 | 482 | 48 | 41 | 64868 | PASS | PASS | 166 ms |
| eid | 16.7 / 17.2 | 650 | 3 / 2 | 2 / 726 | 699 | 703 | 603 | 55 | 41 | 50187 | PASS | PASS | 238 ms |
| lunar | 16.7 / 17.1 | 533 | 3 / 1 | 1 / 554 | 587 | 590 | 516 | 30 | 39 | 58314 | PASS | PASS | 150 ms |
| flag | 16.7 / 17.0 | 217 | 1 / 1 | 1 / 242 | 273 | 275 | 199 | 36 | 40 | 91458 | PASS | PASS | 140 ms |

#### swift-1280 (pembanding perangkat lunak, bukan GPU nyata): ANGLE (Google, Vulkan 1.3.0 (SwiftShader Device (Subzero) (0x0000C0DE)), SwiftShader driver)

| Adegan | median / p95 (ms) | frame >33 / >50 | longtask n / ms | draw call | segitiga |
|---|---|---|---|---|---|
| sleigh | 83.3 / 233.3 | 12 / 11 | 3 / 1515 | 49 | 91056 |
| fireworks | 66.7 / 200.1 | 22 / 21 | 3 / 469 | 33 | 49176 |
| crescent | 100.0 / 643.8 | 10 / 10 | 3 / 1301 | 41 | 64868 |
| eid | 116.6 / 783.2 | 10 / 10 | 3 / 1149 | 41 | 50187 |
| lunar | 99.9 / 383.3 | 15 / 12 | 3 / 749 | 39 | 58314 |
| flag | 66.7 / 383.4 | 22 / 21 | 3 / 670 | 40 | 91458 |

#### Berjendela (bukan headless): nv-1280 (ANGLE (NVIDIA, NVIDIA GeForce RTX 5060 Laptop GPU (0x00002D59) Direct3D11 vs_5_0 ps_5_0, D3D11))

| Adegan | median / p95 (ms) | frame max (ms) | frame >33 / >50 | longtask n / ms | play() resolve | klik ke frame 1 | render pertama (ms) | create() (ms) | draw call | segitiga | PASS cold | PASS steady | play ke frame 1, run 3 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 16.9 | 1000 | 2 / 2 | 1 / 1019 | 1053 | 1055 | 977 | 35 | 49 | 91056 | PASS | PASS | 248 ms |
| fireworks | 16.7 / 16.9 | 400 | 1 / 1 | 1 / 424 | 458 | 460 | 385 | 31 | 33 | 51136 | PASS | PASS | 224 ms |
| crescent | 16.7 / 17.1 | 917 | 1 / 1 | 1 / 937 | 973 | 975 | 884 | 46 | 41 | 64868 | PASS | PASS | 306 ms |
| eid | 16.7 / 17.1 | 850 | 1 / 1 | 1 / 865 | 900 | 902 | 809 | 49 | 41 | 50187 | PASS | PASS | 422 ms |
| lunar | 16.7 / 17.1 | 833 | 1 / 1 | 1 / 846 | 885 | 888 | 797 | 25 | 39 | 58314 | PASS | PASS | 309 ms |
| flag | 16.7 / 17.1 | 317 | 2 / 2 | 1 / 337 | 372 | 374 | 298 | 31 | 40 | 91458 | PASS | PASS | 194 ms |

#### Berjendela (bukan headless): intel-1280 (ANGLE (Intel, Intel(R) UHD Graphics (0x0000A788) Direct3D11 vs_5_0 ps_5_0, D3D11))

| Adegan | median / p95 (ms) | frame max (ms) | frame >33 / >50 | longtask n / ms | play() resolve | klik ke frame 1 | render pertama (ms) | create() (ms) | draw call | segitiga | PASS cold | PASS steady | play ke frame 1, run 3 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 17.0 | 450 | 1 / 1 | 1 / 473 | 506 | 509 | 426 | 38 | 53 | 101128 | PASS | PASS | 125 ms |
| fireworks | 16.7 / 17.0 | 300 | 1 / 1 | 1 / 323 | 356 | 358 | 285 | 31 | 33 | 51136 | PASS | PASS | 131 ms |
| crescent | 16.7 / 17.0 | 500 | 1 / 1 | 1 / 521 | 556 | 558 | 470 | 45 | 41 | 64868 | PASS | PASS | 170 ms |
| eid | 16.7 / 17.0 | 633 | 1 / 1 | 1 / 652 | 677 | 680 | 596 | 51 | 41 | 50187 | PASS | PASS | 209 ms |
| lunar | 16.7 / 17.1 | 516 | 1 / 1 | 1 / 532 | 566 | 569 | 499 | 25 | 39 | 58314 | PASS | PASS | 149 ms |
| flag | 16.7 / 17.0 | 217 | 1 / 1 | 1 / 236 | 262 | 264 | 197 | 32 | 40 | 91458 | PASS | PASS | 116 ms |

#### Berjendela (bukan headless): def-1280 (ANGLE (Intel, Intel(R) UHD Graphics (0x0000A788) Direct3D11 vs_5_0 ps_5_0, D3D11))

| Adegan | median / p95 (ms) | frame max (ms) | frame >33 / >50 | longtask n / ms | play() resolve | klik ke frame 1 | render pertama (ms) | create() (ms) | draw call | segitiga | PASS cold | PASS steady | play ke frame 1, run 3 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| sleigh | 16.7 / 17.0 | 367 | 1 / 1 | 1 / 394 | 427 | 429 | 349 | 37 | 55 | 101180 | PASS | PASS | 130 ms |
| fireworks | 16.7 / 17.0 | 300 | 1 / 1 | 1 / 318 | 352 | 353 | 281 | 30 | 33 | 51136 | PASS | PASS | 131 ms |
| crescent | 16.7 / 17.0 | 500 | 1 / 1 | 1 / 519 | 553 | 554 | 467 | 44 | 41 | 64868 | PASS | PASS | 176 ms |
| eid | 16.7 / 17.0 | 633 | 1 / 1 | 1 / 657 | 691 | 693 | 598 | 51 | 41 | 50187 | PASS | PASS | 237 ms |
| lunar | 16.7 / 16.9 | 517 | 1 / 1 | 1 / 541 | 573 | 576 | 508 | 25 | 39 | 58314 | PASS | PASS | 156 ms |
| flag | 16.7 / 17.0 | 200 | 1 / 1 | 1 / 230 | 263 | 264 | 192 | 31 | 40 | 91458 | PASS | PASS | 146 ms |

#### Info renderer per adegan (program shader, geometri, tekstur; sama di semua GPU kecuali sleigh Intel 53 draw call)

| Adegan | program | geometri | tekstur | pixelRatio akhir run 1 / 2 / 3 (nv-1280) |
|---|---|---|---|---|
| sleigh | 18 | 35 | 7 | 0.75 / 1 / 1 |
| fireworks | 17 | 26 | 4 | 0.75 / 1 / 1 |
| crescent | 20 | 22 | 12 | 0.75 / 0.75 / 0.75 |
| eid | 19 | 34 | 18 | 0.75 / 0.75 / 0.75 |
| lunar | 19 | 38 | 13 | 0.75 / 0.75 / 0.75 |
| flag | 13 | 39 | 7 | 0.75 / 1 / 1 |

#### 3 putaran berturut-turut tanpa reload (nv-1280 / intel-1280): play() resolve ms

| Adegan | nv run1 | nv run2 | nv run3 | intel run1 | intel run2 | intel run3 | nv preload+klik |
|---|---|---|---|---|---|---|---|
| sleigh | 755 | 254 | 237 | 478 | 149 | 133 | 279 |
| fireworks | 465 | 250 | 233 | 372 | 112 | 111 | 278 |
| crescent | 994 | 351 | 343 | 1046 | 320 | 323 | 385 |
| eid | 924 | 430 | 426 | 1327 | 370 | 406 | 473 |
| lunar | 870 | 306 | 320 | 1069 | 278 | 304 | 353 |
| flag | 349 | 205 | 195 | 584 | 207 | 305 | 229 |

#### 20 putaran (duration 700 ms, GC dipaksa tiap putaran)

| GPU | Adegan | konteks aktif setelah putaran (maks) | konteks dibuat total | heap putaran 1 / 10 / 20 (MB) | geometri/tekstur/program tersisa setelah dispose |
|---|---|---|---|---|---|
| nv-1280 | sleigh | 0 | 21 | 6.07 / 7.15 / 9.43 | 0/0/0 |
| nv-1280 | fireworks | 0 | 21 | 5.38 / 6.18 / 8.18 | 0/0/0 |
| nv-1280 | crescent | 0 | 21 | 9.11 / 10.24 / 14.58 | 0/0/0 |
| nv-1280 | eid | 0 | 21 | 11.38 / 12.59 / 13.3 | 0/0/0 |
| nv-1280 | lunar | 0 | 21 | 5.34 / 6.65 / 8.72 | 0/0/0 |
| nv-1280 | flag | 0 | 21 | 5.84 / 6.96 / 8.91 | 0/0/0 |
| intel-1280 | sleigh | 0 | 21 | 6.07 / 7.15 / 9.4 | 0/0/0 |
| intel-1280 | fireworks | 0 | 21 | 5.41 / 6.24 / 8.18 | 0/0/0 |
| intel-1280 | crescent | 0 | 21 | 9.11 / 10.24 / 14.61 | 0/0/0 |
| intel-1280 | eid | 0 | 21 | 11.38 / 12.59 / 16.31 | 0/0/0 |
| intel-1280 | lunar | 0 | 21 | 5.34 / 6.66 / 8.72 | 0/0/0 |
| intel-1280 | flag | 0 | 21 | 5.85 / 6.96 / 8.93 | 0/0/0 |
| swift-1280 | sleigh | 0 | 21 | 6.04 / 7.05 / 9.61 | 0/0/0 |
| swift-1280 | fireworks | 0 | 21 | 5.35 / 6.16 / 8.06 | 0/0/0 |
| swift-1280 | crescent | 0 | 21 | 9 / 10.23 / 14.53 | 0/0/0 |
| swift-1280 | eid | 0 | 21 | 11.32 / 12.52 / 16.21 | 0/0/0 |
| swift-1280 | lunar | 0 | 21 | 5.33 / 6.61 / 8.5 | 0/0/0 |
| swift-1280 | flag | 0 | 21 | 5.83 / 6.93 / 8.86 | 0/0/0 |

## Catatan batasan

- Satu mesin, satu sesi; variasi antar run besar pada angka kompilasi (contoh Intel eid 1254 ms headless vs 652 ms berjendela). Profil Edge baru tiap konfigurasi berarti cache shader ANGLE dingin; pengguna nyata dengan cache hangat mungkin lebih ringan.
- Frame dibatasi vsync 60 Hz; headroom GPU hanya terlihat lewat CPU `render()` (0,4 sampai 1,6 ms), tanpa query timer GPU.
- Tidak diukur: layar dpr 1,25, ponsel, mode hemat daya Windows, banyak tab. Keluhan "tersendat" paling konsisten dengan hitch awal (dugaan 1 dan 2) dan, pada perangkat tanpa GPU, SwiftShader.
- Setelah perbaikan, jalankan `node run.mjs after` dan bandingkan terutama: klik ke frame pertama, longtask ms, frame max, pixelRatio akhir, dan putaran 2 dan 3.

## Sesudah perbaikan (24 Nov, putaran 1 dingin, 1280x720 dpr 1, harness label after dan after2)

Perubahan: (1) renderer.compileAsync sebelum jam animasi, (2) unggah tekstur bertahap per sekitar 12 ms dengan jeda rAF (warmTextures), (3) sampel auto-downgrade melewati 3 frame awal dan frame di atas 250 ms, (4) reuseRenderer dan release(canvas) agar loop pratinjau tidak membuat konteks dan kompilasi ulang.
Temuan tambahan lewat penanda waktu (NVIDIA, cache dingin): setelah kompilasi async, 415 sampai 675 ms render pertama berasal dari unggah 23 sampai 33 tekstur sekaligus, bukan shader.

| Adegan (NVIDIA) | Longtask sebelum (ms) | Longtask sesudah (ms) | Render pertama sebelum / sesudah (ms) | Frame max sesudah (ms) | Pixel ratio akhir |
|---|---|---|---|---|---|
| sleigh | 728 | 475 (2 task) | 686 / 33 | 433 | 1 (sebelumnya 0,75) |
| fireworks | n/a | 0 | n/a / 28 | 34 | 1 |
| crescent | 959 | 247 | 904 / 80 | 267 | 1 (sebelumnya 0,75) |
| eid | 889 | 168 | n/a / 44 | 117 | 1 |
| lunar | 837 | 53 | 803 / 27 | 50 | 1 (sebelumnya 0,75) |
| flag | n/a | 0 | n/a / 31 | 50 | 1 |

Intel UHD sesudah: longtask 0 sampai 133 ms, render pertama 24 sampai 53 ms. Frame stabil tetap median 16,7 ms dan p95 sekitar 17 ms di semua adegan. Loop kedua (warm3): klik ke frame pertama 103 sampai 197 ms, sebelumnya 237 sampai 344 ms.
Sisa: sleigh di NVIDIA masih 475 ms longtask pada pemakaian dingin pertama (satu atau dua tekstur besar), terjadi sebelum frame pertama sehingga poster tampil, bukan di tengah animasi.
