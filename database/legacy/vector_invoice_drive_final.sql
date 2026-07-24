-- ============================================================
-- VECTOR INVOICE - UPGRADE FINAL (IDE 1, 2, 4, 5)
-- ------------------------------------------------------------
-- File ini memakai data asli yang sudah dimigrasikan ke fitur pembayaran bertahap.
-- Termasuk: AI Assistant lanjutan, payment history, pencarian prefix, upload image compression.
-- PERHATIAN: File ini berisi data client/order asli, jangan upload ke public root atau GitHub publik.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `ai_audit_logs`;
DROP TABLE IF EXISTS `ai_logs`;
DROP TABLE IF EXISTS `order_revisions`;
DROP TABLE IF EXISTS `order_payments`;
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

-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: Jun 13, 2026 at 06:15 AM
-- Server version: 8.0.45
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vector_invoice`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_audit_logs`
--

CREATE TABLE `ai_audit_logs` (
  `id` int NOT NULL,
  `action_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `executed_by` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_logs`
--

CREATE TABLE `ai_logs` (
  `id` int NOT NULL,
  `prompt` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` text COLLATE utf8mb4_unicode_ci,
  `confirmed` tinyint(1) DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `body_parts`
--

CREATE TABLE `body_parts` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `body_parts`
--

INSERT INTO `body_parts` (`id`, `name`) VALUES
(1, 'Close Up'),
(2, 'Half Body'),
(3, 'Full Body'),
(4, 'Lainnya');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `phone`, `notes`, `created_at`) VALUES
(1, 'Raditya Pratama', '085236222785', NULL, '2026-01-23 10:08:32'),
(2, 'Didisatrian', '+62 813-3050-2006', NULL, '2026-01-23 11:29:06'),
(3, 'Nuris Costumer', '+62 822-3241-8137', NULL, '2026-01-23 12:56:54'),
(4, 'Danil Costumer', '+62 823-3298-0843', NULL, '2026-01-23 13:14:37'),
(5, 'Gigih Costumer', '+62 822-3225-7711', NULL, '2026-01-23 13:18:08'),
(6, 'Dika Costumer', '+62 877-7788-8335', NULL, '2026-01-23 15:27:57'),
(7, 'Rivki Renaldi', '+62 877-7703-4442', NULL, '2026-01-23 15:34:06'),
(8, 'costumer tebuireng clouth', '+62 856-3361-517', NULL, '2026-01-23 16:10:20'),
(10, 'Firdaus Costumer', '+62 899-0317-008', NULL, '2026-01-24 08:25:59'),
(11, 'cvzd', '', NULL, '2026-01-27 06:46:30'),
(12, 'rodi', '', NULL, '2026-01-27 15:09:15'),
(13, 'Fernando Costumer', '+62 822-6184-0442', NULL, '2026-01-27 18:01:22'),
(14, 'Setya Perdana Costumer', '+62 851-7173-2603', NULL, '2026-01-27 18:05:26'),
(15, 'Ratna Nirmala', '-', NULL, '2026-02-07 19:47:36'),
(16, 'Rasid', '-', NULL, '2026-02-07 19:50:53'),
(17, 'Wahyu', '-', NULL, '2026-02-07 19:56:39'),
(18, 'Imunk', '-', NULL, '2026-02-07 20:05:32'),
(19, 'Yuhannah', '-', NULL, '2026-02-08 09:38:14'),
(20, 'Husnul', '-', NULL, '2026-02-08 09:45:00'),
(21, 'Hadi Hidayat', '-', NULL, '2026-02-08 21:05:58'),
(22, 'Wildan', '-', NULL, '2026-02-08 21:09:06'),
(23, 'Arina', '-', NULL, '2026-02-08 21:13:25'),
(24, 'Reski Ginanjar', '-', NULL, '2026-02-08 21:15:07'),
(25, 'Afton', '-', NULL, '2026-02-08 21:17:04'),
(26, 'Mudrikah', '-', NULL, '2026-02-08 21:21:18'),
(27, 'Bugis Costumer', '', NULL, '2026-02-08 21:24:40'),
(28, 'Febri', '-', NULL, '2026-02-08 21:26:13'),
(29, 'Wardi', '-', NULL, '2026-02-08 21:27:40'),
(30, 'Client 1', '-', NULL, '2026-02-08 21:31:57'),
(31, 'Iqbal', '-', NULL, '2026-02-08 21:37:55'),
(32, 'Dagingrobot Costumer', '-', NULL, '2026-02-08 22:25:03'),
(33, 'Client 2', '-', NULL, '2026-02-08 22:28:00'),
(34, 'Daros', '-', NULL, '2026-02-08 22:33:18'),
(35, 'Client 3', '-', NULL, '2026-02-08 22:38:17'),
(37, 'Firdaus', '+62 899-0317-008', NULL, '2026-02-22 09:38:49'),
(40, 'Firdaus Season 2', '+62 899-0317-008', NULL, '2026-02-24 15:25:04'),
(41, 'Firdaus Season 3', '+62 899-0317-008', NULL, '2026-02-24 15:47:02'),
(45, 'SMK Nurul Hasan', '+62 822-3241-8137', NULL, '2026-02-24 14:18:59'),
(58, 'tes', '-', NULL, '2026-02-26 02:20:20'),
(59, 'tes 2', '-', NULL, '2026-02-26 03:51:30'),
(60, 'Firman', '+62 812-8695-8775', NULL, '2026-04-16 19:18:39');

-- --------------------------------------------------------

--
-- Table structure for table `design_types`
--

CREATE TABLE `design_types` (
  `id` int NOT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preview_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preview_storage` enum('local','drive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local',
  `preview_drive_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preview_drive_url` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `design_types`
--

INSERT INTO `design_types` (`id`, `name`, `preview_image`, `is_active`, `created_at`) VALUES
(2, 'Vektor Perangkat Desa 1', '22c446f77d1081d0b94f6e2c3e369b1d.jpg', 1, '2026-01-23 11:17:39'),
(3, 'Vektor Perangkat Desa 2', '65b9dabaa86f67d21bc1d45f3dd23bcd.jpg', 1, '2026-01-23 11:17:53'),
(4, 'Vektor Perangkat Desa 3', '4dfaaedc997e8f2967a89e8b92b20e2b.jpg', 1, '2026-01-23 11:18:20'),
(5, 'Vektor Perangkat Desa 4', 'b4f51afb878da1c54f66dcb9fae39b06.jpg', 1, '2026-01-23 11:18:33'),
(6, 'Vektor Perangkat Desa 5', '55a2cd25c071b69b448097601ddf70fd.jpg', 1, '2026-01-23 11:18:49'),
(7, 'Vektor Perangkat Desa 6', 'dad6963be92aa55823b767f578cb175a.jpg', 1, '2026-01-23 11:19:04'),
(8, 'Vektor Perangkat Desa 7', '73bb4382c1c107037df3c7ef6ad0d2d9.jpg', 1, '2026-01-23 11:19:22'),
(9, 'Vektor Perangkat Desa 8', 'b28a68497c44588cd1f20355d7273f30.jpg', 1, '2026-01-23 11:19:40'),
(10, 'Vektor Perangkat Desa 9', 'be6974ff2b9a824ad11a21506f0a5d8d.jpg', 1, '2026-01-23 11:19:53'),
(11, 'Vektor Perangkat Desa 10', 'd44c885eee384c21aad8c007ab280bae.jpg', 1, '2026-01-23 11:20:07'),
(12, 'Vektor Perangkat Desa 11', 'a64e571f6144f1a0fbaae8382c188263.jpg', 1, '2026-01-23 11:20:24'),
(13, 'Vektor Perangkat Desa 12', 'e94011e9983aa70663141da3a69d81da.jpg', 1, '2026-01-23 11:20:39'),
(14, 'Vektor Perangkat Desa 13', 'e58c21ef168d11954f26f7e7f6156df8.jpg', 1, '2026-01-23 11:20:53'),
(15, 'Vektor Perangkat Desa 14', 'e8991867e204db5ff72d9d6b63be580d.jpg', 1, '2026-01-23 11:21:14'),
(17, 'Vektor Perangkat Desa 15', 'd4412dcf1adef179d33885ee9cc2ebef.jpg', 1, '2026-01-23 11:21:40'),
(18, 'Vektor Perangkat Desa 16', 'b9ac85a2a07d99c433d4836fa2fb3a15.jpg', 1, '2026-01-23 11:21:52'),
(19, 'Vektor Potrait 1', '41551e91c3306ff196b6de453db18ef8.jpg', 1, '2026-01-23 12:53:21'),
(20, 'Vektor Shiki Fujin', 'fdf728b1e7756719fb6d33618e79aed7.jpg', 1, '2026-01-23 13:07:14'),
(21, 'Vektor ASSB TEAM', '51c5a6c557183182544c4f1ce6626340.jpg', 1, '2026-01-23 13:12:38'),
(22, 'Vektor Potrait 2', '0fa8488e39a04fb113952e1c694ffb22.jpg', 1, '2026-01-23 13:16:36'),
(23, 'Vektor UPI 1', 'c18f72063a668d74c345301db33deebc.jpg', 1, '2026-01-23 14:54:30'),
(24, 'Vektor UPI 2', '331ba5144156f050e5b097374a605f0f.jpg', 1, '2026-01-23 14:54:43'),
(25, 'Vektor UPI 3', 'd66ea1c2f98487f0f475e2c2250ed3ef.jpg', 1, '2026-01-23 14:54:53'),
(26, 'Vektor UPI 4', '7e5953a093b70de8f876a558076ff5df.jpg', 1, '2026-01-23 14:55:04'),
(27, 'Vektor UPI 5', 'a28c5bce4287bdf9819a3b8e73c87b96.jpg', 1, '2026-01-23 14:55:15'),
(28, 'Vektor UPI 6', 'c4ad7f0f1bb62177597c185763fd892c.jpg', 1, '2026-01-23 14:55:28'),
(29, 'Vektor UPI 7', '3840343cc0b4c0c85c8ab999b88b2de7.jpg', 1, '2026-01-23 14:55:46'),
(30, 'Vektor UPI 8', '0d41d5d86eb8e821adcfb01f2ea5238e.jpg', 1, '2026-01-23 14:55:57'),
(31, 'Vektor Style Bola', '9ff7696a4602403a77b3467a9e8f314b.jpg', 1, '2026-01-23 15:32:54'),
(33, 'Vektor Gus Dur 1', '5f31916e1ae5d10eb0269a64cc4399d9.jpg', 1, '2026-01-23 15:37:40'),
(34, 'Vektor Gus Dur 2', '965694d3720207a00d09c36deed81445.jpg', 1, '2026-01-23 15:38:03'),
(35, 'Vektor Gus Dur 3', '29942641a96d3791d9e842e62eb9e390.jpg', 1, '2026-01-23 15:38:14'),
(36, 'Vektor Gus Dur 4', 'b5c70daf9e2f540ac533ffcd5088a063.jpg', 1, '2026-01-23 15:38:39'),
(37, 'Vektor Gus Dur 5', 'c49ee3d9337d193356f6343e7653a05c.jpg', 1, '2026-01-23 15:38:57'),
(38, 'Vektor Gus Dur 6', '2f7f0ab6c5e1a123440e2b2fce216c18.jpg', 1, '2026-01-23 15:39:14'),
(39, 'Vektor Gus Dur 7', '6f9299299a61498b191bbf25e740d7f3.jpg', 1, '2026-01-23 15:39:40'),
(40, 'Vektor Gus Dur 8', '43b8705cd16d291055fbe195f86838c8.jpg', 1, '2026-01-23 15:40:47'),
(41, 'Vektor Gus Dur 9', '4f017122d74eb89f9003ec09f6c45668.jpg', 1, '2026-01-23 15:41:09'),
(42, 'Vektor Gus Dur 10', '2e27fb809c20e6c406cce5bafca646cb.jpg', 1, '2026-01-23 15:41:35'),
(43, 'Vektor Gus Dur 11', '5d23d4070092c73a1f498c6a3dc21baf.jpg', 1, '2026-01-23 15:41:54'),
(44, 'Vektor Gus Dur 12', '8ca803c2906fa5a53befa3e3daa17a1a.jpg', 1, '2026-01-23 15:42:09'),
(45, 'Vektor Gus Dur 13', '4bfa6e5dca40cb56e1a652990a34dd2d.jpg', 1, '2026-01-23 15:42:33'),
(46, 'Vektor Gus Dur 14', '87c8a32c5a65e1ff224f738fcef12e9b.jpg', 1, '2026-01-23 15:42:50'),
(47, 'Scribble Gus Dur 1', 'ce61e8aa8baba3923f00b19e7472d2ab.jpg', 1, '2026-01-23 15:43:46'),
(48, 'Scribble Gus Dur 2', 'b9a733ccefbb52c3042900370fcd1093.jpg', 1, '2026-01-23 15:44:55'),
(49, 'Scribble Gus Dur 3', '4441749457a42ddb1b4b6688401da6f8.jpg', 1, '2026-01-23 15:45:40'),
(50, 'Scribble Gus Dur 4', '8d9391ba7a45c394ebcdf4aeefef5c6f.jpg', 1, '2026-01-23 15:46:06'),
(51, 'Typografi Tebuireng 1', '49746543b14dff8a7dd5809dcc43c07b.jpg', 1, '2026-01-23 15:46:44'),
(52, 'Typografi Tebuireng 2', '618bb79438c0714fe4abb0ecbbfa913a.jpg', 1, '2026-01-23 15:46:59'),
(53, 'Vektor Bonek', 'c446968c335ac93dae890056c397a6cb.jpg', 1, '2026-01-23 15:47:27'),
(54, 'Ilustration Boos', '285e78846a3b188d2d69a8c5fdad1a82.jpg', 1, '2026-01-23 15:48:00'),
(55, 'Logo Sumber Makmur', '747af7eb6bfe04c2c1741b54d387ea53.jpg', 1, '2026-01-23 15:48:13'),
(56, 'Sutenang Efendi', '26c7f7bc58ffa5056f96ba9b6a09bd5b.jpg', 1, '2026-01-23 17:14:58'),
(57, 'Khusnul Insani', '95245427d8fa7597125581af8b9620d4.jpg', 1, '2026-01-23 17:15:59'),
(58, 'Waka Smk', 'bd409cfcad993b6a20ed0b23ae9919a0.jpg', 1, '2026-01-23 17:17:57'),
(59, 'Vektor Gus Dur 15', '394eb9a1f7657babbfdd521fbcb02bba.jpg', 1, '2026-01-24 08:22:53'),
(60, 'Scribble Gus Dur 6', '652f53c85ca062a92443ca84f225adb7.jpg', 1, '2026-01-24 08:23:13'),
(72, 'Vektor Portrait 16', '25a0ef05de63b7882164557400a5a11d.jpg', 1, '2026-01-27 17:58:02'),
(73, 'Vektor Portrait 17', 'a1bab90de75e64de61d82f56c7cfb428.jpg', 1, '2026-01-27 17:58:48'),
(76, 'Vektor 6', 'b0e6b98b66da1ee743086804c83714a3.jpg', 1, '2026-02-07 19:49:32'),
(77, 'vektor 12', '4aae3232e36ca4bec23ee505f76e7bd8.jpg', 1, '2026-02-07 19:54:15'),
(78, 'vektor 14', 'bcf2d19e2041f467ec7af5744a263d29.jpg', 1, '2026-02-07 19:59:03'),
(79, 'vektor 17', 'a87698164961f84618035c3a2440cc89.jpg', 1, '2026-02-07 20:02:30'),
(80, 'vektor 21', '9c96fe73aad0f0f498a955a90d4de5f1.jpg', 1, '2026-02-08 09:36:55'),
(81, 'vektor 29', '4efde37172703103da5945bbdea24b70.jpg', 1, '2026-02-08 09:43:27'),
(82, 'vektor 28', '16844e31746893d7ca5fab653bbe503a.jpg', 1, '2026-02-08 21:03:56'),
(83, 'vektor 31', '1fe62bc27332c5bfe4407ea5b4a1725b.jpg', 1, '2026-02-08 21:07:41'),
(84, 'vektor 32', 'd068967320e201f8f6892199c7463d2f.jpg', 1, '2026-02-08 21:09:26'),
(85, 'vektor 35', '187303f625ec467c876c94ae86279655.jpg', 1, '2026-02-08 21:12:12'),
(86, 'vektor 36', '3fd7bd463516cf132cc19c4ab7e48490.jpg', 1, '2026-02-08 21:13:52'),
(87, 'vektor 38', '64f2f8c8f219e3a5421c606d1e92bae5.jpg', 1, '2026-02-08 21:15:29'),
(88, 'vektor 40', 'acb8eef29bcea2ebd1572ede066a740f.jpg', 1, '2026-02-08 21:17:27'),
(89, 'vektor 41', '4b2d1c4c1583a81dc15e1a028de97497.jpg', 1, '2026-02-08 21:18:59'),
(90, 'vektor 42', '21fadf200c140848be1a9d15bc2f5291.jpg', 1, '2026-02-08 21:21:46'),
(91, 'vektor 44', '2e05d320371fe1c57302eb82bf7fc13e.jpg', 1, '2026-02-08 21:23:36'),
(92, 'vektor 45', '3664c02f538034d58a03833b40dea108.jpg', 1, '2026-02-08 21:25:02'),
(93, 'vektor 48', 'ed672692e31bada9d62186875298184c.jpg', 1, '2026-02-08 21:26:40'),
(94, 'vektor 49', '2154549c45e149244cd06ba8d8864eb8.jpg', 1, '2026-02-08 21:28:06'),
(95, 'vektor 52', 'cf65497b78f98f431613e3ef7f75d621.jpg', 1, '2026-02-08 21:32:18'),
(96, 'vektor 61', 'd23c4467e5043839313a7e300d5dcd9a.jpg', 1, '2026-02-08 21:34:50'),
(97, 'vektor 65', '11a3da78e4d1915154a6904f34ef8e51.jpg', 1, '2026-02-08 21:36:34'),
(98, 'vektor 71', '975c38a274aea9ad6bf7514eb408342c.jpg', 1, '2026-02-08 21:38:16'),
(99, 'vektor 73', '41720180604ba14d6e0926ca130fa2f3.jpg', 1, '2026-02-08 21:39:58'),
(100, 'vektor 75', '21ca85e06141acc9ed6b6ead8071aad2.jpg', 1, '2026-02-08 22:25:24'),
(101, 'vektor 77', '9ccd68ad473ef174a606f94a1cc1770e.jpg', 1, '2026-02-08 22:28:27'),
(102, 'vektor 78', '270d75cbd19d145eab93653071815864.jpg', 1, '2026-02-08 22:28:47'),
(103, 'vektor 79', '89078aa725f7682b43d79fb1a572fac3.jpg', 1, '2026-02-08 22:29:06'),
(104, 'vektor 80', '7c944062c6791257a501d24474f4a4a2.jpg', 1, '2026-02-08 22:34:01'),
(107, 'Scribble gus dur 5', '21b101ba32eb657dcc777a6359b4550d.jpg', 1, '2026-02-22 09:34:53'),
(108, 'Line Art gus dur ', '9990f95f9d2c7b4cfee7bd94de891071.jpg', 1, '2026-02-22 09:35:33'),
(264, 'vektor 55', '8cfe81c0344f531e63a6e0ef37b93586.jpg', 1, '2026-02-24 15:23:06'),
(265, 'KH. Hastim Arsir 1', '4e7481cc99b613add7c770434577a0a0.jpg', 1, '2026-02-24 15:44:10'),
(266, 'KH. Hastim Arsir 2', '7bce6ac2a42e898a969453e059fad8ce.jpg', 1, '2026-02-24 15:45:05'),
(270, 'vektor 58', '56753e0e68fa74f9acc1be5d793e471d.jpg', 1, '2026-02-24 14:14:05'),
(271, 'vektor 57', 'c1ff1d9819e3bb0587eaeb7960856ec3.jpg', 1, '2026-02-24 14:14:35'),
(272, 'vektor 56', '4c0a7581a48fe6cb304cc76f8e7b88c8.jpg', 1, '2026-02-24 14:16:18'),
(298, 'Vektor Bola 1', 'd8bc4228c4325facfb3e06d87f9b6720.jpeg', 1, '2026-04-16 19:15:16'),
(299, 'Vektor Bola 2', '2e8c39423549e0cb6986696fc40636a9.jpeg', 1, '2026-04-16 19:16:12');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int NOT NULL,
  `invoice_no` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_no`, `order_id`, `created_at`) VALUES
(1, 'INV-20260123-7737', 7, '2026-01-23 17:57:00'),
(2, 'INV-20260124-2366', 10, '2026-01-24 14:55:05'),
(5, 'INV-20260210-3921', 45, '2026-02-10 19:17:01'),
(6, 'INV-20260222-7148', 48, '2026-02-22 09:39:01'),
(7, 'INV-20260224-7198', 57, '2026-02-24 15:47:10'),
(8, 'INV-20260416-2911', 80, '2026-04-16 19:18:53');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
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
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `client_id`, `title`, `design_type_id`, `body_part_id`, `base_price`, `addons`, `revision_count`, `revision_fee`, `subtotal`, `discount`, `total`, `paid`, `status`, `deadline`, `created_at`) VALUES
(2, 'ORD-20260123-6644', 2, 'Vektor Art', 2, 1, 1040000, 260000, 0, 0, 0, 0, 1300000, 1300000, 'SELESAI', '2026-01-23', '2026-01-23 11:31:26'),
(3, 'ORD-20260123-3831', 3, 'Karikatur', 19, 3, 100000, 0, 0, 0, 0, 0, 100000, 100000, 'SELESAI', '2026-01-02', '2026-01-23 12:56:54'),
(4, 'ORD-20260123-0513', 4, 'Vektor Kaos', 20, 4, 600000, 0, 0, 0, 0, 0, 600000, 600000, 'SELESAI', '2025-12-11', '2026-01-23 13:14:37'),
(5, 'ORD-20260123-0747', 5, 'Vektor Jendral', 22, 3, 300000, 0, 0, 0, 0, 0, 300000, 300000, 'SELESAI', '2025-12-29', '2026-01-23 13:18:08'),
(6, 'ORD-20260123-6013', 6, 'Vektor Art', 30, 2, 636000, 0, 0, 0, 0, 0, 636000, 636000, 'SELESAI', '2025-10-20', '2026-01-23 15:27:57'),
(7, 'ORD-20260123-2995', 7, 'Vektor Portrait', 31, 2, 87000, 0, 0, 0, 0, 0, 87000, 87000, 'SELESAI', '2026-01-23', '2026-01-23 15:34:06'),
(8, 'ORD-20260123-2107', 8, 'Vektor Kaos', 33, 3, 2455000, 0, 0, 0, 0, 0, 2455000, 1600000, 'SELESAI', '2026-01-17', '2026-01-23 16:10:20'),
(10, 'ORD-20260124-9910', 10, 'Vektor Gus Dur Sablon', 60, 1, 205000, 0, 0, 0, 0, 0, 205000, 205000, 'SELESAI', '2025-12-07', '2026-01-24 08:25:59'),
(13, 'ORD-20260127-0073', 13, 'Vektor Art', 72, 1, 75000, 0, 0, 0, 0, 0, 75000, 75000, 'SELESAI', '2026-01-27', '2026-01-27 18:01:22'),
(14, 'ORD-20260127-8363', 14, 'Vektor Art', 73, 2, 174000, 0, 0, 0, 0, 0, 174000, 174000, 'SELESAI', '2026-01-27', '2026-01-27 18:05:26'),
(19, 'ORD-20260207-8377', 16, 'Vektor Art', 76, 2, 35000, 0, 0, 0, 0, 0, 35000, 35000, 'SELESAI', '2023-02-08', '2026-02-07 19:50:53'),
(20, 'ORD-20260207-9992', 17, 'Vektor Art', 77, 2, 58000, 0, 0, 0, 0, 0, 58000, 58000, 'SELESAI', '2023-02-22', '2026-02-07 19:56:39'),
(21, 'ORD-20260207-7464', 16, 'Vektor Art', 78, 2, 35000, 0, 0, 0, 0, 0, 35000, 35000, 'SELESAI', '2023-07-12', '2026-02-07 20:00:21'),
(22, 'ORD-20260207-8334', 18, 'Vektor Art', 79, 2, 75000, 0, 0, 0, 0, 0, 75000, 75000, 'SELESAI', '2023-10-10', '2026-02-07 20:05:32'),
(23, 'ORD-20260208-0073', 19, 'Vektor Walpapper', 80, 2, 10000, 0, 0, 0, 0, 0, 10000, 10000, 'SELESAI', '2023-12-08', '2026-02-08 09:38:14'),
(24, 'ORD-20260208-8342', 20, 'Vektor Cindramata', 81, 2, 35000, 0, 0, 0, 0, 0, 35000, 35000, 'SELESAI', '2023-12-13', '2026-02-08 09:45:00'),
(25, 'ORD-20260208-1450', 21, 'Vektor Art', 82, 2, 35000, 0, 0, 0, 0, 0, 35000, 35000, 'SELESAI', '2024-02-08', '2026-02-08 21:05:58'),
(26, 'ORD-20260208-1717', 22, 'Vektor Art', 83, 2, 35000, 0, 0, 0, 0, 0, 35000, 35000, 'SELESAI', '2024-03-08', '2026-02-08 21:09:06'),
(27, 'ORD-20260208-6729', 15, 'Vektor Art', 84, 2, 35000, 0, 0, 0, 0, 0, 35000, 35000, 'SELESAI', '2024-05-07', '2026-02-08 21:11:15'),
(28, 'ORD-20260208-0002', 23, 'Vektor Art', 85, 2, 35000, 0, 0, 0, 0, 0, 35000, 35000, 'SELESAI', '2024-05-10', '2026-02-08 21:13:25'),
(29, 'ORD-20260208-0893', 24, 'Vektor Art', 86, 2, 50000, 0, 0, 0, 0, 0, 50000, 50000, 'SELESAI', '2024-06-09', '2026-02-08 21:15:07'),
(30, 'ORD-20260208-4352', 25, 'Vektor Art', 87, 2, 35000, 0, 0, 0, 0, 0, 35000, 35000, 'SELESAI', '2024-05-22', '2026-02-08 21:17:04'),
(31, 'ORD-20260208-1065', 18, 'Vektor Art', 88, 3, 50000, 0, 0, 0, 0, 0, 50000, 50000, 'SELESAI', '2024-07-23', '2026-02-08 21:18:28'),
(32, 'ORD-20260208-8455', 26, 'Vektor Art', 89, 2, 50000, 0, 0, 0, 0, 0, 50000, 50000, 'SELESAI', '2024-07-27', '2026-02-08 21:21:18'),
(33, 'ORD-20260208-2531', 18, 'Vektor Art', 90, 3, 50000, 0, 0, 0, 0, 0, 50000, 50000, 'SELESAI', '2024-08-06', '2026-02-08 21:23:02'),
(34, 'ORD-20260208-3063', 27, 'Vektor Art', 91, 3, 100000, 0, 0, 0, 0, 0, 100000, 100000, 'SELESAI', '2024-08-11', '2026-02-08 21:24:40'),
(35, 'ORD-20260208-2856', 28, 'Vektor Art', 92, 2, 25000, 0, 0, 0, 0, 0, 25000, 25000, 'SELESAI', '2024-08-13', '2026-02-08 21:26:13'),
(36, 'ORD-20260208-7154', 29, 'Vektor Art', 93, 2, 65000, 0, 0, 0, 0, 0, 65000, 65000, 'SELESAI', '2024-08-14', '2026-02-08 21:27:40'),
(37, 'ORD-20260208-8261', 30, 'Vektor Art', 94, 1, 75000, 0, 0, 0, 0, 0, 75000, 75000, 'SELESAI', '2024-08-16', '2026-02-08 21:31:57'),
(38, 'ORD-20260208-6846', 22, 'Vektor Art', 95, 2, 35000, 0, 0, 0, 0, 0, 35000, 35000, 'SELESAI', '2024-08-18', '2026-02-08 21:33:34'),
(39, 'ORD-20260208-1963', 16, 'Vektor Art', 96, 3, 100000, 0, 0, 0, 0, 0, 100000, 100000, 'SELESAI', '2024-08-19', '2026-02-08 21:36:01'),
(40, 'ORD-20260208-0642', 31, 'Vektor Art', 97, 2, 70000, 0, 0, 0, 0, 0, 70000, 70000, 'SELESAI', '2024-08-21', '2026-02-08 21:37:55'),
(41, 'ORD-20260208-9019', 18, 'Vektor Art', 98, 3, 50000, 0, 0, 0, 0, 0, 50000, 50000, 'SELESAI', '2024-08-21', '2026-02-08 21:39:31'),
(42, 'ORD-20260208-1748', 32, 'Vektor Art', 99, 2, 50000, 0, 0, 0, 0, 0, 50000, 50000, 'SELESAI', '2024-08-23', '2026-02-08 22:25:03'),
(43, 'ORD-20260208-8777', 33, 'Vektor Art', 100, 2, 78000, 0, 0, 0, 0, 0, 78000, 78000, 'SELESAI', '2024-08-27', '2026-02-08 22:28:00'),
(44, 'ORD-20260208-9222', 34, 'Vektor Art', 103, 2, 150000, 0, 0, 0, 0, 0, 150000, 150000, 'SELESAI', '2024-08-28', '2026-02-08 22:33:18'),
(45, 'ORD-20260208-0938', 35, 'Vektor Art', 104, 2, 65000, 0, 0, 0, 0, 0, 65000, 65000, 'SELESAI', '2023-08-30', '2026-02-08 22:38:17'),
(48, 'ORD-20260222-6475', 37, 'Art Gus Dur', 108, 1, 245000, 0, 0, 0, 0, 10000, 235000, 235000, 'SELESAI', '2026-02-22', '2026-02-22 09:38:49'),
(56, 'ORD-20260224-9121', 40, 'Scribbe', 264, 2, 65000, 0, 0, 0, 0, 0, 65000, 65000, 'SELESAI', '2026-02-23', '2026-02-24 15:25:04'),
(57, 'ORD-20260224-6517', 41, 'Arsir', 266, 2, 290000, 0, 0, 0, 0, 0, 290000, 290000, 'SELESAI', '2026-02-24', '2026-02-24 15:47:02'),
(61, 'INV-1771939139', 45, 'art', 270, 1, 225000, 25000, 0, 0, 0, 0, 250000, 250000, 'SELESAI', '2025-09-26', '2026-02-24 14:18:59'),
(80, 'ORD-20260416-3803', 60, 'Vektor Potrait', 299, 2, 280000, 0, 0, 0, 0, 0, 280000, 280000, 'SELESAI', '2026-04-16', '2026-04-16 19:18:39');

-- --------------------------------------------------------


-- --------------------------------------------------------

--
-- Table structure for table `order_payments`
--

CREATE TABLE `order_payments` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `amount` int NOT NULL DEFAULT '0',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` date NOT NULL,
  `source` enum('DP','MANUAL','AI','AI_WIZARD','MIGRATION') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MANUAL',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_payments`
--

INSERT INTO `order_payments` (`id`, `order_id`, `amount`, `note`, `payment_date`, `source`, `created_at`) VALUES
(1, 2, 1300000, 'Migrasi pembayaran lama / DP', '2026-01-23', 'MIGRATION', '2026-01-23 11:31:26'),
(2, 3, 100000, 'Migrasi pembayaran lama / DP', '2026-01-23', 'MIGRATION', '2026-01-23 12:56:54'),
(3, 4, 600000, 'Migrasi pembayaran lama / DP', '2026-01-23', 'MIGRATION', '2026-01-23 13:14:37'),
(4, 5, 300000, 'Migrasi pembayaran lama / DP', '2026-01-23', 'MIGRATION', '2026-01-23 13:18:08'),
(5, 6, 636000, 'Migrasi pembayaran lama / DP', '2026-01-23', 'MIGRATION', '2026-01-23 15:27:57'),
(6, 7, 87000, 'Migrasi pembayaran lama / DP', '2026-01-23', 'MIGRATION', '2026-01-23 15:34:06'),
(7, 8, 1600000, 'Migrasi pembayaran lama / DP', '2026-01-23', 'MIGRATION', '2026-01-23 16:10:20'),
(8, 10, 205000, 'Migrasi pembayaran lama / DP', '2026-01-24', 'MIGRATION', '2026-01-24 08:25:59'),
(9, 13, 75000, 'Migrasi pembayaran lama / DP', '2026-01-27', 'MIGRATION', '2026-01-27 18:01:22'),
(10, 14, 174000, 'Migrasi pembayaran lama / DP', '2026-01-27', 'MIGRATION', '2026-01-27 18:05:26'),
(11, 19, 35000, 'Migrasi pembayaran lama / DP', '2026-02-07', 'MIGRATION', '2026-02-07 19:50:53'),
(12, 20, 58000, 'Migrasi pembayaran lama / DP', '2026-02-07', 'MIGRATION', '2026-02-07 19:56:39'),
(13, 21, 35000, 'Migrasi pembayaran lama / DP', '2026-02-07', 'MIGRATION', '2026-02-07 20:00:21'),
(14, 22, 75000, 'Migrasi pembayaran lama / DP', '2026-02-07', 'MIGRATION', '2026-02-07 20:05:32'),
(15, 23, 10000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 09:38:14'),
(16, 24, 35000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 09:45:00'),
(17, 25, 35000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:05:58'),
(18, 26, 35000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:09:06'),
(19, 27, 35000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:11:15'),
(20, 28, 35000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:13:25'),
(21, 29, 50000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:15:07'),
(22, 30, 35000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:17:04'),
(23, 31, 50000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:18:28'),
(24, 32, 50000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:21:18'),
(25, 33, 50000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:23:02'),
(26, 34, 100000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:24:40'),
(27, 35, 25000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:26:13'),
(28, 36, 65000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:27:40'),
(29, 37, 75000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:31:57'),
(30, 38, 35000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:33:34'),
(31, 39, 100000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:36:01'),
(32, 40, 70000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:37:55'),
(33, 41, 50000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 21:39:31'),
(34, 42, 50000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 22:25:03'),
(35, 43, 78000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 22:28:00'),
(36, 44, 150000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 22:33:18'),
(37, 45, 65000, 'Migrasi pembayaran lama / DP', '2026-02-08', 'MIGRATION', '2026-02-08 22:38:17'),
(38, 48, 235000, 'Migrasi pembayaran lama / DP', '2026-02-22', 'MIGRATION', '2026-02-22 09:38:49'),
(39, 56, 65000, 'Migrasi pembayaran lama / DP', '2026-02-24', 'MIGRATION', '2026-02-24 15:25:04'),
(40, 57, 290000, 'Migrasi pembayaran lama / DP', '2026-02-24', 'MIGRATION', '2026-02-24 15:47:02'),
(41, 61, 250000, 'Migrasi pembayaran lama / DP', '2026-02-24', 'MIGRATION', '2026-02-24 14:18:59'),
(42, 80, 280000, 'Migrasi pembayaran lama / DP', '2026-04-16', 'MIGRATION', '2026-04-16 19:18:39');

--
-- Table structure for table `order_files`
--

CREATE TABLE `order_files` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `file_type` enum('PREVIEW','FINAL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PREVIEW',
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage` enum('local','drive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local',
  `drive_file_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `drive_url` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_files`
--

INSERT INTO `order_files` (`id`, `order_id`, `file_type`, `file_name`, `original_name`, `created_at`) VALUES
(5, 10, 'PREVIEW', 'eb64ab289bef3087753099ce169c1936.jpg', 'gus_dur_3.jpg', '2026-01-24 09:03:23'),
(6, 10, 'PREVIEW', 'd8e9bdeddf0ac5d0e36ade4e01bb4770.jpg', 'gus_dur_1.jpg', '2026-01-24 09:03:23');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `design_type_id` int NOT NULL,
  `body_part_id` int NOT NULL,
  `qty` int NOT NULL DEFAULT '1',
  `price` int NOT NULL DEFAULT '0',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `design_type_id`, `body_part_id`, `qty`, `price`, `note`, `created_at`) VALUES
(20, 3, 19, 3, 1, 100000, '', '2026-01-23 12:56:54'),
(40, 4, 20, 4, 1, 300000, '', '2026-01-23 13:15:46'),
(41, 4, 21, 4, 1, 300000, '', '2026-01-23 13:15:46'),
(42, 5, 22, 3, 1, 300000, '', '2026-01-23 13:18:08'),
(107, 2, 2, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(108, 2, 3, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(109, 2, 4, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(110, 2, 5, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(111, 2, 6, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(112, 2, 7, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(113, 2, 8, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(114, 2, 9, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(115, 2, 10, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(116, 2, 11, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(117, 2, 12, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(118, 2, 13, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(119, 2, 14, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(120, 2, 15, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(121, 2, 17, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(122, 2, 18, 1, 1, 65000, '', '2026-01-23 15:20:32'),
(131, 6, 30, 2, 1, 87000, '', '2026-01-23 15:28:20'),
(132, 6, 29, 1, 1, 75000, '', '2026-01-23 15:28:20'),
(133, 6, 28, 1, 1, 75000, '', '2026-01-23 15:28:20'),
(134, 6, 27, 1, 1, 75000, '', '2026-01-23 15:28:20'),
(135, 6, 26, 1, 1, 75000, '', '2026-01-23 15:28:20'),
(136, 6, 25, 2, 1, 87000, '', '2026-01-23 15:28:20'),
(137, 6, 24, 1, 1, 75000, '', '2026-01-23 15:28:20'),
(138, 6, 23, 2, 1, 87000, '', '2026-01-23 15:28:20'),
(198, 7, 31, 2, 1, 87000, '', '2026-01-23 18:00:10'),
(207, 10, 60, 1, 1, 110000, '', '2026-01-24 08:26:17'),
(208, 10, 59, 1, 1, 95000, '', '2026-01-24 08:26:17'),
(212, 13, 72, 1, 1, 75000, '', '2026-01-27 18:01:22'),
(214, 14, 73, 2, 2, 87000, '2 kepala', '2026-01-27 18:05:49'),
(232, 8, 33, 3, 1, 95000, '', '2026-02-07 19:27:49'),
(233, 8, 34, 3, 1, 100000, '', '2026-02-07 19:27:49'),
(234, 8, 35, 1, 1, 90000, '', '2026-02-07 19:27:49'),
(235, 8, 36, 1, 1, 95000, '', '2026-02-07 19:27:49'),
(236, 8, 37, 3, 1, 105000, '', '2026-02-07 19:27:49'),
(237, 8, 38, 3, 1, 100000, '', '2026-02-07 19:27:49'),
(238, 8, 39, 3, 1, 100000, '', '2026-02-07 19:27:49'),
(239, 8, 40, 1, 1, 150000, '', '2026-02-07 19:27:49'),
(240, 8, 41, 1, 1, 85000, '', '2026-02-07 19:27:49'),
(241, 8, 42, 1, 1, 85000, '', '2026-02-07 19:27:49'),
(242, 8, 43, 1, 1, 100000, '', '2026-02-07 19:27:49'),
(243, 8, 44, 1, 1, 95000, '', '2026-02-07 19:27:49'),
(244, 8, 45, 1, 1, 95000, '', '2026-02-07 19:27:49'),
(245, 8, 46, 1, 1, 85000, '', '2026-02-07 19:27:49'),
(246, 8, 47, 1, 1, 150000, '', '2026-02-07 19:27:49'),
(247, 8, 48, 1, 1, 150000, '', '2026-02-07 19:27:49'),
(248, 8, 49, 1, 1, 150000, '', '2026-02-07 19:27:49'),
(249, 8, 50, 1, 1, 150000, '', '2026-02-07 19:27:49'),
(250, 8, 51, 4, 1, 55000, '', '2026-02-07 19:27:49'),
(251, 8, 52, 1, 1, 55000, '', '2026-02-07 19:27:49'),
(252, 8, 53, 4, 1, 100000, '', '2026-02-07 19:27:49'),
(253, 8, 54, 4, 1, 200000, '', '2026-02-07 19:27:49'),
(254, 8, 55, 4, 1, 65000, '', '2026-02-07 19:27:49'),
(256, 19, 76, 2, 1, 35000, '', '2026-02-07 19:50:53'),
(257, 20, 77, 2, 2, 29000, '2 kepala', '2026-02-07 19:56:39'),
(258, 21, 78, 2, 1, 35000, '', '2026-02-07 20:00:21'),
(259, 22, 79, 2, 1, 75000, '', '2026-02-07 20:05:32'),
(260, 23, 80, 2, 1, 10000, '', '2026-02-08 09:38:14'),
(261, 24, 81, 2, 1, 35000, '', '2026-02-08 09:45:00'),
(262, 25, 82, 2, 1, 35000, '', '2026-02-08 21:05:58'),
(263, 26, 83, 2, 1, 35000, '', '2026-02-08 21:09:06'),
(264, 27, 84, 2, 1, 35000, '', '2026-02-08 21:11:15'),
(265, 28, 85, 2, 1, 35000, '', '2026-02-08 21:13:25'),
(266, 29, 86, 2, 1, 50000, '', '2026-02-08 21:15:07'),
(267, 30, 87, 2, 1, 35000, '', '2026-02-08 21:17:04'),
(268, 31, 88, 3, 1, 50000, '', '2026-02-08 21:18:28'),
(269, 32, 89, 2, 1, 50000, '', '2026-02-08 21:21:18'),
(270, 33, 90, 3, 1, 50000, '', '2026-02-08 21:23:02'),
(271, 34, 91, 3, 2, 50000, '', '2026-02-08 21:24:40'),
(272, 35, 92, 2, 1, 25000, '', '2026-02-08 21:26:13'),
(273, 36, 93, 2, 1, 65000, '', '2026-02-08 21:27:40'),
(274, 37, 94, 1, 1, 75000, '', '2026-02-08 21:31:57'),
(275, 38, 95, 2, 1, 35000, '', '2026-02-08 21:33:34'),
(276, 39, 96, 3, 1, 100000, '', '2026-02-08 21:36:02'),
(277, 40, 97, 2, 1, 70000, '', '2026-02-08 21:37:55'),
(278, 41, 98, 3, 2, 25000, '', '2026-02-08 21:39:31'),
(279, 42, 99, 2, 1, 50000, '', '2026-02-08 22:25:03'),
(280, 43, 100, 2, 1, 78000, '', '2026-02-08 22:28:00'),
(284, 44, 103, 2, 1, 65000, '', '2026-02-08 22:33:31'),
(285, 44, 102, 2, 1, 35000, '', '2026-02-08 22:33:31'),
(286, 44, 101, 2, 1, 50000, '', '2026-02-08 22:33:31'),
(287, 45, 104, 2, 1, 65000, '', '2026-02-08 22:38:18'),
(295, 48, 108, 1, 1, 95000, '', '2026-02-23 12:07:54'),
(296, 48, 107, 1, 1, 150000, '', '2026-02-23 12:07:54'),
(304, 56, 264, 2, 1, 65000, '', '2026-02-24 15:27:08'),
(309, 57, 266, 2, 1, 145000, '', '2026-02-24 16:07:54'),
(310, 57, 265, 2, 1, 145000, '', '2026-02-24 16:07:54'),
(334, 61, 270, 1, 1, 75000, '', '2026-02-24 20:21:17'),
(335, 61, 271, 1, 1, 75000, '', '2026-02-24 20:21:17'),
(336, 61, 272, 1, 1, 75000, '', '2026-02-24 20:21:17'),
(354, 80, 299, 2, 2, 70000, '2 kepala', '2026-04-16 19:18:39'),
(355, 80, 298, 2, 2, 70000, '2 kepala', '2026-04-16 19:18:39');

-- --------------------------------------------------------

--
-- Table structure for table `order_revisions`
--

CREATE TABLE `order_revisions` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fee` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `price_matrix`
--

CREATE TABLE `price_matrix` (
  `id` int NOT NULL,
  `design_type_id` int NOT NULL,
  `body_part_id` int NOT NULL,
  `base_price` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `price_matrix`
--

INSERT INTO `price_matrix` (`id`, `design_type_id`, `body_part_id`, `base_price`, `created_at`) VALUES
(2, 2, 1, 50000, '2026-02-26 10:34:26'),
(3, 3, 1, 65000, '2026-02-26 10:34:26'),
(4, 4, 1, 65000, '2026-02-26 10:34:26'),
(5, 5, 1, 65000, '2026-02-26 10:34:26'),
(6, 6, 1, 65000, '2026-02-26 10:34:26'),
(7, 7, 1, 65000, '2026-02-26 10:34:26'),
(8, 8, 1, 65000, '2026-02-26 10:34:26'),
(9, 9, 1, 65000, '2026-02-26 10:34:26'),
(10, 10, 1, 65000, '2026-02-26 10:34:26'),
(11, 11, 1, 65000, '2026-02-26 10:34:26'),
(12, 12, 1, 65000, '2026-02-26 10:34:26'),
(13, 13, 1, 65000, '2026-02-26 10:34:26'),
(14, 14, 1, 65000, '2026-02-26 10:34:26'),
(15, 15, 1, 65000, '2026-02-26 10:34:26'),
(17, 17, 1, 65000, '2026-02-26 10:34:26'),
(18, 18, 1, 65000, '2026-02-26 10:34:26'),
(19, 19, 3, 100000, '2026-02-26 10:34:26'),
(20, 20, 4, 300000, '2026-02-26 10:34:26'),
(21, 21, 4, 300000, '2026-02-26 10:34:26'),
(22, 22, 3, 300000, '2026-02-26 10:34:26'),
(23, 23, 2, 87000, '2026-02-26 10:34:26'),
(24, 24, 1, 75000, '2026-02-26 10:34:26'),
(26, 25, 2, 87000, '2026-02-26 10:34:26'),
(27, 26, 1, 75000, '2026-02-26 10:34:26'),
(28, 27, 1, 75000, '2026-02-26 10:34:26'),
(29, 28, 1, 75000, '2026-02-26 10:34:26'),
(30, 29, 1, 75000, '2026-02-26 10:34:26'),
(31, 30, 2, 87000, '2026-02-26 10:34:26'),
(32, 31, 2, 87000, '2026-02-26 10:34:26'),
(35, 35, 1, 90000, '2026-02-26 10:34:26'),
(36, 36, 1, 95000, '2026-02-26 10:34:26'),
(40, 40, 1, 150000, '2026-02-26 10:34:26'),
(41, 41, 1, 85000, '2026-02-26 10:34:26'),
(42, 42, 1, 85000, '2026-02-26 10:34:26'),
(43, 43, 1, 100000, '2026-02-26 10:34:26'),
(44, 44, 1, 95000, '2026-02-26 10:34:26'),
(45, 45, 1, 95000, '2026-02-26 10:34:26'),
(46, 46, 1, 85000, '2026-02-26 10:34:26'),
(47, 47, 1, 150000, '2026-02-26 10:34:26'),
(48, 48, 1, 150000, '2026-02-26 10:34:26'),
(49, 49, 1, 150000, '2026-02-26 10:34:26'),
(50, 50, 1, 150000, '2026-02-26 10:34:26'),
(51, 51, 4, 55000, '2026-02-26 10:34:26'),
(52, 52, 1, 55000, '2026-02-26 10:34:26'),
(53, 53, 4, 100000, '2026-02-26 10:34:26'),
(54, 54, 4, 200000, '2026-02-26 10:34:26'),
(55, 55, 4, 65000, '2026-02-26 10:34:26'),
(56, 39, 3, 100000, '2026-02-26 10:34:26'),
(57, 38, 3, 100000, '2026-02-26 10:34:26'),
(58, 37, 3, 105000, '2026-02-26 10:34:26'),
(59, 34, 3, 100000, '2026-02-26 10:34:26'),
(60, 33, 3, 95000, '2026-02-26 10:34:26'),
(62, 57, 1, 75000, '2026-02-26 10:34:26'),
(63, 58, 1, 75000, '2026-02-26 10:34:26'),
(64, 56, 1, 75000, '2026-02-26 10:34:26'),
(65, 59, 1, 95000, '2026-02-26 10:34:26'),
(66, 60, 1, 110000, '2026-02-26 10:34:26'),
(69, 72, 1, 75000, '2026-02-26 10:34:26'),
(70, 73, 2, 87000, '2026-02-26 10:34:26'),
(73, 76, 2, 35000, '2026-02-26 10:34:26'),
(74, 77, 2, 29000, '2026-02-26 10:34:26'),
(75, 78, 2, 35000, '2026-02-26 10:34:26'),
(76, 79, 2, 75000, '2026-02-26 10:34:26'),
(77, 80, 2, 10000, '2026-02-26 10:34:26'),
(78, 81, 2, 35000, '2026-02-26 10:34:26'),
(79, 82, 2, 35000, '2026-02-26 10:34:26'),
(80, 83, 2, 35000, '2026-02-26 10:34:26'),
(81, 84, 2, 35000, '2026-02-26 10:34:26'),
(82, 85, 2, 35000, '2026-02-26 10:34:26'),
(83, 86, 2, 50000, '2026-02-26 10:34:26'),
(84, 87, 2, 35000, '2026-02-26 10:34:26'),
(85, 88, 3, 50000, '2026-02-26 10:34:26'),
(86, 89, 2, 50000, '2026-02-26 10:34:26'),
(87, 90, 3, 50000, '2026-02-26 10:34:26'),
(88, 91, 3, 50000, '2026-02-26 10:34:26'),
(89, 92, 2, 25000, '2026-02-26 10:34:26'),
(90, 93, 2, 65000, '2026-02-26 10:34:26'),
(91, 94, 1, 75000, '2026-02-26 10:34:26'),
(92, 95, 2, 35000, '2026-02-26 10:34:26'),
(93, 96, 3, 100000, '2026-02-26 10:34:26'),
(94, 97, 2, 70000, '2026-02-26 10:34:26'),
(95, 98, 3, 25000, '2026-02-26 10:34:26'),
(96, 99, 2, 50000, '2026-02-26 10:34:26'),
(97, 100, 2, 78000, '2026-02-26 10:34:26'),
(98, 103, 2, 65000, '2026-02-26 10:34:26'),
(99, 102, 2, 35000, '2026-02-26 10:34:26'),
(100, 101, 2, 50000, '2026-02-26 10:34:26'),
(101, 104, 2, 65000, '2026-02-26 10:34:26'),
(103, 107, 1, 150000, '2026-02-26 10:34:26'),
(104, 108, 1, 95000, '2026-02-26 10:34:26'),
(120, 264, 2, 65000, '2026-02-26 10:34:26'),
(121, 265, 2, 145000, '2026-02-26 10:34:26'),
(122, 266, 2, 145000, '2026-02-26 10:34:26'),
(126, 270, 1, 75000, '2026-02-26 10:34:26'),
(127, 271, 1, 75000, '2026-02-26 10:34:26'),
(128, 272, 1, 75000, '2026-02-26 10:34:26'),
(153, 298, 2, 70000, '2026-04-16 19:16:32'),
(154, 299, 2, 70000, '2026-04-16 19:16:58');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `remember_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_expired` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password_hash`, `is_active`, `last_login`, `created_at`, `remember_token`, `reset_token`, `reset_expired`) VALUES
(1, 'Muhammad Adi Mulyono', 'muhammadadimulyono@gmail.com', 'muhammadadimulyono@gmail.com', '$2y$12$SiL4EgLnXkqlprkypefhEe4AgAYByTywKzjKR8CmF7lw129.wUb4.', 1, NULL, '2026-01-26 20:16:24', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_audit_logs`
--
ALTER TABLE `ai_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_action` (`action_type`),
  ADD KEY `idx_target` (`target_type`,`target_id`);

--
-- Indexes for table `ai_logs`
--
ALTER TABLE `ai_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `body_parts`
--
ALTER TABLE `body_parts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_client_name` (`name`);

--
-- Indexes for table `design_types`
--
ALTER TABLE `design_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_design_name` (`name`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `idx_order_invoice` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `fk_order_design` (`design_type_id`),
  ADD KEY `fk_order_body` (`body_part_id`);

--
-- Indexes for table `order_files`
--
ALTER TABLE `order_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_file` (`order_id`),
  ADD KEY `idx_file_type` (`file_type`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_design` (`design_type_id`),
  ADD KEY `idx_body` (`body_part_id`);


--
-- Indexes for table `order_payments`
--
ALTER TABLE `order_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_payment` (`order_id`),
  ADD KEY `idx_payment_date` (`payment_date`);

--
-- Indexes for table `order_revisions`
--
ALTER TABLE `order_revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_rev` (`order_id`),
  ADD KEY `idx_created_rev` (`created_at`);

--
-- Indexes for table `price_matrix`
--
ALTER TABLE `price_matrix`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_price` (`design_type_id`,`body_part_id`),
  ADD KEY `idx_design` (`design_type_id`),
  ADD KEY `idx_body` (`body_part_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_audit_logs`
--
ALTER TABLE `ai_audit_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_logs`
--
ALTER TABLE `ai_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `body_parts`
--
ALTER TABLE `body_parts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `design_types`
--
ALTER TABLE `design_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=300;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `order_files`
--
ALTER TABLE `order_files`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=356;


--
-- AUTO_INCREMENT for table `order_payments`
--
ALTER TABLE `order_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `order_revisions`
--
ALTER TABLE `order_revisions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `price_matrix`
--
ALTER TABLE `price_matrix`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_inv_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_body` FOREIGN KEY (`body_part_id`) REFERENCES `body_parts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_design` FOREIGN KEY (`design_type_id`) REFERENCES `design_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `order_files`
--
ALTER TABLE `order_files`
  ADD CONSTRAINT `fk_files_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_items_body` FOREIGN KEY (`body_part_id`) REFERENCES `body_parts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_design` FOREIGN KEY (`design_type_id`) REFERENCES `design_types` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;


--
-- Constraints for table `order_payments`
--
ALTER TABLE `order_payments`
  ADD CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_revisions`
--
ALTER TABLE `order_revisions`
  ADD CONSTRAINT `fk_rev_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `price_matrix`
--
ALTER TABLE `price_matrix`
  ADD CONSTRAINT `fk_pm_body` FOREIGN KEY (`body_part_id`) REFERENCES `body_parts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pm_design` FOREIGN KEY (`design_type_id`) REFERENCES `design_types` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
