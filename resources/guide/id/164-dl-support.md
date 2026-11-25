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
4. Klik sebuah item untuk membuka halaman detailnya.
5. Tab `Planning` menampilkan `Support Planning List`: setiap support dengan progres, status, jumlah aktivitas selesai, dan timeline-nya.

### Ekspor daftar

1. Pilih `Export Excel` di atas tabel. Tombol ini hanya muncul bila role Anda punya fungsi export untuk Delivery Support.
2. Jendela berjudul `Export Delivery Support` terbuka. Isinya daftar `Template` dan semua kotak centang `Fields`.
3. Centang field yang ingin masuk ke file. Ketik di `Search fields` untuk mencari satu field di daftar panjang. `Select all` mencentang semua field dan `Unselect all` menghapus semua centang.
4. Untuk memakai pilihan ini lagi, ketik nama di bawah `Save these fields as a template`, pilih `Personal` atau `Global`, lalu pilih `Save`. Template itu kemudian muncul di daftar `Template` dengan tanda `(Personal)` atau `(Global)`.
5. Lain kali, buka jendela yang sama dan pilih template di `Template`. Field-nya sudah tercentang. Template `Global` buatan rekan ada di daftar yang sama. Pilih `Delete` untuk menghapus template Anda yang tidak dipakai lagi.
6. Pilih `Export`. File hanya berisi field yang dicentang.

### Tambah support

![Formulir Create New Support dengan informasi dasar, timeline, tim, dan approval](images/guide/dlv-support-create.png)

*Tangkapan layar dengan data contoh.*

1. Pilih `Add Delivery Support`.
2. Di `Basic Information` isi `Support Name`, `Client`, dan `Type` (wajib). Tipe menentukan kategori support dan ticket-nya. `Support Method`, `Modules`, `IO Number` (unik, opsional), dan `Vendor` (opsional, hanya business partner bertipe Vendor) bersifat opsional.
3. Di `Timeline` isi `Start Date`, `End Date`, `Resolution Estimated`, dan `Service Window`, yaitu jam operasional per hari.
4. Di `Team Assignment` pilih `Delivery Owner`, `Support Manager` (satu atau lebih), `Co PM`, `Support Admin`, dan `Sales`.
5. Isi `Total Mandays` dan tanggal serta nama `Approval` bila sudah ada, lalu pilih `Create Delivery Support`.

Ticket dihubungkan ke support dari halaman ticket, bukan dari formulir ini.

## Aturan yang perlu diketahui

### Saat mengekspor

- Field yang bisa dicentang adalah kolom export penuh: `Customer`, `Support Name`, `Type`, `IO`, `Total Mandays`, `Start Date`, `End Date`, `Delivery Owner`, `Support Manager`, `Co PM`, `Support Admin` dan `Sales`.
- Template hanya memilih kolom. Baris yang diekspor tetap ditentukan oleh filter, kartu, dan pencarian yang aktif di daftar.
- Bila semua field dicentang, hasilnya export penuh yang biasa. Bila field lebih sedikit, file berisi sel polos: baris judul merah, baris pertama dibekukan dan lebar kolom menyesuaikan, tanpa format angka dan perataan seperti export penuh.
- Minimal satu field harus dicentang, dan template butuh nama sampai 80 karakter.
- Template `Personal` hanya dilihat dan dipakai oleh Anda. Template `Global` dilihat dan dipakai semua orang yang bisa membuka export ini, dan diberi tanda nama pembuatnya.
- Hanya pembuatnya yang boleh mengubah template. Orang lain boleh memakai template `Global` tetapi tidak boleh mengubah atau menghapusnya; untuk menyimpan variasi, mereka menyimpannya dengan nama lain. EC Administrator boleh menghapus template `Global` mana pun.
- Setiap daftar punya field dan template sendiri.
- Di antara template Anda sendiri untuk satu daftar, nama harus unik, baik `Personal` maupun `Global`. Menyimpan dengan nama yang sudah Anda pakai mengganti template itu dan bisa mengubahnya antara `Personal` dan `Global`. Nama `Global` juga harus berbeda dari template `Global` lain di daftar itu.
- `Save` menyimpan field yang sedang tercentang. Mengubah centang pada template yang dipilih lalu memilih `Export` tidak mengubah template.
- Jendela terbuka dengan template yang terakhir Anda pakai di browser ini.
- Bila sebuah field kelak dihapus dari export, field itu hilang begitu saja dari template tersimpan Anda.
- Template, termasuk yang `Global`, tidak mengubah siapa yang boleh mengekspor. Tombol export tetap mengikuti akses Anda di `Menu Access`, jadi template `Global` hanya berguna bagi orang yang bisa mengekspor daftar ini.

## Apa yang terjadi selanjutnya

Support muncul di daftar dan dapat dipilih saat menugaskan ticket. Halaman detailnya dijelaskan di topik berikutnya.

## Pertanyaan umum

**Tidak ada tombol `Add Delivery Support`.** Role Anda tidak memiliki `Add Delivery Support`. Minta administrator.

**Nomor IO ditolak.** Nomor IO harus unik di semua delivery support.

<!-- sources: routes/delivery-support.php, resources/views/delivery/support/index.blade.php, resources/views/delivery/support/list/create.blade.php -->
