---
key: cc-failed-jobs
chapter: control-center
order: 24
title: Failed Jobs
summary: Temukan job latar belakang yang gagal (email, sinkron chat, notifikasi), baca errornya, lalu coba ulang atau hapus.
requires: [control-center.failed-jobs]
updated: 2026-11-02
---
EcoSystem mengirim email, menyinkronkan chat, dan mengerjakan tugas lambat lain di latar belakang. Saat salah satu job gagal, job itu menunggu di `Failed Jobs` agar tidak hilang diam-diam. Administrator dapat membaca errornya lalu memutuskan untuk mencoba ulang atau menghapusnya.

## Siapa yang menggunakan

Administrator, dan role mana pun yang diberi menu `Control Center` › `Failed Jobs` di Menu Access.

## Sebelum Anda mulai

- Baca errornya dulu. Job yang gagal karena server email sedang mati aman dicoba ulang setelah server pulih. Job yang gagal lagi dengan alasan sama perlu diperbaiki, bukan dicoba ulang.
- `Clear All` menghapus semua job gagal selamanya, dan `Delete` menghapus satu. Keduanya tidak bisa dibatalkan.

## Langkah demi langkah

![Halaman Failed Jobs dengan tabel job gagal, masing-masing dengan Retry dan Delete](images/guide/cc-failed-jobs.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Control Center` › `Failed Jobs`.
2. Baca kartunya: `Total Failed`, `Oldest Failure`, dan `Newest Failure`.
3. Baca setiap baris: nama `Job` beserta id-nya, `Queue`, `Error` (dipersingkat), dan `Failed At`.
4. Cari job dengan kotak pencarian (nama job, queue, atau teks error) atau daftar queue. Klik `Queue` atau `Failed At` untuk mengurutkan.
5. Untuk mencoba satu job lagi, pilih `Retry` pada barisnya. Untuk mencoba semuanya, pilih `Retry All`.
6. Untuk membuang job yang tidak diperlukan, pilih `Delete` lalu konfirmasi. Untuk membuang semuanya, pilih `Clear All` lalu konfirmasi.

## Apa yang terjadi selanjutnya

Job yang dicoba ulang kembali ke antrean dan berjalan lagi. Bila gagal lagi, job muncul kembali di halaman ini dengan waktu baru. Daftar yang terus bertambah dengan error yang sama biasanya berarti layanan luar (email atau Microsoft Graph) perlu perhatian.

## Pertanyaan umum

**Kartu di `Control Center` masih menunjukkan job gagal padahal sudah saya coba ulang.** Kartu menghitung yang masih ada di sini. Muat ulang kedua halaman.

**Perlukah saya membersihkan semuanya secara berkala?** Hanya setelah Anda memahami errornya. Job yang dihapus tidak bisa dipulihkan.

<!-- sources: routes/web.php, app/Http/Controllers/AdminJobController.php, resources/views/admin/failed-jobs.blade.php -->
