---
key: contracts
chapter: human-capital
order: 50
title: Contract
summary: Siapkan Contract kerja sebagai Draft, lengkapi data untuk mengaktifkannya, kelola Template, dan jalankan penandatanganan.
requires: [general.contracts, general.contracts.list, general.contracts.templates]
updated: 2026-11-05
---
Menu `Contract` adalah tempat HR menyiapkan Contract kerja Employee internal: dari Draft, menjadi Contract Active, hingga salinan bertanda tangan dirilis kepada Employee.

## Siapa yang menggunakan

Staf HR yang menyiapkan Contract, dan staf Legal yang menjalankan penandatanganan. Employee dan Approver menandatangani dari My Profile.

## Sebelum Anda mulai

Buka `Human Capital` › `Contract`. Ada hingga empat tab, tergantung akses Anda: `Dashboard`, `Contracts`, `Templates`, dan `Settings`.

- `Dashboard` menampilkan Contract yang segera berakhir, catatan terbaru, serta total per bulan, jenis, dan departemen.
- `Contracts` mendaftar semua Contract dengan `Employee`, `Contract No.`, `Type`, `Department`, `Start`, `End`, dan `Status`. Gunakan `View`, `Print`, `Edit`, atau `Manage` pada sebuah baris.
- `Templates` berisi naskah Contract.
- `Settings` mengatur jenis Contract dan jenis posisi yang ditawarkan pada formulir.

## Draft dan Active

| Status | Arti |
|---|---|
| `Draft` | Sedang disiapkan. Anda dapat menyimpan Draft kapan saja, walau data belum lengkap. Hanya Draft yang dapat dihapus. |
| `Active` | Contract yang sedang berjalan. Naskahnya dibekukan. Memerlukan data Employee yang lengkap. |
| `Expired` | Diatur otomatis ketika tanggal berakhir sudah lewat. |
| `Terminated` | Diatur HR ketika Contract diakhiri lebih awal. |

### Lengkapi data terlebih dahulu

Pada halaman `Manage` seorang Employee, kartu `Contract Readiness` menampilkan persentase dan item yang masih kurang. Secara bawaan, Contract hanya dapat diubah menjadi `Active` bila mencapai 100%. Employee internal memerlukan:

- Nomor identitas: NIK, NPWP, BPJS Kesehatan, dan BPJS Ketenagakerjaan.
- Kontak: nomor ponsel, email pribadi, alamat domisili, kota, dan provinsi.
- Nama dan hubungan kontak darurat.
- Nama bank, nomor rekening, dan nama pemilik rekening.
- Dokumen: KTP, Kartu Keluarga, CV, dan Ijazah. Kartu NPWP bersifat opsional.

Jika ada yang kurang, halaman mendaftar tiap item. Klik sebuah item untuk membuka tab yang tepat pada profil Employee di tab baru, sehingga isian yang sudah Anda ketik pada formulir Contract tidak hilang. `Complete Employee Data` membuka profil Employee.

## Langkah demi langkah

### Membuat Contract

1. Buka `Contracts`, lalu pilih `Manage` pada baris Employee (atau cari orangnya).
2. Periksa `Contract Readiness`.
3. Pilih `Add Contract`.
4. Pilih `Contract type` dan `Start date`, lalu `Contract duration` atau `End date`. Nomor Contract dibuat otomatis.
5. Atur `Department`, `Position type`, `Position`, dan `Work location` bila perlu. Jika dikosongkan, isian mengikuti data Employee.
6. Pilih `Template` atau biarkan otomatis: Template jenis posisi, jika tidak ada Template posisi, jika tidak ada Template umum.
7. Pilih `Company signatory`.
8. Isi `Contract basic salary` dan `Allowances` bila akses Anda menampilkannya.
9. Periksa naskah yang dihasilkan di editor dan sesuaikan bila perlu.
10. Atur `Document status` ke `Draft` atau, bila sudah siap, `Active`, lalu pilih `Save Contract`.

