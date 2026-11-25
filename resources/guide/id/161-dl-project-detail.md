---
key: dl-project-detail
chapter: delivery
order: 11
title: Detail project: General, Delivery Info, Team, Documents, dan Location
summary: Baca dan ubah satu project: informasi umum, data penjualan dan termin pembayaran, anggota tim, dokumen, dan lokasi.
requires: [delivery-project.general.view, delivery-project.delivery-data.view, delivery-project.delivery-info.view, delivery-project.location.view, delivery-project.team.view, delivery-project.documents.view]
updated: 2026-11-25
---
Halaman detail project adalah satu halaman panjang. Tab di atas (`General`, `Delivery Info`, `Stakeholders`, `Team`, `Documents`, `Issues`, `Risks`, `Location`, `Planning`, `WRICEF`, `Plan Cost`) menggulir ke sebuah bagian. Topik ini membahas kelompok pertama. Register dan rencana ada di dua topik berikutnya.

## Siapa yang menggunakan

Project head, project administrator, dan tim project. Setiap bagian punya izin `view`, `edit`, dan `create or delete` sendiri di Menu Access. Bagian yang tidak boleh Anda lihat tidak muncul, dan ikon pensil atau tombol `Add` yang tidak boleh Anda pakai disembunyikan.

## Sebelum Anda mulai

- Project yang ditutup terkunci. Buka kembali dulu bila perlu mengubahnya.
- Revenue dan termin pembayaran adalah data keuangan. Periksa sebelum menyimpan.

## Langkah demi langkah

