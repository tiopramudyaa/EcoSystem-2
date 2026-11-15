---
key: mg-employee-data-lists
chapter: management
order: 17
title: Master Employee Settings: Basic Data, Address, Identification, Family, Education, Contract, Bank dan Payment, Attachment
summary: Daftar baca-saja satu jenis data karyawan untuk semua orang, dengan pencarian dan tautan ke data lengkap karyawan.
requires: [management.employee.basic-data, management.employee.address, management.employee.identification, management.employee.family, management.employee.education, management.employee.contract, management.employee.bank, management.employee.payment, management.employee.attachment]
updated: 2026-11-02
---
Di bawah `Management` › `Master Employee Settings` ada satu daftar untuk tiap jenis data karyawan: `Basic Data`, `Address`, `Identification`, `Family`, `Education`, `Contract`, `Bank`, `Payment`, dan `Attachment`. Daftar ini memungkinkan HR meninjau satu jenis data untuk semua karyawan sekaligus. Pengubahan dilakukan di data karyawan masing-masing.

## Siapa yang menggunakan

HR dan administrator. Setiap daftar punya menu sendiri di Menu Access, jadi Anda hanya melihat daftar yang diberikan kepada role Anda.

> [!IMPORTANT]
> `Identification`, `Family`, `Bank`, `Payment`, dan `Attachment` memuat data pribadi dan keuangan. Buka hanya bila pekerjaan Anda memerlukannya, dan jangan menyalin atau membagikan isinya.

## Sebelum Anda mulai

- Daftar ini hanya untuk dibaca. Tidak ada tombol tambah, ubah, atau hapus.
- Ubah datanya di `Master` › `Employee`, pada tab yang sesuai milik karyawan itu.

## Langkah demi langkah

![Daftar Employee Basic Data dengan ECI, nama, posisi, departemen, tanggal mulai, status, dan tautan Detail](images/guide/mgmt-employee-basic-data.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Management` › `Master Employee Settings` lalu pilih daftarnya, misalnya `Basic Data`.
2. Baca kolom pada daftar yang Anda buka:
   - `Basic Data`: ECI, nama, posisi, departemen, tanggal mulai, dan status.
   - `Address`: tipe, alamat, kota, dan wilayah.
   - `Identification`: tipe ID, nomor ID, dan berlaku sampai.
   - `Family`: nama anggota keluarga, hubungan, dan tanggal lahir.
   - `Education`: institusi, jurusan, gelar, dan tahun lulus.
   - `Contract`: tipe kontrak, mulai, selesai, dan status.
   - `Bank`: bank, nomor rekening, dan nama rekening.
   - `Payment`: tipe, jumlah, mata uang, dan berlaku mulai.
   - `Attachment`: nama berkas, tipe, dan ukuran.
3. Ketik di kotak pencarian untuk menemukan orang atau nilai. Teks petunjuknya menyebut yang dicari, misalnya nama, ECI, atau posisi pada `Basic Data`, dan nama, kota, atau provinsi pada `Address`.
4. Baris di bawah tabel menghitung catatan yang ditemukan, seperti `4 employee(s)`.
5. Pilih `Detail →` pada sebuah baris untuk membuka data lengkap karyawan itu di `Master` › `Employee`.

## Apa yang terjadi selanjutnya

Membaca daftar tidak mengubah apa pun. Untuk memperbaiki data, buka karyawannya lewat `Detail →` dan ubah di sana.

## Pertanyaan umum

**Daftar menampilkan `No data`.** Belum ada yang memasukkan jenis data itu, atau pencarian Anda tidak cocok dengan apa pun.

**Saya bisa membuka `Basic Data` tetapi tidak `Bank`.** Setiap daftar adalah menu terpisah. Minta administrator memberikan yang Anda perlukan, disertai alasannya.

<!-- sources: resources/views/management/employee/basic-data/index.blade.php, address, identification, family, education, contract, bank, payment, attachment -->
