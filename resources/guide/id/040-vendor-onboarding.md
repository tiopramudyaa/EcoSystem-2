---
key: vendor-onboarding
chapter: business-legal
order: 40
title: Vendor Onboarding
summary: Daftarkan vendor, periksa dokumen dan pernyataannya, lalu jadikan vendor yang terverifikasi sebagai Business Partner.
requires: [legal.vendor-onboarding, legal.vendor-onboarding.approve, legal.vendor-onboarding.settings, "@approver"]
updated: 2026-11-03
---
Vendor Onboarding adalah tinjauan yang dilakukan sebelum sebuah perusahaan atau perorangan menjadi vendor. Anda mendaftarkan vendor, melampirkan dokumen identitas dan pajaknya, mencatat jawaban due diligence-nya, lalu mengirimnya untuk ditinjau. Setelah `Verified`, satu klik membuat Business Partner bertipe Vendor.

## Siapa yang menggunakan

- **Staf legal dan procurement** mendaftarkan vendor dan memulai tinjauan.
- **Peninjau** adalah posisi atau orang yang ditunjuk pada langkah persetujuan. Mereka memutuskan pendaftaran yang sampai kepada mereka dan menemukannya di [[my-approvals]].
- **Administrator** mengatur langkah persetujuan, jenis dokumen, dan pertanyaan di `Settings`.

## Sebelum Anda mulai

- Kumpulkan dokumen vendor, misalnya NIB, NPWP, dan akta pendirian untuk perusahaan, atau KTP, NPWP, dan CV untuk perorangan.
- Untuk membuat Business Partner, Anda juga memerlukan izin `Create` pada `Master` › `Business Partner`.

## Langkah demi langkah

### Daftarkan vendor

1. Buka `Business & Legal` › `Vendor Onboarding` lalu klik `Add Company` atau `Add Individual`.
2. Isi profil dan rekening bank. Untuk perusahaan, tambahkan penanggung jawab (PIC).
3. Di `Documents`, pilih berkas untuk setiap jenis dokumen. Jenis yang wajib bertanda bintang merah. Satu jenis dapat berisi beberapa berkas.
4. Jawab pernyataan due diligence lalu klik `Save`.
5. Klik `Submit registration`. Status menjadi `Submitted`.

> [!IMPORTANT]
> Dokumen bersifat privat. Hanya orang yang dapat membuka pendaftaran yang dapat membukanya, dan tidak ada tautan publik.

### Kirim untuk ditinjau

1. Buka pendaftaran lalu klik `Start review`. Datanya tidak dapat disunting lagi.
2. Peninjau pertama diberi tahu. Setiap peninjau membaca profil, dokumen, dan pernyataan, menulis catatan, lalu klik `Approve` atau `Reject`.
3. Setelah langkah terakhir menyetujui, status menjadi `Verified`. Penolakan mengakhiri tinjauan. Untuk memperbaikinya, buka pendaftaran lalu klik `Reopen to correct`.

### Buat Business Partner

1. Buka pendaftaran yang `Verified`.
2. Klik `Create Business Partner`. Vendor ditambahkan ke `Master` › `Business Partner` beserta NPWP, NIB atau NIK, rekening bank, alamat, dan kontaknya.
3. Bila vendor sudah ada sebagai Business Partner, pilih di bagian `Link`.

> [!NOTE]
> Bila email, nomor pajak, atau rekening bank sudah dimiliki Business Partner lain, data itu tidak disalin dan pesan akan menyebutkan mana yang dilewati. Dokumen tetap berada di Vendor Onboarding.

### Ubah pengaturan

1. Buka tab `Settings`.
2. Tambah atau ubah langkah persetujuan, jenis dokumen, dan pertanyaan. Mematikan jenis dokumen atau pertanyaan tidak menghapus yang sudah diunggah atau dijawab.

## Apa yang terjadi selanjutnya

Business Partner yang sudah tertaut dapat dipilih sebagai vendor di Delivery Support.

## Topik terkait

- [[business-partners]]
- [[sales-compliance]]
- [[my-approvals]]

<!-- sources: routes/legal-compliance.php, app/Http/Controllers/Legal/VendorOnboardingController.php, app/Http/Controllers/Legal/WorkflowSettingsController.php, app/Services/Legal/VendorOnboardingService.php, app/Services/Legal/StepWorkflowService.php, resources/views/legal/vendor-onboarding/index.blade.php, resources/views/legal/vendor-onboarding/form.blade.php, resources/views/legal/vendor-onboarding/show.blade.php, resources/views/legal/workflow-settings.blade.php -->
