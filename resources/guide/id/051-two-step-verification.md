---
key: two-step-verification
chapter: getting-started
order: 51
title: Verifikasi 2 langkah
summary: Cara mengaktifkan verifikasi 2 langkah, memasukkan kode saat masuk, memakai kode pemulihan, dan mengelolanya di Settings.
requires: []
updated: 2026-11-12
---
Verifikasi 2 langkah menambahkan kode dari ponsel Anda saat masuk, sehingga password yang dicuri saja tidak cukup. Topik ini menjelaskan cara mengaktifkannya, cara memasukkan kode saat masuk, dan apa yang dilakukan jika ponsel Anda hilang. Cara masuk dan mengganti password ada di [[sign-in-and-security]].

## Siapa yang menggunakan

Semua orang yang masuk ke EcoSystem. Bagi kebanyakan orang ini bersifat pilihan, dan administrator dapat mewajibkannya untuk sebuah role. EC Administrator wajib secara bawaan.

## Sebelum Anda mulai

- Pasang aplikasi authenticator di ponsel Anda, misalnya Google Authenticator atau Authy.
- Siapkan tempat yang hanya dapat Anda buka untuk menyimpan kode pemulihan.

## Langkah demi langkah

### Memasukkan kode verifikasi

Jika verifikasi 2 langkah aktif untuk akun Anda, EcoSystem meminta kode setelah password atau proses masuk Microsoft.

![Halaman Two-Factor Verification yang meminta kode 6 angka dari aplikasi authenticator](images/guide/sign-in-2fa-code.png)

*Tangkapan layar dengan data contoh.*

1. Buka aplikasi authenticator Anda dan baca kode 6 angka untuk EcoSystem.
2. Ketik di kotak lalu pilih `Verify`.

Kode berganti setiap 30 detik, dan setiap kode hanya berlaku sekali. Jika ditolak, tunggu kode berikutnya lalu coba lagi.

Jika ponsel Anda hilang, pilih `Use a recovery code instead` lalu masukkan salah satu kode pemulihan yang Anda simpan saat mengaktifkan verifikasi 2 langkah.

![Halaman yang sama dalam mode kode pemulihan, meminta kode pemulihan berbentuk XXXX-XXXX](images/guide/sign-in-2fa-recovery.png)

*Tangkapan layar dengan data contoh.*

Setiap kode pemulihan hanya berlaku sekali. Pilih `Use an authenticator code instead` untuk kembali, atau `Back to login` untuk mengulang dari awal.

### Mengaktifkan verifikasi 2 langkah

Verifikasi 2 langkah menambahkan kode dari ponsel Anda saat masuk, sehingga password yang dicuri saja tidak cukup.

1. Di tab `Security`, temukan `Two-factor authentication`. Statusnya `Not configured` sampai Anda menyiapkannya.
2. Pilih `Enable 2FA`.
3. Pindai kode QR dengan aplikasi authenticator Anda. Jika tidak bisa memindai, ketik kunci yang tampil di bawah `Or enter this key manually`.
4. Ketik kode 6 angka yang ditampilkan aplikasi ke kotak lalu pilih `Confirm`.

![Penyiapan two-factor dengan kode QR, kunci manual, kotak kode, serta tombol Confirm dan Cancel](images/guide/settings-2fa-setup.png)

*Tangkapan layar dengan data contoh. Kode QR dan kunci disembunyikan di gambar ini dengan sengaja. Milik Anda menampilkan nilai asli, jadi jangan pernah membagikannya.*

5. EcoSystem menampilkan 8 kode pemulihan. Simpan di tempat aman yang hanya dapat Anda buka, centang `I've saved these recovery codes somewhere safe`, lalu pilih `Done`.

![Jendela Save your recovery codes dengan delapan kode, kotak centang konfirmasi, dan tombol Done](images/guide/settings-2fa-recovery-codes.png)

*Tangkapan layar dengan data contoh. Kode disembunyikan di gambar ini dengan sengaja.*

Kode pemulihan hanya ditampilkan satu kali. Tombol `Done` tetap abu-abu sampai kotak dicentang.

### Mengelola verifikasi 2 langkah

Saat aktif, bagian itu menampilkan `Enabled`.

![Bagian Two-factor authentication yang menampilkan Enabled dengan tombol Regenerate recovery codes dan Disable](images/guide/settings-2fa-enabled.png)

*Tangkapan layar dengan data contoh.*

