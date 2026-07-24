-- ============================================================
-- VECTOR INVOICE - PATCH LOCAL THUMBNAIL CACHE + CDR DRIVE BACKUP
-- Aman untuk database lama/menengah: kolom hanya ditambah jika belum ada.
-- ============================================================

DROP PROCEDURE IF EXISTS vi_add_column;
DELIMITER $$
CREATE PROCEDURE vi_add_column(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_sql TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @ddl = p_sql;
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL vi_add_column('design_types', 'preview_thumb', 'ALTER TABLE `design_types` ADD COLUMN `preview_thumb` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `preview_image`');
CALL vi_add_column('design_types', 'preview_storage', 'ALTER TABLE `design_types` ADD COLUMN `preview_storage` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''local'' AFTER `preview_thumb`');
CALL vi_add_column('design_types', 'preview_drive_id', 'ALTER TABLE `design_types` ADD COLUMN `preview_drive_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `preview_storage`');
CALL vi_add_column('design_types', 'preview_drive_url', 'ALTER TABLE `design_types` ADD COLUMN `preview_drive_url` text COLLATE utf8mb4_unicode_ci AFTER `preview_drive_id`');
CALL vi_add_column('design_types', 'source_file_name', 'ALTER TABLE `design_types` ADD COLUMN `source_file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `preview_drive_url`');
CALL vi_add_column('design_types', 'source_original_name', 'ALTER TABLE `design_types` ADD COLUMN `source_original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `source_file_name`');
CALL vi_add_column('design_types', 'source_drive_id', 'ALTER TABLE `design_types` ADD COLUMN `source_drive_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `source_original_name`');
CALL vi_add_column('design_types', 'source_drive_url', 'ALTER TABLE `design_types` ADD COLUMN `source_drive_url` text COLLATE utf8mb4_unicode_ci AFTER `source_drive_id`');
CALL vi_add_column('design_types', 'source_size', 'ALTER TABLE `design_types` ADD COLUMN `source_size` bigint DEFAULT NULL AFTER `source_drive_url`');
CALL vi_add_column('design_types', 'source_mime', 'ALTER TABLE `design_types` ADD COLUMN `source_mime` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `source_size`');
CALL vi_add_column('design_types', 'source_uploaded_at', 'ALTER TABLE `design_types` ADD COLUMN `source_uploaded_at` datetime DEFAULT NULL AFTER `source_mime`');

CALL vi_add_column('order_files', 'thumb_path', 'ALTER TABLE `order_files` ADD COLUMN `thumb_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `file_name`');
CALL vi_add_column('order_files', 'storage', 'ALTER TABLE `order_files` ADD COLUMN `storage` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''local'' AFTER `original_name`');
CALL vi_add_column('order_files', 'drive_file_id', 'ALTER TABLE `order_files` ADD COLUMN `drive_file_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `storage`');
CALL vi_add_column('order_files', 'drive_url', 'ALTER TABLE `order_files` ADD COLUMN `drive_url` text COLLATE utf8mb4_unicode_ci AFTER `drive_file_id`');
CALL vi_add_column('order_files', 'file_size', 'ALTER TABLE `order_files` ADD COLUMN `file_size` bigint DEFAULT NULL AFTER `drive_url`');
CALL vi_add_column('order_files', 'mime_type', 'ALTER TABLE `order_files` ADD COLUMN `mime_type` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `file_size`');

ALTER TABLE `order_files`
  MODIFY `file_type` enum('PREVIEW','FINAL','SOURCE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PREVIEW',
  MODIFY `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `order_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `amount` int NOT NULL DEFAULT '0',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` date NOT NULL,
  `source` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MANUAL',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_payments_order_id` (`order_id`),
  CONSTRAINT `fk_order_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_payments` (`order_id`, `amount`, `note`, `payment_date`, `source`, `created_at`)
SELECT o.id, LEAST(o.paid, o.total), 'Migrasi pembayaran lama', DATE(COALESCE(o.created_at, NOW())), 'MIGRATION', COALESCE(o.created_at, NOW())
FROM `orders` o
LEFT JOIN `order_payments` op ON op.order_id = o.id
WHERE o.paid > 0 AND op.id IS NULL;

CREATE TABLE IF NOT EXISTS `drive_oauth_tokens` (
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

UPDATE `design_types`
SET `preview_thumb` = CONCAT('assets/uploads/cache/designs/thumb_', SUBSTRING_INDEX(`preview_image`, '.', 1), '.webp')
WHERE `preview_image` IS NOT NULL AND `preview_image` != '' AND (`preview_thumb` IS NULL OR `preview_thumb` = '');

UPDATE `order_files`
SET `thumb_path` = CONCAT('assets/uploads/cache/previews/thumb_', SUBSTRING_INDEX(`file_name`, '.', 1), '.webp'),
    `mime_type` = CASE
      WHEN LOWER(`file_name`) LIKE '%.png' THEN 'image/png'
      WHEN LOWER(`file_name`) LIKE '%.webp' THEN 'image/webp'
      ELSE 'image/jpeg'
    END
WHERE `file_type` = 'PREVIEW' AND `file_name` IS NOT NULL AND `file_name` != '' AND (`thumb_path` IS NULL OR `thumb_path` = '');

UPDATE `orders` SET `status` = 'SELESAI' WHERE `status` = 'LUNAS';

UPDATE `users`
SET `name` = 'Muhammad Adi Mulyono',
    `username` = 'muhammadadimulyono@gmail.com',
    `email` = 'muhammadadimulyono@gmail.com',
    `password_hash` = '$2y$12$SiL4EgLnXkqlprkypefhEe4AgAYByTywKzjKR8CmF7lw129.wUb4.',
    `remember_token` = NULL,
    `reset_token` = NULL,
    `reset_expired` = NULL
WHERE `id` = 1;

DROP PROCEDURE IF EXISTS vi_add_column;
