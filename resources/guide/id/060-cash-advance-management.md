---
key: cash-advance-management
chapter: finance
order: 60
title: Cash Advance Management
summary: Setujui Cash Advance (CA) dan laporan penyelesaiannya (CAR), pantau laporan terlambat, dan atur aturannya.
requires: [general.cash-advance, general.cash-advance-report, general.cash-advance-dashboard, management.cash-advance-settings, management.approval-workflow.cash-advance, management.approval-workflow.cash-advance-report]
updated: 2026-11-20
---
Cash Advance (CA) adalah uang yang diberikan kepada Employee sebelum dibelanjakan. Setelah itu Employee membuat Cash Advance Report (CAR) untuk menyelesaikannya. Cash Advance Management adalah tempat Approver dan Finance memproses kedua dokumen tersebut. Halaman ini memiliki empat tab: `Dashboard`, `Cash Advance (CA)`, `Cash Advance Report (CAR)`, dan `Settings`.

## Siapa yang menggunakan

Approver, Finance, dan administrator. Anda hanya melihat tab yang diizinkan untuk Role Anda, dan setiap tombol memerlukan akses yang sesuai.

## Sebelum Anda mulai

- Employee mengajukan advance sendiri di `My Workspace` › `Cash Advance` dan melaporkannya di `Cash Advance Report` (lihat [[my-cash-advance]]).
- Langkah persetujuan untuk CA dan CAR terpisah. Lihat [[approval-workflows]].

## Langkah demi langkah

### Membaca Dashboard

`Dashboard` menunjukkan posisi CA dan CAR serta apa yang perlu Anda tangani: `CA waiting for you`, `CAR waiting for you`, `Oldest outstanding`, tampilan `Last 6 Months`, dan uang yang harus diselesaikan: `To collect (refund)` bila Employee membelanjakan lebih sedikit dari yang diterima, dan `To pay (claim)` bila membelanjakan lebih banyak.

### Menyetujui atau menolak CA

1. Buka `Cash Advance (CA)`.
2. Buka dokumen dan periksa nominal, tujuan, cost center, dan timeline persetujuan.
3. Bila giliran Anda, klik `Approve`, atau klik `Reject…` dan isi alasan.
4. Gunakan `New CA` untuk membuat advance atas nama Employee, bila Anda memiliki akses.

CA berjalan melalui `Submitted`, `In review`, `Approved`, `Rejected`, atau `Cancelled`. Secara terpisah, CA yang disetujui memiliki status penyelesaian: `Not reported`, `Reporting`, atau `Settled`.

### Memproses CAR

1. Buka `Cash Advance Report (CAR)`.
2. Bila ada kotak berisi advance yang menunggu dilaporkan, gunakan `+ New CAR` di samping advance yang tepat untuk memulai laporannya.
3. Buka laporan, lalu klik `Approve`, atau `Reject…` dengan alasan.
4. Menyetujui langkah terakhir akan menyelesaikan advance terkait dan menutupnya.

### Memantau laporan terlambat

Setiap CA yang disetujui memiliki batas waktu pelaporan. Laporan yang dikirim setelah tanggal jatuh tempo ditandai `Late report`, dan advance tampil sebagai `Overdue` di daftar. Pengingat harian dikirim kepada pemohon sebelum dan sesudah batas waktu.

### Mengatur aturan (Settings)

1. Buka tab `Settings`.
2. Di `Cash Advance Rules`, periksa batas seperti batas mundur tanggal, nominal minimum dan maksimum serta tindakan bila melebihi maksimum, mata uang yang diizinkan, panjang minimum deskripsi, jenis cost center, dan periode akuntansi terkunci.
3. Di `Settlement Rules (CAR)`, atur `Reporting deadline (days)` (0 berarti tanpa batas waktu) dan pengingat pertama.
4. Klik `Save settings`.
5. Halaman yang sama juga memuat Approval Workflow untuk CA dan CAR. Masing-masing hanya tampil bila Anda memiliki aksesnya. Klik `Save Workflow` setelah mengubah langkah.

### Memakai Object dan dana untuk cash advance

1. Buka `Reimbursement` › `Settings` › `Objects and Funds`. Penanggung jawab office, penyetuju pengganti, dana office, dan alokasi project atau tiket dipakai bersama dengan Reimbursement.
2. Centang `Use Objects and funds for new cash advances (and their reports)` lalu klik `Save flow and policy`. Saklar ini terpisah dari saklar Reimbursement.
3. Di `Approval Workflow` cash advance, sisakan hanya langkah yang berlaku untuk setiap cash advance, seperti Verification dan penyetuju akhir.

