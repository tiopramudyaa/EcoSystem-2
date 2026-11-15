---
key: cc-notification-sounds
chapter: control-center
order: 26
title: Notification Sounds
summary: Unggah dan kelola suara peringatan yang dapat dipilih orang untuk notifikasinya.
requires: [control-center.sounds]
updated: 2026-11-02
---
`Notification Sounds` menyimpan berkas audio yang dapat dipilih karyawan untuk peringatan notifikasinya. Administrator menambah suara baru di sini dan menghapus yang tidak diperlukan.

## Siapa yang menggunakan

Administrator dengan role EC Administrator. Halaman ini memeriksa role itu sendiri, sehingga role lain yang memiliki menu `Notification Sounds` tetap menerima pesan "Superadmin only".

## Sebelum Anda mulai

- Pakai suara pendek dan tenang dalam format `WAV`, `MP3`, `OGG`, atau `AAC`, maksimal 3 MB.
- Pakai suara yang boleh Anda bagikan. Jangan unggah musik berhak cipta.
- Suara bertanda `Default` adalah yang didengar orang sampai mereka memilih yang lain. Suara ini tidak bisa dihapus.

## Langkah demi langkah

![Halaman Notification Sounds dengan formulir unggah dan daftar suara yang tersedia](images/guide/cc-notification-sounds.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Control Center` › `Notification Sounds`.
2. Untuk menambah suara, ketik `Display Name` (misalnya `Soft Chime`), lalu klik atau seret berkas audio ke `Audio File` dan pilih `Upload Sound`.
3. Di `Available Sounds`, pilih `Preview` untuk mendengarkan suara sebelum dipakai orang.
4. Untuk menghapus suara, pilih `Delete` pada barisnya lalu konfirmasi. Suara bawaan bertanda `Protected` dan tidak bisa dihapus.

## Apa yang terjadi selanjutnya

Suara baru muncul di daftar suara pada pengaturan notifikasi setiap orang. Hapus hanya suara yang tidak dipakai siapa pun: orang yang memilih suara yang dihapus harus memilih yang lain.

## Pertanyaan umum

**Unggahan ditolak.** Periksa formatnya dan pastikan ukuran berkas 3 MB atau kurang.

**Bisakah saya mengganti suara bawaan?** Tidak dari halaman ini. Suara bawaan bersifat tetap.

<!-- sources: routes/web.php, app/Http/Controllers/AdminNotificationSoundController.php, resources/views/admin/sounds.blade.php -->
