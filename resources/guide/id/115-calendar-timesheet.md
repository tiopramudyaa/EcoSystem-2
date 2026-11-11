---
key: calendar-timesheet
chapter: my-workspace
order: 115
title: Timesheets
summary: Catat jam kerja pada project, ticket, atau pekerjaan kantor, kirim untuk persetujuan, dan minta akses terlambat.
requires: [calendar.timesheets]
updated: 2026-11-11
---
`Calendar` › `Timesheets` adalah tempat Anda mencatat jam kerja dan mengirimnya untuk persetujuan.

## Siapa yang menggunakan

Employee yang memiliki `Timesheets` di bawah `Calendar`. Head dan approver memakai halaman yang sama untuk memeriksa timnya.

## Sebelum Anda mulai

- Ketahui project atau ticket yang Anda kerjakan, tanggalnya, jam mulai dan selesai, serta apa yang Anda lakukan.
- Tombol `Create Timesheet` hanya tampil bila role Anda mengizinkan. Jika tidak ada, hubungi administrator.

## Langkah demi langkah

### Mencatat jam kerja

![Halaman Timesheets dengan periode, kartu ringkasan, tombol Create Timesheet, dan daftar entri beserta statusnya](images/guide/timesheets.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Calendar` › `Timesheets`. Label di samping tombol menunjukkan periode pelaporan, misalnya `October 2026`, serta apakah statusnya `Open`.
2. Pilih `Create Timesheet`. Formulirnya berjudul `Log Working Hours`.

![Formulir Log Working Hours dengan tipe, tanggal, jam, ticket, MD consumed, activity, dan notes](images/guide/timesheets-log-modal.png)

*Tangkapan layar dengan data contoh.*

3. Pilih `Type`: `Project`, `Non Project`, `Support`, atau `Office`. Lalu periksa `Submit Date`. `Reporting Period` terisi otomatis.
4. Pilih `Time` mulai dan selesai. Formulir menampilkan `Duration`. Jam selesai harus lebih besar dari jam mulai.
5. Pilih `Ticket`. Kotak `Customer`, `Quota MD`, dan `Remaining` terisi dari ticket itu.
6. Atur `Activity Date`, yaitu kapan pekerjaan itu benar-benar terjadi.
7. Isi `MD Consumed` dan centang `On Site` bila Anda bekerja di lokasi customer.
8. Jelaskan pekerjaan di `Activity`. `Notes` bersifat opsional.
9. Pilih `Save Timesheet`. Entri dimulai sebagai `Draft`, dan masih dapat Anda ubah atau hapus.

Langkah 5 sampai 7 untuk `Support`. Untuk `Project`, pilih `Project` dan `Activity`, lalu `Presence` dan `Location`, dan jelaskan pekerjaan di `Activity Detail`. `Non Project` memiliki isian yang sama tanpa `Project` dan `Activity`. Bila `Presence` diatur ke `On-site`, `Location` menawarkan lokasi project.

### Mengirim untuk persetujuan

1. Pilih entri yang masih draft di daftar.
2. Pilih `Submit Timesheets` lalu konfirmasi.
3. Status berubah menjadi `Submitted`. Setelah itu Anda tidak dapat mengubahnya sendiri.

Kartu di atas daftar menghitung entri Anda sebagai `Draft`, `Submitted`, `Approved`, dan `Rejected`, serta menampilkan `Total Quota MD` dan `Total MD Consumed`. Klik kanan baris mana pun lalu pilih `Detail` untuk membuka entri lengkap (baris Project juga menampilkan peta GPS), di panel samping. Tab `Project` memakai urutan kolom yang sama dengan form entri Project.

### Meminta akses terlambat

1. Jika sebuah periode sudah ditutup, pilih `Request Late Access`.
2. Pilih periodenya dan tulis alasannya.
3. Kirim permintaan. Permintaan diperiksa oleh Head Anda lebih dulu, lalu oleh RPMO.

> [!NOTE]
> Jika Anda berwenang menyetujui timesheet, Anda juga melihat `Approve` dan `Reject`, termasuk untuk beberapa entri sekaligus. Selalu tulis alasan yang jelas saat menolak.

## Aturan yang perlu diketahui

Aturan ini diperiksa saat Anda menyimpan atau mengirim. Bila ada yang dilanggar, EcoSystem menampilkan pesan dan tidak menyimpan.

### Untuk setiap entri

- Membuat timesheet memerlukan fungsi `Create Timesheet` di `Menu Access`. Tanpa fungsi itu permintaan ditolak, meskipun dikirim langsung.
- Tanggal tidak boleh di masa depan.
- Anda hanya bisa membuat entri untuk diri sendiri, kecuali EC Administrator, RPMO Head, serta Head of Project dan Head of Support.
- Memindahkan entri yang sudah ada ke tanggal lain memeriksa lagi batas periode untuk tanggal baru.
- Jam selesai harus lebih lambat dari jam mulai, dan deskripsi aktivitas wajib diisi.
- Bila role Anda termasuk domain Project atau Support, Anda hanya dapat mencatat waktu di periode berjalan atau periode sebelumnya, dan hanya selama periode itu terbuka. Periode terbuka bila RPMO sudah membukanya dan Head domain Anda sudah membuka domain Anda. Bila tertutup, minta akses terlambat. Role yang tidak termasuk domain mana pun tidak dibatasi oleh aturan periode ini.
- Anda dapat mengubah entri sendiri selama berstatus `Draft` atau `Rejected`. Hanya EC Administrator, RPMO Head, serta Head of Project dan Head of Support yang juga dapat mengubah entri orang lain. Entri `Submitted` dan `Approved` tidak bisa diubah maupun dihapus. Hanya `Draft` yang bisa dihapus.

### Entri support (dengan ticket)

- Pilih ticket yang Anda kerjakan dan isi `Activity Date`.
- Daftar `Ticket` menampilkan ticket tempat Anda menjadi lead, anggota, atau Co-PM (ditambah ticket Delivery Support yang Anda kelola), tetapi hanya jika Head sudah menyetujui hari untuk Anda dan kuota masih tersisa. Draft yang sedang diedit tetap bisa memilih ticketnya sendiri.
- `MD Consumed` tidak boleh melebihi sisa kuota Anda pada ticket itu. Kuota adalah hari yang disetujui Head untuk Anda (`Approved Days` ditambah additional yang disetujui). Kuota tetap berlaku selagi proposal direvisi, dan draft, entri submitted, dan approved Anda semuanya dihitung. Bila perlu tambahan, minta Head Anda menaikkan kuota.
- Anda dapat menyimpan draft sebelum proposal mandays disetujui, tetapi tidak dapat mengirimnya: EcoSystem menyatakan Head belum menyetujui hari untuk Anda pada ticket itu. Untuk Change Request, Customer Mandays harus disetujui dulu.
- Bila personnel subarea Anda `Project`, satu entri support paling banyak 1 MD. Pecah pekerjaan yang lebih besar menjadi beberapa entri.

### Entri project

- Anda hanya dapat mencatat waktu di project yang timnya Anda ikuti, dan project tidak boleh sudah ditutup.
- Tanggal selalu hari ini dan lokasi direkam saat Anda membuat entri. Entri project tidak bisa diberi tanggal mundur, dan mengubahnya kemudian tidak mengubah tanggal maupun lokasi. Daftar menampilkan jarak GPS dari lokasi project di peta, jika sudah diisi.
- Waktunya tidak boleh bertumpuk dengan entri project Anda yang lain di hari yang sama. Entri yang ditolak membebaskan slotnya lagi.
- Pilih aktivitas yang ditugaskan kepada Anda dan sedang berjalan pada hari itu. Pada hari non-kerja, atau bila Anda tidak punya aktivitas yang ditugaskan, centang opsi untuk mencatat tanpa aktivitas.
- `Presence` dan `Location` wajib diisi. Bila `Presence` adalah `On-site`, `Location` terisi dengan lokasi project dari `Location Information`-nya. Anda dapat mengetik tempat lain.

### Entri non project

- Aturan yang sama dengan entri Project berlaku: tanggal selalu hari ini, lokasi perangkat wajib, dan waktunya tidak boleh bertumpuk dengan entri Project atau Non Project Anda yang lain di hari itu.
- Tidak ada project dan tidak ada aktivitas. `Presence`, `Location`, dan `Activity Detail` wajib diisi.
- Persetujuannya adalah fungsi `Approve or Reject Non Project Timesheet`, yang secara bawaan dipegang Head of Project.

### Entri office

- Pilih `Activity Type`: `Development`, `Meeting`, `Documentation`, `Testing`, `Support`, `Training`, atau `Other`.

### Mengirim dan menyetujui

- Anda hanya dapat mengirim entri `Draft` milik sendiri. Pengiriman memberi notifikasi ke semua orang yang rolenya boleh menyetujui tipe itu (lihat poin berikutnya).
- Menyetujui dan menolak diatur per tipe di `Menu Access`, di bawah `Timesheets`: `Approve or Reject Project Timesheet`, `Approve or Reject Non Project Timesheet`, `Approve or Reject Support Timesheet`, dan `Approve or Reject Office Timesheet`. Masing-masing mencakup menyetujui dan menolak, dan Anda hanya melihat dan memproses tipe yang Anda pegang. Secara bawaan Head of Project memegang Project dan Non Project, Head of Support memegang Support, RPMO Head memegang Office, dan EC Administrator memegang keempatnya. Hanya entri `Submitted` yang dapat disetujui atau ditolak, satu per satu atau sekaligus. Menyetujui entri support memeriksa kuota lagi: jika `MD Consumed` tidak lagi muat dalam kuota yang disetujui, persetujuan ditolak.
- Penolakan memerlukan alasan tertulis. Karyawan melihatnya, dapat memperbaiki entri (kembali ke `Draft`), dan mengirimnya lagi.
- Entri yang sudah disetujui bersifat final: tidak dapat dibuka kembali, diubah, atau dihapus dari halaman ini.

## Apa yang terjadi selanjutnya

Entri yang ditolak menampilkan alasannya: perbaiki (entri kembali ke `Draft`) lalu kirim ulang. Permintaan akses terlambat menampilkan `Pending Head`, lalu `Pending RPMO`. Setelah keduanya menyetujui, Anda dapat mengisi periode itu sampai batas waktunya.

## Pertanyaan umum

**Saya tidak dapat membuat timesheet untuk periode yang sudah lewat.** Periodenya sudah ditutup. Bila Anda memang harus mengisinya, gunakan `Request Late Access`, termasuk untuk bulan lalu.

**Bisakah saya mengubah timesheet yang sudah dikirim?** Tidak, dan juga tidak bisa dihapus. Minta orang yang menyetujuinya untuk menolak, lalu perbaiki dan kirim ulang.

**Mengapa `Quota MD` dan `Remaining` kosong?** Keduanya terisi setelah Anda memilih ticket.

<!-- sources: routes/web.php, app/Http/Controllers/CalendarController.php, app/Http/Controllers/TimesheetController.php, resources/views/calendar/timesheets.blade.php -->
