---
key: consultant-workload
chapter: work-service
order: 20
title: Consultant Workload
summary: Lihat seberapa terbebani tiap konsultan, buka seorang konsultan untuk melihat ticketnya, dan ekspor daftar ke Excel.
requires: [ticket.consultant-workload]
updated: 2026-11-12
---
`Consultant Workload` menampilkan setiap konsultan beserta pekerjaan yang ia pikul: berapa ticket, berapa mandays dialokasikan, berapa tersisa, dan skor beban. Pakai untuk memutuskan siapa yang punya ruang untuk ticket baru dan siapa yang kelebihan beban.

## Siapa yang menggunakan

Head, manajer, dan siapa pun yang diberi menu `Consultant Workload` di Menu Access, misalnya untuk merencanakan siapa yang mengambil ticket berikutnya.

## Sebelum Anda mulai

- Daftar dibangun dari ticket dan Consultant Mandays-nya. Konsultan tanpa alokasi tidak menampilkan angka beban kerja.
- Hanya karyawan aktif dengan role Delivery Support User dan posisi SAP CONSULTANT yang terdaftar.

## Langkah demi langkah

### Membaca daftar

![Consultant Workload dengan bilah filter dan satu baris per konsultan](images/guide/consultant-workload-list.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Consultant Workload` di sidebar. Konsultan dengan persentase beban tertinggi tampil lebih dulu.
2. Tiap baris menampilkan konsultan, `Personnel Sub Area`, `Current Assignment`, `Module` (modul dari kualifikasinya), jumlah `Tickets`, `Alloc Days`, `Remain`, `Workload`, dan `Load Score`.
3. Baca warnanya: bilah `Workload` merah mulai 70%, kuning mulai 40%, dan hijau di bawahnya. `Load Score` merah mulai 10, kuning mulai 5, dan hijau di bawahnya.
4. Lencana oranye kecil di samping `Remain`, misalnya `↑3 d`, adalah sisa hari yang dibulatkan ke atas menjadi hari penuh.

### Menyaring dan mengurutkan

1. Pakai `Group Modul` di kanan atas untuk menampilkan konsultan dari satu grup modul.
2. Di judul kolom, saring menurut `Consultant` (nama atau ECI), `Personnel Sub Area`, dan `Module`.
3. Pilih judul `Tickets`, `Alloc Days`, `Remain`, `Workload`, atau `Load Score` untuk mengurutkan. Pilih lagi untuk membalik urutan.
4. `Expand All` membuka semua konsultan dan `Collapse All` menutupnya.

### Melihat ticket seorang konsultan

![Semua konsultan terbuka, masing-masing dengan tabel ticket, role, status, prioritas, hari alokasi dan sisa, serta progress](images/guide/consultant-workload-expanded.png)

*Tangkapan layar dengan data contoh.*

1. Pilih baris seorang konsultan.
2. Tabel mendaftar tiap ticket dengan `Role` konsultan (Ticket Lead atau Member), `Status`, `Priority`, `Day not Close`, `Alloc Days`, `Add. Days`, `Remain`, dan `Progress`, serta baris total.
3. Untuk memperbarui progress, pilih ikon pensil di samping bilah progress. Pensil tampil di setiap baris, tetapi Anda hanya dapat menyimpan progress Anda sendiri, atau konsultan mana pun di ticket tempat Anda Ticket Lead.
4. Pilih `Export Excel` untuk mengunduh ticket konsultan itu sebagai spreadsheet.

## Aturan yang perlu diketahui

- Daftar mencakup ticket berstatus Open, In Process, Hold, Waiting on Customer, Waiting on 3rd Party, atau Waiting to Confirmation. Ticket yang disembunyikan dan dihapus tidak termasuk. Ini mencakup ticket yang masih `Open`, sehingga angkanya bisa lebih tinggi daripada di `My Tasks`.
- `Alloc Days` adalah mandays konsultan yang disetujui di ticket itu. Sebelum Head menyetujui proposal, mandays yang diajukan yang dipakai, sehingga angkanya mengikuti proposal terbaru.
- `Remain` adalah hari alokasi (beserta hari tambahan yang disetujui) dikali (100% dikurangi progress konsultan di ticket itu). `Workload` adalah sisa dibagi alokasi. `Load Score` adalah sisa dikali (1 ditambah 0,1 kali jumlah ticket).
- Progress satu ticket adalah rata-rata progress semua konsultan di ticket itu. Progress bernilai 0 sampai 100, dan catatan paling banyak 500 karakter.
- Anda hanya dapat mengubah progress Anda sendiri, kecuali Anda Ticket Lead ticket itu. Maka Anda dapat mengubah setiap konsultan di dalamnya. Menyimpan hal lain ditolak dengan pesan bahwa Anda hanya dapat mengubah progress milik sendiri.
- Perubahan progress tercatat di Audit Log. `Export Excel` menghasilkan angka yang sama dengan yang Anda lihat di layar untuk konsultan itu.
- `Day not Close` menghitung hari sejak ticket dibuat.

## Apa yang terjadi selanjutnya

Pakai angka beban saat menugaskan ticket berikutnya. Para konsultan memperbarui progress mereka sendiri di `My Tasks`.

## Pertanyaan umum

**Seorang konsultan tidak ada di daftar.** Hanya karyawan aktif dengan role Delivery Support User dan posisi SAP CONSULTANT yang terdaftar. Minta HR memeriksa posisi dan rolenya.

**Seorang konsultan menampilkan 0 hari.** Tidak ada mandays yang dialokasikan untuk konsultan itu di ticket aktif.

**Halaman hanya menampilkan bulan ini.** Daftar selalu mencerminkan ticket yang aktif saat ini, bukan bulan yang lalu.

**Muncul pesan bahwa saya hanya dapat mengubah progress milik sendiri.** Pensil tampil di setiap baris, tetapi hanya konsultan itu dan Ticket Lead ticketnya yang dapat menyimpan perubahan.

**Angkanya berbeda dari `My Tasks`.** `My Tasks` tidak menampilkan ticket yang masih `Open`. Halaman ini menyertakannya.

<!-- sources: routes/web.php, routes/api.php, resources/views/ticket/consultant-workload.blade.php, app/Http/Controllers/ConsultantWorkloadController.php -->
