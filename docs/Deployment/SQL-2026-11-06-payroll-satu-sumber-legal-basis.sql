-- =====================================================================================================================
-- SQL 6 Nov 2026 — PAYROLL MEMBACA TARIF DARI LEGAL BASIS (satu sumber). Jalankan di phpMyAdmin > SQL (tanpa terminal).
--
-- TIDAK ADA perubahan struktur tabel. Hanya DATA, dan semuanya aman diulang (yang sudah terisi dilewati):
--   1. Saklar `payroll_rates_source` di app_configs = 'settings'  -> perilaku Payroll TIDAK berubah sampai Anda memilih "Legal Basis"
--      (Legal Basis > Settings > "Where Payroll reads its rates").
--   2. Aturan pph21.ptkp   : isi kolom payload (daftar kode PTKP) DARI tabel pph21_ptkp tahun terbaru.   Hanya bila payload masih kosong.
--   3. Aturan pph21.ter    : isi payload (tabel TER per kategori) DARI tabel pph21_ter_rates tahun terbaru. Hanya bila payload masih kosong.
--   4. Aturan bpjs.kesehatan: tambah "wage_cap" (batas upah) dan "min_base" DARI baris bpjs_settings terbaru. Hanya bila belum ada.
-- Aturan yang diisi dari tabel Payroll diberi status "Needs review" (perlu diulas manusia) dan catatan asal datanya.
-- Tidak ada baris yang dihapus. Tarif progresif, biaya jabatan, JHT, JP, dan JKM sudah ada di Legal Basis dan tidak disentuh.
--
-- Cek sesudahnya (lihat di akhir berkas), lalu di aplikasi: Legal Basis > Settings > kartu "Where Payroll reads its rates" menampilkan
-- apakah semua aturan siap dan perbandingan dengan Payroll Settings. Baru setelah itu pilih "Legal Basis".
-- Butuh MySQL 5.7.22+ / MariaDB 10.5+ (JSON_OBJECT, JSON_SET). phpMyAdmin hosting umumnya sudah memenuhi.
-- =====================================================================================================================

/*!50503 SET NAMES utf8mb4 */;
SET SESSION group_concat_max_len = 1048576;   -- tabel TER panjang: batas bawaan 1024 karakter terlalu kecil

-- 1. Saklar sumber tarif (bawaan: Payroll Settings = perilaku lama)
INSERT INTO `app_configs` (`key`, `value`, `description`, `created_at`, `updated_at`)
SELECT 'payroll_rates_source', 'settings', 'Where Payroll reads PPh 21 / BPJS regulation rates: settings (Payroll Settings) or legal (Legal Basis)', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `app_configs` WHERE `key` = 'payroll_rates_source');

-- 2. PTKP dari pph21_ptkp (tahun terbaru)
UPDATE `legal_basis_register` r
SET r.`payload` = (
      SELECT CONCAT('{"codes":[', GROUP_CONCAT(JSON_OBJECT('code', p.`code`, 'description', p.`description`, 'annual_amount', p.`annual_amount` + 0) ORDER BY p.`id` SEPARATOR ','), ']}')
      FROM `pph21_ptkp` p WHERE p.`year` = (SELECT MAX(`year`) FROM `pph21_ptkp`)
    ),
    r.`status` = 'needs_review', r.`reviewed_by` = NULL, r.`reviewed_at` = NULL,
    r.`notes` = CONCAT_WS(' ', r.`notes`, CONCAT('[', DATE_FORMAT(NOW(), '%Y-%m-%d'), '] Values copied from the Payroll Settings PTKP table (tax year ', (SELECT MAX(`year`) FROM `pph21_ptkp`), '). Review against the regulation.')),
    r.`updated_at` = NOW()
WHERE r.`rule_key` = 'pph21.ptkp'
  AND (r.`payload` IS NULL OR r.`payload` = '')
  AND r.`id` = (SELECT x.`id` FROM (SELECT `id` FROM `legal_basis_register` WHERE `rule_key` = 'pph21.ptkp' AND `status` <> 'superseded' ORDER BY `effective_date` DESC, `id` DESC LIMIT 1) x)
  AND EXISTS (SELECT 1 FROM `pph21_ptkp`);

