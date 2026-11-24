# Seasonal Theme: spesifikasi desain dan panduan port ke eco_new

Dokumen ini adalah satu paket untuk memindahkan fitur Seasonal Theme dari `aldy_merge` ke `eco_new` (branch go live) dengan aman. Isinya: jawaban apakah cara ini efektif, strategi port, kondisi kedua branch yang terukur, spesifikasi desain (warna, animasi, penempatan, aturan), daftar berkas, langkah kerja, prompt siap salin, uji penerimaan, dan rencana mundur.

Sumber kode tema: commit `0479ef06` di `aldy_merge` (juga ada di `merge_eco-hr`); semua berkas tema di commit itu sudah final. Folder dokumen ini sendiri diambil dari `origin/aldy_merge` (ditambahkan setelah `0479ef06`). Dokumen ini dibuat 2026-11-24.

Berkas pendamping di folder yang sama:
- `manifest-new-files.txt`: 77 berkas baru (tidak ada di eco_new), dibuat dari git.
- `manifest-replace-files.txt`: 6 berkas tema yang sudah ada di eco_new dan aman ditimpa penuh.
- `extract-seasonal-lang.php`: mengekstrak hanya grup `seasonal` dari berkas bahasa.

---

## 1. Apakah cara ini bisa dan efektif?

**Bisa, dan efektif, dengan satu syarat penting: jangan meminta AI membuat ulang aset dari deskripsi.**

| Bagian | Cara memindahkan | Alasan |
|---|---|---|
| Adegan 3D (`public/js/seasonal/**`), three.js, SVG login, poster, partial tema, kelas PHP tema, tes | **Salin apa adanya dari git** (`git checkout <ref> -- <daftar berkas>`) | Total sekitar 1,5 MB kode dan aset yang sudah diuji di GPU nyata. Prosa tidak bisa mereproduksi 2000+ baris adegan 3D atau ilustrasi SVG 40 KB dengan hasil yang sama. Menyalin menjamin hasil identik dengan yang sudah divalidasi. |
| Titik sambung di berkas bersama (dashboard, sidebar, login, Settings, AuthController, SettingsController, CheckAuthToken, AppServiceProvider, rute, bahasa) | **Manual terpandu**: spesifikasi di bagian 6 dan prompt di bagian 8 | Berkas ini sangat berbeda antara dua branch (dashboard beda 4800 baris). Salinan utuh akan menghapus fitur lain di eco_new. Yang dipindahkan hanya beberapa baris sambungan. |
| Penjelasan desain, aturan bisnis, uji penerimaan | Dokumen ini | Agar siapa pun (atau AI) yang mengerjakan tahu apa yang benar dan bagaimana membuktikannya. |

Jadi dokumen ini berfungsi sebagai **spesifikasi + panduan integrasi + daftar periksa**, sedangkan aset dan kode tema datang dari git. Pendekatan "hanya prompt" saya nilai **tidak efektif** untuk bagian visual dan berisiko menyimpang dari desain yang sudah Anda setujui.

Estimasi kerja: satu sesi terfokus untuk salin dan integrasi, ditambah pengujian di staging. Risiko ke produksi rendah karena tema bawaan `none` (lihat bagian 9).

---

## 2. Kondisi kedua branch (terukur)

Titik temu `eco_new` dan `aldy_merge`: commit `446ec7af` (2026-11-17). `eco_new` maju 14 commit, `aldy_merge` maju 239 commit, 1564 berkas berbeda. **Merge seluruh branch tidak boleh dilakukan**: akan membawa puluhan fitur lain (reimbursement, cash advance, dan lainnya) ke produksi.

Yang sudah ada di eco_new (versi awal tema):
- `app/Support/SeasonalThemes.php`, `GlobalSeasonalTheme.php`, `SeasonalThemeResolver.php`, `SeasonalThemeSettingsController.php`.
- `resources/views/admin/seasonal-theme-settings.blade.php`, `partials/seasonal-theme.blade.php`.
- Menu Control Center > Seasonal Theme (migrasi `2026_09_25_000001_add_seasonal_theme_menu.php`, identik di kedua branch, **jangan disentuh**).
- Whitelist tema pengguna masih literal `['default', 'none', 'natal']` di `SettingsController`.
- Dashboard memakai fallback `'seasonal_theme' => 'none'` (bug: pengguna yang belum pernah menyimpan preferensi tidak ikut tema admin).

Yang **belum ada** di eco_new:
- Composer `SeasonalThemeComposer`, `lang/en/ui.php` dan `lang/id/ui.php`, `resources/views/partials/sidebar.blade.php` (sidebar masih inline di `dashboard.blade.php`), semua adegan 3D, three.js, poster, SVG login, loading screen, pratinjau, aksen dashboard, hiasan navbar, tema login admin.

Selisih berkas bersama (baris tambah dan hapus aldy_merge terhadap eco_new):

| Berkas | Selisih | Konsekuensi |
|---|---|---|
| `resources/views/dashboard.blade.php` | +2345 -2466 | Jangan timpa. Sambung manual di sekitar 8 titik (bagian 6.1). |
| `resources/views/auth/login.blade.php` | +125 -343 | Jangan timpa. Sambung manual (bagian 6.2). |
| `resources/views/settings/index.blade.php` | +252 -136 | Jangan timpa. Hanya blok pemilih tema (bagian 6.4). |
| `resources/views/auth/forgot-password.blade.php` | +11 -10 | Dua baris saja. |
| `AuthController`, `SettingsController`, `CheckAuthToken`, `AppServiceProvider`, `routes/web.php` | 20 sampai 130 baris | Sambung manual, sebagian kecil (bagian 6.3). |

---

## 3. Arsitektur tema (sekali baca)

Satu katalog tunggal `App\Support\SeasonalThemes` menentukan semuanya. Kunci: `none`, `natal`, `tahun_baru`, `ramadan`, `lebaran`, `imlek`, `hut_ri`. Setiap tema membawa: `label_key`, `icon`, `accent`, `palette`, `decoration`, `ornaments`, `scene`, `loading`, `window`.

```
Pengaturan global (admin, AppConfig JSON 'seasonal_theme.global')
  theme, sound, volume, mode (manual|auto), schedule[{theme,start,end}], login{theme,scope}
        |
        v
GlobalSeasonalTheme::active()  <- tema global aktif hari ini (manual atau jadwal)
        |
SeasonalThemeResolver
  effectiveFor(pref)   : pref 'default' -> global; 'none' -> mati; key lain -> pilihan sendiri
  forAuthPage()        : halaman login dan auth (anonim)
  forLoading(pref)     : loading screen setelah login
        |
SeasonalThemeComposer -> $seasonalTheme {key, meta, soundUrl, soundVolume}
        |
dashboard + auth.* : <html data-season="KEY"> + variabel CSS palet + partial per tema
```

