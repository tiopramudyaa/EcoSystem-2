---
key: cc-audit-log
chapter: control-center
order: 22
title: Audit Log
summary: Telusuri siapa yang membuat, mengubah, atau menghapus data di modul mana pun, lengkap dengan nilai sebelum dan sesudah.
requires: [control-center.audit-log]
updated: 2026-11-02
---
`Audit Log` mencatat setiap pembuatan, perubahan, dan penghapusan data di seluruh modul EcoSystem (ticket, customer, role, delivery project, pengaturan, dan lainnya). Pakai untuk menjawab "siapa yang mengubah ini, kapan, dan sebelumnya berisi apa".

## Siapa yang menggunakan

Administrator, dan role mana pun yang diberi menu `Control Center` › `Audit Log` di Menu Access.

> [!IMPORTANT]
> Entri dapat memuat data bisnis. Buka `View` hanya bila perlu, dan jangan bagikan isinya.

## Sebelum Anda mulai

- Ketahui modulnya, datanya (misalnya nomor ticket atau nama customer), atau orang yang mengubahnya.
- Waktu ditampilkan dalam WIB. Daftar ini hanya untuk dibaca: entri tidak bisa diubah atau dihapus.

## Langkah demi langkah

![Halaman Audit Log dengan kartu ringkasan dan tabel perubahan, masing-masing dengan tautan View](images/guide/cc-audit-log.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Control Center` › `Audit Log`.
2. Baca kartunya: `Total Records`, `Created`, `Updated`, dan `Deleted`.
3. Persempit daftar dengan filter di judul kolom:
   - `Activity`: klik ikon corong lalu ketik kata dari deskripsi, data, atau pelakunya.
   - `Module`: pilih satu modul, seperti `Ticket`, `Customer`, atau `Role`.
   - `Event`: pilih `Created`, `Updated`, atau `Deleted`.
   - `Time (WIB)`: pilih tanggal `From` dan `To`, lalu `Apply`.
4. Setiap baris menampilkan deskripsi, data yang berubah, `Module`, `Event`, `Actor`, dan waktunya.
5. Pilih `View` di kolom `Changes` untuk melihat tiap kolom dengan nilai `Before` dan `After`. Untuk data yang dihapus, jendelanya menampilkan nilai sebelum dihapus.
6. Pakai `Clear filters` untuk mengulang dari awal.

## Apa yang terjadi selanjutnya

Tidak ada yang berubah. Jika sebuah perubahan tidak diharapkan, tanyakan dulu kepada pelakunya, lalu perbaiki datanya di modulnya masing-masing.

## Pertanyaan umum

**Sebuah baris tidak punya tautan `View`.** Entri itu tidak menyimpan nilai kolom, misalnya sekadar kejadian biasa.

**Apakah password atau rahasia ikut tampil?** Tidak. Kolom seperti password dan token tidak pernah direkam.

<!-- sources: routes/web.php, app/Http/Controllers/AuditLogController.php, resources/views/admin/audit-log.blade.php -->
