# Approval Bertingkat (Multi-Level Approval) - Konsep, Arsitektur, dan Panduan Penerapan

> **Tujuan dokumen.** Menjelaskan secara utuh bagaimana approval bertingkat bekerja di EcoSystem-2 (acuan utama: modul **Reimbursement**), lalu menyediakan **prompt siap pakai** agar Claude Code (VS Code) di fitur/modul lain dapat menganalisis kode, memahami pola, dan menerapkannya secara konsisten.
>
> **Cara memakai.** (1) Baca Bagian 1-9 untuk memahami konsep. (2) Salin prompt dari **Bagian 12** ke Claude Code pada fitur target. (3) Gunakan **Bagian 11 (checklist)** untuk verifikasi hasil.
>
> **Sumber kebenaran.** Setiap klaim di dokumen ini diverifikasi terhadap kode (path dicantumkan). Bila kode berubah, kode yang menang - perbarui dokumen ini.

---

## Daftar Isi

1. [Ringkasan Konsep](#1-ringkasan-konsep)
2. [Glosarium](#2-glosarium)
3. [Arsitektur dan Peta Komponen](#3-arsitektur-dan-peta-komponen)
4. [Model Data](#4-model-data)
5. [Siklus Hidup dan Mesin Status](#5-siklus-hidup-dan-mesin-status)
6. [Aturan Bisnis (Invariant)](#6-aturan-bisnis-invariant)
7. [Detail Implementasi per Lapisan](#7-detail-implementasi-per-lapisan)
8. [Mengubah Alur Saat Ada Dokumen Berjalan](#8-mengubah-alur-saat-ada-dokumen-berjalan)
9. [Variasi yang Sudah Ada di Modul Lain](#9-variasi-yang-sudah-ada-di-modul-lain)
10. [Keterbatasan yang Diketahui](#10-keterbatasan-yang-diketahui)
11. [Checklist Penerapan pada Fitur Baru](#11-checklist-penerapan-pada-fitur-baru)
12. [Prompt Siap Pakai untuk Claude Code](#12-prompt-siap-pakai-untuk-claude-code)
13. [Kerangka Kode (Skeleton)](#13-kerangka-kode-skeleton)
14. [Strategi Pengujian](#14-strategi-pengujian)
15. [Jebakan Umum (Pitfalls)](#15-jebakan-umum-pitfalls)
16. [Lampiran: Peta File Acuan](#16-lampiran-peta-file-acuan)

---

## 1. Ringkasan Konsep

Approval bertingkat = sebuah dokumen (pengajuan) harus disetujui **berurutan** oleh beberapa langkah (*step*). Dokumen baru lolos penuh bila **seluruh langkah** menyetujui; **satu penolakan menutup seluruh dokumen**.

Empat gagasan inti yang membentuk desain ini:

| # | Gagasan | Intisari |
|---|---------|----------|
| 1 | **Konfigurasi ≠ Riwayat** | Definisi alur disimpan di tabel *konfigurasi* (`*_approval_steps`). Saat dokumen dibuat, langkahnya **disalin (snapshot)** ke tabel *riwayat* per-dokumen (`*_request_approvals`). Mengubah konfigurasi **tidak pernah** mengubah dokumen yang sedang berjalan. |
| 2 | **Alur diatur lewat Settings, bukan kode** | Admin menambah/mengubah/menghapus/mengurutkan langkah dari halaman *Approval Workflow*. Tanpa migrasi, tanpa deploy. |
| 3 | **Service sebagai satu-satunya pengambil keputusan** | `canAct()` di service memutuskan siapa boleh bertindak *pada dokumen ini sekarang*. Controller, view, dan middleware hanya **membuka pintu** atau **menampilkan tombol**. |
| 4 | **Mesin bersama untuk pengintaian lintas modul** | `ApprovalEngine` menjawab "siapa ditunjuk, apa yang menunggu siapa" dan membangun **My Approvals** (inbox) - tanpa memigrasikan riwayat modul mana pun. |

Satu penyetuju per langkah **sudah cukup** ("one person per step is enough"): langkah bisa ditujukan ke *semua pemegang sebuah posisi (role)* atau ke *orang-orang tertentu*, dan siapa pun dari mereka yang lebih dulu bertindak menyelesaikan langkah itu.

---

## 2. Glosarium

| Istilah | Arti |
|---------|------|
| **Document / Request** | Entitas yang disetujui (mis. `ReimbursementRequest`). Memiliki `status` dan `current_step_order`. |
| **Step (konfigurasi)** | Baris di `*_approval_steps`: cetakan satu langkah. Berlaku untuk dokumen **baru**. |
| **Approval (snapshot)** | Baris di `*_request_approvals`: salinan langkah milik **satu dokumen** + hasil tindakannya. |
| **`order_seq`** | Urutan langkah (1,2,3…). Menentukan langkah mana berjalan lebih dulu. |
| **`current_step_order`** | Pada dokumen: `order_seq` langkah yang sedang menunggu; `NULL` bila dokumen sudah tertutup. |
| **Designated approver** | Karyawan yang **disebut namanya** pada langkah (via role / nama). Boleh membuka halaman review tanpa slug menu HR. |
| **Slug** | Kunci hak akses menu (mis. `general.reimbursement.approve`). Mengatur siapa boleh membuka *halaman*, bukan siapa boleh menyetujui *dokumen tertentu*. |
| **`requester_selectable` / "Mandatory"** | Bila *tidak* mandatory, **pemohon wajib memilih** siapa yang bertindak dari kandidat langkah. Bila mandatory, orangnya tetap. |
| **`signs_as`** | (Khusus Reimbursement) kolom tanda tangan dokumen yang diisi langkah ini: `accounting` / `cashier` / `approver`. |
| **Flag** | Penanda pada dokumen (array JSON) untuk anomali/peristiwa, mis. `self_approved`, `workflow_extended`. |

---

## 3. Arsitektur dan Peta Komponen

```
                        ┌──────────────────────────────────────────────┐
   ADMIN (Settings)     │  Approval Workflow editor (satu form, satu   │
                        │  tombol Save)                                │
                        └───────────────┬──────────────────────────────┘
                                        │ POST steps/sync
                                        ▼
                        ┌───────────────────────────────┐
                        │ {X}SettingController::syncSteps│  validasi + upsert + hapus + urut ulang
                        └───────────────┬───────────────┘
                                        ▼
                        ┌───────────────────────────────┐
                        │  {x}_approval_steps  (KONFIG)  │
                        └───────────────┬───────────────┘
                                        │ activeSteps()  (disalin saat submit)
   PEMOHON              ┌───────────────▼───────────────┐
   submit()  ──────────▶│        {X}Service              │
                        │  submit → snapshotSteps        │
                        │  canAct → approve / reject     │
                        │  notify / notifyApprovers      │
                        └───────┬───────────────┬───────┘
                                │               │
                 ┌──────────────▼───┐   ┌───────▼──────────────────┐
                 │ {x}_requests      │   │ {x}_request_approvals     │
                 │ status            │   │ (SNAPSHOT + RIWAYAT)      │
                 │ current_step_order│   │ status waiting/approved/  │
                 └──────────────────┘   │ rejected/skipped          │
                                        └───────┬──────────────────┘
                                                │ dibaca
   PENYETUJU            ┌───────────────────────▼──────────────────────┐
   (tanpa slug HR)      │ ApprovalEngine (registry MODULES)             │
   ───────────────────▶ │  waitingFor · isDesignatedApprover ·          │
                        │  participates · inbox                          │
                        └───────┬──────────────────────┬────────────────┘
                                │                      │
              ┌─────────────────▼───┐        ┌─────────▼─────────────────────┐
              │ Middleware           │        │ ApprovalInboxController        │
              │ menu.approver:{mod}, │        │ My Approvals (index / show)    │
              │ slug…                │        └───────────────────────────────┘
              └─────────────────────┘
```

### Komponen yang HARUS ada per fitur

| Lapisan | Reimbursement (acuan) | Peran |
|---------|----------------------|-------|
| Migrasi konfigurasi | `2026_08_24_000003_create_reimbursement_approval_steps_table.php` | Tabel cetakan langkah + seed langkah awal |
| Migrasi riwayat | `2026_08_24_000006_create_reimbursement_request_approvals_table.php` | Tabel snapshot per dokumen |
| Kolom di dokumen | `reimbursement_requests.status`, `.current_step_order` | Posisi dokumen dalam alur |
| Model konfigurasi | `App\Models\Reimbursement\ReimbursementApprovalStep` | Konstanta tipe, scope, `candidateEmployeeIds()` |
| Model riwayat | `App\Models\Reimbursement\ReimbursementRequestApproval` | Konstanta status, `allows()`, `isWaiting()`, `approverLabel()` |
| Model dokumen | `App\Models\Reimbursement\ReimbursementRequest` | `isOpen()`, `currentApproval()`, `approvals()`, `OPEN_STATUSES` |
| Model setting | `App\Models\Reimbursement\ReimbursementSetting` | `allow_self_approval`, `locked_period_policy`, dst. (tabel satu baris) |
| Service | `App\Services\Reimbursement\ReimbursementService` | `submit`, `canAct`, `approve`, `reject`, `snapshotSteps`, `applyStepToOpenRequests`, notifikasi |
| Controller dokumen | `HR_General\ReimbursementController` (`approve`, `reject`, `show`) | Tipis: validasi input → panggil service → flash |
| Controller setting | `HR_General\ReimbursementSettingController` (`syncSteps`, `stepData`) | Simpan seluruh alur sekaligus |
| Routes | `routes/hr-general.php` | Rute approve/reject, settings, steps sync |
| Middleware | `CheckMenuOrDesignatedApprover` (`menu.approver`) | Pintu: pemegang slug **atau** penyetuju yang ditunjuk |
| Engine bersama | `App\Services\Approval\ApprovalEngine` + `MODULES` | Registrasi modul → inbox & akses |
| Penerima notifikasi | `App\Support\ApprovalRecipients` | Menerjemahkan satu langkah → daftar `employee_id` |
| View editor | `partials/approval-workflow/{editor,row,people-picker}.blade.php` + `reimbursement/components/step-editor.blade.php` | Editor alur yang dipakai bersama |
| View pemohon | `reimbursement/components/step-choices.blade.php` | Tampilan langkah tetap + dropdown pilihan pemohon |
| View dokumen | `reimbursement/show.blade.php` | Tombol putusan + *Approval Trail* |
| Menu/slug | Migrasi `*_add_approval_workflow_menus.php` (`MenuRegistrar`) | Slug hak akses untuk halaman workflow |

---

## 4. Model Data

### 4.1 `{x}_approval_steps` - KONFIGURASI

Acuan: `reimbursement_approval_steps`.

| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| `id` | PK | |
| `module` | string(30) | Default nama modul. Dipertahankan agar satu tabel bisa dipakai bersama bila perlu (mis. Cash Advance memakai satu tabel untuk dua alur). Query selalu difilter dengan `scopeFor{X}()`. |
| `order_seq` | unsigned tinyint | Urutan. **Maks. 255** (lihat Bagian 15). |
| `name` | string(100) | Nama langkah (tampil sebagai "Waiting {name}"). |
| `approver_type` | string(20) | `role` \| `employee` \| `direct_manager` (lihat 4.4). |
| `approver_role_id` | FK nullable → `employee_role.id` (`nullOnDelete`) | Dipakai bila tipe `role`; boleh tetap terisi saat tipe `employee` agar UI menampilkan kembali pilihan. |
| `approver_employee_ids` | JSON nullable | Daftar `employee_id` (disimpan JSON, bukan pivot, karena selalu dibaca utuh). |
| `signs_as` | string(20) nullable | *Khusus Reimbursement* (kolom tanda tangan). Modul lain: tidak perlu. |
| `requester_selectable` | boolean default false | `true` = pemohon wajib memilih orangnya. |
| `is_active` | boolean default true | Langkah nonaktif tidak ikut dokumen baru. |
| `timestamps` | | |
| Index | `(module, order_seq)` | |

### 4.2 `{x}_request_approvals` - SNAPSHOT + RIWAYAT

Acuan: `reimbursement_request_approvals`.

| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| `id` | PK | |
| `{x}_request_id` | FK → dokumen, `onDelete cascade` | |
| `order_seq` | unsigned tinyint | **Unik bersama FK** (`unique[{x}_request_id, order_seq]`). |
| `step_name` | string(100) | **Salinan teks**, bukan FK: nama tetap terbaca walau langkah asli diganti/dihapus. |
| `approver_type`, `approver_role_id`, `approver_employee_ids` | | Salinan definisi saat dokumen dibuat. |
| `signs_as` | nullable | Salinan (bila dipakai). |
| `status` | string(20) default `waiting` | `waiting` \| `approved` \| `rejected` \| `skipped`. Index pada `status`. |
| `acted_by` | FK nullable → `employee.employee_id` (`nullOnDelete`) | Siapa yang **benar-benar** menekan tombol (role bisa dipegang banyak orang). |
| `acted_at` | timestamp nullable | |
| `notes` | text nullable | Catatan; **wajib** saat menolak. |
| `timestamps` | | |

### 4.3 Kolom pada dokumen (`{x}_requests`)

| Kolom | Fungsi |
|-------|--------|
| `status` (string(20), index) | `submitted` → `in_review` → `approved` \| `rejected` |
| `current_step_order` (nullable) | `order_seq` langkah yang sedang menunggu. `NULL` = tertutup. |
| `completed_at` | Waktu dokumen selesai (approved/rejected). |
| `flags` (JSON) | Penanda peristiwa/anomali. |
| `employee_id` | Pemohon. **Wajib ada** (engine membacanya). |
| `created_by` (opsional) | Bila admin membuat atas nama pemohon. Engine ikut membacanya untuk `participates()`. |
| `request_no` | Nomor dokumen (kolom nomor dipetakan di registry engine). |

### 4.4 Tipe penyetuju

| `approver_type` | Siapa yang boleh bertindak | Status di Reimbursement |
|-----------------|----------------------------|-------------------------|
| `role` | Siapa pun pemegang `approver_role_id` (cukup satu) | Aktif |
| `employee` | Salah satu `employee_id` dalam `approver_employee_ids` | Aktif |
| `direct_manager` | Atasan langsung pemohon | **Terdaftar tetapi belum dapat dijalankan** di Reimbursement (`allows()` mengembalikan `false`; UI dinonaktifkan). Pola ini sengaja dipertahankan agar pengaktifannya kelak tanpa migrasi. |

> Pola *requester-selectable*: saat submit, pilihan pemohon **menimpa** snapshot menjadi `approver_type = employee` dengan `approver_employee_ids = [id_pilihan]` dan `approver_role_id = null`. Jadi pada riwayat, langkah pilihan selalu terkunci ke **satu orang**.

---

## 5. Siklus Hidup dan Mesin Status

### 5.1 Status dokumen

```
            submit()
               │
               ▼
        ┌────────────┐  approve (masih ada langkah berikut)   ┌───────────┐
        │ submitted  │ ─────────────────────────────────────▶ │ in_review │ ──┐
        └─────┬──────┘                                        └─────┬─────┘   │ approve
              │ approve (langkah terakhir)                          │         │ (masih ada)
              ▼                                                     │ approve │
        ┌────────────┐ ◀────────────────────────────────────────────┘ (terakhir)
        │ approved   │
        └────────────┘
   reject di langkah mana pun ──▶ ┌────────────┐
                                  │ rejected   │  (langkah sisa → skipped)
                                  └────────────┘
```

* `OPEN_STATUSES = [submitted, in_review]` - dokumen "berjalan"/menunggu tindakan.
* Status hanya **4 nilai**. Tampilan "Waiting GA Verification" dibangun dari **nama langkah** yang sedang menunggu (`statusLabel()`), sehingga mengubah alur tidak perlu menambah nilai status.

### 5.2 Status per langkah (snapshot)

| Status | Arti | Dibuat oleh |
|--------|------|-------------|
| `waiting` | Belum bertindak | `snapshotSteps()` / `applyStepToOpenRequests()` |
| `approved` | Disetujui | `approve()` |
| `rejected` | Ditolak (menutup dokumen) | `reject()` |
| `skipped` | Tidak sempat dijalani karena dokumen sudah ditolak di langkah sebelumnya | `reject()` (massal pada langkah `waiting` yang `order_seq`-nya lebih besar) |

### 5.3 Alur lengkap (urutan eksekusi)

**A. Submit** - `ReimbursementService::submit()`
1. Validasi aturan dokumen (tanggal, item, batas nominal, dst. - *spesifik fitur*).
2. `activeSteps()` - ambil langkah `is_active = true` urut `order_seq`. **Bila kosong → tolak** ("No approval step is configured yet") *sebelum* dokumen dibuat, agar dokumen tidak lahir tanpa jalan keluar.
3. `resolveChoices()` - untuk tiap langkah `requester_selectable`, pilihan pemohon **wajib terisi** dan harus termasuk `candidateEmployeeIds()` langkah itu; bila tidak → tolak.
4. Dalam **satu transaksi DB**: buat dokumen (`status = submitted`, `current_step_order = langkah pertama`), tulis item, `snapshotSteps()`.
5. Di luar transaksi: tanda tangan/artefak sekunder (non-fatal), lalu `notifyApprovers()` ke langkah pertama.

**B. Cek kewenangan** - `canAct($request, $actorId, $canManage)`; urutan gerbang (masing-masing dengan pesan galat berbeda):
1. Dokumen masih terbuka (`isOpen()`).
2. Ada langkah `waiting` pada `currentApproval()`.
3. Periode terkunci → sesuai kebijakan `locked_period_policy` (`off` / `block_employee` / `block_all`). *(spesifik fitur - opsional)*
4. `currentApproval()->allows($actorId, $roleIds)` - aktor termasuk penyetuju langkah ini.
5. Self-approval: bila aktor = pemohon dan `allow_self_approval = false` → tolak.

**C. Approve** - `approve()`
1. `canAct()` harus lolos.
2. (Spesifik Reimbursement) tanda tangan aktor wajib ada; tanpa itu ditolak.
3. Transaksi: tandai flag (`self_approved`, `locked_period` bila relevan) → update snapshot jadi `approved` (+ `acted_by`, `acted_at`, `notes`).
4. Cari langkah berikutnya: `order_seq > ini` & `status = waiting`, urut naik.
   * **Ada** → dokumen `in_review`, `current_step_order = next.order_seq`.
   * **Tidak ada** → dokumen `approved`, `current_step_order = null`, `completed_at = now()`.
5. `notify()` ke pemohon (`approved` / `progressed`); bila belum selesai, `notifyApprovers()` ke langkah berikut.

**D. Reject** - `reject()`
1. `canAct()` harus lolos. **Catatan wajib** (min. 5 karakter, divalidasi di controller).
2. Transaksi: snapshot → `rejected`; semua langkah `waiting` berikutnya → `skipped`; dokumen → `rejected`, `current_step_order = null`, `completed_at = now()`.
3. `notify()` ke pemohon.

---

## 6. Aturan Bisnis (Invariant)

Aturan ini **harus dipertahankan** pada fitur baru.

| # | Aturan | Alasan |
|---|--------|--------|
| R1 | Snapshot disalin saat submit; **tidak pernah** dibaca langsung dari konfigurasi saat memproses. | Cegah dokumen kehilangan langkah / melompat jadi approved ketika alur diubah. |
| R2 | Minimal **satu langkah aktif** harus ada, divalidasi di `syncSteps` **dan** di `submit`. | Dokumen tanpa langkah tak bisa disetujui/ditolak siapa pun. |
| R3 | Satu penolakan **menutup seluruh dokumen**; langkah sisa → `skipped` (bukan dibiarkan `waiting`). | Mencegah dokumen tertutup tampak masih menunggu seseorang. |
| R4 | `employee_id` pemohon **selalu dari sesi**, tidak dari body request. | Cegah pengajuan atas nama orang lain. Pengecualian: pembuatan admin, wajib mencatat `created_by`. |
| R5 | Keputusan "boleh bertindak?" **hanya** di `canAct()` service. Slug/middleware hanya membuka pintu. | Satu sumber kebenaran; tidak ada dua konfigurasi yang bertentangan. |
| R6 | Mengunci pemohon-memilih: pilihan wajib berasal dari `candidateEmployeeIds()` langkah itu. | Cegah pemohon menyisipkan orang di luar kandidat. |
| R7 | Riwayat yang sudah bertindak **tidak pernah** ditulis ulang. | Auditabilitas. |
| R8 | `step_name` disimpan sebagai **teks salinan**, bukan FK. | Nama tetap terbaca setelah langkah asli diganti/dihapus. |
| R9 | Kegagalan notifikasi **tidak boleh** membatalkan persetujuan (try/catch + `Log::error`). | Persetujuan yang tersimpan lebih penting daripada notifikasinya. |
| R10 | Operasi yang mengubah beberapa tabel dibungkus `DB::transaction`. | Konsistensi status dokumen ↔ snapshot. |
| R11 | Perubahan sensitif dicatat ke log (`Log::info/warning`) dengan `actor_id`, sebelum/sesudah. | Penelusuran audit. |
| R12 | Penyetuju yang ditunjuk (tanpa slug) hanya boleh membuka dokumen yang **ia ikuti** (pemohon atau tercantum pada langkahnya). | "Ditunjuk" ≠ "boleh membaca seluruh daftar". |

---

## 7. Detail Implementasi per Lapisan

### 7.1 Model konfigurasi (`ReimbursementApprovalStep`)

* Konstanta: `TYPE_ROLE`, `TYPE_EMPLOYEE`, `TYPE_DIRECT_MANAGER`; `TYPES`, `SELECTABLE_TYPES`, `TYPE_LABELS`.
* Scope: `scopeForReimbursement()` (filter `module`), `scopeActive()`.
* `candidateEmployeeIds()` - untuk tipe `employee`: ID unik dari `approver_employee_ids`; untuk tipe `role`: semua `employee_id` dari `employee_role_assignment` dengan `role_id` tsb; selain itu `[]`.
* `casts`: `approver_employee_ids => array`, `is_active`/`requester_selectable => boolean`.

### 7.2 Model snapshot (`ReimbursementRequestApproval`)

* Konstanta status: `STATUS_WAITING|APPROVED|REJECTED|SKIPPED`.
* `allows(int $employeeId, array $roleIds): bool` - `match` berdasarkan `approver_type`:
  * `role` → `approver_role_id` ∈ `$roleIds`
  * `employee` → `$employeeId` ∈ `approver_employee_ids`
  * `direct_manager`/default → `false` (eksplisit)
* `approverLabel()` - label tampilan (nama role / nama orang / "N selected employee(s)").
* **Hanya memeriksa definisi langkah.** Pemeriksaan keadaan dokumen (langkah aktif? self-approval? periode?) ada di service.

### 7.3 Model dokumen (`ReimbursementRequest`)

Wajib menyediakan (dibaca `ApprovalEngine`):

| Anggota | Dipakai oleh |
|---------|--------------|
| `isOpen(): bool` | Engine (`loadWaiting`), service |
| `currentApproval(): ?Approval` | Engine & service - mengembalikan `null` bila tidak terbuka / `current_step_order` null; selain itu `approvals->firstWhere('order_seq', current_step_order)` |
| `approvals()` | `hasMany(...)->orderBy('order_seq')` |
| `employee_id`, (opsional) `created_by` | Engine `participates()`, `inbox()` |
| kolom nomor (mis. `request_no`) | Engine `inbox()` |
| `OPEN_STATUSES`, `scopeOpen()` | Service & `pendingIdsFor()` |
| `statusLabel($openWord)` | Tampilan |

### 7.4 Service (`ReimbursementService`)

Method kunci (nama dipertahankan di fitur baru agar konsisten):

| Method | Tugas |
|--------|-------|
| `submit($employeeId, $payload, $createdBy = null)` | Lihat 5.3-A. Mengembalikan `['allowed','reason','request']`. |
| `activeSteps()` | `Step::for{X}()->active()->orderBy('order_seq')->get()` |
| `choiceSteps()` / `fixedSteps()` | Data untuk form pemohon: langkah dropdown vs langkah tetap (read-only). |
| `resolveChoices($steps, $raw)` | Validasi pilihan pemohon → `order_seq => employee_id` atau `null`. |
| `snapshotSteps($request, $steps, $choices)` | Salin langkah ke snapshot (lihat 4.4 untuk perilaku pilihan pemohon). |
| `canAct($request, $actorId, $canManage)` | Lihat 5.3-B. Mengembalikan `['allowed','reason','approval']`. |
| `approve(...)` / `reject(...)` | Lihat 5.3-C/D. |
| `pendingIdsFor($actorId)` | ID dokumen yang gilirannya orang ini (menandai/menyaring daftar). |
| `countOpenRequestsBefore($orderSeq)` | Jumlah dokumen berjalan yang terkena bila langkah baru ditambah (ditampilkan **sebelum** tombol Save). |
| `applyStepToOpenRequests($step, $actorId)` | Lihat Bagian 8. |
| `notify()` / `notifyApprovers()` | Notifikasi ke pemohon / ke penyetuju langkah aktif. |
| `roleIdsOf($employeeId)` | `Employee::getRoleIds()`. |

**Konvensi pengembalian gerbang bisnis:** `['allowed' => bool, 'reason' => string, ...]`. Service **tidak** melempar exception untuk penolakan bisnis dan **tidak** menerima `Request` - hanya array biasa (agnostik transport: web/mobile/impor).

### 7.5 Controller dokumen (tipis)

```php
public function approve(Request $request, Doc $doc, Service $service)
{
    $validated = $request->validate(['notes' => ['nullable','string','max:255']]);
    $result = $service->approve($doc, (int) session('user.id'), $validated, $this->canManage());

    if (!$result['allowed']) return back()->with('error', $result['reason']);
    return back()->with('success', $result['completed'] ? '... fully approved.' : 'Step approved. ... next approver.');
}

public function reject(Request $request, Doc $doc, Service $service)
{
    $validated = $request->validate(
        ['notes' => ['required','string','min:5','max:255']],
        ['notes.required' => 'Please explain why ... is rejected.']
    );
    $result = $service->reject($doc, (int) session('user.id'), $validated['notes'], $this->canManage());
    return $result['allowed'] ? back()->with('success','... rejected.') : back()->with('error',$result['reason']);
}
```

`show()` mengirim ke view: `canApprove` (`mayDecide(module, slug)`), `waitingForMe` (hasil `canAct()['allowed']`), `approveRoute`, `rejectRoute`, `backRoute`, plus `approvals` ter-eager-load (`approvals.actor.basicData`, `approvals.role`).

> `mayDecide()` (di base `Controller`) = pemegang slug `.approve` **atau** `ApprovalEngine::isPending(module, employeeId)`. Ini hanya menentukan **apakah tombol dirender**; keputusan final tetap `canAct()`.

### 7.6 Controller setting - `syncSteps()` (simpan seluruh alur sekaligus)

Perilaku (semuanya wajib dipertahankan):

1. Input `steps[]` kosong → tolak ("needs at least one step").
2. Tiap baris divalidasi `stepData()`; galat dikumpulkan per baris (`steps.{i}`) dengan awalan "Step N:".
3. Minimal satu langkah `is_active` → bila tidak, tolak.
4. **Transaksi:**
   * Pindahkan seluruh `order_seq` yang ada ke rentang sementara (mulai **200**) agar urutan baru tak bentrok (kolom TINYINT maks. 255).
   * Loop baris: `id` ada & dikenal → `update`; selain itu → `create` (dengan `module`).
   * Baris yang hilang dari form → `delete`. (Aman: dokumen berjalan memegang snapshot sendiri.)
   * `order_seq` baru = indeks baris di layar + 1.
5. Bila `apply_to_open` dicentang & ada langkah baru → panggil `applyStepToOpenRequests()` per langkah baru; tambahkan catatan hasil di pesan sukses.
6. Redirect ke halaman settings dengan `workflow_saved`.

**`stepData()` - validasi satu baris:**

| Pemeriksaan | Pesan |
|-------------|-------|
| `signs_as` valid *(spesifik Reimbursement)* | "choose what it signs as…" |
| Semua nama yang dipilih masih ada di `employee` | "one of the chosen names no longer exists." |
| Role yang dipilih masih ada | "the chosen position no longer exists." |
| Minimal role **atau** ≥1 nama | "choose a position, or at least one name." |
| Bila tanpa role tetapi semua nama berbagi **tepat satu** role → role itu diisi otomatis (supaya kolom Position terisi). | - |
| Bila `selectable` & tanpa nama & role tidak punya pemegang | "nobody holds that position, so the requester would have nobody to choose." |

Hasil tipe: `employee` bila ada nama, selain itu `role`; `requester_selectable = empty($row['mandatory'])`; `approver_role_id` tetap disimpan walau nama dipilih.

### 7.7 Routes

Pola (lihat `routes/hr-general.php`):

```php
// Keputusan: pemegang slug ATAU penyetuju yang ditunjuk
Route::middleware('menu.approver:reimbursement,general.reimbursement.approve')->group(function () {
    Route::post('/{reimbursementRequest}/approve', [..., 'approve'])->name('approve');
    Route::post('/{reimbursementRequest}/reject',  [..., 'reject'])->name('reject');
});

// Halaman settings: slug aturan ATAU slug workflow
Route::get('/', [SettingController::class,'edit'])->name('edit')
    ->middleware('menu:general.settings.reimbursement,general.approval-workflow.reimbursement');

// Simpan workflow: slug workflow SAJA
Route::post('/steps/sync', [SettingController::class,'syncSteps'])->name('steps.sync')
    ->middleware('menu:general.approval-workflow.reimbursement');
```

🔴 **Urutan rute.** Rute statis (`/settings`, `/dashboard`, `/create`, `/export`) **wajib mendahului** rute berparameter `/{document}`; kalau tidak, "settings" tertangkap sebagai ID dokumen (lihat catatan D177 di file routes).

### 7.8 Middleware `menu.approver`

`menu.approver:{module},{slug1},{slug2}…`

* Lolos bila pemegang **salah satu slug**, **atau** (penyetuju ditunjuk **dan** boleh membuka dokumen itu).
* `isApprover()` → `ApprovalEngine::isDesignatedApprover($module, $id)` (`any` = penyetuju di modul mana pun).
* `mayOpenDocument()` → bila rute membawa parameter dokumen (nama parameter dari `MODULES[module]['param']`), wajib `participates()`; rute daftar (tanpa dokumen) lolos.
* Tidak lolos → JSON 403 (`expectsJson`) atau redirect ke `dashboard` dengan pesan peringatan.

### 7.9 `ApprovalEngine` - registry modul

Fitur baru **didaftarkan** di konstanta `MODULES`:

```php
'{module-key}' => [
    'label'      => 'Nama di inbox',
    'approval'   => \App\Models\X\XRequestApproval::class, // model snapshot
    'relation'   => 'request',                              // relasi snapshot → dokumen
    'fk'         => 'x_request_id',                         // FK snapshot → dokumen
    'number'     => 'request_no',                           // kolom nomor dokumen
    'route'      => 'general.x.show',
    'param'      => 'xRequest',                             // nama parameter rute dokumen
    'document'   => \App\Models\X\XRequest::class,
    'controller' => \App\Http\Controllers\HR_General\XController::class,
],
```

Lalu tambahkan di dua `match` privat: `stepModel()` (modul → model langkah konfigurasi) dan `stepModuleKey()` (nilai kolom `module` pada tabel langkah, hanya bila berbeda dari key).

Kontrak yang diandalkan engine:

* **Snapshot** berbagi bentuk kolom: `approver_type`, `approver_role_id`, `approver_employee_ids`, `status`, `order_seq`, `step_name`, `acted_by`, `updated_at/created_at`. (`chosen_by_requester` bersifat opsional - bila ada dan `true`, hanya `approver_employee_ids` yang dipakai.)
* **Dokumen** punya `isOpen()` dan `currentApproval()`.
* **Controller modul** punya method `show()` dengan parameter bernama sama dengan `param` (dipanggil oleh `ApprovalInboxController::show` via `app()->call`).

Fungsi publik engine: `waitingFor`, `hasPending`/`isPending`, `isDesignatedApprover`, `isConfiguredApprover`, `isApproverAnywhere`, `participates`, `inbox`, `urlFor`, `stepAllows`.

### 7.10 Notifikasi

* `ApprovalRecipients::forStep($step)` - `role` → semua karyawan **aktif** pemegang role; `employee` → `approver_employee_ids`; selain itu `[]`. Hasil unik.
* `notifyApprovers($request, $step, $isFirstStep)` - pesan berbeda untuk langkah pertama vs lanjutan; pemohon **dikecualikan hanya bila** `allow_self_approval = false` (selaras `canAct()`); `type = {x}_pending_approval`, link ke `/general/approvals`.
* `notify($request, $outcome, $notes)` - ke pemohon: `approved | rejected | progressed | …`.
* Keduanya dibungkus `try/catch` (R9).
* Periksa juga `App\Console\Commands\BackfillPendingApprovalNotifications` - perintah ini mengisi ulang notifikasi pending dan memiliki pengetahuan per modul; pertimbangkan mendaftarkan fitur baru di sana.

### 7.11 View

* **Editor workflow**: gunakan partial bersama; cukup buat satu file pembungkus seperti `reimbursement/components/step-editor.blade.php` yang mengisi `$wf`:

  | Kunci `$wf` | Arti |
  |-------------|------|
  | `uid`, `action`, `title`, `blurb` | Identitas editor, URL sync, judul |
  | `name` (bool) | Kolom "Step name" bebas diisi admin (Overtime/PR/CA). Reimbursement `false` (nama diturunkan dari `signs_as`). |
  | `select` | Dropdown tambahan, mis. `signs_as` |
  | `mandatory` (bool) + `mandatory_hint` | Kolom "Mandatory" |
  | `dynamic` (bool) | Kolom "Approver": posisi/nama tetap, atasan pemohon, atau pemimpin proyek (Overtime) |
  | `open_count`, `open_label` | Checkbox "Also apply new steps to N document(s) in progress" |
  | `module`, `note` | Hidden field `module` / kotak info |

  Variabel wajib dari controller: `$steps`, `$openCount`, `$roles`, `$employees`, `$roleMembers`, `$employeeRoles`.
* **Form pemohon**: `step-choices.blade.php` - `fixedSteps` tampil read-only; `choiceSteps` berupa `<select name="step_approver[{step_id}]" required>`.
* **Halaman dokumen**: tombol Approve/Reject hanya bila `canApprove && waitingForMe`; catatan wajib untuk reject; bagian **Approval Trail** menampilkan tiap snapshot (titik warna: hijau=approved, merah=rejected, abu=skipped, kuning=waiting), nama langkah, aktor atau `approverLabel()`, waktu, dan catatan.

### 7.12 Menu & hak akses

* Slug workflow dipisah dari slug aturan (D180): `general.approval-workflow.{module}` - didaftarkan via `MenuRegistrar::register(...)` pada migrasi (`down()` memakai `MenuRegistrar::remove`). Default: aktif hanya untuk EC Administrator; role lain diberi manual lewat Control Center → Menu Access.
* Hak tambahan untuk penyetuju ditunjuk (edit/hapus/ekspor dokumen) dikontrol slug `general.approvals` & `general.approvals.export` (`Controller::approverMay()`); default **mati**.

---

## 8. Mengubah Alur Saat Ada Dokumen Berjalan

Aturannya **sengaja asimetris**:

| Perubahan | Berlaku pada dokumen berjalan? | Alasan |
|-----------|-------------------------------|--------|
| **Menambah** langkah | **Opsional** (checkbox `apply_to_open`), dan hanya jika posisi langkah baru **setelah** langkah yang sedang menunggu | Memperketat kontrol tidak pernah merusak persetujuan yang sudah ada. |
| **Menghapus / melonggarkan / menonaktifkan** langkah | **Tidak pernah** | Dokumen yang menunggu di langkah yang dihapus bisa melompat jadi approved tanpa ditinjau. |
| Mengubah nama/penyetuju langkah | **Tidak** (hanya dokumen baru) | Snapshot adalah milik dokumen. |

`applyStepToOpenRequests()` - batas yang dijaga:
* Hanya dokumen berstatus terbuka (`OPEN_STATUSES`); yang sudah approved/rejected tak tersentuh.
* Hanya bila `current_step_order < step.order_seq` (langkah yang posisinya sudah dilewati diabaikan).
* Jaring pengaman: dilewati bila dokumen sudah punya baris dengan `order_seq` itu.
* Tidak menulis ulang baris yang sudah bertindak.
* Dokumen terhapus (soft delete) dilewati secara alami oleh query.
* Menambahkan flag netral `workflow_extended` dan mencatat `Log::info` (pelaku, nama langkah, jumlah, nomor dokumen).
* Dibungkus transaksi.

---

## 9. Variasi yang Sudah Ada di Modul Lain

Pola dasar sama; berikut variasi yang **terverifikasi di kode** dan bisa dipakai bila fitur baru membutuhkannya:

| Variasi | Di mana | Catatan |
|---------|---------|---------|
| Penyetuju dinamis: **atasan pemohon** (`direct_manager`) / **pemimpin proyek** (`project_lead`) | Overtime (`editor` dengan `dynamic = true`; resolusi di `ApproverResolver`) | Posisi pada baris menjadi **fallback**. Engine `isConfiguredApprover()` sudah memahami kedua tipe ini. |
| Fallback self-approval (`self_approval_fallback_role_id`) | Overtime, Purchase Request, Cash Advance, Cash Advance Report | Dibaca di `canAct()` hanya saat self-approval ditolak, untuk menyebut role pengganti pada pesan galat. |
| Penyetuju boleh menyesuaikan nominal (`allow_approver_adjust_amount`) | Reimbursement, Cash Advance Report | Memberi flag `amount_adjusted`; hak hilang setelah ia menyetujui. |
| Dua alur dari satu tabel langkah (kolom `module`) | Cash Advance & Cash Advance Report | Hak dipisah per slug dan diperiksa ulang di controller. |
| Snapshot dengan `chosen_by_requester` | Purchase Request (per `ApprovalEngineTest`) | Engine memprioritaskan kolom ini bila ada. |
| Kolom tanda tangan (`signs_as`) | Reimbursement | Spesifik dokumen cetak; **jangan** disalin bila fitur target tidak mencetak tanda tangan. |

---

## 10. Keterbatasan yang Diketahui

Diketahui dari kode saat ini; **jangan diasumsikan ada** pada fitur baru kecuali diminta dan dibangun:

* **Hanya serial.** Tidak ada langkah paralel / kuorum ("2 dari 3 harus setuju").
* **Tidak ada "kembalikan ke pemohon" / revisi.** Penolakan bersifat final (menutup dokumen).
* **Tidak ada delegasi / pengganti sementara** (cuti penyetuju).
* **Tidak ada kondisi per-nominal** (mis. langkah hanya bila total > X). Seluruh langkah aktif selalu dibuat.
* **Tidak ada eskalasi / SLA / pengingat otomatis** selain backfill notifikasi manual.
* **`direct_manager` pada Reimbursement belum berfungsi** (`allows()` = `false`; UI dinonaktifkan) walau `ApprovalEngine::isConfiguredApprover()` sudah mengenalinya untuk modul lain. Jangan mengaktifkan di fitur baru sebelum hierarki atasan tersedia dan diverifikasi.
* **`pendingIdsFor()` memuat seluruh dokumen terbuka** ke memori lalu menyaringnya - wajar untuk volume kecil, perlu dioptimalkan bila volume besar.
* **`order_seq` bertipe TINYINT** → maksimum 255 dan rentang sementara 200+ pada `syncSteps` membatasi jumlah langkah praktis (< ~55).
* **Pembatalan oleh pemohon tidak ada** di Reimbursement (keputusan produk; status `cancelled` sengaja dihapus). Fitur lain boleh berbeda (mis. Cash Advance memiliki `allow_requester_cancel`).

---

## 11. Checklist Penerapan pada Fitur Baru

Tandai satu per satu; seluruh item **wajib** kecuali bertanda *(opsional)*.

### Data
- [ ] Migrasi `{x}_approval_steps` (+ seed satu langkah awal agar fitur dapat diuji langsung)
- [ ] Migrasi `{x}_request_approvals` (FK cascade, `unique[request_id, order_seq]`, index `status`)
- [ ] Kolom `status` (4 nilai) & `current_step_order` pada tabel dokumen (+ `completed_at`, `flags`)
- [ ] Docblock migrasi menjelaskan alasan "konfigurasi ≠ riwayat"

### Model
- [ ] `{X}ApprovalStep` (konstanta tipe, scope, `candidateEmployeeIds()`)
- [ ] `{X}RequestApproval` (konstanta status, `allows()`, `approverLabel()`)
- [ ] Dokumen: `OPEN_STATUSES`, `isOpen()`, `currentApproval()`, `approvals()`, `statusLabel()`
- [ ] `{X}Setting` (`allow_self_approval`, dst.) bila fitur punya setting *(opsional)*

### Service
- [ ] `submit()` menolak bila tanpa langkah aktif **sebelum** membuat dokumen
- [ ] `resolveChoices()` + `snapshotSteps()` dalam satu transaksi dengan pembuatan dokumen
- [ ] `canAct()` dengan gerbang berpesan jelas
- [ ] `approve()`/`reject()` (transaksi, `skipped`, `completed_at`, flag)
- [ ] `notify()`/`notifyApprovers()` dengan try/catch + log
- [ ] `pendingIdsFor()`, `countOpenRequestsBefore()`, `applyStepToOpenRequests()`
- [ ] Service tidak menerima `Request`; employee_id dari sesi (R4)

### Controller & Routes
- [ ] `approve()`/`reject()` tipis; catatan wajib saat reject
- [ ] `show()` mengirim `canApprove`, `waitingForMe`, rute aksi, `approvals` ter-eager-load
- [ ] Controller setting: `edit()` + `syncSteps()` + `stepData()`
- [ ] Rute approve/reject di bawah `menu.approver:{module},{slug}`
- [ ] Rute settings & `steps/sync` dengan slug yang benar; rute statis mendahului `/{document}`

### Engine & Akses
- [ ] Daftar di `ApprovalEngine::MODULES`, `stepModel()`, `stepModuleKey()`
- [ ] `show()` controller modul kompatibel dengan `ApprovalInboxController::show`
- [ ] Slug `general.approval-workflow.{module}` didaftarkan lewat `MenuRegistrar` (+ `down()`)
- [ ] Pertimbangkan `BackfillPendingApprovalNotifications` *(opsional)*

### View
- [ ] Pembungkus editor workflow memakai partial bersama
- [ ] Form pemohon: `step-choices` (bila ada langkah pilihan pemohon)
- [ ] Halaman dokumen: tombol putusan + *Approval Trail*
- [ ] Notifikasi muncul dan tertaut ke `/general/approvals`
- [ ] Panduan pengguna `resources/guide/{id,en}/` diperbarui *(opsional)*

### Verifikasi
- [ ] Tes alur (Bagian 14) lulus
- [ ] Uji manual: 2 langkah (role + nama), reject di langkah 2, ubah alur saat dokumen berjalan, penyetuju tanpa slug HR membuka lewat My Approvals

---

## 12. Prompt Siap Pakai untuk Claude Code

> **Petunjuk.** Salin **seluruh blok** prompt ke Claude Code di workspace **fitur target**. Ganti hanya bagian `⟦…⟧`. Pastikan file ini (`docs/approval-bertingkat.md`) terbaca dari workspace tersebut (letakkan/salin ke repo target, atau lampirkan lewat `@docs/approval-bertingkat.md`).
>
> Prompt memuat instruksi **analisis dulu, tulis kode kemudian** agar Claude tidak langsung menebak.

### 12.1 Prompt Utama - Terapkan Approval Bertingkat

```text
Kamu bekerja di repositori Laravel EcoSystem-2. Tugasmu: menerapkan APPROVAL BERTINGKAT
pada fitur ⟦NAMA FITUR, mis. "Cuti" / "Pengajuan Dinas"⟧ dengan mengikuti pola yang sudah
ada pada modul Reimbursement.

SUMBER ACUAN (baca dulu, JANGAN langsung menulis kode):
1. @docs/approval-bertingkat.md  ← spesifikasi konsep, aturan bisnis (R1-R12), dan checklist.
2. Kode acuan Reimbursement:
   - app/Services/Reimbursement/ReimbursementService.php
   - app/Models/Reimbursement/ReimbursementApprovalStep.php
   - app/Models/Reimbursement/ReimbursementRequestApproval.php
   - app/Models/Reimbursement/ReimbursementRequest.php
   - app/Http/Controllers/HR_General/ReimbursementSettingController.php (syncSteps, stepData)
   - app/Http/Controllers/HR_General/ReimbursementController.php (show, approve, reject)
   - app/Services/Approval/ApprovalEngine.php (registry MODULES)
   - app/Http/Middleware/CheckMenuOrDesignatedApprover.php
   - resources/views/hr-general/partials/approval-workflow/*.blade.php
   - database/migrations/2026_08_24_000003_*, 2026_08_24_000006_*, 2026_09_16_000001_*
   - routes/hr-general.php (blok Reimbursement dan Settings)

FITUR TARGET:
- Nama & modul: ⟦...⟧
- File/dir yang sudah ada (model/controller/service/view/migrasi/route): ⟦path...⟧
- Siapa pemohon & dokumen apa yang disetujui: ⟦...⟧
- Status yang saat ini dipakai (jika ada): ⟦...⟧
- Kebutuhan khusus alur (mis. penyetuju atasan, tanpa tanda tangan, nominal): ⟦...⟧

LANGKAH KERJA (ikuti berurutan):

FASE 1 - ANALISIS (hanya baca, belum ubah file):
a. Petakan struktur fitur target: tabel/kolom dokumen, model, service, controller, route,
   view, dan bagaimana status dokumen saat ini ditangani.
b. Tentukan komponen Reimbursement yang BISA dipakai ulang apa adanya (editor workflow
   partial, ApprovalRecipients, ApprovalEngine, middleware) vs yang HARUS dibuat baru
   (tabel steps & request_approvals, model, service, controller setting).
c. Identifikasi bagian Reimbursement yang SPESIFIK reimbursement dan JANGAN disalin kecuali
   fitur target membutuhkannya: signs_as, tanda tangan, periode terkunci, batas nominal,
   pembayaran, flag item, impor Excel.
d. Cari konflik: status lama yang bentrok dengan 4 status standar
   (submitted/in_review/approved/rejected), nama tabel/slug yang sudah terpakai,
   urutan rute yang berisiko tertangkap /{param}.
e. Keluarkan RENCANA berisi: daftar file yang akan dibuat/diubah, skema tabel, nama
   konstanta/method, slug menu, entri ApprovalEngine::MODULES, dan risiko/asumsi.
   Tandai setiap asumsi yang tidak bisa diverifikasi dari kode. BERHENTI dan minta
   konfirmasi bila ada keputusan produk yang ambigu.

FASE 2 - IMPLEMENTASI (setelah rencana disetujui):
Kerjakan sesuai Checklist Bagian 11 di docs/approval-bertingkat.md dan WAJIB mempertahankan
aturan R1-R12 (snapshot saat submit, minimal 1 langkah aktif, reject menutup dokumen dan
menandai sisa langkah 'skipped', employee_id dari sesi, canAct() satu-satunya penentu,
notifikasi tidak boleh membatalkan persetujuan, transaksi DB, audit log).

Konvensi yang harus diikuti:
- Pola nama: {x}_approval_steps, {x}_request_approvals, {X}ApprovalStep, {X}RequestApproval,
  {X}Service::{submit,canAct,approve,reject,snapshotSteps,activeSteps,pendingIdsFor,
  countOpenRequestsBefore,applyStepToOpenRequests}.
- Gerbang bisnis service mengembalikan ['allowed'=>bool,'reason'=>string,...]; service tidak
  menerima Request.
- Gunakan editor workflow bersama (hr-general.partials.approval-workflow.editor) lewat satu
  file pembungkus; jangan menulis ulang editornya.
- Daftarkan fitur di ApprovalEngine::MODULES + stepModel() + stepModuleKey().
- Daftarkan slug general.approval-workflow.⟦module⟧ lewat MenuRegistrar dalam migrasi baru
  (dengan down()).
- Migrasi BARU saja; jangan mengedit migrasi lama. Docblock migrasi menjelaskan alasan desain.
- Bahasa komentar/docblock mengikuti file sekitar (komentar kode di repo ini berbahasa
  Indonesia; pesan UI berbahasa Inggris).

FASE 3 - VERIFIKASI:
- Tulis/jalankan tes sesuai Bagian 14 (alur 2 langkah, reject, self-approval, snapshot tidak
  berubah saat konfigurasi diubah, apply_to_open, tanpa langkah aktif).
- Jalankan: php artisan route:list --name=⟦prefix⟧ untuk memastikan tidak ada bentrok rute,
  dan php artisan migrate --pretend untuk memeriksa SQL.
- Laporkan: file yang dibuat/diubah, hasil tes (apa adanya, termasuk yang gagal), serta
  hal yang tidak dapat diverifikasi.

LARANGAN:
- Jangan mengubah perilaku modul Reimbursement/Overtime/dll. yang sudah berjalan.
- Jangan menyalin fitur spesifik reimbursement tanpa alasan dari fitur target.
- Jangan menambah fitur di luar pola (paralel, kuorum, revisi, delegasi) kecuali diminta.
- Jangan menjalankan migrasi pada database produksi.
```

### 12.2 Prompt Ringkas - bila fitur target sederhana

```text
Terapkan approval bertingkat pada fitur ⟦NAMA FITUR⟧ di EcoSystem-2 persis mengikuti pola
Reimbursement. Baca @docs/approval-bertingkat.md terlebih dahulu (konsep, R1-R12, Bagian 11
checklist), lalu pelajari kode acuannya (ReimbursementService, ReimbursementApprovalStep,
ReimbursementRequestApproval, ReimbursementSettingController::syncSteps, ApprovalEngine).
Pemohon: ⟦...⟧. Dokumen: ⟦...⟧. Tanpa fitur khusus reimbursement (tanda tangan, periode
terkunci, batas nominal, pembayaran).
Mulai dengan rencana (file, skema tabel, slug, asumsi) dan tunggu konfirmasi sebelum menulis
kode. Setelah itu implementasikan seluruh checklist, tambahkan tes, dan laporkan hasil apa
adanya.
```

### 12.3 Prompt Audit - verifikasi penerapan yang sudah ada

```text
Audit penerapan approval bertingkat pada fitur ⟦NAMA FITUR⟧ terhadap
@docs/approval-bertingkat.md. Jangan mengubah kode. Untuk setiap aturan R1-R12 dan setiap
item Checklist Bagian 11, nyatakan: SESUAI / MENYIMPANG / TIDAK ADA, sertakan file:baris
sebagai bukti, dan jelaskan dampak bila menyimpang. Periksa khususnya:
(1) snapshot disalin saat submit dan tak pernah dibaca langsung dari konfigurasi,
(2) submit ditolak bila tanpa langkah aktif sebelum dokumen dibuat,
(3) reject menandai langkah sisa 'skipped' dan menutup dokumen,
(4) canAct() adalah satu-satunya penentu kewenangan,
(5) employee_id dari sesi,
(6) transaksi pada submit/approve/reject/syncSteps,
(7) notifikasi dibungkus try/catch,
(8) fitur terdaftar di ApprovalEngine::MODULES, stepModel(), stepModuleKey(),
(9) urutan rute statis vs /{param},
(10) syncSteps menolak payload kosong & tanpa langkah aktif.
Akhiri dengan daftar temuan berurut berdasarkan tingkat keparahan.
```

### 12.4 Prompt Variasi - penyetuju dinamis (atasan / pemimpin proyek)

```text
Fitur ⟦NAMA FITUR⟧ sudah memakai approval bertingkat sesuai @docs/approval-bertingkat.md.
Tambahkan dukungan penyetuju dinamis: atasan langsung pemohon (direct_manager) dan pemimpin
proyek (project_lead) dengan role sebagai fallback, meniru implementasi modul Overtime:
OvertimeService, OvertimeApprovalStep, App\Services\Approval\ApproverResolver, dan editor
workflow dengan 'dynamic' => true.
Mulai dengan analisis: bagaimana Overtime meresolusi penyetuju saat submit dan menyimpannya
pada snapshot, apa yang terjadi saat tidak ada atasan/proyek, dan bagaimana ApprovalEngine
menangani tipe ini. Verifikasi dari kode, jangan berasumsi. Tampilkan rencana, tunggu
konfirmasi, lalu implementasikan dengan tes.
```

---

## 13. Kerangka Kode (Skeleton)

> Skeleton ini **disederhanakan** dari kode Reimbursement dan memakai placeholder: `{X}` = nama kelas (mis. `Leave`), `{x}` = nama tabel (mis. `leave`). Gunakan sebagai titik awal; selalu rujuk file acuan asli untuk detailnya.

### 13.1 Migrasi konfigurasi

```php
Schema::create('{x}_approval_steps', function (Blueprint $table) {
    $table->id();
    $table->string('module', 30)->default('{x}');
    $table->unsignedTinyInteger('order_seq')->default(1);
    $table->string('name', 100);
    $table->string('approver_type', 20)->default('role');            // role | employee | direct_manager
    $table->unsignedBigInteger('approver_role_id')->nullable();
    $table->json('approver_employee_ids')->nullable();
    $table->boolean('requester_selectable')->default(false);
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->foreign('approver_role_id')->references('id')->on('employee_role')->nullOnDelete();
    $table->index(['module', 'order_seq'], '{x}_steps_module_order_idx');
});

DB::table('{x}_approval_steps')->insert([
    'module' => '{x}', 'order_seq' => 1, 'name' => 'Approval',
    'approver_type' => 'role', 'approver_role_id' => 1, 'is_active' => true,
    'created_at' => now(), 'updated_at' => now(),
]);
```

### 13.2 Migrasi riwayat

```php
Schema::create('{x}_request_approvals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('{x}_request_id')->constrained('{x}_requests')->onDelete('cascade');
    $table->unsignedTinyInteger('order_seq');

    // Salinan definisi saat dokumen dibuat
    $table->string('step_name', 100);
    $table->string('approver_type', 20);
    $table->unsignedBigInteger('approver_role_id')->nullable();
    $table->json('approver_employee_ids')->nullable();

    // Hasil
    $table->string('status', 20)->default('waiting');   // waiting|approved|rejected|skipped
    $table->unsignedBigInteger('acted_by')->nullable();
    $table->timestamp('acted_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->foreign('acted_by')->references('employee_id')->on('employee')->nullOnDelete();
    $table->unique(['{x}_request_id', 'order_seq'], '{x}_approval_request_order_unique');
    $table->index(['status'], '{x}_approval_status_idx');
});
```

Pada tabel dokumen: `status` string(20) default `submitted` (+ index), `current_step_order` unsignedTinyInteger nullable, `completed_at` timestamp nullable, `flags` json nullable.

### 13.3 Model snapshot - `allows()`

```php
public function allows(int $employeeId, array $roleIds): bool
{
    return match ($this->approver_type) {
        {X}ApprovalStep::TYPE_ROLE
            => $this->approver_role_id !== null && in_array((int) $this->approver_role_id, $roleIds, true),
        {X}ApprovalStep::TYPE_EMPLOYEE
            => in_array($employeeId, array_map('intval', $this->approver_employee_ids ?? []), true),
        {X}ApprovalStep::TYPE_DIRECT_MANAGER => false,   // eksplisit: belum dapat dijalankan
        default => false,
    };
}
```

### 13.4 Model dokumen - inti

```php
public const OPEN_STATUSES = [self::STATUS_SUBMITTED, self::STATUS_IN_REVIEW];

public function approvals()
{
    return $this->hasMany({X}RequestApproval::class, '{x}_request_id', 'id')->orderBy('order_seq');
}

public function isOpen(): bool
{
    return in_array($this->status, self::OPEN_STATUSES, true);
}

public function currentApproval(): ?{X}RequestApproval
{
    if (!$this->isOpen() || $this->current_step_order === null) return null;
    return $this->approvals->firstWhere('order_seq', $this->current_step_order);
}
```

### 13.5 Service - `approve()` / `reject()` inti

```php
public function approve({X}Request $request, int $actorId, array $payload = [], bool $canManage = false): array
{
    $gate = $this->canAct($request, $actorId, $canManage);
    if (!$gate['allowed']) return ['allowed' => false, 'reason' => $gate['reason'], 'completed' => false];

    $approval = $gate['approval'];

    $completed = DB::transaction(function () use ($request, $approval, $actorId, $payload) {
        $approval->update([
            'status' => {X}RequestApproval::STATUS_APPROVED,
            'acted_by' => $actorId, 'acted_at' => now(), 'notes' => $payload['notes'] ?? null,
        ]);

        $next = $request->approvals()
            ->where('order_seq', '>', $approval->order_seq)
            ->where('status', {X}RequestApproval::STATUS_WAITING)
            ->orderBy('order_seq')->first();

        if ($next) {
            $request->update(['status' => {X}Request::STATUS_IN_REVIEW, 'current_step_order' => $next->order_seq]);
            return false;
        }

        $request->update([
            'status' => {X}Request::STATUS_APPROVED, 'current_step_order' => null, 'completed_at' => now(),
        ]);
        return true;
    });

    $this->notify($request, $completed ? 'approved' : 'progressed', $payload['notes'] ?? null);
    if (!$completed) {
        $next = $request->fresh('approvals')->currentApproval();
        if ($next) $this->notifyApprovers($request, $next, false);
    }

    return ['allowed' => true, 'reason' => '', 'completed' => $completed];
}

public function reject({X}Request $request, int $actorId, string $notes, bool $canManage = false): array
{
    $gate = $this->canAct($request, $actorId, $canManage);
    if (!$gate['allowed']) return ['allowed' => false, 'reason' => $gate['reason']];

    $approval = $gate['approval'];

    DB::transaction(function () use ($request, $approval, $actorId, $notes) {
        $approval->update([
            'status' => {X}RequestApproval::STATUS_REJECTED,
            'acted_by' => $actorId, 'acted_at' => now(), 'notes' => $notes,
        ]);

        $request->approvals()
            ->where('order_seq', '>', $approval->order_seq)
            ->where('status', {X}RequestApproval::STATUS_WAITING)
            ->update(['status' => {X}RequestApproval::STATUS_SKIPPED]);

        $request->update([
            'status' => {X}Request::STATUS_REJECTED, 'current_step_order' => null, 'completed_at' => now(),
        ]);
    });

    $this->notify($request, 'rejected', $notes);
    return ['allowed' => true, 'reason' => ''];
}
```

### 13.6 Registrasi di `ApprovalEngine`

```php
// MODULES
'{module-key}' => [
    'label' => '{Label}', 'approval' => {X}RequestApproval::class, 'relation' => 'request',
    'fk' => '{x}_request_id', 'number' => 'request_no',
    'route' => 'general.{x}.show', 'param' => '{x}Request',
    'document' => {X}Request::class, 'controller' => \App\Http\Controllers\HR_General\{X}Controller::class,
],

// stepModel()
'{module-key}' => \App\Models\{X}\{X}ApprovalStep::class,

// stepModuleKey() - hanya bila nilai kolom `module` berbeda dari key (mis. memakai underscore)
```

### 13.7 Migrasi slug menu

```php
return new class extends Migration {
    private const PAGES = ['general.approval-workflow.{module}' => 'Approval Workflow - {Label}'];

    public function up(): void   { MenuRegistrar::register('general', self::PAGES, 120, 'page'); }
    public function down(): void { MenuRegistrar::remove(array_keys(self::PAGES)); }
};
```

---

## 14. Strategi Pengujian

Gunakan `tests/Feature/Approval/ApprovalEngineTest.php` sebagai pola (SQLite in-memory dengan tabel minimal; tes dilewati bila `pdo_sqlite` tidak aktif) dan `tests/Feature/Reimbursement/*` untuk tes service.

Skenario minimal:

| # | Skenario | Yang diverifikasi |
|---|----------|-------------------|
| 1 | Submit dengan 2 langkah | Snapshot 2 baris `waiting`; dokumen `submitted`; `current_step_order = 1` |
| 2 | Submit tanpa langkah aktif | Ditolak, **tidak ada** dokumen dibuat |
| 3 | Approve langkah 1 | Dokumen `in_review`, `current_step_order = 2`; notifikasi ke penyetuju langkah 2 |
| 4 | Approve langkah terakhir | Dokumen `approved`, `current_step_order = null`, `completed_at` terisi |
| 5 | Reject di langkah 1 dari 3 | Dokumen `rejected`; langkah 2-3 `skipped` |
| 6 | Orang di luar langkah mencoba approve | `canAct` → ditolak dengan pesan "not an approver for" |
| 7 | Approve ganda / dokumen sudah tertutup | Ditolak ("already …") |
| 8 | Self-approval dimatikan | Pemohon yang juga penyetuju ditolak; dihidupkan → lolos + flag |
| 9 | Ubah konfigurasi (hapus/ganti langkah) saat dokumen berjalan | Snapshot dokumen **tidak berubah** (R1) |
| 10 | `applyStepToOpenRequests` | Hanya dokumen terbuka & posisi sebelum langkah baru; tanpa duplikat `order_seq`; flag `workflow_extended` |
| 11 | Langkah *requester-selectable* | Submit tanpa pilihan ditolak; pilihan di luar kandidat ditolak; snapshot terkunci ke satu `employee` |
| 12 | `syncSteps` | Payload kosong & tanpa langkah aktif ditolak; urutan ulang tidak bentrok; baris hilang terhapus |
| 13 | Engine | `waitingFor`, `participates`, `inbox` untuk penyetuju tanpa slug; orang asing → `participates = false` |
| 14 | Middleware | Penyetuju ditunjuk membuka dokumennya **tetapi tidak** dokumen orang lain |

Perintah (sesuaikan dengan env Laragon):

```powershell
php artisan test --filter=Approval
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/Approval
```

---

## 15. Jebakan Umum (Pitfalls)

| # | Jebakan | Pencegahan |
|---|---------|-----------|
| 1 | **Membaca konfigurasi langsung saat memproses** dokumen berjalan | Selalu proses dari snapshot (R1). |
| 2 | Dokumen dibuat **sebelum** memeriksa ada tidaknya langkah aktif | Periksa `activeSteps()` di awal `submit()`. |
| 3 | Rute `/{doc}` menimpa `/settings`, `/create`, `/dashboard` | Daftarkan rute statis lebih dulu. |
| 4 | `order_seq` bentrok saat mengurutkan ulang (unique) | Pindahkan dulu ke rentang sementara (200+) dalam transaksi. |
| 5 | TINYINT overflow pada `order_seq` | Jaga jumlah langkah kecil; gunakan `smallInteger` bila perlu lebih banyak (konsisten di kedua tabel). |
| 6 | Mengandalkan slug menu untuk menentukan "boleh approve dokumen ini" | Slug hanya membuka pintu; `canAct()` memutuskan (R5). |
| 7 | Checkbox tak dicentang **tidak dikirim** browser | Tetapkan `request->boolean()` secara eksplisit di controller. |
| 8 | Notifikasi gagal membatalkan approve | Bungkus try/catch + log (R9). |
| 9 | Pemohon dikecualikan dari notifikasi padahal self-approval diizinkan | Samakan kondisinya dengan gerbang di `canAct()`. |
| 10 | Langkah sisa dibiarkan `waiting` setelah reject | Ubah ke `skipped` (R3). |
| 11 | Menyalin fitur spesifik reimbursement (tanda tangan, periode terkunci, nominal) | Salin hanya bila fitur target membutuhkannya. |
| 12 | Cache setting per-request tak dibersihkan setelah update | Panggil `forgetCache()` seperti `ReimbursementSettingController::update`. |
| 13 | Lupa mendaftarkan fitur ke `ApprovalEngine` | Inbox & akses penyetuju tanpa slug tidak akan berfungsi. |
| 14 | `show()` controller modul tidak kompatibel dengan inbox | Parameter harus bernama sama dengan `MODULES[...]['param']`. |
| 15 | Mengaktifkan `direct_manager` tanpa hierarki atasan yang valid | Verifikasi data atasan; ikuti pola Overtime + `ApproverResolver`. |

---

## 16. Lampiran: Peta File Acuan

| Kategori | Path |
|----------|------|
| Service | `app/Services/Reimbursement/ReimbursementService.php` |
| Engine | `app/Services/Approval/ApprovalEngine.php`, `app/Services/Approval/ApproverResolver.php` |
| Penerima notifikasi | `app/Support/ApprovalRecipients.php` |
| Model | `app/Models/Reimbursement/{ReimbursementApprovalStep,ReimbursementRequestApproval,ReimbursementRequest,ReimbursementSetting}.php` |
| Controller | `app/Http/Controllers/HR_General/{ReimbursementController,ReimbursementSettingController,MyReimbursementController,ApprovalInboxController}.php`, `app/Http/Controllers/Controller.php` (`mayDecide`, `approverMay`) |
| Middleware | `app/Http/Middleware/CheckMenuOrDesignatedApprover.php` |
| Routes | `routes/hr-general.php` |
| Migrasi | `database/migrations/2026_08_24_000003_create_reimbursement_approval_steps_table.php`, `…_000006_create_reimbursement_request_approvals_table.php`, `…_000004_create_reimbursement_requests_table.php`, `2026_10_13_000003_reimbursement_steps_sign_as.php`, `2026_09_16_000001_add_approval_workflow_menus.php` |
| View editor | `resources/views/hr-general/partials/approval-workflow/{editor,row,people-picker}.blade.php`, `resources/views/hr-general/reimbursement/components/{step-editor,step-choices}.blade.php` |
| View dokumen | `resources/views/hr-general/reimbursement/{show,submit,settings}.blade.php`, `resources/views/hr-general/approvals/` |
| Tes | `tests/Feature/Approval/ApprovalEngineTest.php`, `tests/Feature/Reimbursement/` |
| Modul pembanding | `app/Services/Overtime/OvertimeService.php`, `app/Services/PurchaseRequest/PurchaseRequestService.php`, `app/Services/CashAdvance/{CashAdvanceService,CashAdvanceReportService}.php` |
| Command | `app/Console/Commands/BackfillPendingApprovalNotifications.php` |

---

*Dokumen ini disusun dari pembacaan langsung kode sumber EcoSystem-2. Bagian yang bergantung pada perilaku runtime (mis. tampilan UI aktual, `MenuRegistrar`, isi `resources/views/hr-general/approvals/*`) tidak dijalankan/diuji saat penulisan; verifikasi pada lingkungan Anda sebelum dijadikan dasar keputusan.*
