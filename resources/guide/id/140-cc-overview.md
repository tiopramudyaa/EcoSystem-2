---
key: cc-overview
chapter: control-center
order: 10
title: Control Center Overview
summary: Halaman awal Control Center: angka terkini untuk login, keamanan, sesi, job gagal, dan tugas terjadwal, dengan pintasan ke setiap halaman.
requires: [control-center.overview]
updated: 2026-11-02
---
Halaman `Control Center` adalah titik awal untuk administrasi sistem. Setiap kartu memberi gambaran singkat satu alat dan tombol untuk membuka halaman lengkapnya.

## Siapa yang menggunakan

Administrator, dan role mana pun yang diberi menu `Control Center` › `Overview` di Menu Access. Sebuah kartu hanya membuka halaman yang boleh dipakai role Anda.

## Sebelum Anda mulai

- Halaman ini untuk membaca dan berpindah halaman. Tidak ada data yang berubah dari sini.
- Angka dihitung saat halaman dibuka. Muat ulang halaman untuk melihat angka terbaru.

## Langkah demi langkah

![Halaman Control Center dengan satu kartu per alat dan tombol untuk membukanya](images/guide/cc-overview.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Control Center`.
2. Baca kartu dari kiri ke kanan:
   - `Activity Log`: total catatan, login berhasil, dan percobaan gagal.
   - `Security Center`: kejadian keamanan terbuka, kejadian kritis atau tinggi, dan akun terkunci.
   - `Active Sessions`: sesi aktif, orang yang sedang login, dan tamu.
   - `Failed Jobs`: jumlah job latar belakang yang gagal dan kapan yang terakhir gagal.
   - `Schedule Monitor`: jumlah tugas terjadwal yang perlu perhatian (gagal, terlambat, atau belum pernah jalan).
   - `Notification Sounds`, `AI Settings`, dan `Backup & Export`: ringkasan singkat dan pintu masuknya.
3. Angka berwarna merah atau kuning berarti ada yang perlu dilihat. Pakai tombol di kartu itu (misalnya `Open Failed Jobs`) untuk melihat detail dan bertindak.

## Apa yang terjadi selanjutnya

Anda masuk ke halaman lengkap alat tersebut. Pakai tombol kembali di peramban atau menu `Control Center` untuk kembali ke sini.

## Pertanyaan umum

**Kartu yang saya harapkan tidak ada atau muncul pesan tidak punya akses.** Role Anda belum memiliki halaman itu di Menu Access. Minta administrator memberikannya.

**Kartu Schedule Monitor menyebut ada tugas perlu perhatian di server uji.** Tugas terjadwal hanya berjalan di tempat scheduler aktif. Lihat bagian Schedule Monitor pada topik keamanan.

<!-- sources: routes/web.php, resources/views/admin/index.blade.php -->
