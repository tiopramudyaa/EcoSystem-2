# Rancangan: Object (pengganti Branch) dan penanda tangan otomatis

Status: USULAN, menunggu keputusan di bagian 6. Belum ada kode yang diubah untuk nomor 4.
Cakupan: Cash Advance (CA), Reimbursement (RB), Purchase Order (PO). Purchase Request tidak punya nominal dan tidak ikut.

## 1. Kondisi sekarang (diverifikasi dari kode)

| Hal | Cash Advance | Reimbursement | Purchase Order |
|---|---|---|---|
| Penyetuju langkah 1 | Dropdown Approver hanya bila langkah menawarkan pilihan (`approverChoice()`), selebihnya otomatis | Penanda tangan hanya baca (`signatories()`) | `cashier_id` dan accounting dipilih di form, default dari Settings |
| Branch / Project | `Charged To`: satu tipe (branch atau project) dan satu target (`resolveCostCenter()`) | `charged_branch_id` satu cabang, untuk penyaringan | tidak ada |
| Project admin / manager | tidak dipakai untuk persetujuan | tidak dipakai | tidak dipakai |
| Nomor tiket | tidak ada | tidak ada | tidak ada |

Data yang sudah ada dan bisa dipakai: `delivery_projects.project_manager_id` dan `project_admin_id` (nullable), `tickets.ticket_number`, tabel `branches`, `ApproverResolver` dan `ApprovalEngine`.

Temuan: kata "Fithriya" tidak ada di kode atau data seed. Pengganti saat project tidak punya admin harus berupa pengaturan (orang yang dipilih di Settings), bukan nama yang ditanam di kode.

## 2. Prinsip

1. Pemohon tidak mengisi apa yang bisa diturunkan sistem. Approver, Accounting dan Cashier tidak pernah menjadi isian wajib. Layar hanya menampilkan pratinjau "Will be reviewed by" (hanya baca).
2. Alur persetujuan dibekukan saat dokumen dikirim (aturan yang sudah berlaku). Mengubah Object sesudah kirim tidak mengubah alur.
3. Siapa yang harus tahu dan menyetujui ditentukan oleh Object, bukan oleh pilihan pemohon.
4. Tidak ada nama orang di kode. Semua pengganti (fallback) adalah pengaturan.

## 3. Object menggantikan Branch / Charged To

Nama layar: `Object` (kolom `Charged To` diganti). Satu dokumen boleh punya lebih dari satu baris Object.

Tipe Object:

- `Office`: memilih cabang dari tabel `branches` (perilaku Branch sekarang).
- `Project`: memilih proyek terbuka dari `delivery_projects`.
- `Support Ticket`: memilih nomor tiket (mis. akomodasi, transport untuk support). Dicari dengan mengetik nomor tiket.
- `Other`: teks bebas pendek, hanya bila Settings mengizinkan. Tidak memicu persetujuan tambahan.

Penyimpanan: satu tabel anak per dokumen (mis. `charge_objects` dengan `document_type`, `document_id`, `object_type`, `ref_id`, `label`, `amount` opsional). Kolom `charged_branch_id` dan `charged_project_id` lama dimigrasikan menjadi satu baris Object lalu dipertahankan hanya untuk kompatibilitas laporan. `label` disalin saat simpan supaya dokumen lama tetap terbaca bila proyek atau cabang diubah namanya.

Pembagian nominal per Object: opsional. Bila ada, jumlah baris harus sama dengan total dokumen; bila kosong, seluruh nominal dianggap ditanggung Object pertama.

## 4. Alur persetujuan berdasarkan Object

Urutan saat dokumen dikirim:

1. Sistem membaca semua Object dokumen.
2. Untuk setiap Object bertipe Project, sistem menyisipkan langkah dinamis SEBELUM langkah Settings:
   - Project Admin (`project_admin_id`) lalu Project Manager (`project_manager_id`).
   - Bila proyek tidak punya admin: langkah admin dilewati dan orang pengganti dari Settings (`Project fallback`) mengambil perannya. Bila pengganti juga kosong, pengiriman ditolak dengan pesan yang menyebut proyeknya dan siapa yang harus mengisi admin.
   - Satu orang yang sama muncul di dua peran atau di dua proyek hanya diminta sekali (deduplikasi, urutan pertama menang).
3. Pemohon yang kebetulan adalah admin atau manager proyek itu tidak menyetujui dokumennya sendiri; langkah itu dilewati dan dicatat sebagai "skipped: requester".
4. Object bertipe Support Ticket: pemilik tiket (akun atau tim) hanya diberi tahu, tidak menyetujui, kecuali diputuskan lain (lihat pertanyaan 4).
5. Setelah langkah dinamis, langkah Settings berjalan seperti sekarang (Verificator, Accounting, Cashier, Approver). Penanda tangan kolom cetak diisi otomatis dari pelaku langkah dengan actor yang sama.

Efek dana proyek: persetujuan Project Manager berarti dana proyek boleh dipakai untuk dokumen ini. Dokumen yang ditolak di langkah ini tidak berlanjut ke Accounting.

## 5. Kasus tepi dan pesan galat

- Proyek ditutup saat dokumen masih berjalan: dokumen lanjut, tetapi dokumen baru ditolak dengan pesan "Project X is closed. Choose an open project."
- Admin atau manager nonaktif: pengiriman ditolak dengan nama proyek dan peran yang kosong, bukan galat umum.
- Tidak ada langkah Settings aktif: pesan yang sudah ada dipertahankan.
- Object kosong saat `require_cost_center` aktif: pesan menyebut "Add at least one Object".
- Dua baris Object yang sama persis: ditolak sebagai duplikat.

