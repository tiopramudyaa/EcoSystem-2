---
key: mg-role
chapter: management
order: 10
title: Role
summary: Buat dan ubah role (Role Management), lihat siapa pemegang tiap role, dan tentukan menu apa yang dapat dibuka sebuah role.
requires: [management.roles]
updated: 2026-11-02
---
`Management` › `Role` (halamannya bernama Role Management) adalah tempat role EcoSystem dikelola. Role adalah sekumpulan menu: semua pemegang role mendapat menu itu persis.

## Siapa yang menggunakan

Administrator, dan role mana pun yang diberi menu `Management` › `Role` di Menu Access.

> [!IMPORTANT]
> Akses menu menentukan siapa yang dapat melihat data customer, karyawan, dan keuangan. Ubah hanya bila Anda tahu alasannya, dan periksa hasilnya sesudahnya.

## Sebelum Anda mulai

- Role tidak bisa dihapus selama masih ada karyawan yang memegangnya. Pindahkan mereka ke role lain dulu.
- Memberi menu berarti memberi akses melihat. Tombol di dalam halaman (buat, setujui, ekspor) adalah menu `function` tersendiri di bawah halamannya, ditandai ikon petir di `Menu Access`.
- Untuk memberi role kepada seseorang, pakai `Master` › `Employee`. Role tidak diberikan dari halaman ini.

## Langkah demi langkah

![Daftar Role Management dengan Menu Access, Edit, dan Delete pada setiap role](images/guide/mgmt-roles.png)

*Tangkapan layar dengan data contoh.*

### Tambah atau ubah role

1. Buka `Management` › `Role`. Pakai kotak pencarian untuk mencari role dari nama atau deskripsi.
2. Pilih `Add Role`, ketik `Name` (wajib, harus baru) dan deskripsi bila perlu, lalu `Save`. Untuk mengubah role yang ada, pilih `Edit`.
3. Baca lencana `Members`: jumlah orang yang memegang role itu. Klik untuk melihat namanya.

### Pilih menu sebuah role

![Jendela Menu Access sebuah role dengan kotak centang untuk setiap menu](images/guide/mgmt-role-menu-access.png)

*Tangkapan layar dengan data contoh.*

1. Pada role, pilih `Menu Access`. Jendelanya menampilkan semua menu sebagai pohon. Pakai kotak pencarian untuk menemukan satu menu.
2. Centang menu untuk memberikannya. EcoSystem bertanya `Grant Access?`: pilih `Yes, Grant`. Bila menu punya sub-item, semuanya ikut diberikan.
3. Hapus centang untuk mencabutnya. EcoSystem bertanya `Revoke Access?`: pilih `Yes, Revoke`. Sub-itemnya ikut dicabut.
4. Sub-item berwarna abu-abu selama menu induknya mati. Nyalakan induknya dulu.
5. Setiap centang langsung tersimpan. Tidak ada tombol simpan terpisah.

### Hapus role

Pilih `Delete` pada role lalu konfirmasi. Bila masih ada pemegangnya, EcoSystem menolak dan menyebut jumlahnya.

## Apa yang terjadi selanjutnya

Pemegang role mendapat menu baru saat membuka halaman berikutnya. Setiap perubahan tercatat di `Control Center` › `Audit Log` pada modul `Role Management`.

## Pertanyaan umum

**Seseorang tidak melihat menu yang sudah saya berikan.** Minta mereka memuat ulang. Periksa juga bahwa menu induknya menyala, dan bahwa orang itu benar memegang role ini di `Master` › `Employee`.

**Saya perlu nama atau slug menu yang tepat.** Buka `Management` › `Menu Access`, yang mendaftar semuanya.

<!-- sources: resources/views/management/roles/index.blade.php, app/Http/Controllers/RoleController.php -->
