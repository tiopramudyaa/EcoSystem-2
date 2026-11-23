# Seasonal Theme - Katalog, Jadwal, dan Cara Menambah Tema

> **Tujuan dokumen.** Panduan resmi untuk developer tentang sistem tema musiman: bagaimana katalog bekerja, bagaimana tema dipilih untuk tiap pengguna, dan langkah menambah tema baru. Panduan pengguna ada di `resources/guide/{en,id}/148-cc-seasonal-theme.md`.

## 1. Gambaran

Tema musiman mengubah empat hal: dekorasi halaman (efek animasi dan ornamen vektor SVG), warna sidebar, header, tombol, dan tautan, adegan 3D loading setelah login (three.js), serta ikon tema di picker. Tidak ada emoji atau stiker; semua aset digambar sebagai SVG atau dirender 3D. Semua definisi tema berada di **satu katalog**: `App\Support\SeasonalThemes::CATALOG`. Tujuh key yang dikenal: `none`, `natal`, `tahun_baru`, `ramadan`, `lebaran`, `imlek`, `hut_ri`.

## 2. Urutan penentuan tema

Tema yang dirender untuk satu pengguna ditentukan oleh `App\Support\SeasonalThemeResolver::effectiveFor()`:

1. Preferensi pengguna (`auth_users.preferences.seasonal_theme`):
   - `default` : ikut admin (langkah 2).
   - `none` : dimatikan pengguna, menang atas admin.
   - key tema lain : dipilih pengguna, menang atas admin.
2. Setting global admin (`GlobalSeasonalTheme::active()`): mode manual (tema yang dipilih selalu aktif) atau jadwal (tema sesuai tanggal hari ini).
3. Tidak ada tema (`none`).

Nilai tak dikenal (data lama atau rusak) selalu menjadi `none`. Pengguna yang belum pernah menyimpan preferensi dianggap `default`.

Halaman login belum punya pengguna, jadi memakai `GlobalSeasonalTheme::active()` langsung.

## 3. Penyimpanan data

| Data | Lokasi |
|---|---|
| Setting global admin | tabel `app_configs`, key `seasonal_theme.global` (JSON: `theme`, `mode`, `schedule`, `sound`, `volume`) |
| Preferensi pengguna | `auth_users.preferences.seasonal_theme` |
| File musik | `public/sounds/` (folder yang sama dengan Notification Sounds) |

Data selalu disanitasi ulang saat dibaca (`GlobalSeasonalTheme::sanitize()`), sehingga baris yang diedit manual atau menyebut tema yang sudah dihapus tidak menjatuhkan halaman. Ketika menyimpan, kirim **semua** field bersamaan (`theme`, `sound`, `volume`, `mode`, `schedule`); field yang hilang jatuh ke default.

## 4. Jadwal

Mode `auto` memakai daftar `schedule` berisi `{theme, start, end}`:

- Tanggal berformat `Y-m-d`, kedua ujung **inklusif**.
- Di luar semua rentang hasilnya `none`.
- Rentang yang tumpang tindih: baris yang tertulis **lebih dulu** menang (urutan input dipertahankan).
- Maksimal 20 baris. Form admin menolak input tidak valid dengan galat validasi (tema tidak dikenal atau `none`, tanggal bukan Y-m-d, akhir sebelum awal). Saat dibaca, baris rusak dibuang diam-diam bila temanya `none` atau tidak dikenal, tanggal tidak valid, atau tanggal akhir sebelum tanggal awal.
- Mode `manual` mengabaikan jadwal (tetap tersimpan).
- Tanggal Ramadan, Lebaran, dan Imlek berpindah tiap tahun, jadi `window` katalog bernilai `null` untuk tema itu dan admin mengisi tanggal tepatnya.

## 5. Struktur satu entri katalog

