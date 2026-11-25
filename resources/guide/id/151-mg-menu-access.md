---
key: mg-menu-access
chapter: management
order: 11
title: Menu Access
summary: Telusuri daftar lengkap menu beserta sub-itemnya dengan nama dan slug yang tepat.
requires: [management.permissions]
updated: 2026-11-02
---
`Management` › `Menu Access` adalah daftar rujukan semua hal yang dapat dinyalakan atau dimatikan untuk sebuah role: setiap grup, halaman, dan `function` tingkat tombol, lengkap dengan slug-nya. Anda membacanya di sini dan memberikannya di `Management` › `Role`.

## Siapa yang menggunakan

Administrator, dan role mana pun yang diberi menu `Management` › `Menu Access` di Menu Access.

## Sebelum Anda mulai

- Halaman ini hanya menampilkan daftar. Tidak ada yang Anda lakukan di sini yang mengubah akses siapa pun.
- Untuk memberi atau mencabut, pakai `Menu Access` pada sebuah role di `Management` › `Role`.

## Langkah demi langkah

![Halaman Menu List dengan pohon menu lengkap dan slug setiap item](images/guide/mgmt-menu-access.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Management` › `Menu Access`.
2. Baca kartu di bagian atas: jumlah seluruh menu dan berapa yang berupa grup, halaman, dan function. Lalu baca pohonnya. Ikon tumpukan ungu adalah grup (seperti `Calendar` atau `Reporting`), ikon halaman biru adalah halaman, dan ikon petir adalah function: tombol atau aksi di dalam halaman, misalnya `Create Event`. Sebuah grup juga menampilkan jumlah item di dalamnya.
3. Label abu-abu di kanan setiap baris adalah slug-nya, berupa nama pendek bertitik. Klik untuk menyalinnya, lalu tempel saat melaporkan menu yang hilang kepada administrator.
4. Klik panah pada baris, atau `Expand All` dan `Collapse All`, untuk menampilkan atau menyembunyikan sub-item.
5. Ketik di `Search menu name...` untuk mencari menu. Pakai tombol `All`, `Groups`, `Pages`, dan `Functions` untuk menampilkan satu jenis saja. Pilih `Reset` untuk menghapus pencarian dan filter.

## Aturan yang perlu diketahui

- **Hanya baca.** Halaman ini tidak mengubah apa pun. Menu hanya diberikan dari `Management` › `Role`.
- **Siapa yang boleh membukanya.** Hanya orang yang diberi menu `Management` › `Menu Access` di Menu Access. Orang yang bisa membuka `Management` › `Role` juga menerima daftar yang sama di dalam jendela role, tetapi tidak halaman ini.
- **Hanya menu aktif yang tampil.** Menu yang dimatikan tidak muncul.

## Apa yang terjadi selanjutnya

Tidak ada yang berubah. Buka `Management` › `Role` untuk memberikan menu yang Anda temukan.

## Pertanyaan umum

**Halaman yang saya tahu tidak ada di daftar.** Daftar hanya menampilkan menu aktif. Tanyakan kepada tim teknis bila halaman itu seharusnya ada.

**Mengapa sebagian function berada di bawah halaman?** Itu adalah tombol pada halaman tersebut. Sebuah role bisa membuka halaman tanpa boleh memakainya.

<!-- sources: resources/views/management/permissions/index.blade.php -->
