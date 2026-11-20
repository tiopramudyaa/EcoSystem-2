---
key: reimbursement-management
chapter: finance
order: 40
title: Reimbursement Management
summary: Periksa, setujui, dan bayar Reimbursement Employee, serta atur aturan dan langkah persetujuannya.
requires: [general.reimbursement, general.settings.reimbursement, general.approval-workflow.reimbursement]
updated: 2026-11-20
---
Reimbursement Management adalah tempat HR, Finance, dan Approver menangani Request Reimbursement yang diajukan Employee. Halaman ini memiliki tiga tab: `Dashboard`, `Reimbursement Management`, dan `Settings`.

## Siapa yang menggunakan

Approver, HR, dan Finance. Anda hanya melihat tab dan tombol yang diizinkan akses Anda. Bila ada yang tidak tampil, hubungi administrator.

## Sebelum Anda mulai

- Employee mengajukan sendiri di `My Workspace` › `Reimbursement` (lihat [[my-reimbursement]]).
- Langkah persetujuan harus sudah ada di `Settings` › `Approval Workflow`. Lihat [[approval-workflows]].

## Langkah demi langkah

### Mencari dokumen

1. Buka `Reimbursement Management` › `Reimbursement Management`.
2. Baca kartu. Klik `Waiting for You` atau `Awaiting Payment` untuk menyaring daftar; klik lagi untuk menghapus saringan.
3. Pilih `Month`, atau gunakan ikon filter di header tabel. Klik `Reset Filter` untuk mengulang.

### Menyetujui atau menolak

1. Buka dokumen.
2. Periksa item, kuitansi, dan tautan pendukung di `Reimbursement Item Details`.
3. Bila giliran Anda, klik `Approve & Sign`, atau klik `Reject`, tulis alasan (minimal 5 karakter), lalu klik `Confirm Rejection`.
4. `Approval Trail` menampilkan siapa yang bertindak dan kapan.

### Mencatat pembayaran

1. Buka dokumen `Approved` yang berstatus `Awaiting payment`.
2. Di `Record payment`, isi `Paid on` dan `Transfer reference`. Tambahkan catatan bila perlu.
3. Klik `Mark as paid`. Dokumen menampilkan `Paid` beserta tanggalnya.

> [!WARNING]
> Bila Reimbursement sudah masuk periode payroll, ia dibayar bersama gaji. Catat pembayaran di sini hanya bila memang sudah dibayar terpisah.

### Membuat, mengimpor, dan mengekspor

- `New RB` membuat dokumen atas nama Employee.
- `Import RB` memuat banyak dokumen dari berkas template.
- `Monthly Export` mengunduh daftar yang sudah disaring. Tanpa rentang tanggal, yang diekspor adalah bulan berjalan. Dokumen bisa dicetak dengan `Print Form`.

### Menghapus dokumen

Buka dokumen, klik `Delete`, lalu isi alasan. Dokumen yang dihapus tetap terlihat pada filter status `Deleted`. Menghapus memerlukan akses khusus.

### Mengatur aturan (Settings)

1. Buka tab `Settings`.
2. Di `Approval Workflow`, tambah atau atur langkah, lalu klik `Save Workflow`.
3. Di `Reimbursement Rules`, atur batas dan isian wajib, lalu klik `Save Settings`. Aturan flag hanya menyorot dokumen; block menolaknya. Nilai 0 berarti tanpa batas.

### Mengatur Object dan dana (Settings)

Setiap item dibebankan ke sebuah Object: `Office`, `Project`, atau tiket `Support`. Object menentukan penyetuju dan dana yang diperiksa.

1. Buka tab `Settings` dan cari `Objects and Funds`.
2. Centang `Use Objects and funds for new reimbursements`. Biarkan mati untuk tetap memakai perilaku sebelumnya.
3. Di `Project approvals`, pilih siapa yang menyetujui (`Project Admin`, `Project Manager`), urutannya, serta role dan orang pengganti untuk project yang belum punya.
4. Di `Support approvals`, pilih apakah `Ticket Lead` dan `Support Manager` menyetujui, serta penggantinya.
5. Di `Who can charge costs to a project or ticket`, pilih `Team (lead and members)`, `Lead only`, atau `Everyone` untuk project dan untuk tiket support.
6. Di `When an amount exceeds the remaining fund`, pilih `Warn` atau `Block` untuk tiap Object. Klik `Save flow and policy` dan setujui dialognya.
7. Di `Office funds and responsible person`, beri setiap office seorang penanggung jawab (role, lalu orang). Aktifkan `Fund active` dan isi nominal bulanan hanya untuk office yang punya dana. Klik `Save offices`.
8. Di `Project and ticket fund allocations`, tambahkan alokasi untuk project atau tiket, dengan kategori opsional seperti Transport. Untuk project, `Amount comes from` dapat diisi `Project plan (read only)`: pilih satu baris biaya dan nominalnya mengikuti Plan Cost project. Selain itu pilih `Typed amount`. Klik `Add allocation`. Gunakan `Update` untuk mengubah nominal yang diketik atau menonaktifkannya.
9. Di `Exceptions for one project or ticket`, tetapkan aturan berbeda untuk satu project atau tiket lalu klik `Save exception`.

Setiap penyimpanan meminta konfirmasi lebih dulu; bila dibatalkan, tidak ada yang berubah.

### Membatalkan (void) dokumen yang sudah disetujui

1. Buka dokumen yang sudah disetujui dan belum dibayar. Jika sudah dibayar, klik `Reverse payment` lebih dulu.
2. Di bagian `Void`, tulis alasan (minimal 10 karakter) lalu klik `Void document`.
3. Dokumen berstatus `Void`, dana yang dipakainya kembali, dan pemohon diberi tahu.

## Aturan yang perlu diketahui