### Membatalkan (void) cash advance yang sudah disetujui

1. Buka cash advance yang sudah disetujui dan belum punya laporan.
2. Tulis alasan (minimal 10 karakter) lalu klik `Void cash advance`.
3. Cash advance berstatus `Void`, dan dana yang dipakainya kembali.

## Aturan yang perlu diketahui

- Konsep Object hanya berlaku untuk cash advance yang diajukan saat saklarnya aktif. Cash advance yang sudah diajukan tetap memakai alurnya.
- Satu cash advance dibebankan ke tepat satu Object: `Office`, `Project`, atau tiket `Support`. Object wajib diisi dan tidak dapat diubah saat dokumen diedit.
- Siapa yang boleh membebankan ke project atau tiket mengikuti aturan di `Reimbursement` › `Settings` › `Objects and Funds` (`Team`, `Lead only`, atau `Everyone`, beserta pengecualian). Diperiksa untuk pemilik dokumen saat cash advance diajukan. Office tidak dibatasi.
- Penyetuju ditetapkan otomatis saat cash advance diajukan dan tidak berubah sesudahnya. Mereka berjalan lebih dulu sebagai langkah `Verificator`, disusul langkah di `Approval Workflow`. Orang yang sama hanya diminta sekali, dan pemohon yang juga penyetuju melewati langkah itu.
- Pengajuan ditolak beserta alasannya bila project sudah ditutup, penyetuju yang dibutuhkan sudah tidak aktif, project atau tiket tidak punya penyetuju dan tidak ada pengganti, atau office belum punya penanggung jawab.
- Dana berkurang sebesar nominal uang muka hanya saat cash advance disetujui sepenuhnya. Tepat sebelum persetujuan terakhir sisa dana diperiksa ulang.
- Halaman cash advance menampilkan panel `Budget check` untuk penyetuju: dana, yang sudah disetujui, yang menunggu, cash advance ini, dan sisa setelahnya. Pemohon tidak melihatnya.
- Dengan `Warn`, nominal di atas sisa dana tetap bisa diajukan dengan alasan minimal 10 karakter, dan cash advance ditandai `Over fund allocation`. Dengan `Block` pengajuan ditolak.
- Laporan sebuah cash advance diperiksa oleh penyetuju Object yang sama, disusul langkah persetujuan laporan. Pemohon tidak memilihnya.
- Saat laporan disetujui dan cash advance berstatus settled, dana berubah menjadi nominal yang sungguh dibelanjakan. Bila lebih sedikit, selisihnya kembali ke dana. Bila lebih banyak, dana berkurang sebesar kelebihannya dan dapat berakhir di bawah nol, yang ditandai `Over fund allocation`.
- Cash advance yang sudah punya laporan tidak dapat di-void. Batalkan atau hapus laporannya lebih dulu. Hanya pengguna yang boleh mengelola cash advance yang dapat melakukan void, dan cash advance yang di-void tidak dapat dilaporkan.
- Langkah yang ditambahkan kemudian ke `Approval Workflow` dan diterapkan ke dokumen yang berjalan ditempatkan setelah langkah yang sudah dimiliki dokumen.
## Apa yang terjadi selanjutnya

Advance yang `Settled` ditutup. Nominal refund atau claim tampil di `Dashboard` sampai ditangani.

## Pertanyaan umum

**Bisakah saya mengubah langkah persetujuan untuk dokumen yang sedang berjalan?** Langkah disalin ke setiap dokumen saat dibuat, jadi perubahan berlaku untuk dokumen baru, kecuali Anda mencentang pilihan untuk menerapkan langkah baru pada dokumen yang sedang berjalan.

**Mengapa saya tidak melihat sebuah tab?** Setiap tab memiliki akses tersendiri. Hubungi administrator.

## Topik terkait

- [[my-cash-advance]]
- [[approval-workflows]]
- [[my-approvals]]
- [[workflow-finance]]

<!-- sources: routes/hr-general.php, app/Http/Controllers/HR_General/CashAdvance*.php, resources/views/hr-general/cash-advance/*.blade.php, app/Models/CashAdvance/CashAdvance.php -->