## 6. Keputusan yang dibutuhkan sebelum implementasi

1. Siapa "Fithriya": peran atau orang tertentu? Usulan: pengaturan `Project fallback approver` di Settings tiap modul, diisi orangnya.
2. Apakah Project Admin dan Project Manager keduanya wajib menyetujui berurutan, atau cukup salah satu? Usulan: keduanya berurutan (admin lalu manager).
3. Apakah RB dan PO ikut aturan Object ini, atau hanya CA dulu? Usulan: CA dan RB sekarang, PO menyusul.
4. Support Ticket: hanya pelabelan, atau pemilik tiket harus menyetujui? Usulan: hanya pelabelan dan notifikasi.
5. Apakah satu dokumen boleh memuat beberapa proyek sekaligus (dua manager menyetujui)? Usulan: boleh, deduplikasi orang.
6. Apakah pembagian nominal per Object dibutuhkan sekarang? Usulan: ditunda, nominal ikut Object pertama.

## 7. Rencana kerja setelah keputusan

1. Migrasi tabel Object dan pemindahan data lama.
2. Layanan `ChargeObjectResolver` dan integrasi ke `ApprovalEngine` (langkah dinamis, deduplikasi, lewati diri sendiri).
3. Form CA dan RB: kontrol Object berulang, pratinjau penyetuju, hapus isian penanda tangan manual.
4. Settings: `Project fallback approver`, tipe Object yang diaktifkan.
5. Panduan (EN dan ID) untuk Cash Advance Management, My Cash Advance, Reimbursement, bagian `Rules to know`.
6. Uji: proyek tanpa admin, pemohon adalah manager, dua proyek, proyek ditutup, penyetuju nonaktif.

## 8. Keputusan pengguna (2026-11-18)

- A1: pengganti penyetuju dipilih di Settings sebagai role lalu orangnya, bukan nama tetap di kode.
- A2: Project Admin lalu Project Manager secara berurutan, tetapi urutan dan keikutsertaan setiap langkah dapat diatur di Settings.
- A3: Cash Advance dan Reimbursement dulu. Purchase Order menyusul dan WAJIB diingatkan ke pengguna setelah keduanya selesai.
- A4: Support Ticket hanya label dan notifikasi bagi pemilik tiket (untuk Cash Advance). Untuk Reimbursement, tiket juga menjadi sumber dana (lihat bagian 9).
- A5: satu dokumen boleh memuat beberapa Object, penyetuju yang sama hanya diminta sekali.
- A6: nominal per Object ditunda.

## 9. Reimbursement per Object dengan dana (hasil riset kode)

Temuan: Reimbursement sudah punya kolom `cost_center_type` dan `delivery_project_id` per baris, tetapi alurnya dikunci ke branch (`ReimbursementItem::SELECTABLE_COST_CENTER_TYPES`, `normaliseItems`). Cash Advance dan Purchase Request sudah punya pola project/branch yang bisa disalin. Tiket tidak punya kolom dana. Proyek punya anggaran biaya tanpa kategori baku, dan `actual_amount` ditimpa dari cost item. Master cabang tidak punya penanggung jawab. Tidak ada role Cashier.

Visualisasi konsep dan tabel keputusan D1 sampai D7 ada di artefak "Alur Reimbursement per Object". Prinsipnya: dana dipesan (reserved) saat kirim, dikunci (committed) saat disetujui, dipotong (spent) saat dibayar, dan dikembalikan (released) bila ditolak. Reimbursement tidak menulis `actual_amount` langsung.

## 10. Keputusan final tambahan (2026-11-19)

- D1: tabel alokasi dana tiket, diisi Support Manager, tidak melebihi anggaran kontrak bila tiket terhubung.
- D2: tabel alokasi reimbursement terpisah, data biaya project tidak disentuh.
- D3: kebijakan dana melebihi sisa diatur di Settings (Peringatan, Blokir, per Object), bawaan Peringatan dengan alasan wajib.
- D4: dana berkurang saat disetujui, sisa diperiksa ulang pada persetujuan terakhir.
- D5: penanggung jawab Office di tabel pengaturan per cabang (role, lalu orang).
- D6: Accounting dan Cashier dari role HO Accounting dan HO Finance, diatur di Settings.
- D7: satu dokumen boleh memuat beberapa Object.
- E1: periode dana Office per bulan. E2: Finance dapat void dokumen yang sudah disetujui, alasan wajib. E3: alokasi Project diusulkan PM dan disetujui Finance.

Dokumen lengkap dengan diagram: "Rancangan Reimbursement per Object" (Claude Docs).

## 11. Status implementasi (2026-11-19, branch feature/reimbursement-object-fund)

Selesai untuk **Reimbursement**: migrasi tambahan, kantong dana, penyetuju otomatis per Object, Object per baris (Office, Project, Support), pratinjau penyetuju, alasan melebihi dana, pengaturan Objects and Funds, dan Void oleh Finance. Semua di balik saklar `object_flow_settings.enabled`.

Penyederhanaan tahap 1 (disengaja):
- Persetujuan per **dokumen**, bukan per baris. Dana berkurang saat dokumen disetujui sepenuhnya. Keputusan per baris (ditolak sebagian) ditunda.
- Alokasi project dan tiket diisi **Finance** di Settings. Usulan oleh Project Manager dan persetujuan Finance (E3) ditunda.
- Void dicatat lewat kolom `voided_at`, status tetap `approved`.
- **Belum dikerjakan:** Cash Advance (A3: menyusul setelah Reimbursement) dan Purchase Order (menyusul sesudahnya, wajib diingatkan).