> [!NOTE]
> Contract waktu tertentu (PKWT) tidak boleh berlangsung lebih dari lima tahun.

### Mengelola Template

1. Buka `Templates` lalu pilih sebuah Template, atau buat yang baru.
2. Atur `Template name`, `Status`, `Contract type`, dan, bila perlu, `Position type` dan `Position`.
3. Pilih `Company signatory` bawaan dan `Approver` yang menandatangani setelah Employee.
4. Tulis `Contract Text` seperti di pengolah kata.
5. Pilih `Create Template` atau `Save Template`.

Template bawaan sistem dapat diubah tetapi tidak dapat dihapus. Kop surat dan penandatangan dikelola di Document Identity, lihat [[letter-templates]].

### Ekspor dan impor Template

Gunakan ini untuk menyimpan salinan Template, memeriksanya di luar sistem, atau memindahkannya ke sistem lain. Pada tab `Templates`:

1. Pilih `Export templates` untuk membuka menu. `All templates — PDF (ZIP)` menghasilkan contoh Contract dengan nilai contoh, untuk dibaca, dicetak, atau dikirim untuk ditinjau. PDF tidak dapat diimpor kembali. `All templates — backup file` menghasilkan satu berkas berisi semua Template, untuk menyimpan salinan atau memindahkan Template.
2. Pada baris sebuah Template, gunakan `PDF`, `HTML`, atau `Backup` untuk Template itu saja.
3. Untuk menyunting naskah di luar sistem, unduh `HTML` lalu buka di editor teks seperti Notepad atau VS Code. Jangan simpan ulang dari Word: rincian Template hilang dan impor ditolak.
4. Pilih `Import templates` lalu pilih berkas backup (satu atau banyak Template) atau berkas HTML (satu Template). PDF ditolak, karena tidak dapat dibaca kembali dengan aman.
5. Pilih `Check file`. Belum ada yang tersimpan.
6. Tinjau tiap Template di tabel: `Template in the file`, `In this system`, `Notes`, dan `What to do`. Pilih `Add as new`, `Update the existing one`, `Add as a copy`, atau `Skip`.
7. Pilih `Import`.

Cara pemeriksaan bekerja:

- Template dicocokkan lewat jenis Contract ditambah nama. Template yang identik ditampilkan sebagai "Already here, identical".
- Template dengan placeholder tidak dikenal, jenis Contract tidak dikenal, atau teks kosong otomatis diatur ke `Skip` beserta alasannya.
- Template bawaan sistem yang hilang dipulihkan.
- Penandatangan dicocokkan lewat ECI Employee. Jika orangnya belum terdaftar sebagai penandatangan di sistem ini, kolomnya dikosongkan dengan peringatan.
- Script dalam teks dibuang, seperti saat menyimpan di editor.
- Kop surat dan data perusahaan tidak ikut: atur di `Letter Templates` › `Settings`. Jenis Contract dan jenis posisi harus sudah ada di `Settings` Contract, bila belum muncul peringatan dan Template tetap tersimpan.

> [!TIP]
> Setelah database dibersihkan, ikuti urutan: `Settings` Contract, penandatangan, impor Template, lalu Contract.

Setiap tombol hanya tampil bila Role Anda diberi izinnya di `Control Center` › `Menu Access` (menu Contract), misalnya `Template export — PDF` atau `Template import — backup file (.json)`. Secara bawaan hanya EC Administrator yang memilikinya. Impor juga memerlukan Create pada menu Templates, dan Edit untuk memperbarui Template yang sudah ada.

### Mencatat Contract yang sudah berlaku

Gunakan ini untuk Contract yang sudah berlaku dan ada sebagai dokumen cetak atau hasil pindai. Tidak ada teks buatan sistem dan tidak ada gaji yang dicatat.