Aturan resolusi (sudah dibuktikan tes `ThemeConceptTest`, 13 test):
1. Admin bisa mengatur tema global (Control Center > Seasonal Theme) dan tema dirinya sendiri (Settings > Appearance), sama seperti pengguna lain.
2. Pengguna lain memilih `Default`, satu tema, atau `Off`.
3. `Default` mengikuti global (termasuk jadwal tanggal). `Off` mematikan tema untuk dirinya meski global aktif. Tema pilihan sendiri menang atas global.
4. Yang boleh mengubah global adalah pemegang izin menu `control-center.seasonal-theme` (bawaan hanya EC Administrator). Resolver tidak memeriksa role.
5. Tema login (admin saja): `theme` = `inherit` (bawaan, ikut global), `none`, atau satu tema; `scope` = `both` (bawaan), `page` (hanya tampilan halaman login dan auth), `loading` (hanya loading screen setelah login). Bila tema login bukan `inherit`, loading screen memakai tema itu untuk semua pengguna sesuai cakupan, termasuk yang memilih `Off` (hanya untuk layar loading). Dashboard, sidebar, navbar tetap mengikuti pilihan pengguna.

Data lama tanpa `login` berperilaku identik dengan sebelumnya (`inherit`/`both`). Tidak ada migrasi dan tidak ada query tambahan (memo per request, cache AppConfig 5 menit).

---

## 4. Spesifikasi desain per tema

### 4.1 Prinsip satu identitas

Setiap tema punya **satu keluarga warna dominan** yang dipakai identik di tiga permukaan: halaman login, loading screen, dan dashboard (sidebar, tombol utama, link, header). Warna kedua hanya muncul sebagai ornamen atau sorotan kecil, tidak pernah sebagai warna utama. Tes `PaletteCoherenceTest` menjaga ini (hue primer dalam 25 derajat dari login, kontras teks putih di atas primer paling sedikit 4,5:1, primer berbeda dari merah bahaya).

Merah bahaya aplikasi (`#dc2626`, tombol hapus dan reject) **tidak boleh berubah** di tema mana pun. Perlindungannya lewat selector (`data-danger`, handler `onclick` delete/reject/revoke, kelas red-500/600) di `seasonal-ui-remap`, bukan lewat warna.

### 4.2 Tabel palet final (nilai persis dari katalog)

| Tema | primary_rgb | primary_dark_rgb | accent | login_from / mid / to | button | loading_bottom | Identitas |
|---|---|---|---|---|---|---|---|
| natal | 20 69 47 | 11 42 30 | #d4af37 | #0b2a1e / #14452f / #1f6b43 | #1b6a40 | #14452f | hijau pinus, emas |
| tahun_baru | 34 34 44 | 10 10 15 | #f3dc8e | #07070b / #16161f / #2b2b36 | #2b2b36 | #2b2b36 | arang hitam, emas sampanye |
| ramadan | 30 64 124 | 15 32 70 | #f5c542 | #050a1c / #0f2046 / #1e3a8a | #1e3a8a | #1e3a8a | biru tua, emas |
| lebaran | 27 94 32 | 14 61 20 | #d4e157 | #06240d / #0e3d14 / #2e7d32 | #2e7d32 | #2e7d32 | hijau ketupat, hijau janur |
| imlek | 168 52 22 | 122 31 16 | #f5c542 | #3a0a05 / #7a1f10 / #a83416 | #a83416 | #7a1f10 | merah lak vermilion, emas |
| hut_ri | 168 12 36 | 125 10 30 | #ffffff | #3d0610 / #7d0a1e / #a80c24 | #a80c24 | #7d0a1e | merah bendera, putih |

Catatan kejujuran tentang sumber warna: tidak ada sumber resmi yang memberi heks untuk bendera Indonesia (UU 24/2009 hanya menyebut merah dan putih berbanding 2:3), merah Tionghoa, atau palet Natal dan Ramadan. Nilai di atas adalah keputusan desain dengan kontras yang dihitung (WCAG 2.1), bukan standar resmi. Natal juga punya varian gelap `dark` (belum dipakai).

Kontras terukur: menu aktif sidebar 5,1 sampai 8,8; teks panel login terburuk 5,0 sampai 7,1; teks tombol utama 6,5 sampai 15,7.

### 4.3 Loading screen setelah login (adegan 3D)

Overlay layar penuh, tampil sampai tiga detik setelah login berhasil (batas mutlak 3600 ms, adegan berjalan 2600 ms). Latar gradien 180 derajat dari `login_from` ke `loading_bottom`, kanvas WebGL di atasnya, judul dan subjudul di pita bawah dengan tabir gelap agar terbaca. Pengalihan selalu dijadwalkan lebih dulu oleh satu timer sehingga kegagalan apa pun tidak menahan login. Tanpa WebGL, Save-Data, 2g/3g, atau reduced motion: gambar diam dan teks saja.

Kontrak adegan: modul ES `create(THREE, ctx) -> {update(t, dt), dispose()}`, tanpa bundler. API `stage.js`: `supportsWebGL()`, `preload(scene)`, `play(canvas, {scene, palette, duration})`. Tier kualitas: `low` bila `deviceMemory <= 4`, `hardwareConcurrency <= 4`, atau Save-Data; bila 10 frame pertama rata-rata di atas 40 ms, pixel ratio turun ke 1 dan, bila sudah 1, skala render turun ke 0,75. Pita bawah (teks) selalu dibiarkan kosong.

