---
key: cc-login-log
chapter: control-center
order: 21
title: Login Log
summary: Lihat login atau logout terakhir setiap karyawan, lengkap dengan perangkat, alamat IP, dan lokasi.
requires: [control-center.login-log]
updated: 2026-11-02
---
`Login Log` menjawab "siapa yang sedang login atau terakhir terlihat, dan dari mana". Halaman ini menampilkan satu baris per karyawan: login atau logout terbarunya.

## Siapa yang menggunakan

Administrator, dan role mana pun yang diberi menu `Control Center` › `Login Log` di Menu Access.

> [!IMPORTANT]
> Halaman ini menampilkan kapan dan di mana orang bekerja. Pakai hanya untuk administrasi dan keamanan, dan jangan bagikan isinya.

## Sebelum Anda mulai

- Percobaan gagal tidak tercantum di sini. Untuk menyelidikinya, pakai `Activity Log`.
- Waktu ditampilkan dalam WIB. Halaman ini hanya untuk dibaca.

## Langkah demi langkah

![Halaman Login Log dengan satu baris per karyawan dan status login terakhirnya](images/guide/cc-login-log.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Control Center` › `Login Log`.
2. Baca setiap baris: `Employee`, `Status` (`Login` atau `Logout`), `Device`, `IP Address`, `Location`, `Time (WIB)`, dan `Last Login` dalam kata-kata, misalnya "Last login 5 hours ago".
3. Untuk mencari satu orang, klik ikon corong pada `Employee` lalu ketik namanya. Untuk melihat hanya yang sedang login atau hanya yang sudah logout, pakai filter `Status`.
4. Klik `Employee`, `Status`, `Time (WIB)`, atau `Last Login` untuk mengurutkan. Ubah `25 / page` untuk menampilkan lebih banyak baris. `Clear filters` mengembalikan semuanya.

## Apa yang terjadi selanjutnya

Tidak ada yang berubah. Untuk mengakhiri sesi seseorang, buka `Active Sessions`.

## Pertanyaan umum

**Seorang karyawan berstatus `Login` padahal tidak sedang bekerja.** `Login` berarti kejadian terakhir yang tercatat adalah login. Orang yang menutup peramban tanpa logout tetap `Login` sampai sesinya berakhir.

**Di mana customer?** Halaman ini hanya mendaftar karyawan. Login customer ada di `Activity Log`.

<!-- sources: routes/web.php, app/Http/Controllers/LoginLogController.php, resources/views/admin/login-log.blade.php -->
