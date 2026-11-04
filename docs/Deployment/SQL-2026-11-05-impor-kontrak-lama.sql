-- =====================================================================================================================
-- SQL 5 Nov 2026 — IMPOR KONTRAK YANG SUDAH BERLAKU (Contract › halaman karyawan › "Add existing contract")
-- Padanan migrasi 2026_11_05_000001_existing_contract_import (untuk server tanpa terminal; jalankan lewat phpMyAdmin > SQL).
--
-- Isi (semuanya aman diulang; yang sudah ada dilewati):
--   1. Tabel employee_contract: kolom origin (bawaan 'system'), imported_at, imported_by. Kontrak yang ada TIDAK berubah.
--   2. Slug izin "Import Existing Contract" (general.contracts.import) di bawah menu Contract, aktif HANYA untuk EC Administrator.
--      Pemilik sistem membagikannya ke role HR lewat Control Center > Menu Access (centang View; Create/Edit/Delete mengikuti
--      kotak menu Contracts).
--   3. Mencatat migrasi di tabel "migrations" agar "php artisan migrate" kelak tidak mengulanginya.
-- Tidak ada baris yang dihapus atau dikosongkan.
--
-- Setelah menjalankan: keluar-masuk (logout/login) akun yang diuji, atau kosongkan cache izin bila aplikasi memakai cache database:
--   DELETE FROM `cache` WHERE `key` LIKE '%perm_%';     -- opsional; lewati bila tabel "cache" tidak ada
-- Cek:
--   SELECT COUNT(*) AS kolom_baru FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'employee_contract' AND column_name IN ('origin','imported_at','imported_by');   -- 3
--   SELECT id, slug FROM menu WHERE slug = 'general.contracts.import';                                                                                                                          -- 1 baris
--   SELECT origin, COUNT(*) FROM employee_contract GROUP BY origin;                                                                                                                              -- semua 'system'
-- =====================================================================================================================

/*!50503 SET NAMES utf8mb4 */;

-- 1. Kolom baru di employee_contract
SET @has_origin := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'employee_contract' AND column_name = 'origin');
SET @ddl := IF(@has_origin = 0,
  'ALTER TABLE `employee_contract` ADD COLUMN `origin` VARCHAR(20) NOT NULL DEFAULT ''system'', ADD COLUMN `imported_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `imported_by` BIGINT UNSIGNED NULL DEFAULT NULL',
  'SELECT ''kolom origin sudah ada - dilewati'' AS info');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Slug izin di bawah menu Contract (dilewati bila menu induk belum ada atau slug sudah terdaftar)
INSERT INTO `menu` (`parent_id`, `name`, `slug`, `type`, `order_seq`, `is_active`, `created_at`, `updated_at`)
SELECT p.`id`, 'Import Existing Contract', 'general.contracts.import', 'function', 12, 1, NOW(), NOW()
FROM `menu` p
WHERE p.`slug` = 'general.contracts'
  AND NOT EXISTS (SELECT 1 FROM `menu` m WHERE m.`slug` = 'general.contracts.import');

-- Izin awal: hanya EC Administrator (View). Role lain diberi oleh pemilik sistem di Menu Access.
INSERT INTO `role_menu` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `created_at`, `updated_at`)
SELECT r.`id`, m.`id`, 1, 0, 0, 0, NOW(), NOW()
FROM `employee_role` r
JOIN `menu` m ON m.`slug` = 'general.contracts.import'
WHERE r.`name` = 'EC Administrator'
  AND NOT EXISTS (SELECT 1 FROM `role_menu` x WHERE x.`role_id` = r.`id` AND x.`menu_id` = m.`id`);

-- 3. Catat migrasi
INSERT INTO `migrations` (`migration`, `batch`)
SELECT t.`name`, (SELECT MAX(x.`batch`) FROM `migrations` x)
FROM (SELECT '2026_11_05_000001_existing_contract_import' AS `name`) t
WHERE NOT EXISTS (SELECT 1 FROM `migrations` m WHERE m.`migration` = t.`name`);
