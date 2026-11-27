---
key: ticket-export
chapter: work-service
order: 21
title: Export Tickets
summary: Unduh daftar ticket sebagai file Excel, pilih field yang masuk ke file, dan simpan pilihan itu sebagai template.
requires: [ticket.export]
updated: 2026-11-25
---
Di halaman `Support Tickets`, tombol `Export` mengunduh ticket yang sedang Anda lihat sebagai file Excel. Anda memilih field dulu, dan pilihan itu bisa disimpan sebagai template untuk lain kali.

## Siapa yang menggunakan

Siapa pun yang rolenya punya fungsi `Export` untuk ticket di `Menu Access`. Ticket lead, Helpdesk, dan head paling sering memakainya untuk membuat laporan ticket.

## Sebelum Anda mulai

- Atur dulu filter di halaman `Support Tickets`: kartu status, filter kolom, rentang tanggal, dan kotak pencarian. File mengikuti persis apa yang Anda atur.
- Tentukan field yang Anda perlukan. File penuh punya banyak kolom, misalnya `Last Update`, `Tiket`, `Description`, `Start Date`, `Close Date`, `Day on Close`, `Customer`, `End Customer`, `Ticket Lead`, `Priority`, `Scale`, `Status`, `Type`, `Module`, `Assign Delivery`, `Customer Mandays`, `Activity Date`, `Progress`, kolom waktu respon dan resolusi beserta statusnya.

## Langkah demi langkah

### Pilih field dan ekspor

1. Di `Support Tickets`, atur filter Anda, lalu pilih `Export` di kanan atas.
2. Jendela berjudul `Export Support Tickets` terbuka. Isinya daftar `Template` dan semua kotak centang `Fields`.
3. Centang field yang ingin masuk ke file. Ketik di `Search fields` untuk mencari satu field di daftar panjang. `Select all` mencentang semua field dan `Unselect all` menghapus semua centang.
4. Untuk memakai pilihan ini lagi, ketik nama di bawah `Save these fields as a template`, pilih `Personal` atau `Global`, lalu pilih `Save`. Template itu kemudian muncul di daftar `Template` dengan tanda `(Personal)` atau `(Global)`.
5. Lain kali, buka jendela yang sama dan pilih template di `Template`. Field-nya sudah tercentang. Template `Global` buatan rekan ada di daftar yang sama. Pilih `Delete` untuk menghapus template Anda yang tidak dipakai lagi.
6. Pilih `Export`. File hanya berisi field yang dicentang.

### Memakai template lagi

1. Pilih `Export` lagi.
2. Pilih template Anda di `Template`. Field template itu langsung tercentang.
3. Pilih `Export`.

Contoh: template `A` berisi `Tiket`, `Description`, dan kolom SLA, template `B` berisi `Tiket`, `Description`, dan `Customer`. Buat masing-masing sekali, lalu pilih `A` atau `B` setiap kali mengekspor.

## Aturan yang perlu diketahui

### Siapa yang boleh mengekspor dan ticket apa yang masuk

- Mengekspor butuh fungsi `Export` untuk ticket di `Menu Access`. Tanpa itu unduhan ditolak, bahkan bila alamatnya dibuka langsung.
- File berisi ticket yang cocok dengan filter di halaman: kartu status, `Status`, `Priority`, `Scale`, `Type`, `Document Status`, `Module`, `Assign Delivery`, `Customer`, `Ticket Lead`, rentang tanggal (berdasarkan tanggal ticket dibuat), serta pencarian nomor ticket dan deskripsi.
- Ticket yang disembunyikan dan ticket yang sudah dihapus tidak pernah masuk ke file.
- Nama file `TICKET SUPPORT` diikuti tanggal hari ini, diurutkan menurut nomor ticket dari yang terlama.

### Saat mengekspor

- Field yang bisa dicentang adalah kolom export ticket penuh.
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

File terunduh di browser Anda. Template tetap tersimpan dan ditawarkan lagi saat Anda memilih `Export` berikutnya.

## Pertanyaan umum

**Bisakah rekan memakai template saya?** Hanya jika Anda menyimpannya sebagai `Global`. Template `Personal` hanya terlihat oleh Anda. Rekan yang memakai template `Global` tidak bisa mengubahnya, tetapi bisa menyimpan versi sendiri dengan nama lain.

**Template saya kehilangan satu field.** Field itu dihapus dari export setelah Anda menyimpan template. Centang field yang Anda mau lalu pilih `Save` dengan nama yang sama untuk memperbaruinya.

**Baris di file lebih sedikit dari dugaan.** Filter di halaman tetap berlaku. Bersihkan dulu bila Anda ingin semua ticket.

<!-- sources: routes/web.php, app/Http/Controllers/TicketController.php, app/Exports/TicketExport.php, app/Support/ExportColumns.php, resources/views/ticket/index.blade.php, resources/views/partials/export-picker.blade.php -->
