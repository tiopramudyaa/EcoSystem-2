---
key: ms-employee
chapter: master
order: 10
title: Employee
summary: Temukan karyawan, buat karyawan baru, tentukan role, ubah ID atau password karyawan, ekspor daftar, dan hapus karyawan.
requires: [master.employee, master.employee.create, master.employee.action]
updated: 2026-11-02
---
`Master` › `Employee` adalah daftar semua orang yang bekerja dengan EcoSystem. Seorang karyawan juga adalah akun pengguna: ID karyawan (ECI) adalah username untuk login.

## Siapa yang menggunakan

HR, administrator, dan role mana pun yang diberi menu `Master` › `Employee` di Menu Access. `Create Employee` dan menu baris hanya muncul untuk role yang diberi `Create Employee` dan `Actions (Edit/Delete)`.

> [!IMPORTANT]
> Halaman ini memuat data pribadi dan mengatur siapa yang dapat login. Ubah dengan hati-hati, dan jangan pernah membagikan password lewat chat atau email.

## Sebelum Anda mulai

- Ketahui nama lengkap, ID karyawan, email kerja (menerima tautan aktivasi akun), dan posisi orangnya.
- Role menentukan menu apa yang dilihat seseorang. Pilih bersama atasannya, dan lihat `Management` › `Role` untuk isi tiap role.

## Langkah demi langkah

![Daftar Employee Management dengan filter di judul kolom dan tombol Create Employee](images/guide/mst-employee-list.png)

*Tangkapan layar dengan data contoh.*

### Cari karyawan

1. Buka `Master` › `Employee`. Baris judul menyebut jumlah karyawan yang tampil.
2. Pakai ikon corong di judul kolom untuk menyaring menurut `ECI`, `Full Name`, `Position`, `Module`, `Employee Group`, `Division`, `Department`, `Home Base`, `Personnel Area`, `Employee Type`, `Status`, dan lainnya. `Clear` mengembalikan filter.
3. Kolom `Status` berisi `Active`, `Blocked`, atau `Flagged for Deletion`. Orang yang diblokir atau ditandai tidak bisa login.
4. Klik sebuah baris untuk membuka data karyawan. `Export Excel` mengunduh daftar. `Manage Dropdown Data` membuka daftar dropdown yang dipakai formulir.

### Buat karyawan

![Jendela Create Employee dengan data umum, alamat standar, dan data organisasi](images/guide/mst-employee-create.png)

*Tangkapan layar dengan data contoh.*

1. Pilih `Create Employee`.
2. Di `General Data` isi ID karyawan, password beserta konfirmasinya, nama, nama panggilan, gender, tanggal lahir, dan email kerja. Email kerja adalah tujuan tautan aktivasi.
3. Di `Standard Address` isi jalan dan rantai wilayah (provinsi, kota, kecamatan, kelurahan).
4. Di `Organizational Data` pilih personnel area, posisi, employee group dan sub-group, divisi, dan home base.
5. Simpan. Orang itu mengaktifkan akun lewat tautan yang dikirim ke email kerjanya, lalu login dengan ID karyawan.

### Menu baris

Pilih ikon menu pada baris untuk:

- `Change Password`: ketik password baru (minimal 6 karakter) dua kali.
- `Change Employee ID`: ID juga adalah username login, jadi dikunci di formulir biasa. Ketik ID baru dua kali dan konfirmasi dengan password Anda sendiri.
- `Change Role`: centang satu role atau lebih di `Assign Roles` lalu simpan. Satu orang boleh memegang beberapa role dan mendapat menu dari semuanya.
- `Delete`: menghapus karyawan secara permanen setelah Anda konfirmasi.

## Apa yang terjadi selanjutnya

Karyawan baru mendapat email aktivasi di email kerjanya. Role yang diubah berlaku sejak halaman berikutnya yang dibuka orang itu. Setiap perubahan tercatat di `Control Center` › `Audit Log`.

## Pertanyaan umum

**Saya tidak sengaja menghapus karyawan.** Penghapusan permanen dan tidak bisa dibatalkan. Buat orangnya lagi. Untuk menghentikan seseorang login tanpa kehilangan data, minta tim teknis memblokir akunnya, bukan menghapusnya.

**ID karyawan tidak bisa diubah.** Pakai `Change Employee ID` dari menu baris.

**Di mana data lain milik orang itu?** Klik barisnya dan pakai tab, seperti dijelaskan di topik data karyawan.

<!-- sources: routes/web.php, resources/views/master/employee/index.blade.php -->
