---
key: cc-backup-export
chapter: control-center
order: 25
title: Backup & Export
summary: Buat backup database, ekspor data ke CSV atau ZIP, dan impor data massal dengan urutan yang benar.
requires: [control-center.backup]
updated: 2026-11-02
---
`Backup & Export` adalah kotak peralatan administrator untuk mengeluarkan data dari EcoSystem dan memasukkan data: backup database penuh, ekspor CSV atau ZIP, dan impor massal.

## Siapa yang menggunakan

Administrator, dan role mana pun yang diberi menu `Control Center` › `Backup & Export` di Menu Access.

> [!IMPORTANT]
> Backup dan ekspor berisi data customer dan karyawan yang sebenarnya. Simpan berkasnya dengan aman, jangan dikirim lewat email, dan hapus salinan yang tidak lagi diperlukan.

## Sebelum Anda mulai

- Impor mengubah data dan sulit dibatalkan. Unduh templatenya dulu, isi, dan coba dengan beberapa baris.
- Impor sesuai urutan di halaman: data master dulu, lalu detail yang bergantung padanya (lihat langkah 8).

## Langkah demi langkah

![Halaman Backup & Export dengan kartu Database Backup dan kartu ekspor serta impor Ticket Data](images/guide/cc-backup-export.png)

*Tangkapan layar dengan data contoh.*

### Backup database

1. Buka `Control Center` › `Backup & Export`.
2. Di `Database Backup`, pilih `Create Backup Now`. Salinan penuh dibuat di server dan tercantum di `Existing Backups`.
3. Pilih `Refresh` untuk memperbarui daftar dan pakai tautan unduh pada berkas. Bila server tidak bisa membuat backup, muncul pesan `mysqldump not found on this server`: hubungi tim teknis.

### Ekspor ticket

4. Di `Ticket Data`, pilih `Year` dan `Month` (atau `All Months`) lalu pilih `Download CSV`. Berkas berisi nomor ticket, subjek, status, prioritas, skala, tipe, customer, PIC, mandays, dan kolom SLA.
5. Di `Ticket Migration (Full ZIP)` pakai `Download ZIP` untuk mengekspor ticket beserta riwayat chat dan lampirannya. Untuk periode besar bisa memakan beberapa menit.

### Impor data

6. Pilih `Download Template (.xlsx)` pada kartu yang Anda perlukan, isi berkasnya, taruh di `Drop CSV here or browse`, lalu pilih tombol impor. Untuk ticket, baris dengan nomor ticket baru membuat ticket dan baris dengan nomor yang sudah ada memperbaruinya. Baris tanpa nomor ticket dilewati.
7. Untuk memulihkan ZIP buatan `Ticket Migration`, pakai `Import ZIP`. Ticket yang sudah ada diperbarui dan pesan yang sudah ada tidak digandakan.
8. Untuk impor lain, ikuti langkah di halaman secara berurutan: `Master Data` (`Employee Data`, `Customer Data`), lalu `Employee Qualification`, `Delivery Support`, `Customer Contact Person`, lalu `Ticket Members Import`, kemudian `Resolution Days` dan `Timesheet`.
9. Bila ada baris yang gagal, baca `Import Errors` di bagian bawah halaman. Perbaiki baris itu dan impor lagi.

## Apa yang terjadi selanjutnya

Ekspor terunduh ke komputer Anda. Impor langsung membuat atau memperbarui data, dan tercatat di `Audit Log`.

## Pertanyaan umum

**Impor berkata customer atau karyawan tidak ditemukan.** Impor data master dulu, atau perbaiki ejaan di berkas Anda.

**Bisakah saya mengimpor ticket dari sistem lama?** Bisa. `Import Tiket dari Sistem Lama (API)` menarik ticket dan riwayat chat dari daftar nomor ticket. Pakai hanya dengan bantuan tim teknis.

<!-- sources: routes/web.php, app/Http/Controllers/AdminBackupController.php, resources/views/admin/backup.blade.php -->
