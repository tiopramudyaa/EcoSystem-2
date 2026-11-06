---
key: dl-project-detail
chapter: delivery
order: 11
title: Detail project: General, Delivery Info, Team, Documents, dan Location
summary: Baca dan ubah satu project: informasi umum, data penjualan dan termin pembayaran, anggota tim, dokumen, dan lokasi.
requires: [delivery-project.general.view, delivery-project.delivery-data.view, delivery-project.delivery-info.view, delivery-project.location.view, delivery-project.team.view, delivery-project.documents.view]
updated: 2026-11-02
---
Halaman detail project adalah satu halaman panjang. Tab di atas (`General`, `Delivery Info`, `Stakeholders`, `Team`, `Documents`, `Issues`, `Risks`, `Location`, `Planning`, `WRICEF`, `Plan Cost`) menggulir ke sebuah bagian. Topik ini membahas kelompok pertama. Register dan rencana ada di dua topik berikutnya.

## Siapa yang menggunakan

Project head, project administrator, dan tim project. Setiap bagian punya izin `view`, `edit`, dan `create or delete` sendiri di Menu Access. Bagian yang tidak boleh Anda lihat tidak muncul, dan ikon pensil atau tombol `Add` yang tidak boleh Anda pakai disembunyikan.

## Sebelum Anda mulai

- Project yang ditutup terkunci. Buka kembali dulu bila perlu mengubahnya.
- Revenue dan termin pembayaran adalah data keuangan. Periksa sebelum menyimpan.

## Langkah demi langkah

![Halaman Project Detail dengan tab di atas dan kartu General Information](images/guide/dlv-project-detail.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Delivery` › `Project` lalu klik sebuah project. Bilah judul menampilkan project dan customer, dengan tombol `Create Folder`, `Close Project`, dan `Delete Project`.
2. Di `General Information` baca customer, nama, owner, tipe, nomor IO, kategori, fase, tanggal kontrak, dan estimasi go-live. Pilih ikon pensil untuk mengubah. `Category`, `Phase`, dan `Go Live Estimated` terisi dari planning dan tidak bisa diketik.
3. `Delivery Data` memuat metode delivery, masa garansi, dan total mandays.

![Kartu Delivery Information dengan data penjualan dan rencana Term Of Payment](images/guide/dlv-project-delivery-info.png)

*Tangkapan layar dengan data contoh.*

4. Di `Delivery Information`, `Sales Data` menampilkan account executive, `Revenue`, `Plan Cost`, `Gross Profit`, persentasenya, dan angka aktual. Pilih pensil untuk mengubah.
5. Di bawahnya, pilih `TOP Type`: `% of Revenue` (jumlah mengikuti persentase pembayaran) atau `Contract Line Item`. Lalu pakai `Add Payment Term` untuk menambah tiap termin dengan persentase atau jumlah, persyaratan, tanggal estimasi, data invoice, tanggal bayar, dan status. Baris total di bawah menampilkan jumlahnya dan memberi peringatan bila termin kurang atau lebih dari revenue. `Export Excel` mengunduh tabelnya.

![Tabel Team Members dengan nama, posisi, module, role, dan periode](images/guide/dlv-project-team.png)

*Tangkapan layar dengan data contoh.*

6. Di `Team Members` pilih `Add Team Member` untuk menambah orang dengan module, role, dan periode. Pakai aksi pada baris untuk mengubah atau menghapus anggota.
7. Di `Project Documents` pilih `Create Folder` dulu: dokumen disimpan di folder OneDrive dan halaman menyatakannya sampai folder ada. Lalu `Upload Document`, dan ubah atau hapus dokumen dari barisnya.
8. `Location Information` memuat nama lokasi, tipe alamat, negara, wilayah, kota, masa berlaku, dan alamat jalan. Pilih pensil untuk mengubah.

## Apa yang terjadi selanjutnya

Menyimpan sebuah bagian langsung memperbarui project dan `Last Update Date`. Angka keuangan mengalir ke laporan seperti `Collection Outlook`.

## Pertanyaan umum

**Dokumen tidak bisa diunggah.** Buat folder dulu dengan `Create Folder`.

**Saya bisa melihat Delivery Info tetapi tidak mengubahnya.** Anda punya `view` tetapi tidak `edit`. Minta izinnya bila pekerjaan memerlukan.

**Total termin tidak sama dengan revenue.** Baris total memberi tahu berapa yang kurang atau lebih. Sesuaikan termin atau revenue.

<!-- sources: resources/views/delivery/project/projects/show.blade.php, routes/web.php -->
