---
key: mg-hidden-tickets
chapter: management
order: 13
title: Hidden Tickets
summary: Temukan ticket yang disembunyikan dari daftar utama dan tampilkan kembali.
requires: [management.hidden-tickets]
updated: 2026-11-02
---
Sebuah ticket dapat disembunyikan dari daftar `Ticket` utama, misalnya ticket duplikat atau ticket uji. `Management` › `Hidden Tickets` mendaftarkannya agar tidak ada yang hilang dan Anda dapat memulihkannya.

## Siapa yang menggunakan

Helpdesk dan administrator, dan role mana pun yang diberi menu `Management` › `Hidden Tickets` di Menu Access.

## Sebelum Anda mulai

- Ticket tersembunyi juga tidak ikut dalam laporan, seperti `Ticketing Overview`, `Resolution Days`, dan `Log Shifting`. Menampilkannya kembali memasukkannya lagi ke angka laporan.
- Halaman ini dan layarnya sebagian berbahasa Indonesia: `Tiket` berarti ticket dan `Tampilkan` berarti show.

## Langkah demi langkah

![Daftar Hidden Tickets dengan jumlah ticket tersembunyi dan tombol untuk menampilkan tiap ticket kembali](images/guide/mgmt-hidden-tickets.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Management` › `Hidden Tickets`. Lencana oranye menghitung ticket tersembunyi.
2. Baca setiap baris: `Tiket #`, `Deskripsi`, `Customer`, `Ticket Lead`, `Priority`, `Status`, dan `Tgl Dibuat` (tanggal dibuat).
3. Pakai kotak pencarian (`Cari nomor tiket atau deskripsi...`) untuk menemukan satu ticket dari nomor atau teksnya.
4. Untuk memulihkan ticket, pilih `Tampilkan` pada barisnya lalu konfirmasi `Tampilkan Kembali`.

Untuk menyembunyikan ticket, buka ticketnya dan pilih `Sembunyikan Tiket`. Ini memerlukan izin `ticket.hide`. Pada ticket yang tersembunyi, tempat yang sama menampilkan `Tampilkan Kembali`.

## Apa yang terjadi selanjutnya

Ticket kembali ke daftar `Ticket` utama dan ke laporan, dengan seluruh riwayatnya utuh.

## Pertanyaan umum

**Saya tidak menemukan ticket di daftar utama.** Mungkin disembunyikan. Cari nomornya di sini.

**Apakah ticket tersembunyi dihapus?** Tidak. Tidak ada yang dihapus. Ticket hanya tidak ikut daftar dan laporan.

<!-- sources: resources/views/management/hidden-tickets/index.blade.php, resources/views/ticket/show.blade.php -->
