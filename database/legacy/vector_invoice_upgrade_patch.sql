-- Vector Invoice Google Drive Storage Patch
-- Jalankan satu kali pada database lama sebelum memakai fitur Drive Storage.

ALTER TABLE `design_types`
  ADD COLUMN `preview_storage` enum('local','drive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local' AFTER `preview_image`,
  ADD COLUMN `preview_drive_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `preview_storage`,
  ADD COLUMN `preview_drive_url` text COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `preview_drive_id`;

ALTER TABLE `order_files`
  ADD COLUMN `storage` enum('local','drive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local' AFTER `original_name`,
  ADD COLUMN `drive_file_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `storage`,
  ADD COLUMN `drive_url` text COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `drive_file_id`;

UPDATE `design_types`
SET `preview_storage` = 'local'
WHERE `preview_storage` IS NULL OR `preview_storage` = '';

UPDATE `order_files`
SET `storage` = 'local'
WHERE `storage` IS NULL OR `storage` = '';