- Konsep Object hanya berlaku untuk dokumen yang diajukan saat `Use Objects and funds for new reimbursements` aktif. Dokumen yang sudah diajukan tetap memakai alurnya.
- Penyetuju ditetapkan otomatis saat dokumen diajukan dan tidak berubah sesudahnya. Item project ke `Project Admin` dan `Project Manager`, item support ke `Ticket Lead` (dan `Support Manager` bila diaktifkan), dan item office ke penanggung jawab office itu. Langkah ini lebih dulu, disusul langkah di `Approval Workflow`.
- Siapa yang boleh membebankan: dengan `Team`, lead project, delivery manager, dan anggota, atau lead tiket dan anggota, boleh membebankan ke sana. Dengan `Lead only`, hanya lead. Office tidak dibatasi. Aturan berlaku untuk pemilik dokumen, juga saat HR membuatkan dokumennya. Project atau tiket yang tidak boleh dipakai pemohon tidak ditawarkan di pencarian. Pengecualian menggantikan aturan bawaan untuk satu project atau tiket itu saja.
- Orang yang sama hanya diminta sekali walau muncul di beberapa item. Pemohon yang juga penyetuju melewati langkah itu, dan pelewatan itu dicatat.
- Pengajuan ditolak beserta alasannya bila project sudah ditutup, penyetuju yang dibutuhkan sudah tidak aktif, project atau tiket tidak punya penyetuju dan tidak ada pengganti, atau office belum punya penanggung jawab.
- Dana berkurang hanya saat dokumen disetujui sepenuhnya, bukan saat diajukan.
- Saat Anda membuka dokumen yang memakai Object, halaman menampilkan panel `Budget check`. Panel ini menunjukkan sisa dana bila dokumen disetujui. Merah berarti di bawah nol, dengan lencana `Over the fund`. Pada `Block`, persetujuan terakhir ditolak. Pemohon tidak melihat panel ini.
- Sisa dana = alokasi dikurangi dokumen yang sudah disetujui, diperiksa ulang tepat sebelum persetujuan terakhir.
- Object tanpa dana tidak diperiksa: office dengan `Fund active` mati, atau project atau tiket tanpa alokasi. Tetap melewati persetujuan.
- Dana office bersifat bulanan dan mengikuti tanggal dokumen. Alokasi project dan tiket tidak punya tanggal berakhir. Alokasi tanpa kategori mencakup semua kategori yang tidak punya alokasi sendiri.
- Dengan `Warn`, nominal di atas sisa dana tetap bisa diajukan bila pemohon menulis alasan (minimal 10 karakter). Penyetuju melihat alasannya dan dokumen ditandai `Over fund allocation`. Dengan `Block`, pengajuan ditolak dan pesannya menyebut sisa dana.
- Alokasi tidak bisa diturunkan di bawah yang sudah dipakai dokumen yang disetujui.
- Kategori hanyalah label untuk sebuah alokasi, misalnya Transport. Pemohon melihat pilihan `Fund category` hanya untuk project atau tiket yang punya minimal satu alokasi berkategori. Kosongkan kategori untuk satu alokasi umum. `Setup checklist` di bagian atas `Objects and Funds` menunjukkan apa yang masih kurang.
- Hanya pengguna yang boleh mencatat pembayaran yang dapat melakukan void, dan hanya untuk dokumen yang sudah disetujui dan belum dibayar. Void memerlukan alasan dan tidak dapat dibatalkan dari layar ini. Dokumen yang di-void tidak dapat dibayar.
- Alokasi yang mengikuti rencana project memakai nominal rilis dari baris biaya yang dipilih (atau budget-nya) dan diperbarui otomatis. Rencana tidak pernah diubah. Bila barisnya dihapus, nominal terakhir tetap dipakai. Tiket tidak punya budget rencana, jadi nominalnya diketik.
- `Approval Workflow` berisi langkah yang berlaku untuk setiap reimbursement, seperti Accounting, Cashier, dan penyetuju akhir. Langkah ini berjalan setelah langkah yang ditetapkan dari Object. Jangan menambahkan penyetuju project, tiket, atau office di sana, karena itu ditetapkan otomatis dan orang yang ada di kedua tempat akan diminta dua kali.
- Langkah yang ditambahkan ke `Approval Workflow` kemudian dapat diterapkan ke dokumen yang sedang berjalan. Langkah itu ditempatkan setelah langkah yang sudah dimiliki dokumen, dan dokumen yang sudah melewati titik itu tidak terpengaruh.
- Kolom Accounting dan Cashier pada dokumen cetak terisi bila langkah workflow memiliki `Signs as` Accounting atau Cashier. Tambahkan langkah itu di `Approval Workflow`; sebelum itu kedua kolom kosong.

## Apa yang terjadi selanjutnya

Dokumen yang disetujui menunggu pembayaran. Bila [[payroll]] menyertakan Reimbursement yang disetujui, nilainya dibayar bersama gaji.

## Pertanyaan umum

**Mengapa saya tidak bisa menyetujui dokumen?** Hanya orang yang langkahnya sedang berjalan yang dapat bertindak. Halaman menampilkan langkah saat ini.

**Apakah perubahan aturan memengaruhi dokumen yang ada?** Perubahan aturan berlaku untuk pengajuan baru. Dokumen yang sedang berjalan tetap memakai langkahnya.

## Topik terkait

- [[my-reimbursement]]
- [[approval-workflows]]
- [[payroll]]
- [[my-approvals]]

<!-- sources: routes/hr-general.php, app/Http/Controllers/HR_General/ReimbursementController.php, app/Http/Controllers/HR_General/ReimbursementSettingController.php, resources/views/hr-general/reimbursement/*.blade.php, app/Models/Reimbursement/ReimbursementRequest.php -->
