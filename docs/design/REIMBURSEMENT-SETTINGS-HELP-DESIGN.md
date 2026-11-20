# Rancangan ikon bantuan (?) untuk Reimbursement Settings

Status: DITERAPKAN dengan Opsi 2 (arahan dan penjelasan) pada 2026-11-20. Beralih ke Opsi 1 cukup dengan mengubah `mode` di config/reimbursement_help.php menjadi `explain`.

## 1. Tujuan

Halaman Reimbursement Settings memuat banyak aturan yang saling terkait (alur, kebijakan dana, aturan akses, alokasi, workflow). Ikon "?" di samping judul bagian dan label penting menjelaskan arti pengaturan tanpa meninggalkan halaman.

## 2. Dua opsi

| | Opsi 1: Penjelasan saja | Opsi 2: Arahan dan penjelasan |
|---|---|---|
| Isi popover | Apa fungsi pengaturan, apa akibat tiap pilihan | Sama, ditambah: nilai yang direkomendasikan dan alasannya, akibat bila salah pilih, tautan "Open the guide" ke topik panduan |
| Tombol | Tidak ada | `Use recommended` mengisi input saja (tidak menyimpan), `Open the guide` |
| Risiko | Sangat rendah | Rendah: tombol hanya mengubah nilai di layar, penyimpanan tetap lewat tombol Save dan dialog konfirmasi |
| Biaya | Satu komponen dan satu berkas teks | Opsi 1 ditambah konstanta rekomendasi dan tautan panduan |
| Rekomendasi | | Dipilih bila Finance sering ragu memilih nilai. Dapat diterapkan bertahap: mulai Opsi 1, tambah arahan per bagian |

## 3. Tata letak

```
Who can charge costs to a project or ticket (?)        <- ikon di luar elemen judul
 [Projects: Team v]  [Support tickets: Team v]

 +--------------------------------------------+
 | Who can charge costs                  [x]  |   popover (fixed, lebar 320px)
 | Team lets the lead and members charge...   |
 | Recommended: Team   (Opsi 2)               |
 | If wrong: members cannot charge...         |
 | [Use recommended]  [Open the guide]        |
 +--------------------------------------------+
```

Aturan penempatan:
- Ikon berupa `<button type="button">` kecil (24px area sentuh) setelah judul, bukan di dalam `<h2>` atau `<h3>`, supaya navigasi bagian Settings dan pencarian panduan tidak terpengaruh.
- Popover memakai posisi fixed dan dihitung dari ikon, dibalik ke atas bila tidak muat. Tidak terpotong `overflow` tabel.
- Tutup dengan Esc, klik di luar, atau tombol tutup. Fokus kembali ke ikon.
- Atribut: `aria-expanded`, `aria-controls`, `aria-label="Help: <judul>"`, popover `role="dialog"`.
- `print:hidden`. Warna memakai token tema yang sudah ada supaya aman bagi tema musiman.
- Layar sempit: popover menjadi lembar bawah selebar layar.
- Kunjungan pertama: satu banner ringkas "New: help icons explain each setting" yang bisa ditutup (disimpan di localStorage dengan try/catch).

## 4. Penyimpanan isi

- `config/reimbursement_help.php`: larik berkunci per bidang, berisi `title`, `body`, `recommended` (opsional), `risk` (opsional), `guide` (kunci topik dan jangkar).
- Teks bahasa Inggris, sama dengan layar. Panduan pengguna tetap dua bahasa.
- Nilai rekomendasi memakai konstanta yang sama dengan kode (`ObjectAccessPolicy::TEAM`, `ObjectFlowSetting::POLICY_WARN`), tidak diketik ulang.
- Tautan panduan memakai kunci topik yang sudah ada, sehingga hasil suntingan di User Guide Editor ikut terbawa.

## 5. Daftar bantuan (isi awal)

| Bagian | Isi inti | Rekomendasi (Opsi 2) |
|---|---|---|
| Use Objects and funds | Menyalakan konsep Object untuk dokumen baru. Dokumen lama tidak berubah | On setelah checklist selesai |
| Project approvals | Siapa dan urutan penyetuju project, serta pengganti | Admin lalu Manager, pengganti diisi |
| Support approvals | Ticket Lead dan Support Manager | Lead on, Manager sesuai struktur |
| Who can charge | Team, Lead only, Everyone, dan pengecualian | Team untuk project dan tiket |
| Warn or Block | Warn meminta alasan, Block menolak | Warn untuk Office, Block untuk dana ketat |
| Office funds | Dana bulanan dan penanggung jawab | Isi penanggung jawab untuk semua office |
| Allocations | Typed amount atau Project plan, kategori | Project plan bila Plan Cost sudah diisi |
| Exceptions | Aturan berbeda untuk satu project atau tiket | Dipakai seperlunya |
| Approval Workflow | Langkah untuk semua dokumen, kolom Signs as | Accounting dan Cashier di sini |

## 6. Aturan konsistensi

- Satu komponen Blade (`partials/help-tip`) dan satu berkas JS, dipakai semua bagian.
- Tidak ada tanda pisah panjang di teks. Tidak ada slug teknis di teks.
- Teks bantuan tidak menampilkan data nyata. `Use recommended` tidak pernah menyimpan.
- Teks bantuan harus sama dengan perilaku kode. Tambahkan tes yang memastikan setiap kunci bantuan punya `title` dan `body`, dan bahwa tautan panduan menunjuk topik yang ada.

## 7. Rencana QA rancangan

1. Tata letak: ikon tidak merusak judul, tidak terpotong tabel, terbaca di lebar 360px dan 1280px, tema terang dan musiman.
2. Aksesibilitas: dapat dibuka dengan keyboard, fokus kembali, pembaca layar membaca judul.
3. Isi: setiap pernyataan dicocokkan dengan kode (controller, service) sebelum ditulis.
4. Keamanan: tidak ada innerHTML dari data pengguna, tombol rekomendasi hanya mengubah input.
5. Dampak: tidak ada perubahan skema atau perilaku server.

## 8. Langkah penerapan (setelah opsi dipilih)

1. Buat komponen dan konfigurasi untuk satu bagian (Who can charge) sebagai uji.
2. Tinjau tampilan bersama pengguna.
3. Perluas ke bagian lain, perbarui panduan EN dan ID, jalankan guide-check dan tes.