- `Regenerate recovery codes` membuat 8 kode baru. Konfirmasi, lalu masukkan password Anda saat ini. Kode lama langsung tidak berlaku, jadi simpan kode yang baru.
- `Disable` mematikan verifikasi 2 langkah. Konfirmasi, lalu masukkan password Anda saat ini.

## Aturan yang perlu diketahui

- Verifikasi 2 langkah bersifat pilihan untuk kebanyakan orang. Administrator dapat mewajibkannya untuk sebuah role, dan EC Administrator wajib secara bawaan. Jika wajib untuk Anda dan belum Anda siapkan, setiap halaman mengarahkan Anda ke `Settings` sampai selesai. Jika Anda mematikannya lagi, Anda diminta menyiapkannya lagi.
- Verifikasi belum aktif sampai Anda mengonfirmasi kode yang benar. Jika Anda memilih `Cancel` atau meninggalkan halaman sebelum itu, statusnya tetap mati. Memilih `Enable 2FA` lagi membuat kunci baru dan kunci lama yang belum dikonfirmasi dibuang.
- Kode dari aplikasi authenticator hanya berlaku sekali. Kode yang baru saja dipakai ditolak, jadi tunggu kode berikutnya.
- Anda mendapat 8 kode pemulihan yang ditampilkan satu kali. Masing-masing hanya berlaku sekali. Membuat ulang mengganti seluruh set dan kode lama tidak berlaku lagi.
- `Regenerate recovery codes` dan `Disable` selalu meminta password Anda saat ini.
- Setelah password atau proses masuk Microsoft, Anda punya 5 menit untuk memasukkan kode. Lewat dari itu, masuk lagi dari awal.
- Setelah 5 kode salah berturut-turut, akun dikunci selama 30 menit dan administrator diberi peringatan.
- Saat Anda memasukkan kode yang benar, browser itu dipercaya untuk waktu terbatas (biasanya 2 jam, sama dengan lama sebuah sesi). Selama itu Anda tidak diminta kode lagi di browser tersebut. Waktunya mulai dari awal hanya saat Anda memasukkan kode baru. Mematikan atau mengaktifkan ulang verifikasi 2 langkah menghapus kepercayaan ini.
- `Keep me signed in` diabaikan untuk akun yang memakai verifikasi 2 langkah. Setelah sesi berakhir, Anda masuk lagi dengan kode.
- EC Administrator dapat mereset verifikasi 2 langkah orang lain yang kehilangan ponsel dan kode pemulihannya. Reset menghapus kunci dan kode pemulihan, orangnya diberi tahu lewat email dan di aplikasi, dan reset tercatat di Audit Log. Tidak ada yang dapat mereset miliknya sendiri dengan cara ini.
- Mengaktifkan atau mematikan verifikasi 2 langkah, serta membuat ulang kode pemulihan, tercatat di Audit Log. Kunci dan kode tidak pernah dicatat di sana.
- Masuk dengan Microsoft tidak melewati verifikasi 2 langkah. Kode juga diminta setelah masuk Microsoft.

## Apa yang terjadi selanjutnya

Setelah diaktifkan, proses masuk meminta kode, baik dengan password maupun dengan Microsoft, kecuali di browser yang baru saja Anda verifikasi. Simpan kode pemulihan dengan aman, karena itu satu-satunya jalan kembali jika ponsel Anda hilang.

## Pertanyaan umum

**Saya tidak menerima kode verifikasi.** Kode berasal dari aplikasi authenticator, bukan dari email atau SMS. Periksa bahwa jam di ponsel Anda sudah benar, lalu coba kode berikutnya.

**Ponsel saya hilang.** Masuk dengan password, pilih `Use a recovery code instead` lalu masukkan satu kode pemulihan. Setelah itu matikan dan aktifkan lagi verifikasi 2 langkah untuk menghubungkan ponsel baru, lalu simpan kode pemulihan yang baru.

**Ponsel dan kode pemulihan saya hilang.** Anda tidak dapat memperbaikinya sendiri. Minta EC Administrator mereset verifikasi 2 langkah Anda. Anda mendapat email dan pemberitahuan di aplikasi, lalu masuk hanya dengan password dan menyiapkannya lagi.

<!-- sources: resources/views/auth/two-factor-challenge.blade.php, resources/views/settings/index.blade.php, routes/web.php, app/Http/Controllers/TwoFactorController.php, app/Http/Controllers/AuthController.php, app/Services/TwoFactorAuthService.php, app/Http/Middleware/EnforceTwoFactorForAdmins.php, config/security_center.php -->
