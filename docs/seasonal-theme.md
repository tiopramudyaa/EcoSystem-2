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
| `palette` | Nilai CSS variable `--st-*` (primary_rgb, primary_dark_rgb, accent, login_from, login_to). `null` untuk `none` |
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

### Warna tombol dan UI

`partials/seasonal-theme-vars` menimpa `--primary-rgb`, `--primary-dark-rgb`, `--primary-color`, dan `--primary-surface` di bawah `html[data-season]`, sehingga sidebar, `.primary-*`, dan halaman accent-remap ikut berubah. `partials/seasonal-ui-remap` memetakan utility merek (`bg-red-700/800/900`, hover-nya, `text-red-700/800` pada tautan dan tombol, border, ring, gradien) ke palet. Warna bahaya tidak ikut: `bg-red-500/600`, elemen `[data-danger]`, `onclick` yang memuat delete, reject, atau revoke, lencana `.uppercase`, dan isi kotak peringatan. Tombol hapus atau tolak baru yang memakai `bg-red-700/800` wajib diberi atribut `data-danger`. Warna `#991b1b` yang ditulis langsung di blok `<style>` halaman tidak ikut dipetakan.

### Loading screen 3D

Setelah login sukses, respons `POST /api/auth/login` membawa `seasonal_theme` (tema efektif pengguna itu, dihitung dari kolom `preferences` yang sudah dimuat tanpa query tambahan). `login.blade.php` memilih adegan dari data itu. Alurnya:

| Jalur | Pengalihan ke dashboard |
|---|---|
| Tema `none` | Toast, 1000 ms |
| 3D berjalan | maks(adegan selesai, 2200 ms), tidak pernah lebih dari 3600 ms |
| WebGL tidak ada, impor gagal, atau reduced motion | Versi statis, 1800 ms |

Pengalihan selalu dijadwalkan lebih dulu oleh satu timer sehingga kegagalan apa pun tidak menahan login. three.js (r170, `public/vendor/three`) dan modul adegan baru diunduh saat adegan akan diputar, atau saat idle bila tema global tidak `none`; tidak ada unduhan untuk tema `none`. Adegan membuang semua geometri, material, dan konteks WebGL setelah selesai, membatasi pixel ratio 1,5, tanpa shadow map, dan maksimal 2500 partikel. Dashboard tidak lagi memiliki splash. Musik latar hanya diatur admin (lagu dan volume, global); pengguna hanya dapat membisukannya secara lokal di browser (WCAG 1.4.2).

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
| `resources/views/partials/seasonal-theme-vars.blade.php` | CSS variable `--st-*` dari palet tema |
| `resources/views/partials/seasonal-loading.blade.php` | Overlay loading (kanvas 3D, versi statis, data tema) |
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
