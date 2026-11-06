---
key: cc-active-sessions
chapter: control-center
order: 23
title: Active Sessions
summary: Lihat siapa yang sedang login sekarang dan akhiri sesi (force logout) bila perlu.
requires: [control-center.sessions]
updated: 2026-11-02
---
`Active Sessions` mendaftar orang yang sedang login di EcoSystem, satu baris per sesi peramban. Administrator dapat mengakhiri sesi seketika, misalnya saat perangkat hilang atau akun disalahgunakan.

## Siapa yang menggunakan

Administrator, dan role mana pun yang diberi menu `Control Center` › `Active Sessions` di Menu Access.

## Sebelum Anda mulai

- Mengakhiri sesi langsung mengeluarkan orangnya. Pekerjaan yang belum disimpan di halaman terbukanya hilang, jadi beri tahu bila bisa.
- Orang itu bisa login lagi dengan passwordnya. Bila akunnya sendiri harus berhenti, minta administrator memblokirnya di data karyawan.

## Langkah demi langkah

![Halaman Active Sessions dengan satu baris per sesi dan tautan Force Logout](images/guide/cc-active-sessions.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Control Center` › `Active Sessions`.
2. Baca dua kartu: `Active Sessions` dan `Unique Users`. Satu orang dengan beberapa peramban memiliki beberapa sesi.
3. Baca setiap baris: orangnya (nama, kode karyawan, email), `IP Address`, `Location`, `Browser / Device`, `Last Activity`, dan `Status`.
4. Cari sesi dengan kotak pencarian (nama, email, IP, atau lokasi) atau klik `User` atau `Last Activity` untuk mengurutkan.
5. Untuk mengakhiri satu sesi, pilih `Force Logout` pada barisnya lalu konfirmasi.
6. Untuk mengakhiri semua sesi lain, pilih `Force Logout All Others` lalu konfirmasi. Sesi Anda sendiri tetap aktif.

## Apa yang terjadi selanjutnya

Orang itu kembali ke halaman login pada klik berikutnya.

## Pertanyaan umum

**Mengapa tidak ada `Force Logout` pada baris saya?** Baris Anda bertanda `You`. Sesi sendiri tidak bisa diakhiri di sini, pakai logout biasa.

**Mengapa sebuah baris bertanda `Protected`?** Akun sistem yang dilindungi tidak bisa dikeluarkan dari halaman ini, dan `Force Logout All Others` melewatinya.

<!-- sources: routes/web.php, app/Http/Controllers/AdminSessionController.php, resources/views/admin/sessions.blade.php -->
