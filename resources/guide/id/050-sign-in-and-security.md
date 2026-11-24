---
key: sign-in-and-security
chapter: getting-started
order: 50
title: Masuk dan keamanan akun
summary: Cara masuk dengan password atau Microsoft, mengganti password, dan menjaga akun tetap aman.
requires: []
updated: 2026-11-15
---
Topik ini menjelaskan cara masuk ke EcoSystem dengan password atau akun kerja Microsoft Anda, tempat mengganti password, dan kebiasaan yang menjaga akun Anda tetap aman.

## Siapa yang menggunakan

Semua orang yang masuk ke EcoSystem.

## Sebelum Anda mulai

- Siapkan email atau employee ID dan password Anda sendiri. Keduanya hanya milik Anda.
- Untuk masuk dengan Microsoft, Anda perlu akun kerja Microsoft 365 perusahaan, dan login Microsoft harus sudah diaktifkan oleh EC Administrator.

## Langkah demi langkah

### Masuk dengan password

![Halaman masuk EcoSystem dengan kolom email atau employee ID, kolom password, dan tombol Sign In](images/guide/sign-in-login.png)

*Tangkapan layar dengan data contoh.*

1. Buka alamat EcoSystem yang diberikan perusahaan Anda.
2. Isi `Email or Employee ID` dan `Password` Anda.
3. Centang `Keep me signed in` hanya di komputer yang hanya Anda pakai.
4. Pilih `Sign In`.
5. Jika akun Anda memakai verifikasi 2 langkah, masukkan kode Anda di halaman berikutnya. Langkahnya ada di [[two-step-verification]].

### Masuk dengan Microsoft

Jika perusahaan Anda sudah mengaktifkannya, halaman masuk menampilkan `Sign in with Microsoft` di bawah tombol `Sign In`. Ini cara masuk tambahan. Password Anda tetap bisa dipakai.

![Halaman masuk dengan tombol Sign in with Microsoft di bawah tombol Sign In](images/guide/sign-in-microsoft.png)

*Tangkapan layar dengan data contoh.*

1. Di halaman masuk, pilih `Sign in with Microsoft`.
2. Pilih akun kerja Anda di halaman Microsoft lalu selesaikan proses masuk Microsoft.
3. Anda kembali ke EcoSystem. Jika akun Anda memakai verifikasi 2 langkah, masukkan kode Anda seperti biasa (lihat [[two-step-verification]]).

Pada pertama kali, EcoSystem mencocokkan email kerja Microsoft Anda dengan email akun EcoSystem Anda lalu mengingat sambungannya. Jika emailnya tidak cocok, Anda belum bisa masuk dengan cara ini sampai administrator memperbaiki email Anda, atau sampai Anda menyambungkan akun sendiri (lihat "Menyambungkan atau memutus akun Microsoft").

### Jika Anda lupa password

Pilih `Forgot password?` di halaman masuk. Langkahnya ada di [[forgot-password]].

### Mengganti password

![Tab Security di Settings dengan formulir password, Microsoft account, two-factor authentication, dan active sessions](images/guide/settings-security.png)

*Tangkapan layar dengan data contoh.*

1. Buka `Settings` lalu pilih tab `Security`.
2. Pada bagian `Password`, isi password baru (minimal 8 karakter) lalu ulangi di `Confirm new password`.
3. Pilih `Update password`.

### Menyambungkan atau memutus akun Microsoft

Sambungkan akun Microsoft Anda satu kali, lalu Anda bisa masuk dengannya setelah itu.

1. Buka `Settings` lalu pilih tab `Security`.
2. Di bagian `Microsoft account`, pilih `Connect Microsoft account` lalu masuk dengan Microsoft.

![Bagian Microsoft account yang menampilkan Not connected dan tombol Connect Microsoft account](images/guide/settings-microsoft-account.png)

*Tangkapan layar dengan data contoh.*

Jika berhasil, bagian itu menampilkan `Connected to Microsoft` dengan email Microsoft Anda dan `Active`.

![Bagian Microsoft account yang menampilkan Connected to Microsoft, email Microsoft, Active, dan tombol Disconnect](images/guide/settings-microsoft-connected.png)

