---
key: my-tasks
chapter: work-service
order: 19
title: My Tasks
summary: Lihat ticket aktif yang ditugaskan kepada Anda, sisa pekerjaannya, dan perbarui progress Anda.
requires: [ticket.my-tasks]
updated: 2026-11-12
---
`My Tasks` mendaftar ticket aktif yang ditugaskan kepada Anda, beserta porsi pekerjaan Anda di tiap ticket. Pakai untuk melihat apa yang tersisa dan menjaga progress Anda tetap terbaru bagi tim.

## Siapa yang menggunakan

Konsultan dan ticket lead yang diberi menu `My Tasks` di Menu Access.

## Sebelum Anda mulai

- Sebuah ticket muncul di sini hanya jika ditugaskan kepada Anda, sebagai Ticket Lead, anggota tim yang aktif, atau lewat alokasi Consultant Mandays. Topik Ticket menjelaskan cara ticket ditugaskan dan cara mandays diajukan.
- Angka-angkanya butuh alokasi mandays pada ticket. Tanpa itu, ticket tetap tampil tetapi tanpa angka beban kerja.

## Langkah demi langkah

### Membaca daftar Anda

![My Tasks dengan kartu ringkasan dan daftar dua ticket aktif](images/guide/task-list.png)

*Tangkapan layar dengan data contoh.*

1. Buka `My Tasks` di sidebar.
2. Lima kartu di atas meringkas ticket Anda:
   - `Active Tickets`: jumlah ticket yang tampil.
   - `Alloc MD`: mandays yang dialokasikan untuk Anda.
   - `Remaining`: mandays yang masih harus Anda kerjakan.
   - `Workload`: sisa dibagi alokasi, dalam persen.
   - `Load Score`: satu angka untuk seberapa berat beban Anda (lihat aturan di bawah).
3. Tiap baris menampilkan nomor ticket (dengan tanda `Lead` bila Anda Ticket Lead), `Customer`, `Status`, `Priority`, `Module`, `Man Days`, bilah `Workload` Anda, `End Date`, dan `Progress` ticket.
4. Ketik di kotak pencarian untuk mencari ticket menurut nomor, subjek, customer, atau modul. Pakai panah di samping `Status` untuk menampilkan satu status saja. Pilih `Module` atau `Workload` di judul kolom untuk mengurutkan.

### Melihat siapa yang mengerjakan sebuah ticket

![Baris ticket yang dibuka untuk menampilkan tiap konsultan dengan mandays alokasi, sisa mandays, progress, dan catatan](images/guide/task-detail.png)

*Tangkapan layar dengan data contoh.*

1. Pilih tombol kecil berangka di ujung baris. Angkanya adalah jumlah konsultan yang mengerjakan ticket itu.
2. Tabel menampilkan modul, `Alloc MD`, `Add. MD`, `Remain`, progress, dan catatan tiap konsultan, dengan baris total.

### Memperbarui progress Anda

![Jendela progress dengan penggeser, kotak persen, dan catatan untuk konsultan](images/guide/task-edit-progress.png)

*Tangkapan layar dengan data contoh.*

1. Di tabel yang terbuka, pilih `Edit` pada baris Anda sendiri. Ticket Lead melihat `Edit` di setiap baris konsultan.
2. Geser penggeser atau ketik persen dari 0 sampai 100.
3. Tambahkan catatan singkat di `Catatan` bila perlu. Catatan paling banyak 500 karakter.
4. Pilih `Simpan`. Pilih `Batal` untuk keluar tanpa menyimpan.

Jika sebuah ticket belum punya alokasi konsultan, Ticket Lead dapat mengatur progress ticket langsung dengan tombol `Edit` di samping persen progress.

## Aturan yang perlu diketahui

- Daftar menampilkan ticket berstatus In Progress, Hold, Waiting on Customer, Waiting on 3rd Party, atau Waiting to Confirmation. Ticket yang `Open`, selesai, disembunyikan, atau dihapus tidak tampil di sini.
- Kotak bulan dan tahun di atas memang ditampilkan, tetapi daftar selalu menampilkan ticket aktif Anda saat ini, bulan apa pun yang Anda pilih.
- Mandays yang dialokasikan untuk Anda adalah mandays yang disetujui ditambah mandays tambahan yang disetujui. Sebelum Head menyetujui proposal, angka yang diajukan yang dipakai.
- `Remaining` untuk sebuah ticket adalah mandays alokasi Anda dikali (100% dikurangi progress Anda). `Workload` adalah sisa dibagi alokasi. `Load Score` adalah sisa dikali (1 ditambah 0,1 kali jumlah ticket), sehingga makin banyak ticket, sisa pekerjaan yang sama dihitung lebih berat.
- Progress sebuah ticket adalah rata-rata sederhana progress semua konsultan di ticket itu.
- Anda hanya dapat mengubah progress Anda sendiri. Ticket Lead dapat mengubah progress setiap konsultan di ticket itu dan progress ticketnya sendiri. Selain itu ditolak.
- Progress harus antara 0 dan 100, dan catatan paling banyak 500 karakter.
- Setiap perubahan progress konsultan tercatat di Audit Log dengan nilai lama dan baru.

## Apa yang terjadi selanjutnya

Progress ticket dan angka Anda diperbarui segera setelah Anda menyimpan. Angka yang sama dipakai di `Consultant Workload`, tempat tim melihat beban semua orang.

## Pertanyaan umum

**Ticket saya tidak ada di daftar.** Periksa bahwa ticket itu ditugaskan kepada Anda, dan statusnya bukan `Open`. Ticket yang masih `Open` muncul di sini setelah berpindah ke In Progress. Ticket yang disembunyikan atau selesai tidak pernah tampil.

**Saya tidak melihat tombol `Edit`.** Anda hanya dapat mengedit baris Anda sendiri, kecuali Anda Ticket Lead ticket itu.

**Angka saya berbeda dengan `Consultant Workload`.** `Consultant Workload` juga menghitung ticket yang masih `Open`. `My Tasks` tidak menampilkannya.

**`Workload` menampilkan tanda strip.** Belum ada alokasi mandays untuk Anda di ticket itu.

<!-- sources: routes/web.php, routes/api.php, resources/views/ticket/task.blade.php, app/Http/Controllers/TaskController.php, app/Http/Controllers/ConsultantWorkloadController.php, app/Models/Ticket.php -->
