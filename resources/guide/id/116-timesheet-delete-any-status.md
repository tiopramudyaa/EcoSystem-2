---
key: timesheet-delete-any-status
chapter: my-workspace
order: 116
title: Delete Timesheet in Any Status
summary: Hapus timesheet karyawan mana pun yang sudah submitted, approved, atau rejected dari halaman Timesheets.
requires: [timesheet.delete.any-status]
updated: 2026-11-21
---
Biasanya hanya timesheet `Draft` yang bisa dihapus. Dengan fungsi ini Anda juga dapat menghapus entri yang sudah `Submitted`, `Approved`, atau `Rejected`, misalnya entri approved yang tercatat di project yang salah dan kini muncul di `MD Recap`.

## Siapa yang menggunakan

Pemegang fungsi `Delete Timesheet in Any Status` di `Menu Access`, di bawah `Timesheets`. Saat fungsi ini dibuat, hanya EC Administrator yang memegangnya.

## Sebelum Anda mulai

- Pastikan entri itu memang harus dihapus. Penghapusan mengubah angka mandays di laporan.
- Ketahui pemilik entri dan tanggalnya, supaya Anda memilih baris yang benar.

## Langkah demi langkah

1. Buka `Calendar` › `Timesheets`. Anda melihat entri semua karyawan untuk tipe yang boleh Anda lihat.
2. Cari entrinya. Gunakan filter atau tab tipe untuk mempersempit daftar.
3. Klik kanan barisnya lalu pilih `Delete`. Anda juga dapat memilih `Detail` lalu memilih `Delete` di bagian atas panel.
4. Dialog `Delete Timesheet` menampilkan status, nama karyawan, dan tanggal. Periksa ketiganya.
5. Pilih `Delete`. Entri hilang dari daftar dan muncul pesan berhasil.

`Delete` tidak tampil pada baris `Draft`: draft dihapus dengan cara biasa, lewat kotak centangnya dan tombol `Delete` di atas daftar.

## Aturan yang perlu diketahui

- Menghapus entri `Submitted`, `Approved`, atau `Rejected` memerlukan fungsi ini. Tanpa fungsi ini permintaan ditolak, meskipun dikirim langsung.
- Saat dibuat, fungsi ini hanya diberikan ke EC Administrator. Role lain baru mendapatkannya bila administrator mencentangnya di `Menu Access`.
- Pemegangnya dapat menghapus entri karyawan mana pun, di status apa pun dan di periode mana pun, termasuk periode yang sudah ditutup.
- Entri yang dihapus tidak lagi dihitung di mana pun: `Timesheets`, `MD Recap`, export-nya, dan laporan lain tidak memuatnya, dan mandays-nya tidak lagi dihitung terhadap kuota karyawan pada ticket.
- Bila angka periode itu sudah dikirim ke customer, angkanya tidak akan cocok lagi dengan yang tampil di EcoSystem.
- Penghapusan tercatat di `Audit Log` atas nama Anda. Entri tetap tersimpan di database dan dapat dipulihkan oleh administrator sistem.

## Apa yang terjadi selanjutnya

Karyawan tidak lagi melihat entri itu. Bila pekerjaannya tetap harus dicatat, karyawan membuat entri baru dan mengirimnya lagi untuk persetujuan. Entri baru itu mengikuti aturan periode dan kuota yang biasa.

## Pertanyaan umum

**Bisakah penghapusan dibatalkan?** Tidak dari halaman ini. Minta administrator sistem memulihkannya dari database.

**Mengapa saya tidak melihat `Delete` pada entri approved?** Role Anda tidak memegang `Delete Timesheet in Any Status`, atau menunya belum diperbarui. Muat ulang halaman setelah fungsi itu dicentang.

<!-- sources: app/Http/Controllers/TimesheetController.php (destroy), resources/views/calendar/partials/timesheet-detail-panel.blade.php, resources/views/calendar/timesheets.blade.php, database/migrations/2026_11_21_000001_add_timesheet_delete_any_status_permission.php -->