| Tema | Adegan | Isi dan gerak | Anggaran terukur (RTX 5060, 720p) |
|---|---|---|---|
| natal | `sleigh` | Malam hijau pinus-teal dengan aurora zamrud, bulan besar berhalo, desa bersalju dengan gereja dan jendela emas, cerobong berasap, pinus bersalju. Kereta Santa dengan empat rusa (tanduk bercabang, hidung merah menyala hanya pada rusa pemimpin) melintas dan melewati bulan sekitar pertengahan, jejak cahaya emas, salju turun berlapis (dekat dan jauh). Merah kereta `#cc2a2e`. | 55 draw call, 101 ribu segitiga, median 1,4 ms |
| tahun_baru | `fireworks` | Kota malam dengan siluet gedung berjendela, pantulan di air, menara dengan bola hitung mundur yang turun sepanjang antena, kembang api bertahap (peony, krisan, willow, palem, cincin, hati, crossette) digambar sistem percikan GPU satu draw call dengan ekor dan kilau, kerumunan siluet berempat pose (tangan terangkat, melambai, memegang ponsel, berdiri), konfeti emas. Palet emas, sampanye, putih di atas hampir hitam. **Tidak ada citra minuman.** | 33 draw call, 58 ribu segitiga, median 0,7 ms |
| ramadan | `crescent` | Masjid dengan kubah tengah berusuk, empat setengah kubah, empat menara berbalkon dan berujung runcing, jendela lengkung runcing bercahaya, pola geometris lewat tekstur, kolam pantulan, bulan sabit tipis dengan bintang, palem, lentera fanous berayun dari tali dengan lampu hias. Biru tua dan emas. | 41 draw call, 65 ribu segitiga, median 0,7 ms |
| lebaran | `eid` | Senja hijau-teal di halaman rumah berteras kayu: sembilan tokoh berproporsi natural (baju koko dan peci, kebaya dan hijab, batik atau sarung) dengan pose salaman, pelukan, nenek dan anak, ayah melambai, wanita di meja opor dan ketupat; napas dan gerak lembut tanpa kaku. Ketupat anyaman dan janur bergantung, masjid berkubah hijau, kembang api jauh. Lampu dan jendela amber sebagai aksen hangat. Tokoh dalam satu mesh berkulit, satu draw call. | 42 draw call, 50 ribu segitiga, median 0,9 ms |
| imlek | `lunar` | Naga Tiongkok panjang berkelok (tubuh sekitar 52 unit, kepala sekitar 5,8 unit, proporsional), sisik merah tua bertepi emas, perut pelat krem-emas, punggung berduri kecil emas. Kepala ringkas: alis berat bersudut, mata kecil dalam, hidung besar bercuping melengkung, bibir atas tebal, taring, tanduk rusa tipis bercabang tiga, kumis pita pipih dari sudut bibir, janggut, surai api, mutiara bercahaya di depan mulut. Empat kaki ramping dengan telapak berjari lima bercakar melengkung. Latar lampion merah, kembang api emas, pagoda, bulan. Acuan anatomi: lukisan Nine Dragons (Chen Rong) dan relief Dinding Sembilan Naga Beihai. Skor kualitas internal sekitar 7 dari 10 (gaya, bukan fotorealistis). | 39 draw call, 58 ribu segitiga, median 1,1 ms |
| hut_ri | `flag` | Upacara bendera saat fajar dilihat dari belakang barisan: Merah Putih berbanding 2:3 (merah `#E70011` atas, putih bawah) pada kain berkibar dengan gelombang berjalan di GPU, lipatan, tembus cahaya dari belakang, jahitan dan mata ayam; sekitar 70 tokoh (paskibra, pramuka, TNI berbaret marun, veteran, sipil, anak SD) memberi hormat dengan tangan kanan ke pelipis; bambu runcing bersilang berpita merah putih, umbul-umbul, bendera kecil dan rumbai, patung pahlawan samar di kabut. Langit fajar merah tua ke oranye dengan cahaya putih-emas. | 40 draw call, 91 ribu segitiga, median 0,5 ms |

Poster referensi visual: `public/images/seasonal/posters/<key>.webp` (640x360, 16 sampai 36 KB) dan thumbnail `posters/thumb/<key>.webp` (160x90, 2 sampai 5 KB). Gunakan sebagai acuan akhir apakah port sudah benar.

Aturan implementasi adegan (jangan dilanggar saat memodifikasi): tanpa alokasi per frame, `dispose` lengkap, tidak ada efek samping saat import, kualitas `low` mengurangi segmen dan instansi.

### 4.4 Halaman login (panel kiri)

Panel kiri berisi ilustrasi vektor asli (SVG statis) per tema di `public/images/seasonal/login/<key>.svg`, dimuat lewat CSS `background-image` hanya untuk tema aktif dan hanya di lebar 768 px ke atas (panel disembunyikan di bawah itu, sehingga tidak ada permintaan gambar). Kotak gambar rasio tetap 5:4, posisi absolut di kanan bawah panel (CLS 0), dengan tabir gelap di kiri dan mask pudar kiri atas supaya teks merek, judul, daftar fitur, dan tautan tetap terbaca (AA terukur 5,0 sampai 7,1). Form login di panel kanan **tidak pernah disentuh**. URL memakai `?v=filemtime`.

| Tema | Ilustrasi |
|---|---|
| natal | Pohon Natal berhias lampu, bola, bintang bercahaya, kado, kabin berjendela, pinus, bulan, salju turun |
| tahun_baru | Siluet kota malam dengan beberapa letusan kembang api, **menara jam bercahaya pukul 12.00** (cincin emas, tanda jam, kedua jarum), bintang percikan, konfeti emas |
| ramadan | Masjid berkubah dan menara, bulan sabit dan bintang, lentera fanous, pantulan lembut |
| lebaran | Gugusan ketupat dengan janur, masjid dan bedug, lentera hangat, kembang api kecil |
| imlek | Naga Tiongkok melingkar elegan dengan kepala bertanduk dan kumis, lampion merah, bunga plum, emas batangan, awan |
| hut_ri | Bendera Merah Putih berkibar di tiang, siluet Monas, bambu runcing bersilang, rumbai merah putih, fajar hangat |

Gerak ambien: maksimal 10 elemen `<i>` dengan animasi `transform` dan `opacity` saja (salju untuk natal, bintang berkedip, cahaya lentera). Hanya di bawah `prefers-reduced-motion: no-preference`. Ornamen polos lama (orb, ring, pill) disembunyikan saat tema aktif dan tampil seperti biasa tanpa tema.

Tombol Sign In memakai variabel `--btn-bg`, `--btn-fg`, `--btn-bg-h` dari palet (`button`). Tahun Baru memakai tombol arang (`#2b2b36`) dengan teks putih.

### 4.5 Navbar (hiasan header dashboard)

Dua potongan SVG kecil (`viewBox 190x40`) di pojok kiri dan kanan header, dihasilkan `SeasonalNavbarArt` dan dicetak inline (tanpa berkas gambar). Bukan rumbai selebar layar. Garis tepi header (y=28 pada viewBox) membagi: bagian di dalam header hanya di pojok luar (x < 56 kiri, x > 134 kanan, di balik tombol menu dan avatar) sehingga judul dan subjudul tidak tertutup; sepanjang tepi hanya bandul mungil jarang yang menjuntai maksimal 12 unit.

Penempatan CSS: `.seasonal-navbar` `position:absolute; left:0; right:0; bottom:-14px; height:48px; z-index:-1; pointer-events:none; contain: layout paint size`. Potongan 228 px lebar. Di bawah 640 px tinggi 36 px dan hanya potongan kiri; di bawah 400 px tinggi 30 px. Disembunyikan saat print dan `prefers-contrast: more`. Mode gelap memakai `filter: brightness(1.22) saturate(1.05)` (Lebaran hanya `brightness(1.05) saturate(.85)`; Natal tepi beku opasitas .3).

