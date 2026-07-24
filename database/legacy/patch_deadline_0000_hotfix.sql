-- ============================================================
-- HOTFIX DEADLINE 0000-00-00
-- Jalankan di phpMyAdmin jika database lama masih punya deadline 0000-00-00
-- dan halaman Dashboard menampilkan HTTP ERROR 500.
-- ============================================================

UPDATE `orders`
SET `deadline` = NULL
WHERE CAST(`deadline` AS CHAR) = '0000-00-00'
   OR CAST(`deadline` AS CHAR) = '';

ALTER TABLE `orders`
MODIFY `deadline` DATE NULL DEFAULT NULL;
