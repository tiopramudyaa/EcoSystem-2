# Langkah 6b: QA UI dan fungsi di browser (hanya baca)

Cabang aldy_eco, HEAD 69d00f68. Edge headless (msedge, `--use-angle=d3d11 --enable-gpu`), CDP lewat node 24, server `php -S 127.0.0.1:8095` dengan HTML hasil render (tanpa database). Skrip dan tangkapan layar ada di scratchpad `uiqa`.
Catatan: halaman statis tanpa backend, jadi 2 galat 401 (`/api/notification-sounds`, `/api/notifications/unread-count`) muncul di konsol. Itu artefak harness, bukan tema, dan diabaikan.

## Butir 1: Dialog Play preview. PASS (Settings dan admin)
Bukti (Settings, tema global natal, pilihan Default; admin natal manual hasilnya sama):
- Klik Play: overlay `role="dialog"` `aria-modal="true"`, `aria-label="Loading screen preview: Christmas"`.
- Fokus awal: `document.activeElement` = `.sp-close`.
- Tab (3 kali) dan Shift+Tab: fokus tetap di Close (4 dari 4).
- Scroll body: `body.style.overflow="hidden"` saat buka; wheel 400 px di dialog tidak mengubah scrollY (418 sebelum dan sesudah); setelah tutup overflow kembali "" (computed `visible`).
- Esc menutup, fokus kembali ke tombol Play. Klik Close menutup, fokus kembali ke Play.
- Isi: logo EC (natural 800 px, tampil 100x35 px, animasi `sp-logo-in`), judul besar "Merry Christmas from Eclectic Consulting", subjudul doa "Wishing you joy, peace, and a wonderful holiday season". Tombol berlabel "Close preview".
- Tutup otomatis: log perubahan kelas menunjukkan overlay `hidden` pada 6024 ms (Settings) dan 6025 ms (admin) sejak klik.
- Tangkapan layar yang dibaca: shots/set_seq_02.png, set_loop_05/07/12.png, adm1_seq_03.png.

