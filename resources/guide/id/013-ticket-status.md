---
key: ticket-status
chapter: work-service
order: 13
title: Status ticket dan penutupan
summary: Arti setiap status ticket, siapa yang dapat mengubahnya, dan apa yang terjadi pada customer serta jam SLA saat status berubah.
requires: [tickets.inbox]
updated: 2026-11-02
---
Setiap ticket punya satu status. Mengubahnya memberi tahu customer posisi ticket dan juga menggerakkan jam SLA, jadi penting untuk memilih dengan benar.

## Siapa yang menggunakan

Helpdesk dan administrator mengubah status. Ticket lead, member, dan head membacanya. Kontrol status ada di halaman ticket dan hanya muncul untuk role yang diberi izin mengubah kolom ticket di Menu Access.

## Sebelum Anda mulai

- Baca ticketnya dulu. Pilih status yang sesuai dengan siapa yang harus bertindak berikutnya.
- Mengubah status tidak meminta konfirmasi langkah berikutnya, jadi periksa dampaknya ke customer di bawah sebelum menutup atau membatalkan.

## Langkah demi langkah

1. Buka ticket dari `Ticket`.
2. Ubah status di detail ticket. Pilihannya:
   - `Open`: baru, belum ada yang mulai.
   - `In Process`: tim sedang mengerjakan.
   - `Waiting on Customer`: tim butuh jawaban dari customer.
   - `Waiting on 3rd Party`: tim menunggu vendor atau SAP.
   - `Waiting to Confirmation`: solusi sudah diberikan dan customer harus mengonfirmasi.
   - `Hold`: pekerjaan sengaja dijeda.
   - `Cancelled`: ticket ditarik.
   - `Closed`: ticket selesai.
3. Periksa baris yang ditambahkan sistem ke chat saat Anda memilih `Closed` atau `Cancelled`.

## Aturan yang perlu diketahui

- Status apa pun dapat diubah ke status lain. Tidak ada urutan tetap, dan ticket yang ditutup dapat dibuka lagi dengan memilih status lain.
- Memilih `Closed` mencatat tanggal selesai ticket. Membuka kembali ticket yang ditutup menghapus tanggal itu.
- Bila ticket punya customer, customer menerima notifikasi di portal customer setiap status berubah. Untuk `Closed` dan `Cancelled`, customer juga menerima email bila alamatnya diketahui, dan chat mencatat baris yang menyebut status berubah menjadi Closed, oleh siapa, dan kapan.
- Jam SLA mengikuti status. `Waiting on Customer`, `Waiting to Confirmation`, `Hold`, dan `Waiting on 3rd Party` menghentikan jam, karena ball ada di customer atau pihak ketiga. `Open` dan `In Process` menjalankannya. `Closed` dan `Cancelled` mengakhirinya: ticket yang dibatalkan selalu dihitung terpenuhi. Rinciannya ada di topik SLA Report.
- Ticket tersembunyi mempertahankan statusnya tetapi tidak ikut daftar dan laporan. Lihat `Hidden Tickets`.

## Apa yang terjadi selanjutnya

Ticket pindah ke tab dan filter yang sesuai. SLA report dan pengingat ticket open langsung membaca status baru.

## Pertanyaan umum

**Saya tidak sengaja menutup ticket.** Ubah statusnya kembali, misalnya ke `In Process`. Tanggal selesai dihapus, tetapi customer sudah menerima email penutupan.

**Mengapa waktu SLA berhenti bertambah?** Ticket berstatus menunggu atau hold. Jam berjalan lagi saat status kembali ke `Open` atau `In Process`, atau saat customer membalas.

<!-- sources: app/Http/Controllers/TicketController.php, app/Services/SlaService.php -->
