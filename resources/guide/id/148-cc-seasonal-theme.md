---
key: cc-seasonal-theme
chapter: control-center
order: 27
title: Seasonal Theme
summary: Pilih tampilan perayaan untuk EcoSystem, aktifkan secara manual atau dengan jadwal tanggal, atur musik latar, dan lihat bagaimana setiap orang dapat menimpanya.
requires: [control-center.seasonal-theme]
updated: 2026-11-20
---
Tema musiman memberi EcoSystem tampilan perayaan untuk hari besar: dekorasi di halaman, aksen warna di sidebar dan header, serta layar loading bertema setelah masuk. Administrator memilih tema bawaan untuk semua orang dan untuk halaman masuk. Setiap orang tetap dapat mengikutinya, mematikannya, atau memilih tema lain untuk dirinya sendiri di `Settings`.

## Siapa yang menggunakan

Administrator. Menu `Control Center` › `Seasonal Theme` hanya untuk role administrator. Pengguna lain memilih tema untuk dirinya sendiri di `Settings` › `Appearance` (lihat [[my-seasonal-theme]]).

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
3. Pada mode manual, pilih kartu tema yang diinginkan di bawah `Tema`. Pilih `Off` bila tidak ingin ada tema. Baris `Active today` menunjukkan tema yang aktif saat ini.
4. Pada mode otomatis, pakai `Schedule` dan `Add period` untuk tiap periode: pilih tema, `Start date`, dan `End date`. Pakai `Remove` untuk menghapus sebuah periode. Anda dapat menambah hingga 20 periode.
5. Di bawah `Background music`, pilih lagu (atau `None (no background music)`), pakai `Preview` untuk mendengarkan, dan atur `Volume`.
6. Pilih `Save settings`.

### Mengatur tema terpisah untuk halaman login dan layar loading

1. Di halaman yang sama, cari kartu `Login and auth pages`.
2. Di bawah `Tema`, pilih `Ikuti tema global` (bawaan), `Off`, atau satu tema.
3. Di bawah `Berlaku untuk`, pilih `Halaman login dan loading screen`, `Halaman login saja`, atau `Loading screen setelah login saja`.
4. Pilih `Save settings`.

### Melihat pratinjau layar loading sebuah tema