## Butir 2: Jaringan. PASS
- Sebelum klik Play (3,5 detik setelah load, Settings: 22 permintaan; admin: 24): 0 permintaan ke three.module.min.js, /js/seasonal/stage.js, /js/seasonal/scenes/*.
- Sesudah klik: stage.js, vendor/three/three.module.min.js, scenes/sleigh.js, _n-reindeer.js, _n-sleigh.js, _n-sky.js diunduh (semua berversi `?v=1795496700`).

## Butir 3: Loop pratinjau (reuseRenderer). PASS dengan 1 temuan (kedip antar putaran)
- Garis waktu kelas overlay (ms sejak klik, Settings): poster 16, 3d 122, poster 2748, 3d 2842, poster 5477, hidden 6033. Jadi 2 putaran adegan (2,6 detik masing-masing) lalu poster lalu tutup otomatis. Admin sama (2732, 2826, 5461, 6025).
- Konteks WebGL (patch getContext dan WEBGL_lose_context): saat dialog terbuka live = 1; setelah ditutup (Esc, Close, otomatis) live = 0 pada semua kasus. Dibuat 2 konteks pada pembukaan pertama (1 probe supportsWebGL, 1 renderer) lalu 1 per pembukaan berikutnya, semuanya dilepas (created = lost).
- 10 kali buka dan tutup: setiap iterasi midLive = 1, endLive = 0, fokus kembali ke Play, created = lost (14 = 14 di akhir). Heap JS 3,8 MB ke 4,2 MB, Nodes 4324 tetap, JSEventListeners 188 tetap, canvas di DOM tetap 1.
- Konsol: 0 Runtime.exceptionThrown, 0 console.error (selain 401 harness).
- Putaran kedua memakai ulang canvas (tidak ada konteks baru): created tidak bertambah antar putaran.
- TEMUAN (lihat daftar bug B1): pada sambungan antar putaran terlihat urutan terang (adegan) lalu poster terang (sekitar 95 ms) lalu satu frame hitam penuh lalu fade-in gelap. Bukti screencast CDP (shots/gap_172_2883.jpg adegan, gap_173_2916.jpg poster, gap_174_3000.jpg hitam, gap_175_3016.jpg redup).

## Butir 4: Reduced motion. PASS (Settings dan admin)
Emulation.setEmulatedMedia prefers-reduced-motion: reduce (matchMedia = true). Klik Play: dialog terbuka, `is-3d` tidak pernah aktif, canvas `display:none`, poster tampil (naturalWidth 640, display block), WebGL dibuat 0 kali (`created` 0, live 0), 0 permintaan three.js, stage.js, scenes. Logo `animation-name: none`, opacity 1, transform none. Catatan menjadi "Showing a still image because the animation cannot run on this device or with your current settings." (menggantikan doa; sesuai rancangan). Menutup sendiri sekitar 6 detik, 0 galat. Tangkapan layar dibaca: shots/rm_set_natal_default.png.

## Butir 5: Pemilih tema di Settings. PASS (1 catatan kosmetik)
Halaman Settings, tema global natal, pilihan awal Default. Delapan kartu: default, none, natal, tahun_baru, ramadan, lebaran, imlek, hut_ri. Hasil per klik (identik di 1280 dan 1920):
| Kartu | is-selected (jumlah, kunci) | html[data-season] | --primary-color pratinjau | Play |
|---|---|---|---|---|
| default | 1, default | natal (mengikuti global) | rgb(20, 69, 47) | aktif |
| none | 1, none | dihapus (null) | kembali ke #991b1b (aksen pengguna) | nonaktif, petunjuk "Select a theme that has a loading screen to preview it." |
| natal | 1 | natal | rgb(20, 69, 47) | aktif |
| tahun_baru | 1 | tahun_baru | rgb(34, 34, 44) | aktif |
| ramadan | 1 | ramadan | rgb(30, 64, 124) | aktif |
| lebaran | 1 | lebaran | rgb(27, 94, 32) | aktif |
| imlek | 1 | imlek | rgb(168, 52, 22) | aktif |
| hut_ri | 1 | hut_ri | rgb(168, 12, 36) | aktif |
Tata letak: 1280 px, kartu 96 px lebar membungkus 4 + 4, kolom kanan x 793 sampai 1213 dalam viewport 1280, tidak ada kartu di luar layar, docScrollWidth = clientWidth (1270, tanpa overflow horizontal), label tidak terpotong. 1920 px: 8 kartu satu baris (x 1001 sampai 1853), tanpa overflow. Tangkapan layar dibaca: shots/layout_1280.png, layout_1920.png.
Catatan kosmetik (K1): kartu dengan label dua baris ("Lunar New Year", "Independence Day") setinggi 108 px, kartu lain 92 px, sehingga tinggi baris tidak seragam (tidak ada yang terpotong).

## Butir 6: Layar loading asli di halaman login. PASS (1 catatan kontras)
Dipanggil `SeasonalLoading.start(tema, go)` pada HTML login hasil render (overlay `#seasonalLoginOverlay`) untuk 6 tema, masing-masing di halaman baru. Kondisi diukur pada 1,5 detik (kelas `is-on is-3d is-visible`) dan fungsi go dihitung sampai 4,1 detik.
- `start()` mengembalikan true di 6 dari 6 tema; go dipanggil tepat 1 kali di semua kasus; waktu panggil go (ms): EN 1280: natal 2836, tahun_baru 2933, ramadan 2983, lebaran 2903, imlek 2817, hut_ri 2928. ID 1280: 2964, 2839, 2953, 3197, 2944, 2834. ID 500: 3093, 2887, 3188, 3142, 3027, 3191. ID 360: 3306, 2923, 3057, 3137, 2997, 2933. Semua di bawah batas 3600 ms (terlama 3306 ms, sisa 294 ms).
- Elemen logo (151x52 px), judul (ucapan), subjudul (doa), baris "Preparing your EcoSystem workspace..." (EN) atau "Menyiapkan ruang kerja EcoSystem Anda..." (ID) semuanya terlihat, berada di dalam viewport (left >= 0, right <= lebar viewport) dan tidak terpotong (scrollWidth <= clientWidth) di 24 kombinasi (6 tema x 4 kondisi: EN 1280, ID 1280, ID 500, ID 360). Galat konsol dan exception: 0.
- Teks ID tampil benar (locale id lewat app()->setLocale('id')): "Selamat Natal dari Eclectic Consulting", "Selamat Hari Raya Idulfitri dari Eclectic Consulting" (subjudul "Mohon maaf lahir dan batin"), "Dirgahayu Republik Indonesia", dst.
- Teks panjang Idulfitri: di 1280 satu baris (lebar 657 px), di 500 px dua baris (x 24 sampai 476), di 360 px tiga baris (x 24 sampai 336), tidak terpotong dan tidak keluar layar.
- Tangkapan layar yang dibaca: ld_en_1280_natal.png, ld_en_1280_imlek.png, ld_en_1280_tahun_baru.png, ld_id_500_lebaran.png, ld_id_360_lebaran.png, ld_id_360_hut_ri.png. Judul putih tebal terbaca jelas di atas scrim gelap di semua.
- Catatan (K2): di 360 px tema HUT RI, logo (teks abu "Eclectic") berada di atas kerumunan figur putih sehingga kontras logo lebih rendah daripada tema lain; tetap terbaca. Scrim gelap baru menebal di bawah logo.

## Butir 7: Tema none. PASS (2 catatan)
Render Settings (global none) dan login (none), Edge, 4,5 detik setelah load.
- Login none: 4 permintaan total, 0 permintaan ke seasonal, three, scenes, stage.js, SVG tema, poster. `html[data-season]` tidak ada, tanpa atribut style. Overlay `#seasonalLoginOverlay` ada di DOM tetapi `display:none` (tidak terlihat, 0 canvas terlihat); `login-art` 0, ornamen/garland/lentera 0, tidak ada background-image seasonal. Pembanding: login natal memuat 8 permintaan seasonal.
- Settings none: 0 permintaan three, stage.js, scenes, SVG tema. Satu-satunya aset bertema adalah 6 gambar `posters/thumb/*.webp?v=` untuk kartu pemilih (bagian UI pemilih, bukan ornamen). `data-season` tidak ada, canvas 1 (milik dialog pratinjau, tersembunyi), 0 galat konsol.
- Catatan (K3): HTML login none tidak byte per byte sama dengan eco_new (ada pembungkus `<div class="dp-ambient">` dengan `display:contents`, overlay loading tersembunyi, import map, dan data 6 tema). Tampilan identik (orb bawaan tetap), tetapi sasaran "none identik byte per byte" di PROGRESS.md tidak berlaku untuk login. Tidak ada permintaan jaringan tambahan.

## Butir 8: Regresi non-tema. FAIL (1 bug: tombol konfirmasi danger berubah warna)
Settings dengan tema natal dan none, Edge 1280:
- Kontrol ada dan bisa diklik: Theme (3 tombol; klik Dark memberi border merah pada pilihan ke-2), Accent color (9 swatch; klik memberi tanda centang dan `--primary-color` #1976d2), Sidebar style (2; klik Solid menambah `primary-solid` pada #sidebar), Font size (3; klik Large mengubah root font-size 18px), Compact mode dan Show animations (checkbox bisa toggle), Save changes (4 tombol; klik memicu fetch `/settings/preferences`), Reset (klik membuka dialog konfirmasi). Hasil natal dan none sama. 0 galat konsol.
- Warna Save changes: natal = rgb(20, 69, 47) bila belum memilih aksen (hijau tema; mengikuti aksen pengguna setelah memilih), none = merah bawaan (red-700). Sesuai rancangan remap.
- Merah bahaya yang tetap merah saat natal aktif: tombol "Disable" 2FA (teks dan border red-600), "Sign Out" (teks merah), lencana notifikasi (bg red-600). "Remove" di jadwal admin ternyata tombol abu-abu berbingkai (border-gray-300, teks abu), bukan merah, di sumber maupun di port, jadi tidak berubah (diukur: bg transparan, teks oklch(0.446 0.03 256.8), klik menghapus baris: 4 menjadi 3).
- GAGAL: tombol OK pada dialog konfirmasi varian danger (Reset settings, Disable 2FA, dan semua showConfirm(..., 'danger')) berubah menjadi hijau tema saat natal aktif: `#globalConfirmOkBtn` bg rgb(20, 69, 47) dengan ikon peringatan merah dan tanpa atribut `data-danger`. Penyebab: `resources/views/partials/confirm-modal.blade.php` di aldy_eco tidak memuat baris dari origin/aldy_merge `if (variant === 'danger') { okBtn.setAttribute('data-danger', ''); } else { okBtn.removeAttribute('data-danger'); }`, padahal remap CSS (seasonal-ui-remap, komentar baris 11) mengandalkannya. Bukti: shots/reg_danger_confirm_natal.png. Lihat bug B2.
- Catatan (K4): kartu pilihan Theme dan Sidebar style yang terpilih tetap memakai border-red-600 dan bg-red-50/50 (merah) di tema hijau (natal), karena red-50 sampai red-600 sengaja tidak dipetakan. Tidak ada kartu yang rusak, hanya aksen merah di tengah tema hijau (terlihat di shots/reg_danger_confirm_natal.png, latar buram).

## Tambahan butir 3: loop untuk 6 adegan (Settings, pilih kartu lalu Play)
Garis waktu kelas overlay (p = poster, 3 = 3d, h = hidden; ms sejak klik), konteks sisa setelah tutup, galat:
| Tema | Garis waktu | live | created/lost | galat |
|---|---|---|---|---|
| natal | p25 3:387 p:3002 3:3127 p:5769 h:6034 | 0 | 2/2 | 0 |
| tahun_baru | p24 3:263 p:2885 3:2998 p:5650 h:6029 | 0 | 2/2 | 0 |
| ramadan | p17 3:428 p:3043 3:3202 p:5827 h:6026 | 0 | 2/2 | 0 |
| lebaran | p24 3:453 p:3065 3:3270 p:5925 h:6028 | 0 | 2/2 | 0 |
| imlek | p29 3:350 p:2957 3:3084 p:5712 h:6041 | 0 | 2/2 | 0 |
| hut_ri | p24 3:285 p:2899 3:3011 p:5666 h:6029 | 0 | 2/2 | 0 |
Semua tema: 2 putaran adegan, jeda poster 100 sampai 200 ms antar putaran, tutup otomatis 6,03 sampai 6,04 detik. Tangkapan layar 2 detik: shots/all3_<tema>.png.

# Ringkasan

| Butir | Hasil |
|---|---|
| 1 Dialog Play (Settings dan admin) | PASS |
| 2 Jaringan lazy (0 sebelum, three dan stage sesudah klik) | PASS |
| 3 Loop pratinjau, konteks, kebocoran, konsol | PASS (temuan kedip B1) |
| 4 Reduced motion | PASS |
| 5 Pemilih tema Settings (1280 dan 1920) | PASS (kosmetik K1) |
| 6 Loading screen login EN dan ID, 360 dan 500 px | PASS (kontras K2) |
| 7 Tema none | PASS (K3) |
| 8 Regresi non-tema | FAIL (B2) |

## Daftar bug berurut keparahan
B2 (Sedang). Tombol OK dialog konfirmasi danger berubah warna mengikuti tema.
- Reproduksi: aktifkan tema musiman (mis. natal), buka Settings, klik Reset (atau panggil `showConfirm('x','Reset Settings','danger')`). Tombol OK hijau (rgb(20, 69, 47)) dengan ikon peringatan merah; seharusnya tetap merah.
- Penyebab: `resources/views/partials/confirm-modal.blade.php` di aldy_eco tidak memuat baris `data-danger` yang ada di origin/aldy_merge (berkas ini tidak masuk daftar kait). Dampak seluruh aplikasi: setiap showConfirm varian danger dan confirmSubmit default danger (Disable 2FA, hapus data, dsb).
- Perbaikan yang disarankan: tambahkan satu baris `if (variant === 'danger') { okBtn.setAttribute('data-danger', ''); } else { okBtn.removeAttribute('data-danger'); }` setelah `okBtn.className = OK_CLS[variant];`, atau selaraskan seluruh berkas dengan aldy_merge (perhatikan perbedaan lain di blok akhir berkas) lalu catat sebagai kait di DEVIATIONS.md.

B1 (Rendah). Kedip antar putaran pratinjau.
- Reproduksi: Settings, tema natal, klik Play, amati sekitar detik 2,7 sampai 3,0 (screencast CDP). Urutan: adegan terang, poster terang (sekitar 95 ms), satu frame hitam penuh, lalu fade-in gelap ke putaran kedua. Bukti: shots/gap_172_2883.jpg, gap_173_2916.jpg, gap_174_3000.jpg, gap_175_3016.jpg. Ketiga tema lain sama (jeda poster 100 sampai 200 ms).
- Saran: pada sambungan putaran, pertahankan frame terakhir canvas atau jangan tampilkan poster; atau mulai fade-in putaran kedua dari poster (crossfade) agar tidak ada lompatan terang, hitam, terang.

K1, K2, K3, K4 (kosmetik atau catatan, bukan bug fungsi): tinggi kartu pemilih tidak seragam; kontras logo di atas kerumunan terang di HUT RI 360 px; login none tidak byte per byte identik (pembungkus dp-ambient, overlay tersembunyi); kartu terpilih Theme dan Sidebar tetap merah di tema hijau.

Catatan lingkungan: dua galat 401 di konsol (`/api/notification-sounds`, `/api/notifications/unread-count`) berasal dari harness statis tanpa backend. GPU: Edge headless `--use-angle=d3d11 --enable-gpu`; frame time tidak diukur di sini (itu langkah 6c). Server uji 8095 sudah dimatikan lewat PID. Kode repo tidak diubah; hanya berkas ini yang ditulis.