Tiap tema juga punya: garis tepi 2 px (natal hijau pinus dengan tengah emas, tahun_baru arang dengan tengah emas sampanye, ramadan biru tua dengan tengah emas dan bintang kecil, lebaran hijau tua dengan limau dan emas, imlek vermilion dengan blok emas, hut_ri merah bendera), lapisan pojok ketiga (`viewBox 70x40`), dan khusus natal tepi beku halus tidak beraturan setinggi maksimal 6 px.

| Tema | Motif pojok dan bandul |
|---|---|
| natal | Kiri: ranting pinus melengkung ke atas dengan beri merah kecil dan pita; bandul bola, bintang, es, bola emas. Kanan: holly dengan lonceng emas, tongkat permen emas. Warna mint-beku untuk kristal. |
| tahun_baru | Kiri: pita emas dan perak melingkar, kilau; bandul bintang, pita bergelombang. Kanan: tiga letusan kembang api emas, sampanye, perak. |
| ramadan | Kiri: bulan sabit berpendar dan bintang; bandul lentera dan bintang. Kanan: siluet kubah dan menara; bandul bintang dan bulan kecil. |
| lebaran | Kiri dan kanan: kipas janur melengkung; bandul ketupat dan kilau; bulan sabit kecil. |
| imlek | Kiri: lampion besar menggantung dan ranting bunga plum; bandul koin dan tassel. Kanan: ranting plum dan bintang. Kelopak krem-emas. |
| hut_ri | Kiri: dua bendera bersilang; bandul rumbai pendek dan bintang. Kanan: dua bendera kecil dan rumbai. |

Gerak (kelas `sn-*`): `sn-sway` ayun pendulum, `sn-twinkle` kedip, `sn-pop` letup kembang api, `sn-wave` kibar, `sn-breathe` berpendar, `sn-drift` dan `sn-fall` untuk lapisan ketiga. Semuanya `transform` dan `opacity`, tiap elemen punya durasi, amplitudo, dan jeda sendiri, maksimal 8 elemen bergerak per potongan, hanya bila `Show animations` aktif dan perangkat tidak reduced motion. Anggaran: maksimal 14 KB per potongan, tanpa atribut `id`.

### 4.6 Sidebar, header, dan aksen dashboard

Sidebar: gradien `primary-gradient` memakai `--primary-rgb` dan `--primary-dark-rgb` tema (selector `html[data-season] #sidebar.primary-gradient` lebih spesifik dari kelas dasar, mode gelap tidak diubah). Aksen aktif memakai `--sb-accent: var(--st-accent)`. Dekorasi sidebar (`partials/seasonal-theme`, placement `sidebar`): hanya maksimal 3 ornamen kecil 15 sampai 18 px di ujung kanan footer (bottom 17 px), **tidak pernah** di atas menu atau ikon.

Aksen dashboard (`partials/seasonal-accents`, hanya CSS statis, hanya untuk tema aktif, 1,2 sampai 4,1 KB): selector daftar putih `#sidebar`, `#sidebarHeader`, `#sidebar a.nav-link.active`, `#mainContent > header`, dan kartu `> div.rounded-xl` pada `section[aria-label="Company overview" | "Tickets" | "Recent activity"]`. Per tema: natal garis mint-beku, kristal di pojok kartu, bintik salju statis (lapisan `body::before` tetap, opasitas .45, `contain: strict`), tutup salju tipis di menu aktif; tahun_baru garis kilau emas dan kilau saat hover; ramadan arabesk samar 3 sampai 6 persen di kepala sidebar dan garis emas; lebaran anyaman ketupat samar dan garis limau; imlek siku emas di pojok kartu; hut_ri garis merah-putih 3 px di atas header.

Larangan mutlak untuk aksen: tidak dipakai sebagai warna teks; tidak menyentuh tabel data, form, modal, grafik, warna status atau bahaya, cetakan, atau ekspor; pseudo-element absolut tanpa mengubah tinggi (CLS 0); **tanpa `backdrop-filter`, tanpa animasi `box-shadow`, tanpa `@keyframes`** di berkas aksen; gerak apa pun hanya `transform`/`opacity` dan hanya bila `Show animations` aktif dan bukan reduced motion; z-index di bawah dropdown, modal, dan toast.

`seasonal-ui-remap` memetakan utilitas brand (bg-red-700/800/900 dan sejenisnya) ke palet di bawah `html[data-season]`, dimuat hanya saat tema aktif atau di halaman Settings (pratinjau).

### 4.7 Pratinjau loading screen (Settings dan Control Center)

Thumbnail poster statis di tiap kartu tema (lazy, lebar dan tinggi ditetapkan). Tombol "Play preview" opsional: three.js dan modul adegan baru diunduh setelah klik (`import()` dinamis di dalam `open()`, tidak ada impor statis, tidak ada preload). Dialog `role=dialog`, `aria-modal`, fokus ke tombol Close, Tab terkunci, Esc, klik, atau Close menutup, tutup otomatis sekitar 6 detik, satu pratinjau pada satu waktu, scene dibuang dan RAF dihentikan saat ditutup atau tab disembunyikan. Save-Data, 2g/3g, reduced motion, atau tanpa WebGL: poster diam dengan catatan. Import map pratinjau memakai `?v=mtime` dan **harus dicetak dengan `@json(...)`, bukan `Js::from`** (hasil `Js::from` adalah `JSON.parse('...')` yang bukan JSON valid untuk import map).

### 4.8 Musik latar

Hanya admin yang memasang musik (unggah wav, mp3, ogg, aac maksimal 3 MB, ekstensi diperiksa) dan volumenya; berlaku untuk semua pengguna; hanya diputar bila tema aktif dan ada berkas.

---

## 5. Anggaran performa dan kualitas (uji penerimaan)

Port dianggap benar bila angka berikut terpenuhi di staging eco_new:

