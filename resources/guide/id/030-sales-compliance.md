---
key: sales-compliance
chapter: business-legal
order: 30
title: Sales Compliance
summary: Kirim setiap deal penjualan untuk ditinjau compliance sebelum ditandatangani, dan putuskan deal yang dikirim kepada Anda.
requires: [legal.sales-compliance, legal.sales-compliance.approve, legal.sales-compliance.settings, "@approver"]
updated: 2026-11-03
---
Sales Compliance adalah tempat setiap deal penjualan diperiksa sebelum ditandatangani. Sales menjelaskan deal-nya, mencantumkan harga kesepakatan terhadap MRP, melampirkan dokumen harga, dan menjawab pernyataan anti-suap serta benturan kepentingan. Orang-orang yang ditunjuk pada langkah persetujuan lalu menyetujui atau menolaknya.

## Siapa yang menggunakan

- **Sales** mengisi dan mengajukan permintaan mereka sendiri. Mereka hanya melihat permintaan yang mereka buat.
- **Peninjau compliance** adalah posisi atau orang yang ditunjuk pada langkah persetujuan. Mereka memutuskan permintaan yang sampai kepada mereka dan tidak memerlukan izin menu untuk itu. Permintaannya juga ada di [[my-approvals]].
- **Administrator** mengatur langkah persetujuan dan pertanyaan di `Settings`.

## Sebelum Anda mulai

- Siapkan dokumen harga kesepakatan dan dokumen MRP (PDF, JPG, PNG, atau WEBP, maksimal 10 MB per berkas).
- Seseorang tidak dapat memutuskan permintaan yang ia ajukan sendiri. Bila Anda membuat permintaan atas nama sales, orang lain yang harus memutuskannya.

## Langkah demi langkah

### Kirim deal untuk ditinjau

1. Buka `Business & Legal` › `Sales Compliance` lalu klik `New Request`.
2. Isi `Project name`, `Customer`, `Customer PIC`, serta keterangan pertemuan dan jamuan. Pilih customer dari daftar atau ketik nama baru.
3. Isi `Final (agreed) price` dan `MRP price`. Halaman menampilkan selisihnya. Bila keduanya berbeda, tulis `Price justification`.
4. Jawab setiap pertanyaan yang bertanda bintang merah, lalu klik `Save draft`.
5. Di `Documents`, unggah dokumen harga kesepakatan dan dokumen MRP. Unggahan baru untuk salah satunya menggantikan yang lama.
6. Klik `Submit for review`. Status berubah menjadi `In Review` dan penyetuju pertama diberi tahu.

> [!NOTE]
> Selama permintaan berstatus `In Review`, Anda tidak dapat menyuntingnya. Gunakan `Cancel request` untuk menariknya kembali.

### Putuskan sebuah permintaan

1. Buka permintaan dari [[my-approvals]] atau dari tab `Requests`.
2. Baca deal-nya, harga terhadap MRP, dokumen, dan pernyataannya.
3. Tulis catatan, lalu klik `Approve` atau `Reject`. Catatan wajib diisi saat menolak.
4. Setelah langkah terakhir menyetujui, permintaan menjadi `Approved`. Penolakan langsung mengakhiri tinjauan.

### Perbaiki permintaan yang ditolak

1. Buka permintaan yang ditolak dan baca catatannya.
2. Klik `Reopen to correct`, ubah yang perlu, lalu ajukan lagi. Putaran sebelumnya tetap terlihat di `Approval steps`.

### Ubah siapa yang menyetujui (Settings)

1. Buka tab `Settings`.
2. Di `Approval steps`, tambahkan langkah, pilih posisi atau orang tertentu, dan atur urutannya. Permintaan yang sedang ditinjau tetap memakai langkah saat ia mulai.
3. Di `Questions`, ubah kalimat atau matikan sebuah pertanyaan. Jawaban yang sudah diberikan tetap terbaca.

## Apa yang terjadi selanjutnya

Sales diberi tahu atas setiap keputusan. Permintaan yang `Approved` adalah catatan bahwa deal tersebut lolos tinjauan compliance.

## Pertanyaan umum

**Mengapa saya tidak bisa menyetujui permintaan saya sendiri?** Tinjauan ini ada agar ada pemeriksa kedua, sehingga orang yang mengajukan tidak pernah memutuskan permintaannya.

**Mengapa saya hanya melihat sebagian permintaan?** Sales melihat permintaan yang mereka buat. Orang yang memutuskan permintaan, dan administrator, melihat semuanya.

## Topik terkait

- [[my-approvals]]
- [[vendor-onboarding]]
- [[business-partners]]

<!-- sources: routes/legal-compliance.php, app/Http/Controllers/Legal/SalesComplianceController.php, app/Http/Controllers/Legal/WorkflowSettingsController.php, app/Services/Legal/SalesComplianceService.php, app/Services/Legal/StepWorkflowService.php, resources/views/legal/sales-compliance/index.blade.php, resources/views/legal/sales-compliance/form.blade.php, resources/views/legal/sales-compliance/show.blade.php, resources/views/legal/workflow-settings.blade.php -->
