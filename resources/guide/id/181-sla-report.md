---
key: sla-report
chapter: sla
order: 11
title: SLA Report
summary: Pantau respons dan penyelesaian setiap ticket terhadap SLA-nya, baca log jeda sebuah ticket, dan ekspor laporan.
requires: [sla.report, ticket.sla-log]
updated: 2026-11-02
---
`SLA` › `SLA Report` (SLA Tracker) menampilkan untuk setiap ticket apakah sudah dijawab dan diselesaikan dalam waktu yang disepakati. Halaman ini menyegarkan diri setiap 60 detik.

## Siapa yang menggunakan

Helpdesk, support manager dan head, serta role mana pun yang diberi menu `SLA` › `SLA Report` di Menu Access. `SLA Log` per ticket juga tersedia dari ruang chat ticket bagi role yang diberi tombol SLA Log di Menu Access.

## Sebelum Anda mulai

- Hanya ticket bertipe `Incident`, `Change Request`, dan `Consult` yang punya SLA penuh. Tipe lain tidak didaftar.
- Targetnya berasal dari `SLA` › `SLA Config`. Ticket tanpa policy tidak menampilkan target.

## Langkah demi langkah

![SLA Tracker dengan filter dan kolom pertama laporan](images/guide/sla-report.png)

*Tangkapan layar dengan data contoh.*

1. Buka `SLA` › `SLA Report`. Persempit daftar dengan `All Customers`, `All Dates` (pilih `From` dan `To`), `All Statuses`, `All Type of Service`, dan `All Types`, atau cari ticket dengan `Cari tiket...`. `Refresh` memuat ulang, dan halaman menampilkan sampai 200 catatan.
2. Blok pertama `INFORMASI TIKET` menampilkan tahun, customer, nomor ticket, module, prioritas, skala, issue, PIC customer dan fungsional, tipe layanan (MO, CR, atau SR), tipe, status, dan tanggal ticket diterima.

![Blok SLA Response dan SLA Resolution pada laporan](images/guide/sla-report-columns.png)

*Tangkapan layar dengan data contoh.*

3. `SLA RESPONSE` menampilkan target, kapan jam SLA mulai, kapan respons jatuh tempo, kapan ticket direspons, berapa lama, dan apakah target `Achieved`.
4. `SLA RESOLUTION` menampilkan target dalam jam, waktu mulai dan jatuh tempo, tanggal pertama diselesaikan, tanggal dokumen atau email penyelesaian dikirim, durasi penyelesaian, target waktu dan statusnya, tanggal ditutup, dan catatan. Durasi dihitung stop-and-go: waktu menunggu customer atau pihak ketiga tidak dihitung.
5. Pilih `Export SLA` untuk mengunduh laporan sebagai spreadsheet.

![Jendela SLA Log sebuah ticket dengan respons, penyelesaian, waktu tunggu, dan ball holder](images/guide/sla-report-log.png)

*Tangkapan layar dengan data contoh.*

6. Di kolom terakhir pilih `Log / PDF` untuk membuka `SLA Log` ticket. Jendelanya menampilkan respons dan penyelesaian terhadap targetnya, total waktu jeda (`Waiting`), dan `Ball Holder`: pihak yang ditunggu ticket. Daftar di bawahnya mencatat setiap kejadian beserta waktunya. Pilih `Download PDF` untuk menyimpan salinan.

## Aturan yang perlu diketahui

- Jam SLA hanya berjalan saat ticket ada di helpdesk. `Waiting on Customer`, `Waiting to Confirmation`, `Hold`, dan `Waiting on 3rd Party` menghentikannya, dan Ball Holder menunjukkan siapa yang ditunggu ticket.
- Saat customer membalas, ball kembali ke helpdesk dan jam berjalan lagi, kecuali penahanan meeting sedang aktif (lihat topik meeting ticket).
- Jam dihitung sebagai jam kerja (Senin sampai Jumat, tidak termasuk hari libur nasional Indonesia, dan tidak termasuk istirahat pada policy) kecuali policy `24/7 Calendar Hours`.
- `Closed` dan `Cancelled` menyelesaikan SLA. Ticket yang dibatalkan selalu dihitung terpenuhi.
- Target respons diperiksa pada balasan pertama. Waktu penyelesaian adalah waktu bersih: total waktu berjalan dikurangi seluruh waktu tunggu.

## Apa yang terjadi selanjutnya

Laporan hanya membaca data. Perbaiki target yang salah di `SLA Config`, dan ubah status ticket di ticketnya sendiri.

## Pertanyaan umum

**Sebuah ticket tidak ada di laporan.** Tipenya bukan `Incident`, `Change Request`, atau `Consult`, atau sebuah filter menyembunyikannya.

**Mengapa waktu penyelesaian tampak lebih pendek dari waktu sebenarnya?** Jam berhenti saat ball ada di customer atau pihak ketiga. Buka `Log / PDF` untuk melihat setiap jeda.

<!-- sources: routes/web.php, resources/views/admin/sla/report.blade.php, app/Http/Controllers/SlaController.php, app/Services/SlaService.php -->