| Field | Arti |
|---|---|
| `label_key` | Kunci terjemahan nama tema, `ui.seasonal.themes.<key>` |
| `icon` | Kelas Font Awesome cadangan (tema `none`) |
| `accent` | Warna chip pratinjau |
| `palette` | Nilai CSS variable `--st-*`: `primary_rgb`, `primary_dark_rgb`, `accent`, `login_from`, `login_mid` (tengah gradien panel login), `login_to`, `button` (warna tombol Sign In), `loading_bottom` (warna bawah langit loading). Opsional: `button_text` (teks tombol, default putih), `button_hover`, `link` (tautan dan label di atas putih, default `button`), dan `dark` (varian mode gelap, hanya natal; belum dipakai). `null` untuk `none` |
| `decoration` | Tag efek: `snow`, `confetti`, `stars`, `sparkle`, `lantern`, `flag`, atau `null` |
| `ornaments` | Nama ornamen vektor, symbol SVG `st-orn-<nama>` di `partials/seasonal-icons` |
| `scene` | Adegan 3D loading: `sleigh`, `fireworks`, `crescent`, `eid`, `lunar`, `flag`, atau `null` |
| `loading` | Kunci judul dan subjudul loading screen, `ui.seasonal.loading.<key>.title` dan `.subtitle` |
| `window` | Saran jadwal `[mm-dd awal, mm-dd akhir]`, atau `null` bila tanggal berpindah |

## 6. Cara menambah tema baru

1. Tambahkan konstanta key dan satu entri di `SeasonalThemes::CATALOG` (`app/Support/SeasonalThemes.php`). Daftar yang diterima validator Settings dan halaman admin dibangun dari `SeasonalThemes::keys()`, jadi tidak ada literal lain yang perlu diubah.
2. Tambahkan teks di `lang/en/ui.php` dan `lang/id/ui.php`:
   - `ui.seasonal.themes.<key>`
   - `ui.seasonal.loading.<key>.title`
   - `ui.seasonal.loading.<key>.subtitle`
3. Pilih `decoration` dari tag yang sudah ada (`snow`, `confetti`, `stars`, `sparkle`, `lantern`, `flag`). Tag baru berarti menambah efek di partial dekorasi (`resources/views/partials/seasonal-theme.blade.php`).
   - Tambahkan symbol ikon `st-icon-<key>` dan ornamen `st-orn-<nama>` di `resources/views/partials/seasonal-icons.blade.php`.
   - Pilih `scene` yang ada, atau buat modul baru `public/js/seasonal/scenes/<scene>.js` yang mengekspor `create(THREE, ctx)` dan daftarkan di `public/js/seasonal/stage.js`.
   - Pastikan teks putih di atas `primary_rgb` dan `primary_dark_rgb` memenuhi kontras minimal 4,5:1.
4. Perbarui panduan pengguna (daftar tema di topik `Seasonal Theme`, EN dan ID) dan tambahkan test unit untuk katalog bila perlu.
5. Jangan memakai em dash atau en dash di teks.

## 7. Animasi dan aksesibilitas

Dekorasi bergerak hanya bila preferensi `Show animations` pengguna aktif dan perangkat tidak memakai reduced motion.

### Palet musim 2026 dan ilustrasi panel login

Prinsip satu identitas: tiap tema punya satu keluarga warna dominan yang sama di halaman login (gradien dan tombol), loading screen, dan dashboard (sidebar `primary_dark` ke `primary`, tombol, tautan, status aktif lewat remap); aksen identik di ketiganya; warna sekunder hanya ornamen kecil. Dijaga oleh `PaletteCoherenceTest`. Nilai: natal evergreen (`primary` 20 69 47, `primary_dark` 11 42 30, login `#0b2a1e` ke `#14452f` ke `#1f6b43`, tombol `#1b6a40`, aksen emas `#d4af37`, merah hanya ornamen); tahun_baru arang hampir hitam (`primary` 34 34 44, `primary_dark` 10 10 15, login `#07070b` ke `#16161f` ke `#2b2b36`, tombol arang `#2b2b36`, aksen emas `#f3dc8e`); ramadan biru tua; lebaran hijau ketupat; imlek lak ke vermilion `#a83416` dengan emas; hut_ri merah bendera `#a80c24` dengan aksen putih. Merah imlek dan hut_ri sengaja lebih gelap dari merah bahaya aplikasi (`#dc2626`, jarak RGB minimal 50) supaya tombol Primary tidak terbaca sebagai tombol hapus. Tidak ada citra minuman di tema mana pun: ikon cadangan tahun_baru `fa-clock`, ornamen `clock`, ilustrasi login berupa menara jam tengah malam. Gradien panel login: `login_from` ke `login_mid` (55%) ke `login_to`. Loading screen: langit atas `login_from`, bawah `loading_bottom` (tahun_baru, ramadan, dan lebaran memakai `login_to`).