| Aspek | Batas |
|---|---|
| Query tambahan pada halaman login, dashboard, Settings, admin | 0 (login hanya satu pembacaan `seasonal_theme.global` yang sudah ada) |
| CLS | 0 (batas toleransi 0,01) |
| Tema `none` | 0 byte tambahan dari partial tema, tidak ada permintaan gambar atau JS tema |
| three.js | tidak diunduh di halaman Settings, admin, atau dashboard sampai tombol Play ditekan; di halaman login hanya pemanasan idle bila tema loading aktif (dilewati Save-Data dan 2g/3g) |
| Hiasan navbar | maksimal 14 KB per potongan, 8 elemen bergerak per potongan |
| Aksen dashboard | maksimal sekitar 4,2 KB CSS per tema, nol layout, nol style recalc, nol paint saat idle |
| Adegan 3D, GPU nyata | median di bawah 2,5 ms sampai 4K; draw call maksimal 55; tanpa kebocoran buffer, program, VAO, shader dalam 20 kali putar |
| Heap JS | tumbuh kurang dari 1,5 MB dalam 20 kali putar |
| SVG login | maksimal 45 KB mentah (4 sampai 10 KB gzip) |
| Kontras | teks sidebar dan menu aktif minimal 4,5:1, teks panel login minimal 4,5:1 |
| Tema bahaya | tombol hapus tetap `rgb(220,38,38)`, reject `rgb(185,28,28)` di keenam tema |
| Aksesibilitas | dialog pratinjau tertutup dengan keyboard, reduced motion menampilkan gambar diam, `prefers-contrast: more` menyembunyikan ornamen |

Keterbatasan yang diketahui (jangan dianggap bug baru): adegan lebih lambat dari tolok ukur lama di renderer perangkat lunak (SwiftShader) dan belum diukur di ponsel; naga masih bergaya (bukan fotorealistis); adegan Natal belum punya tier `low` sendiri; Imlek dan HUT RI memakai primer merah yang dekat dengan merah bahaya (dibedakan oleh keadaan lebih gelap dan oleh perlindungan selector).

---

## 6. Titik sambung di berkas bersama (jangan timpa berkas ini)

Semua nomor baris merujuk `aldy_merge` (commit `0479ef06`) hanya sebagai penunjuk; cari berdasarkan teksnya di eco_new.

### 6.1 `resources/views/dashboard.blade.php` (di eco_new sidebar masih inline)

1. Tag `<html ...>`: tambahkan `@if(($seasonalTheme['key'] ?? 'none') !== 'none') data-season="{{ $seasonalTheme['key'] }}"@endif`.
2. Blok PHP awal: ganti fallback preferensi `'seasonal_theme' => 'none'` menjadi `'default'` (perbaikan bug), dan ganti perhitungan tema inline menjadi:
   `$seasonalTheme = $seasonalTheme ?? ['key' => 'none', 'meta' => null, 'soundUrl' => null, 'soundVolume' => null]; $effectiveSeasonalTheme = $seasonalTheme['key'];` (dibagikan composer). Musik: `GlobalSeasonalTheme::soundUrl()` dan `soundVolume()`.
3. Di `<head>` setelah blok `:root`: `@include('partials.seasonal-theme-vars')`.
4. Di akhir `<head>`:
   ```blade
   @if(($seasonalTheme['key'] ?? 'none') !== 'none' || request()->routeIs('settings.*'))
       @include('partials.seasonal-ui-remap', ['dark' => ($preferences['theme'] ?? 'light') === 'dark'])
   @endif
   @if(($seasonalTheme['key'] ?? 'none') !== 'none')
       @include('partials.seasonal-accents', ['themeKey' => $seasonalTheme['key'], 'dark' => ($preferences['theme'] ?? 'light') === 'dark', 'showAnimations' => $preferences['show_animations'] ?? true])
   @endif
   ```
   (cocokkan argumen persis dengan baris di aldy_merge `dashboard.blade.php` sekitar baris 1643 sampai 1645).
5. Awal `<body>`: `@if($effectiveSeasonalTheme !== 'none') @include('partials.seasonal-icons') @endif`.
6. Di dalam `<header ...>` sebelum konten: `@if($effectiveSeasonalTheme !== 'none') @include('partials.seasonal-navbar', ['themeKey' => $effectiveSeasonalTheme, 'showAnimations' => $preferences['show_animations'] ?? true, 'dark' => ($preferences['theme'] ?? 'light') === 'dark']) @endif`.
7. Di dalam `<aside id="sidebar">` paling atas: `@if(($seasonalTheme['key'] ?? 'none') !== 'none') @include('partials.seasonal-theme', ['themeKey' => $seasonalTheme['key'], 'showAnimations' => $preferences['show_animations'] ?? true, 'placement' => 'sidebar']) @endif`. Ganti dua pemanggilan `partials.seasonal-theme` lama (placement header) dengan yang di atas.
8. CSS sidebar (di aldy_merge ada di `partials/sidebar.blade.php` baris sekitar 1468 sampai 1480; di eco_new taruh di blok `<style>` dashboard):
   ```css
   html[data-season] #sidebar.primary-gradient { background: linear-gradient(180deg, rgb(var(--primary-rgb)) 0%, rgb(var(--primary-dark-rgb)) 100%) !important; }
   html[data-season] #sidebar.primary-solid { background: rgb(var(--primary-rgb)) !important; }
   html[data-season] #sidebar { --sb-accent: var(--st-accent, #f2c14e); }
   ```
   Salin nilai persis dari aldy_merge (jangan menebak). Bila eco_new belum punya `--sb-accent`, `.sb-footer`, `#sidebarHeader`, atau mode rail, lewati bagian itu dan catat (aksen yang selectornya tidak ada tidak akan berefek, tidak merusak).
9. Menu sidebar admin: tautan `Seasonal Theme` sudah ada di eco_new; jangan duplikasi.

### 6.2 `resources/views/auth/login.blade.php` (dan `forgot-password.blade.php`, auth lain)

1. `<html ... data-season>` sama seperti di dashboard; `@include('partials.seasonal-theme-vars')` di `<head>`.
2. CSS merek: blok `:root[data-season] { ... }` yang menimpa variabel merek (`--brand`, gradien panel, tombol `--btn-bg`, `--btn-fg`, `--btn-bg-h`) dengan fallback ke nilai lama agar tanpa tema tidak berubah. Salin dari aldy_merge sekitar baris 48 sampai 80.
3. Awal `<body>`: hitung `$loginSeasonalTheme = $seasonalTheme['key']`, `@include('partials.seasonal-loading', ['loadingId' => 'seasonalLoginOverlay', 'warmKey' => \App\Support\SeasonalThemeResolver::forLoading('default')])`, dan `<script>window.__seasonalGlobalKey = {{ \Illuminate\Support\Js::from($loginSeasonalTheme) }};</script>`.
4. Panel kiri: `@if($loginSeasonalTheme !== 'none') @include('partials.login-art', ['themeKey' => $loginSeasonalTheme]) @endif`; bungkus orb dan ring lama dalam `.dp-ambient` yang disembunyikan saat tema aktif.
5. Skrip submit sukses: pakai `data.seasonal_theme` untuk memanggil `window.SeasonalLoading.start(themeKey, go)`; pertahankan penjaga `if (btn.disabled) return;` (cegah kirim ganda). Jangan mengubah id, handler, atau validasi yang sudah ada di eco_new.
6. Catatan: eco_new mungkin tidak memiliki perbaikan label autofill dari tim di `merge_eco-hr` (bukan bagian tema); jangan ikut membawanya kecuali dibutuhkan.

