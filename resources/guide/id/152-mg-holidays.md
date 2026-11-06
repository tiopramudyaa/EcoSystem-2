---
key: mg-holidays
chapter: management
order: 12
title: Holidays
summary: Kelola hari libur nasional, cuti bersama, dan hari libur kustom yang dipakai kalender sistem dan pemilih tanggal.
requires: [management.holidays]
updated: 2026-11-02
---
`Management` › `Holidays` menyimpan daftar hari tidak kerja yang dipakai EcoSystem pada kalender dan pemilih tanggal. Perbarui setiap tahun.

## Siapa yang menggunakan

Administrator, dan role mana pun yang diberi menu `Management` › `Holidays` di Menu Access.

## Sebelum Anda mulai

- Ketahui tanggal yang tepat, nama, dan tipenya: `National Holiday`, `Collective Leave`, atau `Custom Holiday` (misalnya ulang tahun perusahaan).
- Dua hari libur dengan tanggal dan nama yang sama tidak diizinkan.

## Langkah demi langkah

![Daftar Holidays dengan tanggal, hari, nama, tipe, status, dan tombol Edit serta Delete](images/guide/mgmt-holidays.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Management` › `Holidays`. Setiap baris menampilkan tanggal, hari, nama, tipe, dan status.
2. Persempit daftar dengan daftar tahun (`All Years`) dan kotak pencarian `Search holiday name...`.
3. Untuk menambah hari, pilih `Add Holiday`.

![Jendela Add Holiday dengan tanggal, nama, tipe, dan centang Active](images/guide/mgmt-holiday-add.png)

*Tangkapan layar dengan data contoh.*

4. Isi `Date`, `Name`, dan `Type`. Biarkan `Active (applied to calendar & date pickers)` tercentang agar hari itu berlaku, atau hapus centang untuk menyimpan entri tanpa memberlakukannya. Pilih `Save`.
5. Untuk mengubah hari, pilih `Edit`. Untuk menghapusnya selamanya, pilih `Delete` lalu konfirmasi.

## Apa yang terjadi selanjutnya

Hari libur aktif langsung berlaku di kalender sistem dan pemilih tanggal. Yang tidak aktif tetap ada di daftar tetapi tampil redup dan tidak berlaku.

## Pertanyaan umum

**Muncul pesan hari libur sudah ada.** Hari libur dengan tanggal dan nama yang sama sudah ada di daftar. Ubah entri itu saja.

**Tanggal sebuah libur pemerintah berubah.** Ubah entrinya dan ganti `Date`. Tidak perlu dihapus dulu.

<!-- sources: resources/views/management/holidays/index.blade.php, app/Http/Controllers/HolidayManagementController.php -->
