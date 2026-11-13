---
key: ticket-mandays
chapter: work-service
order: 14
title: Customer Mandays dan Resolution Days
summary: Dua estimasi pada sebuah ticket, siapa yang menyetujui masing-masing, dan alurnya dari draft sampai disetujui.
requires: [ticket.propose-mandays-customer, ticket.propose-mandays-resolution, ticket.review-mandays, ticket.head-mandays, ticket.approve-resolution-days]
updated: 2026-11-13
---
Ticket yang membutuhkan estimasi pekerjaan membawa dua proposal yang berbeda. Keduanya mudah tertukar, jadi bedakan dengan baik.

## Siapa yang menggunakan

Ticket lead dan konsultan yang mengusulkan, dua level persetujuan dan Helpdesk untuk Customer Mandays, serta kepala yang menyetujui Resolution Days. Tombol yang tampil bergantung pada role Anda dan apakah sedang giliran Anda menyetujui.

## Sebelum Anda mulai

- Buka ticket dan cari bagian `Mandays`.
- Modul yang dapat dipilih pada proposal berasal dari keahlian ticket lead dan anggota tim, jadi pastikan dulu tim ticket sudah benar.

| | Customer Mandays | Resolution Days |
|---|---|---|
| Apa itu | Hari kerja yang disetujui **customer** | Estimasi internal untuk menyelesaikan ticket |
| Disusun oleh | Penanggung jawab ticket | Penanggung jawab ticket |
| Disetujui oleh | Level 1 dan Level 2 di internal, lalu customer | Kepala |

## Langkah demi langkah

### Customer Mandays

![Halaman ticket dengan bagian Mandays: Propose Mandays, Propose Resolution Days, Review Mandays Proposal, dan Review Resolution Days](images/guide/ticket-detail.png)

*Tangkapan layar dengan data contoh.*

1. Pilih `Propose Mandays` lalu isi rinciannya. Simpan dulu sebagai `Draft`.
2. Periksa angkanya lalu pilih `Submit for Approval`. Badge menampilkan `Level 1`, dan proposal menampilkan `Waiting` diikuti nama Level 1, misalnya `Waiting Admin Approval`.
3. Penyetuju Level 1 membuka `Review Mandays Proposal` di ticket, memilih versinya, lalu `Review This Version`. Badge di sebelahnya menampilkan `Your turn` saat gilirannya tiba. Ia dapat menyesuaikan mandays lalu memilih `Approve`, atau menulis alasan di `Approval Notes` lalu memilih `Reject`.
4. Setelah Level 1 menyetujui, proposal menunggu Level 2 (bawaannya Helpdesk), yang meninjau dengan cara yang sama.
5. Setelah Level 2 menyetujui, statusnya menjadi `Ready to Send`. Helpdesk memakai `Review Mandays Proposal` untuk memeriksanya, menyesuaikan bila perlu, lalu mengirimkannya ke customer lewat chat dan email.
6. Customer menyetujui lewat tautan yang diterima, dan proposal menjadi `Approved`.
7. Bila sebuah level menolak, statusnya menjadi `Rejected` dan alasannya ditampilkan ke PIC. PIC memilih `New Version`, merevisi proposal, lalu mengirimnya lagi.
8. Helpdesk dapat membatalkan proposal. Revisi menghasilkan versi baru.

Setiap versi menampilkan `Approval Trail` berisi tiap level, siapa yang bertindak, kapan, dan catatannya.

### Resolution Days

1. Pilih `Propose Resolution Days`, isi rinciannya, dan simpan `Draft`.
2. Kirim. Statusnya menjadi `Pending Head`.
3. Kepala memakai `Review Resolution Days` untuk menyetujuinya, atau menolaknya disertai alasan.

## Aturan yang perlu diketahui

### Customer Mandays

