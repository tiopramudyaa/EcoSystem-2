---
key: cc-active-sessions
chapter: control-center
order: 23
title: Active Sessions
summary: Lihat siapa yang sedang login, akhiri sesi (force logout), dan kunci sistem saat deployment.
requires: [control-center.sessions]
updated: 2026-11-10
---
`Active Sessions` mendaftar orang yang sedang login di EcoSystem, satu baris per sesi peramban. Administrator dapat mengakhiri sesi seketika, misalnya saat perangkat hilang atau akun disalahgunakan.

## Siapa yang menggunakan

Administrator, dan role mana pun yang diberi menu `Control Center` › `Active Sessions` di Menu Access.

## Sebelum Anda mulai

- Mengakhiri sesi langsung mengeluarkan orangnya. Pekerjaan yang belum disimpan di halaman terbukanya hilang, jadi beri tahu bila bisa.
- Orang itu bisa login lagi dengan passwordnya. Bila akunnya sendiri harus berhenti, minta administrator memblokirnya di data karyawan.

## Langkah demi langkah

![Halaman Active Sessions dengan satu baris per sesi dan tautan Force Logout](images/guide/cc-active-sessions.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Control Center` › `Active Sessions`.
2. Baca dua kartu: `Active Sessions` dan `Unique Users`. Satu orang dengan beberapa peramban memiliki beberapa sesi.
3. Baca setiap baris: orangnya (nama, kode karyawan, email), `IP Address`, `Location`, `Browser / Device`, `Last Activity`, dan `Status`.
4. Cari sesi dengan kotak pencarian (nama, email, IP, atau lokasi) atau klik `User` atau `Last Activity` untuk mengurutkan.
5. Untuk mengakhiri satu sesi, pilih `Force Logout` pada barisnya. Jendela `Session Detail` terbuka berisi orangnya, IP, lokasi, dan peramban. Pilih `Force Logout` di jendela itu, lalu konfirmasi. `Cancel` menutup jendela tanpa mengakhiri apa pun.
6. Untuk mengakhiri semua sesi lain, pilih `Force Logout All Others` lalu konfirmasi. Sesi Anda sendiri tetap aktif.

### Mengunci sistem (untuk deployment)

Pakai ini saat tidak boleh ada yang login atau mengubah data, misalnya selama deployment.

1. Login dengan akun administrator yang dilindungi, lalu buka `Control Center` › `Active Sessions`.
2. Di kartu `System Lock` paling atas, isi pesan untuk halaman login bila perlu, lalu pilih `Lock System` dan konfirmasi.
3. Semua orang selain administrator yang dilindungi langsung dikeluarkan dan tidak bisa login sampai Anda membuka kunci.
4. Setelah selesai, pilih `Unlock System` dan konfirmasi. Kartu berwarna merah selama sistem terkunci.

## Aturan yang perlu diketahui

- Selama sistem terkunci, hanya akun administrator yang dilindungi yang bisa login atau tetap memakai sistem. Semua orang lain ditolak, baik di aplikasi web maupun di Lite.
- Hanya administrator yang dilindungi yang bisa mengunci atau membuka kunci sistem. Orang lain yang bisa membuka halaman ini melihat status kunci tetapi tidak melihat tombolnya. Ini mencegah sebuah akun mengunci dirinya sendiri.
- Mengunci langsung mengeluarkan semua orang lain yang sedang login. Orang yang entah bagaimana masih terhubung dikeluarkan pada klik berikutnya. Login lewat "remember me" yang tersimpan atau token Lite juga ditolak.
- Orang yang ditolak saat login melihat pesan yang Anda tulis, atau pesan pemeliharaan bawaan. Password yang salah tetap menampilkan galat biasa.
- Kunci tetap aktif sampai ada yang membukanya. Kunci tidak berakhir sendiri dan tetap ada setelah server di-restart.
- Job terjadwal, antrean, dan integrasi yang memakai API key tetap berjalan saat sistem terkunci. Kunci hanya menghentikan orang.
- Setiap kunci dan buka kunci dicatat di `Audit Log` pada modul `Session`.

- Hanya sesi dengan aktivitas dalam 120 menit terakhir yang ditampilkan. Sesi yang lebih lama sudah kedaluwarsa dan tidak muncul.
- Hanya sesi orang yang sudah login yang ditampilkan. Pengunjung di halaman login, atau login yang gagal, tidak muncul.
- Anda tidak bisa mengakhiri sesi sendiri di sini, dan akun sistem yang dilindungi tidak bisa diakhiri sama sekali. `Force Logout All Others` melewati keduanya.
- `Force Logout All Others` hanya mengakhiri sesi yang masih aktif, jadi angka di pesan konfirmasi sesuai dengan baris yang Anda lihat.
- Setiap `Force Logout` dan `Force Logout All Others` dicatat di `Audit Log` pada modul `Session`, lengkap dengan siapa yang melakukannya dan kapan.
- Halaman ini hanya mencakup sesi peramban. Sesi aplikasi lain tidak ditampilkan dan tidak bisa diakhiri.
- Bila daftar gagal dimuat, halaman menampilkan `Failed to load sessions`. Muat ulang halaman untuk mencoba lagi.

## Apa yang terjadi selanjutnya

Orang itu kembali ke halaman login pada klik berikutnya.

## Pertanyaan umum

**Mengapa tidak ada `Force Logout` pada baris saya?** Baris Anda bertanda `You`. Sesi sendiri tidak bisa diakhiri di sini, pakai logout biasa.

**Mengapa sebuah baris bertanda `Protected`?** Akun sistem yang dilindungi tidak bisa dikeluarkan dari halaman ini, dan `Force Logout All Others` melewatinya.

<!-- sources: routes/web.php, app/Http/Controllers/AdminSessionController.php, resources/views/admin/sessions.blade.php -->
