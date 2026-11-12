# Reset 2FA darurat dari server

Untuk saat seorang karyawan kehilangan ponsel **dan** kode pemulihan, dan tombol `Reset 2FA`
di `Master` › `Employee` tidak bisa dipakai. Kasus utamanya: **satu-satunya EC Administrator**
yang terkunci, sehingga tidak ada admin lain yang bisa mereset.

Kalau masih ada EC Administrator lain, pakai tombol itu (lihat topik Employee di User Guide).
Perintah ini hanya jalur darurat.

## Menjalankan

Di server aplikasi, dari folder proyek:

```
php artisan security:reset-two-factor <ECI> --reason="alasan reset"
```

Contoh:

```
php artisan security:reset-two-factor ECI001 --reason="Satu-satunya admin kehilangan ponsel, tiket IT 123"
```

Perintah menampilkan ECI, email, dan alasan, lalu meminta Anda mengetik ulang ECI untuk
konfirmasi. Kalau ECI yang diketik berbeda, tidak ada yang berubah.

| Opsi | Fungsi |
|---|---|
| `--reason="..."` | Alasan reset. **Wajib.** Masuk Audit Log. Kalau dikosongkan di shell interaktif, perintah menanyakannya. |
| `--unlock` | Sekaligus membuka kunci akun yang terkunci karena kode 2FA salah berulang kali (kunci 30 menit). Berguna karena orang yang kehilangan kode biasanya sudah mencoba beberapa kali. |
| `--force` | Melewati konfirmasi mengetik ulang ECI. Untuk skrip. Di shell non-interaktif opsi ini wajib. |

## Yang terjadi

- Kunci 2FA, kode pemulihan, dan browser tepercaya karyawan itu dihapus. Ia masuk hanya dengan
  password. Jika rolenya mewajibkan 2FA, ia diminta menyiapkannya lagi setelah masuk.
- Tercatat di `Control Center` › `Audit Log`: `reset two-factor authentication of <nama> (by server console on <host>): <alasan>`.
  Juga ditulis ke log aplikasi.
- Karyawannya diberi tahu lewat notifikasi aplikasi dan email, supaya reset yang tidak ia
  minta ketahuan. Kalau email gagal terkirim, reset tetap berlaku dan perintah memberi tahu Anda.

## Keamanan

Batas kepercayaannya adalah akses ke server: siapa pun yang bisa menjalankan perintah ini memang
sudah bisa mengubah database. Karena itu perintah ini sengaja tidak punya jalur lewat web.

Praktik yang disarankan: punya **minimal dua EC Administrator**, supaya jalur darurat ini jarang
dibutuhkan.

## Kode

- Perintah: `app/Console/Commands/ResetTwoFactor.php`
- Logika bersama (dipakai juga tombol `Reset 2FA`): `app/Services/TwoFactorResetService.php`