### 6.3 PHP bersama

- `AuthController` (respons JSON login): tambahkan `'seasonal_theme' => self::effectiveSeasonalTheme($authUser)` dan method privat statis `effectiveSeasonalTheme(object $authUser): string` yang membaca `preferences` (string JSON atau array), mengambil `seasonal_theme` (bawaan `default`), memanggil `SeasonalThemeResolver::forLoading($choice)`, dan jatuh ke `'none'` bila gagal. Salin dari aldy_merge baris sekitar 1072 dan 1090 sampai 1105.
- `SettingsController`: bawaan `'seasonal_theme' => 'default'`; hapus literal `['default','none','natal']` dari `ALLOWED_VALUES` dan bangun whitelist dari `\App\Support\SeasonalThemes::userChoices()` di `allowedValues()`.
- `CheckAuthToken`: saat preferensi kosong, isi bawaan (termasuk `seasonal_theme = 'default'`) supaya ikut tema admin. Lihat aldy_merge baris sekitar 119.
- `AppServiceProvider::boot()`: `View::composer(['dashboard', 'auth.*'], \App\View\Composers\SeasonalThemeComposer::class);`
- `routes/web.php`: rute `seasonal-theme` (GET, POST) dan `seasonal-theme/upload-sound` sudah ada di eco_new. Pastikan ketiganya bermiddleware `menu:control-center.seasonal-theme`.

### 6.4 `resources/views/settings/index.blade.php`

Ganti hanya blok pemilih tema: kartu katalog (`st-card` dengan ikon vektor `partials.seasonal-icon` dan thumbnail `partials.seasonal-poster`), kartu `Default` dan `Off`, tautan "Manage" untuk pemegang izin menu, `@include('partials.seasonal-preview')`, dan CSS kartu (`.st-card`, tanpa mengubah `font-weight` saat dipilih agar tinggi kartu tetap). Sertakan sprite `partials.seasonal-icons` (`@once`) di halaman ini. Teks lewat `__('ui.seasonal.*')`.

### 6.5 Bahasa

eco_new belum punya `lang/en/ui.php` dan `lang/id/ui.php`. Buat berkas minimal berisi hanya grup `seasonal` (87 kunci, EN dan ID identik) memakai `extract-seasonal-lang.php`. Jangan menyalin `ui.php` aldy_merge utuh (berisi fitur lain). Bila berkas itu sudah ada di eco_new saat port dikerjakan, tambahkan grup `seasonal` ke dalamnya secara manual.

### 6.6 Panduan pengguna (aturan proyek CLAUDE.md)

Salin topik baru `021-my-seasonal-theme` dan `148-cc-seasonal-theme` (EN dan ID, sudah ada di manifest). Perbarui tipis topik yang menyebut tema (020, 030, 062, 070) bila ada di eco_new; hapus bagian tema lama di 062 bila menduplikasi 148. Jalankan `php docs/HRGA/tools/guide-check.php` (target 0 galat dan 0 peringatan baru) dan `php artisan test tests/Unit/Guide` (atau dengan `-d extension=pdo_sqlite -d extension=sqlite3` bila `pdo_sqlite` mati di `php.ini`).

---

## 7. Langkah kerja (runbook)

Prasyarat: sudah ada akses ke `origin` yang sama (kedua branch ada di satu repositori). Kerjakan di branch baru dari `origin/eco_new`; jangan menyentuh `eco_new` langsung.

```bash
git fetch origin
git checkout -b feature/seasonal-theme-port origin/eco_new
SRC=0479ef06   # commit sumber KODE tema di aldy_merge

# 0. Ambil folder dokumen ini lebih dulu (berisi manifest dan skrip bahasa); belum ada di eco_new
git checkout origin/aldy_merge -- docs/seasonal-theme-port

# 1. Salin berkas BARU apa adanya (77 berkas)
git checkout $SRC -- $(grep -v '^#' docs/seasonal-theme-port/manifest-new-files.txt)

# 2. Timpa berkas tema yang sudah ada (6 berkas, murni tema)
git checkout $SRC -- $(grep -v '^#' docs/seasonal-theme-port/manifest-replace-files.txt)

# 3. Bahasa (hanya grup seasonal)
git show $SRC:lang/en/ui.php > /tmp/ui_en.php
git show $SRC:lang/id/ui.php > /tmp/ui_id.php
php docs/seasonal-theme-port/extract-seasonal-lang.php /tmp/ui_en.php lang/en/ui.php
php docs/seasonal-theme-port/extract-seasonal-lang.php /tmp/ui_id.php lang/id/ui.php

# 4. Sambung berkas bersama sesuai bagian 6 (manual, per berkas, commit kecil per berkas)

# 5. Uji
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Seasonal tests/Feature/Seasonal tests/Unit/Guide
php docs/HRGA/tools/guide-check.php
php artisan view:cache && php artisan view:clear
```

Urutan commit yang disarankan (tiap commit lolos tes sendiri): (1) berkas baru dan timpa, (2) bahasa, (3) backend (composer, resolver, controller, middleware, SettingsController), (4) dashboard dan sidebar, (5) login dan auth, (6) Settings dan pratinjau, (7) panduan dan dokumen.

Hasil uji coba kering (2026-11-24, worktree terpisah dari `origin/eco_new`, tanpa menyentuh branch mana pun): langkah 0 sampai 3 menyalin 77 berkas baru dan mengganti 6 berkas (81 berkas ditambahkan termasuk folder dokumen ini, 6 diubah), lang minimal terbuat dengan 87 kunci EN dan 87 kunci ID, semua PHP lolos `php -l`, semua JS lolos `node --check`. Ketergantungan kelas tema (`AppConfig::getJson` dan `setJson`, `AuditLog::recordAction`, `AiModelSettings`, `SettingsController`, `AuthController`) semuanya sudah ada di eco_new. Yang belum diuji: sambungan di berkas bersama (bagian 6) dan tes PHPUnit setelah sambungan, karena itu bagian pekerjaan port.

Catatan tes: tes tema di aldy_merge memakai `Tests\TestCase` dan menyemai cache; tes yang membaca partial atau aset (`SeasonalAssetsTest`, `LoginArtTest`, `SeasonalNavbarArtTest`, `PaletteCoherenceTest`) ikut ter-copy lewat manifest. Bila eco_new tidak punya kelas atau helper yang dipakai tes lain, sesuaikan di tes tema saja, bukan di kode produksi.

