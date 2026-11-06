# Aturan proyek untuk Claude Code

## Fitur baru atau alur baru WAJIB disertai User Guide

Setiap kali Anda menambah atau mengubah fitur, halaman, menu, tombol, atau alur kerja yang terlihat oleh pengguna, tulis atau perbarui User Guide-nya di perubahan yang sama. Pekerjaan belum selesai kalau panduannya belum ada. Aturan ini berlaku di semua branch, dan harus ikut di-commit bersama kodenya supaya ikut ter-merge.

### Yang harus dilakukan

1. **Satu topik per menu atau halaman.** Judul topik memakai nama menu persis seperti di aplikasi (misalnya `Failed Jobs`, `Holidays`) agar pencarian di panduan menemukannya. Alur baru di halaman yang sudah ada: perbarui topik halaman itu, jangan membuat topik baru yang menduplikasi.
2. **Dua bahasa, berpasangan.** Buat berkas di `resources/guide/en/` dan `resources/guide/id/` dengan nama berkas dan `key` yang sama. Nama menu, tombol, dan label layar tetap bahasa Inggris di kedua bahasa (sama seperti layar aplikasi).
3. **Front matter dan struktur** mengikuti topik yang sudah ada (contoh: `resources/guide/en/145-cc-failed-jobs.md`): `key`, `chapter`, `order`, `title`, `summary`, `requires`, `updated`. Bagian isi berurutan: pembuka, `## Who uses this`, `## Before you start`, `## Step by step`, `## What happens next`, `## Common questions`, lalu komentar `<!-- sources: ... -->`.
4. **`requires`** berisi slug menu yang membuka halaman itu (dari tabel `menu`, sama dengan middleware `menu:` di `routes/*.php`). Menu baru harus juga ditambahkan ke `MenuSeeder` atau migrasi menu seperti biasa. Panduan per role memakai `@role:Nama Role`.
5. **Bab.** Pakai bab yang sudah ada di `config/user_guide.php` (`control-center`, `management`, `master`, `delivery`, `sla`, `rpmo`, dan lainnya). Menu grup baru: tambahkan bab baru di config, di urutan yang sama dengan sidebar.
6. **Screenshot** mengikuti `resources/guide/_IMAGES.md`: file di `public/images/guide/`, maksimal 600 KB, hanya data karangan dari database demo sementara (jangan pernah memotret database kerja), tanpa password, token, kredensial, rekening, atau data gaji. Beri teks alternatif dan baris `*Screenshot with sample data.*` / `*Tangkapan layar dengan data contoh.*`.
7. **Tulis yang benar-benar terjadi di aplikasi.** Verifikasi kata di layar dan perilakunya (jalankan halamannya, atau baca kode), jangan menebak. Kalau perilakunya tidak sesuai harapan, tulis perilaku nyata dan laporkan temuannya.
8. **Aturan bisnis wajib ditulis.** Panduan bukan hanya langkah klik. Sertakan bagian `## Rules to know` (ID: `## Aturan yang perlu diketahui`) yang memuat semua aturan yang benar-benar dipaksakan kode: validasi dan batas (misalnya tanggal tidak boleh di masa depan, kuota MD), siapa yang boleh melakukan apa (role atau izin menu), alur status (draft, submitted, approved, rejected dan syarat tiap perpindahan), syarat persetujuan, dan efeknya ke fitur lain. Ambil aturan dari controller, service, dan request validation, bukan dari ingatan, dan uji kasus yang meragukan di aplikasi. Setiap kali aturan di kode berubah, perbarui bagian ini di perubahan yang sama. Jika menemukan perilaku yang bertentangan dengan alur yang seharusnya, laporkan ke pengguna dan perbaiki hanya bila jelas salah.
9. **Gaya.** Jangan memakai tanda pisah panjang (em dash) atau en dash di panduan, UI, maupun dokumen. Jangan menaruh tautan topik ke topik yang tidak bisa dilihat semua pembaca topik sumbernya. Jangan menulis slug teknis di teks (kecuali dalam `requires`).

### Cara memverifikasi sebelum menyatakan selesai

```
php docs/HRGA/tools/guide-check.php
php artisan test tests/Unit/Guide
```

Keduanya harus bersih (0 galat, 0 peringatan). `tests/Unit/Guide/UserGuideMenuCoverageTest.php` gagal kalau ada menu di grup yang sudah ditulis tanpa topik. Saat menyelesaikan satu grup menu baru, tambahkan prefix slug-nya ke `COVERED_GROUPS` di test itu.

Jika perubahan tidak terlihat oleh pengguna (refactor, perbaikan internal), tidak perlu panduan, tetapi sebutkan alasannya di ringkasan pekerjaan.

### Admin editor

Isi panduan juga bisa diubah admin lewat `Control Center` › `User Guide Editor` (lapisan database di atas berkas). Berkas di `resources/guide/` tetap sumber bawaan dan yang di-commit.