1. Buka `Contracts`, lalu buka Employee dengan `Manage`.
2. Pilih `Add existing contract`.
3. Isi `Contract number (as printed)`, `Contract type`, `Start date`, `End date`, dan `Signed date`.
4. Atur `Status` ke `Active — still running` atau `Terminated — already ended`.
5. Isi `Position`, `Position type`, `Department`, `Work location`, dan `Notes` bila perlu.
6. Lampirkan `Scanned contract (PDF)`. Wajib diisi (PDF sungguhan, maksimal 10 MB) dan disimpan privat. Hanya yang dapat melihat gaji yang dapat membukanya.
7. Simpan catatan.

Aturannya: PKWT wajib tanggal akhir (maksimal 60 bulan), PKWTT tanpa tanggal akhir, Internship wajib tanggal akhir, nomor harus unik, dan Employee hanya boleh memiliki satu Contract berjalan (catat yang lama sebagai `Terminated — already ended`).

Riwayat menampilkan lencana `Imported`. Halaman dokumen menampilkan hasil pindai, tanpa teks buatan sistem, panel Signing, atau tab Generated. Payroll dan Salary Components tidak tersentuh, dan tanda tangan elektronik tidak berlaku.

Untuk mengoreksi catatan, pilih `Edit details` (perbaiki data atau ganti PDF). Pilih `Remove` bila salah Employee atau berkas: hasil pindai ikut terhapus. Tombol `Edit` biasa tidak berlaku untuk Contract impor.

### Menandatangani Contract

Penandatanganan dimulai ketika Contract bukan lagi Draft. Tahapannya adalah `E-stamped`, `Employee signs`, `Approver signs`, `Print`, dan `Released`.

1. Buka dokumen Contract, unduh PDF, bubuhkan meterai, lalu unggah salinan bermeterai.
2. Pilih `Send to employee`, atur batas waktu, pilih `Approver`, dan tambahkan catatan. Employee menerima Notification.
3. Employee memeriksa dan menandatangani setiap halaman dari My Profile. Anda dapat membuka `Signing record` kapan saja.
4. Pilih `Forward to approver`. Approver menandatangani.
5. Cetak Contract yang sudah disetujui, lalu pilih `Release to employee` agar Employee dapat mengunduhnya.

Jika penandatangan meminta perubahan, ubah Contract, unggah salinan bermeterai yang baru, lalu kirim kembali. Anda dapat menarik Contract dari proses penandatanganan. Tombol penandatanganan memerlukan izin penandatanganan Contract.

## Apa yang terjadi selanjutnya

Contract Active muncul di riwayat Contract Employee dan dihitung dalam [[onboarding]]. Ketika seseorang keluar, [[offboarding]] menutup Contract yang sedang berjalan.

## Pertanyaan umum

**Mengapa saya tidak bisa memilih Active?** Data Employee belum lengkap. Simpan sebagai Draft dan lengkapi item yang kurang.

**Mengapa gaji tidak terlihat?** Nilai gaji memerlukan izin tersendiri.

**Bisakah saya mengimpor PDF sebagai Template?** Tidak. PDF tidak menyimpan struktur teks dan isian `{{placeholder}}`. Impor berkas backup atau berkas HTML.

**Mengapa Add existing contract atau tombol ekspor tidak ada?** Role Anda belum diberi izinnya. Hubungi administrator.

**Mengapa sebuah Template dilewati saat impor?** Kolom `Notes` memberi alasannya. Perbaiki berkasnya lalu periksa lagi.

## Topik terkait

- [[employee-data]]
- [[letter-templates]]
- [[offboarding]]
- [[external-contracts]]

<!-- sources: routes/hr-general.php, config/hc_contract.php, app/Support/Contracts/ContractRules.php, app/Http/Controllers/HR_General/ContractController.php, resources/views/hr-general/contracts/show.blade.php, resources/views/hr-general/contracts/index.blade.php, resources/views/hr-general/contracts/components/signing-panel.blade.php, resources/views/hr-general/contracts/template-form.blade.php -->
