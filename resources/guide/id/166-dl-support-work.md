---
key: dl-support-work
chapter: delivery
order: 22
title: Pekerjaan support: Activities, SLA, Plan Cost, dan Recons
summary: Rencanakan aktivitas sebuah support, atur kebijakan SLA dan rencana biayanya, dan kelompokkan ticket yang sudah closed ke batch recons.
requires: [delivery-support.activities.view, delivery-support.sla.view, delivery-support.plan-cost.view, delivery-support.recons.view]
updated: 2026-11-02
---
Bagian bawah halaman detail support mencakup jalannya support sehari-hari: aktivitas dan planning, target SLA, rencana biaya, dan rekonsiliasi ticket yang sudah closed.

## Siapa yang menggunakan

Support manager, helpdesk, support head, dan project administrator. Setiap bagian punya izin `view`, `edit`, dan `create or delete` sendiri di Menu Access.

## Sebelum Anda mulai

- Kebijakan SLA menentukan target respons dan penyelesaian ticket di bawah support ini. Atur sebelum ticket masuk.
- Recons hanya mengambil ticket closed yang punya Customer MD dan belum pernah direkonsiliasi.

## Langkah demi langkah

### Activities dan planning

![Halaman Support Planning dengan phase management dan ikhtisar progres](images/guide/dlv-support-planning.png)

*Tangkapan layar dengan data contoh.*

1. Di `Activities` baca aktivitas support beserta progresnya. Pilih `View all` atau `Open Planning` untuk halaman penuh.
2. Di halaman planning pilih `Configure Phases` untuk membuat fase, lalu tambah grup, tahap, dan aktivitas dengan bobot, tanggal, dan status. Ticket yang terhubung muncul sebagai aktivitas.
3. Beralih antara `Table View`, `Gantt Chart`, dan `S-Curve`, dan pilih `Export` untuk mengunduh. Ikhtisar menampilkan progres keseluruhan dan progres per fase.

### SLA Configuration

![Tabel SLA Configuration dengan tombol Add Policy dan bagian Plan Cost di bawahnya](images/guide/dlv-support-sla.png)

*Tangkapan layar dengan data contoh.*

1. Pilih tab `SLA` lalu `Add Policy`.
2. Pilih `Priority` dan `Scale` (`Simple`, `Medium`, atau `Complex`). Isi waktu respons, dalam jam atau menit, dan waktu penyelesaian dalam jam.
3. Pilih mode: centang `24/7 Calendar Hours` untuk menghitung setiap jam, atau biarkan mati dan atur `Work Start` dan `Work End` agar hanya jam kerja yang dihitung.
4. Simpan. Tabel mendaftar setiap kebijakan dengan mode dan statusnya. Ubah atau hapus kebijakan dari barisnya.

### Plan Cost

1. Pilih tab `Plan Cost`. Kartu menampilkan total budget, release, aktual, dan yang masih tersedia.
2. Support baru dimulai dengan dua baris, `INDIRECT COST` dan `DIRECT COST`. Pilih `Add sub-item` di bawah sebuah baris, atau `Add Cost Item`, untuk menambah biaya dengan budget, release, dan aktual.

### Recons

![Bagian Recons dengan tampilan Tickets dan Recons List serta tombol New Recons](images/guide/dlv-support-recons.png)

*Tangkapan layar dengan data contoh.*

1. Pilih tab `Recons`. `Tickets` mendaftar ticket support dengan tanggal close, tipe, Customer MD, dan nomor recons. `Recons List` mendaftar batch-nya.
2. Pilih `New Recons`, pilih ticket closed yang memenuhi syarat, lalu simpan. Recons dimulai sebagai draft dan kemudian disubmit.
3. Tanggal close terisi saat ticket mencapai `Closed`. Ticket yang sudah masuk recons tidak dapat ditambahkan lagi.

## Aturan yang perlu diketahui

- Data jadwal di balik tampilan tabel, Gantt, dan S-curve (termasuk ringkasannya) hanya diberikan kepada role yang memegang fungsi lihat `Activities & Planning` di `Menu Access`. Selain itu ditolak, meskipun permintaan dikirim langsung.
- Di bagian SLA, membaca policy memerlukan fungsi lihat `SLA Configuration` (atau `SLA Report`), dan membuat, mengubah, atau menghapus policy memerlukan fungsi buat atau hapus `SLA Configuration` (atau `SLA Config`). Setiap policy terikat pada tepat satu delivery support.
- Menugaskan ticket ke support dari sini memerlukan fungsi `Assign to Delivery Support`, sama dengan yang dipakai di halaman ticket.

## Apa yang terjadi selanjutnya

Kebijakan SLA berlaku untuk ticket support ini sejak saat itu. Batch recons menjadi dasar rekonsiliasi mandays dengan customer.

## Pertanyaan umum

**Ticket closed tidak ditawarkan untuk recons.** Mungkin belum punya Customer MD, atau sudah ada di recons lain.

**Jam SLA menghitung malam hari.** Kebijakan diatur ke `24/7 Calendar Hours`. Matikan dan atur jendela kerja agar hanya jam kerja yang dihitung.

<!-- sources: routes/delivery-support.php, resources/views/delivery/support/list/show.blade.php, resources/views/delivery/support/planning/index.blade.php -->
