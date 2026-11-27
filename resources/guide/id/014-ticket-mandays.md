---
key: ticket-mandays
chapter: work-service
order: 14
title: Customer Mandays dan Resolution Days
summary: Dua estimasi pada sebuah ticket, siapa yang menyetujui masing-masing, dan alurnya dari draft sampai disetujui.
requires: [ticket.propose-mandays-customer, ticket.propose-mandays-resolution, ticket.review-mandays, ticket.head-mandays, ticket.approve-resolution-days]
updated: 2026-11-27
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
3. Penyetuju Level 1 membuka `Review Mandays Proposal` di ticket, memilih versinya, lalu `Review This Version`. Badge di sebelahnya menampilkan `Your turn` saat gilirannya tiba. Ia dapat menyesuaikan mandays lalu memilih `Approve`, atau memilih `Reject`, menulis alasannya, lalu memilih `Confirm Reject`.
4. Setelah Level 1 menyetujui, proposal menunggu Level 2 (bawaannya Helpdesk). Penyetuju Level 2 membuka `Review Mandays Proposal`, boleh menyesuaikan mandays, lalu memilih salah satu:
   - `Send to Customer`: menyetujui Level 2 sekaligus langsung mengirim proposal ke customer lewat chat dan email. Sebelum memilihnya, gunakan bagian `For the Customer` di jendela yang sama. Di `Attach Documents` Anda dapat mencentang dokumen pendukung, misalnya CR Form, SoW, atau WO. Daftarnya diambil dari `Deliverable Documents` ticket, dan tautan dokumen yang dicentang ikut terkirim bersama proposal. Di `Message to Customer` Anda dapat menulis catatan, misalnya keterangan dokumen yang dilampirkan. Lalu pilih `Send to Customer`. Statusnya menjadi `Sent to Chat`.
   - `Approve`: menyetujui proposal secara langsung tanpa menunggu customer, misalnya bila customer sudah setuju lewat email. Statusnya menjadi `Approved`.
   - `Reject`: tulis alasannya, lalu pilih `Confirm Reject`.
5. Setelah dikirim, customer menyetujui lewat tautan yang diterima, dan proposal menjadi `Approved`. Selama berstatus `Sent to Chat`, penyetuju Level 2 masih dapat memilih `Approve` bila customer setuju dengan cara lain.
6. Bila customer menolak, statusnya menjadi `Ready to Send` beserta alasan customer. Helpdesk merevisi proposal lalu memilih `Revise & Resend`. Sebelumnya, sesuaikan dokumen dan pesannya di bagian `For the Customer`. Dokumen pengiriman terakhir sudah tercentang.
7. Bila sebuah level menolak, statusnya menjadi `Rejected` dan alasannya ditampilkan ke PIC. PIC memilih `New Version`, merevisi proposal, lalu mengirimnya lagi.
8. Helpdesk, atau penyetuju Level 2 selama proposal berstatus `Ready to Send` atau `Sent to Chat`, dapat memilih `Cancel Proposal`. Revisi menghasilkan versi baru.

Setiap versi menampilkan `Approval Trail` berisi tiap level, siapa yang bertindak, kapan, dan alasannya bila sebuah level menolak.

### Resolution Days

1. Pilih `Propose Resolution Days`, isi rinciannya, dan simpan `Draft`.
2. Kirim. Statusnya menjadi `Pending Head`.
3. Kepala memakai `Review Resolution Days` untuk menyetujuinya, atau menolaknya disertai alasan.

## Aturan yang perlu diketahui

### Customer Mandays

