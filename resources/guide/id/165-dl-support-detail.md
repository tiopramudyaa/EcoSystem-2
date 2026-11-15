---
key: dl-support-detail
chapter: delivery
order: 21
title: Detail support: General, Approval, Financial, Team, dan Customer PIC
summary: Baca dan ubah satu delivery support: informasi, approval, data penjualan dan termin pembayaran, tim, customer PIC, folder, dan ticket terkait.
requires: [delivery-support.general.view, delivery-support.approval.view, delivery-support.financial.view, delivery-support.team.view, delivery-support.customer-pic.view, delivery-support.documents.view, delivery-support.remove-ticket]
updated: 2026-11-02
---
Halaman detail support adalah satu halaman panjang, seperti detail project. Tab di atas (`General`, `Approval`, `Financial`, `Team`, `Customer PIC`, `Activities`, `SLA`, `Plan Cost`, `Recons`) menggulir ke sebuah bagian. Topik ini membahas lima yang pertama dan tombol di bagian atas.

## Siapa yang menggunakan

Support manager, helpdesk, support head, dan tim support. Setiap bagian punya izin `view`, `edit`, dan `create or delete` sendiri di Menu Access, sehingga bagian atau tombol yang tidak boleh Anda pakai disembunyikan.

## Sebelum Anda mulai

- Sebuah ticket hanya boleh milik satu delivery support. Memasukkannya ke support lain memindahkannya dari yang pertama.
- Customer PIC menentukan apa yang dilihat customer di portal customer (JARVIES), jadi jaga tetap akurat.

## Langkah demi langkah

![Halaman Support Details dengan tombol judul dan kartu Support Information](images/guide/dlv-support-detail.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Delivery` › `Support` lalu klik sebuah item. Judul menampilkan customer, tipe, dan progresnya, dengan tombol `Edit Support`, `Open Planning`, `Deliverable Folder`, `Documents`, `Updates & Notes`, `Remove Ticket`, dan `Delete Support`.
2. `Support Information` menampilkan nama, client, tipe, metode, module, tanggal mulai dan selesai, estimasi, total mandays, dan service window, dengan progres keseluruhan di kanan. Pilih pensil atau `Edit Support` untuk mengubah.
3. `Approval Information` menampilkan tanggal approval dan siapa yang menyetujui (`Not approved yet` sampai diisi).

![Kartu Financial Information dengan data penjualan dan tabel termin pembayaran](images/guide/dlv-support-financial.png)

*Tangkapan layar dengan data contoh.*

4. `Financial Information` memuat `Sales Data`: nomor IO (unik), revenue, plan cost, gross profit dan persentasenya, angka aktual, dan vendor opsional. Pilih `TOP Type` (`% of Revenue` atau `Contract Line Item`) dan pakai `Add Payment Term` untuk menyusun `Term Of Payment Plan`, seperti pada project.

![Kartu Team dan Customer PIC sebuah delivery support](images/guide/dlv-support-team.png)

*Tangkapan layar dengan data contoh.*

5. `Team` menampilkan `Delivery Owner`, `Support Manager`, `Co PM`, `Support Admin`, dan `Sales`. Pilih pensil untuk mengubahnya.
6. Di `Customer PIC` pilih `Manage Customer PIC` dan pilih kontak customer untuk support ini. Hanya mereka yang melihat ticket support ini di JARVIES.
7. `Deliverable Folder` memberi akses ke folder OneDrive untuk deliverable. `Documents` dan `Updates & Notes` menampilkan jumlah berkas dan catatan support.
8. Untuk mengeluarkan ticket dari support, pilih `Remove Ticket`, pilih ticketnya di jendela, lalu konfirmasi.
9. `Delete Support` menghapus support selamanya setelah konfirmasi.

## Apa yang terjadi selanjutnya

Perubahan berlaku seketika. Mengeluarkan ticket membuatnya bebas ditugaskan ke support lain. Kontak customer yang ditambahkan sebagai PIC melihat ticket support itu saat login berikutnya.

## Pertanyaan umum

**Customer tidak melihat ticketnya di JARVIES.** Periksa bahwa orang itu ada di `Customer PIC` pada support tempat ticket berada.

**Saya tidak bisa menghapus support.** Anda mungkin tidak punya `Delete Delivery Support`, atau support masih punya data terkait.

<!-- sources: routes/delivery-support.php, resources/views/delivery/support/list/show.blade.php -->