---

## 8. Prompt siap salin (untuk Claude Code di branch eco_new)

Gunakan berurutan. Setiap prompt berdiri sendiri. Kode sumber: commit `0479ef06`; dokumen dan manifest: `origin/aldy_merge`.

### Prompt 0: pemeriksaan awal (tanpa mengubah apa pun)

```
Kita akan memindahkan fitur Seasonal Theme dari commit 0479ef06 (branch aldy_merge) ke branch baru feature/seasonal-theme-port yang dibuat dari origin/eco_new. Baca docs/seasonal-theme-port/PORT-KE-ECO_NEW.md bagian 1, 2, 3, dan 6 di commit itu (git show origin/aldy_merge:docs/seasonal-theme-port/PORT-KE-ECO_NEW.md). Jangan mengubah file. Lakukan pemeriksaan: (1) apakah eco_new masih sama dengan keadaan di dokumen (SeasonalThemes.php hanya natal, tidak ada composer, tidak ada lang/en/ui.php, sidebar inline di dashboard.blade.php); (2) daftar berkas bersama di bagian 6 dan apakah titik sambungnya masih ada di eco_new (cari teksnya, bukan nomor baris); (3) apakah ada commit baru di eco_new sejak 2026-11-24 yang menyentuh berkas-berkas itu; (4) selector aksen dashboard yang ada di eco_new (#sidebar, #sidebarHeader, a.nav-link.active, #mainContent > header, section[aria-label="Company overview"|"Tickets"|"Recent activity"]). Laporkan temuan dan risiko konflik. Jangan menebak: tunjukkan bukti (potongan kode dan path).
```

### Prompt 1: salin berkas apa adanya dan backend

```
Di branch feature/seasonal-theme-port (dari origin/eco_new): jalankan langkah 1 sampai 3 di bagian 7 dokumen docs/seasonal-theme-port/PORT-KE-ECO_NEW.md (salin 77 berkas baru dan 6 berkas timpa dari commit 0479ef06, buat lang minimal dengan extract-seasonal-lang.php). Lalu kerjakan sambungan backend bagian 6.3: SeasonalThemeComposer terdaftar di AppServiceProvider untuk ['dashboard','auth.*']; AuthController mengirim seasonal_theme lewat SeasonalThemeResolver::forLoading; SettingsController membangun whitelist dari SeasonalThemes::userChoices() dan bawaan 'default'; CheckAuthToken mengisi bawaan preferensi; ketiga rute seasonal-theme memakai middleware menu:control-center.seasonal-theme. Jangan menyentuh migrasi apa pun dan jangan membuat migrasi baru (data tema memakai AppConfig JSON yang sudah ada). Jalankan php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Seasonal tests/Feature/Seasonal dan laporkan hasil persis. Satu commit untuk salinan, satu commit untuk sambungan backend. Jangan memakai em dash atau en dash di teks mana pun.
```

### Prompt 2: dashboard dan sidebar

```
Kerjakan bagian 6.1 di docs/seasonal-theme-port/PORT-KE-ECO_NEW.md pada resources/views/dashboard.blade.php di eco_new. Berkas ini JANGAN ditimpa dari aldy_merge (berbeda 4800 baris, fitur lain akan hilang). Lakukan hanya sambungan kecil: atribut data-season pada <html>, perbaikan fallback preferensi 'seasonal_theme' => 'default', perhitungan $seasonalTheme dari composer, include seasonal-theme-vars di head, remap dan aksen (hanya saat tema aktif), sprite ikon, navbar di dalam header, dekorasi sidebar, dan tiga baris CSS sidebar html[data-season] #sidebar. Bandingkan dengan aldy_merge (git show 0479ef06:resources/views/dashboard.blade.php dan resources/views/partials/sidebar.blade.php) hanya untuk menyalin teks sambungan persis. Pastikan dengan tema none halaman identik dengan sebelumnya (tidak ada byte tambahan dari partial tema). Tampilkan git diff yang minimal untuk berkas ini dan jelaskan setiap hunk. Verifikasi dengan php artisan view:cache lalu view:clear.
```

### Prompt 3: login dan halaman auth

```
Kerjakan bagian 6.2 di docs/seasonal-theme-port/PORT-KE-ECO_NEW.md pada resources/views/auth/login.blade.php dan forgot-password.blade.php (dan halaman auth lain yang memakai layout serupa) di eco_new. Jangan menimpa berkas dari aldy_merge. Sambungkan: data-season, seasonal-theme-vars, blok :root[data-season] dengan fallback ke nilai lama, include seasonal-loading dengan warmKey, partial login-art di panel kiri, pembungkus .dp-ambient, dan pemanggilan SeasonalLoading.start dari respons login. Pertahankan semua id, handler, validasi, toggle password, Enter, dan penjaga kirim ganda yang sudah ada di eco_new. Tema none harus tampil identik. Verifikasi dengan merender halaman login untuk keenam tema (render Blade dengan memo tema di memori, tanpa database) dan lihat tangkapan layar di 1920x1080, 1280x720, 1024x768, dan 390x844: form tidak boleh tertutup, teks panel kontras minimal 4,5:1, tidak ada overflow horizontal.
```

### Prompt 4: Settings, admin, pratinjau

```
Kerjakan bagian 6.4 dan 4.7 di docs/seasonal-theme-port/PORT-KE-ECO_NEW.md: ganti hanya blok pemilih tema di resources/views/settings/index.blade.php (kartu katalog dengan ikon vektor dan poster thumbnail, kartu Default dan Off, tautan Manage untuk pemegang izin, include seasonal-preview, sprite seasonal-icons @once, CSS .st-card tanpa font-weight saat dipilih). Halaman admin admin/seasonal-theme-settings.blade.php sudah ikut tertimpa dari manifest-replace-files.txt: periksa bahwa kartu "Login and auth pages" dan pratinjau tampil benar. Import map di seasonal-preview harus dicetak dengan @json(..., JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES), bukan Js::from. Verifikasi: tombol Play membuka dialog, Esc dan Close menutup, three.js tidak diunduh sebelum klik, mode reduced motion menampilkan poster diam, tidak ada error konsol.
```

### Prompt 5: panduan pengguna dan dokumen

```
Ikuti aturan di CLAUDE.md untuk panduan pengguna. Pastikan topik resources/guide/{en,id}/021-my-seasonal-theme.md dan 148-cc-seasonal-theme.md ada, berpasangan, dan front matter serta slug requires sesuai tabel menu di eco_new. Perbarui topik 020, 030, 062, 070 hanya bila menyebut tema dan hapus duplikasi di 062. Jalankan php docs/HRGA/tools/guide-check.php (0 galat; peringatan baru tidak boleh bertambah) dan php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Unit/Guide. Tidak boleh ada em dash atau en dash. Perbarui docs/seasonal-theme.md bila ada penyimpangan dari kenyataan di eco_new.
```