Panel kiri halaman login menampilkan ilustrasi vektor statis `public/images/seasonal/login/<key>.svg` (viewBox 1000x800, maksimal 45 KB, tanpa skrip, gambar raster, atau referensi luar), dimuat lewat CSS `background-image` hanya untuk tema aktif (`partials/login-art`). Kotak gambar berposisi absolut di kanan bawah dengan `aspect-ratio: 5 / 4` (tanpa CLS); panel disembunyikan di bawah 768 px sehingga tidak ada unduhan di ponsel dan tidak ada permintaan untuk tema `none`. Scrim gradien menjaga teks panel terbaca (kontras terburuk terukur 5,0:1 pada piksel paling terang). Dekorasi polos bawaan (`.dp-ambient`) disembunyikan saat tema aktif. Gerak ambien: paling banyak 10 elemen `<i>`, hanya transform dan opacity, hanya tanpa reduced motion. Subjek utama diletakkan di separuh kanan gambar karena teks panel menutupi sisi kiri. Berkas dibuat dari generator skrip (tidak ada dependensi build); tema baru wajib menambah SVG dan daftar efeknya di `partials/login-art`. Diuji di `LoginArtTest`.

### Warna tombol dan UI

`partials/seasonal-theme-vars` menimpa `--primary-rgb`, `--primary-dark-rgb`, `--primary-color`, dan `--primary-surface` di bawah `html[data-season]`, sehingga sidebar, `.primary-*`, dan halaman accent-remap ikut berubah. `partials/seasonal-ui-remap` memetakan utility merek (`bg-red-700/800/900`, hover-nya, `text-red-700/800` pada tautan dan tombol, border, ring, gradien) ke palet. Warna bahaya tidak ikut: `bg-red-500/600`, elemen `[data-danger]`, `onclick` yang memuat delete, reject, atau revoke, lencana `.uppercase`, dan isi kotak peringatan. Tombol hapus atau tolak baru yang memakai `bg-red-700/800` wajib diberi atribut `data-danger`. Warna `#991b1b` yang ditulis langsung di blok `<style>` halaman tidak ikut dipetakan. CSS remap hanya dimuat saat tema aktif atau di halaman `settings.*` (pratinjau memasang `data-season` lewat JS); halaman tanpa tema tidak membawanya. Pratinjau tema di luar `settings.*` di masa depan harus ikut memuatnya.

### Hiasan navbar

