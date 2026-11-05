---
key: legal-basis
chapter: business-legal
order: 10
title: Legal Basis
summary: Kelola daftar undang-undang dan peraturan, serta angka yang dapat dibaca Offboarding, Payroll, dan Overtime dari sana.
requires: [legal, finance.legal-basis, finance.legal-basis.settings]
updated: 2026-11-05
---
Legal Basis adalah daftar undang-undang dan peraturan di balik aturan perhitungan di EcoSystem: PPh 21, BPJS, Overtime, Contract (PKWT), dan Offboarding. Setiap aturan juga menyimpan angka yang dipakainya, sehingga sebagian modul dapat membaca tarifnya dari sini.

## Siapa yang menggunakan

Peninjau dari Legal, HR, dan Finance, serta administrator. Halaman ini memiliki dua tab: `Register` dan `Settings`. Anda hanya melihat tab yang diizinkan untuk Role Anda.

## Sebelum Anda mulai

- Tentukan siapa yang meninjau aturan dan siapa pemilik tiap aturan.
- Isi awal register ditulis dari sumber sekunder. Seseorang wajib memeriksanya terhadap teks resmi sebelum mengandalkannya, terutama tabel TER, PTKP, dan batas upah BPJS.

## Langkah demi langkah

### Membaca register

1. Buka `Business & Legal` › `Legal Basis` › `Register`.
2. Saring berdasarkan modul bila Anda hanya ingin satu bidang, misalnya BPJS.
3. Baca kolom `Regulation`, `In force since`, `Amended by`, `Status`, dan `Your confidence`. Gunakan tautan sumber untuk membuka teks resminya.

Satu baris adalah satu versi aturan. Statusnya `Needs review` (kuning), `Reviewed` (hijau, beserta siapa yang meninjau dan kapan), atau `Superseded` (versi lama yang tidak dipakai lagi).

### Menambah aturan atau versi baru

1. Klik `Add rule or new version`.
2. Isi detail peraturan, `In force since`, `Your confidence`, dan `Owner (responsible person)` yang dipilih dari karyawan aktif. Tambahkan tautan sumber dan catatan bila ada.
3. Isi `Calculation values`. Isiannya berupa kolom berlabel, serta tabel dengan `+ Add a row` dan tombol ✕. Ketik rupiah sebagai 60.000.000 atau 60000000, dan persen sebagai persen (5 berarti 5%). Koma maupun titik dapat dipakai untuk desimal. Kosongkan kolom "No limit". Huruf ditolak dengan pesan "Use numbers only".
4. Simpan. Entri ditambahkan dengan status `Needs review`.

Tanggal berlaku baru membuat versi baru. Versi lama tetap ada di riwayat, dan sistem memakai versi yang berlaku pada tanggal perhitungan. Gunakan `Supersede` untuk menghentikan sebuah versi dan `Restore` untuk mengembalikannya.

> [!IMPORTANT]
> Mengubah isi entri mengembalikannya ke `Needs review`. Mengubah hanya `Owner` tidak mengubah status.

### Meninjau perubahan

1. Pada versi yang memiliki versi lebih lama, klik `Changes`.
2. Bandingkan `Previous version` (merah) dengan `This version` (hijau): detail peraturan lebih dulu, lalu nilai perhitungan.
3. Cocokkan dengan teks resmi, lalu klik `Mark reviewed`. Klik `Needs review` untuk mengembalikan entri.

### Pemilik dan pengingat ulasan

Ulasan berlaku 12 bulan. Kartu ringkasan `Review overdue (12+ months)` menghitung aturan yang terlambat, dengan keterangan untuk yang jatuh tempo dalam 30 hari. Baris menampilkan `Review due in N days` (kuning) atau `Review overdue by N days` (merah). Pada hari kerja pukul 08:15, pemilik menerima notifikasi bila ulasan jatuh tempo dalam 30 hari atau terlambat, atau bila aturan menunggu di `Needs review` lebih dari 30 hari. Sebuah aturan mendapat paling banyak satu pengingat setiap 7 hari. Aturan tanpa pemilik tidak diingatkan, tetapi tetap ditandai di halaman.

