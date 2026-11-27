---
key: mg-holidays
chapter: management
order: 12
title: Holidays
summary: Kelola hari libur nasional, cuti bersama, dan hari libur kustom yang dipakai kalender sistem dan pemilih tanggal.
requires: [management.holidays]
updated: 2026-11-02
---
`Management` › `Holidays` menyimpan daftar hari tidak kerja yang dipakai EcoSystem pada kalender dan pemilih tanggal. Perbarui setiap tahun.

## Siapa yang menggunakan

Administrator, dan role mana pun yang diberi menu `Management` › `Holidays` di Menu Access.

## Sebelum Anda mulai

- Ketahui tanggal yang tepat, nama, dan tipenya: `National Holiday`, `Collective Leave`, atau `Custom Holiday` (misalnya ulang tahun perusahaan).
- Dua hari libur dengan tanggal dan nama yang sama tidak diizinkan.

## Langkah demi langkah

![Daftar Holidays dengan kartu ringkasan, filter, dan satu baris untuk setiap hari libur](images/guide/mgmt-holidays.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Management` › `Holidays`. Empat kartu di bagian atas menghitung hari libur yang tampil dan berapa yang berupa nasional, cuti bersama, dan custom. Setiap baris menampilkan kotak kalender kecil, tanggal beserta harinya, nama, tipe, dan status. Hari libur yang aktif dan sudah dekat menampilkan label seperti `Today`, `Tomorrow`, atau `in 12 days`, dan yang sudah lewat menampilkan `Past`.
2. Persempit daftar dengan daftar tahun (`All Years`), kotak pencarian `Search holiday name...`, dan tombol tipe `All`, `National Holiday`, `Collective Leave`, dan `Custom Holiday`. Hitungan di sebelah tombol menunjukkan berapa dari hari libur yang terdaftar sedang tampil.
3. Untuk menambah hari, pilih `Add Holiday`.

![Jendela Add Holiday dengan tanggal, nama, tipe, dan sakelar Active](images/guide/mgmt-holiday-add.png)

*Tangkapan layar dengan data contoh.*

4. Isi `Date`, `Name`, dan `Type`. Biarkan sakelar `Active` menyala agar hari itu berlaku, atau matikan untuk menyimpan entri tanpa memberlakukannya. Pilih `Save`.
5. Untuk mengubah hari, pilih tombol pensil pada barisnya (tooltip `Edit holiday`). Untuk menghapusnya selamanya, pilih tombol tempat sampah (tooltip `Delete holiday`) lalu konfirmasi.

## Aturan yang perlu diketahui

- **Siapa yang boleh mengubah hari libur.** Hanya orang yang diberi menu `Management` › `Holidays` di Menu Access. Sistem memeriksanya pada setiap perubahan, bukan hanya dengan menyembunyikan menu.
- **Isian wajib.** `Date` harus tanggal yang valid, `Name` wajib diisi (paling banyak 255 karakter), dan `Type` harus salah satu dari tiga tipe.
- **Tidak boleh ganda.** Tanggal yang sama dengan nama yang sama tidak bisa disimpan dua kali, baik saat menambah maupun mengubah. Dua nama berbeda pada satu tanggal diperbolehkan.
- **Hanya hari libur aktif yang berlaku.** Hari libur yang tidak aktif tetap ada di daftar tetapi diabaikan oleh kalender, pemilih tanggal, dan perhitungan hari kerja untuk SLA ticket.
- **Hapus bersifat permanen.** Tidak ada pembatalan. Matikan `Active` bila hari itu mungkin dibutuhkan lagi.

## Apa yang terjadi selanjutnya

Hari libur aktif langsung berlaku di kalender sistem, pemilih tanggal, dan perhitungan hari kerja untuk SLA ticket. Yang tidak aktif tetap ada di daftar tetapi tampil redup dan tidak berlaku.

## Pertanyaan umum

**Muncul pesan hari libur sudah ada.** Hari libur dengan tanggal dan nama yang sama sudah ada di daftar. Ubah entri itu saja.

**Tanggal sebuah libur pemerintah berubah.** Ubah entrinya dan ganti `Date`. Tidak perlu dihapus dulu.

**Bagaimana menutup jendela tanpa menyimpan?** Pilih X atau `Cancel`, tekan `Esc`, atau klik di luar jendela. Tidak ada yang berubah.

<!-- sources: resources/views/management/holidays/index.blade.php, app/Http/Controllers/HolidayManagementController.php -->
