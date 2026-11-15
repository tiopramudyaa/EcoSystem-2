---
key: forgot-password
chapter: getting-started
order: 52
title: Lupa password
summary: Cara masuk kembali ke akun saat Anda lupa password, dengan tautan reset yang dikirim ke email Anda.
requires: []
updated: 2026-11-12
---
Jika Anda lupa password, Anda dapat membuat yang baru sendiri. EcoSystem mengirim tautan reset ke email akun Anda. Cara masuk yang biasa ada di [[sign-in-and-security]].

## Siapa yang menggunakan

Semua orang yang masuk ke EcoSystem dengan password.

## Sebelum Anda mulai

- Ketahui alamat email yang tersimpan di akun EcoSystem Anda. Tautan hanya dikirim ke alamat itu.
- Pastikan Anda bisa membuka kotak email itu sekarang. Tautan berlaku 24 jam.

## Langkah demi langkah

### Meminta tautan reset

1. Di halaman masuk, pilih `Forgot password?`.
2. Ketik email Anda di `Registered Email` lalu pilih `Send Reset Link`.

![Halaman Reset your password dengan kolom Registered Email dan tombol Send Reset Link](images/guide/forgot-password-request.png)

*Tangkapan layar dengan data contoh.*

3. Halaman `Check Your Email` muncul dan menampilkan email Anda dengan sebagian karakternya disembunyikan.

![Halaman Check Your Email yang menampilkan alamat email tersamarkan, petunjuk Reset My Password, dan tips bila email tidak datang](images/guide/forgot-password-check-email.png)

*Tangkapan layar dengan data contoh.*

### Membuat password baru

1. Buka email lalu pilih `Reset My Password`.
2. Di halaman `Set New Password`, ketik password baru di `New Password` dan ulangi di `Confirm Password`. Dua centang, `At least 8 characters` dan `Passwords match`, harus sama-sama hijau.
3. Pilih `Save Password`.

![Halaman Set New Password dengan kedua kolom password terisi dan kedua centang persyaratan berwarna hijau](images/guide/forgot-password-new-password.png)

*Tangkapan layar dengan data contoh.*

4. Anda kembali ke halaman masuk dengan pesan bahwa password sudah disimpan. Masuk dengan password baru Anda.

### Jika email tidak datang

1. Periksa folder `Spam` atau `Junk`.
2. Periksa bahwa alamat yang Anda ketik adalah yang tersimpan di akun Anda.
3. Tunggu satu menit, lalu pilih `Resend password reset link` di halaman `Check Your Email`. Ini membawa Anda kembali ke halaman permintaan, tempat Anda mengirim permintaan lagi.

## Aturan yang perlu diketahui

- Tautan hanya dikirim jika email itu milik akun EcoSystem yang aktif. Halamannya tampil sama baik email itu dikenal maupun tidak, sehingga tidak ada yang bisa mencari tahu email mana yang terdaftar. Jika tidak ada yang datang, mungkin email itu bukan yang tersimpan di akun Anda. Tanyakan ke administrator.
- Tautan berlaku 24 jam dan hanya bisa dipakai sekali. Meminta tautan baru membuat tautan yang lama tidak berlaku lagi.
- Jika Anda meminta lagi dalam satu menit, email kedua tidak dikirim. Setelah satu menit itu Anda dapat meminta lagi. Dari satu tempat, paling banyak 5 permintaan per menit yang diterima.
- Password baru minimal 8 karakter, dan kedua isiannya harus sama.
- Menyimpan password baru tidak langsung memasukkan Anda. Anda diarahkan ke halaman masuk dan masuk sendiri.
- Setelah password disimpan, semua sesi lain akun Anda berakhir, `Keep me signed in` dihapus, dan browser yang pernah Anda verifikasi untuk verifikasi 2 langkah tidak dipercaya lagi. Anda masuk lagi di setiap perangkat.
- Verifikasi 2 langkah tetap aktif. Jika aktif untuk akun Anda, kode tetap diminta saat Anda masuk.
- Penyimpanan password baru tercatat di Audit Log. Password itu sendiri tidak pernah dicatat di sana.

## Apa yang terjadi selanjutnya

Setelah password disimpan, Anda masuk lagi. Jika Anda tidak meminta email reset itu, jangan buka tautannya, dan beri tahu administrator.

## Pertanyaan umum

**Tautan menyatakan sudah kedaluwarsa.** Tautan berlaku 24 jam dan hanya sekali. Pilih `Forgot password?` dan minta yang baru.

**`Save Password` berwarna abu-abu.** Tombol baru aktif jika password minimal 8 karakter dan kedua isian sama.

**Muncul pesan tentang terlalu banyak permintaan.** Tunggu satu menit lalu coba lagi.

**Saya terus terkunci.** Reset password tidak membuka akun yang terkunci karena terlalu banyak percobaan gagal. Tunggu kunci berakhir, atau tanyakan ke administrator.

**Saya masuk dengan Microsoft.** Anda tetap bisa melakukannya. Reset password hanya mengubah password EcoSystem Anda.

<!-- sources: resources/views/auth/forgot-password.blade.php, resources/views/auth/check-email.blade.php, resources/views/auth/change-password.blade.php, routes/web.php, app/Http/Controllers/PasswordSetupController.php -->
