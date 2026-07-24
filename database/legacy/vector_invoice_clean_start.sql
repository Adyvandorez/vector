-- ============================================================
-- VECTOR INVOICE - CLEAN START DATABASE
-- ------------------------------------------------------------
-- SQL bersih untuk mulai ulang agar data tidak duplikat/rusak/tidak sinkron.
-- Isi data transaksi dikosongkan: clients, design_types, price_matrix,
-- orders, order_items, order_files, order_payments, revisions, invoices.
-- Yang dipertahankan hanya master body_parts dan 1 akun admin.
-- Setelah import, hubungkan ulang Google Drive lalu input/sinkron data baru.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

DROP TABLE IF EXISTS `ai_audit_logs`;
DROP TABLE IF EXISTS `ai_logs`;
DROP TABLE IF EXISTS `drive_oauth_tokens`;
DROP TABLE IF EXISTS `order_payments`;
DROP TABLE IF EXISTS `order_revisions`;
DROP TABLE IF EXISTS `order_files`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `price_matrix`;
DROP TABLE IF EXISTS `design_types`;
DROP TABLE IF EXISTS `body_parts`;
DROP TABLE IF EXISTS `clients`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `remember_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_expired` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_client_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `body_parts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `design_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preview_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preview_thumb` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preview_storage` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local',
  `preview_drive_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preview_drive_url` text COLLATE utf8mb4_unicode_ci,
  `source_file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_drive_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_drive_url` text COLLATE utf8mb4_unicode_ci,
  `source_size` bigint DEFAULT NULL,
  `source_mime` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_uploaded_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_design_name` (`name`),
  KEY `idx_active` (`is_active`),
  KEY `idx_name` (`name`),
  KEY `idx_preview_drive_id` (`preview_drive_id`),
  KEY `idx_source_drive_id` (`source_drive_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `price_matrix` (
  `id` int NOT NULL AUTO_INCREMENT,
  `design_type_id` int NOT NULL,
  `body_part_id` int NOT NULL,
  `base_price` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_price` (`design_type_id`,`body_part_id`),
  KEY `idx_design` (`design_type_id`),
  KEY `idx_body` (`body_part_id`),
  CONSTRAINT `fk_pm_body` FOREIGN KEY (`body_part_id`) REFERENCES `body_parts` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pm_design` FOREIGN KEY (`design_type_id`) REFERENCES `design_types` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` int NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `design_type_id` int DEFAULT NULL,
  `body_part_id` int DEFAULT NULL,
  `base_price` int NOT NULL DEFAULT '0',
  `addons` int NOT NULL DEFAULT '0',
  `revision_count` int NOT NULL DEFAULT '0',
  `revision_fee` int NOT NULL DEFAULT '0',
  `subtotal` int DEFAULT '0',
  `discount` int NOT NULL DEFAULT '0',
  `total` int NOT NULL DEFAULT '0',
  `paid` int NOT NULL DEFAULT '0',
  `status` enum('MASUK','PROSES','REVISI','SELESAI','LUNAS') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MASUK',
  `deadline` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_code` (`order_code`),
  KEY `idx_created` (`created_at`),
  KEY `idx_deadline` (`deadline`),
  KEY `idx_status` (`status`),
  KEY `idx_client` (`client_id`),
  KEY `fk_order_design` (`design_type_id`),
  KEY `fk_order_body` (`body_part_id`),
  CONSTRAINT `fk_order_body` FOREIGN KEY (`body_part_id`) REFERENCES `body_parts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_order_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_order_design` FOREIGN KEY (`design_type_id`) REFERENCES `design_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `design_type_id` int NOT NULL,
  `body_part_id` int NOT NULL,
  `qty` int NOT NULL DEFAULT '1',
  `price` int NOT NULL DEFAULT '0',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_design` (`design_type_id`),
  KEY `idx_body` (`body_part_id`),
  CONSTRAINT `fk_items_body` FOREIGN KEY (`body_part_id`) REFERENCES `body_parts` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_items_design` FOREIGN KEY (`design_type_id`) REFERENCES `design_types` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `file_type` enum('PREVIEW','FINAL','SOURCE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PREVIEW',
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumb_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local',
  `drive_file_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `drive_url` text COLLATE utf8mb4_unicode_ci,
  `file_size` bigint DEFAULT NULL,
  `mime_type` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_file` (`order_id`),
  KEY `idx_file_type` (`file_type`),
  KEY `idx_drive_file_id` (`drive_file_id`),
  CONSTRAINT `fk_files_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `amount` int NOT NULL DEFAULT '0',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` date NOT NULL,
  `source` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MANUAL',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_payments_order_id` (`order_id`),
  KEY `idx_payment_date` (`payment_date`),
  CONSTRAINT `fk_order_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_revisions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fee` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_rev` (`order_id`),
  KEY `idx_created_rev` (`created_at`),
  CONSTRAINT `fk_rev_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `idx_order_invoice` (`order_id`),
  CONSTRAINT `fk_inv_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ai_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prompt` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` text COLLATE utf8mb4_unicode_ci,
  `confirmed` tinyint(1) DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ai_audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `action_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `executed_by` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_action` (`action_type`),
  KEY `idx_target` (`target_type`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `drive_oauth_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token` text COLLATE utf8mb4_unicode_ci,
  `refresh_token` text COLLATE utf8mb4_unicode_ci,
  `token_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Bearer',
  `expires_at` int DEFAULT NULL,
  `scope` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `body_parts` (`id`, `name`) VALUES
(1, 'Close Up'),
(2, 'Half Body'),
(3, 'Full Body'),
(4, 'Lainnya');

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password_hash`, `is_active`, `last_login`, `created_at`, `remember_token`, `reset_token`, `reset_expired`) VALUES
(1, 'Ady_vandorez', 'Ady_vandorez', 'muhammadadimulyono@gmail.com', '$2y$12$SiL4EgLnXkqlprkypefhEe4AgAYByTywKzjKR8CmF7lw129.wUb4.', 1, NULL, NOW(), NULL, NULL, NULL);

ALTER TABLE `body_parts` AUTO_INCREMENT = 5;
ALTER TABLE `users` AUTO_INCREMENT = 2;

COMMIT;
