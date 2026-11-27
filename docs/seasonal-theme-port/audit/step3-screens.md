# Langkah 3: QA visual halaman login (read-only)

Metode: router PHP sementara (127.0.0.1:8099, tanpa DB) melayani public/ dan HTML hasil render; Edge headless dikendalikan lewat CDP (emulasi viewport, tangkapan layar, pengukuran DOM, log permintaan jaringan). Tangkapan layar dibaca langsung (semua tema di 1920 dan 1024, sebagian di 1280 dan 390; nilai 1280 dan 390 lainnya didukung pengukuran DOM identik). Server sudah dihentikan.

Status progres: none SELESAI, natal SELESAI, tahun_baru SELESAI, ramadan SELESAI, lebaran SELESAI, imlek SELESAI, hut_ri SELESAI (masing-masing 4 viewport).

## Ukuran (CDP)

| Hal | Hasil di semua 28 kombinasi |
|---|---|
| Form tertutup elemen lain (elementFromPoint di tengah dan dua sudut form) | Tidak, selalu elemen form |
| scrollWidth html vs clientWidth | Sama (tidak ada scroll horizontal) |
| Panel kiri (#deco-panel) di 390 px | display:none, lebar 0 |
| Panel kiri di 1024 px ke atas | tampil, lebar 50 persen (1024) dan 55 persen (1280, 1920) |
| Permintaan /images/seasonal/login/*.svg di 390 px | Tidak ada (tiap tema); di >=1024 px tepat 1 SVG sesuai tema |
| Tema none | Tidak ada permintaan seasonal sama sekali (nol SVG, nol JS, nol three) |

## Hasil per tema per viewport

Kriteria: (a) form tidak tertutup, (b) teks panel kiri terbaca, (c) tanpa overflow horizontal, (d) di bawah 768 px panel kiri disembunyikan dan tanpa aset ilustrasi, (e) none tampak seperti login merah bawaan.

| Tema | 1920x1080 | 1280x720 | 1024x768 | 390x844 |
|---|---|---|---|---|
| none | PASS | PASS | PASS | PASS |
| natal | PASS | PASS | PASS | PASS (lihat catatan 3) |
| tahun_baru | PASS | PASS | PASS | PASS (lihat catatan 3) |
| ramadan | PASS | PASS | PASS | PASS (lihat catatan 3) |
| lebaran | PASS | PASS | PASS | PASS (lihat catatan 3) |
| imlek | PASS | PASS | PASS | PASS (lihat catatan 3) |
| hut_ri | PASS | PASS | PASS | PASS (lihat catatan 3) |

Bukti visual utama:
- none: gradien merah gelap bawaan dengan bentuk kapsul/berlian halus, tanpa ilustrasi musiman, tombol merah. Sesuai (e).
- natal: pohon natal, rumah, salju, hijau gelap; tombol hijau.
- tahun_baru: kembang api dan jam di kota malam; tombol hitam arang.
- ramadan: masjid, bulan sabit, lentera, biru malam; tombol biru.
- lebaran: ketupat, bedug, masjid, hijau; tombol hijau.
- imlek: naga, lentera, plum, emas batangan, merah bata; tombol merah bata.
- hut_ri: bendera merah putih, panji, Monas, bambu runcing; tombol merah.

## Temuan dan catatan (tidak ada yang memblokir)

1. Kontras teks panel kiri: semua tema memakai teks putih/hampir putih di atas gradien gelap, kira-kira jauh di atas 4,5:1. Panel diberi scrim dan kartu fitur transparan gelap sehingga teks tetap terbaca meski ornamen lewat di belakang. Titik terlemah (tetap terbaca): subjudul di atas ledakan kembang api/bulan (lebaran 1920, natal 1024), kartu fitur di atas badan naga (imlek 1920), dan notice "Authorized personnel only" yang menimpa kabin/hadiah natal (diberi pil gelap).
2. Pada 1024 px ilustrasi tampak diredam dan terpotong di balik teks (berfungsi sebagai tekstur latar). Teks tetap terbaca.
3. Tema musiman masih memuat JS (stage.js, scene*.js, three.module.min.js sekitar 690 KB) di 390 px lewat pemanasan idle. Ini sengaja (untuk overlay loading pasca login, dilewati saat Save-Data/2g/3g) dan bukan gambar ilustrasi panel login, jadi (d) dinilai PASS untuk aset ilustrasi. Bila ingin nol aset 3D di ponsel, pemanasan bisa dikondisikan dengan matchMedia (min-width:768px). Saran, bukan cacat.
4. Elemen dekoratif (DIV lebar 420 px, tepi kanan 500 px) menjulur melewati viewport 390 px (body.scrollWidth 500) tetapi dipotong overflow-x:hidden pada body, sehingga tidak ada scroll horizontal. Sama persis pada tema none, jadi bawaan lama.
5. Di 390 px ada scrollbar vertikal (tinggi dokumen 852 vs 844) di semua tema termasuk none; bawaan lama. Logo Eclectic muncul dua kali (bilah header mobile dan logo kanan atas), juga bawaan.
6. Footer hak cipta di sisi form berkontras rendah (abu muda di putih), bawaan lama di semua tema.
7. Perbandingan dengan poster referensi (public/images/seasonal/posters/*.webp, contoh imlek): poster adalah render 3D (naga bersisik, lentera, rumah), sedangkan panel login memakai ilustrasi SVG vektor datar dengan motif dan palet sama. Motif dan warna sesuai tema, gaya tidak identik. Kanvas 3D hidup tidak tampak pada tangkapan headless (renderer perangkat lunak), jadi yang dinilai adalah SVG statis.

## Ringkasan

28 dari 28 kombinasi PASS. Tidak ada form tertutup, tidak ada overflow horizontal nyata, panel kiri tersembunyi di bawah 768 px tanpa permintaan SVG ilustrasi, dan tema none identik dengan login merah bawaan tanpa ilustrasi atau aset musiman. Hanya catatan minor (poin 3 sampai 6).