- Proposal harus bertotal di atas 0 mandays. EcoSystem menolak menyimpan proposal kosong.
- Proposal yang dikirim melewati dua level persetujuan secara berurutan sebelum Helpdesk dapat mengirimnya. Siapa penyetuju tiap level diatur di `Management` › `Flow` › `Customer Mandays Approval`. Bawaannya, Level 1 adalah EC Administrator dan Level 2 adalah Helpdesk.
- Hanya penyetuju level yang sedang menunggu yang dapat menyetujui atau menolaknya. Level 2 tidak dapat bertindak sebelum Level 1. Satu penyetuju per level sudah cukup.
- Penyetuju boleh mengubah mandays sebelum menyetujui. Totalnya harus tetap di atas 0.
- Menolak wajib disertai alasan minimal 5 karakter. Versi itu ditutup sebagai `Rejected` dan level berikutnya dilewati. PIC membuat versi baru untuk merevisinya.
- PIC boleh menyetujui proposalnya sendiri bila ia termasuk penyetuju level yang sedang menunggu.
- Proposal tetap memakai penyetuju saat ia dikirim, walaupun alurnya diubah sesudahnya.
- Pengiriman ditolak bila ada level yang belum punya penyetuju. Minta administrator mengatur alurnya.
- Proposal tidak dapat diubah saat menunggu persetujuan, menunggu Helpdesk mengirimnya, atau setelah dikirim ke customer. Tunggu jawabannya, atau minta Helpdesk membatalkannya.
- Helpdesk tidak dapat menyetujui proposal secara langsung. Proposal baru menjadi `Approved` saat customer menyetujuinya.
- Setelah kedua level menyetujui, Helpdesk masih dapat menyesuaikan proposal sebelum mengirimnya ke customer lewat chat dan email.
- Customer dapat menolak proposal dari tautan yang diterimanya dan boleh menulis alasan. Proposal lalu kembali ke Helpdesk, yang merevisi dan mengirimnya lagi.
- Pada ticket `Change Request`, hanya ticket lead yang dapat mengusulkan Customer Mandays. Head tidak dibatasi aturan ini.
- Membatalkan proposal memerlukan izin cancel. Membatalkan yang sudah disetujui memerlukan izin tersendiri, dan customer tidak diberi tahu otomatis, jadi Helpdesk menindaklanjuti lewat email.
- Mengusulkan Customer Mandays memerlukan fungsi `Propose Mandays Customer` dan mengusulkan Resolution Days memerlukan fungsi `Propose Resolution Days` di `Menu Access`, selain menjadi ticket lead atau member. Menyimpan hasil review memerlukan `Save Draft` atau `Send to Customer`, karena mengirim menyimpan perubahan Anda lebih dulu. Menyetujui sebuah level tidak memerlukan fungsi di `Menu Access`: itu mengikuti penyetuju yang diatur di alur. Tanpa fungsi itu permintaan ditolak, meskipun dikirim langsung.
- Saat proposal Customer Mandays dikirim, penyetuju Level 1 mendapat notifikasi bell. Saat Level 1 menyetujui, penyetuju Level 2 diberi tahu. Saat Level 2 menyetujui, semua orang yang rolenya punya izin review di `Role Access` diberi tahu bahwa proposal siap dikirim. Saat sebuah level menolak, PIC diberi tahu. Saat Resolution Days dikirim, semua orang yang rolenya punya izin head atau persetujuan Resolution Days mendapat notifikasi.
- Aksi di layar review (edit, simpan draft, kirim ke customer, batalkan) masing-masing punya izin sendiri di `Role Access`. Role yang bisa membuka review tetapi tidak punya izin ini melihat proposal tanpa tombol aksi.

### Resolution Days

- Hanya ticket lead atau anggota ticket yang dapat menyusun dan mengirimnya. Proposal masih dapat diubah sampai disetujui.
- Pada ticket `Change Request`, Resolution Days tidak dapat dikirim, dan head tidak dapat menyetujuinya, sebelum Customer Mandays disetujui.
- Head (Head of Support atau administrator) mengisi hari yang disetujui untuk tiap orang. Ini wajib: persetujuan tidak dapat diproses tanpa itu.
- Bila angka yang disetujui membuat seseorang memiliki sisa MD negatif, EcoSystem memperingatkan dulu dan meminta head mengonfirmasi sebelum menyetujui.
- Head juga dapat menolak, dengan alasan. Proposal lalu diperbaiki dan dikirim lagi.

### Dampak pada timesheet

- Hari yang disetujui menjadi kuota tiap orang pada ticket. Konsultan tidak dapat mencatat MD melebihi sisa kuota, dan tidak dapat mengirim timesheet pada ticket yang belum ada hari yang disetujui untuknya. Jika proposal diedit lalu kembali ke `Draft`, hari yang sudah disetujui tetap menjadi kuota sampai Head menyetujui lagi. Lihat topik timesheet untuk rinciannya.

## Apa yang terjadi selanjutnya

Hari yang disetujui dipakai untuk beban kerja di `My Tasks` dan `Consultant Workload`, serta untuk kuota pada laporan `MD Validation`. Proposal Resolution Days yang ditolak diperbaiki lalu dikirim lagi.

## Pertanyaan umum

**Yang mana yang saya butuhkan?** Pakai Customer Mandays untuk yang dibayar atau disetujui customer, dan Resolution Days untuk rencana internal.

**Saya tidak melihat tombol peninjauan.** Meninjau hanya untuk Helpdesk dan kepala. Hubungi administrator bila Anda merasa seharusnya memilikinya.

**Saya penyetuju tetapi tidak melihat tombol `Approve`.** Tombol itu hanya muncul selama proposal menunggu di level Anda. Bila proposal menunggu di level lain, atau dikirim sebelum Anda ditetapkan sebagai penyetuju, berarti bukan giliran Anda. Muat ulang ticket setelah proposal dikirim.

<!-- sources: routes/web.php, routes/api.php, app/Http/Controllers/MandaysController.php, app/Services/CustomerMandaysApprovalService.php, resources/views/ticket/show.blade.php, resources/views/ticket/task.blade.php -->
