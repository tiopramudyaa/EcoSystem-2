---
key: microsoft-365-login-setup
chapter: getting-started
order: 53
title: Menyiapkan login Microsoft 365
summary: Cara EC Administrator menghubungkan EcoSystem ke tenant Microsoft 365 perusahaan agar orang dapat masuk dengan Microsoft.
requires: ["@role:EC Administrator"]
updated: 2026-11-15
---
Topik ini menjelaskan cara mengaktifkan dan mengatur Sign in with Microsoft untuk seluruh perusahaan. Cara orang memakainya untuk masuk ada di [[sign-in-and-security]].

## Siapa yang menggunakan

Hanya EC Administrator. Role lain tidak melihat topik ini dan tidak melihat kotak `Microsoft 365 tenant configuration`.

## Sebelum Anda mulai

- Anda perlu akses ke app registration perusahaan di Microsoft Entra, supaya dapat menambahkan redirect URI dan membaca tenant ID, client ID, dan client secret.
- Siapkan Value dari client secret. Microsoft hanya menampilkannya sekali, saat secret dibuat.

## Langkah demi langkah

Hanya EC Administrator yang melihat kotak `Microsoft 365 tenant configuration` di bagian `Microsoft account` pada `Settings` tab `Security`.

![Kotak Microsoft 365 tenant configuration dengan tenant ID, client ID, client secret, redirect URI, dan pilihan enable](images/guide/settings-microsoft-config.png)

*Tangkapan layar dengan data contoh.*

1. Di Microsoft Entra, buka app registration perusahaan lalu tambahkan redirect URI bertipe `Web` dengan alamat yang persis sama seperti di `Web redirect URI` (pakai `Copy`).
2. Pastikan app registration mengizinkan izin dasar untuk masuk dan administrator sudah menyetujuinya untuk organisasi.
3. Di EcoSystem, isi `Directory (tenant) ID`, `Application (client) ID`, dan `Client secret value`. Pakai Value dari secret, bukan ID secret.
4. Centang `Enable Microsoft 365 login` lalu pilih `Save M365 configuration`.

Kosongkan `Client secret value` untuk mempertahankan secret yang sudah tersimpan. Secret disimpan terenkripsi dan tidak pernah ditampilkan lagi. Kolom ID yang kosong memakai app registration Microsoft bersama milik server, jika ada.

## Aturan yang perlu diketahui

- Hanya EC Administrator yang dapat mengaktifkan atau mengubah pengaturan masuk Microsoft. Sebelum itu fitur ini mati.
- Tenant ID dan client ID harus berbentuk `00000000-0000-0000-0000-000000000000`. Login tidak dapat diaktifkan selama salah satu dari ketiga nilai belum terisi.
- Tombol `Sign in with Microsoft` muncul di halaman masuk hanya jika login diaktifkan dan tenant ID, client ID, serta client secret sudah lengkap.
- Hanya akun dari organisasi Microsoft yang dikonfigurasi yang dapat masuk dengan cara ini.
- Setiap perubahan konfigurasi tercatat di Audit Log. Client secret tidak pernah dicatat di sana.
- Masuk dengan password tetap tersedia dan tidak berubah, dan verifikasi 2 langkah tetap berlaku setelah masuk Microsoft.

## Apa yang terjadi selanjutnya

Setelah Anda menyimpan dengan `Enable Microsoft 365 login` tercentang, orang melihat `Sign in with Microsoft` di halaman masuk dan dapat menyambungkan akun Microsoft mereka sendiri di `Settings`.

## Pertanyaan umum

**Tombol Sign in with Microsoft masih tidak ada di halaman masuk.** Periksa bahwa `Enable Microsoft 365 login` tercentang dan tenant ID, client ID, serta client secret sudah terisi semua.

**Bisakah saya melihat client secret yang tersimpan lagi?** Tidak. Secret terenkripsi dan tidak pernah ditampilkan. Masukkan Value baru untuk menggantinya.

<!-- sources: resources/views/settings/index.blade.php, app/Http/Controllers/MicrosoftAuthController.php, app/Support/MicrosoftLoginSettings.php, app/Http/Controllers/AuthController.php -->
