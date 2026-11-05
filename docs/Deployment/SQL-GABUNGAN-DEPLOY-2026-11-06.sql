-- =====================================================================================================================
-- SQL GABUNGAN DEPLOY — 6 Nov 2026. SATU berkas untuk phpMyAdmin > database aplikasi > tab SQL > Go.
-- Empat bagian BERURUTAN (jangan diubah urutannya); masing-masing aman diulang, tanpa menghapus data, tanpa ubah struktur
-- selain menambah tabel/kolom baru yang boleh kosong:
--   BAGIAN 0  Compliance Management, My Compliance, Web Report (fitur rekan)  (9 tabel, data awal, 3 menu, izin EC Administrator)
--   BAGIAN 1  Impor kontrak lama + izin ekspor/impor template                  (kolom employee_contract.origin/imported_at/imported_by, 6 slug izin)
--   BAGIAN 2  Payroll membaca tarif dari Legal Basis                           (isi PTKP, TER, batas upah BPJS Kesehatan dari tabel Payroll; saklar = settings)
--   BAGIAN 3  Aturan lembur + pemilik aturan                                   (kolom legal_basis_register.owner_employee_id; pengali lembur; saklar = settings)
-- Setelah selesai: Ctrl+F5 di aplikasi. Perilaku Payroll TIDAK berubah sampai Anda memilih "Legal Basis" di Legal Basis > Settings.
-- Berkas terpisah (SQL-2026-11-02-..., SQL-2026-11-05-..., SQL-2026-11-06-...) isinya sama persis; pakai bila ingin per bagian.
-- =====================================================================================================================

-- ################################ BAGIAN 0: SQL-2026-11-02-compliance-dan-web-report.sql ################################
-- =====================================================================================================================
-- SQL — COMPLIANCE MANAGEMENT, MY COMPLIANCE, WEB REPORT (fitur rekan; padanan migrasi 2026_11_02_000001_create_compliance_and_web_report)
-- Untuk server tanpa terminal: phpMyAdmin > database aplikasi > tab SQL > Go. AMAN DIULANG (tabel/data yang sudah ada dilewati).
-- Jalankan HANYA bila server belum punya tabel compliance (cek: SHOW TABLES LIKE 'compliance_%'); bila sudah ada, aman tetap dijalankan.
--   1. 9 tabel: compliance_categories/articles/rules/alerts/sessions/report_statuses/options, web_reports, web_report_events.
--   2. Data awal (status laporan dan pilihan dropdown) dari ComplianceDefaultsSeeder; hanya bila belum ada.
--   3. 3 menu: Compliance Management (di bawah Legal), Web Report Management (di bawah Control Center), My Compliance (menu utama).
--      Izin awal HANYA EC Administrator (View); pemilik sistem membagikannya lewat Control Center > Menu Access.
--   4. Mencatat migrasi di tabel migrations.
-- Tidak ada baris yang dihapus. Konten (artikel, aturan, sesi, kategori) diisi lewat menu Compliance Management.
-- =====================================================================================================================

/*!50503 SET NAMES utf8mb4 */;
-- 1. compliance_categories
CREATE TABLE IF NOT EXISTS `compliance_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#0F7B6C',
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `compliance_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1. compliance_articles
CREATE TABLE IF NOT EXISTS `compliance_articles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `title` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `tags` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `views` int unsigned NOT NULL DEFAULT '0',
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compliance_articles_category_id_foreign` (`category_id`),
  KEY `idx_comp_art_active` (`is_active`,`is_featured`),
  CONSTRAINT `compliance_articles_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `compliance_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1. compliance_rules
CREATE TABLE IF NOT EXISTS `compliance_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned DEFAULT NULL,
  `type` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `severity` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compliance_rules_category_id_foreign` (`category_id`),
  CONSTRAINT `compliance_rules_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `compliance_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1. compliance_alerts
