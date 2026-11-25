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

1. Buka `Management` › `Role`. Tiga kartu di bagian atas menunjukkan total role, total penugasan role, dan jumlah role yang belum punya anggota. Pakai kotak pencarian untuk mencari role dari nama atau deskripsi; hitungan di sebelahnya menunjukkan berapa dari seluruh role yang sedang tampil.
2. Pilih `Add Role`, ketik `Name` (wajib, harus baru) dan deskripsi bila perlu, lalu `Save`. Untuk mengubah role yang ada, pilih tombol pensil pada barisnya (tooltip `Edit role`).
3. Baca lencana `Members`: jumlah orang yang memegang role itu. Klik untuk membuka jendela berisi nama dan nomor ECI mereka. Bila sebuah role punya lebih dari 8 anggota, kotak pencarian muncul di jendela itu.

### Pilih menu sebuah role

![Jendela Menu Access sebuah role dengan sakelar untuk setiap menu](images/guide/mgmt-role-menu-access.png)

*Tangkapan layar dengan data contoh.*

1. Pada role, pilih `Menu Access`. Jendelanya menampilkan semua menu sebagai pohon. Di bagian atas, sebuah bar menunjukkan berapa menu yang sudah diberikan ke role. Pakai kotak pencarian untuk menemukan menu, tombol `All`, `Granted`, dan `Not granted` untuk menyaring daftar, serta `Expand all` atau `Collapse all` untuk membuka atau menutup pohon. Sebuah group menampilkan hitungan seperti 3/5, yaitu jumlah sub-itemnya yang sudah diberikan.
2. Nyalakan sakelar sebuah menu untuk memberikannya. EcoSystem bertanya `Grant Access?`: pilih `Yes, Grant`. Bila menu punya sub-item, semuanya ikut diberikan.
3. Matikan sakelarnya untuk mencabut menu. EcoSystem bertanya `Revoke Access?`: pilih `Yes, Revoke`. Sub-itemnya ikut dicabut.
4. Sub-item berwarna abu-abu selama menu induknya mati. Nyalakan induknya dulu.
5. Setiap sakelar langsung tersimpan. Tidak ada tombol simpan terpisah.

### Hapus role

Pilih tombol tempat sampah pada role (tooltip `Delete role`) lalu konfirmasi. Bila masih ada pemegangnya, EcoSystem menolak dan menyebut jumlahnya.

## Aturan yang perlu diketahui

- **Siapa yang boleh mengubah role.** Hanya orang yang diberi menu `Management` › `Role` di Menu Access. Sistem memeriksanya pada setiap perubahan, bukan hanya dengan menyembunyikan menu. Orang tanpa menu ini ditolak, walaupun tahu cara mengirim permintaannya langsung.
- **Nama role.** Nama wajib diisi, paling banyak 100 karakter, dan tidak boleh sama dengan role lain. Deskripsi boleh kosong, paling banyak 255 karakter.
- **Role yang masih dipakai tidak bisa dihapus.** Jika masih ada orang yang memegangnya, EcoSystem menolak dan menampilkan jumlahnya. Cabut role itu dulu dari orangnya di `Master` › `Employee`.
- **Pemberian hanya berupa hak melihat.** Sakelar di `Menu Access` memberi hak melihat halaman atau tombol. Hak buat, ubah, dan hapus tidak dinyalakan dari jendela ini.
- **Sub-item mengikuti induknya.** Menyalakan sebuah menu ikut menyalakan semua sub-itemnya. Mematikannya juga mencabut sub-item. Sub-item berwarna abu-abu selama induknya mati.
- **Beberapa role dijumlahkan.** Orang dengan lebih dari satu role mendapat semua menu yang diizinkan salah satu rolenya. Mencabut menu dari satu role tidak menghilangkannya bila role lain milik orang itu masih memberikannya.
- **Menu yang tidak aktif tidak pernah terbuka**, meskipun sebuah role sudah diberi menu itu.
- **Setiap perubahan dicatat** di `Control Center` › `Audit Log` pada modul `Role Management`, lengkap dengan nilai sebelum dan sesudahnya.

## Apa yang terjadi selanjutnya

Pemegang role mendapat menu baru saat membuka halaman berikutnya. Setiap perubahan tercatat di `Control Center` › `Audit Log` pada modul `Role Management`.

## Pertanyaan umum

**Seseorang tidak melihat menu yang sudah saya berikan.** Minta mereka memuat ulang. Periksa juga bahwa menu induknya menyala, dan bahwa orang itu benar memegang role ini di `Master` › `Employee`.

**Saya perlu nama atau slug menu yang tepat.** Buka `Management` › `Menu Access`, yang mendaftar semuanya.

**Bagaimana menutup jendela tanpa menyimpan?** Pilih X, tekan `Esc`, atau klik di luar jendela. Tidak ada yang berubah.

<!-- sources: resources/views/management/roles/index.blade.php, app/Http/Controllers/RoleController.php -->
