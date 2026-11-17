---
key: web-report-management
chapter: administration
order: 62
title: Web Report Management
summary: Baca dan tindak lanjuti laporan rahasia yang dikirim lewat formulir Web Report publik.
requires: [control-center.web-reports]
updated: 2026-11-04
---
Web Report adalah formulir publik yang dapat dipakai siapa saja untuk melaporkan masalah, tanpa akun. Formulir dibuka dengan tombol `Report` di halaman sign-in. `Web Report Management` adalah tempat orang yang menangani laporan itu membacanya, mengubah statusnya, dan menjawab pelapor.

## Siapa yang menggunakan

Beberapa orang yang menangani laporan, biasanya compliance atau administrator senior. Berikan akses ini seperlunya. Dengan akses lihat Anda dapat membaca laporan. Dengan izin `Edit` Anda juga dapat mengubah status dan menulis catatan. Laporan tidak pernah dihapus. Laporan ditutup.

> [!IMPORTANT]
> Laporan bersifat sangat rahasia. Membuka laporan dan mengunduh buktinya dicatat di riwayat aksesnya. Buka laporan hanya untuk menanganinya.

## Sebelum Anda mulai

- Laporan yang dikirim karyawan yang sudah sign in dari [[my-compliance]] tidak ditampilkan di sini. Laporan itu ditangani di [[compliance-management]].
- Pelapor boleh tidak memberi nama dan kontak. Jangan mencoba mencari tahu siapa mereka.
- Bukti berasal dari orang yang tidak dikenal. Pindai berkas sebelum membukanya.

## Langkah demi langkah

### Baca inbox

1. Buka `Control Center` › `Web Report Management`.
2. Periksa kartu: satu untuk setiap status (`New`, `In review`, `Investigating`, `Closed`) dan satu untuk laporan yang melewati tanggal tindak lanjut. Klik kartu untuk menyaring.
3. Gunakan ikon corong di header tabel untuk menyaring menurut `Code`, tanggal `Received`, `Follow-up due`, `Status`, atau `Evidence`. Klik `Reset Filter` untuk menghapus saringan. Ubah `Rows per page` di bagian bawah.
4. Daftar menampilkan laporan yang masih terbuka lebih dulu, lalu yang sudah ditutup. Klik `Open` pada sebuah laporan.

Pelapor tetap anonim, jadi laporan dicari lewat kodenya, yang berbentuk `WR-XXXX-XXXX`.

### Tindak lanjuti laporan

1. Baca laporan di sebelah kiri: kode, pelapor bila ada, kontak, tanggal diterima, siapa atau apa yang dilaporkan, kronologi, dan bukti.
2. Untuk membaca bukti, klik nama berkasnya. Pengunduhan dicatat.
3. Di sebelah kanan, pilih `Status`. Catatan di bawahnya menunjukkan kapan tindak lanjut jatuh tempo.
4. Tulis `Message the reporter will see`. Pesan ini muncul di halaman status di samping kode pelacakan. Jangan memasukkan nama atau bukti ke dalamnya.
5. Tulis `Internal note` untuk tim bila perlu. Pelapor tidak pernah melihatnya.
6. Klik `Save`. Perubahan ditambahkan ke `Access history`.

### Tutup laporan

1. Buka laporan dan atur `Status` menjadi `Closed`.
2. Tulis pesan terakhir untuk pelapor, lalu klik `Save`. Tanggal penutupan dicatat.

## Apa yang terjadi selanjutnya

Pelapor memeriksa status di halaman status laporan publik dengan kode pelacakan. Mereka melihat status, tanggal tindak lanjut yang diharapkan, dan pesan Anda. Laporan yang tidak dijawab dalam batas hari tindak lanjut dihitung di kartu `Past the 14-day follow-up`.

## Pertanyaan umum

**Mengapa kolomnya berwarna abu-abu?** Anda dapat membaca laporan tetapi tidak memiliki izin `Edit`. Minta administrator.

**Mengapa saya tidak menemukan laporan dari karyawan?** Karyawan melapor dari `My Compliance`. Laporan itu ada di `Compliance Management` › `Reports`, dengan kode yang diawali `CL-`.

**Bisakah saya menambah atau mengubah status?** Tidak di sini. Formulir publik selalu memakai empat status yang sama. Status untuk laporan karyawan diatur di `Compliance Management` › `Settings`.

## Topik terkait

- [[compliance-management]]
- [[my-compliance]]
- [[control-center]]

<!-- sources: routes/web-report.php, app/Http/Controllers/WebReport/*, app/Services/WebReport/*, resources/views/admin/web-report/*, resources/views/web-report/*, config/web-report.php -->
