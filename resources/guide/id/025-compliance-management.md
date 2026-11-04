---
key: compliance-management
chapter: business-legal
order: 25
title: Compliance Management
summary: Tindak lanjuti laporan yang dikirim karyawan, dan kelola isi My Compliance serta pilihan yang ditawarkannya.
requires: [legal.compliance]
updated: 2026-11-04
---
`Compliance Management` punya dua fungsi. Di sini Anda membaca dan menindaklanjuti laporan yang dikirim karyawan dari [[my-compliance]], dan mengelola isi My Compliance: artikel, aturan, critical rules, dan sesi pelatihan. Tab `Settings` mengatur status yang dilalui laporan dan pilihan di dropdown.

## Siapa yang menggunakan

Tim Compliance dan administrator. Halaman ini memiliki empat tab: `Dashboard`, `Reports`, `Content`, dan `Settings`. Halaman terbuka di `Reports`. Yang dapat Anda lakukan bergantung pada akses Anda: dengan akses lihat Anda membaca dan mengekspor, dengan `Create` Anda menambah item, dengan `Edit` Anda mengubah item, menindaklanjuti laporan, dan menyalakan atau mematikan item, dan dengan `Delete` Anda menghapus item.

> [!IMPORTANT]
> Laporan bersifat rahasia. Membuka laporan, mengunduh buktinya, dan mengekspornya dicatat di riwayat akses laporan. Gunakan hanya untuk menangani laporan.

## Sebelum Anda mulai

- Laporan dari formulir Web Report publik ditangani di tempat lain, yaitu di [[web-report-management]]. Halaman ini hanya menampilkan laporan yang dikirim dari `My Compliance`.
- Tentukan langkah yang akan dilalui laporan (misalnya `New`, `In review`, `Investigating`, `Closed`) dan atur terlebih dahulu di `Settings`.

## Langkah demi langkah

### Tindak lanjuti laporan

1. Buka `Business & Legal` › `Compliance Management`. Tab `Reports` menampilkan kartu untuk setiap status dan jumlah laporan yang melewati tanggal tindak lanjut.
2. Gunakan ikon corong di header tabel untuk menyaring menurut tanggal `Received`, `Reported by` (`Employee` atau `Anonymous`), kata di `About`, `Status`, atau `Follow-up due`. Klik `Reset Filter` untuk menghapus saringan.
3. Klik `Follow up` pada sebuah laporan. Sisi kiri menampilkan siapa pelapornya, kontak, pokok laporan, ceritanya, dan bukti. Sisi kanan berisi `Handling`.
4. Pilih `Status`. Tulis `Message the reporter will see` dan, bila perlu, `Internal note`. Jangan memasukkan nama atau bukti ke dalam pesan.
5. Klik `Save`. Perubahan ditambahkan ke `Access history`.

Laporan yang mencapai status penutup tidak lagi dihitung terhadap tanggal tindak lanjut.

### Ekspor rekap

1. Di tab `Reports` klik `Export recap`.
2. Pilih `Period`: semua waktu, hari ini, kemarin, minggu ini atau lalu, bulan ini atau lalu, tahun ini, satu hari, satu bulan, satu tahun, atau rentang tanggal.
3. Pilih `Status` dan `File`: Excel (lembar rekap dan daftar) atau CSV (hanya daftar).
4. Klik `Download`. Cerita, kontak, dan catatan internal tidak pernah diekspor.

### Kelola isi

1. Buka tab `Content` dan pilih daftar di sebelah kiri: `Articles`, `Do & Don't`, `Critical rules`, atau `Training sessions`.
2. Klik `Add` untuk membuat item, atau `Edit` pada sebuah baris. Tulis artikel di editor dengan toolbarnya: judul, tebal, miring, garis bawah, coret, daftar, kutipan, dan tautan. Tanggal memakai kalender dan jam memakai kolom jam.
3. Gunakan sakelar di kolom `Visible` untuk menampilkan atau menyembunyikan item di My Compliance seketika, tanpa membukanya. `Featured` bekerja sama untuk artikel.
4. Untuk `Do & Don't` dan `Critical rules`, ubah urutan dengan tombol atas dan bawah, atau seret baris lewat pegangannya. Urutan tersimpan seketika dan menjadi urutan yang dilihat orang.

### Atur pilihan (Settings)

1. Buka tab `Settings`. Daftar di kiri berisi `Handling statuses`, dan di bawah `Content dropdowns`: `Categories`, `Rule severity`, `Critical rule colours`, `Training status`, dan `Training time of day`.
2. Di `Handling statuses`, atur `Follow-up deadline (days)`. Batas ini berlaku untuk laporan yang dikirim sesudahnya.
3. Tambahkan pilihan dengan `Add`, beri nama dan warna, dan atur apakah dapat dipilih. Sebuah status juga dapat diatur untuk menutup laporan. Pindahkan baris untuk mengubah urutannya.
4. Untuk berhenti menawarkan sebuah pilihan, matikan alih-alih menghapusnya. Item yang sudah memakainya tetap menyimpannya.

> [!WARNING]
> `New` adalah awal setiap laporan dan tidak dapat dihapus. Minimal satu status harus menutup laporan, dan satu pilihan di setiap dropdown harus tetap menyala. Pilihan yang sudah dipakai tidak dapat dihapus.

## Apa yang terjadi selanjutnya

Perubahan di `Content` dan `Settings` langsung muncul di `My Compliance` dan di dropdown. Pelapor melihat pesan yang Anda tulis dan status baru di `My reports`, atau di halaman status publik untuk laporan anonim.

## Pertanyaan umum

**Mengapa saya tidak dapat mengubah laporan?** Akses Anda memungkinkan membaca tetapi tidak mengubah. Minta administrator memberi izin `Edit`.

**Mengapa status yang saya tambahkan tidak muncul di dropdown?** Statusnya dimatikan. Nyalakan `In use` untuknya di `Settings`.

**Bisakah saya menghapus laporan?** Tidak. Tutup saja, supaya catatan dan riwayatnya tetap ada.

## Topik terkait

- [[my-compliance]]
- [[web-report-management]]
- [[roles-and-menu-access]]

<!-- sources: routes/compliance.php, app/Http/Controllers/Legal/Compliance/*, app/Services/Compliance/*, resources/views/legal/compliance/management/*, app/Models/Compliance/* -->
