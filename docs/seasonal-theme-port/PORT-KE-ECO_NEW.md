# Seasonal Theme: spesifikasi dan panduan penerapan di aldy_eco (lalu merge ke eco_new)

Dokumen ini hanya membahas **fitur Seasonal Theme**: bagaimana tema bekerja, seperti apa tampilannya, animasi, warna, penempatan, aturan, berkas yang membentuknya, dan cara memasangnya di branch lain. Sidebar, header, dan layout dashboard **bukan** bagian yang diubah. Layout hanya menerima beberapa "kait" kecil (bagian 6) supaya tema bisa tampil di dalamnya.

Rencana kerja yang dipakai dokumen ini: Anda mengerjakan di `aldy_eco`, lalu `aldy_eco` di-merge ke `eco_new`.

Sumber kode tema: branch `aldy_merge` (juga `merge_eco-hr`). Kesamaan dengan sumber dijamin oleh **ID blob git** per berkas (bagian 7), bukan oleh ingatan atau salinan manual. Dokumen dibuat 2026-11-24.

Berkas pendamping (folder yang sama):
- `manifest-new-files.txt`: 77 berkas tema yang baru bagi eco_new.
- `manifest-replace-files.txt`: 6 berkas tema yang sudah ada di eco_new dan aman ditimpa penuh (isinya murni tema).
- `blob-ids.txt`: ID blob git semua berkas di kedua manifest (bukti kesamaan).
- `verify-port.sh`: memeriksa bahwa berkas di working tree identik dengan sumber.
- `extract-seasonal-lang.php`: mengekstrak hanya grup bahasa `seasonal`.

---

## 1. Apakah cara ini bisa dan efektif?

**Bisa dan efektif, dengan satu syarat: aset dan kode tema disalin apa adanya dari git, tidak dibuat ulang dari deskripsi.**

| Bagian | Cara memindahkan | Alasan |
|---|---|---|
| Adegan 3D, three.js, SVG login, poster, partial tema, kelas PHP tema, tes, panduan tema | **Salin dari git** (`git checkout <ref> -- <daftar>`) lalu cocokkan dengan `blob-ids.txt` | Sekitar 1,5 MB kode dan aset yang sudah diuji di GPU nyata. Prosa tidak bisa mereproduksi adegan 3D 2000+ baris atau ilustrasi SVG 40 KB dengan hasil yang sama. |
| Kait di berkas bersama (bagian 6) | Manual terpandu, kode referensi **dicetak dari git**, bukan diketik ulang | Berkas bersama berbeda jauh antar branch. Hanya beberapa baris yang ditambahkan. |
| Penjelasan desain, aturan, uji penerimaan | Dokumen ini | Agar pengerjaan dan pengujian punya patokan yang jelas. |

Pendekatan "hanya prompt" tidak efektif untuk bagian visual dan berisiko menyimpang dari desain yang sudah disetujui. Yang efektif adalah dokumen ini sebagai spesifikasi dan daftar periksa, ditambah prompt untuk bagian integrasi dan pengujian.

---

## 2. Kondisi branch dan prasyarat (penting)

Terukur pada 2026-11-24:

| Branch | Posisi | Keterangan |
|---|---|---|
| `origin/aldy_merge` | sumber tema | berisi seluruh fitur tema final |
| `origin/eco_new` | go live | maju 142 commit dari `aldy_eco` |
| `origin/aldy_eco` | `78fe3555` (2026-10-01) | **tertinggal 142 commit dari eco_new**, hanya punya satu commit sendiri (merge PR #367) |

Konsekuensi: kalau tema dipasang di `aldy_eco` lama lalu di-merge ke `eco_new`, diff PR akan memuat 142 commit lain dan berisiko konflik di banyak berkas. Karena itu **langkah 0 wajib: sinkronkan `aldy_eco` dengan `eco_new` dulu.** Uji coba kering menunjukkan merge ini bersih tanpa konflik (`git merge-tree --write-tree origin/aldy_eco origin/eco_new` menghasilkan pohon tanpa konflik).

`eco_new` sudah punya versi awal tema: `SeasonalThemes.php` (hanya `natal`), `GlobalSeasonalTheme.php`, `SeasonalThemeResolver.php`, `SeasonalThemeSettingsController.php`, halaman admin, `partials/seasonal-theme.blade.php`, menu Control Center > Seasonal Theme (migrasi dan `MenuSeeder` sudah ada, **jangan disentuh**). Whitelist tema pengguna masih literal `['default','none','natal']` dan dashboard memakai fallback `'seasonal_theme' => 'none'` (bug: pengguna yang belum pernah menyimpan preferensi tidak ikut tema admin).

Yang belum ada di eco_new: composer, `lang/en/ui.php` dan `lang/id/ui.php`, semua adegan 3D, three.js, poster, SVG login, loading screen, pratinjau, aksen, hiasan navbar, tema login admin.

Jangan merge seluruh `aldy_merge` ke mana pun: selisihnya 369 commit dan membawa puluhan fitur lain.

---

## 3. Inti tema: cara kerja dan aturan

Satu katalog tunggal `App\Support\SeasonalThemes` menentukan semuanya. Kunci: `none`, `natal`, `tahun_baru`, `ramadan`, `lebaran`, `imlek`, `hut_ri`. Tiap tema membawa `label_key`, `icon`, `accent`, `palette`, `decoration`, `ornaments`, `scene`, `loading`, `window`.

```
Pengaturan global admin (AppConfig JSON 'seasonal_theme.global')
  theme, sound, volume, mode (manual|auto), schedule[{theme,start,end}], login{theme,scope}
        |
GlobalSeasonalTheme::active()   tema global yang berlaku hari ini
        |
SeasonalThemeResolver
  effectiveFor(pref)   dashboard: 'default' -> global; 'none' -> mati; key lain -> pilihan sendiri
  forAuthPage()        halaman login dan auth (anonim)
  forLoading(pref)     loading screen setelah login
        |
SeasonalThemeComposer  ->  $seasonalTheme {key, meta, soundUrl, soundVolume}
        |
dashboard + auth.*  ->  <html data-season="KEY"> + variabel CSS palet + partial per tema
```

Aturan (dibuktikan tes `ThemeConceptTest`, 13 test, dan `LoginSeasonalThemeTest`):
1. Admin mengatur tema global (Control Center > Seasonal Theme) dan tema dirinya sendiri (Settings), sama seperti pengguna lain.
2. Pengguna lain memilih `Default`, satu tema, atau `Off`.
3. `Default` mengikuti global, termasuk jadwal tanggal. `Off` mematikan tema untuk dirinya meski global aktif. Tema pilihan sendiri menang atas global, bahkan saat global mati.
4. Yang mengubah global adalah pemegang izin menu `control-center.seasonal-theme` (bawaan hanya EC Administrator). Resolver tidak melihat role.
5. Tema login (hanya admin): `theme` = `inherit` (bawaan), `none`, atau satu tema; `scope` = `both` (bawaan), `page` (tampilan halaman login dan auth), `loading` (loading screen setelah login). Bila tema login bukan `inherit` dan cakupannya mencakup loading, loading screen memakai tema itu untuk semua pengguna, termasuk yang memilih `Off` (hanya untuk layar itu). Dashboard dan navbar tetap mengikuti pilihan pengguna.
6. Jadwal otomatis: mode `auto`, hingga 20 entri `{theme,start,end}` format `Y-m-d`, akhir minimal sama dengan awal, tanggal tumpang tindih dimenangkan entri pertama. Saran jadwal bawaan di katalog: Natal 12-01 sampai 12-26, Tahun Baru 12-27 sampai 01-03, HUT RI 08-10 sampai 08-20; Ramadan, Lebaran, Imlek tanggalnya diisi admin tiap tahun.

Data lama tanpa `login` berperilaku identik dengan sebelumnya. Tidak ada migrasi dan tidak ada query tambahan (memo per request dan cache AppConfig 5 menit; halaman login hanya membaca satu baris `seasonal_theme.global`).

---

## 4. Desain tiap tema

### 4.1 Prinsip satu identitas

Setiap tema punya **satu keluarga warna dominan**, dipakai sama di tiga permukaan: halaman login, loading screen, dan antarmuka aplikasi (warna primer, tombol, link, header). Warna kedua hanya muncul sebagai ornamen atau sorotan kecil. Tes `PaletteCoherenceTest` menjaganya (hue primer dalam 25 derajat dari warna login, kontras teks putih di atas primer paling sedikit 4,5:1, primer berbeda dari merah bahaya).

Merah bahaya aplikasi (tombol hapus dan reject, `#dc2626`) **tidak boleh berubah** di tema mana pun. Perlindungannya lewat selector di `seasonal-ui-remap` (`data-danger`, handler `onclick` delete/reject/revoke, kelas red-500/600), bukan lewat warna.

### 4.2 Palet final (dihasilkan langsung dari katalog)

| Tema | primary_rgb | primary_dark_rgb | accent | login_from | login_mid | login_to | button | loading_bottom | Identitas |
|---|---|---|---|---|---|---|---|---|---|
| `natal` | 20 69 47 | 11 42 30 | #d4af37 | #0b2a1e | #14452f | #1f6b43 | #1b6a40 | #14452f | hijau pinus + emas |
| `tahun_baru` | 34 34 44 | 10 10 15 | #f3dc8e | #07070b | #16161f | #2b2b36 | #2b2b36 | #2b2b36 | arang hampir hitam + emas sampanye |
| `ramadan` | 30 64 124 | 15 32 70 | #f5c542 | #050a1c | #0f2046 | #1e3a8a | #1e3a8a | #1e3a8a | biru tua + emas |
| `lebaran` | 27 94 32 | 14 61 20 | #d4e157 | #06240d | #0e3d14 | #2e7d32 | #2e7d32 | #2e7d32 | hijau ketupat + hijau janur |
| `imlek` | 168 52 22 | 122 31 16 | #f5c542 | #3a0a05 | #7a1f10 | #a83416 | #a83416 | #7a1f10 | merah lak vermilion + emas |
| `hut_ri` | 168 12 36 | 125 10 30 | #ffffff | #3d0610 | #7d0a1e | #a80c24 | #a80c24 | #7d0a1e | merah bendera + putih |

Natal juga punya varian gelap `dark` (`accent #e0bc4a`, `login_to #2a7f52`) yang belum dipakai. Kunci opsional `button_text`, `button_hover`, dan `link` didukung oleh `seasonal-theme-vars` tetapi tidak dipakai tema mana pun saat ini.

Tentang sumber warna (jujur): tidak ada sumber resmi yang memberi heks untuk bendera Indonesia (UU 24/2009 hanya menyebut merah dan putih berbanding 2:3), merah Tionghoa, atau palet Natal dan Ramadan. Nilai di atas adalah keputusan desain dengan kontras yang dihitung (WCAG 2.1). Merah bendera di adegan HUT RI (`#E70011`) adalah konversi sRGB dari Pantone 186 C oleh pihak ketiga, bukan nilai resmi.

Kontras terukur: item menu aktif sidebar 5,1 sampai 8,8; teks panel login terburuk 5,0 sampai 7,1; teks tombol utama 6,5 sampai 15,7.

Variabel CSS: `partials/seasonal-theme-vars` mencetak untuk tema aktif `--st-primary-rgb`, `--st-primary-dark-rgb`, `--st-accent`, `--st-login-from/mid/to`, `--st-button`, `--st-button-text`, `--st-button-hover`, `--st-link`, `--st-loading-bottom`, dan menimpa `--primary-rgb`, `--primary-dark-rgb`, `--primary-color`, `--primary-surface` di bawah `:root[data-season="KEY"]` (spesifisitas lebih tinggi dari `:root`). Tanpa `data-season`, tidak ada yang berubah.

### 4.3 Loading screen setelah login (adegan 3D)

Overlay layar penuh sampai tiga detik setelah login berhasil (adegan berjalan 2600 ms, batas mutlak 3600 ms). Latar gradien 180 derajat dari `login_from` ke `loading_bottom`, kanvas WebGL di atasnya, judul dan subjudul di pita bawah dengan tabir gelap. Pengalihan selalu dijadwalkan lebih dulu oleh satu timer sehingga kegagalan apa pun tidak menahan login. Tanpa WebGL, Save-Data, 2g/3g, atau reduced motion: gambar diam dan teks.

Kontrak adegan: modul ES `create(THREE, ctx) -> {update(t, dt), dispose()}`, tanpa bundler. `stage.js`: `supportsWebGL()`, `preload(scene)`, `play(canvas, {scene, palette, duration})`. Tier `low` bila `deviceMemory <= 4`, `hardwareConcurrency <= 4`, atau Save-Data. Bila 10 frame pertama rata-rata di atas 40 ms, pixel ratio turun ke 1, dan bila sudah 1, skala render turun ke 0,75. Pita bawah (teks) selalu dibiarkan kosong.

| Tema | Adegan | Isi dan gerak | Terukur (RTX 5060, 720p) |
|---|---|---|---|
| natal | `sleigh` | Malam hijau pinus-teal, aurora zamrud, bulan besar berhalo, desa bersalju dengan gereja dan jendela emas, cerobong berasap, pinus bersalju. Kereta Santa dengan empat rusa (dua pasang, hanya rusa pemimpin berhidung merah menyala) melintas dan melewati bulan, jejak cahaya emas, salju turun berlapis dekat dan jauh. | 55 draw call, 101 ribu segitiga, median 1,4 ms |
| tahun_baru | `fireworks` | Kota malam berjendela, pantulan di air, menara dengan bola hitung mundur turun sepanjang antena, kembang api (peony, krisan, willow, palem, cincin, hati, crossette) lewat sistem percikan GPU satu draw call dengan ekor dan kilau, kerumunan siluet empat pose (tangan terangkat, melambai, ponsel, berdiri), konfeti emas. **Tanpa citra minuman.** | 33 draw call, 58 ribu segitiga, median 0,7 ms |
| ramadan | `crescent` | Masjid dengan kubah tengah berusuk, setengah kubah, empat menara berbalkon berujung runcing, jendela lengkung bercahaya, pola geometris lewat tekstur, kolam pantulan, bulan sabit tipis dan bintang, palem, lentera fanous berayun dengan lampu hias. | 41 draw call, 65 ribu segitiga, median 0,7 ms |
| lebaran | `eid` | Senja hijau-teal di halaman rumah berteras kayu: sembilan tokoh berproporsi natural (baju koko dan peci, kebaya dan hijab, batik atau sarung) dengan salaman, pelukan, nenek dan anak, ayah melambai, wanita di meja opor dan ketupat; napas dan gerak halus. Ketupat anyaman dan janur bergantung, masjid berkubah hijau, kembang api jauh; lampu dan jendela amber sebagai aksen hangat. Semua tokoh satu mesh berkulit. | 42 draw call, 50 ribu segitiga, median 0,9 ms |
| imlek | `lunar` | Naga Tiongkok panjang berkelok (kepala sekitar 5,8 unit pada tubuh sekitar 52 unit), sisik merah bertepi emas, perut pelat krem-emas, punggung berduri kecil emas. Kepala ringkas: alis berat, mata kecil dalam, hidung besar bercuping, bibir atas tebal, taring, tanduk rusa tipis bercabang, kumis pita pipih, janggut, surai api, mutiara bercahaya di depan mulut. Empat kaki ramping, telapak berjari lima bercakar melengkung (20 cakar teriinstan). Latar lampion merah, kembang api emas, pagoda, bulan. Acuan anatomi: lukisan Nine Dragons (Chen Rong) dan relief Dinding Sembilan Naga Beihai. Gaya, bukan fotorealistis. | 39 draw call, 58 ribu segitiga, median 1,1 ms |
| hut_ri | `flag` | Upacara bendera saat fajar dilihat dari belakang barisan: Merah Putih berbanding 2:3 pada kain yang berkibar dengan gelombang berjalan di GPU, lipatan, tembus cahaya dari belakang, jahitan dan mata ayam; puluhan tokoh (paskibra, pramuka, TNI berbaret marun, veteran, sipil, anak SD; sekitar 70 pada tier tinggi, 55 pada rendah) memberi hormat dengan tangan kanan ke pelipis; bambu runcing bersilang berpita merah putih, umbul-umbul, rumbai, patung pahlawan samar di kabut. Langit fajar merah tua ke oranye dengan cahaya putih-emas. | 40 draw call, 91 ribu segitiga, median 0,5 ms |

Poster referensi visual: `public/images/seasonal/posters/<key>.webp` (640x360, 16 sampai 36 KB) dan thumbnail `posters/thumb/<key>.webp` (160x90, 2 sampai 5 KB). Pakai sebagai acuan akhir apakah hasil port sama.

Aturan implementasi adegan: tanpa alokasi per frame, `dispose` lengkap, tanpa efek samping saat import, tier `low` mengurangi segmen dan instansi.

### 4.4 Halaman login (panel kiri)

Ilustrasi vektor asli (SVG statis) per tema di `public/images/seasonal/login/<key>.svg`, dimuat lewat CSS `background-image` hanya untuk tema aktif dan hanya di lebar 768 px ke atas (di bawah itu panel disembunyikan sehingga tidak ada permintaan gambar). Kotak gambar rasio tetap 5:4, tinggi 76 persen panel, absolut di kanan bawah (CLS 0), dengan tabir gelap di kiri dan mask pudar agar teks merek, judul, daftar fitur, dan tautan tetap terbaca. Form login di panel kanan **tidak pernah disentuh**. URL memakai `?v=filemtime`.

| Tema | Ilustrasi |
|---|---|
| natal | Pohon Natal berhias lampu, bola, bintang bercahaya, kado, kabin berjendela, pinus, bulan, salju turun |
| tahun_baru | Siluet kota malam dengan letusan kembang api, **menara jam bercahaya pukul 12.00**, bintang percikan, konfeti emas |
| ramadan | Masjid berkubah dan menara, bulan sabit dan bintang, lentera fanous, pantulan lembut |
| lebaran | Gugusan ketupat dengan janur, masjid dan bedug, lentera hangat, kembang api kecil |
| imlek | Naga Tiongkok melingkar dengan kepala bertanduk dan kumis, lampion merah, bunga plum, emas batangan, awan |
| hut_ri | Bendera Merah Putih berkibar di tiang, siluet Monas, bambu runcing bersilang, rumbai merah putih, fajar hangat |

Gerak ambien: paling banyak 10 elemen `<i>` dengan animasi `transform` dan `opacity` saja (salju natal, bintang berkedip, cahaya lentera), hanya di bawah `prefers-reduced-motion: no-preference`. Ornamen polos lama (orb, ring, pill) disembunyikan saat tema aktif dan tampil seperti biasa tanpa tema. Tombol Sign In memakai `--btn-bg`, `--btn-fg`, `--btn-bg-h` dari palet `button`.

Teks di atas panel: kontras terburuk 5,0 sampai 7,1 (natal 5,02; tahun_baru 5,78; ramadan 7,09; lebaran 5,43; imlek 5,65; hut_ri 5,01), dicapai dengan tabir dan opasitas teks yang dinaikkan hanya saat tema aktif.

### 4.5 Hiasan navbar

Dua potongan SVG kecil (`viewBox 190x40`) di pojok kiri dan kanan header, dihasilkan `SeasonalNavbarArt` dan dicetak inline (tanpa berkas gambar, tanpa JavaScript). Bukan rumbai selebar layar. Garis tepi header (y=28 pada viewBox) membagi dua: di dalam header hanya di pojok luar (x < 56 kiri, x > 134 kanan, di balik tombol menu dan avatar) sehingga judul dan subjudul tidak tertutup; sepanjang tepi hanya bandul mungil jarang yang menjuntai maksimal 12 unit.

CSS: `.seasonal-navbar { position:absolute; left:0; right:0; bottom:-14px; height:48px; z-index:-1; pointer-events:none; contain: layout paint size }`, potongan 228 px lebar. Di bawah 640 px tinggi 36 px dan hanya potongan kiri; di bawah 400 px tinggi 30 px. Disembunyikan saat print dan `prefers-contrast: more`. Mode gelap: `filter: brightness(1.22) saturate(1.05)` (Lebaran `brightness(1.05) saturate(.85)`; tepi beku Natal opasitas .3).

Tiap tema juga punya garis tepi 2 px, lapisan pojok ketiga (`viewBox 70x40`), dan khusus natal tepi beku halus tidak beraturan setinggi maksimal 6 px.

| Tema | Garis tepi | Motif pojok dan bandul |
|---|---|---|
| natal | hijau pinus, tengah emas | Kiri: ranting pinus melengkung ke atas dengan beri merah kecil dan pita; bandul bola, bintang, es, bola emas. Kanan: holly dengan lonceng emas, tongkat permen emas. Kristal mint-beku. |
| tahun_baru | arang, tengah emas sampanye | Kiri: pita emas dan perak melingkar, kilau; bandul bintang dan pita bergelombang. Kanan: tiga letusan kembang api emas, sampanye, perak. |
| ramadan | biru tua, tengah emas, bintang kecil | Kiri: bulan sabit berpendar dan bintang; bandul lentera dan bintang. Kanan: siluet kubah dan menara; bandul bintang dan bulan kecil. |
| lebaran | hijau tua, limau dan emas | Kipas janur melengkung di kedua pojok; bandul ketupat dan kilau; bulan sabit kecil. |
| imlek | vermilion, blok emas di tengah | Kiri: lampion besar menggantung dan ranting plum; bandul koin dan tassel. Kanan: ranting plum dan bintang. Kelopak krem-emas. |
| hut_ri | merah bendera | Kiri: dua bendera bersilang; bandul rumbai pendek dan bintang. Kanan: dua bendera kecil dan rumbai. |

Gerak (kelas `sn-*`): `sn-sway` ayun, `sn-twinkle` kedip, `sn-pop` letup, `sn-wave` kibar, `sn-breathe` berpendar, `sn-drift` dan `sn-fall` untuk lapisan ketiga. Semuanya `transform` dan `opacity`, tiap elemen punya durasi, amplitudo, dan jeda sendiri, maksimal 8 elemen bergerak per potongan, hanya bila `Show animations` aktif dan perangkat tidak reduced motion. Anggaran: maksimal 14 KB per potongan, tanpa atribut `id`.

### 4.6 Aksen dan dekorasi dashboard

`partials/seasonal-accents`: CSS statis, hanya untuk tema aktif (1,2 sampai 4,1 KB), seluruhnya di bawah `html[data-season="KEY"]`. Selector daftar putih: `#sidebar`, `#sidebarHeader`, `#sidebar a.nav-link.active`, `#mainContent > header`, dan kartu `> div.rounded-xl` pada `section[aria-label="Company overview" | "Tickets" | "Recent activity"]`. Selector yang tidak ada di halaman target tidak berefek dan tidak merusak.

Per tema: natal garis mint-beku di tepi kartu dan header, kristal di pojok kartu, bintik salju statis (lapisan `body::before` tetap, opasitas .45, `contain: strict`), tutup salju tipis di menu aktif; tahun_baru garis kilau emas dan kilau saat hover; ramadan arabesk samar 3 sampai 6 persen dan garis emas; lebaran anyaman ketupat samar dan garis limau; imlek siku emas di pojok kartu; hut_ri garis merah-putih 3 px di atas header.

Larangan mutlak: tidak dipakai sebagai warna teks; tidak menyentuh tabel data, form, modal, grafik, warna status atau bahaya, cetakan, atau ekspor; pseudo-element absolut tanpa mengubah tinggi (CLS 0); **tanpa `backdrop-filter`, tanpa animasi `box-shadow`, tanpa `@keyframes`** di berkas aksen; gerak apa pun hanya `transform`/`opacity` dan hanya bila `Show animations` aktif dan bukan reduced motion; z-index di bawah dropdown, modal, dan toast.

`partials/seasonal-theme` (dekorasi lama, disesuaikan): placement `sidebar` hanya menampilkan maksimal 3 ornamen kecil 15 sampai 18 px di ujung kanan footer sidebar (`bottom:17px`), tidak pernah di atas menu atau ikon; di mode rail disembunyikan.

`seasonal-ui-remap`: memetakan utilitas warna brand (bg-red-700/800/900 dan sejenisnya) ke palet di bawah `html[data-season]`; dimuat hanya saat tema aktif atau di halaman Settings (pratinjau).

### 4.7 Pratinjau loading screen (Settings dan Control Center)

Thumbnail poster statis di tiap kartu tema (lazy, lebar dan tinggi ditetapkan). Tombol "Play preview" opsional: three.js dan modul adegan baru diunduh setelah klik (`import()` dinamis di dalam `open()`; tanpa impor statis, tanpa preload). Dialog `role=dialog`, `aria-modal`, fokus ke Close, Tab terkunci, Esc atau klik atau Close menutup, tutup otomatis sekitar 6 detik, satu pratinjau pada satu waktu, adegan dibuang dan RAF dihentikan saat ditutup atau tab disembunyikan. Save-Data, 2g/3g, reduced motion, atau tanpa WebGL: poster diam dengan catatan. Import map pratinjau memakai `?v=mtime` dan **harus dicetak dengan `@json(...)`**, bukan `Js::from` (hasil `Js::from` adalah `JSON.parse('...')` yang bukan JSON valid untuk import map).

### 4.8 Musik latar

Hanya admin yang memasang musik (unggah wav, mp3, ogg, aac maksimal 3 MB, ekstensi diperiksa) dan volumenya; berlaku untuk semua pengguna; diputar hanya bila tema aktif dan ada berkas.

---

## 5. Anggaran dan uji penerimaan

| Aspek | Batas |
|---|---|
| Query tambahan (login, dashboard, Settings, admin) | 0 (login hanya membaca satu baris `seasonal_theme.global` yang sudah ada) |
| CLS | 0 (toleransi 0,01) |
| Tema `none` | 0 byte tambahan dari partial tema; tidak ada permintaan gambar atau JS tema |
| three.js | tidak diunduh di Settings, admin, atau dashboard sampai Play ditekan; di login hanya pemanasan idle bila tema loading aktif (dilewati Save-Data dan 2g/3g) |
| Hiasan navbar | maksimal 14 KB per potongan, 8 elemen bergerak per potongan |
| Aksen dashboard | maksimal sekitar 4,2 KB CSS per tema; nol layout, nol style recalc, nol paint saat idle |
| Adegan 3D (GPU nyata) | median di bawah 2,5 ms sampai 4K; draw call maksimal 55; tanpa kebocoran buffer, program, VAO, shader dalam 20 putaran |
| Heap JS | tumbuh kurang dari 1,5 MB dalam 20 putaran |
| SVG login | maksimal 45 KB mentah (4 sampai 10 KB gzip) |
| Kontras | teks menu aktif dan teks panel login minimal 4,5:1 |
| Tombol bahaya | tetap `rgb(220,38,38)` (hapus) dan `rgb(185,28,28)` (reject) di keenam tema |
| Aksesibilitas | dialog pratinjau bisa ditutup dengan keyboard; reduced motion menampilkan gambar diam; `prefers-contrast: more` menyembunyikan ornamen |

Keterbatasan yang diketahui (bukan bug baru): adegan lebih lambat dari tolok ukur lama di renderer perangkat lunak (SwiftShader) dan belum diukur di ponsel; Natal belum punya tier `low` sendiri; naga bergaya, bukan fotorealistis; Imlek dan HUT RI memakai primer merah yang dekat dengan merah bahaya (dibedakan oleh nilai yang lebih gelap dan oleh perlindungan selector); kompresi dan header cache produksi belum diukur.

---

## 6. Kait di berkas bersama (jangan timpa berkas ini)

**Prinsip:** tidak ada layout yang direfaktor. Tiap kait hanyalah menambah beberapa baris. Cari tempatnya di eco_new berdasarkan teks (bukan nomor baris), lalu cetak baris referensi persis dari sumber dengan perintah di kolom terakhir. **Jangan mengetik ulang dari ingatan atau dari dokumen ini.**

Atur dulu `SRC=origin/aldy_merge`. Perintah umum: `git show $SRC:<berkas> | grep -n -i "seasonal\|data-season\|login-art"`.

| Berkas di eco_new | Yang ditambahkan | Cetak referensi |
|---|---|---|
| `resources/views/dashboard.blade.php` (besar, **jangan ditimpa**) | (a) `data-season` pada tag `<html>`; (b) fallback preferensi `'seasonal_theme' => 'default'` (bukan `'none'`); (c) `$seasonalTheme` dan `$effectiveSeasonalTheme` dari composer; (d) `@include('partials.seasonal-theme-vars')` di `<head>`; (e) di akhir `<head>`: include `seasonal-ui-remap` dan `seasonal-accents` hanya saat tema aktif; (f) awal `<body>`: sprite `seasonal-icons`; (g) di dalam `<header>`: include `seasonal-navbar`; (h) di dalam `<aside id="sidebar">`: include `seasonal-theme` dengan `placement => 'sidebar'` (menggantikan dua pemanggilan `seasonal-theme` lama); (i) CSS `html[data-season] #sidebar...` untuk gradien sidebar dan `--sb-accent` | `git show $SRC:resources/views/dashboard.blade.php \| grep -n -i "seasonal\|data-season"` dan `git show $SRC:resources/views/partials/sidebar.blade.php \| grep -n -i "seasonal\|data-season"` (di aldy_merge sidebar sudah dipisah jadi partial; di eco_new sidebar masih inline di dashboard, jadi item (h) dan (i) dipasang di `dashboard.blade.php`) |
| `resources/views/auth/login.blade.php` | `data-season`, include `seasonal-theme-vars`, blok CSS `:root[data-season]` yang menimpa variabel merek dengan fallback ke nilai lama (termasuk `--btn-bg`, `--btn-fg`, `--btn-bg-h`), include `seasonal-loading` dengan `warmKey`, `window.__seasonalGlobalKey`, include `login-art` di panel kiri, pembungkus `.dp-ambient`, pemanggilan `SeasonalLoading.start` dari respons login | `git show $SRC:resources/views/auth/login.blade.php \| grep -n -i "seasonal\|data-season\|login-art\|dp-ambient\|btn-bg"` |
| `resources/views/auth/forgot-password.blade.php`, `change-password.blade.php`, `check-email.blade.php`, `two-factor-challenge.blade.php` | masing-masing hanya dua baris: `data-season` pada `<html>` dan `@include('partials.seasonal-theme-vars')` di `<head>` | `git show $SRC:resources/views/auth/forgot-password.blade.php \| grep -n -i "seasonal"` (sama untuk ketiga lainnya) |
| `resources/views/settings/index.blade.php` | hanya blok pemilih tema: kartu katalog (`partials.seasonal-icon`, `partials.seasonal-poster`), `Default` dan `Off`, tautan Manage untuk pemegang izin menu, `@include('partials.seasonal-preview')`, sprite `seasonal-icons` (`@once`), CSS `.st-card` (tanpa mengubah `font-weight` saat dipilih) | `git show $SRC:resources/views/settings/index.blade.php \| grep -n -i "seasonal\|st-card"` |
| `app/Http/Controllers/AuthController.php` | respons JSON login: `'seasonal_theme' => self::effectiveSeasonalTheme($authUser)` dan method privat statis yang membaca preferensi (string JSON atau array), default `default`, memanggil `SeasonalThemeResolver::forLoading`, fallback `'none'` | `git show $SRC:app/Http/Controllers/AuthController.php \| grep -n -i -B2 -A14 "effectiveSeasonalTheme"` |
| `app/Http/Controllers/SettingsController.php` | bawaan `'seasonal_theme' => 'default'`; hapus literal `['default','none','natal']`, bangun whitelist dari `SeasonalThemes::userChoices()` di `allowedValues()` | `git show $SRC:app/Http/Controllers/SettingsController.php \| grep -n -i -B2 -A3 "seasonal"` |
| `app/Http/Middleware/CheckAuthToken.php` | saat preferensi kosong, isi bawaan termasuk `seasonal_theme = 'default'` | `git show $SRC:app/Http/Middleware/CheckAuthToken.php \| grep -n -i -B4 -A6 "seasonal"` |
| `app/Providers/AppServiceProvider.php` | satu baris: `View::composer(['dashboard', 'auth.*'], \App\View\Composers\SeasonalThemeComposer::class);` | `git show $SRC:app/Providers/AppServiceProvider.php \| grep -n -i -B1 -A1 "SeasonalThemeComposer"` |
| `routes/web.php` | tiga rute `seasonal-theme` (GET, POST, `upload-sound`) bermiddleware `menu:control-center.seasonal-theme`; **sudah ada di eco_new**, cukup pastikan | `git show $SRC:routes/web.php \| grep -n "seasonal-theme"` |
| `lang/en/ui.php`, `lang/id/ui.php` | belum ada di eco_new: buat minimal berisi hanya grup `seasonal` (87 kunci per bahasa); bila sudah ada saat porting, tambahkan grupnya ke dalamnya | skrip `extract-seasonal-lang.php` (bagian 7) |
| Panduan `resources/guide/{en,id}/020-navigating-ecosystem.md`, `070-whats-new.md` (dan `030-workflow-employee.md`) | hanya kalimat yang menyebut tema, bila topiknya ada di eco_new; topik `021` dan `148` sudah masuk manifest | `git show $SRC:resources/guide/en/020-navigating-ecosystem.md \| grep -n -i "seasonal"` |

Yang **tidak** boleh disalin dari aldy_merge: `resources/views/partials/sidebar.blade.php` (sidebar di eco_new tetap inline), `dashboard.blade.php`, `login.blade.php`, `settings/index.blade.php`, `routes/web.php`, `lang/*/ui.php` utuh, dan migrasi apa pun. Kait di dashboard memakai selector yang ada di eco_new (`#sidebar`, `a.nav-link.active`, `#mainContent`, `<header class="sticky ...">`); `#sidebarHeader`, footer sidebar, dan mode rail belum ada di eco_new, jadi sebagian aksen sidebar tidak berefek sampai elemen itu ada (aman).

---

## 7. Langkah kerja

### 7.0 Sinkronkan aldy_eco dengan eco_new (wajib, sekali)

```bash
git fetch origin
git checkout aldy_eco                  # membuat cabang lokal dari origin/aldy_eco
git merge origin/eco_new               # aldy_eco tertinggal 142 commit; uji kering bersih tanpa konflik
# bila ada konflik tak terduga, pilih sisi eco_new untuk berkas non tema, jangan lanjut sebelum bersih
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit   # baseline hijau SEBELUM tema masuk
git push origin aldy_eco
```

Disarankan mengerjakan tema di cabang kerja dari `aldy_eco` (mis. `aldy_eco-seasonal`) lalu merge ke `aldy_eco`, agar mudah dibatalkan. Itu pilihan Anda; langkah di bawah berlaku sama.

### 7.1 Pasang tema

```bash
SRC=origin/aldy_merge

# 1. Ambil folder dokumen ini lebih dulu (manifest, blob-ids, skrip)
git checkout $SRC -- docs/seasonal-theme-port

# 2. Salin berkas tema BARU (77) dan TIMPA berkas tema yang sudah ada (6), apa adanya
git checkout $SRC -- $(grep -v '^#' docs/seasonal-theme-port/manifest-new-files.txt)
git checkout $SRC -- $(grep -v '^#' docs/seasonal-theme-port/manifest-replace-files.txt)

# 3. Bukti kesamaan: semua berkas harus identik dengan sumber
bash docs/seasonal-theme-port/verify-port.sh        # harus mencetak: OK, 83 berkas identik dengan sumber

# 4. Bahasa (hanya grup seasonal)
git show $SRC:lang/en/ui.php > /tmp/ui_en.php
git show $SRC:lang/id/ui.php > /tmp/ui_id.php
php docs/seasonal-theme-port/extract-seasonal-lang.php /tmp/ui_en.php lang/en/ui.php
php docs/seasonal-theme-port/extract-seasonal-lang.php /tmp/ui_id.php lang/id/ui.php

# 5. Pasang kait (bagian 6), satu berkas satu commit kecil
# 6. Uji
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Seasonal tests/Feature/Seasonal tests/Unit/Guide
php docs/HRGA/tools/guide-check.php
php artisan view:cache && php artisan view:clear
```

`verify-port.sh` membandingkan `git hash-object <berkas>` dengan ID blob di `blob-ids.txt`. ID blob tidak bergantung pada branch maupun akhir baris (autocrlf), sehingga bila lolos, berkas dijamin sama persis dengan tema di aldy_merge. Jalankan juga di branch sumber untuk memastikan dokumen belum usang; jika ada berkas tema diubah setelah 2026-11-24, hasilkan ulang `blob-ids.txt` dengan `bash docs/seasonal-theme-port/verify-port.sh --regenerate` di branch sumber.

Urutan commit yang disarankan (tiap commit lolos tes): (1) salinan berkas tema, (2) bahasa, (3) kait backend (AuthController, SettingsController, CheckAuthToken, AppServiceProvider), (4) kait dashboard, (5) kait login dan halaman auth, (6) kait Settings dan pratinjau, (7) panduan.

Hasil uji coba kering (2026-11-24, worktree terpisah dari `origin/eco_new`, tanpa menyentuh branch mana pun): langkah 1 sampai 4 menambah 81 berkas (77 tema + folder dokumen) dan mengubah 6, lang minimal terbentuk dengan 87 kunci EN dan 87 kunci ID, semua PHP lolos `php -l`, semua JS lolos `node --check`, dan semua kelas yang dipanggil tema (`AppConfig::getJson` dan `setJson`, `AuditLog::recordAction`, `AiModelSettings`, `SettingsController`, `AuthController`) sudah ada di eco_new. Yang belum diuji: kait di berkas bersama dan tes PHPUnit setelah kait, karena itu bagian pekerjaan port.

---

## 8. Prompt siap salin (Claude Code, di branch kerja aldy_eco)

Gunakan berurutan. Setiap prompt berdiri sendiri. Sumber tema: `origin/aldy_merge`; dokumen: `docs/seasonal-theme-port/PORT-KE-ECO_NEW.md`.

### Prompt 0: pemeriksaan awal (tanpa mengubah berkas)

```
Kita memasang fitur Seasonal Theme di branch aldy_eco, lalu aldy_eco akan di-merge ke eco_new. Baca docs/seasonal-theme-port/PORT-KE-ECO_NEW.md dari origin/aldy_merge (git show origin/aldy_merge:docs/seasonal-theme-port/PORT-KE-ECO_NEW.md) bagian 2, 3, 6, dan 7. Jangan mengubah berkas. Periksa dan laporkan dengan bukti (path dan potongan kode, jangan menebak): (1) apakah aldy_eco sudah disinkronkan dengan origin/eco_new (git log origin/eco_new..aldy_eco harus kecil dan git log aldy_eco..origin/eco_new harus kosong); (2) apakah eco_new/aldy_eco masih sama dengan keadaan di dokumen (SeasonalThemes.php hanya natal, tidak ada SeasonalThemeComposer, tidak ada lang/en/ui.php, sidebar inline di dashboard.blade.php); (3) tiap kait di tabel bagian 6: apakah tempat sambungnya masih ada (cari berdasarkan teks) dan apakah ada perubahan di eco_new yang menyentuhnya; (4) selector aksen yang ada (#sidebar, #sidebarHeader, a.nav-link.active, #mainContent > header, section[aria-label="Company overview"|"Tickets"|"Recent activity"]). Ringkas risiko konflik.
```

### Prompt 1: salin berkas tema dan backend

```
Di branch kerja aldy_eco: jalankan langkah 1 sampai 4 pada bagian 7.1 docs/seasonal-theme-port/PORT-KE-ECO_NEW.md (ambil folder dokumen, salin 77 berkas baru dan timpa 6 berkas tema dari origin/aldy_merge, jalankan verify-port.sh sampai OK, buat lang minimal dengan extract-seasonal-lang.php). Lalu pasang kait backend dari tabel bagian 6 (AppServiceProvider mendaftarkan SeasonalThemeComposer untuk ['dashboard','auth.*']; AuthController mengirim seasonal_theme lewat SeasonalThemeResolver::forLoading; SettingsController membangun whitelist dari SeasonalThemes::userChoices() dengan bawaan 'default'; CheckAuthToken mengisi bawaan preferensi; ketiga rute seasonal-theme bermiddleware menu:control-center.seasonal-theme). Cetak kode referensi dari origin/aldy_merge dengan perintah di kolom terakhir tabel, jangan mengarang. Jangan menyentuh migrasi apa pun dan jangan membuat migrasi baru. Jalankan php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Seasonal tests/Feature/Seasonal dan laporkan hasil persis. Dua commit: salinan, dan kait backend. Tanpa em dash atau en dash.
```

### Prompt 2: kait dashboard (hanya sambungan tema)

```
Pasang kait tema di resources/views/dashboard.blade.php sesuai tabel bagian 6 (item a sampai i). Berkas ini JANGAN ditimpa dari aldy_merge dan layout JANGAN direfaktor: hanya tambahkan baris sambungan. Ambil teks sambungan persis dari origin/aldy_merge dengan perintah cetak di tabel; sidebar di eco_new masih inline sehingga include seasonal-theme dan CSS html[data-season] #sidebar dipasang di dashboard.blade.php. Ganti fallback preferensi 'none' menjadi 'default'. Pastikan dengan tema none halaman identik dengan sebelumnya (tidak ada byte tambahan dari partial tema). Tampilkan git diff minimal dan jelaskan tiap hunk. Verifikasi php artisan view:cache lalu view:clear.
```

### Prompt 3: kait login dan halaman auth

```
Pasang kait tema di resources/views/auth/login.blade.php (kait lengkap di tabel bagian 6) dan dua baris kait di forgot-password, change-password, check-email, two-factor-challenge. Jangan menimpa berkas dari aldy_merge. Pertahankan semua id, handler, validasi, toggle password, Enter, dan penjaga kirim ganda yang ada di eco_new. Tema none harus identik. Verifikasi: render halaman login untuk keenam tema (render Blade dengan memo tema di memori, tanpa database) dan lihat tangkapan layar di 1920x1080, 1280x720, 1024x768, 390x844: form tidak tertutup, kontras teks panel minimal 4,5:1, tidak ada overflow horizontal, tidak ada permintaan gambar di bawah 768 px.
```

### Prompt 4: Settings dan pratinjau

```
Pasang kait pemilih tema di resources/views/settings/index.blade.php (hanya blok pemilih tema) sesuai tabel bagian 6, dan periksa halaman admin admin/seasonal-theme-settings.blade.php (sudah tertimpa dari manifest): kartu "Login and auth pages" dan pratinjau harus tampil benar. Import map pratinjau harus dicetak dengan @json, bukan Js::from. Verifikasi: tombol Play membuka dialog, Esc dan Close menutup dan fokus kembali ke tombol, three.js tidak diunduh sebelum klik, reduced motion menampilkan poster diam, tidak ada galat konsol.
```

### Prompt 5: panduan pengguna

```
Ikuti aturan di CLAUDE.md untuk panduan pengguna. Pastikan resources/guide/{en,id}/021-my-seasonal-theme.md dan 148-cc-seasonal-theme.md berpasangan, front matter dan requires sesuai tabel menu di eco_new. Perbarui kalimat tema di topik 020, 030, 070 hanya bila topiknya ada. Jalankan php docs/HRGA/tools/guide-check.php (0 galat, tidak menambah peringatan) dan php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Guide. Tanpa em dash atau en dash.
```

### Prompt 6: gerbang QA (tiga agen terpisah, hanya baca)

```
QA PERFORMA dan RENDER: dengan GPU nyata (Edge dengan --force_high_performance_gpu bila ada GPU NVIDIA; konfirmasi renderer lewat WEBGL_debug_renderer_info) dan SwiftShader sebagai pembanding. Untuk keenam adegan: median dan p95 waktu frame di 1280x720 dan 1920x1080, draw call, segitiga, kebocoran GL (buffer, program, VAO, shader) dalam 20 putaran, drift heap, galat konsol. Halaman: CLS, long task, style recalc, jumlah elemen beranimasi, biaya scroll lapisan salju natal, bobot login per tema. Bandingkan dengan tabel anggaran bagian 5. Laporkan PASS atau FAIL per butir dengan bukti; jangan mengubah berkas.
```

```
QA QUERY dan KEAMANAN: ukur jumlah query (baca-saja, jangan menulis ke database kerja) untuk login, forgot-password, dashboard, Settings, admin Seasonal Theme GET dan POST sebelum dan sesudah; semua partial tema harus 0 query. Tinjau XSS (setiap {!! !!}, @json, Js::from, atribut dari data), path traversal pada URL aset, CSRF, otorisasi rute, validasi Rule::in, aturan unggah musik, SVG statis (tanpa script, foreignObject, event handler, referensi eksternal). Jalankan matriks login (cakupan page, loading, both x tema inherit, none, satu tema x preferensi default, none, satu tema) dan konfirmasi resolver tidak memeriksa role. Laporkan PASS atau FAIL dengan bukti.
```

```
QA UI dan FUNGSI: render Blade asli untuk keenam tema (terang dan gelap) di 360, 768, 1280, 1920: ornamen tidak menutupi judul atau tombol (elementFromPoint), tidak ada overflow horizontal, kontras teks, tombol hapus dan reject tetap merah bahaya, dropdown dan modal di atas dekorasi, print dan prefers-contrast: more menyembunyikan ornamen, dialog pratinjau bisa dioperasikan keyboard. Alur: login (semua tema), loading sampai dashboard, Settings (pilih tema, Default, Off, simpan, muat ulang), admin (manual, jadwal, musik, kartu login, validasi, akses ditolak bagi non-admin). Pastikan menu dan fitur lain tidak berubah (tabel, form, modal, ekspor). Laporkan dengan tangkapan layar yang benar-benar dilihat.
```

### Prompt 7: serah terima

```
Buat ringkasan port: daftar commit, berkas yang disalin utuh (dan hasil verify-port.sh), kait yang dipasang (jumlah baris diff per berkas), hasil tes (jumlah persis), hasil QA per gerbang, hal yang tidak bisa diverifikasi, dan langkah deploy. Bandingkan tampilan keenam tema dengan poster referensi di public/images/seasonal/posters. Pastikan git diff --stat origin/eco_new...aldy_eco hanya memuat berkas tema dan kait di bagian 6.
```

---

## 9. Merge aldy_eco ke eco_new, rilis, dan mundur

Karena `aldy_eco` sudah disinkronkan dengan `eco_new` di langkah 7.0, diff PR ke `eco_new` **hanya** boleh berisi berkas tema dan kait. Periksa sebelum membuka PR: `git diff --stat origin/eco_new...aldy_eco` (harus cocok dengan manifest dan tabel kait). Bila ada berkas lain, hentikan dan telusuri.

Rilis aman karena nilai bawaan tidak mengubah tampilan: tema global bawaan `none` dan pengguna bawaan `default`, sehingga setelah deploy tidak ada yang berubah sampai admin memilih tema. Perbaikan fallback `'none'` menjadi `'default'` hanya berpengaruh bila admin mengaktifkan tema.

Urutan: (1) uji di staging, jalankan Prompt 6; (2) deploy dengan tema global `none`, pantau galat; (3) admin mengaktifkan satu tema mode manual untuk pengguna uji, periksa login, loading, dashboard, Settings; (4) aktifkan jadwal otomatis.

Deploy: `php artisan view:clear` dan `php artisan cache:clear` (cache AppConfig 5 menit); pastikan aset statis baru (`public/js/seasonal`, `public/vendor/three`, `public/images/seasonal`, sekitar 1,5 MB) ikut terkirim; periksa kompresi gzip dan header cache untuk `public/vendor/three` dan `public/js/seasonal` (login pertama dengan tema aktif mengunduh sekitar 172 KB gzip untuk three.js saat idle; belum pernah diukur di produksi).

Mundur: matikan tema seketika dari Control Center (tema global `none`, tema login `inherit`) tanpa deploy. Untuk mundur kode, `git revert` commit port (tanpa force push). Data tema ada di satu baris AppConfig; tidak perlu rollback database.

---

## 10. Daftar periksa penerimaan

- [ ] `aldy_eco` sudah memuat seluruh `origin/eco_new` sebelum tema dipasang (`git log aldy_eco..origin/eco_new` kosong).
- [ ] `verify-port.sh` mencetak OK untuk 83 berkas tema.
- [ ] `git diff --stat origin/eco_new...aldy_eco` hanya berisi berkas tema dan kait bagian 6.
- [ ] Tema `none`: dashboard dan login identik dengan sebelumnya, tanpa permintaan jaringan baru.
- [ ] Keenam tema: login, loading, dan dashboard seirama (satu keluarga warna); tidak ada citra minuman di Tahun Baru.
- [ ] Tes tema, panduan, dan seluruh suite hijau; `guide-check` 0 galat.
- [ ] Anggaran bagian 5 terpenuhi di staging dengan GPU nyata.
- [ ] Tangkapan layar tiap tema cocok dengan poster referensi.
- [ ] Rencana mundur dipahami admin.

## 11. Yang tidak ikut dipindahkan

- Fitur lain di aldy_merge (reimbursement, cash advance, dan seterusnya) dan refaktor sidebar menjadi partial.
- Perbaikan label autofill login dan helper `apiFetch` dari tim di `merge_eco-hr` (bukan bagian tema).
- Kunci tema paksa untuk semua pengguna (belum dibuat).
- Penerapan tema ke tabel, form, dan modal (sengaja tidak disentuh).
