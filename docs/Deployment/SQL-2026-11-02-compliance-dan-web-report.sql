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
