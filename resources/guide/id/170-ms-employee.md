---
key: ms-employee
chapter: master
order: 10
title: Employee
summary: Temukan karyawan, buat karyawan baru, tentukan role, ubah ID atau password karyawan, reset verifikasi 2 langkah, ekspor daftar, dan hapus karyawan.
requires: [master.employee, master.employee.create, master.employee.action, master.employee.reset-two-factor]
updated: 2026-11-12
---
`Master` › `Employee` adalah daftar semua orang yang bekerja dengan EcoSystem. Seorang karyawan juga adalah akun pengguna: ID karyawan (ECI) adalah username untuk login.

## Siapa yang menggunakan

HR, administrator, dan role mana pun yang diberi menu `Master` › `Employee` di Menu Access. `Create Employee` dan menu baris hanya muncul untuk role yang diberi `Create Employee` dan `Actions (Edit/Delete)`. `Reset 2FA` di menu baris butuh satu izin lagi, `Reset Two-Factor Authentication`, yang secara bawaan hanya dimiliki EC Administrator kecuali Menu Access menentukan lain.

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
- `Reset 2FA`: mematikan verifikasi 2 langkah seseorang yang kehilangan ponsel dan kode pemulihannya. Lihat bagian berikut.
- `Delete`: menghapus karyawan secara permanen setelah Anda konfirmasi.

### Mereset verifikasi 2 langkah seseorang

Pakai ini hanya jika seseorang kehilangan ponsel dan kode pemulihannya sehingga tidak bisa masuk. Pastikan permintaannya benar-benar dari orang itu, misalnya dengan bertanya langsung atau lewat telepon.

![Menu baris seorang karyawan dengan Change Password, Change Employee ID, Change Role, Reset 2FA, dan Delete](images/guide/employee-reset-2fa-menu.png)

*Tangkapan layar dengan data contoh.*

1. Pilih ikon menu pada baris orang itu, lalu `Reset 2FA`.
2. Baca pesannya lalu pilih `OK`.

![Jendela konfirmasi untuk mereset two-factor authentication milik orang itu](images/guide/employee-reset-2fa-confirm.png)

*Tangkapan layar dengan data contoh.*

3. Saat diminta, masukkan kode 2FA Anda sendiri lalu kirim.
4. Sebuah pesan menyatakan orang itu sudah diberi tahu. Ia kini dapat masuk dengan password dan menyiapkan verifikasi 2 langkah lagi.

## Aturan yang perlu diketahui

- Menu baris hanya muncul dengan `Actions (Edit/Delete)`. `Reset 2FA` juga butuh `Reset Two-Factor Authentication`. EC Administrator memilikinya secara bawaan, dan Menu Access dapat mengubahnya.
- Anda tidak dapat mereset verifikasi 2 langkah Anda sendiri di sini. Pakai `Settings` › `Security`.
- Orang itu harus sudah mengaktifkan verifikasi 2 langkah. Jika belum, reset ditolak.
- Anda sendiri harus sudah mengaktifkan verifikasi 2 langkah, dan harus memasukkan kode baru dari aplikasi authenticator Anda untuk setiap reset. Kode yang salah ditolak, dan kode salah berulang kali mengunci akun Anda untuk sementara.
- Reset menghapus kunci, kode pemulihan, dan browser tepercaya milik orang itu. Setelah itu ia masuk hanya dengan password. Jika rolenya mewajibkan verifikasi 2 langkah, ia diminta menyiapkannya lagi setelah masuk.
- Orang itu diberi tahu di aplikasi dan lewat email, sehingga reset yang tidak ia minta tidak lolos tanpa terlihat. Jika email tidak dapat dikirim, reset tetap berlaku dan pesannya memberi tahu Anda.
- Setiap reset tercatat di `Control Center` › `Audit Log` dengan nama Anda dan nama orang itu.
- `Change Password`: minimal 6 karakter, diketik dua kali. `Change Employee ID`: harus baru, paling banyak 50 karakter, butuh password Anda sendiri, dan mengakhiri sesi orang itu yang sedang berjalan. `Change Role`: minimal satu role harus tetap terpilih, dan memberi seseorang role EC Administrator butuh kode 2FA baru dari Anda. `Delete` tidak bisa dibatalkan.

## Apa yang terjadi selanjutnya

Karyawan baru mendapat email aktivasi di email kerjanya. Role yang diubah berlaku sejak halaman berikutnya yang dibuka orang itu. Setiap perubahan tercatat di `Control Center` › `Audit Log`. Setelah `Reset 2FA`, minta orangnya segera menyiapkan verifikasi 2 langkah lagi.

## Pertanyaan umum

**Saya tidak sengaja menghapus karyawan.** Penghapusan permanen dan tidak bisa dibatalkan. Buat orangnya lagi. Untuk menghentikan seseorang login tanpa kehilangan data, minta tim teknis memblokir akunnya, bukan menghapusnya.

**ID karyawan tidak bisa diubah.** Pakai `Change Employee ID` dari menu baris.

**Di mana data lain milik orang itu?** Klik barisnya dan pakai tab, seperti dijelaskan di topik data karyawan.

<!-- sources: routes/web.php, routes/api.php, resources/views/master/employee/index.blade.php, app/Http/Controllers/EmployeeController.php, app/Services/SecurityMailer.php -->
