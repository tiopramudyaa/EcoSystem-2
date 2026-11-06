---
key: ticket-meeting
chapter: work-service
order: 18
title: Meeting ticket
summary: Jadwalkan meeting dengan customer dari sebuah ticket, kirim undangannya, dan pahami bagaimana jam SLA ditahan selama meeting.
requires: [ticket.meeting]
updated: 2026-11-02
---
Tombol `Meeting` pada ticket menjadwalkan panggilan dengan customer dan mencatatnya di ticket. Selama meeting berjalan, jam SLA ditahan.

## Siapa yang menggunakan

Helpdesk dan siapa pun yang diberi izin `Meeting` di Menu Access.

## Sebelum Anda mulai

- Tentukan waktu mulai dan selesai dan siapkan tautan meeting (Teams, Meet, atau Zoom). Tautan bersifat opsional.
- Customer perlu punya alamat email di ticket, bila tidak undangan hanya disimpan sebagai catatan di ticket.

## Langkah demi langkah

1. Buka ticket dan pilih `Meeting`. Bila sudah ada meeting yang berjalan, tombolnya bertuliskan `Meeting Active`, dan memilihnya memungkinkan Anda menjadwalkan yang baru.
2. Atur `Start` dan `End` (tanggal dan jam) dan, bila ada, `Meeting Link`.
3. Tulis undangan di kotak catatan, misalnya agenda.
4. Centang `Save as template` dan beri nama bila undangan ini akan diulang. Template hanya dapat dipakai di ticket yang sama.
5. Pilih tombol konfirmasi. Waktu dan tautan dikirim ke customer lewat email, jadwalnya diumumkan di chat Teams ticket bila ada, dan ticket naik ke posisi teratas daftar.

## Aturan yang perlu diketahui

- Jam selesai harus setelah jam mulai.
- Meeting menahan jam SLA sejak meeting dimulai. Ball ticket pindah ke customer dan jedanya dicatat sebagai jeda meeting.
- Pesan chat dari customer tidak mengakhiri penahanan. Penahanan berlangsung sampai jam selesai meeting, lalu berakhir sendiri: tidak ada tombol End Meeting.
- Menjadwalkan meeting lain saat satu meeting aktif memindahkan jam selesai penahanan ke meeting yang baru.
- Penahanan tampil di SLA Log ticket sebagai waktu tunggu.

## Apa yang terjadi selanjutnya

Customer menerima undangan, percakapan mencatatnya sebagai pesan meeting, dan jam SLA berjalan lagi pada jam selesai.

## Pertanyaan umum

**Customer tidak menerima email.** Periksa email customer di ticket. Bila tidak ada, undangan hanya disimpan sebagai pesan di ticket.

**Bisakah saya mengakhiri meeting lebih awal?** Tidak ada akhir manual. Jadwalkan meeting dengan jam selesai yang tepat.

<!-- sources: app/Http/Controllers/SlaController.php, app/Services/SlaService.php, resources/views/ticket/show.blade.php -->
