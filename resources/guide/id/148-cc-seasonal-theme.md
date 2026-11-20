---
key: cc-seasonal-theme
chapter: control-center
order: 27
title: Seasonal Theme
summary: Pilih tampilan perayaan untuk EcoSystem, aktifkan secara manual atau dengan jadwal tanggal, atur musik latar, dan lihat bagaimana setiap orang dapat menimpanya.
requires: [control-center.seasonal-theme]
updated: 2026-11-19
---
Tema musiman memberi EcoSystem tampilan perayaan untuk hari besar: dekorasi di halaman, aksen warna di sidebar dan header, serta layar loading bertema setelah masuk. Administrator memilih tema bawaan untuk semua orang dan untuk halaman masuk. Setiap orang tetap dapat mengikutinya, mematikannya, atau memilih tema lain untuk dirinya sendiri di `Settings`.

## Siapa yang menggunakan

Administrator. Menu `Control Center` › `Seasonal Theme` hanya untuk role administrator. Pengguna lain memilih tema untuk dirinya sendiri di `Settings` › `Appearance` (lihat bagian terakhir topik ini).

## Sebelum Anda mulai

- Tema yang dapat dipilih adalah `Off`, `Christmas`, `New Year`, `Ramadan`, `Eid (Lebaran)`, `Lunar New Year`, dan `Independence Day`. Nama tema tampil sesuai bahasa masing-masing pengguna (misalnya `Natal`, `Tahun Baru`, `Imlek`, dan `HUT RI` dalam bahasa Indonesia).
- Tentukan apakah tema selalu aktif (manual) atau mengikuti tanggal (jadwal otomatis).
- Tanggal Ramadan, Lebaran, dan Imlek berubah setiap tahun, jadi isi sendiri tanggal tepatnya di jadwal.
- Untuk memakai musik sendiri, siapkan berkas audio (WAV, MP3, OGG, atau AAC, maksimal 3 MB).

## Langkah demi langkah

### Mengatur tema bawaan

1. Buka `Control Center` › `Seasonal Theme`.
2. Di bawah `Activation`, pilih mode:
   - `Manual`: tema yang Anda pilih selalu aktif.
   - `Automatic schedule`: tema mengikuti rentang tanggal yang Anda daftarkan.
3. Pada mode manual, pilih kartu tema yang diinginkan di bawah `Theme`. Pilih `Off` bila tidak ingin ada tema. Baris `Active today` menunjukkan tema yang aktif saat ini.
4. Pada mode otomatis, pakai `Schedule` dan `Add period` untuk tiap periode: pilih tema, `Start date`, dan `End date`. Pakai `Remove` untuk menghapus sebuah periode. Anda dapat menambah hingga 20 periode.
5. Di bawah `Background music`, pilih lagu (atau `None (no background music)`), pakai `Preview` untuk mendengarkan, dan atur `Volume`.
6. Pilih `Save settings`.

### Menambahkan musik sendiri

1. Di halaman yang sama, di bawah `Upload a new track`, pilih berkas audio lalu pilih `Upload`.
2. Setelah terunggah, pilih berkas baru itu di daftar musik lalu pilih `Save settings` agar aktif.

## Aturan yang perlu diketahui