`partials/seasonal-navbar` memasang dua potong vektor kecil (pojok kiri dan kanan header) yang dihasilkan `App\Support\SeasonalNavbarArt` dan dicetak inline (tanpa berkas gambar, tanpa JavaScript). Bukan rumbai selebar layar: bagian di dalam header hanya di pojok luar (di balik tombol menu dan avatar), sepanjang tepi hanya bandul mungil jarang yang menjuntai maksimal 12 unit, sehingga judul dan subjudul tidak tertutup. Tiap tema punya motif berbeda (natal: ranting pinus, holly, lonceng; tahun_baru: pita melingkar, kembang api; ramadan: bulan sabit, lentera, siluet masjid; lebaran: janur, ketupat; imlek: lampion, ranting plum, koin; hut_ri: bendera bersilang, umbul-umbul pendek). Aturan lapisan: `position:absolute`, `z-index:-1`, `pointer-events:none`, `contain`, tinggi header tidak berubah, disembunyikan saat print dan `prefers-contrast: more`. Gerak bervariasi (kelas sn-sway ayun, sn-twinkle kedip, sn-pop letup, sn-wave kibar, sn-breathe berpendar) hanya lewat transform/opacity, aktif bila `Show animations` menyala dan bukan reduced motion. Layar di bawah 640 px hanya menampilkan potongan kiri. Tema baru wajib menambah method di `SeasonalNavbarArt`. Tambahan: garis tepi header 2px per tema (`::before`, mask memudar di ujung, natal hijau evergreen dengan sorot emas, tahun_baru arang dengan sorot emas, ramadan biru tua dan emas dengan bintang kecil lewat `SeasonalNavbarArt::edgeStarsTile()`, lebaran hijau janur dengan sorot lime dan emas, imlek vermilion dan emas, hut_ri merah bendera); potongan ketiga `fl` dan `fr` (viewBox 70x40, `SeasonalNavbarArt::fx()`) berisi elemen jarang di pojok luar per tema (natal kepingan salju dan kristal berwarna es kehijauan, tahun_baru jejak kilau, ramadan bintang kecil, lebaran kilau, imlek kelopak, hut_ri bintang kecil) dengan gerak `sn-drift` dan `sn-fall` selain `sn-twinkle`; dan khusus natal tepi es tidak beraturan setinggi maksimal 6px (`::after`, ubin data URI `SeasonalNavbarArt::frostTile()`, opacity .5). Anggaran tiap potongan: maksimal 14 KB dan 8 elemen bergerak (diuji di `SeasonalNavbarArtTest`). Variabel `--sn-b` (14, 11, 9px) memberi tinggi bagian layer di bawah tepi header.

### Aksen dashboard (fase 1)

`partials/seasonal-accents` dipasang dari `dashboard.blade.php` hanya saat ada tema aktif dan hanya mencetak CSS tema itu (sekitar 1 sampai 4 KB), semuanya di bawah `html[data-season="..."]`. Selector memakai daftar putih dari markup nyata: `#sidebar`, `#sidebarHeader`, `#sidebar a.nav-link.active`, `#mainContent > header`, dan kartu ringkasan `section[aria-label="Company overview" | "Tickets" | "Recent activity"] > div.rounded-xl`. Kartu memakai `background-image` (tanpa mengubah position atau tinggi), pseudo-element hanya di sidebar dan header yang sudah positioned. Per tema: natal (garis rambut es kehijauan (mint) di kartu, header, dan tepi sidebar, selaras dengan sidebar evergreen; tutup salju di item aktif; sudut beku di kartu; bintik salju statis di `body::before` fixed, opacity .45, `contain: strict`), tahun_baru (garis kilau emas di kartu dan header, garis emas di bawah header sidebar, hover glint di kartu Company overview dengan transform dan opacity saja bila Show animations aktif), ramadan (arabesque sekitar 6 persen di header sidebar, garis emas tipis), lebaran (anyaman ketupat samar di header sidebar, garis hijau di header), imlek (siku emas di kartu, garis emas di header sidebar), hut_ri (garis 3px merah putih di atas header). Dilarang: backdrop-filter, filter di wadah besar, animasi box-shadow, perubahan tabel, form, modal, grafik, warna status atau bahaya. Tersembunyi saat print dan `prefers-contrast: more`. Fase berikutnya (kartu modul lain) menambah daftar putih, bukan selector luas.

### Loading screen 3D

Setelah login sukses, respons `POST /api/auth/login` membawa `seasonal_theme` (tema efektif pengguna itu, dihitung dari kolom `preferences` yang sudah dimuat tanpa query tambahan). `login.blade.php` memilih adegan dari data itu. Alurnya:

| Jalur | Pengalihan ke dashboard |
|---|---|
| Tema `none` | Toast, 1000 ms |
| 3D berjalan | maks(adegan selesai, 2200 ms), tidak pernah lebih dari 3600 ms |
| WebGL tidak ada, impor gagal, atau reduced motion | Versi statis, 1800 ms |

