-- =====================================================================================================================
-- SQL 6 Nov 2026 (3) — ATURAN LEMBUR DI LEGAL BASIS + PEMILIK ATURAN. Jalankan di phpMyAdmin > SQL (tanpa terminal). Aman diulang.
--
--   1. legal_basis_register: kolom owner_employee_id (penanggung jawab aturan; boleh kosong). Padanan migrasi
--      2026_11_06_000001_legal_basis_rule_owner. Data lama tidak berubah.
--   2. Aturan overtime.multipliers: isi pengali per jam (hari kerja, libur 5 hari kerja, libur 6 hari kerja) sama dengan aturan baku
--      PP 35/2021, DITAMBAHKAN ke pembagi 173 yang sudah ada. Hanya bila belum terisi. Status menjadi "Needs review".
--   3. Saklar payroll_overtime_source = 'settings' (lembur memakai aturan baku di kode; PERILAKU TIDAK BERUBAH sampai Anda menyalakannya di
--      Legal Basis > Settings > "Overtime multipliers").
--   4. Mencatat migrasi di tabel migrations.
-- Tidak ada baris yang dihapus. Butuh MySQL 5.7.22+ / MariaDB 10.5+ (JSON_MERGE_PATCH, JSON_SET).
-- =====================================================================================================================

/*!50503 SET NAMES utf8mb4 */;

-- 1. Kolom pemilik aturan
SET @has_owner := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'legal_basis_register' AND column_name = 'owner_employee_id');
SET @ddl := IF(@has_owner = 0,
  'ALTER TABLE `legal_basis_register` ADD COLUMN `owner_employee_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `reviewed_at`, ADD INDEX `legal_basis_register_owner_employee_id_index` (`owner_employee_id`)',
  'SELECT ''kolom owner_employee_id sudah ada - dilewati'' AS info');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Pengali lembur (aturan baku PP 35/2021) pada versi aturan yang sedang berlaku; pembagi yang sudah ada dipertahankan
UPDATE `legal_basis_register` r
SET r.`payload` = CASE
        WHEN JSON_EXTRACT(COALESCE(r.`payload`, JSON_OBJECT()), '$.monthly_divisor') IS NULL
        THEN JSON_SET(JSON_MERGE_PATCH(COALESCE(r.`payload`, JSON_OBJECT()), '{"workday":[{"hours":1,"multiplier":1.5},{"hours":null,"multiplier":2}],"rest_day_5":[{"hours":8,"multiplier":2},{"hours":1,"multiplier":3},{"hours":null,"multiplier":4}],"rest_day_6":[{"hours":7,"multiplier":2},{"hours":1,"multiplier":3},{"hours":null,"multiplier":4}]}'), '$.monthly_divisor', 173)
        ELSE JSON_MERGE_PATCH(r.`payload`, '{"workday":[{"hours":1,"multiplier":1.5},{"hours":null,"multiplier":2}],"rest_day_5":[{"hours":8,"multiplier":2},{"hours":1,"multiplier":3},{"hours":null,"multiplier":4}],"rest_day_6":[{"hours":7,"multiplier":2},{"hours":1,"multiplier":3},{"hours":null,"multiplier":4}]}')
    END,
    r.`status` = 'needs_review', r.`reviewed_by` = NULL, r.`reviewed_at` = NULL, r.`updated_at` = NOW()
WHERE r.`rule_key` = 'overtime.multipliers'
  AND (r.`payload` IS NULL OR JSON_EXTRACT(r.`payload`, '$.workday') IS NULL)
  AND r.`id` = (SELECT x.`id` FROM (SELECT `id` FROM `legal_basis_register` WHERE `rule_key` = 'overtime.multipliers' AND `status` <> 'superseded' ORDER BY `effective_date` DESC, `id` DESC LIMIT 1) x);

-- 3. Saklar lembur (bawaan: aturan baku di kode)
INSERT INTO `app_configs` (`key`, `value`, `description`, `created_at`, `updated_at`)
SELECT 'payroll_overtime_source', 'settings', 'Where Payroll reads overtime multipliers: settings (built-in standard) or legal (Legal Basis overtime.multipliers)', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `app_configs` WHERE `key` = 'payroll_overtime_source');

-- 4. Catat migrasi
INSERT INTO `migrations` (`migration`, `batch`)
SELECT t.`name`, (SELECT MAX(x.`batch`) FROM `migrations` x)
FROM (SELECT '2026_11_06_000001_legal_basis_rule_owner' AS `name`) t
WHERE NOT EXISTS (SELECT 1 FROM `migrations` m WHERE m.`migration` = t.`name`);

-- ── Cek sesudah menjalankan ────────────────────────────────────────────────────────────────────────────────────
-- SELECT COUNT(*) AS kolom_owner FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'legal_basis_register' AND column_name = 'owner_employee_id';   -- 1
-- SELECT `key`, `value` FROM app_configs WHERE `key` IN ('payroll_rates_source', 'payroll_overtime_source');                                                                       -- keduanya 'settings'
-- SELECT JSON_EXTRACT(payload, '$.monthly_divisor') AS pembagi, JSON_LENGTH(JSON_EXTRACT(payload, '$.workday')) AS band_hari_kerja FROM legal_basis_register WHERE rule_key = 'overtime.multipliers';  -- 173, 2
