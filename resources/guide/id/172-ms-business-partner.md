---
key: ms-business-partner
chapter: master
order: 20
title: Business Partner
summary: Temukan customer dan vendor, buat business partner, kelompokkan customer, dan pahami parent serta end customer.
requires: [master.customer, master.customer.create, master.customer.action]
updated: 2026-11-02
---
`Master` › `Business Partner` adalah daftar perusahaan yang bekerja sama dengan EcoSystem: customer (klien project, support, dan ticket) dan vendor (dapat dipilih sebagai vendor sebuah delivery support).

## Siapa yang menggunakan

Sales, HR dan administrator, staf project dan support, serta role mana pun yang diberi menu `Master` › `Business Partner` di Menu Access. `Create Business Partner` dan menu baris hanya muncul untuk role yang diberi `Create Business Partner` dan `Actions (Edit/Delete)`.

## Sebelum Anda mulai

- Periksa bahwa perusahaan belum ada di daftar, juga di bawah parent customer-nya.
- Ketahui kode customer (maksimal 4 huruf dan angka), nama perusahaan, dan bila perlu domain email.

## Langkah demi langkah

![Daftar Business Partner dengan email, nama perusahaan, tipe, grup, kategori, industri, dan status](images/guide/mst-partner-list.png)

*Tangkapan layar dengan data contoh.*

### Cari business partner

1. Buka `Master` › `Business Partner`. Baris judul menyebut jumlah yang tampil.
2. Pakai ikon corong di judul kolom (email, nama perusahaan, tipe, customer group, customer category, industry sector, status) untuk menyaring.
3. Perusahaan yang punya parent menampilkan nama parent di bawah namanya, misalnya `Customer A Plant Surabaya` di bawah `Customer A`.
4. Klik sebuah baris untuk membuka datanya (lihat topik berikutnya).

### Buat business partner

![Jendela Create Business Partner dengan data umum, alamat standar, dan data organisasi](images/guide/mst-partner-create.png)

*Tangkapan layar dengan data contoh.*

1. Pilih `Create Business Partner`.
2. Di `General Data` pilih `Type`: `Customer` atau `Vendor`. Isi `Customer Code` (wajib, maksimal 4 huruf dan angka), `Company Name` (wajib), dan bila perlu email perusahaan, title (PT, CV, UD), dan `Email Domain`.
3. `Email Domain` mengarahkan email masuk: email yang pengirimnya berakhiran domain ini masuk ke daftar `Ticket Validation` customer tersebut.
4. Di `Standard Address` isi jalan, gedung, kode pos, serta rantai negara dan wilayah.
5. Di `Organizational Data` pilih `Parent Customer` (kosongkan untuk customer tingkat atas), `Customer Group`, kategori, dan tipe credit limit. Detail lain dapat ditambahkan nanti di datanya.
6. Simpan. Akses login tidak diatur di sini: diberikan per contact person nanti.

### Parent customer, end customer, dan grup

- Parent customer dapat memiliki end customer (misalnya pabrik-pabrik satu perusahaan). Ticket dan kredensial end customer ditangani di bawah end customer itu.
- `View Grouping` membuka `Customer Grouping`: bagian pertama mendaftar parent customer beserta end customer-nya, bagian kedua mendaftar `Customer Groups` yang datar.
- Di `Customer Groups` pilih `New group` untuk membuat, `Add member` untuk memasukkan customer, dan `Edit` atau `Delete` untuk mengubahnya. Anggota grup tetap customer yang mandiri, dengan login, kontak, dan ticket sendiri, dan hanya ditampilkan bersama.

![Customer Grouping dengan parent dan end customer serta customer group](images/guide/mst-partner-grouping.png)

*Tangkapan layar dengan data contoh.*

### Hapus business partner

Pilih `Delete` di menu baris lalu konfirmasi. Tindakan ini tidak dapat dibatalkan.

## Apa yang terjadi selanjutnya

Customer ditawarkan di project, support, ticket, dan laporan. Email dari domainnya diarahkan ke daftar staging-nya.

## Pertanyaan umum

**Saya tidak sengaja membuat duplikat.** Periksa parent customer dulu: perusahaan bernama sama di bawah sebuah parent bisa jadi end customer tersendiri dengan sengaja.

**Tidak ada tombol `Create Business Partner`.** Role Anda tidak memilikinya. Minta administrator.

<!-- sources: routes/web.php, resources/views/master/customer/index.blade.php, resources/views/master/customer/grouping.blade.php -->