Pengalihan selalu dijadwalkan lebih dulu oleh satu timer sehingga kegagalan apa pun tidak menahan login. three.js (r170, `public/vendor/three`) dan modul adegan baru diunduh saat adegan akan diputar, atau saat idle bila tema global tidak `none`; tidak ada unduhan untuk tema `none`. Adegan membuang semua geometri, material, dan konteks WebGL setelah selesai, membatasi pixel ratio 1,5, tanpa shadow map, dan maksimal 2500 partikel. Dashboard tidak lagi memiliki splash.

**Versi aset 3D.** `seasonal-loading` mencetak import map yang memetakan setiap modul di `public/js/seasonal` dan `public/vendor/three` ke URL `?v=<mtime terbaru>`, termasuk impor relatif antar-modul, sehingga deploy baru tidak memuat campuran modul lama dan baru dari cache browser (browser tanpa import map tetap bekerja, hanya tanpa jaminan itu).

**Kualitas adaptif.** `stage.js` memakai tier `low` (pixel ratio 1, tanpa antialias) bila `deviceMemory <= 4`, `hardwareConcurrency <= 4`, atau Save-Data aktif; pada tier `high` pixel ratio maksimal 1,5. Bila rata-rata 10 frame pertama di atas 40 ms, pixel ratio turun ke 1, dan bila sudah 1 (layar DPR 1 atau tier `low`), skala render turun ke 0,75. Preload idle dilewati saat Save-Data atau koneksi 2g/3g.

**Pratinjau (opt-in).** Kartu tema di Settings > Appearance dan Control Center > Seasonal Theme menampilkan poster statis (`partials/seasonal-poster`, WebP 640x360, `loading="lazy"`, width/height diset). Satu tombol `Play preview` (`partials/seasonal-preview`) memutar adegan kartu yang sedang dipilih (`default` memakai tema global, tema tanpa adegan menonaktifkan tombol). Tidak ada unduhan three.js saat halaman dimuat: `stage.js` dan adegan baru di-`import()` setelah klik, memakai import map berversi yang sama dengan `seasonal-loading`. Aturan: maksimal sekitar 6 detik lalu menutup sendiri (adegan 2600 ms diulang bila sisa waktu cukup), tutup dengan Esc, klik, atau tombol `Close preview`, fokus terkunci dan dikembalikan ke tombol, `role="dialog"` `aria-modal`, hanya satu pratinjau, adegan dibuang (RAF berhenti, konteks GL dilepas) saat tutup dan saat tab tersembunyi. Bila WebGL tidak ada, Save-Data, koneksi 2g/3g, atau reduced motion, WebGL tidak dijalankan dan overlay menampilkan poster statis. Tanpa query DB dan tanpa panggilan jaringan selain aset statis. Poster dibuat ulang dengan merender adegan asli pada frame yang bagus (SwiftShader atau GPU nyata, `renderer.render` lalu screenshot WebP kualitas sekitar 70) dan disimpan di `public/images/seasonal/posters/<key>.webp`; ukurannya diuji maksimal 40 KB. Tema baru wajib menambah posternya.

**Adegan.** `sleigh` (Natal: malam evergreen-teal, aurora zamrud dan teal lembut, salju kehijauan, desa bersalju, gereja, bulan bertekstur), `fireworks` (Tahun Baru: kota, sungai, jembatan, lima jenis kembang api), `crescent` (Ramadan: bulan sabit berfase, masjid Utsmani dengan pantulan, lentera berpola), `eid` (Lebaran: keluarga berkumpul, ketupat dua warna hijau tua dan janur; senja hijau-teal dari `login_from`/`login_mid`/`login_to`, kabut dan bayangan kehijauan, kayu dan paving zaitun, cahaya pelita dan jendela emas-amber sebagai aksen hangat), `lunar` (Imlek: naga emas dan lampion), `flag` (HUT RI: bendera naik, barisan hormat, bambu runcing; fajar merah tua ke jingga hangat dari palet tema dengan pendar putih-emas, kabut merah, bukit dan tanah merah-cokelat ke zaitun, merah putih bendera tetap tajam). Figur manusia memakai pembuat parametrik `scenes/_people.js`. Batas per adegan: tanpa shadow map, maksimal 2500 partikel dan 250 draw call, semua resource dibuang di akhir.