- Proposal harus bertotal di atas 0 mandays. EcoSystem menolak menyimpan proposal kosong.
- Proposal yang dikirim melewati dua level persetujuan secara berurutan sebelum dapat dikirim ke customer. Siapa penyetuju tiap level diatur di `Management` › `Flow` › `Customer Mandays Approval`. Bawaannya, Level 1 adalah EC Administrator dan Level 2 adalah Helpdesk.
- Hanya penyetuju level yang sedang menunggu yang dapat menyetujui atau menolaknya. Level 2 tidak dapat bertindak sebelum Level 1. Satu penyetuju per level sudah cukup.
- Penyetuju boleh mengubah mandays sebelum menyetujui. Totalnya harus tetap di atas 0.
- Menolak wajib disertai alasan minimal 5 karakter. Versi itu ditutup sebagai `Rejected` dan level berikutnya dilewati. PIC membuat versi baru untuk merevisinya.
- PIC boleh menyetujui proposalnya sendiri bila ia termasuk penyetuju level yang sedang menunggu.
- Bila alurnya diubah saat proposal menunggu, level yang belum diputuskan mengikuti penyetuju baru. Level yang sudah disetujui tetap seperti semula.
- Pengiriman ditolak bila ada level yang belum punya penyetuju. Minta administrator mengatur alurnya.
- Proposal tidak dapat diubah saat menunggu persetujuan, menunggu Helpdesk mengirimnya ulang, atau setelah dikirim ke customer. Tunggu jawabannya, atau minta Helpdesk membatalkannya.
- Di Level 2, penyetuju bertindak seperti Helpdesk sebelum ada dua level: `Send to Customer` menyetujui Level 2 dan mengirim dalam satu langkah, dan `Approve` langsung membuat proposal `Approved`. Penyetuju Level 1 tidak mendapat `Send to Customer`. Penyetuju Level 2 tidak memerlukan fungsi di `Menu Access` untuk kedua tombol itu: itu mengikuti penyetuju yang diatur di alur.
- Proposal menjadi `Approved` saat customer menyetujuinya, atau saat penyetuju Level 2 menyetujuinya secara langsung. Secara langsung berarti di Level 2, atau setelahnya pada versi terbaru selama berstatus `Sent to Chat`, tetapi tidak tepat setelah customer menolaknya. Persetujuan dengan cara ini mengisi mandays ticket dengan total proposal, sama seperti persetujuan customer.
- Pengguna Helpdesk yang bukan penyetuju Level 2 tidak dapat menyetujui proposal secara langsung.
- Dokumen pendukung bersifat opsional. Hanya dokumen ticket ini di `Deliverable Documents` yang sudah punya file yang dapat dipilih, paling banyak 20 per pengiriman. Unggah dokumennya di sana lebih dulu bila belum ada di daftar.
- Dokumen yang dicentang dicantumkan beserta tautannya di email dan pesan chat proposal, dan portal customer menerima daftar yang sama. Di `Deliverable Documents` statusnya menjadi `Sent`, sama seperti dikirim dari sana.
- Pengiriman ulang mengganti daftar dokumen versi itu dengan pilihan yang baru. Setelah terkirim, `Review Mandays Proposal` menampilkannya di `Documents Sent`.
- `Message to Customer` bersifat opsional, paling banyak 2.000 karakter. Pesan ini tampil di pesan chat dan email ke customer, tepat setelah kalimat pembuka proposal.
- Customer dapat menolak proposal dari tautan yang diterimanya dan boleh menulis alasan. Proposal lalu kembali ke Helpdesk sebagai `Ready to Send`, yang merevisi dan mengirimnya lagi. Pengiriman ulang tidak melewati Level 1 dan Level 2 lagi.
- Pada ticket `Change Request`, hanya ticket lead yang dapat mengusulkan Customer Mandays. Head tidak dibatasi aturan ini.
- Membatalkan proposal memerlukan izin cancel. Penyetuju Level 2 juga dapat membatalkan versi terbaru selama berstatus `Ready to Send` atau `Sent to Chat`, tanpa izin tersebut. Membatalkan yang sudah disetujui memerlukan izin tersendiri, dan customer tidak diberi tahu otomatis, jadi Helpdesk menindaklanjuti lewat email.
- Mengusulkan Customer Mandays memerlukan fungsi `Propose Mandays Customer` dan mengusulkan Resolution Days memerlukan fungsi `Propose Resolution Days` di `Menu Access`, selain menjadi ticket lead atau member. Menyimpan hasil review memerlukan `Save Draft` atau `Send to Customer`, karena mengirim menyimpan perubahan Anda lebih dulu. Menyetujui sebuah level tidak memerlukan fungsi di `Menu Access`: itu mengikuti penyetuju yang diatur di alur. Tanpa fungsi itu permintaan ditolak, meskipun dikirim langsung.
- Saat proposal Customer Mandays dikirim, penyetuju Level 1 mendapat notifikasi bell. Saat Level 1 menyetujui, penyetuju Level 2 diberi tahu. Saat sebuah level menolak, PIC diberi tahu. Saat Resolution Days dikirim, semua orang yang rolenya punya izin head atau persetujuan Resolution Days mendapat notifikasi.
- Aksi di layar review (edit, simpan draft, kirim ke customer, batalkan) masing-masing punya izin sendiri di `Role Access`. Role yang bisa membuka review tetapi tidak punya izin ini melihat proposal tanpa tombol aksi.
- Penyetuju juga dapat memutuskan di aplikasi mobile EcoSystem Lite dengan aturan yang sama. Proposal yang berubah setelah dibuka harus dimuat ulang lebih dulu.

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

**Saya penyetuju tetapi tidak melihat tombol `Approve`.** Tombol itu hanya muncul selama proposal menunggu di level Anda. Bila proposal menunggu di level lain, berarti bukan giliran Anda. Muat ulang ticket setelah proposal dikirim.

**Dokumen yang ingin saya kirim tidak ada di `Attach Documents`.** Hanya dokumen yang sudah punya file di `Deliverable Documents` ticket ini yang muncul. Unggah dokumennya di sana, lalu buka `Review Mandays Proposal` lagi.

**Customer sudah setuju lewat email, tetapi proposal masih `Sent to Chat`.** Minta penyetuju Level 2 membuka `Review Mandays Proposal` lalu memilih `Approve`. Siapa penyetuju Level 2 diatur di flow.

<!-- sources: routes/web.php, routes/api.php, routes/lite.php, app/Http/Controllers/MandaysController.php, app/Http/Controllers/Lite/LiteCustomerMandaysController.php, app/Services/CustomerMandaysApprovalService.php, app/Services/LiteCustomerMandaysPresenter.php, app/Http/Controllers/TicketDeliverableController.php, resources/views/ticket/show.blade.php, resources/views/ticket/task.blade.php -->
