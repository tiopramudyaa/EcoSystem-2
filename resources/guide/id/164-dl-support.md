---
key: dl-support
chapter: delivery
order: 20
title: Support
summary: Temukan delivery support, saring dengan kartu ringkasan, tambah yang baru, ekspor daftar, dan ikuti daftar planning.
requires: [delivery.support, delivery-support.add-new, delivery-support.export, delivery-support.delete-support]
updated: 2026-11-02
---
`Delivery` › `Support` mendaftar semua delivery support: kontrak dukungan tempat ticket dikerjakan (misalnya AMS, MO, atau hypercare). Setiap ticket milik satu delivery support.

## Siapa yang menggunakan

Helpdesk, support head dan manager, staf project dan RPMO, serta role mana pun yang diberi menu `Delivery` › `Support` di Menu Access. Tombol seperti `Add Delivery Support` hanya muncul untuk role yang diberi izinnya.

## Sebelum Anda mulai

- Ketahui customer, tipe support, periode, dan tim kontrak.
- Customer harus ada di `Master` › `Business Partner`, dan tipe support berasal dari `Management` › `Support Type`.

## Langkah demi langkah

![Daftar Delivery Support dengan kartu ringkasan dan tabel support](images/guide/dlv-support-list.png)

*Tangkapan layar dengan data contoh.*

### Cari support

1. Buka `Delivery` › `Support`. Tab di atas adalah `Support` (daftar) dan `Planning`.
2. Baca kartu `SUMMARY`: `Total Delivery Support`, `In Progress`, `Completed`, `Not Started`, `Ending ≤ 30 days`, `Past End Date`, `Upcoming`, dan `No Timeline`. Klik kartu untuk menyaring.
3. Ketik di kotak pencarian (client, ID ticket, atau deskripsi) atau pakai ikon corong di judul kolom. Kolomnya antara lain `Customer`, `Support Name`, `Support Type`, `IO Number`, `Progress`, dan `Timeline`.
4. Klik sebuah item untuk membuka halaman detailnya. Pilih `Export Excel` untuk mengunduh daftar.
5. Tab `Planning` menampilkan `Support Planning List`: setiap support dengan progres, status, jumlah aktivitas selesai, dan timeline-nya.

### Tambah support

![Formulir Create New Support dengan informasi dasar, timeline, tim, dan approval](images/guide/dlv-support-create.png)

*Tangkapan layar dengan data contoh.*

1. Pilih `Add Delivery Support`.
2. Di `Basic Information` isi `Support Name`, `Client`, dan `Type` (wajib). Tipe menentukan kategori support dan ticket-nya. `Support Method`, `Modules`, `IO Number` (unik, opsional), dan `Vendor` (opsional, hanya business partner bertipe Vendor) bersifat opsional.
3. Di `Timeline` isi `Start Date`, `End Date`, `Resolution Estimated`, dan `Service Window`, yaitu jam operasional per hari.
4. Di `Team Assignment` pilih `Delivery Owner`, `Support Manager` (satu atau lebih), `Co PM`, `Support Admin`, dan `Sales`.
5. Isi `Total Mandays` dan tanggal serta nama `Approval` bila sudah ada, lalu pilih `Create Delivery Support`.

Ticket dihubungkan ke support dari halaman ticket, bukan dari formulir ini.

## Apa yang terjadi selanjutnya

Support muncul di daftar dan dapat dipilih saat menugaskan ticket. Halaman detailnya dijelaskan di topik berikutnya.

## Pertanyaan umum

**Tidak ada tombol `Add Delivery Support`.** Role Anda tidak memiliki `Add Delivery Support`. Minta administrator.

**Nomor IO ditolak.** Nomor IO harus unik di semua delivery support.

<!-- sources: routes/delivery-support.php, resources/views/delivery/support/index.blade.php, resources/views/delivery/support/list/create.blade.php -->