**Memo server.** `GlobalSeasonalTheme::all()` dibaca sekali per request lewat memo di container (satu SELECT cache bila `CACHE_STORE=database`, nol bila hangat); `save()` memanggil `flushMemo()`. Daemon `queue:work` memakai container seumur proses, jadi job yang membaca ini harus memanggil `flushMemo()` dulu. Musik latar hanya diatur admin (lagu dan volume, global); pengguna hanya dapat membisukannya secara lokal di browser (WCAG 1.4.2).

## 8. Peta file

| File | Peran |
|---|---|
| `app/Support/SeasonalThemes.php` | Katalog tema, `keys()`, `catalog()`, `isValid()` |
| `app/Support/GlobalSeasonalTheme.php` | Setting global admin, mode, jadwal, musik, sanitasi |
| `app/Support/SeasonalThemeResolver.php` | Menggabungkan preferensi pengguna dengan setting admin |
| `app/Http/Controllers/SeasonalThemeSettingsController.php` | Halaman admin (simpan, unggah musik), audit log |
| `app/Http/Controllers/SettingsController.php` | Simpan preferensi pengguna (`seasonal_theme`) |
| `resources/views/admin/seasonal-theme-settings.blade.php` | Halaman Control Center > Seasonal Theme |
| `resources/views/settings/index.blade.php` | Picker di Settings > Appearance |
| `resources/views/partials/seasonal-theme.blade.php` | Dekorasi |
| `resources/views/partials/seasonal-navbar.blade.php` + `app/Support/SeasonalNavbarArt.php` | Hiasan pojok navbar (dua svg inline per tema) dengan gerak bervariasi, di dalam header dashboard. z-index -1, pointer-events none, hanya menjuntai maksimal 12 unit, disembunyikan saat print dan prefers-contrast: more. Menggantikan placement `header` pada partial dekorasi |
| `resources/views/partials/seasonal-theme-vars.blade.php` | CSS variable `--st-*` dari palet tema |
| `resources/views/partials/login-art.blade.php` + `public/images/seasonal/login/<key>.svg` | Ilustrasi panel kiri login, scrim, dan efek ambien |
| `resources/views/partials/seasonal-loading.blade.php` | Overlay loading (kanvas 3D, versi statis, data tema) |
| `resources/views/partials/seasonal-preview.blade.php` | Tombol `Play preview` opt-in dan overlay pratinjau (Settings dan halaman admin), lihat bagian Pratinjau |
| `resources/views/partials/seasonal-poster.blade.php` | Thumbnail poster statis di kartu tema |
| `public/images/seasonal/posters/<key>.webp` | Satu poster 640x360 per tema beranimasi (maks 40 KB) |
| `resources/views/partials/seasonal-icons.blade.php` | Sprite SVG: `st-icon-<key>` dan `st-orn-<nama>` |
| `resources/views/partials/seasonal-icon.blade.php` | Pemakai satu symbol (parameter `name`, `class`, `size`) |
| `resources/views/partials/seasonal-ui-remap.blade.php` | Pemetaan warna tombol dan UI per tema |
| `public/js/seasonal/stage.js`, `common.js`, `scenes/*.js` | Pemuat dan adegan 3D |
| `public/vendor/three/` | three.js r170 (lisensi MIT) |
| `app/View/Composers/SeasonalThemeComposer.php` | Membagikan variabel `seasonalTheme` ke dashboard dan halaman auth |
| `app/Http/Controllers/AuthController.php` | `effectiveSeasonalTheme()` untuk respons login |
| `lang/{en,id}/ui.php` | Teks (`ui.seasonal.*`) |
| `routes/web.php` | Rute `admin.seasonal-theme*` dengan middleware `menu:control-center.seasonal-theme` |
| `resources/guide/{en,id}/148-cc-seasonal-theme.md` | Panduan pengguna |

Akses menu `control-center.seasonal-theme` hanya untuk EC Administrator secara bawaan dan dapat dipindah lewat Menu Access.
