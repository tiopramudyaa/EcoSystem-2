---
key: ms-business-partner-record
chapter: master
order: 21
title: Data business partner: Basic Data, Address, Contact Person, Identification, Bank, Credential, Attachment, Report Templates
summary: Baca dan ubah satu business partner, tab demi tab, dan beri kontak customer akses ke portal customer.
requires: [customer.section.basic_data.view, customer.section.address.view, customer.section.contact.view, customer.section.contact.group, customer.section.identification.view, customer.section.bank.view, customer.section.credential.view, customer.section.history.view, customer.section.attachment.view, customer.section.report_templates.view]
updated: 2026-11-02
---
Klik sebuah business partner di `Master` › `Business Partner` untuk membuka datanya. Bagian atas menampilkan nama, status, kode, tipe, email, telepon, grup, kategori, dan industri. Tab memuat detailnya.

## Siapa yang menggunakan

Sales, HR, staf project dan support, serta administrator. Setiap tab punya izin `view` dan `update` sendiri di Menu Access, jadi Anda hanya melihat tab yang diberikan kepada role Anda dan `Save` disembunyikan di tempat yang tidak boleh Anda ubah.

> [!IMPORTANT]
> `Bank Account` dan `Credential` memuat data sensitif. Credential adalah rahasia sistem customer: buka hanya bila pekerjaan memerlukannya, dan jangan pernah menyalinnya ke chat atau ticket.

## Sebelum Anda mulai

- Kontak adalah orang di pihak customer. Hanya kontak yang diberi akses yang dapat login ke portal customer (JARVIES).
- Seorang kontak dapat melihat lebih banyak atau lebih sedikit ticket menurut `Ticket Access` dan grup, seperti dijelaskan di bawah.

## Langkah demi langkah

![Halaman Business Partner Detail dengan kartu header dan tab Basic Data](images/guide/mst-partner-detail.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Master` › `Business Partner` dan klik sebuah baris. Pakai `Back to List` untuk kembali.
2. `Basic Data`: kode, tipe, email perusahaan, domain email, nama dan search term, serta informasi customer: grup, parent customer, tipe credit limit, industry sector, dan account executive. `Audit Information` menampilkan siapa yang membuat dan terakhir mengubahnya. Pilih `Save Changes` setelah mengubah.
3. `Address`: satu alamat atau lebih milik perusahaan.
4. `Identification`: dokumen perusahaan dengan nomor, penerbit, dan masa berlakunya.
5. `Bank Account`: rekening bank perusahaan.
6. `Credential`: catatan kredensial yang disimpan untuk customer ini. Pilih `Save` atau tekan Ctrl+S setelah mengubah.
7. `Attachment`: berkas pendukung.
8. `Report Templates`: template Word (`.docx`) yang dipakai `Word Report Generator`. Pilih `Upload Template` sekali dan pakai ulang untuk setiap laporan customer ini.

### Contact Person

![Tab Contact Person dengan tabel kontak, kolom akses Jarvies dan akses ticket](images/guide/mst-partner-contact.png)

*Tangkapan layar dengan data contoh.*

1. Pilih tab `Contact Person`. Setiap kontak menampilkan nama, jabatan, departemen, telepon, email, masa berlaku, `Jarvies Access`, `Ticket Access`, dan `Group`.
2. Pilih `New` untuk menambah kontak, isi data umum dan detail komunikasi lalu `Save`. `Copy` menggandakan kontak dan `Delete` menghapusnya.
3. Agar kontak dapat login, pilih kontaknya lalu `Grant Jarvies Access`. Ketik email login dan konfirmasi: email pengaturan password dikirim otomatis. `Revoke Jarvies Access` mencabut aksesnya.
4. `Ticket Access` menandai kontak sebagai administrator yang melihat semua ticket perusahaan. Kontak lain melihat ticket miliknya, dan ticket delivery support tempat mereka menjadi Customer PIC.
5. Pilih `Groups` untuk membuat grup kontak. Anggota grup yang sama dapat melihat ticket satu sama lain di JARVIES. Kontak administrator tidak dapat ditambahkan, mereka sudah melihat semuanya.

## Apa yang terjadi selanjutnya

Perubahan berlaku seketika dan tampil di `Control Center` › `Audit Log`. Kontak dengan akses baru menerima email pengaturan password dan dapat login ke JARVIES.

## Pertanyaan umum

**Customer tidak melihat sebuah ticket.** Periksa `Ticket Access` kontak, grupnya, dan untuk support, daftar Customer PIC di `Delivery` › `Support`.

**Email password tidak masuk.** Periksa email login dari salah ketik dan folder spam, lalu cabut dan beri akses lagi.

<!-- sources: routes/web.php, resources/views/master/customer/show.blade.php, resources/views/master/customer/sections/contact.blade.php -->
