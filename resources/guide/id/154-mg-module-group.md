---
key: mg-module-group
chapter: management
order: 14
title: Module Group
summary: Kelompokkan module yang saling terkait (misalnya Logistic atau Finance) agar dapat dipakai sebagai satu kesatuan.
requires: [management.module-groups]
updated: 2026-11-02
---
`Management` › `Module Group` mengumpulkan module yang saling terkait ke dalam grup bernama, seperti `Logistic` untuk `MM`, `SD`, dan `PM`. Grup memudahkan membaca dan memfilter pekerjaan per area. Module-nya sendiri disimpan di `Management` › `Qualification` (Master Module).

## Siapa yang menggunakan

Administrator dan project administrator, dan role mana pun yang diberi menu `Management` › `Module Group` di Menu Access.

## Sebelum Anda mulai

- Buat module dulu di halaman Master Module, lalu kelompokkan di sini.
- Sebuah module dapat muncul di grup yang Anda pilih. Tentukan pengelompokannya dulu agar nama tetap konsisten.

## Langkah demi langkah

![Daftar Module Group dengan module setiap grup tampil sebagai tag kecil](images/guide/module-group.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Management` › `Module Group`. Setiap baris menampilkan `Group Name`, `Description`, `Modules` di dalamnya sebagai tag, dan `Status`.
2. Cari grup dengan kotak pencarian (`Search group name...`) atau daftar status (`All Status`).
3. Untuk menambah grup, pilih `Add Group`. Isi `Group Name` (wajib), `Description` bila perlu, pilih `Modules`, biarkan `Active` tercentang, lalu pilih `Save`.
4. Untuk mengubah grup, pilih `Edit`. Untuk menghapusnya, pilih `Delete` lalu konfirmasi.

## Apa yang terjadi selanjutnya

Grup langsung tersedia di mana pun module dikelompokkan. Menghapus grup tidak menghapus module di dalamnya.

## Pertanyaan umum

**Sebuah module tidak muncul di daftar pilihan.** Module itu belum ada di `Management` › `Qualification`. Tambahkan di sana dulu.

**Bisakah satu module masuk dua grup?** Bisa. Tambahkan ke masing-masing grup.

<!-- sources: resources/views/management/module-groups/index.blade.php, app/Http/Controllers/ModuleGroupController.php -->
