---
key: resource-timeline
chapter: reporting
order: 122
title: Resource Timeline
summary: Lihat penempatan setiap konsultan hari demi hari, dan catat atau ubah penugasan konsultan ke customer.
requires: [reporting.resource-timeline]
updated: 2026-11-09
---
Resource Timeline adalah kisi kalender para konsultan. Setiap baris adalah seorang konsultan dan setiap kolom adalah satu hari, sehingga Anda dapat melihat siapa ditempatkan di customer mana dan kapan.

## Siapa yang menggunakan

Pimpinan delivery operation, RPMO, dan administrator yang aksesnya mencakup `Reporting` › `Resource Timeline`.

## Sebelum Anda mulai

- Penugasan dicatat menurut kode customer, jadi ketahui kode customernya lebih dulu. Customer yang tidak ada di daftar master bisa diketik langsung.
- Mengubah timeline mengubah apa yang dilihat semua orang yang punya akses, jadi periksa tanggalnya sebelum menyimpan.

## Langkah demi langkah

### Baca kisinya

1. Buka `Reporting` › `Resource Timeline`.
2. Pilih `Monthly` atau `Yearly`, lalu bulan dan tahun.
3. Persempit baris dengan `Search customer code` dan filter `Home Base` (`Jakarta`, `Yogyakarta`, `Surabaya` atau `Others`).
4. Baca tiap baris: `Consultant`, `Module`, `Status` dan satu sel per hari. Hari akhir pekan diberi warna.

### Catat penugasan

1. Klik baris seorang konsultan, lalu `Create Timeline`.
2. Periksa `Consultant`, lalu isi `Start Date` dan `End Date`.
3. Pilih customer dari dropdown `Customer Code`. Jika customer tidak ada di daftar master, ketik namanya di kolom `Or enter a customer that is not in the master list`.
4. Pilih `Save`.

### Ubah atau hapus penugasan

1. Klik baris konsultan. `Existing entries` menampilkan setiap penugasan beserta kode customer dan tanggalnya.
2. Untuk mengubah, pilih ikon edit pada entri itu. Formulir terisi dengan entri tersebut. Ubah `Start Date`, `End Date` atau `Customer Code`, lalu pilih `Save`. Pilih `Cancel Edit` untuk berhenti tanpa menyimpan.
3. Untuk menghapus, pilih ikon hapus lalu konfirmasi `Clear`. Hanya kode customer itu yang dikosongkan pada tanggal tersebut.

## Aturan yang perlu diketahui

- Hanya karyawan aktif dengan posisi SAP Consultant, Salesforce Consultant atau Project Management Officer yang tampil sebagai baris. Karyawan yang diblokir atau ditandai untuk dihapus tidak tampil.
- Baris dikelompokkan menurut Module (A sampai Z). Dalam satu kelompok, Lead di atas, lalu nama A sampai Z. `Status` menampilkan Module Group yang dipimpin orang tersebut.
- `End Date` tidak boleh sebelum `Start Date`.
- Dropdown `Customer Code` berisi customer aktif dari data master. Kode yang dipilih darinya harus ada di data master.
- Customer di luar daftar master bisa diketik di kolom khusus. Memilih dari dropdown mengosongkan kolom khusus, dan mengetik di kolom khusus mengosongkan dropdown, sehingga hanya satu yang disimpan. Jika teksnya sama dengan kode master (tanpa membedakan huruf besar atau kecil), yang disimpan adalah kode master. Spasi berlebih dipangkas, dan teks maksimal 255 karakter. Hanya huruf, angka, spasi, dan karakter `.` `,` `&` `(` `)` `/` `_` `-` yang diterima. Karakter lain, seperti tanda kutip atau kurung sudut, ditolak.
- Customer khusus tidak ditambahkan ke data master. Ia tampil di kisi seperti customer lain dan dapat diubah atau dikosongkan dengan cara yang sama.
- Seorang konsultan boleh berada di lebih dari satu customer pada hari yang sama. Menyimpan satu customer tidak menimpa customer lain pada tanggal yang sama. Keduanya tampil di kisi.
- Menyimpan dengan `Customer Code` kosong mengosongkan semua customer pada tanggal yang dipilih.
- Saat mengubah entri, tanggal dan kode customer lama dihapus lebih dulu, sehingga memperpendek atau menggeser tanggal tidak meninggalkan hari sisa.
- Hanya orang yang aksesnya mencakup `Reporting` › `Resource Timeline` yang dapat membaca atau mengubah data.
- Setiap pembuatan, perubahan dan penghapusan dicatat di Audit Log sebagai satu entri per aksi, berisi siapa pelakunya, konsultan mana, kode customer apa dan tanggal berapa.

## Apa yang terjadi selanjutnya

Kisi menampilkan penugasan baru pada hari yang Anda pilih, bagi semua orang yang dapat membuka halaman ini.

## Pertanyaan umum

**Kisinya kosong.** Tidak ada konsultan yang cocok dengan filter, atau belum ada yang diatur untuk bulan itu. Hapus filter lebih dulu.

**Saya tidak menemukan kode customer.** Jika customer seharusnya ada di daftar master, minta pemilik data customer menambahkannya. Jika customer sekali pakai di luar daftar master, ketik namanya di `Or enter a customer that is not in the master list`. Tulis dengan ejaan yang sama setiap kali, karena ejaan berbeda dianggap customer berbeda.

**Saya mengetik customer tetapi dropdown jadi kosong.** Itu memang begitu. Hanya satu dari dua kolom yang disimpan, dan yang Anda pakai terakhir yang dipakai.

<!-- sources: routes/web.php, app/Http/Controllers/ResourceTimelineController.php, resources/views/reporting/resource-timeline.blade.php -->