### Memilih siapa yang membaca angka (Settings)

Di tab `Settings`, daftar kesiapan menunjukkan aturan yang belum memiliki nilai.

- **Offboarding.** Final Settlement membaca empat aturan: pengali menurut alasan keluar, tabel pesangon dan UPMK, pajak pesangon, dan kompensasi PKWT. Pembagi uang cuti diambil dari `Offboarding Settings`.
- **Payroll.** Pada kartu `Where Payroll reads its rates`, pilih `Payroll Settings` (bawaan) atau `Legal Basis` untuk tarif PPh 21 (tarif progresif, biaya jabatan, PTKP, TER) dan tarif serta batas upah BPJS. Pilihan perusahaan tetap di `Payroll Settings`: tahun aktif, kelas JKK, saklar BPJS dan PPh 21, premi pemberi kerja, jadwal, dan pembulatan.
- **Overtime.** Pada kartu `Overtime multipliers`, pilih `Off — built-in standard` (bawaan: PP 35/2021, pembagi 173) atau `On — read from Legal Basis`. Perusahaan yang belum memakai Overtime cukup membiarkannya Off. Lembur hanya dibayar bila dinyalakan di `Settings` Payroll.

Klik `Save source` atau `Save overtime source`. Bila kedua sumber memberi angka berbeda, daftar selisih muncul dan Anda mencentang konfirmasi, karena hasil gaji berubah. Kembali ke sumber lama selalu boleh. Bila aturan belum lengkap, peralihan ditolak disertai penjelasan. Selama sumbernya `Legal Basis`, halaman pengaturan PPh 21 dan BPJS menampilkan spanduk bahwa angkanya dikelola di sini, dan perubahan PTKP, tarif progresif, atau TER di sana ditolak.

Warna kuning pada kotak `Calculation values` berarti dibaca kalkulator. Biru berarti hanya salinan referensi. Modul lain, seperti Contract, memakai register sebagai rujukan.

Tabel `Where the legal basis applies` mengatur per modul `Show panel`, `Warn`, dan mode: `Draft mode` atau `Enforced`. Klik `Save switches`.

> [!WARNING]
> `Enforced` dapat menghentikan sebuah modul sampai aturannya `Reviewed`. Periksa dulu kolom aturan yang tertunda.

## Apa yang terjadi selanjutnya

Bila diaktifkan, Payroll dan halaman lain menampilkan panel dasar hukum atau peringatan tentang aturan yang belum ditinjau. Saat peraturan berubah, tambahkan versi baru dengan tanggal dan nilai baru, periksa dengan `Changes`, lalu klik `Mark reviewed`. Jika Payroll membaca dari `Legal Basis`, hanya itu yang perlu Anda lakukan. Jika membaca dari `Payroll Settings`, ubah juga angkanya di sana.

## Pertanyaan umum

**Apakah mengubah register mengubah nominal payroll?** Hanya bila modul membaca dari register: Offboarding, serta Payroll atau Overtime jika Anda memilih Legal Basis di `Settings`. Selain itu, register hanya catatan.

**Bisakah saya kembali ke sumber lama?** Bisa, kapan saja.

## Topik terkait

- [[payroll]]
- [[bpjs]]
- [[pph21]]
- [[contracts]]
- [[offboarding]]

<!-- sources: routes/hr-offboarding.php, routes/web.php, routes/console.php, app/Http/Controllers/Finance/LegalBasisController.php, app/Http/Controllers/Finance/PayrollSettingsController.php, app/Services/Legal/LegalBasisService.php, app/Services/Legal/PayrollRatesFromLegal.php, app/Services/Overtime/OvertimeRateService.php, app/Console/Commands/LegalBasisReviewReminders.php, resources/views/legal/*.blade.php, resources/views/finance/legal-basis/settings.blade.php -->
