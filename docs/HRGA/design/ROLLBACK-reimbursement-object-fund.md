# Titik kembali: konsep Reimbursement lama dan baru

Dua konsep dipertahankan supaya keputusan atasan dapat diikuti tanpa kehilangan pekerjaan.

| Konsep | Lokasi | Isi |
|---|---|---|
| Lama (sebelum Object dan dana) | Tag `checkpoint/reimbursement-before-object-fund` (commit 8f0fc404), branch `aldy_merge` | Reimbursement dengan cabang saja, penyetuju dari langkah tetap di Settings |
| Baru (Object, dana, penyetuju otomatis) | Branch `feature/reimbursement-object-fund` | Project, Office, Support, kantong dana, void, penyetuju otomatis |

## Memilih konsep lama
1. `git checkout aldy_merge` (atau `git checkout checkpoint/reimbursement-before-object-fund` untuk membaca kondisi tepatnya).
2. Bila migrasi konsep baru sudah dijalankan di sebuah database: dari branch konsep baru jalankan `php artisan migrate:rollback --step=<jumlah migrasi baru>` sebelum berpindah. Migrasi baru berawalan `2026_11_19_` dan seluruhnya punya `down()`.
3. Data Object, dana, dan void hanya ada di tabel baru, jadi menghapusnya tidak menyentuh data Reimbursement lama.

## Memilih konsep baru
`git checkout feature/reimbursement-object-fund`, lalu `php artisan migrate`.

## Menggabungkan
Konsep baru digabung ke `aldy_merge` hanya setelah disetujui. Tag checkpoint tetap sebagai titik acuan.

## Aturan selama pengerjaan
- Semua migrasi konsep baru bersifat penambahan (tabel dan kolom baru, nullable), tidak mengubah atau menghapus kolom yang sudah ada.
- Perilaku Reimbursement lama tetap berjalan saat pengaturan konsep baru belum diisi.