-- 3. Tabel TER dari pph21_ter_rates (tahun terbaru), dikelompokkan per kategori A/B/C. Tarif disimpan sebagai pecahan (0.25% = 0.0025).
UPDATE `legal_basis_register` r
SET r.`payload` = (
      SELECT CONCAT('{"categories":{', GROUP_CONCAT(CONCAT('"', c.`category`, '":[', c.`arr`, ']') ORDER BY c.`category` SEPARATOR ','), '}}')
      FROM (
        SELECT t.`category`,
               GROUP_CONCAT(JSON_OBJECT('gross_over', t.`gross_over` + 0, 'gross_upto', t.`gross_upto` + 0, 'rate', ROUND(t.`rate` / 100, 8)) ORDER BY t.`seq` SEPARATOR ',') AS `arr`
        FROM `pph21_ter_rates` t WHERE t.`year` = (SELECT MAX(`year`) FROM `pph21_ter_rates`) GROUP BY t.`category`
      ) c
    ),
    r.`status` = 'needs_review', r.`reviewed_by` = NULL, r.`reviewed_at` = NULL,
    r.`notes` = CONCAT_WS(' ', r.`notes`, CONCAT('[', DATE_FORMAT(NOW(), '%Y-%m-%d'), '] Values copied from the Payroll Settings TER table (tax year ', (SELECT MAX(`year`) FROM `pph21_ter_rates`), '). Verify with Accounting.')),
    r.`updated_at` = NOW()
WHERE r.`rule_key` = 'pph21.ter'
  AND (r.`payload` IS NULL OR r.`payload` = '')
  AND r.`id` = (SELECT x.`id` FROM (SELECT `id` FROM `legal_basis_register` WHERE `rule_key` = 'pph21.ter' AND `status` <> 'superseded' ORDER BY `effective_date` DESC, `id` DESC LIMIT 1) x)
  AND EXISTS (SELECT 1 FROM `pph21_ter_rates`);

-- 4. BPJS Kesehatan: batas upah dan upah minimum dari bpjs_settings terbaru (JP sudah punya "wage_cap" per versi)
UPDATE `legal_basis_register` r
SET r.`payload` = JSON_SET(r.`payload`,
        '$.wage_cap', (SELECT b.`health_cap` + 0 FROM `bpjs_settings` b ORDER BY b.`effective_date` DESC, b.`id` DESC LIMIT 1),
        '$.min_base', (SELECT b.`health_min_base` + 0 FROM `bpjs_settings` b ORDER BY b.`effective_date` DESC, b.`id` DESC LIMIT 1)),
    r.`status` = 'needs_review', r.`reviewed_by` = NULL, r.`reviewed_at` = NULL,
    r.`notes` = CONCAT_WS(' ', r.`notes`, CONCAT('[', DATE_FORMAT(NOW(), '%Y-%m-%d'), '] Wage ceiling copied from BPJS Settings. Review against the BPJS announcement.')),
    r.`updated_at` = NOW()
WHERE r.`rule_key` = 'bpjs.kesehatan'
  AND r.`payload` IS NOT NULL
  AND JSON_EXTRACT(r.`payload`, '$.wage_cap') IS NULL
  AND r.`id` = (SELECT x.`id` FROM (SELECT `id` FROM `legal_basis_register` WHERE `rule_key` = 'bpjs.kesehatan' AND `status` <> 'superseded' ORDER BY `effective_date` DESC, `id` DESC LIMIT 1) x)
  AND EXISTS (SELECT 1 FROM `bpjs_settings`);

-- ── Cek sesudah menjalankan ────────────────────────────────────────────────────────────────────────────────────
-- SELECT `key`, `value` FROM app_configs WHERE `key` = 'payroll_rates_source';                                        -- 'settings'
-- SELECT rule_key, status, JSON_LENGTH(JSON_EXTRACT(payload, '$.codes')) AS kode_ptkp FROM legal_basis_register WHERE rule_key = 'pph21.ptkp';       -- 8
-- SELECT rule_key, status, JSON_LENGTH(JSON_EXTRACT(payload, '$.categories.A')) AS baris_ter_A FROM legal_basis_register WHERE rule_key = 'pph21.ter'; -- jumlah baris kategori A
-- SELECT rule_key, JSON_EXTRACT(payload, '$.wage_cap') AS batas_upah FROM legal_basis_register WHERE rule_key = 'bpjs.kesehatan';                  -- 12000000
