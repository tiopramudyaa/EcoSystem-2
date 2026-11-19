---
key: my-reimbursement
chapter: my-workspace
order: 50
title: My Reimbursement
summary: Klaim biaya yang sudah Anda bayar, lampirkan tautan bukti, dan pantau klaim sampai disetujui.
requires: [general.my-reimbursement]
updated: 2026-11-19
---
Gunakan `My Reimbursement` untuk mengklaim biaya yang sudah Anda bayar dengan uang pribadi. Klaim diverifikasi oleh Approver sebelum dibayarkan.

## Siapa yang menggunakan

Setiap Employee yang memiliki menu `My Reimbursement`.

## Sebelum Anda mulai

- Kumpulkan struk atau bukti Anda dalam satu folder bersama atau berkas, lalu salin tautannya. Tautan harus dapat dibuka oleh orang yang memverifikasi klaim; sistem tidak dapat memeriksanya untuk Anda.
- Ketahui cabang untuk setiap biaya, tanggal bukti, dan nominalnya.

## Langkah demi langkah

### Mengajukan Reimbursement

1. Buka `My Reimbursement` dari sidebar lalu pilih `Submit Reimbursement`.
2. Jika ada pesan merah bahwa belum ada langkah persetujuan, berhenti. HR harus mengaturnya terlebih dahulu.
3. Periksa `Document No.`. Isinya `Generated on submit`.
4. Isi `Request Date` dan pilih `Currency` (IDR adalah bawaan).
5. Isi `Reimbursement Title`: satu baris yang merangkum seluruh klaim, misalnya biaya operasional bulan ini.
6. Tempel tautan `Supporting Document`. Jika kolom ditandai wajib, Anda tidak dapat mengirim tanpanya, dan halaman dapat menampilkan situs tautan yang diterima.
7. Isi tabel item. Untuk setiap item isi `Description`, tanggal bukti (dan tanggal akhir untuk bukti multi-hari), `Branch`, `Receipt No.`, dan `Amount`.
8. Pilih `Add More Reimbursement` untuk menambah item, atau ikon hapus untuk menghapusnya.
9. Di area tanda tangan, pilih Approver untuk setiap langkah yang meminta Anda memilih. Langkah tetap ditampilkan baca-saja.
10. Baca `Current rules`. Aturan ini menunjukkan batas mundur tanggal, jumlah item yang diizinkan, total maksimum, dan apakah nomor bukti wajib.
11. Pilih `Submit Request`.

> [!NOTE]
> Melebihi total maksimum akan ditolak atau ditandai untuk pemeriksa, tergantung aturan yang tampil di `Current rules`.

### Memantau klaim

1. Pada tabel `Reimbursement Requests`, baca `Document No.`, `Date`, `Summary`, `Item`, `Amount`, dan `Status`.
2. Pilih nomor dokumen untuk memperluas itemnya, berisi `Description`, `Category`, `Receipt No.`, `Receipt Date`, dan `Amount`.
3. Buka ikon detail untuk melihat jejak persetujuan, atau ikon cetak untuk mencetak dokumen.

## Aturan yang perlu diketahui

- Saat Object diaktifkan, setiap item punya kolom `Object`. Pilih `Office` dan sebuah office, `Project` dan sebuah project, atau `Support` dan nomor tiket, serta kategori bila ditawarkan.
- Anda tidak memilih penyetuju. `Reviewed by (assigned automatically)` menunjukkan siapa yang akan menyetujui dokumen, berdasarkan Object pada item Anda.
- `Fund category` hanya muncul bila project atau tiket yang Anda pilih punya kategori. Pilihan ini opsional. Bila tidak muncul, tidak ada yang perlu dipilih.
- Project harus masih berjalan. Untuk `Support`, klik kotak tiket untuk melihat tiket terbaru, atau ketik nomor tiket atau kata dari deskripsinya, lalu pilih tiket dari daftar. Daftar berasal dari menu Ticket. Teks yang diketik tanpa memilih tiket tidak diterima.
- Bila nominal di atas dana yang ditetapkan untuk sebuah Object, kotak `Reason for exceeding the fund` muncul. Tulis minimal 10 karakter agar dapat diajukan, kalau tidak dokumen ditolak. Pada kebijakan `Block`, dokumen tidak dapat diajukan sampai nominal dikurangi.
- Bila pengajuan ditolak karena project, tiket, atau office belum punya penyetuju, pesannya menyebut namanya. Minta Finance mengaturnya.
- Dana tidak ditampilkan kepada Anda. Dana hanya berpengaruh bila nominal melebihinya.
- Dokumen yang di-void tampil `Void` beserta alasannya, dan tidak dapat dibayar.
## Apa yang terjadi selanjutnya

Klaim berpindah dari `Submitted` ke `In review`, dan teks status menyebutkan langkah yang sedang ditunggu. Penolakan mengakhiri klaim dan alasannya ada di jejak persetujuan. Lihat [[statuses-and-approvals]].

> [!IMPORTANT]
> Anda tidak dapat membatalkan atau mengedit klaim yang sudah dikirim. Hanya HR yang dapat mengubahnya. Jika Anda salah, hubungi HR sebelum klaim disetujui.

## Pertanyaan umum

**Saya tidak dapat mengirim.** Pastikan setiap item memiliki cabang, nominal, dan deskripsi, serta nomor bukti atau tautan yang diwajibkan sudah terisi.

**Tautan saya ditolak.** Perusahaan hanya mengizinkan tautan dari situs tertentu. Situs yang diterima tercantum di bawah kolom.

## Topik terkait

- [[statuses-and-approvals]]
- [[my-cash-advance]]
- [[my-approvals]]

<!-- sources: routes/hr-general.php, app/Http/Controllers/HR_General/MyReimbursementController.php, resources/views/hr-general/reimbursement/my-reimbursement.blade.php, resources/views/hr-general/reimbursement/submit.blade.php, resources/views/hr-general/reimbursement/show.blade.php, resources/views/hr-general/reimbursement/components/item-rows.blade.php, app/Models/Reimbursement/ReimbursementRequest.php -->
