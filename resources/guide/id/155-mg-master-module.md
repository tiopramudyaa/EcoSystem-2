---
key: mg-master-module
chapter: management
order: 15
title: Qualification (Master Module)
summary: Kelola daftar module tempat konsultan dapat dikualifikasikan, beserta lead dan membernya.
requires: [management.employee.qualification]
updated: 2026-11-02
---
`Management` › `Master Employee Settings` › `Qualification` membuka halaman bernama Master Module. Ini adalah daftar induk module (misalnya `ABAP`, `FI`, `MM`) tempat konsultan dikualifikasikan dan dasar penugasan ticket.

## Siapa yang menggunakan

Administrator, dan role mana pun yang diberi menu `Management` › `Qualification` di Menu Access.

## Sebelum Anda mulai

- Lead module adalah orang yang bertanggung jawab atas ticket module itu, jadi jaga daftar lead tetap akurat.
- Menghapus module tidak sepele: data kualifikasi yang memakainya kehilangan acuan module.

## Langkah demi langkah

![Daftar Master Module dengan grup, lead, member, dan status setiap module](images/guide/mgmt-master-module.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Management` › `Qualification`. Setiap baris menampilkan `Module Name`, `Description`, `Groups` tempatnya berada, `Leads`, `Members`, dan `Status`.
2. Cari module dengan kotak pencarian (`Search module name...`) atau daftar status (`All Status`: `Active` atau `Inactive`).
3. Untuk menambah module, pilih `Add Module`. Isi `Module Name` (wajib) dan `Description` bila perlu, biarkan `Active` tercentang, lalu pilih `Save`.
4. Untuk mengubah module, pilih ikon pensil pada barisnya.
5. Untuk mengatur lead, pilih `Manage` di kolom `Leads`. Cari karyawan, pilih `Add Lead`, dan hapus lead dari `Current Leads`. Pilih `Close` bila selesai.
6. Untuk melihat siapa yang berkualifikasi di module itu, pilih `View` di kolom `Members`.
7. Untuk menghapus module, pilih ikon tempat sampah lalu konfirmasi `Delete Module?`.

## Apa yang terjadi selanjutnya

Module ditawarkan di mana pun konsultan dan ticket dikaitkan dengan module. Keanggotaan grup diatur di `Management` › `Module Group`.

## Pertanyaan umum

**Judul halaman tertulis Master Module, bukan Qualification.** Itu halaman yang sama. Menu memakai nama pertama dan halaman memakai nama kedua.

**Seseorang tidak bisa ditambahkan sebagai lead.** Kemungkinan dia sudah menjadi lead module ini, daftar menandainya `(already a lead)`.

<!-- sources: resources/views/management/employee/qualification/index.blade.php, app/Http/Controllers/ModuleController.php -->
