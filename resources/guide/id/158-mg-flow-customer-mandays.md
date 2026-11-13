---
key: mg-flow-customer-mandays
chapter: management
order: 18
title: Customer Mandays Approval
summary: Tentukan siapa yang menyetujui Level 1 dan Level 2 proposal Customer Mandays sebelum Helpdesk mengirimnya ke customer.
requires: [management.flow.customer-mandays]
updated: 2026-11-13
---
`Management` › `Flow` › `Customer Mandays Approval` mengatur dua level persetujuan internal yang dilalui setiap proposal Customer Mandays setelah PIC mengirimnya. Helpdesk baru dapat mengirim proposal ke customer setelah kedua level menyetujuinya.

## Siapa yang menggunakan

Administrator, dan role mana pun yang diberi `Management` › `Flow` › `Customer Mandays Approval` di Menu Access. Saat halaman ini pertama kali dipasang, hanya EC Administrator yang memilikinya.

## Sebelum Anda mulai

- Tentukan siapa yang menyetujui tiap level. Satu level dapat diberikan ke semua pemegang sebuah posisi (role), atau hanya ke orang yang Anda sebut namanya.
- Pastikan posisi itu dipegang minimal satu orang aktif. Jika tidak, tidak ada yang bisa menyetujui level tersebut.
- Bawaannya, Level 1 adalah `Admin Approval` (EC Administrator) dan Level 2 adalah `Helpdesk Review` (Delivery Support Service Helpdesk).

## Langkah demi langkah

1. Buka `Management` › `Flow` › `Customer Mandays Approval`. Kartu `Approval Workflow` menampilkan satu baris per level, bernomor 1 dan 2.
2. Di `Step Name`, ketik nama yang dilihat orang selama proposal menunggu, misalnya `Admin Approval`. Ticket lalu menampilkan `Waiting Admin Approval`.
3. Di `Position`, pilih role yang menyetujui level ini. Baris di bawahnya menunjukkan berapa orang aktif yang memegang posisi itu.
4. Untuk membatasi level ke orang tertentu, buka `Name` lalu centang namanya. Biarkan `Name` kosong agar semua pemegang posisi dapat menyetujui. Bila nama dipilih, baris di bawah `Position` menampilkan role yang dipegang orang-orang tersebut.
5. Pakai panah di samping nomor level untuk menukar urutan kedua level.
6. Pilih `Save Workflow`. Kedua level disimpan bersamaan.

## Aturan yang perlu diketahui

- Jumlah level selalu tepat dua. Level tidak dapat ditambah, dihapus, atau dinonaktifkan.
- Setiap level wajib punya nama langkah (paling banyak 100 karakter) dan sebuah posisi atau minimal satu nama. Posisi yang tidak dipegang siapa pun ditolak bila tidak ada nama yang dipilih.
- Bila nama dipilih, hanya orang-orang itu yang dapat menyetujui level tersebut, walaupun ada orang lain yang memegang posisinya.
- Satu orang per level sudah cukup. Siapa pun yang bertindak lebih dulu memutuskan level itu.
- Perubahan hanya berlaku untuk proposal yang dikirim setelah Anda menyimpan. Proposal yang sudah menunggu tetap memakai penyetuju saat ia dikirim. Halaman ini memberi tahu berapa proposal yang sedang menunggu.
- Level berjalan berurutan: Level 2 tidak dapat bertindak sebelum Level 1 menyetujui.
- Penyetuju boleh mengubah angka mandays sebelum menyetujui. Angka baru disimpan bersama persetujuannya.
- Menolak wajib disertai alasan minimal 5 karakter. Proposal lalu ditutup sebagai `Rejected`, level sesudahnya ditandai dilewati, dan PIC merevisinya sebagai versi baru.
- PIC boleh menyetujui proposalnya sendiri bila ia termasuk penyetuju level itu.
- Setelah Level 2 menyetujui, proposal siap dikirim. Helpdesk mengirimnya ke customer, dan proposal baru menjadi `Approved` saat customer menyetujuinya. Helpdesk tidak lagi dapat menyetujui proposal secara langsung.
- Setiap penyetuju mendapat notifikasi bell saat proposal sampai di levelnya. PIC mendapat notifikasi saat proposal ditolak.

## Apa yang terjadi selanjutnya

Proposal baru mengikuti level yang disimpan. Saat gilirannya tiba, penyetuju membuka `Review Mandays Proposal` di ticket dan menemukan tombol `Approve` dan `Reject` di sana, walaupun rolenya tidak punya akses review. Alur proposal selengkapnya di ticket dijelaskan pada topik Customer Mandays dan Resolution Days.

## Pertanyaan umum

**Saya sudah mengganti penyetuju, tetapi ada proposal yang masih menunggu penyetuju lama.** Proposal itu dikirim sebelum perubahan Anda dan tetap memakai penyetujunya sendiri. Minta penyetuju lama bertindak, atau minta Helpdesk membatalkannya agar PIC dapat mengirim versi baru.

**Mengapa saya tidak dapat menambah level ketiga?** Alur ini memang sengaja ditetapkan dua level.

<!-- sources: routes/web.php, app/Http/Controllers/CustomerMandaysFlowController.php, app/Services/CustomerMandaysApprovalService.php, resources/views/management/flow/customer-mandays.blade.php, database/migrations/2026_11_13_000001_create_customer_mandays_approval_tables.php -->