![Halaman Project Detail dengan tab di atas dan kartu General Information](images/guide/dlv-project-detail.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Delivery` › `Project` lalu klik sebuah project. Bilah judul menampilkan project dan customer, dengan tombol `Create Folder`, `Close Project`, dan `Delete Project`.
2. Di `General Information` baca customer, nama, owner, tipe, nomor IO, kategori, fase, tanggal kontrak, dan estimasi go-live. Pilih ikon pensil untuk mengubah. `Category`, `Phase`, dan `Go Live Estimated` terisi dari planning dan tidak bisa diketik.
3. `Delivery Data` memuat metode delivery, masa garansi, dan total mandays.

![Kartu Delivery Information dengan data penjualan dan rencana Term Of Payment](images/guide/dlv-project-delivery-info.png)

*Tangkapan layar dengan data contoh.*

4. Di `Delivery Information`, `Sales Data` menampilkan account executive, `Revenue`, `Plan Cost`, `Gross Profit`, persentasenya, dan angka aktual. Pilih pensil untuk mengubah.
5. Di bawahnya, pilih `TOP Type`: `% of Revenue` (jumlah mengikuti persentase pembayaran) atau `Contract Line Item`. Lalu pakai `Add Payment Term` untuk menambah tiap termin dengan persentase atau jumlah, persyaratan, tanggal estimasi, data invoice, tanggal bayar, dan status. Baris total di bawah menampilkan jumlahnya dan memberi peringatan bila termin kurang atau lebih dari revenue. `Export Excel` mengunduh tabelnya.

![Tabel Team Members dengan nama, posisi, module, role, dan periode](images/guide/dlv-project-team.png)

*Tangkapan layar dengan data contoh.*

6. Di `Team Members` pilih `Add Team Member` untuk menambah orang dengan module, role, dan periode. Pakai aksi pada baris untuk mengubah atau menghapus anggota.
7. Di `Project Documents` pilih `Create Folder` di bilah judul dulu: dokumen disimpan di folder OneDrive dan halaman menyatakannya sampai folder ada. Dokumen dikelompokkan dalam folder: `01. Discover`, `02. Prepare`, `03. Explore`, `04. Realize`, `05. Deploy`, `06. Run`, dan `00. Other`, lalu folder yang ditambahkan tim Anda. Setiap folder menampilkan jumlah dokumennya. Klik folder untuk membuka atau menutupnya, atau pakai `Expand all` dan `Collapse all`.
   - Untuk mengunggah, pilih `Upload Document` lalu klik salah satu folder di bawah `Folder`. Folder yang dipilih ditandai dan ditambahkan di ujung `TUJUAN UPLOAD`, yang menunjukkan jalur lengkapnya di OneDrive. Lalu pilih file dan `Document Type`, kemudian pilih `Upload`. Ikon unggah pada sebuah folder membuka jendela yang sama dengan folder itu sudah terpilih.
   - Untuk menambah folder, pilih `Add Folder`, ketik namanya saja (misalnya `Change Request`), lalu pilih `Save`. Nomornya diberikan otomatis: folder tambahan pertama bernomor `11`, lalu `12`, `13`, dan seterusnya.
   - Pada folder tambahan, ikon pensil mengganti namanya dan ikon tempat sampah menghapusnya.
   - Untuk mengubah atau menghapus dokumen, centang dokumennya (atau centang `Select all documents`) lalu pakai `Edit` atau `Delete` di bilah yang muncul. Di `Edit` Anda bisa mengubah nama, `Folder`, dan `Document Type`, atau mengganti file.
8. `Location Information` memuat nama lokasi, tipe alamat, negara, wilayah, kota, masa berlaku, dan alamat jalan. Pilih pensil untuk mengubah.

## Aturan yang perlu diketahui

### Project Documents

- Folder standar (`01. Discover` sampai `06. Run` dan `00. Other`) ada di setiap project dan tidak bisa diganti nama atau dihapus.
- Folder tambahan mendapat nomor setelah nomor folder tambahan tertinggi yang masih ada di project, mulai dari 11. Celah di tengah tidak diisi lagi; bila folder tambahan terbaru dihapus, folder baru berikutnya memakai nomornya.
- Nama folder wajib diisi, paling banyak 100 karakter, dan harus berbeda dari semua folder lain di project, termasuk folder standar (huruf besar dan kecil dianggap sama). Nama tidak boleh memuat `\ / : * ? " < > |` dan tidak boleh diawali angka dan titik, karena nomornya ditambahkan otomatis.
- Folder hanya bisa dihapus bila tidak berisi dokumen. Pindahkan atau hapus dokumennya dulu. Foldernya di OneDrive hanya ikut dihapus bila di sana juga kosong; bila tidak, folder tetap ada di OneDrive.
- Setiap folder adalah subfolder dari folder project di OneDrive. Subfolder dibuat bersama folder project, dan paling lambat saat unggahan pertama ke folder itu.
- Memilih folder wajib saat mengunggah. Ukuran file paling besar 100 MB.
- Memindahkan dokumen ke folder lain lewat `Edit` juga memindahkan filenya di OneDrive, dan tautannya tetap sama. File pengganti diunggah ke folder yang dipilih.
- Dokumen yang diunggah sebelum ada folder berada di `00. Other`. Memindahkannya hanya mengubah folder di EcoSystem; filenya tetap di tempatnya di OneDrive.
- Dokumen pendukung expense `Plan Cost` disimpan di OneDrive pada `01. Discover` › `Plan Cost`. Folder `Plan Cost` lama yang langsung berada di folder project dipindahkan ke sana secara otomatis pada unggahan dokumen pendukung berikutnya di `Plan Cost` atau saat `Create Folder` dipakai lagi, dan tautannya tetap berfungsi.
- Mengunggah dokumen, menambah atau menghapus folder, dan menghapus dokumen memerlukan izin create or delete bagian dokumen. Mengganti nama folder dan mengubah dokumen memerlukan izin edit-nya. Project yang ditutup tidak bisa diubah.

## Apa yang terjadi selanjutnya

Menyimpan sebuah bagian langsung memperbarui project dan `Last Update`. Angka keuangan mengalir ke laporan seperti `Collection Outlook`.

## Pertanyaan umum

**Dokumen tidak bisa diunggah.** Buat folder dulu dengan `Create Folder`.

**Di mana dokumen yang diunggah sebelum ada folder?** Di `00. Other`. Pakai `Edit` untuk memindahkannya ke folder yang tepat.

**Saya tidak bisa menghapus folder.** Folder standar tidak bisa dihapus, dan folder tambahan harus kosong dulu. Pindahkan atau hapus dokumennya, lalu hapus foldernya.

**File Plan Cost tidak ketemu di OneDrive.** Sekarang ada di `01. Discover` › `Plan Cost` di dalam folder project.

**Saya bisa melihat Delivery Info tetapi tidak mengubahnya.** Anda punya `view` tetapi tidak `edit`. Minta izinnya bila pekerjaan memerlukan.

**Total termin tidak sama dengan revenue.** Baris total memberi tahu berapa yang kurang atau lebih. Sesuaikan termin atau revenue.

<!-- sources: resources/views/delivery/project/projects/show.blade.php, resources/views/delivery/project/projects/partials/documents-section.blade.php, app/Http/Controllers/DeliveryProjectController.php (storeDocumentFolder, updateDocumentFolder, destroyDocumentFolder, updateDocument, createDocumentUploadSession), app/Models/DeliveryProjectDocumentFolder.php, app/Services/ProjectDocumentFolderService.php, routes/web.php -->
