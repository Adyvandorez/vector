-- ============================================================
-- VECTOR INVOICE - PATCH UPGRADE IDE 1,2,4,5
-- Pakai file ini jika database lama sudah ada dan tidak ingin import ulang full SQL.
-- ============================================================

CREATE TABLE IF NOT EXISTS `order_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `amount` int NOT NULL DEFAULT '0',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` date NOT NULL,
  `source` enum('DP','MANUAL','AI','AI_WIZARD','MIGRATION') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MANUAL',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_payment` (`order_id`),
  KEY `idx_payment_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_payments` (`order_id`, `amount`, `note`, `payment_date`, `source`, `created_at`)
SELECT o.id, LEAST(o.paid, o.total), 'Migrasi pembayaran lama / DP', DATE(o.created_at), 'MIGRATION', o.created_at
FROM `orders` o
WHERE o.paid > 0
  AND NOT EXISTS (SELECT 1 FROM `order_payments` op WHERE op.order_id = o.id);

UPDATE `orders` SET `status` = 'SELESAI' WHERE `status` = 'LUNAS';

-- Constraint FK opsional. Jika sudah pernah dibuat, abaikan error duplicate constraint.
-- ALTER TABLE `order_payments` ADD CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