CREATE TABLE IF NOT EXISTS `compliance_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `icon` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1. compliance_sessions
CREATE TABLE IF NOT EXISTS `compliance_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `session_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `duration_min` smallint unsigned NOT NULL DEFAULT '30',
  `session_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'morning',
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'upcoming',
  `facilitator` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `meeting_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recording_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comp_sess` (`status`,`session_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1. compliance_report_statuses
CREATE TABLE IF NOT EXISTS `compliance_report_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tone` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gray',
  `is_final` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `compliance_report_statuses_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1. compliance_options
CREATE TABLE IF NOT EXISTS `compliance_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `list` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tone` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gray',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `compliance_options_list_key_unique` (`list`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1. web_reports
CREATE TABLE IF NOT EXISTS `web_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tracking_code` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'web',
  `employee_id` bigint unsigned DEFAULT NULL,
  `reporter_name` text COLLATE utf8mb4_unicode_ci,
  `reporter_contact` text COLLATE utf8mb4_unicode_ci,
  `reported_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `chronology` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `evidence_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `evidence_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `internal_note` text COLLATE utf8mb4_unicode_ci,
  `public_note` text COLLATE utf8mb4_unicode_ci,
  `lang` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `due_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `web_reports_tracking_code_unique` (`tracking_code`),
  KEY `idx_wb_status` (`status`,`created_at`),
  KEY `idx_wr_channel` (`channel`,`status`,`created_at`),
  KEY `idx_wr_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1. web_report_events
CREATE TABLE IF NOT EXISTS `web_report_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `action` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `web_report_events_report_id_foreign` (`report_id`),
  CONSTRAINT `web_report_events_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `web_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Data awal compliance_report_statuses (4 baris; hanya yang belum ada)
INSERT INTO `compliance_report_statuses` (`key`, `label`, `tone`, `is_final`, `is_active`, `sort_order`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'new' AS `key`, 'New' AS `label`, 'red' AS `tone`, '0' AS `is_final`, '1' AS `is_active`, '1' AS `sort_order`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_report_statuses` x WHERE x.`key` = 'new');
INSERT INTO `compliance_report_statuses` (`key`, `label`, `tone`, `is_final`, `is_active`, `sort_order`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'in_review' AS `key`, 'In review' AS `label`, 'amber' AS `tone`, '0' AS `is_final`, '1' AS `is_active`, '2' AS `sort_order`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_report_statuses` x WHERE x.`key` = 'in_review');
INSERT INTO `compliance_report_statuses` (`key`, `label`, `tone`, `is_final`, `is_active`, `sort_order`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'investigating' AS `key`, 'Investigating' AS `label`, 'violet' AS `tone`, '0' AS `is_final`, '1' AS `is_active`, '3' AS `sort_order`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_report_statuses` x WHERE x.`key` = 'investigating');
INSERT INTO `compliance_report_statuses` (`key`, `label`, `tone`, `is_final`, `is_active`, `sort_order`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'closed' AS `key`, 'Closed' AS `label`, 'gray' AS `tone`, '1' AS `is_final`, '1' AS `is_active`, '4' AS `sort_order`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_report_statuses` x WHERE x.`key` = 'closed');

-- 2. Data awal compliance_options (11 baris; hanya yang belum ada)
INSERT INTO `compliance_options` (`list`, `key`, `label`, `tone`, `sort_order`, `is_active`, `is_system`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'rule_severity' AS `list`, 'info' AS `key`, 'Info' AS `label`, 'gray' AS `tone`, '1' AS `sort_order`, '1' AS `is_active`, '1' AS `is_system`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_options` x WHERE x.`list` = 'rule_severity' AND x.`key` = 'info');
INSERT INTO `compliance_options` (`list`, `key`, `label`, `tone`, `sort_order`, `is_active`, `is_system`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'rule_severity' AS `list`, 'warning' AS `key`, 'Warning' AS `label`, 'amber' AS `tone`, '2' AS `sort_order`, '1' AS `is_active`, '1' AS `is_system`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_options` x WHERE x.`list` = 'rule_severity' AND x.`key` = 'warning');
INSERT INTO `compliance_options` (`list`, `key`, `label`, `tone`, `sort_order`, `is_active`, `is_system`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'rule_severity' AS `list`, 'critical' AS `key`, 'Critical' AS `label`, 'red' AS `tone`, '3' AS `sort_order`, '1' AS `is_active`, '1' AS `is_system`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_options` x WHERE x.`list` = 'rule_severity' AND x.`key` = 'critical');
INSERT INTO `compliance_options` (`list`, `key`, `label`, `tone`, `sort_order`, `is_active`, `is_system`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'alert_color' AS `list`, 'danger' AS `key`, 'Red (danger)' AS `label`, 'red' AS `tone`, '1' AS `sort_order`, '1' AS `is_active`, '1' AS `is_system`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_options` x WHERE x.`list` = 'alert_color' AND x.`key` = 'danger');
INSERT INTO `compliance_options` (`list`, `key`, `label`, `tone`, `sort_order`, `is_active`, `is_system`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'alert_color' AS `list`, 'warning' AS `key`, 'Amber (warning)' AS `label`, 'amber' AS `tone`, '2' AS `sort_order`, '1' AS `is_active`, '1' AS `is_system`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_options` x WHERE x.`list` = 'alert_color' AND x.`key` = 'warning');
INSERT INTO `compliance_options` (`list`, `key`, `label`, `tone`, `sort_order`, `is_active`, `is_system`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'alert_color' AS `list`, 'info' AS `key`, 'Gray (info)' AS `label`, 'gray' AS `tone`, '3' AS `sort_order`, '1' AS `is_active`, '1' AS `is_system`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_options` x WHERE x.`list` = 'alert_color' AND x.`key` = 'info');
INSERT INTO `compliance_options` (`list`, `key`, `label`, `tone`, `sort_order`, `is_active`, `is_system`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'session_status' AS `list`, 'upcoming' AS `key`, 'Upcoming' AS `label`, 'violet' AS `tone`, '1' AS `sort_order`, '1' AS `is_active`, '1' AS `is_system`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_options` x WHERE x.`list` = 'session_status' AND x.`key` = 'upcoming');
INSERT INTO `compliance_options` (`list`, `key`, `label`, `tone`, `sort_order`, `is_active`, `is_system`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'session_status' AS `list`, 'completed' AS `key`, 'Completed' AS `label`, 'green' AS `tone`, '2' AS `sort_order`, '1' AS `is_active`, '1' AS `is_system`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_options` x WHERE x.`list` = 'session_status' AND x.`key` = 'completed');
INSERT INTO `compliance_options` (`list`, `key`, `label`, `tone`, `sort_order`, `is_active`, `is_system`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'session_status' AS `list`, 'cancelled' AS `key`, 'Cancelled' AS `label`, 'gray' AS `tone`, '3' AS `sort_order`, '1' AS `is_active`, '0' AS `is_system`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_options` x WHERE x.`list` = 'session_status' AND x.`key` = 'cancelled');
INSERT INTO `compliance_options` (`list`, `key`, `label`, `tone`, `sort_order`, `is_active`, `is_system`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'session_period' AS `list`, 'morning' AS `key`, 'Morning' AS `label`, 'amber' AS `tone`, '1' AS `sort_order`, '1' AS `is_active`, '1' AS `is_system`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_options` x WHERE x.`list` = 'session_period' AND x.`key` = 'morning');
INSERT INTO `compliance_options` (`list`, `key`, `label`, `tone`, `sort_order`, `is_active`, `is_system`, `created_at`, `updated_at`)
SELECT s.* FROM (SELECT 'session_period' AS `list`, 'afternoon' AS `key`, 'Afternoon' AS `label`, 'blue' AS `tone`, '2' AS `sort_order`, '1' AS `is_active`, '1' AS `is_system`, '2026-11-05 11:33:14' AS `created_at`, '2026-11-05 11:33:14' AS `updated_at`) s
WHERE NOT EXISTS (SELECT 1 FROM `compliance_options` x WHERE x.`list` = 'session_period' AND x.`key` = 'afternoon');

-- 3. Menu (induk harus ada; bila belum, baris itu dilewati seperti di migrasi)
INSERT INTO `menu` (`parent_id`, `name`, `slug`, `type`, `order_seq`, `is_active`, `created_at`, `updated_at`)
SELECT p.`id`, 'Compliance Management', 'legal.compliance', 'page', 10, 1, NOW(), NOW() FROM `menu` p
WHERE p.`slug` = 'legal' AND NOT EXISTS (SELECT 1 FROM `menu` m WHERE m.`slug` = 'legal.compliance');

INSERT INTO `menu` (`parent_id`, `name`, `slug`, `type`, `order_seq`, `is_active`, `created_at`, `updated_at`)
SELECT p.`id`, 'Web Report Management', 'control-center.web-reports', 'page', 9, 1, NOW(), NOW() FROM `menu` p
WHERE p.`slug` = 'control-center' AND NOT EXISTS (SELECT 1 FROM `menu` m WHERE m.`slug` = 'control-center.web-reports');

-- My Compliance = menu utama (tanpa induk), urutan 7; dibuat hanya bila menu "general" ada
INSERT INTO `menu` (`parent_id`, `name`, `slug`, `type`, `order_seq`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, 'My Compliance', 'ess.my_compliance', 'page', 7, 1, NOW(), NOW() FROM `menu` p
WHERE p.`slug` = 'general' AND NOT EXISTS (SELECT 1 FROM `menu` m WHERE m.`slug` = 'ess.my_compliance') LIMIT 1;

-- Izin awal: HANYA EC Administrator (View)
INSERT INTO `role_menu` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `created_at`, `updated_at`)
SELECT r.`id`, m.`id`, 1, 0, 0, 0, NOW(), NOW()
FROM `employee_role` r JOIN `menu` m ON m.`slug` IN ('legal.compliance', 'control-center.web-reports', 'ess.my_compliance')
WHERE r.`name` = 'EC Administrator'
  AND NOT EXISTS (SELECT 1 FROM `role_menu` x WHERE x.`role_id` = r.`id` AND x.`menu_id` = m.`id`);

-- 4. Catat migrasi
INSERT INTO `migrations` (`migration`, `batch`)
SELECT t.`name`, (SELECT MAX(x.`batch`) FROM `migrations` x)
FROM (SELECT '2026_11_02_000001_create_compliance_and_web_report' AS `name`) t
WHERE NOT EXISTS (SELECT 1 FROM `migrations` m WHERE m.`migration` = t.`name`);

-- Cek: SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND (table_name LIKE 'compliance\_%' OR table_name LIKE 'web\_report%');   -- 9

-- ################################ BAGIAN 1: SQL-2026-11-05-impor-kontrak-lama.sql ################################
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

-- 4. Izin ekspor / impor Template per format (padanan migrasi 2026_11_05_000002_contract_template_transfer_permissions).
--    Lahir aktif HANYA untuk EC Administrator; dibagikan ke role lain lewat Control Center > Menu Access.
INSERT INTO `menu` (`parent_id`, `name`, `slug`, `type`, `order_seq`, `is_active`, `created_at`, `updated_at`)
SELECT p.`id`, v.`name`, v.`slug`, 'function', v.`seq`, 1, NOW(), NOW()
FROM `menu` p
JOIN (
  SELECT 'general.contracts.templates.export-pdf'  AS `slug`, 'Template export — PDF'                    AS `name`, 13 AS `seq`
  UNION ALL SELECT 'general.contracts.templates.export-html', 'Template export — HTML (editable)',          14
  UNION ALL SELECT 'general.contracts.templates.export-json', 'Template export — backup file (.json)',     15
  UNION ALL SELECT 'general.contracts.templates.import-html', 'Template import — HTML file',               16
  UNION ALL SELECT 'general.contracts.templates.import-json', 'Template import — backup file (.json)',     17
) v ON 1 = 1
WHERE p.`slug` = 'general.contracts'
  AND NOT EXISTS (SELECT 1 FROM `menu` m WHERE m.`slug` = v.`slug`);

INSERT INTO `role_menu` (`role_id`, `menu_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `created_at`, `updated_at`)
SELECT r.`id`, m.`id`, 1, 0, 0, 0, NOW(), NOW()
FROM `employee_role` r
JOIN `menu` m ON m.`slug` LIKE 'general.contracts.templates.%port-%'
WHERE r.`name` = 'EC Administrator'
  AND NOT EXISTS (SELECT 1 FROM `role_menu` x WHERE x.`role_id` = r.`id` AND x.`menu_id` = m.`id`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT t.`name`, (SELECT MAX(x.`batch`) FROM `migrations` x)
FROM (SELECT '2026_11_05_000002_contract_template_transfer_permissions' AS `name`) t
WHERE NOT EXISTS (SELECT 1 FROM `migrations` m WHERE m.`migration` = t.`name`);

-- ################################ BAGIAN 2: SQL-2026-11-06-payroll-satu-sumber-legal-basis.sql ################################
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

-- ################################ BAGIAN 3: SQL-2026-11-06-lembur-dan-pemilik-aturan.sql ################################
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
