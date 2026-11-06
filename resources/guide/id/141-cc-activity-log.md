---
key: cc-activity-log
chapter: control-center
order: 20
title: Activity Log
summary: Tinjau setiap login, percobaan gagal, dan logout, lengkap dengan siapa, dari mana, dan memakai perangkat apa.
requires: [control-center.activity-log]
updated: 2026-11-02
---
`Activity Log` menyimpan riwayat akses ke EcoSystem. Setiap baris adalah satu kejadian: login berhasil, percobaan gagal, atau logout. Pakai untuk menjawab "siapa yang login, kapan, dan dari mana".

## Siapa yang menggunakan

Administrator, dan role mana pun yang diberi menu `Control Center` › `Activity Log` di Menu Access.

> [!IMPORTANT]
> Halaman ini menampilkan kapan dan di mana orang bekerja. Pakai hanya untuk administrasi dan keamanan, dan jangan bagikan isinya.

## Sebelum Anda mulai

- Tentukan yang dicari: seseorang, alamat IP, rentang waktu, atau hanya percobaan yang gagal.
- Waktu ditampilkan dalam WIB. Daftar ini hanya untuk dibaca: catatan tidak bisa diubah atau dihapus di sini.

## Langkah demi langkah

![Halaman Activity Log dengan kartu ringkasan dan tabel catatan login](images/guide/cc-activity-log.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Control Center` › `Activity Log`.
2. Baca empat kartu di atas: `Total Records`, `Successful Logins`, `Failed Attempts`, dan `Logouts`. Kartu menghitung semua catatan, bukan hanya yang tampil setelah difilter.
3. Persempit tabel dengan filter di judul kolom:
   - `User`: klik ikon corong lalu ketik nama, alamat IP, browser, atau lokasi.
   - `Status`: pilih `Success`, `Failed`, atau `Logout`.
   - `Time (WIB)`: klik ikon corong, pilih tanggal `From` dan `To`, lalu `Apply`.
4. Klik judul kolom (`User`, `Status`, `IP Address`, `Location`, `Time (WIB)`) untuk mengurutkan. Ubah `25 / page` untuk menampilkan lebih banyak baris.
5. Saat ada filter aktif, tombol `Clear filters` muncul. Pakai untuk mengulang dari awal.

## Apa yang terjadi selanjutnya

Tidak ada yang berubah. Bila ada baris mencurigakan (percobaan gagal dari tempat asing, atau login di jam tak biasa), lanjutkan di `Security Center` atau tanyakan kepada orang yang bersangkutan.

## Pertanyaan umum

**Seseorang mengaku sudah login tetapi tidak ketemu.** Cari dengan namanya atau hapus filter tanggal. Login customer muncul dengan tipe pengguna sendiri.

**Apa bedanya dengan `Login Log`?** `Activity Log` mencantumkan semua kejadian. `Login Log` hanya menampilkan status terakhir tiap karyawan.

<!-- sources: routes/web.php, app/Http/Controllers/ActivityLogController.php, resources/views/admin/activity-log.blade.php -->