1. Buka `Control Center` › `Seasonal Theme` (atau `Settings` › `Appearance` untuk tema Anda sendiri). Setiap kartu tema menampilkan gambar diam kecil dari layar loading-nya.
2. Pilih kartu tema yang ingin dilihat.
3. Pilih `Play preview`. Pratinjau layar penuh terbuka dan memutar adegan loading selama beberapa detik.
4. Untuk keluar lebih awal, pilih `Close preview`, tekan `Esc`, atau klik di mana saja pada pratinjau. Pratinjau juga menutup sendiri setelah sekitar enam detik.

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
- **Halaman masuk.** Belum ada pengguna yang masuk, jadi halaman masuk itu sendiri menampilkan tema global administrator (manual atau sesuai jadwal), kecuali kartu `Login and auth pages` menetapkan tema terpisah untuknya.
- **Tema login terpisah.** Kartu `Login and auth pages` berisi tema (`Ikuti tema global`, `Off`, atau satu tema) dan cakupan. Dengan `Ikuti tema global` tidak ada yang berubah: halaman mengikuti tema global dan layar loading mengikuti tema pilihan tiap orang. Dengan pilihan lain, `Halaman login dan loading screen` menerapkannya ke keduanya, `Halaman login saja` menerapkannya ke halaman masuk, lupa password, verifikasi dua langkah, dan ganti password serta membiarkan layar loading seperti semula, dan `Loading screen setelah login saja` menerapkannya ke layar loading serta membiarkan halaman di tema global. Memilih `Off` di sini mematikan tema pada bagian yang dipilih.
- **Siapa yang dapat mengubah tema login.** Hanya pengguna dengan menu `Seasonal Theme`. Pengguna tidak dapat mengubahnya di `Settings`. Bila tema login ditetapkan untuk layar loading, tema itu menggantikan pilihan pribadi tiap orang hanya untuk layar tersebut. Dashboard, sidebar, navbar, dan musik tidak terpengaruh dan tetap mengikuti pilihan orang itu atau tema global. Layar loading hanya muncul setelah masuk dengan password. Langkah verifikasi dua langkah dan masuk dengan Microsoft langsung ke dashboard tanpa layar tersebut.
- **Layar loading.** Setelah masuk dengan password, adegan 3D beranimasi diputar sekitar tiga detik sebelum dashboard terbuka. Kecuali tema login terpisah ditetapkan untuk layar loading, adegan mengikuti tema orang yang masuk, dan orang yang memilih `Off` tidak melihat adegan. Tombol yang menghapus, menolak, atau memperingatkan tetap merah pada tema apa pun. Isi adegan tiap tema dijelaskan di [[my-seasonal-theme]].
- **Pratinjau layar loading.** `Play preview` bersifat opsional dan hanya berjalan saat Anda menekannya. Sebelum itu halaman hanya mengunduh gambar diam kecil pada kartu tema, tidak pernah berkas 3D-nya. Pratinjau menampilkan adegan dari kartu tema yang dipilih (`Default` menampilkan tema global saat ini, dan tema tanpa layar loading seperti `Off` tidak dapat dipratinjau), berjalan paling lama sekitar enam detik, ditutup dengan `Close preview`, `Esc`, atau klik, dan hanya satu pratinjau yang dapat terbuka pada satu waktu. Pratinjau tidak memasukkan Anda ke akun, tidak mengubah pilihan yang tersimpan, dan tidak memakai data tambahan dari server. Pada perangkat yang tidak dapat menggambar 3D, dengan Save-Data aktif, koneksi lambat (2G atau 3G), atau reduced motion, yang tampil adalah gambar diam. Rincian untuk pengguna dari `Settings` ada di [[my-seasonal-theme]].
- **Musik latar.** Hanya administrator yang memilih lagu dan volumenya, dan berlaku untuk semua orang. Pengguna tidak dapat memilih lagu. Pengguna hanya dapat membisukannya di layarnya sendiri, dan pilihan itu tidak tersimpan untuk pengguna lain.
- **Audit.** Penyimpanan tema tercatat di audit log.

## Apa yang terjadi selanjutnya

Setelah Anda menyimpan, orang yang memakai `Default` melihat tema baru saat halaman dimuat berikutnya. Orang yang memilih tema sendiri tidak melihat perubahan. Pada mode otomatis, tema mulai dan berakhir sendiri pada tanggal yang Anda isi, tanpa perlu menyimpan lagi.

## Pertanyaan umum

**Saya memilih tema tetapi tidak ada yang berubah bagi saya.** Pilihan pribadi Anda di `Settings` › `Appearance` › `Seasonal theme` mungkin `Off` atau tema lain. Ubah ke `Default` untuk mengikuti administrator.

**Jadwal aktif tetapi tidak ada tema hari ini.** Hari ini berada di luar semua periode, atau mode `Manual` dengan pilihan `Off`. Periksa `Active today`, tanggalnya (kedua ujung ikut dihitung), dan modenya.

**Dua periode tumpang tindih, mana yang tampil?** Baris yang tertulis lebih dulu.


<!-- sources: routes/web.php, app/Support/SeasonalThemes.php, app/Support/GlobalSeasonalTheme.php, app/Support/SeasonalThemeResolver.php, app/Http/Controllers/SeasonalThemeSettingsController.php, app/Http/Controllers/SettingsController.php, resources/views/admin/seasonal-theme-settings.blade.php, resources/views/partials/seasonal-navbar.blade.php, resources/views/settings/index.blade.php, resources/views/partials/seasonal-preview.blade.php, lang/id/ui.php -->
