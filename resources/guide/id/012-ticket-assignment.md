---
key: ticket-assignment
chapter: work-service
order: 12
title: Menugaskan ticket dan mengelola tim
summary: Ambil ticket, tentukan ticket lead, tambah anggota tim, dan kaitkan ticket ke satu Delivery Support.
requires: [ticket.assign-pic, ticket.assign-delivery-support, ui.ticket.manage-members, ticket.take]
updated: 2026-11-02
---
Setiap ticket butuh pemilik. Topik ini menunjukkan bagaimana ticket mendapat ticket lead dan timnya, serta dikaitkan ke sebuah Delivery Support.

## Siapa yang menggunakan

Helpdesk dan kepala yang menugaskan ticket, serta konsultan yang mengambil ticket sendiri. Setiap tombol hanya tampil bila role Anda mengizinkan.

## Sebelum Anda mulai

- Buka `Work & Service` › `Ticket`.
- Hanya orang yang diizinkan menjadi ticket lead atau anggota ticket yang ditawarkan di daftar. Ini diatur administrator, dan akun yang diblokir tidak pernah ditawarkan.

## Langkah demi langkah

### Mencari ticket tanpa pemilik

1. Buka tab `Unassigned` (tampil sebagai `Unassigned Ticket` pada sebagian role).
2. Buka ticket yang sesuai dengan Anda atau tim.

### Mengambil ticket sendiri

1. Buka ticket.
2. Pilih `Take This Ticket`.

Jika ticket belum jelas atau di luar keahlian Anda, tulis `Internal Note` untuk Helpdesk, jangan didiamkan.

### Menentukan ticket lead

![Halaman ticket dengan tombol Change Ticket Lead dan Assign to Delivery Support di panel kanan](images/guide/ticket-detail.png)

*Tangkapan layar dengan data contoh.*

1. Buka ticket lalu pilih `Assign Ticket Lead`. Bila ticket sudah punya lead, tombolnya bertuliskan `Change Ticket Lead`.
2. Pilih orangnya dan konfirmasi.

### Mengelola tim

1. Buka `Team Members` pada ticket.
2. Tambahkan rekan yang perlu ikut mengerjakan, atau keluarkan yang tidak lagi perlu.
3. Simpan perubahan.

### Mengaitkan ticket ke Delivery Support

1. Pilih `Assign to Delivery Support`.
2. Pilih Delivery Support milik customer yang tepat.

Sebuah ticket hanya milik **satu** Delivery Support. Jika salah kaitan, lepas dulu, lalu kaitkan yang benar.

> [!WARNING]
> `Hide Ticket` dan `Delete Ticket` hanya untuk role tertentu dan berdampak ke semua orang yang melihat ticket itu. Pakai hanya bila Anda yakin berwenang.

## Aturan yang perlu diketahui

- Orang yang nonaktif, diblokir, atau ditandai untuk dihapus tidak bisa menjadi ticket lead atau member. EcoSystem menolaknya meskipun permintaan dikirim langsung.
- Setelah ticket punya module (boleh lebih dari satu), ticket lead dan member harus dipilih dari orang yang berkualifikasi di module itu. Bila tidak, EcoSystem menyatakan orang itu harus anggota module ticket.
- Menambah, mengubah, dan menghapus member memerlukan fungsi `Manage Members` di `Menu Access`. Delivery Support User yang memilikinya hanya dapat melakukannya pada ticket yang ia pegang sebagai ticket lead. Ticket lead dan module lead dari ticket itu selalu dapat mengelola membernya. Selain itu ditolak, meskipun permintaan dikirim langsung. Ticket lead tidak bisa sekaligus ditambahkan sebagai member, dan satu orang tidak bisa ditambahkan dua kali.
- `Take This Ticket` langsung menjadikan Anda ticket lead. Tombol ini hanya menugaskan diri Anda, tidak pernah orang lain. Tombol ini memerlukan fungsi `Take Ticket` di `Menu Access`, dan Anda juga harus memiliki kualifikasi DSM.
- Karyawan external tidak dapat mengubah ticket.
- `Assign to Delivery Support` (termasuk membuat delivery support baru dari ticket) hanya berfungsi untuk role yang diberi fungsi itu di Menu Access. Secara bawaan: administrator, Delivery Support Head, Helpdesk, dan Delivery RPMO Head. Selain itu ditolak, meskipun permintaan dikirim langsung. Cukup beri fungsi itu ke role lain di `Control Center` › `Menu Access`, tidak ada yang perlu diubah lagi.
- `Delete Ticket` hanya berfungsi untuk role yang diberi fungsi `Delete Ticket` di Menu Access. Secara bawaan: administrator, Delivery Support Manager II, dan Delivery Support Section Head. Selain itu ditolak, meskipun permintaan dikirim langsung.
- Menghapus ticket mengeluarkannya dari semua daftar dan laporan, dan halamannya tidak bisa dibuka lagi. Tidak ada yang dihapus dari database: pesan, record SLA, Resolution Days, dan timesheet tetap tersimpan. EcoSystem tidak punya layar untuk mengembalikan ticket yang sudah dihapus, jadi penghapusan yang keliru perlu bantuan tim teknis. Penghapusan tercatat di `Audit Log` sebagai `Deleted Ticket`.
- Untuk menyingkirkan ticket tetapi tetap bisa menampilkannya lagi, pakai `Hide Ticket`. Lihat `Hidden Tickets`.

## Apa yang terjadi selanjutnya

Ticket muncul di tab `My Tickets` milik ticket lead dan anggota timnya, serta di `My Tasks`. Delivery Support Manager juga melihat ticket dari Delivery Support yang dikelolanya. Usulan mandays memakai keahlian ticket lead dan anggota, jadi jaga tim tetap akurat.

## Pertanyaan umum

**Seseorang tidak ada di daftar.** Ia mungkin tidak diizinkan menjadi ticket lead atau anggota, atau akunnya diblokir. Hubungi administrator.

**Saya tidak dapat mengubah Delivery Support.** Role Anda mungkin tidak mencakup `Assign to Delivery Support`. Hubungi Helpdesk atau kepala Anda, atau minta administrator memberi fungsi itu ke role Anda di Menu Access.

<!-- sources: routes/web.php, app/Http/Controllers/TicketController.php, resources/views/ticket/index.blade.php, resources/views/ticket/show.blade.php -->
