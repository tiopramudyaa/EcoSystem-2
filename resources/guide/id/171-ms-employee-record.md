---
key: ms-employee-record
chapter: master
order: 11
title: Data karyawan: Basic Data, Address, Identification, Family, Education, Qualification, Contract, Bank, Payment, Attachment
summary: Baca dan ubah seluruh data satu karyawan, tab demi tab, dengan tiap tab hanya tampil untuk role yang boleh melihatnya.
requires: [employee.section.basic_data.view, employee.section.address.view, employee.section.identification.view, employee.section.family.view, employee.section.education.view, employee.section.qualification.view, employee.section.contract.view, employee.section.bank.view, employee.section.payment.view, employee.section.attachment.view]
updated: 2026-11-02
---
Klik seorang karyawan di `Master` › `Employee` untuk membuka datanya. Bagian atas menampilkan nama, ECI, status, email, telepon, departemen, divisi, dan tanggal mulai. Tab di bawahnya memuat detailnya.

## Siapa yang menggunakan

HR dan administrator. Setiap tab punya izin `view` dan `update` sendiri di Menu Access, jadi sebuah role bisa melihat `Basic Data` tetapi tidak `Bank Account`. Tab yang tidak boleh Anda lihat tidak muncul, dan `Save Changes` disembunyikan pada tab yang tidak boleh Anda ubah.

> [!IMPORTANT]
> `Identification`, `Family`, `Bank Account`, `Basic Payment`, dan `Attachment` memuat data pribadi dan keuangan. Buka hanya bila pekerjaan Anda memerlukannya.

## Sebelum Anda mulai

- Ubah data hanya dari dokumen atau permintaan tertulis, bukan dari ingatan.
- Data yang sama didaftar untuk semua karyawan di `Management` › `Master Employee Settings`. Daftar itu hanya untuk dibaca, dan tautan `Detail →`-nya membawa Anda ke sini.

## Langkah demi langkah

![Halaman Employee Detail dengan kartu header dan tab Basic Data](images/guide/mst-employee-detail.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Master` › `Employee` dan klik orangnya. Pakai `Back to List` untuk kembali.
2. `Basic Data`: title, nama panggilan, nama depan dan belakang, gender, agama, status pernikahan, tanggal dan tempat lahir, tanggal mulai, dan data organisasi (personnel area dan subarea, posisi, employee group dan sub-group, divisi, departemen, home base, manager, dan lainnya). Kolom bertanda bintang merah wajib diisi. Search term dibuat otomatis. Pilih `Save Changes` setelah mengubah.
3. `Address`: satu alamat atau lebih, dengan tipe dan penanda alamat utama.
4. `Identification`: dokumen identitas dengan nomor, penerbit, dan masa berlakunya.
5. `Family`: anggota keluarga dengan hubungan dan tanggal lahirnya.
6. `Education`: sekolah dan gelar dengan jurusan dan tahun lulus.
7. `Qualification`: module tempat konsultan berkualifikasi. Daftar module-nya sendiri disimpan di `Management` › `Qualification`.
8. `Contract`: nomor kontrak, tipe, tanggal mulai dan selesai, dan apakah masih aktif.
9. `Bank Account` dan `Basic Payment`: rekening bank dan pembayaran dasar orang itu.
10. `Attachment`: berkas pendukung karyawan.

Pada tab yang memuat daftar, tambah, ubah, atau hapus baris dengan tombol pada tab itu, lalu simpan.

## Apa yang terjadi selanjutnya

Menyimpan langsung memperbarui data dan tampil di `Control Center` › `Audit Log`. Perubahan posisi, divisi, atau departemen juga mengubah tampilan orang itu di daftar dan laporan.

## Pertanyaan umum

**Sebuah tab tidak ada.** Role Anda tidak memiliki bagian itu di Menu Access. Minta administrator, disertai alasan.

**Saya tidak bisa mengubah ID karyawan di sini.** Pakai `Change Employee ID` di menu baris `Master` › `Employee`.

<!-- sources: routes/web.php, resources/views/master/employee/show.blade.php -->
