---
key: automatic-reminders
chapter: reference
order: 140
title: Pengingat otomatis
summary: Pengingat yang dikirim EcoSystem sendiri: ticket open, timesheet hari ini, tanggal akhir kontrak, dan tanggal invoice.
requires: []
updated: 2026-11-02
---
EcoSystem mengirim beberapa pengingat sendiri agar tidak ada yang menunggu tanpa disadari. Topik ini mendaftar siapa yang menerima masing-masing, kapan, dan apa yang menghentikannya.

## Siapa yang menggunakan

Semua yang mungkin menerima salah satunya: module lead, konsultan, serta Head dan administrator project. Anda tidak perlu melakukan apa pun untuk mengaktifkannya.

## Sebelum Anda mulai

- Pengingat dikirim oleh tugas terjadwal di server. Bila sebuah tugas tidak berjalan di sana, pengingatnya tidak datang. Administrator dapat memeriksanya di `Control Center` › `Schedule Monitor`.

## Aturan yang perlu diketahui

### Ticket open

- Ticket yang masih `Open` mengingatkan module lead-nya lewat kartu Microsoft Teams. Ticket yang sama diingatkan lagi setelah jeda, yaitu 60 menit secara bawaan.
- Berhenti saat ticket keluar dari `Open`. Ticket tersembunyi tidak diingatkan.
- Hanya bekerja bila koneksi Teams (Power Automate) sudah dikonfigurasi. Bila belum, tidak ada yang dikirim.

### Timesheet hari ini

- Setiap hari pukul 15:00 notifikasi bell dikirim ke konsultan yang belum punya entri timesheet bertanggal hari ini. Draft sudah dihitung terisi.
- Tidak dikirim pada akhir pekan atau hari libur nasional, dan paling banyak sekali per orang per hari.

### Tanggal project

- Setiap hari pukul 07:00 Head of Project dan Project Administrator menerima notifikasi bell untuk:
  - project yang tanggal akhir kontraknya dalam 30 hari ke depan, atau sudah lewat, dan belum ditutup. Pesannya menyebut addendum mungkin diperlukan;
  - termin pembayaran yang tanggal estimasinya sudah tiba sementara `Submit Invoice Date` masih kosong.
- Pengingat langsung diperbarui saat tanggal kontrak atau termin pembayaran diubah, sehingga hilang saat kondisinya teratasi.

### Saat submit dan saat status berubah

- Mengirim timesheet memberi notifikasi ke penyetuju yang tepat, lihat topik timesheet.
- Perubahan status ticket memberi notifikasi ke customer, lihat topik status ticket.

## Apa yang terjadi selanjutnya

Pengingat muncul di ikon bell (atau sebagai kartu Teams untuk ticket open). Tindak lanjuti dan pengingat berhenti sendiri: ubah status ticket, isi timesheet, isi tanggal invoice, atau perpanjang kontrak.

## Pertanyaan umum

**Saya tidak menerima pengingat timesheet.** Mungkin Anda sudah mencatat hari ini (draft dihitung), hari itu akhir pekan atau libur, atau posisi Anda bukan konsultan.

**Pengingat project tidak hilang.** Isi `Submit Invoice Date` pada termin pembayaran, atau ubah tanggal akhir kontrak project, atau tutup project.

<!-- sources: routes/console.php, app/Console/Commands/SendOpenTicketReminders.php, app/Console/Commands/SendTimesheetReminders.php, app/Services/ProjectReminderService.php -->
