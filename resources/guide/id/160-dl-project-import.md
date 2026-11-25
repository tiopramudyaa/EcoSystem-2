---
key: dl-project-import
chapter: delivery
order: 10
title: Project: Import Excel
summary: Tambah banyak delivery project sekaligus dari file Excel, dan lihat baris mana yang diimpor, dilewati karena duplikat, atau ditolak.
requires: [delivery-project.add-new]
updated: 2026-11-25
---
`Import Excel` membuat delivery project dari file Excel, satu project per baris. Setiap baris diperiksa seperti `New Delivery Project Form`, dan baris yang sudah ada dilaporkan lalu dilewati.

## Siapa yang menggunakan

Project administrator dan siapa pun yang role-nya punya `Add New Project` di Menu Access. Tanpa izin itu tombol `Import Excel` tidak tampil.

## Sebelum Anda mulai

- Customer harus sudah ada di `Master` › `Business Partner` dengan tipe Customer, serta project owner dan account executive internal di Master Employee.
- Project type harus aktif di `Management` › `Project Type`.

## Langkah demi langkah

1. Buka `Delivery` › `Project` lalu pilih `Import Excel` di samping `Add New`. Tombol yang sama ada di bagian atas `New Delivery Project Form`.
2. Di jendela `Import Projects from Excel`, pilih `Download Template`. File berisi tiga sheet: `Projects` untuk diisi, `Lists` berisi nilai dropdown (project type, customer, karyawan, dan pilihan tetap), dan `Guide` berisi aturan setiap kolom.
3. Di `Projects`, isi satu project per baris mulai baris 3. Kolomnya dikelompokkan seperti formulir: `Project Form`, `Delivery Information`, dan `Location Information`. Kolom bertanda `*` wajib diisi.
4. Kembali ke jendela, pilih file (.xlsx, .xls, atau .csv, paling besar 10 MB) lalu pilih `Import`.
5. Hasilnya menampilkan tiga angka: `Imported`, `Duplicates skipped`, dan `Rows with errors`. Klik sebuah angka untuk menampilkan baris itu saja. Setiap baris menampilkan nomor barisnya di Excel dan alasannya.
6. Perbaiki baris yang error di file lalu impor file yang sama lagi. Baris yang sudah diimpor kini dilaporkan sebagai duplikat dan dilewati, jadi tidak ada yang dibuat dua kali.
7. Pilih `Close`. Bila ada project yang diimpor, daftar project terbuka dengan project barunya.

## Aturan yang perlu diketahui

- Satu file paling banyak 1.000 project. Baris judul dikenali dari judul kolomnya, jadi urutan kolom boleh berbeda. Kolom `No` dan judul grup di atas baris judul diabaikan.
- Wajib di setiap baris: `Customer/Client`, `Project Owner`, `Project Type`, `Contract Start Date`, `Contract End Date`, `IO/Number Order`, `Project Name`, dan `Description`.
- `Customer/Client` berisi kode customer atau nama customer. Tulisan `(Induk)` di belakang nama boleh dipakai. Bila customer induk dan cabangnya bernama sama, nama itu berarti induknya; untuk memilih cabang, isi kode customer cabangnya. Bila nama itu dipakai customer yang bukan induk dan cabang, baris ditolak; isi kode customer.
- `Project Owner`, dan `Account Executive Name` bila tipenya `Internal`, harus nama lengkap karyawan. Account executive eksternal berupa teks bebas. `Account Executive Type` wajib bila nama diisi.
- `Project Type` harus tipe aktif dengan ejaan yang sama, misalnya `Implementation`, bukan `Implementasi`.
- `High Level Risk` berisi Low, Moderate, atau High. `Delivery Method` berisi Onsite, Hybrid, atau WFH. `Type of Address` berisi Head Office atau Plant. Huruf besar atau kecil tidak berpengaruh.
- Tanggal boleh berupa tanggal Excel atau teks seperti 2026-01-31, 31/01/2026, atau 31-Jan-26. Tanggal selesai harus sama dengan atau setelah tanggal mulai.
- Revenue dan Plan Cost berupa angka 0 atau lebih. `Gross Profit (Rp)` dan `% Gross Profit` di file tidak dibaca: keduanya dihitung ulang sebagai Revenue dikurangi Plan Cost, sama seperti formulir. Warranty Period dan Total Mandays berupa bilangan bulat. Email harus alamat yang sah.
- Latitude dan Longitude diisi bersamaan. Bila keduanya kosong, koordinat diambil dari `Paste from Google Maps` (koordinat atau link Google Maps).
- IO Number hanya boleh dipakai ulang dalam customer yang sama, sama seperti formulir.
- Duplikat dilaporkan dan dilewati, tidak diimpor: baris yang sama persis dengan baris sebelumnya di file, dan baris yang Customer, IO Number, dan Project Name-nya sudah ada di EcoSystem atau di baris sebelumnya yang berhasil diimpor. Perbedaan huruf besar atau kecil dan spasi berlebih tidak dihitung sebagai perbedaan.
- Baris yang error dilewati; baris lain tetap diimpor. Setiap impor tercatat di `Audit Log`.

## Apa yang terjadi selanjutnya

Project hasil impor dimulai seperti project dari `Add New`: kategori Open, status On Track, dan folder dokumen standar. Tambahkan anggota tim, termin pembayaran, dan planning di halaman detail project.

## Pertanyaan umum

**Customer "cocok dengan lebih dari satu Business Partner".** Ada dua customer bernama sama yang bukan induk dan cabang. Isi kode customer; kodenya ada di pesan dan di sheet `Lists`.

**Project masuk ke customer induk, bukan cabang.** Nama yang dipakai induk dan cabangnya berarti induknya. Isi kode customer cabang untuk memilih cabang.

**Project Type ditolak.** Nilainya harus sama persis dengan Project Type yang aktif. Pakai dropdown di template.

**Saya mengimpor file yang sama dua kali.** Tidak ada yang dibuat dua kali: pada impor kedua semua baris dilaporkan sebagai duplikat.

<!-- sources: routes/web.php, app/Http/Controllers/DeliveryProjectImportController.php, app/Services/DeliveryProjectImporter.php, resources/views/delivery/project/projects/partials/import-projects-modal.blade.php, resources/views/delivery/project/projects/index.blade.php, resources/views/delivery/project/projects/create.blade.php -->
