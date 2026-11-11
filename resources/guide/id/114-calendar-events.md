---
key: calendar-events
chapter: my-workspace
order: 114
title: Events
summary: Lihat event di kalender dan tambahkan meeting, task, deadline, dan reminder.
requires: [calendar.events]
updated: 2026-11-11
---
`Calendar` › `Events` menampilkan event yang boleh Anda lihat dan memungkinkan Anda menambah event baru.

## Siapa yang menggunakan

Employee yang memiliki `Events` di bawah `Calendar`.

## Sebelum Anda mulai

- Ketahui judul, jenis, tanggal, dan jam mulai event.
- Tombol `Create Event` hanya tampil bila role Anda mengizinkan. Jika tidak ada, hubungi administrator.

## Langkah demi langkah

### Membaca kalender

![Halaman Events Calendar bulan Oktober dengan Today, navigasi bulan, dan tampilan Month, Week, dan Day](images/guide/calendar-events.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Calendar` › `Events`. Halamannya berjudul `Events Calendar`.
2. Gunakan tombol panah untuk berpindah bulan, atau pilih `Today` untuk kembali ke tanggal sekarang.
3. Pindah antara tampilan `Month`, `Week`, dan `Day`.
4. Pilih sebuah event untuk membaca rinciannya.

### Membuat event

![Formulir Create New Event dengan judul, jenis, deskripsi, tanggal, jam, dan lokasi](images/guide/calendar-create-event.png)

*Tangkapan layar dengan data contoh.*

1. Pilih `Create Event`. Formulirnya berjudul `Create New Event`.
2. Isi `Event Title` dan pilih `Event Type`: `Meeting`, `Task`, `Deadline`, `Urgent`, atau `Reminder`.
3. Isi `Start Date` dan `Start Time`. Tambahkan `End Date` dan `End Time` bila perlu, atau centang `All day event`.
4. Tambahkan `Description` dan `Location` bila berguna.
5. Pilih `Save Event`.

## Aturan yang perlu diketahui

Aturan ini diperiksa saat Anda menyimpan. Bila ada yang dilanggar, EcoSystem menampilkan pesan dan tidak menyimpan.

- Membuat event memerlukan fungsi `Create Event` di `Menu Access`. Tanpa fungsi itu permintaan ditolak, meskipun dikirim langsung.
- `Event Title`, `Event Type`, `Start Date`, dan `Start Time` wajib diisi.
- `Event Type` harus `Meeting`, `Task`, `Deadline`, `Urgent`, atau `Reminder`.
- `End Date` tidak boleh lebih awal dari `Start Date`.
- Judul dan lokasi masing-masing paling banyak 255 karakter.

## Apa yang terjadi selanjutnya

Event baru tersimpan dan tampil di kalender. Pilih event itu untuk membaca rinciannya.

## Pertanyaan umum

**Saya tidak melihat `Create Event`.** Role Anda tidak memiliki fungsi itu. Minta administrator mengaktifkannya di `Menu Access`.

**Di mana saya mencatat jam kerja?** Di `Calendar` › `Timesheets`.

<!-- sources: routes/web.php, app/Http/Controllers/CalendarController.php, resources/views/calendar/events.blade.php -->