### Prompt 6: gerbang QA (jalankan paralel, hanya baca)

Jalankan sebagai agen terpisah, masing-masing hanya membaca dan melaporkan:

```
QA PERFORMA dan RENDER: ukur di staging atau lokal dengan GPU nyata (Edge dengan --force_high_performance_gpu bila ada GPU NVIDIA; konfirmasi renderer lewat WEBGL_debug_renderer_info) dan SwiftShader sebagai pembanding perangkat lemah. Untuk keenam adegan: median dan p95 waktu frame di 1280x720 dan 1920x1080, draw call, segitiga, kebocoran GL (buffer, program, VAO, shader) dalam 20 putaran, drift heap, galat konsol. Halaman: CLS, long task, style recalc, jumlah elemen beranimasi, biaya scroll lapisan salju natal, bobot login per tema. Bandingkan dengan anggaran di bagian 5 dokumen. Laporkan PASS atau FAIL per butir dengan bukti, jangan mengubah berkas.
```

```
QA QUERY dan KEAMANAN: ukur jumlah query (baca-saja, jangan menulis ke database kerja) untuk login, forgot-password, dashboard, Settings, admin Seasonal Theme GET dan POST sebelum dan sesudah port; semua partial tema harus 0 query. Tinjau XSS (setiap {!! !!}, @json, Js::from, atribut dari data), path traversal pada URL aset, CSRF, otorisasi rute (menu:control-center.seasonal-theme pada GET, POST, upload-sound), validasi Rule::in, aturan unggah musik, SVG statis (tanpa script, foreignObject, event handler, referensi eksternal). Jalankan matriks login (cakupan page, loading, both x tema inherit, none, satu tema x preferensi pengguna default, none, satu tema) dan konfirmasi resolver tidak memeriksa role. Laporkan PASS atau FAIL dengan bukti dan perbaikan.
```

```
QA UI dan FUNGSI: render Blade asli untuk keenam tema (terang dan gelap) di 360, 768, 1280, 1920: ornamen navbar tidak menutupi judul atau tombol (elementFromPoint), tidak ada overflow horizontal, kontras teks sidebar dan panel login, tombol hapus dan reject tetap merah bahaya, dropdown dan modal di atas dekorasi, print menyembunyikan ornamen, prefers-contrast: more menyembunyikan ornamen, dialog pratinjau bisa dioperasikan dengan keyboard. Alur: login (semua tema), loading screen sampai dashboard, Settings (pilih tema, Default, Off, simpan, muat ulang), admin (manual, jadwal, musik, kartu login, validasi, akses ditolak bagi non-admin). Pastikan menu dan fitur lain di eco_new tidak berubah (tabel, form, modal, ekspor). Laporkan dengan tangkapan layar yang benar-benar dilihat.
```

### Prompt 7: rangkuman dan serah terima

```
Buat ringkasan port: daftar commit, berkas yang disalin utuh, berkas bersama yang disambung (dengan jumlah baris diff tiap berkas), hasil tes (jumlah persis), hasil QA per gerbang, hal yang tidak bisa diverifikasi, dan langkah deploy (view:clear, cache, aset statis baru sekitar 1,5 MB di public/). Bandingkan tampilan keenam tema dengan poster referensi di public/images/seasonal/posters.
```

---

## 9. Rencana rilis dan mundur

Rilis aman karena nilai bawaan tidak mengubah tampilan: tema global bawaan `none` dan pengguna bawaan `default`, sehingga setelah deploy tidak ada yang berubah sampai admin memilih tema. Perbaikan fallback `'none'` menjadi `'default'` hanya berpengaruh bila admin mengaktifkan tema.

Urutan yang disarankan:
1. Merge `feature/seasonal-theme-port` ke staging eco_new, jalankan Prompt 6 (tiga QA) di staging.
2. Deploy ke produksi dengan tema global `none`. Pantau galat 15 menit.
3. Admin mengaktifkan satu tema (mis. `natal`) mode manual untuk pengguna uji, periksa login, loading, dashboard, Settings.
4. Aktifkan jadwal otomatis sesuai kalender (Natal 1 sampai 26 Desember, Tahun Baru 27 Desember sampai 3 Januari, HUT RI 10 sampai 20 Agustus; Ramadan, Lebaran, Imlek tanggalnya diisi admin tiap tahun).

Deploy: `php artisan view:clear`, `php artisan cache:clear` (cache AppConfig 5 menit), pastikan aset statis baru (`public/js/seasonal`, `public/vendor/three`, `public/images/seasonal`, sekitar 1,5 MB) ikut terkirim, dan cek di server bahwa kompresi gzip dan header cache aktif untuk `public/vendor/three` dan `public/js/seasonal` (login pertama dengan tema aktif mengunduh sekitar 172 KB gzip untuk three.js saat idle; belum pernah diukur di produksi).

Mundur: tema dimatikan seketika dari Control Center (tema global `none`, tema login `inherit`) tanpa deploy. Bila perlu mundur kode, `git revert` commit port (tanpa force push); data tema ada di satu baris AppConfig dan tidak memerlukan rollback database.

---

## 10. Daftar periksa penerimaan akhir

- [ ] `git diff --stat origin/eco_new..feature/seasonal-theme-port` hanya menyentuh berkas tema, ditambah hunk sambungan kecil di berkas bersama (bagian 6). Tidak ada berkas fitur lain yang berubah.
- [ ] Tema `none`: dashboard dan login identik dengan sebelumnya, tidak ada permintaan jaringan baru.
- [ ] Keenam tema: login, loading, dan dashboard seirama (satu keluarga warna); tidak ada citra minuman di Tahun Baru.
- [ ] Tes tema, panduan, dan seluruh suite hijau; `guide-check` 0 galat.
- [ ] Anggaran bagian 5 terpenuhi di staging dengan GPU nyata.
- [ ] Dua perbandingan visual: poster referensi dan tangkapan layar hasil port untuk tiap tema cocok.
- [ ] Rencana mundur dipahami admin (bagian 9).

## 11. Apa yang tidak ikut dipindahkan

- Fitur lain di aldy_merge (reimbursement, cash advance, dan seterusnya).
- Perbaikan label autofill login dan helper `apiFetch` dari tim di `merge_eco-hr` (bukan bagian tema).
- Kunci tema paksa untuk semua pengguna (belum dibuat).
- Penerapan tema ke tabel, form, dan modal (sengaja tidak disentuh).