*Tangkapan layar dengan data contoh.*

Untuk menghapus sambungan, pilih `Disconnect` lalu konfirmasi. Anda tetap bisa masuk dengan password.

Jika bagian itu berisi `Sign in with Microsoft is not enabled yet`, berarti EC Administrator belum mengaktifkannya.

### Memeriksa tempat Anda masuk

Bagian `Active sessions` di tab `Security` mendaftar sesi akun Anda. Baris bertanda `Current session` adalah browser yang sedang Anda pakai.

> [!IMPORTANT]
> Jangan pernah membagikan password, kode verifikasi, atau kode pemulihan kepada siapa pun, termasuk rekan kerja, atasan, atau orang yang mengaku dari tim IT. Jangan menuliskannya di chat, email, atau catatan ticket.

> [!TIP]
> Keluar (sign out) bila Anda meninggalkan komputer bersama.

## Aturan yang perlu diketahui

- Masuk dengan Microsoft adalah pilihan tambahan. Masuk dengan password tetap tersedia dan tidak berubah.
- Hanya EC Administrator yang dapat mengaktifkan atau mengubah pengaturannya. Sebelum itu fitur ini mati. Tombolnya muncul di halaman masuk hanya setelah fitur diaktifkan.
- Hanya akun dari organisasi Microsoft yang dikonfigurasi yang dapat masuk dengan cara ini.
- EcoSystem tidak pernah membuat akun dari proses masuk Microsoft. Akun Microsoft harus milik akun karyawan EcoSystem yang sudah ada dan aktif.
- Pada pertama kali, email kerja Microsoft dicocokkan dengan email akun EcoSystem. Sesudah itu sambungannya memakai akun Microsoft itu sendiri, sehingga email yang berubah tidak memutusnya. Akun EcoSystem yang sudah tersambung ke satu akun Microsoft tidak dapat diambil alih oleh akun Microsoft lain.
- Satu akun Microsoft hanya dapat tersambung ke satu pengguna EcoSystem, dan satu pengguna EcoSystem hanya ke satu akun Microsoft.
- Pemeriksaan biasa tetap berlaku sesudah masuk Microsoft: akun harus aktif dan sudah menyelesaikan penyiapan password pertama, harus punya akses ke EcoSystem, dan tidak dapat masuk saat sistem dikunci atau saat akun terkunci karena terlalu banyak percobaan gagal. Customer tidak dapat memakai EcoSystem dan diarahkan ke portal customer.
- Verifikasi 2 langkah tidak dilewati. Jika aktif untuk akun Anda, kode diminta setelah masuk Microsoft, sama seperti setelah password.
- `Keep me signed in` tidak berlaku untuk masuk Microsoft. Sesi Anda berakhir seperti sesi biasa.
- Proses masuk Microsoft harus selesai dalam 10 menit setelah Anda memilih `Sign in with Microsoft`. Lewat dari itu, mulai lagi.
- Menyambungkan dan memutus akun Microsoft tercatat di Audit Log.

## Apa yang terjadi selanjutnya

Percobaan masuk yang gagal berulang kali akan mengunci akun untuk sementara demi melindunginya. Tunggu kunci berakhir, atau hubungi administrator jika Anda masih terblokir.

## Pertanyaan umum

**Tombol Sign in with Microsoft tidak ada di halaman masuk saya.** Login Microsoft belum diaktifkan. Hubungi EC Administrator.

**Muncul pesan bahwa akun Microsoft saya tidak dapat masuk.** Email akun kerja Microsoft Anda tidak cocok dengan akun EcoSystem yang aktif. Masuk dengan password, sambungkan akun Microsoft Anda di `Settings`, atau minta administrator memeriksa email Anda.

**Saya curiga akun saya dipakai orang lain.** Segera ganti password dan beri tahu administrator.

<!-- sources: resources/views/auth/login.blade.php, resources/views/settings/index.blade.php, routes/web.php, app/Http/Controllers/MicrosoftAuthController.php, app/Http/Controllers/AuthController.php, app/Support/MicrosoftLoginSettings.php, app/Support/MicrosoftIdToken.php -->
