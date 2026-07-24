-- ============================================================
-- VECTOR INVOICE - GOOGLE DRIVE OAUTH PATCH
-- Jalankan patch ini jika kamu sudah memakai database lama/drive sebelumnya.
-- Setelah patch, buka Drive Storage lalu klik Hubungkan Google Drive.
-- ============================================================

CREATE TABLE IF NOT EXISTS `drive_oauth_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `provider` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'google_drive',
  `access_token` text COLLATE utf8mb4_unicode_ci,
  `refresh_token` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'Bearer',
  `expires_at` int DEFAULT NULL,
  `scope` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `amount` int NOT NULL DEFAULT '0',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `source` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MANUAL',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `design_types`
  ADD COLUMN IF NOT EXISTS `preview_storage` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local' AFTER `preview_image`,
  ADD COLUMN IF NOT EXISTS `preview_drive_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `preview_storage`,
  ADD COLUMN IF NOT EXISTS `preview_drive_url` text COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `preview_drive_id`;

ALTER TABLE `order_files`
  ADD COLUMN IF NOT EXISTS `storage` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local' AFTER `original_name`,
  ADD COLUMN IF NOT EXISTS `drive_file_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `storage`,
  ADD COLUMN IF NOT EXISTS `drive_url` text COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `drive_file_id`;

INSERT INTO `order_payments` (`order_id`, `amount`, `note`, `payment_date`, `source`, `created_at`)
SELECT o.id, o.paid, 'Migrasi pembayaran awal dari data lama', DATE(o.created_at), 'MIGRATION', NOW()
FROM `orders` o
WHERE o.paid > 0
  AND NOT EXISTS (SELECT 1 FROM `order_payments` op WHERE op.order_id = o.id);
