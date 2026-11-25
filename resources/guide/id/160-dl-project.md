---
key: dl-project
chapter: delivery
order: 10
title: Project
summary: Temukan delivery project, saring dengan kartu ringkasan, tambah project baru, ekspor daftar, dan tutup atau hapus project.
requires: [delivery.project, delivery-project.add-new, delivery-project.close-project, delivery-project.delete-project]
updated: 2026-11-02
---
`Delivery` › `Project` mendaftar semua delivery project perusahaan. Dari sini Anda membuka project, menambah project baru, dan mengekspor daftar.

## Siapa yang menggunakan

Project head, project administrator, RPMO, dan manajemen, serta role mana pun yang diberi menu `Delivery` › `Project` di Menu Access. Tombol seperti `Add New` hanya muncul untuk role yang diberi izinnya.

## Sebelum Anda mulai

- Ketahui customer, project owner, tipe project, tanggal kontrak, dan nomor IO project.
- Customer harus sudah ada di `Master` › `Business Partner`, dan tipe project berasal dari `Management` › `Project Type`.

## Langkah demi langkah

![Daftar Project Delivery dengan kartu ringkasan dan tabel project](images/guide/dlv-project-list.png)

*Tangkapan layar dengan data contoh.*

### Cari project

1. Buka `Delivery` › `Project`. Tiga tab di atas adalah `Project`, `Issues`, dan `Planning`. `Project` adalah daftarnya.
2. Baca kartu `SUMMARY`: `Total Projects`, `Open`, `In Process`, `Closed`, `On Track`, `At Risk`, `At Critical`, dan `Ending ≤ 30 days`. Klik kartu untuk menyaring daftar.
3. Di tabel, setiap project menampilkan `Last Update`, `IO Number`, `Customer`, `Project Type`, `Project Name`, `AE`, `Project Owner`, `Category`, dan `Project Status`. Project yang paling baru diperbarui ada di atas. Pakai panah di judul kolom untuk mengurutkan dan ikon corong untuk menyaring.
4. Klik sebuah project untuk membuka halaman detailnya (lihat topik detail project).
5. Pilih `Export Excel`, lalu `Project List`, untuk mengunduh daftar. Item lain di menu itu (`Project Revenue`, `Invoiced Terms of Payment`) tetap langsung mengunduh seperti sebelumnya.

### Pilih field yang diekspor

1. Jendela berjudul `Export Delivery Projects` terbuka. Isinya daftar `Template` dan semua kotak centang `Fields`.
2. Centang field yang ingin masuk ke file. Ketik di `Search fields` untuk mencari satu field di daftar panjang. `Select all` mencentang semua field dan `Unselect all` menghapus semua centang.
3. Untuk memakai pilihan ini lagi, ketik nama di bawah `Save these fields as a template`, pilih `Personal` atau `Global`, lalu pilih `Save`. Template itu kemudian muncul di daftar `Template` dengan tanda `(Personal)` atau `(Global)`.
4. Lain kali, buka jendela yang sama dan pilih template di `Template`. Field-nya sudah tercentang. Template `Global` buatan rekan ada di daftar yang sama. Pilih `Delete` untuk menghapus template Anda yang tidak dipakai lagi.
5. Pilih `Export`. File hanya berisi field yang dicentang.

### Tambah project

![Formulir New Delivery Project dengan kolom wajib bertanda](images/guide/dlv-project-create.png)

*Tangkapan layar dengan data contoh.*

1. Pilih `Add New`.
2. Isi kolom wajib bertanda bintang merah: `Customer/Client`, `Project Owner`, `Project Type`, `Contract Start Date`, `Contract End Date`, `IO/Number Order`, `Project Name`, dan `Description`. `High Level Risk` bersifat opsional.
3. `Category`, `Phase`, dan `Go Live Estimated` tidak bisa diketik. Kolom ini terisi otomatis dari planning project setelah planning ada.
4. Formulir yang sama juga memuat data penjualan (`Delivery Information`), `Delivery Data`, `Team Members` pertama, dan `Location Information`. Isi yang sudah Anda ketahui, sisanya dapat ditambahkan nanti di halaman detail project.
5. Kirim formulir. Project baru lalu muncul di daftar.

### Tutup, buka kembali, atau hapus project

Di halaman detail project:

- `Close Project` mengunci project: datanya tidak bisa diubah lagi. Tombolnya lalu menjadi `Reopen Project` bagi yang berhak.
- `Delete Project` menghapus project selamanya. Bila project masih punya data terkait, EcoSystem menolak dan menyebut masih ada data terkait. Tutup saja project bila pekerjaan sudah selesai.

## Aturan yang perlu diketahui

### Saat mengekspor

- Field yang bisa dicentang adalah kolom export penuh `Project List`.
- Template hanya memilih kolom. Baris yang diekspor tetap ditentukan oleh filter, kartu, dan pencarian yang aktif di daftar.
- Bila semua field dicentang, hasilnya export penuh yang biasa. Bila field lebih sedikit, file berisi sel polos: baris judul merah, baris pertama dibekukan dan lebar kolom menyesuaikan, tanpa format angka dan perataan seperti export penuh.
- Minimal satu field harus dicentang, dan template butuh nama sampai 80 karakter.
- Template `Personal` hanya dilihat dan dipakai oleh Anda. Template `Global` dilihat dan dipakai semua orang yang bisa membuka export ini, dan diberi tanda nama pembuatnya.
- Hanya pembuatnya yang boleh mengubah template. Orang lain boleh memakai template `Global` tetapi tidak boleh mengubah atau menghapusnya; untuk menyimpan variasi, mereka menyimpannya dengan nama lain. EC Administrator boleh menghapus template `Global` mana pun.
- Setiap daftar punya field dan template sendiri.
- Di antara template Anda sendiri untuk satu daftar, nama harus unik, baik `Personal` maupun `Global`. Menyimpan dengan nama yang sudah Anda pakai mengganti template itu dan bisa mengubahnya antara `Personal` dan `Global`. Nama `Global` juga harus berbeda dari template `Global` lain di daftar itu.
- `Save` menyimpan field yang sedang tercentang. Mengubah centang pada template yang dipilih lalu memilih `Export` tidak mengubah template.
- Jendela terbuka dengan template yang terakhir Anda pakai di browser ini.
- Bila sebuah field kelak dihapus dari export, field itu hilang begitu saja dari template tersimpan Anda.
- Template, termasuk yang `Global`, tidak mengubah siapa yang boleh mengekspor. Tombol export tetap mengikuti akses Anda di `Menu Access`, jadi template `Global` hanya berguna bagi orang yang bisa mengekspor daftar ini.

## Apa yang terjadi selanjutnya

Project baru muncul di daftar dan di laporan yang membaca project, seperti `Collection Outlook`. Project yang ditutup dihitung di kartu `Closed`.

## Pertanyaan umum

**Tidak ada tombol `Add New`.** Role Anda tidak memiliki `Add New Project`. Minta administrator.

**Saya tidak bisa mengubah apa pun pada project.** Kemungkinan project sudah ditutup. Buka kembali dulu bila Anda berhak.

<!-- sources: routes/web.php, resources/views/delivery/project/projects/index.blade.php, resources/views/delivery/project/projects/create.blade.php -->
