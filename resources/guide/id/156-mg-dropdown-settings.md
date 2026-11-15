---
key: mg-dropdown-settings
chapter: management
order: 16
title: Dropdown Settings
summary: Kelola daftar di balik dropdown Employee Information, seperti Position, Division, dan Department.
requires: [management.employee.dropdown-settings, management.employee.dropdown-settings.manage]
updated: 2026-11-02
---
`Management` › `Master Employee Settings` › `Dropdown Settings` mengatur pilihan yang tampil di formulir karyawan: Department, Division, Employee Group, Employee Subgroup, Personnel Area, Personnel Subarea, Position, dan daftar lain yang Anda tambah.

## Siapa yang menggunakan

Administrator dan HR, dan role mana pun yang diberi menu `Management` › `Dropdown Settings` di Menu Access.

## Sebelum Anda mulai

- Sebuah value disimpan di data karyawan persis seperti yang ditulis. Ketik persis seperti yang seharusnya tampil, dengan ejaan dan huruf besar yang benar.
- Mematikan satu daftar penuh menyembunyikan kolom itu dari formulir Employee, bukan hanya mengosongkan pilihannya.

## Langkah demi langkah

![Dropdown Settings dengan daftar dropdown di kiri dan value Department di kanan](images/guide/mgmt-dropdown-settings.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Management` › `Dropdown Settings`. Sisi kiri mendaftar setiap dropdown, lengkap dengan kode dan jumlah value-nya.
2. Pilih satu daftar. Value-nya tampil di kanan dengan `Order`, `Value`, dan `Status`. Filter dengan `Search value` dan daftar `All Status`, dan `Clear` untuk mengulang.
3. Untuk menambah value, pilih `Add Value`, ketik `Value`, biarkan `Active` tercentang (ditawarkan di dropdown) lalu `Save`. Sebuah value harus baru di dalam daftarnya.
4. Pakai tanda panah untuk memindahkan value ke atas atau bawah. Urutannya adalah urutan yang dilihat orang.
5. Pakai pensil untuk mengganti nama atau menonaktifkan value agar tidak ditawarkan lagi. Pakai tempat sampah untuk menghapusnya.
6. Untuk menambah daftar baru, pilih `New Dropdown List`. Isi `Name` (label di formulir), `Code` yang unik, dan `Description`.
7. Untuk mengubah atau menghapus satu daftar penuh, pakai pensil atau tempat sampah di samping judulnya.

## Aturan yang perlu diketahui

- Membuka halaman memerlukan `Dropdown Settings`. Menambah, mengubah, atau menghapus daftar dropdown maupun nilainya juga memerlukan fungsi `Manage Dropdown Lists & Values` di `Menu Access`. Tanpa fungsi itu perubahan ditolak, meskipun permintaan dikirim langsung. Membaca daftar, yang dilakukan form Employee, tetap terbuka bagi semua pengguna yang login.

## Apa yang terjadi selanjutnya

Perubahan langsung ditawarkan di formulir Employee. Karyawan yang sudah memegang value lama tetap memakainya di datanya.

## Pertanyaan umum

**Value yang saya tambah tidak muncul.** Pastikan statusnya `Active` dan daftarnya sendiri menyala.

**Kode dinyatakan sudah ada.** Setiap daftar membutuhkan `Code` unik. Pilih yang lain.

<!-- sources: resources/views/management/employee/dropdown-settings/index.blade.php, app/Http/Controllers/DropdownConfigController.php -->
