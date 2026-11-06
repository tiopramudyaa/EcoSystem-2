---
key: ticket-mandays
chapter: work-service
order: 14
title: Customer Mandays dan Resolution Days
summary: Dua estimasi pada sebuah ticket, siapa yang menyetujui masing-masing, dan alurnya dari draft sampai disetujui.
requires: [ticket.propose-mandays-customer, ticket.propose-mandays-resolution, ticket.review-mandays, ticket.head-mandays, ticket.approve-resolution-days]
updated: 2026-10-28
---
Ticket yang membutuhkan estimasi pekerjaan membawa dua proposal yang berbeda. Keduanya mudah tertukar, jadi bedakan dengan baik.

## Siapa yang menggunakan

Ticket lead dan konsultan yang mengusulkan, Helpdesk yang meninjau Customer Mandays, dan kepala yang menyetujui Resolution Days. Tombol yang tampil bergantung pada role Anda.

## Sebelum Anda mulai

- Buka ticket dan cari bagian `Mandays`.
- Modul yang dapat dipilih pada proposal berasal dari keahlian ticket lead dan anggota tim, jadi pastikan dulu tim ticket sudah benar.

| | Customer Mandays | Resolution Days |
|---|---|---|
| Apa itu | Hari kerja yang disetujui **customer** | Estimasi internal untuk menyelesaikan ticket |
| Disusun oleh | Penanggung jawab ticket | Penanggung jawab ticket |
| Disetujui oleh | Customer, setelah ditinjau Helpdesk | Kepala |

## Langkah demi langkah

### Customer Mandays

![Halaman ticket dengan bagian Mandays: Propose Mandays, Propose Resolution Days, Review Mandays Proposal, dan Review Resolution Days](images/guide/ticket-detail.png)

*Tangkapan layar dengan data contoh.*

1. Pilih `Propose Mandays` lalu isi rinciannya. Simpan dulu sebagai `Draft`.
2. Periksa angkanya lalu kirim. Proposal lalu menunggu peninjauan.
3. Helpdesk memakai `Review Mandays Proposal` untuk memeriksa atau menyesuaikannya, lalu mengirimkannya ke customer lewat chat dan email.
4. Customer menyetujui lewat tautan yang diterima, dan proposal menjadi `Approved`.
5. Helpdesk dapat membatalkan proposal. Revisi menghasilkan versi baru.

### Resolution Days

1. Pilih `Propose Resolution Days`, isi rinciannya, dan simpan `Draft`.
2. Kirim. Statusnya menjadi `Pending Head`.
3. Kepala memakai `Review Resolution Days` untuk menyetujuinya, atau menolaknya disertai alasan.

## Aturan yang perlu diketahui

### Customer Mandays

- Proposal harus bertotal di atas 0 mandays. EcoSystem menolak menyimpan proposal kosong.
- Proposal tidak dapat diubah saat menunggu Helpdesk atau setelah dikirim ke customer. Tunggu jawabannya, atau minta Helpdesk membatalkannya.
- Helpdesk dapat menyesuaikan draft, mengirimnya ke customer lewat chat dan email, atau langsung menyetujuinya. Persetujuan Helpdesk sudah cukup: mengirim ke customer lebih dulu tidak wajib.
- Customer dapat menolak proposal dari tautan yang diterimanya dan boleh menulis alasan. Helpdesk lalu merevisinya, yang membuat versi baru.
- Pada ticket `Change Request`, hanya ticket lead yang dapat mengusulkan Customer Mandays. Head tidak dibatasi aturan ini.
- Membatalkan proposal memerlukan izin cancel. Membatalkan yang sudah disetujui memerlukan izin tersendiri, dan customer tidak diberi tahu otomatis, jadi Helpdesk menindaklanjuti lewat email.
- Mengusulkan Customer Mandays memerlukan fungsi `Propose Mandays Customer` dan mengusulkan Resolution Days memerlukan fungsi `Propose Resolution Days` di `Menu Access`, selain menjadi ticket lead atau member. Menyimpan hasil review memerlukan `Save Draft`, `Send to Customer`, atau `Approve Proposal`, karena mengirim dan menyetujui menyimpan perubahan Anda lebih dulu. Tanpa fungsi itu permintaan ditolak, meskipun dikirim langsung.
- Penerima notifikasi bell mengikuti `Role Access`, bukan role tetap. Saat proposal Customer Mandays dikirim, semua orang yang rolenya punya izin review mendapat notifikasi. Saat Resolution Days dikirim, semua orang yang rolenya punya izin head atau persetujuan Resolution Days mendapat notifikasi.
- Aksi di layar review (edit, simpan draft, kirim ke customer, setujui, batalkan) masing-masing punya izin sendiri di `Role Access`. Role yang bisa membuka review tetapi tidak punya izin ini melihat proposal tanpa tombol aksi.

### Resolution Days

- Hanya ticket lead atau anggota ticket yang dapat menyusun dan mengirimnya. Proposal masih dapat diubah sampai disetujui.
- Pada ticket `Change Request`, Resolution Days tidak dapat dikirim, dan head tidak dapat menyetujuinya, sebelum Customer Mandays disetujui.
- Head (Head of Support atau administrator) mengisi hari yang disetujui untuk tiap orang. Ini wajib: persetujuan tidak dapat diproses tanpa itu.
- Bila angka yang disetujui membuat seseorang memiliki sisa MD negatif, EcoSystem memperingatkan dulu dan meminta head mengonfirmasi sebelum menyetujui.
- Head juga dapat menolak, dengan alasan. Proposal lalu diperbaiki dan dikirim lagi.

### Dampak pada timesheet

- Hari yang disetujui menjadi kuota tiap orang pada ticket. Konsultan tidak dapat mencatat MD melebihi sisa kuota, dan tidak dapat mengirim timesheet pada ticket yang belum punya proposal yang disetujui. Lihat topik timesheet untuk rinciannya.

## Apa yang terjadi selanjutnya

Hari yang disetujui dipakai untuk beban kerja di `My Tasks` dan `Consultant Workload`, serta untuk kuota pada laporan `MD Validation`. Proposal Resolution Days yang ditolak diperbaiki lalu dikirim lagi.

## Pertanyaan umum

**Yang mana yang saya butuhkan?** Pakai Customer Mandays untuk yang dibayar atau disetujui customer, dan Resolution Days untuk rencana internal.

**Saya tidak melihat tombol peninjauan.** Meninjau dan menyetujui hanya untuk Helpdesk dan kepala. Hubungi administrator bila Anda merasa seharusnya memilikinya.

<!-- sources: routes/web.php, app/Http/Controllers/MandaysController.php, resources/views/ticket/show.blade.php, resources/views/ticket/task.blade.php -->