- **Siapa yang dapat mengelola.** Hanya pengguna dengan menu `Seasonal Theme`, yaitu role administrator secara bawaan. Pengguna lain hanya dapat memilih tema untuk dirinya sendiri di `Settings`.
- **Urutan prioritas.** Pilihan pribadi di `Settings` didahulukan. Jika memilih `Default`, orang itu mendapat tema administrator. Jika memilih `Off` atau tema tertentu, pilihan itu menang atas administrator untuk orang itu saja.
- **Mode manual.** Tema yang dipilih selalu aktif setiap hari. Periode yang sudah diisi tetap tersimpan tetapi diabaikan.
- **Mode jadwal otomatis.** Setiap periode berisi tema, tanggal mulai, dan tanggal selesai. Kedua tanggal ikut dihitung. Pada hari di luar semua periode, tidak ada tema yang tampil.
- **Periode yang tumpang tindih.** Jika dua periode mencakup hari yang sama, yang tertulis lebih dulu menang.
- **Batas dan validasi.** Jadwal berisi paling banyak 20 periode. Setiap periode wajib punya tema, tanggal mulai, dan tanggal selesai, dan tanggal selesai tidak boleh sebelum tanggal mulai, jika tidak halaman menampilkan galat dan tidak ada yang disimpan. Periode tidak dapat memakai `Off`.
- **Halaman masuk.** Belum ada pengguna yang masuk, jadi halaman masuk itu sendiri selalu menampilkan tema administrator (manual atau sesuai jadwal).
- **Yang diubah oleh tema.** Dekorasi di halaman, warna sidebar, header, tombol, dan tautan, serta layar loading setelah masuk. Tombol yang menghapus, menolak, atau memperingatkan tetap merah agar maknanya tidak berubah.
- **Layar loading.** Setelah berhasil masuk, adegan 3D beranimasi diputar sekitar tiga detik sebelum dashboard terbuka. Adegan mengikuti tema orang yang masuk, jadi orang yang memilih tema sendiri melihat adegan tema itu, dan orang yang memilih `Off` tidak melihat adegan. Setiap tema punya adegannya sendiri: Natal menampilkan kereta luncur Sinterklas terbang di atas desa bersalju pada malam hari, Tahun Baru menampilkan kembang api di atas kota dan sungainya, Ramadan menampilkan bulan sabit di atas masjid berkubah dengan lentera gantung, Lebaran menampilkan keluarga yang saling bersalaman di halaman saat senja di bawah ketupat gantung, Imlek menampilkan naga emas di antara lampion, dan HUT RI menampilkan bendera Merah Putih naik sementara orang-orang memberi hormat.
- **Perangkat tanpa 3D.** Jika perangkat atau browser tidak dapat menggambar 3D, atau pengaturan reduced motion aktif, yang tampil adalah versi diam yang tenang dan proses masuk tidak pernah tertunda. Perangkat berdaya rendah, atau yang mulai lambat, menggambar adegan dengan kualitas lebih rendah agar tetap lancar.
- **Hiasan navbar.** Ornamen kecil ada di dua pojok luar bilah atas dan beberapa bandul mungil menjuntai dari tepi bawahnya, berbeda untuk tiap tema: ranting pinus dengan beri, lonceng, dan holly untuk Natal; pita melingkar dan letusan kembang api untuk Tahun Baru; bulan sabit, lentera, dan siluet masjid untuk Ramadan; janur dan ketupat untuk Lebaran; lampion merah dan ranting bunga plum untuk Imlek; serta bendera bersilang dengan umbul-umbul pendek untuk HUT RI. Hiasan tidak pernah menutupi judul halaman, tombol, atau teks, tidak dapat diklik, tidak mengubah ukuran bilah atas, dan disembunyikan saat mencetak. Bila `Show animations` aktif dan perangkat tidak memakai reduced motion, tiap bagian bergerak dengan caranya sendiri (berayun, berkedip, meletus, berkibar, berpendar); selain itu diam.
- **Animasi.** Dekorasi yang bergerak mengikuti pengaturan `Show animations` masing-masing orang dan pengaturan reduced motion pada perangkatnya. Jika salah satunya mati, dekorasi diam.
- **Musik latar.** Hanya administrator yang memilih lagu dan volumenya, dan berlaku untuk semua orang. Pengguna tidak dapat memilih lagu. Pengguna hanya dapat membisukannya di layarnya sendiri, dan pilihan itu tidak tersimpan untuk pengguna lain.
- **Preferensi yang belum ada.** Orang yang belum pernah menyimpan preferensinya mengikuti `Default`, sehingga melihat tema administrator.
- **Warna aksen.** Selama tema musiman aktif bagi seseorang, warna sidebar, tombol, dan tautan mengikuti tema. `Accent color` yang dipilih di `Settings` tampil di pratinjau tetapi kembali ke warna tema setelah halaman dimuat ulang. Untuk mempertahankan warna aksen sendiri, orang itu mengatur `Seasonal theme` ke `Off`.
- **Audit.** Penyimpanan tema tercatat di audit log.

## Apa yang terjadi selanjutnya

Setelah Anda menyimpan, orang yang memakai `Default` melihat tema baru saat halaman dimuat berikutnya. Orang yang memilih tema sendiri tidak melihat perubahan. Pada mode otomatis, tema mulai dan berakhir sendiri pada tanggal yang Anda isi, tanpa perlu menyimpan lagi.

## Pertanyaan umum

**Saya memilih tema tetapi tidak ada yang berubah bagi saya.** Pilihan pribadi Anda di `Settings` › `Appearance` › `Seasonal theme` mungkin `Off` atau tema lain. Ubah ke `Default` untuk mengikuti administrator.

**Jadwal aktif tetapi tidak ada tema hari ini.** Hari ini berada di luar semua periode, atau mode `Manual` dengan pilihan `Off`. Periksa `Active today`, tanggalnya (kedua ujung ikut dihitung), dan modenya.

**Dua periode tumpang tindih, mana yang tampil?** Baris yang tertulis lebih dulu.

**Mengapa gerakan tidak muncul?** `Show animations` mungkin mati di `Settings` › `Appearance`, atau perangkat memakai pengaturan reduced motion.

**Bisakah satu orang mematikan musik untuk semua?** Tidak. Pengguna hanya dapat membisukannya di layarnya sendiri.

### Cara setiap orang memilih temanya sendiri

1. Buka `Settings` › `Appearance`.
2. Cari `Seasonal theme`. Sebuah keterangan menunjukkan tema administrator yang aktif saat ini.
3. Pilih `Default` untuk mengikuti administrator, `Off` untuk tanpa tema, atau satu tema untuk diri sendiri.
4. Pilih `Save changes`.

<!-- sources: routes/web.php, app/Support/SeasonalThemes.php, app/Support/GlobalSeasonalTheme.php, app/Support/SeasonalThemeResolver.php, app/Http/Controllers/SeasonalThemeSettingsController.php, app/Http/Controllers/SettingsController.php, resources/views/admin/seasonal-theme-settings.blade.php, resources/views/partials/seasonal-navbar.blade.php, resources/views/settings/index.blade.php, lang/id/ui.php -->
