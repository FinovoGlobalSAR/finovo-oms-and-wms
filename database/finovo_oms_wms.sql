-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 24, 2026 at 08:53 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `finovo_oms_wms`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `store_id` int(11) DEFAULT NULL,
  `actor` varchar(150) NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `store_id`, `actor`, `action`, `entity_type`, `entity_id`, `details`, `created_at`) VALUES
(1, 1, 'Admin User', 'credential_update', 'store', '1', 'Shopify/WooCommerce credentials updated.', '2026-09-17 03:30:31'),
(2, 1, 'Admin User', 'mapping_create', 'product', '38', 'Mapped to external ID 99999', '2026-09-17 03:31:35');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `external_customer_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `store_id`, `name`, `email`, `external_customer_id`, `created_at`) VALUES
(1, 1, 'Unknown', NULL, NULL, '2026-09-08 11:14:37'),
(2, 1, 'ali', NULL, NULL, '2026-09-08 11:14:37'),
(3, 1, 'Sara Khan', NULL, NULL, '2026-09-08 11:14:37'),
(4, 1, 'Ahmed', NULL, NULL, '2026-09-08 11:14:37'),
(5, 1, 'Mahir', NULL, NULL, '2026-09-08 11:14:37');

-- --------------------------------------------------------

--
-- Table structure for table `field_mappings`
--

DROP TABLE IF EXISTS `field_mappings`;
CREATE TABLE `field_mappings` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `entity_type` enum('product','order') NOT NULL,
  `finovo_field` varchar(50) NOT NULL,
  `external_field` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `integration_errors`
--

DROP TABLE IF EXISTS `integration_errors`;
CREATE TABLE `integration_errors` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `connector` varchar(30) NOT NULL,
  `operation` varchar(100) NOT NULL,
  `external_id` varchar(150) DEFAULT NULL,
  `internal_id` varchar(150) DEFAULT NULL,
  `http_status` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 1,
  `next_retry_at` datetime DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `integration_errors`
--

INSERT INTO `integration_errors` (`id`, `store_id`, `connector`, `operation`, `external_id`, `internal_id`, `http_status`, `error_message`, `attempts`, `next_retry_at`, `status`, `created_at`, `updated_at`) VALUES
(2, 1, 'shopify', 'sync_products', NULL, NULL, 401, '{\"errors\":\"[API] Invalid API key or access token (unrecognized login or wrong password)\"}', 1, '2026-09-23 10:25:02', 'resolved', '2026-09-23 08:24:02', '2026-09-23 10:42:49'),
(3, 15, 'prestashop', 'sync_products', NULL, NULL, 500, '<!DOCTYPE html>\n<html lang=\"en\">\n    <head>\n        <title>500 Server Error</title>\n        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n        <meta name=\"description\" content=\"This store is powered by PrestaShop\" />\n        <style>\n            ::-moz-selection {\n                background: #b3d4fc;\n                text-shadow: none;\n            }\n\n            ::selection {\n                background: #b3d4fc;\n                text-shadow: none;\n            }\n\n            html {\n                padding: 30px 10px;\n                font-size: 16px;\n                line-height: 1.4;\n                color: #737373;\n                background: #f0f0f0;\n                -webkit-text-size-adjust: 100%;\n                -ms-text-size-adjust: 100%;\n            }\n\n            html,\n            input {\n                font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif;\n            }\n\n            body {\n                max-width:600px;\n                _width: 600px;\n                padding: 30px 20px 50px;\n                border: 1px solid #b3b3b3;\n                border-radius: 4px;\n                margin: 0 auto;\n                box-shadow: 0 1px 10px #a7a7a7, inset 0 1px 0 #fff;\n                background: #fcfcfc;\n            }\n\n            h1 {\n                margin: 0 10px;\n                font-size: 50px;\n                text-align: center;\n            }\n\n            h1 span {\n                color: #bbb;\n            }\n            h2 {\n                color: #D35780;\n                margin: 0 10px;\n                font-size: 40px;\n                text-align: center;\n            }\n\n            h2 span {\n                color: #bbb;\n                font-size: 60px;\n            }\n\n            h3 {\n                margin: 1.5em 0 0.5em;\n            }\n\n            p {\n                margin: 1em 0;\n            }\n\n            ul {\n                padding: 0 0 0 40px;\n                margin: 1em 0;\n            }\n\n            .container {\n                max-width: 380px;\n                _width: 380px;\n                margin: 0 auto;\n            }\n\n            input::-moz-focus-inner {\n                padding: 0;\n                border: 0;\n            }\n        </style>\n    </head>\n    <body>\n        <div class=\"container\">\n            <h2><span>500</span> Server Error</h2>\n            <p>Oops, something went wrong.<br /><br />Try to refresh this page or feel free to contact us if the problem persists.</p>\n        </div>\n    </body>\n</html>\n', 1, '2026-09-24 05:13:47', 'pending', '2026-09-24 03:12:47', '2026-09-24 03:12:47'),
(4, 13, 'woocommerce', 'sync_products', NULL, NULL, 500, '<!DOCTYPE html>\n<html dir=\'ltr\'>\n<head>\n	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n	<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n			<title>Database Error</title>\n	<style>\n		html {\n			background: #f1f1f1;\n		}\n		body {\n			background: #fff;\n			border: 1px solid #ccd0d4;\n			color: #444;\n			font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", sans-serif;\n			margin: 2em auto;\n			padding: 1em 2em;\n			max-width: 700px;\n			-webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .04);\n			box-shadow: 0 1px 1px rgba(0, 0, 0, .04);\n		}\n		h1 {\n			border-bottom: 1px solid #dadada;\n			clear: both;\n			color: #666;\n			font-size: 24px;\n			margin: 30px 0 0 0;\n			padding: 0;\n			padding-bottom: 7px;\n		}\n		#error-page {\n			margin-top: 50px;\n		}\n		#error-page p,\n		#error-page .wp-die-message {\n			font-size: 14px;\n			line-height: 1.5;\n			margin: 25px 0 20px;\n		}\n		#error-page code {\n			font-family: Consolas, Monaco, monospace;\n		}\n		ul li {\n			margin-bottom: 10px;\n			font-size: 14px ;\n		}\n		a {\n			color: #3858e9;\n		}\n		a:hover,\n		a:active {\n			color: #183ad6;\n		}\n		a:focus {\n			color: #183ad6;\n			box-shadow: 0 0 0 var(--wp-admin-border-width-focus, 1.5px) var(--wp-admin-theme-color, #3858e9);\n			outline: 2px solid transparent;\n		}\n		.button {\n			background: #f3f5f6;\n			border: 1px solid #016087;\n			color: #016087;\n			display: inline-block;\n			text-decoration: none;\n			font-size: 13px;\n			line-height: 2;\n			height: 28px;\n			margin: 0;\n			padding: 0 10px 1px;\n			cursor: pointer;\n			-webkit-border-radius: 3px;\n			-webkit-appearance: none;\n			border-radius: 3px;\n			white-space: nowrap;\n			-webkit-box-sizing: border-box;\n			-moz-box-sizing:    border-box;\n			box-sizing:         border-box;\n\n			vertical-align: top;\n		}\n\n		.button.button-large {\n			line-height: 2.30769231;\n			min-height: 32px;\n			padding: 0 12px;\n		}\n\n		.button:hover,\n		.button:focus {\n			background: #f1f1f1;\n		}\n\n		.button:focus {\n			background: #f3f5f6;\n			border-color: #007cba;\n			-webkit-box-shadow: 0 0 0 1px #007cba;\n			box-shadow: 0 0 0 1px #007cba;\n			color: #016087;\n			outline: 2px solid transparent;\n			outline-offset: 0;\n		}\n\n		.button:active {\n			background: #f3f5f6;\n			border-color: #7e8993;\n			-webkit-box-shadow: none;\n			box-shadow: none;\n		}\n\n			</style>\n</head>\n<body id=\"error-page\">\n	<div class=\"wp-die-message\"><h1>Error establishing a database connection</h1></div></body>\n</html>\n	', 1, '2026-09-24 05:16:24', 'pending', '2026-09-24 03:15:24', '2026-09-24 03:15:24'),
(5, 13, 'woocommerce', 'sync_products', NULL, NULL, 0, 'No response', 1, '2026-09-24 05:18:09', 'pending', '2026-09-24 03:17:09', '2026-09-24 03:17:09');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_ledger`
--

DROP TABLE IF EXISTS `inventory_ledger`;
CREATE TABLE `inventory_ledger` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `warehouse_id` int(11) NOT NULL,
  `quantity_before` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `quantity_after` int(11) NOT NULL,
  `source` varchar(50) NOT NULL,
  `reference` varchar(150) DEFAULT NULL,
  `actor` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_ledger`
--

INSERT INTO `inventory_ledger` (`id`, `product_id`, `variant_id`, `warehouse_id`, `quantity_before`, `quantity_change`, `quantity_after`, `source`, `reference`, `actor`, `created_at`) VALUES
(1, 39, NULL, 1, 50, -2, 48, 'custom_bridge_webhook', 'external:BRIDGE-ORD-1789624721', 'webhook', '2026-09-17 05:58:41'),
(2, 34, NULL, 3, 3, -3, 0, 'manual_order', 'order_group:ORD-20260921125227-343', 'Admin User', '2026-09-21 10:52:27'),
(3, 34, NULL, 1, 2, -2, 0, 'manual_order', 'order_group:ORD-20260921125227-343', 'Admin User', '2026-09-21 10:52:27'),
(4, 190, NULL, 3, 100, -1, 99, 'oscommerce_pull', 'external:4', 'oscommerce_sync', '2026-09-22 08:44:39'),
(5, 205, NULL, 3, 100, -1, 99, 'oscommerce_pull', 'external:3', 'oscommerce_sync', '2026-09-22 08:44:39'),
(6, 215, NULL, 3, 2038, -1, 2037, 'oscommerce_pull', 'external:2', 'oscommerce_sync', '2026-09-22 08:44:39'),
(7, 190, NULL, 3, 99, 1, 100, 'return_completed', 'return:3', 'Admin User', '2026-09-22 09:30:35'),
(8, 198, NULL, 3, 100, -10, 90, 'manual_order', 'order_group:ORD-20260923081055-852', 'Admin User', '2026-09-23 06:10:55');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `store_id` int(11) NOT NULL,
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `result_message` text DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `type`, `store_id`, `status`, `result_message`, `attempts`, `created_at`, `updated_at`) VALUES
(1, 'shopify_products_sync', 1, 'failed', 'Shopify API error (HTTP 401).', 1, '2026-09-23 08:21:54', '2026-09-23 08:22:49'),
(2, 'oscommerce_products_sync', 18, 'completed', '0 product(s) synced.', 1, '2026-09-24 03:45:18', '2026-09-24 03:45:57'),
(3, 'bigcommerce_products_sync', 9, 'completed', '0 product(s) synced.', 1, '2026-09-24 04:56:39', '2026-09-24 04:56:41'),
(4, 'bigcommerce_products_sync', 14, 'completed', '0 product(s) synced.', 1, '2026-09-24 04:56:39', '2026-09-24 04:56:42'),
(5, 'opencart_products_sync', 17, 'completed', '0 product(s) synced.', 1, '2026-09-24 04:56:39', '2026-09-24 04:56:42'),
(6, 'oscommerce_products_sync', 18, 'completed', '0 product(s) synced.', 1, '2026-09-24 04:56:39', '2026-09-24 04:56:43'),
(7, 'prestashop_products_sync', 10, 'completed', '0 product(s) synced.', 1, '2026-09-24 04:56:39', '2026-09-24 04:56:51'),
(8, 'prestashop_products_sync', 15, 'completed', '0 product(s) synced.', 1, '2026-09-24 04:56:39', '2026-09-24 04:56:53'),
(9, 'prestashop_products_sync', 16, 'completed', '19 product(s) synced.', 1, '2026-09-24 04:56:39', '2026-09-24 04:57:08'),
(10, 'shopify_products_sync', 1, 'completed', '0 product(s) synced.', 1, '2026-09-24 04:56:39', '2026-09-24 04:57:08'),
(11, 'shopify_products_sync', 7, 'failed', 'Shopify API error (HTTP 401).', 1, '2026-09-24 04:56:40', '2026-09-24 04:57:09'),
(12, 'shopify_products_sync', 12, 'completed', '0 product(s) synced.', 1, '2026-09-24 04:56:40', '2026-09-24 04:57:09'),
(13, 'woocommerce_products_sync', 3, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 04:56:40', '2026-09-24 04:57:17'),
(14, 'woocommerce_products_sync', 8, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 04:56:40', '2026-09-24 04:57:18'),
(15, 'woocommerce_products_sync', 13, 'completed', '0 product(s) synced.', 1, '2026-09-24 04:56:40', '2026-09-24 04:57:20'),
(16, 'bigcommerce_products_sync', 9, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:01:06', '2026-09-24 05:01:07'),
(17, 'bigcommerce_products_sync', 14, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:01:06', '2026-09-24 05:01:08'),
(18, 'opencart_products_sync', 17, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:01:06', '2026-09-24 05:01:08'),
(19, 'oscommerce_products_sync', 18, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:01:06', '2026-09-24 05:01:08'),
(20, 'prestashop_products_sync', 10, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:01:06', '2026-09-24 05:01:09'),
(21, 'prestashop_products_sync', 15, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:01:06', '2026-09-24 05:01:09'),
(22, 'prestashop_products_sync', 16, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:01:06', '2026-09-24 05:01:09'),
(23, 'shopify_products_sync', 1, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:01:06', '2026-09-24 05:01:10'),
(24, 'shopify_products_sync', 7, 'failed', 'Shopify API error (HTTP 401).', 1, '2026-09-24 05:01:06', '2026-09-24 05:01:10'),
(25, 'shopify_products_sync', 12, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:01:06', '2026-09-24 05:01:10'),
(26, 'woocommerce_products_sync', 3, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:01:06', '2026-09-24 05:01:12'),
(27, 'woocommerce_products_sync', 8, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:01:06', '2026-09-24 05:01:13'),
(28, 'woocommerce_products_sync', 13, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:01:06', '2026-09-24 05:01:14'),
(29, 'bigcommerce_products_sync', 9, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:06:06', '2026-09-24 05:06:07'),
(30, 'bigcommerce_products_sync', 14, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:06:06', '2026-09-24 05:06:08'),
(31, 'opencart_products_sync', 17, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:06:06', '2026-09-24 05:06:08'),
(32, 'oscommerce_products_sync', 18, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:06:06', '2026-09-24 05:06:08'),
(33, 'prestashop_products_sync', 10, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:06:07', '2026-09-24 05:06:09'),
(34, 'prestashop_products_sync', 15, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:06:07', '2026-09-24 05:06:09'),
(35, 'prestashop_products_sync', 16, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:06:07', '2026-09-24 05:06:09'),
(36, 'shopify_products_sync', 1, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:06:07', '2026-09-24 05:06:10'),
(37, 'shopify_products_sync', 7, 'failed', 'Shopify API error (HTTP 401).', 1, '2026-09-24 05:06:07', '2026-09-24 05:06:10'),
(38, 'shopify_products_sync', 12, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:06:07', '2026-09-24 05:06:10'),
(39, 'woocommerce_products_sync', 3, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:06:07', '2026-09-24 05:06:12'),
(40, 'woocommerce_products_sync', 8, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:06:07', '2026-09-24 05:06:13'),
(41, 'woocommerce_products_sync', 13, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:06:07', '2026-09-24 05:06:14'),
(42, 'bigcommerce_products_sync', 9, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:11:06', '2026-09-24 05:11:07'),
(43, 'bigcommerce_products_sync', 14, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:11:06', '2026-09-24 05:11:08'),
(44, 'opencart_products_sync', 17, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:11:06', '2026-09-24 05:11:08'),
(45, 'oscommerce_products_sync', 18, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:11:06', '2026-09-24 05:11:08'),
(46, 'prestashop_products_sync', 10, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:11:06', '2026-09-24 05:11:09'),
(47, 'prestashop_products_sync', 15, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:11:06', '2026-09-24 05:11:09'),
(48, 'prestashop_products_sync', 16, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:11:06', '2026-09-24 05:11:09'),
(49, 'shopify_products_sync', 1, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:11:06', '2026-09-24 05:11:09'),
(50, 'shopify_products_sync', 7, 'failed', 'Shopify API error (HTTP 401).', 1, '2026-09-24 05:11:06', '2026-09-24 05:11:10'),
(51, 'shopify_products_sync', 12, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:11:06', '2026-09-24 05:11:10'),
(52, 'woocommerce_products_sync', 3, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:11:06', '2026-09-24 05:11:12'),
(53, 'woocommerce_products_sync', 8, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:11:06', '2026-09-24 05:11:13'),
(54, 'woocommerce_products_sync', 13, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:11:06', '2026-09-24 05:11:14'),
(55, 'bigcommerce_products_sync', 9, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:16:07', '2026-09-24 05:16:08'),
(56, 'bigcommerce_products_sync', 14, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:16:07', '2026-09-24 05:16:09'),
(57, 'opencart_products_sync', 17, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:16:07', '2026-09-24 05:16:09'),
(58, 'oscommerce_products_sync', 18, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:16:07', '2026-09-24 05:16:09'),
(59, 'prestashop_products_sync', 10, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:16:07', '2026-09-24 05:16:09'),
(60, 'prestashop_products_sync', 15, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:16:07', '2026-09-24 05:16:10'),
(61, 'prestashop_products_sync', 16, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:16:07', '2026-09-24 05:16:10'),
(62, 'shopify_products_sync', 1, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:16:07', '2026-09-24 05:16:10'),
(63, 'shopify_products_sync', 7, 'failed', 'Shopify API error (HTTP 401).', 1, '2026-09-24 05:16:07', '2026-09-24 05:16:11'),
(64, 'shopify_products_sync', 12, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:16:07', '2026-09-24 05:16:11'),
(65, 'woocommerce_products_sync', 3, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:16:07', '2026-09-24 05:16:14'),
(66, 'woocommerce_products_sync', 8, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:16:07', '2026-09-24 05:16:16'),
(67, 'woocommerce_products_sync', 13, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:16:07', '2026-09-24 05:16:17'),
(68, 'bigcommerce_products_sync', 9, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:21:06', '2026-09-24 05:21:07'),
(69, 'bigcommerce_products_sync', 14, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:21:06', '2026-09-24 05:21:08'),
(70, 'opencart_products_sync', 17, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:21:06', '2026-09-24 05:21:08'),
(71, 'oscommerce_products_sync', 18, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:21:06', '2026-09-24 05:21:08'),
(72, 'prestashop_products_sync', 10, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:21:06', '2026-09-24 05:21:09'),
(73, 'prestashop_products_sync', 15, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:21:06', '2026-09-24 05:21:09'),
(74, 'prestashop_products_sync', 16, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:21:06', '2026-09-24 05:21:09'),
(75, 'shopify_products_sync', 1, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:21:06', '2026-09-24 05:21:10'),
(76, 'shopify_products_sync', 7, 'failed', 'Shopify API error (HTTP 401).', 1, '2026-09-24 05:21:06', '2026-09-24 05:21:10'),
(77, 'shopify_products_sync', 12, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:21:06', '2026-09-24 05:21:10'),
(78, 'woocommerce_products_sync', 3, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:21:06', '2026-09-24 05:21:13'),
(79, 'woocommerce_products_sync', 8, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:21:06', '2026-09-24 05:21:14'),
(80, 'woocommerce_products_sync', 13, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:21:06', '2026-09-24 05:21:15'),
(81, 'bigcommerce_products_sync', 9, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:26:06', '2026-09-24 05:26:07'),
(82, 'bigcommerce_products_sync', 14, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:26:06', '2026-09-24 05:26:08'),
(83, 'opencart_products_sync', 17, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:26:06', '2026-09-24 05:26:08'),
(84, 'oscommerce_products_sync', 18, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:26:06', '2026-09-24 05:26:08'),
(85, 'prestashop_products_sync', 10, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:26:06', '2026-09-24 05:26:09'),
(86, 'prestashop_products_sync', 15, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:26:06', '2026-09-24 05:26:09'),
(87, 'prestashop_products_sync', 16, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:26:06', '2026-09-24 05:26:09'),
(88, 'shopify_products_sync', 1, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:26:06', '2026-09-24 05:26:09'),
(89, 'shopify_products_sync', 7, 'failed', 'Shopify API error (HTTP 401).', 1, '2026-09-24 05:26:06', '2026-09-24 05:26:10'),
(90, 'shopify_products_sync', 12, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:26:06', '2026-09-24 05:26:10'),
(91, 'woocommerce_products_sync', 3, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:26:06', '2026-09-24 05:26:12'),
(92, 'woocommerce_products_sync', 8, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:26:06', '2026-09-24 05:26:13'),
(93, 'woocommerce_products_sync', 13, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:26:06', '2026-09-24 05:26:14'),
(94, 'bigcommerce_products_sync', 9, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:31:06', '2026-09-24 05:31:07'),
(95, 'bigcommerce_products_sync', 14, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:31:06', '2026-09-24 05:31:08'),
(96, 'opencart_products_sync', 17, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:31:06', '2026-09-24 05:31:08'),
(97, 'oscommerce_products_sync', 18, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:31:06', '2026-09-24 05:31:08'),
(98, 'prestashop_products_sync', 10, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:31:06', '2026-09-24 05:31:09'),
(99, 'prestashop_products_sync', 15, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:31:06', '2026-09-24 05:31:09'),
(100, 'prestashop_products_sync', 16, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:31:06', '2026-09-24 05:31:09'),
(101, 'shopify_products_sync', 1, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:31:06', '2026-09-24 05:31:09'),
(102, 'shopify_products_sync', 7, 'failed', 'Shopify API error (HTTP 401).', 1, '2026-09-24 05:31:06', '2026-09-24 05:31:10'),
(103, 'shopify_products_sync', 12, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:31:06', '2026-09-24 05:31:10'),
(104, 'woocommerce_products_sync', 3, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:31:06', '2026-09-24 05:31:12'),
(105, 'woocommerce_products_sync', 8, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:31:06', '2026-09-24 05:31:13'),
(106, 'woocommerce_products_sync', 13, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:31:06', '2026-09-24 05:31:14'),
(107, 'bigcommerce_products_sync', 9, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:36:06', '2026-09-24 05:36:07'),
(108, 'bigcommerce_products_sync', 14, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:36:06', '2026-09-24 05:36:09'),
(109, 'opencart_products_sync', 17, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:36:06', '2026-09-24 05:36:09'),
(110, 'oscommerce_products_sync', 18, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:36:07', '2026-09-24 05:36:09'),
(111, 'prestashop_products_sync', 10, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:36:07', '2026-09-24 05:36:09'),
(112, 'prestashop_products_sync', 15, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:36:07', '2026-09-24 05:36:09'),
(113, 'prestashop_products_sync', 16, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:36:07', '2026-09-24 05:36:10'),
(114, 'shopify_products_sync', 1, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:36:07', '2026-09-24 05:36:10'),
(115, 'shopify_products_sync', 7, 'failed', 'Shopify API error (HTTP 401).', 1, '2026-09-24 05:36:07', '2026-09-24 05:36:10'),
(116, 'shopify_products_sync', 12, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:36:07', '2026-09-24 05:36:11'),
(117, 'woocommerce_products_sync', 3, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:36:07', '2026-09-24 05:36:13'),
(118, 'woocommerce_products_sync', 8, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:36:07', '2026-09-24 05:36:13'),
(119, 'woocommerce_products_sync', 13, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:36:07', '2026-09-24 05:36:14'),
(120, 'bigcommerce_products_sync', 9, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:41:06', '2026-09-24 05:41:07'),
(121, 'bigcommerce_products_sync', 14, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:41:06', '2026-09-24 05:41:08'),
(122, 'opencart_products_sync', 17, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:41:06', '2026-09-24 05:41:08'),
(123, 'oscommerce_products_sync', 18, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:41:06', '2026-09-24 05:41:08'),
(124, 'prestashop_products_sync', 10, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:41:06', '2026-09-24 05:41:08'),
(125, 'prestashop_products_sync', 15, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:41:06', '2026-09-24 05:41:09'),
(126, 'prestashop_products_sync', 16, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:41:06', '2026-09-24 05:41:09'),
(127, 'shopify_products_sync', 1, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:41:06', '2026-09-24 05:41:09'),
(128, 'shopify_products_sync', 7, 'failed', 'Shopify API error (HTTP 401).', 1, '2026-09-24 05:41:06', '2026-09-24 05:41:10'),
(129, 'shopify_products_sync', 12, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:41:06', '2026-09-24 05:41:10'),
(130, 'woocommerce_products_sync', 3, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:41:06', '2026-09-24 05:41:12'),
(131, 'woocommerce_products_sync', 8, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:41:06', '2026-09-24 05:41:13'),
(132, 'woocommerce_products_sync', 13, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:41:06', '2026-09-24 05:41:13'),
(133, 'bigcommerce_products_sync', 9, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:46:06', '2026-09-24 05:46:07'),
(134, 'bigcommerce_products_sync', 14, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:46:06', '2026-09-24 05:46:08'),
(135, 'opencart_products_sync', 17, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:46:06', '2026-09-24 05:46:08'),
(136, 'oscommerce_products_sync', 18, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:46:06', '2026-09-24 05:46:08'),
(137, 'prestashop_products_sync', 10, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:46:06', '2026-09-24 05:46:09'),
(138, 'prestashop_products_sync', 15, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:46:06', '2026-09-24 05:46:09'),
(139, 'prestashop_products_sync', 16, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:46:06', '2026-09-24 05:46:09'),
(140, 'shopify_products_sync', 1, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:46:06', '2026-09-24 05:46:10'),
(141, 'shopify_products_sync', 7, 'failed', 'Shopify API error (HTTP 401).', 1, '2026-09-24 05:46:06', '2026-09-24 05:46:10'),
(142, 'shopify_products_sync', 12, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:46:06', '2026-09-24 05:46:10'),
(143, 'woocommerce_products_sync', 3, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:46:06', '2026-09-24 05:46:12'),
(144, 'woocommerce_products_sync', 8, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:46:06', '2026-09-24 05:46:13'),
(145, 'woocommerce_products_sync', 13, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:46:06', '2026-09-24 05:46:14'),
(146, 'bigcommerce_products_sync', 9, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:51:06', '2026-09-24 05:51:07'),
(147, 'bigcommerce_products_sync', 14, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:51:06', '2026-09-24 05:51:08'),
(148, 'opencart_products_sync', 17, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:51:06', '2026-09-24 05:51:08'),
(149, 'oscommerce_products_sync', 18, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:51:06', '2026-09-24 05:51:08'),
(150, 'prestashop_products_sync', 10, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:51:06', '2026-09-24 05:51:09'),
(151, 'prestashop_products_sync', 15, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:51:06', '2026-09-24 05:51:09'),
(152, 'prestashop_products_sync', 16, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:51:06', '2026-09-24 05:51:09'),
(153, 'shopify_products_sync', 1, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:51:06', '2026-09-24 05:51:09'),
(154, 'shopify_products_sync', 7, 'failed', 'Shopify API error (HTTP 401).', 1, '2026-09-24 05:51:06', '2026-09-24 05:51:10'),
(155, 'shopify_products_sync', 12, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:51:06', '2026-09-24 05:51:10'),
(156, 'woocommerce_products_sync', 3, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:51:06', '2026-09-24 05:51:12'),
(157, 'woocommerce_products_sync', 8, 'failed', 'WooCommerce API error (HTTP 401).', 1, '2026-09-24 05:51:06', '2026-09-24 05:51:13'),
(158, 'woocommerce_products_sync', 13, 'completed', '0 product(s) synced.', 1, '2026-09-24 05:51:06', '2026-09-24 05:51:13');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(11) NOT NULL,
  `migration` varchar(255) NOT NULL,
  `run_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `run_at`) VALUES
(1, '001_create_companies_table.php', '2026-09-04 03:30:58'),
(2, '002_create_roles_table.php', '2026-09-04 03:30:58'),
(5, '005_create_users_table.php', '2026-09-04 03:30:59'),
(6, '007_create_orders_table.php', '2026-09-04 08:36:11'),
(7, '008_create_settings_table.php', '2026-09-04 08:36:12'),
(8, '010_set_fixed_api_key.php', '2026-09-04 10:29:27'),
(9, '011_add_shopify_settings.php', '2026-09-07 03:13:15'),
(10, '012_add_external_order_id.php', '2026-09-07 03:13:15'),
(11, '013_create_stores_table.php', '2026-09-07 07:39:04'),
(12, '014_create_customers_table.php', '2026-09-07 07:39:04'),
(13, '015_create_products_table.php', '2026-09-07 07:39:04'),
(14, '016_restructure_orders_table.php', '2026-09-07 07:39:04'),
(16, '018_add_order_status.php', '2026-09-09 07:52:36'),
(17, '019_add_product_stock.php', '2026-09-09 09:28:06'),
(18, '020_create_warehouses.php', '2026-09-10 07:07:20'),
(19, '017_inventory_and_warehouse_system.php', '2026-09-10 09:32:50'),
(24, '018_remove_companies_single_tenant.php', '2026-09-10 15:23:44'),
(25, '019_add_images_and_variants.php', '2026-09-11 03:18:52'),
(26, '020_add_stock_warning.php', '2026-09-11 08:29:10'),
(27, '021_store_warehouse_many_to_many.php', '2026-09-11 08:29:11'),
(28, '022_add_invoice_fields.php', '2026-09-12 04:34:01'),
(29, '023_rename_roles_to_staff.php', '2026-09-12 04:34:01'),
(30, '024_add_password_reset_fields.php', '2026-09-12 04:53:53'),
(31, '025_woocommerce_per_store.php', '2026-09-12 06:08:00'),
(32, '026_shipments.php', '2026-09-13 16:25:41'),
(33, '027_core_oms_features.php', '2026-09-14 07:34:16'),
(34, '028_sku_mapping_and_ledger.php', '2026-09-16 05:07:50'),
(35, '029_webhooks.php', '2026-09-16 16:10:17'),
(36, '030_outbox_and_errors.php', '2026-09-16 17:57:23'),
(37, '031_store_health.php', '2026-09-17 03:03:44'),
(38, '032_audit_log.php', '2026-09-17 03:25:45'),
(39, '033_custom_bridge.php', '2026-09-17 04:26:18'),
(40, '034_fix_orders_status_column.php', '2026-09-21 04:02:23'),
(41, '035_bigcommerce.php', '2026-09-21 05:32:18'),
(42, '036_returns.php', '2026-09-22 09:08:33'),
(43, '037_field_mappings.php', '2026-09-22 09:53:08'),
(44, '038_3pl_fields.php', '2026-09-23 03:34:55'),
(45, '039_stripe_payments.php', '2026-09-23 06:04:14'),
(46, '040_jobs.php', '2026-09-23 08:16:31');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `source` varchar(20) NOT NULL DEFAULT 'manual',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `external_order_id` varchar(100) DEFAULT NULL,
  `store_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `external_wc_order_id` varchar(100) DEFAULT NULL,
  `payment_status` varchar(50) NOT NULL DEFAULT 'unpaid',
  `warehouse_id` int(11) DEFAULT NULL,
  `order_group` varchar(64) DEFAULT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `variant_label` varchar(150) DEFAULT NULL,
  `stock_warning` tinyint(1) NOT NULL DEFAULT 0,
  `customer_email` varchar(150) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(100) DEFAULT NULL,
  `shipment_id` int(11) DEFAULT NULL,
  `picking_status` varchar(50) NOT NULL DEFAULT 'not_started',
  `external_bc_order_id` varchar(100) DEFAULT NULL,
  `external_ps_order_id` varchar(100) DEFAULT NULL,
  `external_ocart_order_id` varchar(100) DEFAULT NULL,
  `external_osc_order_id` varchar(100) DEFAULT NULL,
  `stripe_session_id` varchar(255) DEFAULT NULL,
  `external_wix_order_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_name`, `product_name`, `quantity`, `price`, `status`, `source`, `created_at`, `external_order_id`, `store_id`, `customer_id`, `product_id`, `external_wc_order_id`, `payment_status`, `warehouse_id`, `order_group`, `variant_id`, `variant_label`, `stock_warning`, `customer_email`, `customer_phone`, `billing_address`, `shipping_address`, `discount`, `shipping_cost`, `tax`, `payment_method`, `shipment_id`, `picking_status`, `external_bc_order_id`, `external_ps_order_id`, `external_ocart_order_id`, `external_osc_order_id`, `stripe_session_id`, `external_wix_order_id`) VALUES
(1, 'Mahir', 't shirt', 1, 50.00, 'Pending', 'manual', '2026-09-04 08:43:50', NULL, 2, NULL, NULL, NULL, 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'packed', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Ahmed', 'Shoes', 2, 1500.00, 'Pending', 'api_push', '2026-09-04 08:57:33', NULL, 5, NULL, NULL, NULL, 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'packed', NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Sara Khan', 'Wireless Mouse', 1, 1200.00, 'Pending', 'api_push', '2026-09-04 11:17:51', NULL, 5, NULL, NULL, NULL, 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'packed', NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'ali', 'mobile cover', 1, 500.00, '', 'manual', '2026-09-04 11:18:21', '8566898786490', 2, NULL, NULL, NULL, 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'packed', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Unknown', 'test shirt', 1, 3.00, 'pending', 'shopify_pull', '2026-09-07 03:30:16', '8563398377658', 1, NULL, NULL, NULL, 'paid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, 8, 'packed', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'Unknown', 'Mobile Cover', 1, 500.00, 'delivered', 'shopify_pull', '2026-09-08 10:44:27', '8566990700730', 1, NULL, NULL, NULL, 'paid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, 10, 'packed', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'Unknown', 'Mobile Cover', 1, 500.00, 'Delivered', 'csv_import', '2026-09-08 11:14:37', NULL, 5, 1, 3, NULL, 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, 9, 'packed', NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'Unknown', 'test shirt', 1, 3.00, 'Pending', 'csv_import', '2026-09-08 11:14:37', NULL, 5, 1, 26, NULL, 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'ali', 'mobile cover', 1, 500.00, 'Pending', 'csv_import', '2026-09-08 11:14:37', NULL, 5, 2, 3, NULL, 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'Sara Khan', 'Wireless Mouse', 1, 1200.00, 'Delivered', 'csv_import', '2026-09-08 11:14:37', NULL, 5, 3, 1, NULL, 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, 5, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'Ahmed', 'Shoes', 2, 1500.00, 'Pending', 'csv_import', '2026-09-08 11:14:37', NULL, 5, 4, 27, NULL, 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'Mahir', 't shirt', 1, 50.00, 'Pending', 'csv_import', '2026-09-08 11:14:37', NULL, 5, 5, 28, NULL, 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(22, 'Haris', 'Router', 2, 5000.00, 'Delivered', 'manual', '2026-09-09 05:35:39', '8569659031738', 2, NULL, NULL, NULL, 'paid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(23, 'Rauf Khan', 'Bluetooth Speaker', 2, 298.00, 'Pending', 'woocommerce_pull', '2026-09-09 06:05:56', NULL, 3, NULL, NULL, '17', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(24, 'Order #1006', 'Bluetooth Speaker', 1, 149.00, 'delivered', 'shopify_pull', '2026-09-09 10:36:38', '8569874809018', 1, NULL, NULL, NULL, 'paid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, 11, 'packed', NULL, NULL, NULL, NULL, NULL, NULL),
(25, 'Order #1004', 'Mobile Cover', 1, 500.00, 'pending', 'shopify_pull', '2026-09-09 10:36:38', '8567034577082', 1, NULL, NULL, NULL, 'paid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, 12, 'packed', NULL, NULL, NULL, NULL, NULL, NULL),
(26, 'rehan', 'mobile cover', 2, 150.00, 'Pending', 'manual', '2026-09-09 11:00:13', NULL, 2, NULL, NULL, NULL, 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(27, 'Order #1007', 'Bluetooth Speaker', 1, 149.00, 'Delivered', 'shopify_pull', '2026-09-09 11:01:34', '8569900695738', 1, NULL, NULL, NULL, 'paid', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, 3, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(28, 'Yaris', 'Earbuds', 10, 600.00, 'Pending', 'manual', '2026-09-10 09:44:33', NULL, 2, NULL, NULL, NULL, 'unpaid', NULL, 'ORD-20260910114433-575', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(29, 'Rizwan', 'bluetooth headphone', 273, 50.00, 'Pending', 'manual', '2026-09-10 11:30:11', NULL, 2, NULL, NULL, NULL, 'unpaid', NULL, 'ORD-20260910133011-731', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(30, 'ali', 'test-pants', 60, 14.00, 'Pending', 'manual', '2026-09-11 05:12:19', NULL, 2, NULL, NULL, NULL, 'paid', NULL, 'ORD-20260911071219-671', 3, 'Large / Blue', 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'COD', 7, 'packed', NULL, NULL, NULL, NULL, NULL, NULL),
(31, 'khan', 'laptop', 3, 1000.00, 'Delivered', 'manual', '2026-09-11 09:27:56', NULL, 2, NULL, 33, NULL, 'unpaid', 1, 'ORD-20260911112756-541', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, 4, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(32, 'karim', 'test-pants', 20, 10.00, 'Delivered', 'manual', '2026-09-11 09:44:18', NULL, 2, NULL, 36, NULL, 'paid', 1, 'ORD-20260911114418-198', 1, 'Small / Blue', 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'JazzCash', 6, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(33, 'Yaris', 'laptop', 1, 1000.00, 'Delivered', 'manual', '2026-09-11 16:39:57', NULL, 2, NULL, 33, NULL, 'paid', 1, 'ORD-20260911183957-340', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'COD', 1, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(34, 'Rizwan', 'Shoes', 40, 0.00, 'Pending', 'manual', '2026-09-12 06:34:52', NULL, 3, NULL, 38, NULL, 'unpaid', 1, 'ORD-20260912083452-250', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(35, 'Ashmal', 'laptop', 100, 1000.00, 'Delivered', 'manual', '2026-09-14 02:45:59', NULL, 2, NULL, 33, NULL, 'paid', 1, 'ORD-20260914044559-405', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Bank Transfer', 2, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(36, 'Haris -', 'Bluetooth Speaker', 1, 149.00, 'pending', 'shopify_pull', '2026-09-16 17:46:35', '8591666413754', 1, NULL, 34, NULL, 'paid', 3, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', 13, 'packed', NULL, NULL, NULL, NULL, NULL, NULL),
(37, 'Bridge Test Customer', 'Bridge Product #1', 2, 50.00, 'Pending', 'custom_bridge_pull', '2026-09-17 05:58:41', 'BRIDGE-ORD-1789624721', 4, NULL, 39, NULL, 'unpaid', 1, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(38, 'Haris -', 'Bluetooth Speaker', 1, 149.00, 'processing', 'shopify_pull', '2026-09-21 04:02:33', '8591666413754', 7, NULL, 42, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(39, '', 'Bluetooth Speaker', 1, 149.00, 'delivered', 'shopify_pull', '2026-09-21 04:02:33', '8591639707834', 7, NULL, 42, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(40, 'ali -', 'Bluetooth Speaker', 1, 149.00, 'delivered', 'shopify_pull', '2026-09-21 04:02:33', '8569900695738', 7, NULL, 42, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(41, 'Haris -', 'Bluetooth Speaker', 1, 149.00, 'delivered', 'shopify_pull', '2026-09-21 04:02:33', '8569874809018', 7, NULL, 42, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(42, 'Haris -', 'Router', 2, 10000.00, 'delivered', 'shopify_pull', '2026-09-21 04:02:33', '8569659031738', 7, NULL, 49, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(43, '', 'Mobile Cover', 1, 500.00, 'processing', 'shopify_pull', '2026-09-21 04:02:33', '8567034577082', 7, NULL, 47, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(44, 'ali -', 'Mobile Cover', 1, 500.00, 'delivered', 'shopify_pull', '2026-09-21 04:02:33', '8566990700730', 7, NULL, 47, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(45, 'ali -', 'mobile cover', 1, 500.00, 'pending', 'shopify_pull', '2026-09-21 04:02:33', '8566898786490', 7, NULL, 47, NULL, 'unpaid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(46, 'Ayumu Hirano', 'test shirt', 1, 3.00, 'delivered', 'shopify_pull', '2026-09-21 04:02:33', '8563398377658', 7, NULL, 50, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(47, 'faizan', 'Shoes', 1, 0.00, 'pending', 'woocommerce_pull', '2026-09-21 04:25:27', NULL, 8, NULL, 51, '21', 'unpaid', 2, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, '', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(48, 'Wali ali', 'Shoes', 10, 0.00, 'pending', 'woocommerce_pull', '2026-09-21 04:25:27', NULL, 8, NULL, 51, '18', 'unpaid', 2, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, '', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(49, 'Bashir Ali', 'Shoes', 5, 0.00, 'pending', 'woocommerce_pull', '2026-09-21 04:25:27', NULL, 8, NULL, 51, '17', 'unpaid', 2, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Other', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(50, '', 'Shoes', 1, 0.00, 'delivered', 'woocommerce_pull', '2026-09-21 04:25:27', NULL, 8, NULL, 51, '16', 'paid', 2, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, '', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(51, '', 'Unknown product', 1, 0.00, 'delivered', 'woocommerce_pull', '2026-09-21 04:25:27', NULL, 8, NULL, 52, '15', 'paid', 2, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, '', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(52, 'Order #5', 'PrestaShop Order #5', 1, 20.90, 'pending', 'prestashop_pull', '2026-09-21 10:01:26', NULL, 10, NULL, 67, NULL, 'unpaid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, '5', NULL, NULL, NULL, NULL),
(53, 'Order #4', 'PrestaShop Order #4', 1, 14.90, 'pending', 'prestashop_pull', '2026-09-21 10:01:27', NULL, 10, NULL, 68, NULL, 'unpaid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, '4', NULL, NULL, NULL, NULL),
(54, 'Order #3', 'PrestaShop Order #3', 1, 14.90, 'cancelled', 'prestashop_pull', '2026-09-21 10:01:27', NULL, 10, NULL, 69, NULL, 'unpaid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, '3', NULL, NULL, NULL, NULL),
(55, 'Order #2', 'PrestaShop Order #2', 1, 169.90, 'pending', 'prestashop_pull', '2026-09-21 10:01:27', NULL, 10, NULL, 70, NULL, 'unpaid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, '2', NULL, NULL, NULL, NULL),
(56, 'Order #1', 'PrestaShop Order #1', 1, 61.80, 'cancelled', 'prestashop_pull', '2026-09-21 10:01:27', NULL, 10, NULL, 71, NULL, 'unpaid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, '1', NULL, NULL, NULL, NULL),
(57, 'umer', 'Bluetooth Speaker', 3, 149.00, 'pending', 'manual', '2026-09-21 10:52:27', NULL, 1, NULL, 34, NULL, 'unpaid', 3, 'ORD-20260921125227-343', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'COD', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(58, 'umer', 'Bluetooth Speaker', 2, 149.00, 'pending', 'manual', '2026-09-21 10:52:27', NULL, 1, NULL, 34, NULL, 'unpaid', 1, 'ORD-20260921125227-343', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'COD', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(59, 'Haris -', 'Bluetooth Speaker', 1, 149.00, 'processing', 'shopify_pull', '2026-09-21 11:11:25', '8591666413754', 12, NULL, 91, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(60, '', 'Bluetooth Speaker', 1, 149.00, 'delivered', 'shopify_pull', '2026-09-21 11:11:25', '8591639707834', 12, NULL, 91, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(61, 'ali -', 'Bluetooth Speaker', 1, 149.00, 'delivered', 'shopify_pull', '2026-09-21 11:11:25', '8569900695738', 12, NULL, 91, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(62, 'Haris -', 'Bluetooth Speaker', 1, 149.00, 'delivered', 'shopify_pull', '2026-09-21 11:11:25', '8569874809018', 12, NULL, 91, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(63, 'Haris -', 'Router', 2, 10000.00, 'delivered', 'shopify_pull', '2026-09-21 11:11:25', '8569659031738', 12, NULL, 92, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(64, '', 'Mobile Cover', 1, 500.00, 'processing', 'shopify_pull', '2026-09-21 11:11:25', '8567034577082', 12, NULL, 93, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(65, 'ali -', 'Mobile Cover', 1, 500.00, 'delivered', 'shopify_pull', '2026-09-21 11:11:25', '8566990700730', 12, NULL, 93, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(66, 'ali -', 'mobile cover', 1, 500.00, 'pending', 'shopify_pull', '2026-09-21 11:11:25', '8566898786490', 12, NULL, 93, NULL, 'unpaid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(67, 'Ayumu Hirano', 'test shirt', 1, 3.00, 'delivered', 'shopify_pull', '2026-09-21 11:11:25', '8563398377658', 12, NULL, 94, NULL, 'paid', 1, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(68, 'faizan', 'Shoes', 1, 0.00, 'pending', 'woocommerce_pull', '2026-09-21 11:14:16', NULL, 13, NULL, 103, '21', 'unpaid', 3, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, '', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(69, 'Wali ali', 'Shoes', 10, 0.00, 'pending', 'woocommerce_pull', '2026-09-21 11:14:16', NULL, 13, NULL, 103, '18', 'unpaid', 3, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, '', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(70, 'Bashir Ali', 'Shoes', 5, 0.00, 'pending', 'woocommerce_pull', '2026-09-21 11:14:16', NULL, 13, NULL, 103, '17', 'unpaid', 3, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Other', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(71, '', 'Shoes', 1, 0.00, 'delivered', 'woocommerce_pull', '2026-09-21 11:14:16', NULL, 13, NULL, 103, '16', 'paid', 3, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, '', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(72, '', 'Unknown product', 1, 0.00, 'delivered', 'woocommerce_pull', '2026-09-21 11:14:16', NULL, 13, NULL, 104, '15', 'paid', 3, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, '', NULL, 'not_started', NULL, NULL, NULL, NULL, NULL, NULL),
(73, 'Order #5', 'PrestaShop Order #5', 1, 20.90, 'pending', 'prestashop_pull', '2026-09-21 11:34:10', NULL, 15, NULL, 119, NULL, 'unpaid', 3, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, '5', NULL, NULL, NULL, NULL),
(74, 'Order #4', 'PrestaShop Order #4', 1, 14.90, 'pending', 'prestashop_pull', '2026-09-21 11:34:10', NULL, 15, NULL, 120, NULL, 'unpaid', 3, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, '4', NULL, NULL, NULL, NULL),
(75, 'Order #3', 'PrestaShop Order #3', 1, 14.90, 'cancelled', 'prestashop_pull', '2026-09-21 11:34:10', NULL, 15, NULL, 121, NULL, 'unpaid', 3, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, '3', NULL, NULL, NULL, NULL),
(76, 'Order #2', 'PrestaShop Order #2', 1, 169.90, 'pending', 'prestashop_pull', '2026-09-21 11:34:11', NULL, 15, NULL, 122, NULL, 'unpaid', 3, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, '2', NULL, NULL, NULL, NULL),
(77, 'Order #1', 'PrestaShop Order #1', 1, 61.80, 'cancelled', 'prestashop_pull', '2026-09-21 11:34:11', NULL, 15, NULL, 123, NULL, 'unpaid', 3, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, '1', NULL, NULL, NULL, NULL),
(78, 'Abraham Dalton', 'Royal London 41003-03', 1, 157.50, 'pending', 'oscommerce_pull', '2026-09-22 08:44:39', NULL, 18, NULL, 190, NULL, 'paid', 3, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, NULL, NULL, '4', NULL, NULL),
(79, 'Mandy Cross', 'CANON PIXMA TS5350 AllinOne Wireless Inkjet Printer', 1, 58.33, 'delivered', 'oscommerce_pull', '2026-09-22 08:44:39', NULL, 18, NULL, 205, NULL, 'paid', 3, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, NULL, NULL, '3', NULL, NULL),
(80, 'Li Chong', 'JACK 4 PIECE KING BEDROOM SUITE', 1, 1303.48, 'delivered', 'oscommerce_pull', '2026-09-22 08:44:39', NULL, 18, NULL, 215, NULL, 'paid', 3, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, 'not_started', NULL, NULL, NULL, '2', NULL, NULL),
(81, 'idrees', '2 Drawer Side Filing Office Cabinet', 10, 54.17, 'pending', 'manual', '2026-09-23 06:10:55', NULL, 1, NULL, 198, NULL, 'paid', 3, 'ORD-20260923081055-852', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Card', NULL, 'not_started', NULL, NULL, NULL, NULL, 'cs_test_a1D9NqTsgLUi4U9Q7YyFzixAKtIKa6rIMvDRldvYsFltTvfg2htaEA0870', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

DROP TABLE IF EXISTS `order_status_history`;
CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `actor` varchar(150) DEFAULT NULL,
  `source` varchar(50) NOT NULL DEFAULT 'user',
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `external_product_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `external_wc_product_id` varchar(100) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `image_url` varchar(500) DEFAULT NULL,
  `external_bc_product_id` varchar(100) DEFAULT NULL,
  `external_ps_product_id` varchar(100) DEFAULT NULL,
  `external_ocart_product_id` varchar(100) DEFAULT NULL,
  `external_osc_product_id` varchar(100) DEFAULT NULL,
  `external_wix_product_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `store_id`, `name`, `sku`, `price`, `external_product_id`, `created_at`, `external_wc_product_id`, `stock_quantity`, `low_stock_threshold`, `image_url`, `external_bc_product_id`, `external_ps_product_id`, `external_ocart_product_id`, `external_osc_product_id`, `external_wix_product_id`) VALUES
(1, 5, 'Wireless Mouse', 'SKU001', 1200.00, NULL, '2026-09-08 05:05:13', NULL, 200, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 1, 'Keyboard', 'SKU002', 3400.00, '8539863777466', '2026-09-08 05:05:13', NULL, 250, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 5, 'Mobile Cover', 'SKU003', 500.00, '8523934630074', '2026-09-08 05:05:13', NULL, 37, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 1, 'bluetooth headphone', NULL, 50.00, '8524045877434', '2026-09-08 10:25:20', NULL, 304, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(25, 1, 'Earbuds', 'SKU004', 600.00, NULL, '2026-09-08 11:12:55', NULL, 930, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(26, 5, 'test shirt', NULL, 3.00, NULL, '2026-09-08 11:14:37', NULL, 155, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(27, 5, 'Shoes', NULL, 1500.00, NULL, '2026-09-08 11:14:37', NULL, 300, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(28, 5, 't shirt', NULL, 50.00, NULL, '2026-09-08 11:14:37', NULL, 65, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(33, 2, 'laptop', '0019', 1000.00, '8538417725626', '2026-09-08 11:17:43', NULL, 1896, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(34, 3, 'Bluetooth Speaker', '', 149.00, '8527318581434', '2026-09-09 07:03:20', '15', 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(35, 1, 'Bluetooth Speaker', NULL, 149.00, '8526129070266', '2026-09-11 05:07:35', NULL, 50, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(36, 2, 'test-pants', NULL, 10.00, '8531210895546', '2026-09-11 05:07:35', NULL, 381, 5, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', NULL, NULL, NULL, NULL, NULL),
(38, 3, 'Shoes', NULL, 20.00, NULL, '2026-09-12 06:33:43', '13', 10, 5, 'http://localhost/wordpress/wp-content/uploads/2026/09/Screenshot-2026-09-12-104548.png', NULL, NULL, NULL, NULL, NULL),
(39, 4, 'Test Wireless Mouse', 'MOUSE-001', 25.00, '1', '2026-09-17 05:42:56', NULL, 48, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(40, 4, 'Test Keyboard', 'KEY-001', 45.00, '2', '2026-09-17 05:42:56', NULL, 30, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(41, 7, 'bluetooth headphone', NULL, 50.00, '8524045877434', '2026-09-21 03:52:10', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(42, 7, 'Bluetooth Speaker', NULL, 149.00, '8526129070266', '2026-09-21 03:52:10', NULL, 10, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(43, 7, 'Bluetooth Speaker', NULL, 149.00, '8527318581434', '2026-09-21 03:52:10', NULL, 10, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(44, 7, 'Keyboard', 'SKU002', 3400.00, '8539863777466', '2026-09-21 03:52:11', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(45, 7, 'laptop', '0019', 1000.00, '8524081660090', '2026-09-21 03:52:11', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(46, 7, 'laptop', '0019', 1000.00, '8538417725626', '2026-09-21 03:52:11', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(47, 7, 'Mobile Cover', 'SKU003', 500.00, '8523934630074', '2026-09-21 03:52:11', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(48, 7, 'test-pants', NULL, 10.00, '8531210895546', '2026-09-21 03:52:11', NULL, 220, 5, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', NULL, NULL, NULL, NULL, NULL),
(49, 7, 'Router', NULL, 10000.00, NULL, '2026-09-21 04:02:33', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(50, 7, 'test shirt', NULL, 3.00, NULL, '2026-09-21 04:02:33', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(51, 8, 'Shoes', NULL, 0.00, NULL, '2026-09-21 04:25:27', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(52, 8, 'Unknown product', NULL, 0.00, NULL, '2026-09-21 04:25:27', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(53, 8, 'Shoes', '', 0.00, NULL, '2026-09-21 04:25:37', '13', 0, 5, 'http://localhost/wordpress/wp-content/uploads/2026/09/Screenshot-2026-09-12-104548.png', NULL, NULL, NULL, NULL, NULL),
(54, 9, '[Sample] Fog Linen Chambray Towel - Beige Stripe', 'SLCTBS', 49.00, NULL, '2026-09-21 05:45:45', NULL, 0, 5, NULL, '77', NULL, NULL, NULL, NULL),
(55, 9, '[Sample] Orbit Terrarium - Large', 'OTL', 109.00, NULL, '2026-09-21 05:45:45', NULL, 0, 5, NULL, '80', NULL, NULL, NULL, NULL),
(56, 9, '[Sample] Orbit Terrarium - Small', 'OTS', 89.00, NULL, '2026-09-21 05:45:45', NULL, 0, 5, NULL, '81', NULL, NULL, NULL, NULL),
(57, 9, '[Sample] Able Brewing System', 'ABS', 225.00, NULL, '2026-09-21 05:45:45', NULL, 0, 5, NULL, '86', NULL, NULL, NULL, NULL),
(58, 9, '[Sample] Chemex Coffeemaker 3 Cup', 'CC3C', 49.50, NULL, '2026-09-21 05:45:45', NULL, 0, 5, NULL, '88', NULL, NULL, NULL, NULL),
(59, 9, '[Sample] 1 L Le Parfait Jar', 'SLLPJ', 9.95, NULL, '2026-09-21 05:45:45', NULL, 0, 5, NULL, '93', NULL, NULL, NULL, NULL),
(60, 9, '[Sample] Oak Cheese Grater', 'OCG', 34.95, NULL, '2026-09-21 05:45:45', NULL, 0, 5, NULL, '94', NULL, NULL, NULL, NULL),
(61, 9, '[Sample] Tiered Wire Basket', 'TWB', 119.95, NULL, '2026-09-21 05:45:45', NULL, 0, 5, NULL, '97', NULL, NULL, NULL, NULL),
(62, 9, '[Sample] Laundry Detergent', 'CGLD', 29.95, NULL, '2026-09-21 05:45:45', NULL, 0, 5, NULL, '98', NULL, NULL, NULL, NULL),
(63, 9, '[Sample] Canvas Laundry Cart', 'CLC', 249.00, NULL, '2026-09-21 05:45:45', NULL, 0, 5, NULL, '103', NULL, NULL, NULL, NULL),
(64, 9, '[Sample] Utility Caddy', 'OFSUC', 45.95, NULL, '2026-09-21 05:45:45', NULL, 0, 5, NULL, '104', NULL, NULL, NULL, NULL),
(65, 9, '[Sample] Dustpan & Brush', 'DPB', 34.95, NULL, '2026-09-21 05:45:45', NULL, 0, 5, NULL, '107', NULL, NULL, NULL, NULL),
(66, 9, '[Sample] Smith Journal 13', 'SM13', 25.00, NULL, '2026-09-21 05:45:45', NULL, 0, 5, NULL, '111', NULL, NULL, NULL, NULL),
(67, 10, 'PrestaShop Order #5', NULL, 20.90, NULL, '2026-09-21 10:01:26', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(68, 10, 'PrestaShop Order #4', NULL, 14.90, NULL, '2026-09-21 10:01:27', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(69, 10, 'PrestaShop Order #3', NULL, 14.90, NULL, '2026-09-21 10:01:27', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(70, 10, 'PrestaShop Order #2', NULL, 169.90, NULL, '2026-09-21 10:01:27', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(71, 10, 'PrestaShop Order #1', NULL, 61.80, NULL, '2026-09-21 10:01:27', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(72, 10, 'Hummingbird printed t-shirt', 'demo_1', 23.90, NULL, '2026-09-21 10:01:52', NULL, 2400, 5, NULL, NULL, '1', NULL, NULL, NULL),
(73, 10, 'Hummingbird printed sweater', 'demo_3', 35.90, NULL, '2026-09-21 10:01:52', NULL, 2100, 5, NULL, NULL, '2', NULL, NULL, NULL),
(74, 10, 'The best is yet to come\' Framed poster', 'demo_6', 29.00, NULL, '2026-09-21 10:01:53', NULL, 1500, 5, NULL, NULL, '3', NULL, NULL, NULL),
(75, 10, 'The adventure begins Framed poster', 'demo_5', 29.00, NULL, '2026-09-21 10:01:53', NULL, 1500, 5, NULL, NULL, '4', NULL, NULL, NULL),
(76, 10, 'Today is a good day Framed poster', 'demo_7', 29.00, NULL, '2026-09-21 10:01:53', NULL, 900, 5, NULL, NULL, '5', NULL, NULL, NULL),
(77, 10, 'Mug The best is yet to come', 'demo_11', 11.90, NULL, '2026-09-21 10:01:54', NULL, 300, 5, NULL, NULL, '6', NULL, NULL, NULL),
(78, 10, 'Mug The adventure begins', 'demo_12', 11.90, NULL, '2026-09-21 10:01:54', NULL, 300, 5, NULL, NULL, '7', NULL, NULL, NULL),
(79, 10, 'Mug Today is a good day', 'demo_13', 11.90, NULL, '2026-09-21 10:01:54', NULL, 300, 5, NULL, NULL, '8', NULL, NULL, NULL),
(80, 10, 'Mountain fox cushion', 'demo_15', 18.90, NULL, '2026-09-21 10:01:55', NULL, 600, 5, NULL, NULL, '9', NULL, NULL, NULL),
(81, 10, 'Brown bear cushion', 'demo_16', 18.90, NULL, '2026-09-21 10:01:55', NULL, 600, 5, NULL, NULL, '10', NULL, NULL, NULL),
(82, 10, 'Hummingbird cushion', 'demo_17', 18.90, NULL, '2026-09-21 10:01:55', NULL, 600, 5, NULL, NULL, '11', NULL, NULL, NULL),
(83, 10, 'Mountain fox - Vector graphics', 'demo_18', 9.00, NULL, '2026-09-21 10:01:56', NULL, 300, 5, NULL, NULL, '12', NULL, NULL, NULL),
(84, 10, 'Brown bear - Vector graphics', 'demo_19', 9.00, NULL, '2026-09-21 10:01:56', NULL, 300, 5, NULL, NULL, '13', NULL, NULL, NULL),
(85, 10, 'Hummingbird - Vector graphics', 'demo_20', 9.00, NULL, '2026-09-21 10:01:57', NULL, 300, 5, NULL, NULL, '14', NULL, NULL, NULL),
(86, 10, 'Pack Mug + Framed poster', 'demo_21', 35.00, NULL, '2026-09-21 10:01:57', NULL, 100, 5, NULL, NULL, '15', NULL, NULL, NULL),
(87, 10, 'Mountain fox notebook', 'demo_8', 12.90, NULL, '2026-09-21 10:01:57', NULL, 1200, 5, NULL, NULL, '16', NULL, NULL, NULL),
(88, 10, 'Brown bear notebook', 'demo_9', 12.90, NULL, '2026-09-21 10:01:58', NULL, 1200, 5, NULL, NULL, '17', NULL, NULL, NULL),
(89, 10, 'Hummingbird notebook', 'demo_10', 12.90, NULL, '2026-09-21 10:01:58', NULL, 1200, 5, NULL, NULL, '18', NULL, NULL, NULL),
(90, 10, 'Customizable mug', 'demo_14', 13.90, NULL, '2026-09-21 10:01:58', NULL, 300, 5, NULL, NULL, '19', NULL, NULL, NULL),
(91, 12, 'Bluetooth Speaker', NULL, 149.00, NULL, '2026-09-21 11:11:25', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(92, 12, 'Router', NULL, 10000.00, NULL, '2026-09-21 11:11:25', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(93, 12, 'Mobile Cover', NULL, 500.00, NULL, '2026-09-21 11:11:25', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(94, 12, 'test shirt', NULL, 3.00, NULL, '2026-09-21 11:11:25', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(95, 12, 'bluetooth headphone', NULL, 50.00, '8524045877434', '2026-09-21 11:11:39', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(96, 12, 'Bluetooth Speaker', NULL, 149.00, '8526129070266', '2026-09-21 11:11:39', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(97, 12, 'Bluetooth Speaker', NULL, 149.00, '8527318581434', '2026-09-21 11:11:39', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(98, 12, 'Keyboard', 'SKU002', 3400.00, '8539863777466', '2026-09-21 11:11:39', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(99, 12, 'laptop', '0019', 1000.00, '8524081660090', '2026-09-21 11:11:39', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(100, 12, 'laptop', '0019', 1000.00, '8538417725626', '2026-09-21 11:11:39', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(101, 12, 'Mobile Cover', 'SKU003', 500.00, '8523934630074', '2026-09-21 11:11:39', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(102, 12, 'test-pants', NULL, 10.00, '8531210895546', '2026-09-21 11:11:39', NULL, 220, 5, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', NULL, NULL, NULL, NULL, NULL),
(103, 13, 'Shoes', NULL, 0.00, NULL, '2026-09-21 11:14:16', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(104, 13, 'Unknown product', NULL, 0.00, NULL, '2026-09-21 11:14:16', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(105, 13, 'Shoes', '', 0.00, NULL, '2026-09-21 11:14:29', '13', 0, 5, 'http://localhost/wordpress/wp-content/uploads/2026/09/Screenshot-2026-09-12-104548.png', NULL, NULL, NULL, NULL, NULL),
(106, 14, '[Sample] Fog Linen Chambray Towel - Beige Stripe', 'SLCTBS', 49.00, NULL, '2026-09-21 11:28:17', NULL, 0, 5, NULL, '77', NULL, NULL, NULL, NULL),
(107, 14, '[Sample] Orbit Terrarium - Large', 'OTL', 109.00, NULL, '2026-09-21 11:28:17', NULL, 0, 5, NULL, '80', NULL, NULL, NULL, NULL),
(108, 14, '[Sample] Orbit Terrarium - Small', 'OTS', 89.00, NULL, '2026-09-21 11:28:17', NULL, 0, 5, NULL, '81', NULL, NULL, NULL, NULL),
(109, 14, '[Sample] Able Brewing System', 'ABS', 225.00, NULL, '2026-09-21 11:28:17', NULL, 0, 5, NULL, '86', NULL, NULL, NULL, NULL),
(110, 14, '[Sample] Chemex Coffeemaker 3 Cup', 'CC3C', 49.50, NULL, '2026-09-21 11:28:17', NULL, 0, 5, NULL, '88', NULL, NULL, NULL, NULL),
(111, 14, '[Sample] 1 L Le Parfait Jar', 'SLLPJ', 9.95, NULL, '2026-09-21 11:28:17', NULL, 0, 5, NULL, '93', NULL, NULL, NULL, NULL),
(112, 14, '[Sample] Oak Cheese Grater', 'OCG', 34.95, NULL, '2026-09-21 11:28:17', NULL, 0, 5, NULL, '94', NULL, NULL, NULL, NULL),
(113, 14, '[Sample] Tiered Wire Basket', 'TWB', 119.95, NULL, '2026-09-21 11:28:17', NULL, 0, 5, NULL, '97', NULL, NULL, NULL, NULL),
(114, 14, '[Sample] Laundry Detergent', 'CGLD', 29.95, NULL, '2026-09-21 11:28:17', NULL, 0, 5, NULL, '98', NULL, NULL, NULL, NULL),
(115, 14, '[Sample] Canvas Laundry Cart', 'CLC', 249.00, NULL, '2026-09-21 11:28:17', NULL, 0, 5, NULL, '103', NULL, NULL, NULL, NULL),
(116, 14, '[Sample] Utility Caddy', 'OFSUC', 45.95, NULL, '2026-09-21 11:28:17', NULL, 0, 5, NULL, '104', NULL, NULL, NULL, NULL),
(117, 14, '[Sample] Dustpan & Brush', 'DPB', 34.95, NULL, '2026-09-21 11:28:17', NULL, 0, 5, NULL, '107', NULL, NULL, NULL, NULL),
(118, 14, '[Sample] Smith Journal 13', 'SM13', 25.00, NULL, '2026-09-21 11:28:17', NULL, 0, 5, NULL, '111', NULL, NULL, NULL, NULL),
(119, 15, 'PrestaShop Order #5', NULL, 20.90, NULL, '2026-09-21 11:34:10', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(120, 15, 'PrestaShop Order #4', NULL, 14.90, NULL, '2026-09-21 11:34:10', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(121, 15, 'PrestaShop Order #3', NULL, 14.90, NULL, '2026-09-21 11:34:10', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(122, 15, 'PrestaShop Order #2', NULL, 169.90, NULL, '2026-09-21 11:34:11', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(123, 15, 'PrestaShop Order #1', NULL, 61.80, NULL, '2026-09-21 11:34:11', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(124, 15, 'Hummingbird printed t-shirt', 'demo_1', 23.90, NULL, '2026-09-21 11:34:19', NULL, 2400, 5, NULL, NULL, '1', NULL, NULL, NULL),
(125, 15, 'Hummingbird printed sweater', 'demo_3', 35.90, NULL, '2026-09-21 11:34:19', NULL, 2100, 5, NULL, NULL, '2', NULL, NULL, NULL),
(126, 15, 'The best is yet to come\' Framed poster', 'demo_6', 29.00, NULL, '2026-09-21 11:34:20', NULL, 1500, 5, NULL, NULL, '3', NULL, NULL, NULL),
(127, 15, 'The adventure begins Framed poster', 'demo_5', 29.00, NULL, '2026-09-21 11:34:20', NULL, 1500, 5, NULL, NULL, '4', NULL, NULL, NULL),
(128, 15, 'Today is a good day Framed poster', 'demo_7', 29.00, NULL, '2026-09-21 11:34:20', NULL, 900, 5, NULL, NULL, '5', NULL, NULL, NULL),
(129, 15, 'Mug The best is yet to come', 'demo_11', 11.90, NULL, '2026-09-21 11:34:21', NULL, 300, 5, NULL, NULL, '6', NULL, NULL, NULL),
(130, 15, 'Mug The adventure begins', 'demo_12', 11.90, NULL, '2026-09-21 11:34:21', NULL, 300, 5, NULL, NULL, '7', NULL, NULL, NULL),
(131, 15, 'Mug Today is a good day', 'demo_13', 11.90, NULL, '2026-09-21 11:34:21', NULL, 300, 5, NULL, NULL, '8', NULL, NULL, NULL),
(132, 15, 'Mountain fox cushion', 'demo_15', 18.90, NULL, '2026-09-21 11:34:22', NULL, 600, 5, NULL, NULL, '9', NULL, NULL, NULL),
(133, 15, 'Brown bear cushion', 'demo_16', 18.90, NULL, '2026-09-21 11:34:22', NULL, 600, 5, NULL, NULL, '10', NULL, NULL, NULL),
(134, 15, 'Hummingbird cushion', 'demo_17', 18.90, NULL, '2026-09-21 11:34:22', NULL, 600, 5, NULL, NULL, '11', NULL, NULL, NULL),
(135, 15, 'Mountain fox - Vector graphics', 'demo_18', 9.00, NULL, '2026-09-21 11:34:23', NULL, 300, 5, NULL, NULL, '12', NULL, NULL, NULL),
(136, 15, 'Brown bear - Vector graphics', 'demo_19', 9.00, NULL, '2026-09-21 11:34:23', NULL, 300, 5, NULL, NULL, '13', NULL, NULL, NULL),
(137, 15, 'Hummingbird - Vector graphics', 'demo_20', 9.00, NULL, '2026-09-21 11:34:23', NULL, 300, 5, NULL, NULL, '14', NULL, NULL, NULL),
(138, 15, 'Pack Mug + Framed poster', 'demo_21', 35.00, NULL, '2026-09-21 11:34:24', NULL, 100, 5, NULL, NULL, '15', NULL, NULL, NULL),
(139, 15, 'Mountain fox notebook', 'demo_8', 12.90, NULL, '2026-09-21 11:34:24', NULL, 1200, 5, NULL, NULL, '16', NULL, NULL, NULL),
(140, 15, 'Brown bear notebook', 'demo_9', 12.90, NULL, '2026-09-21 11:34:24', NULL, 1200, 5, NULL, NULL, '17', NULL, NULL, NULL),
(141, 15, 'Hummingbird notebook', 'demo_10', 12.90, NULL, '2026-09-21 11:34:25', NULL, 1200, 5, NULL, NULL, '18', NULL, NULL, NULL),
(142, 15, 'Customizable mug', 'demo_14', 13.90, NULL, '2026-09-21 11:34:25', NULL, 300, 5, NULL, NULL, '19', NULL, NULL, NULL),
(143, 17, 'Product 8', 'Product 8', 100.00, NULL, '2026-09-22 06:24:13', NULL, 1000, 5, NULL, NULL, NULL, '35', NULL, NULL),
(144, 17, 'iPod Classic', 'product 20', 100.00, NULL, '2026-09-22 06:24:13', NULL, 995, 5, NULL, NULL, NULL, '48', NULL, NULL),
(145, 17, 'iPhone', 'product 11', 101.00, NULL, '2026-09-22 06:24:13', NULL, 970, 5, NULL, NULL, NULL, '40', NULL, NULL),
(146, 17, 'HTC Touch HD', 'Product 1', 100.00, NULL, '2026-09-22 06:24:13', NULL, 939, 5, NULL, NULL, NULL, '28', NULL, NULL),
(147, 17, 'MacBook Air', 'Product 17', 1000.00, NULL, '2026-09-22 06:24:13', NULL, 1000, 5, NULL, NULL, NULL, '44', NULL, NULL),
(148, 17, 'MacBook Pro', 'Product 18', 2000.00, NULL, '2026-09-22 06:24:13', NULL, 998, 5, NULL, NULL, NULL, '45', NULL, NULL),
(149, 17, 'Palm Treo Pro', 'Product 2', 279.99, NULL, '2026-09-22 06:24:13', NULL, 999, 5, NULL, NULL, NULL, '29', NULL, NULL),
(150, 17, 'iPod Nano', 'Product 9', 100.00, NULL, '2026-09-22 06:24:13', NULL, 994, 5, NULL, NULL, NULL, '36', NULL, NULL),
(151, 17, 'Sony VAIO', 'Product 19', 1000.00, NULL, '2026-09-22 06:24:13', NULL, 1000, 5, NULL, NULL, NULL, '46', NULL, NULL),
(152, 17, 'HP LP3065', 'Product 21', 100.00, NULL, '2026-09-22 06:24:13', NULL, 1000, 5, NULL, NULL, NULL, '47', NULL, NULL),
(153, 17, 'iPod Touch', 'Product 5', 100.00, NULL, '2026-09-22 06:24:13', NULL, 999, 5, NULL, NULL, NULL, '32', NULL, NULL),
(154, 17, 'iMac', 'Product 14', 100.00, NULL, '2026-09-22 06:24:13', NULL, 977, 5, NULL, NULL, NULL, '41', NULL, NULL),
(155, 17, 'Samsung SyncMaster 941BW', 'Product 6', 200.00, NULL, '2026-09-22 06:24:13', NULL, 1000, 5, NULL, NULL, NULL, '33', NULL, NULL),
(156, 17, 'iPod Shuffle', 'Product 7', 100.00, NULL, '2026-09-22 06:24:13', NULL, 1000, 5, NULL, NULL, NULL, '34', NULL, NULL),
(157, 17, 'MacBook', 'Product 16', 500.00, NULL, '2026-09-22 06:24:13', NULL, 929, 5, NULL, NULL, NULL, '43', NULL, NULL),
(158, 17, 'Nikon D300', 'Product 4', 80.00, NULL, '2026-09-22 06:24:13', NULL, 1000, 5, NULL, NULL, NULL, '31', NULL, NULL),
(159, 17, 'Samsung Galaxy Tab 10.1', 'SAM1', 199.99, NULL, '2026-09-22 06:24:13', NULL, 0, 5, NULL, NULL, NULL, '49', NULL, NULL),
(160, 17, 'Apple Cinema 30&quot;', 'Product 15', 100.00, NULL, '2026-09-22 06:24:13', NULL, 990, 5, NULL, NULL, NULL, '42', NULL, NULL),
(161, 17, 'Canon EOS 5D', 'Product 3', 100.00, NULL, '2026-09-22 06:24:13', NULL, 7, 5, NULL, NULL, NULL, '30', NULL, NULL),
(162, 18, 'DKNY Women\'s NY2146 Soho Colored Chronograph Bracelet Watch', 'NY2146', 111.90, NULL, '2026-09-22 08:44:33', NULL, 99, 5, NULL, NULL, NULL, NULL, '1', NULL),
(163, 18, 'Puma Women\'s Motor White Polyurethane Quartz Watch with Black Dial', 'PU102742005', 87.54, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '2', NULL),
(164, 18, 'Puma Women\'s Motor Pink Polyurethane Analog Quartz Watch', 'PU102812003', 180.00, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '3', NULL),
(165, 18, 'Guess Men\'s U25004L1 Silver Stainless-Steel Quartz Watch with Silver Dial', 'U25004L1', 37.45, NULL, '2026-09-22 08:44:33', NULL, 98, 5, NULL, NULL, NULL, NULL, '4', NULL),
(166, 18, 'Tommy Hilfiger Men\'s Skywinder Stainless Steel and Leather Strap Watch', '11807012', 55.83, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '5', NULL),
(167, 18, 'Tommy Hilfiger Men\'s 1790708 Black Silicone Sport Watch', '1790708', 150.00, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '6', NULL),
(168, 18, 'Tommy Hilfiger Men\'s Black Silicone Quartz Watch', '1790853', 28.94, NULL, '2026-09-22 08:44:33', NULL, 98, 5, NULL, NULL, NULL, NULL, '7', NULL),
(169, 18, 'Gucci Men\'s YA126238 \'Timeless\' Grey Dial Stainless Steel Chrono', 'YA126238', 61.28, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '8', NULL),
(170, 18, 'DKNY Women\'s NY8183 Gold Stainless Steel Quartz Watch with White zusä', 'NY8183', 49.36, NULL, '2026-09-22 08:44:33', NULL, 98, 5, NULL, NULL, NULL, NULL, '9', NULL),
(171, 18, 'Interlocking G Small Brown Dial Ladies Watch', 'YA133504', 109.84, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '10', NULL),
(172, 18, 'Gucci Women\'s YA129444 Gucci U Play Collection Stainless Steel Watch with Striped Nylon Band', 'YA129444', 217.49, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '11', NULL),
(173, 18, 'Gucci Men\'s YA133202 \'Grammy XL Interlocking\' Yellow Dial Black Leather Strap', 'YA133202', 47.66, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '12', NULL),
(174, 18, 'Casio G-SHOCK G100-1BV Wrist Watch PRICE- 1200', 'G100-1BV', 44.20, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '13', NULL),
(175, 18, 'Casio Baby-G BG169G-7B Face Protector Ion-Plated Metal White Rose Gold Watch Digital1', 'BG169G-7B', 180.00, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '14', NULL),
(176, 18, 'Casio Men\'s Core MTP1192E-7A Brown Leather Quartz Watch with White Dial', 'MTP1192E-7A', 40.04, NULL, '2026-09-22 08:44:33', NULL, 96, 5, NULL, NULL, NULL, NULL, '15', NULL),
(177, 18, 'Casio General Men\'s Watches Edifice Digital-Analog Combination', 'EFA121D-7AV', 38.30, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '16', NULL),
(178, 18, 'Casio Men\'s W753-2AV Blue Resin Quartz Watch with Digital Dial', 'W753-2AV', 56.17, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '17', NULL),
(179, 18, 'Nemesis Women\'s Classic Yellow Plant Art Leather Cuff Band Watch', 'THR827N', 51.67, NULL, '2026-09-22 08:44:33', NULL, 99, 5, NULL, NULL, NULL, NULL, '18', NULL),
(180, 18, 'Nemesis Men\'s Brown Wide Leather Cuff Band Analog Brown Dial Watch', 'BBB516S', 42.97, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '19', NULL),
(181, 18, 'Nemesis Men\'s Wide Sunrise Black Leather Cuff Watch', 'TWXB078K', 150.00, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '20', NULL),
(182, 18, 'Cartier Men\'s Santos 18k Gold and Steel Automatic Watch', 'W20072X7', 11.51, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '21', NULL),
(183, 18, 'Edox Men\'s \'Les Vauberts Automatic\' Yellow Goldtone Stainless Steel Mechanical A', '85010-37J-AID', 211.67, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '22', NULL),
(184, 18, 'Edox Men\'s \'WRC\' Stainless Steel Multifunction Chronograph Watch', '01112-3-BUIN', 264.17, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '23', NULL),
(185, 18, 'Concord Men\'s \'Saratoga\' Black Stainless Steel Swiss Quartz Watch', '0311818', 145.00, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '24', NULL),
(186, 18, 'Citizen Eco-Drive Silver Tone Men', 'AO9030-05E', 189.17, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '25', NULL),
(187, 18, 'Citizen Men\'s \'Eco-Drive\' Primo Black/ Blue Chronograph Watch', 'CA0467-03E', 35.00, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '26', NULL),
(188, 18, 'Citizen Men\'s Eco-Drive AT2215-07E Black Leather Eco-Drive Watch with Black Dial', 'AT2215-07E', 39.15, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '27', NULL),
(189, 18, 'Citizen Men\'s BJ8050-08E Eco-Drive Professional Diver Black Sport Watch', 'BJ8050-08E', 50.00, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '28', NULL),
(190, 18, 'Royal London 41003-03', 'Royal-41003-03', 157.50, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '29', NULL),
(191, 18, 'Senator Task Mesh Back Chair, Fully Adjustable, Red & Black', '79189', 82.50, NULL, '2026-09-22 08:44:33', NULL, 100, 5, NULL, NULL, NULL, NULL, '30', NULL),
(192, 18, 'WorkWell Meeting Chair Cantilever MWT15', 'MWT15', 74.16, NULL, '2026-09-22 08:44:34', NULL, 100, 5, NULL, NULL, NULL, NULL, '31', NULL),
(193, 18, 'WorkWell Meeting Chair 5-Star Castor Base MWT13', 'MWT13', 107.49, NULL, '2026-09-22 08:44:34', NULL, 100, 5, NULL, NULL, NULL, NULL, '32', NULL),
(194, 18, 'Executive Office Chair, High Back, Armrests, Real Leather, Black', '39849', 65.83, NULL, '2026-09-22 08:44:34', NULL, 100, 5, NULL, NULL, NULL, NULL, '33', NULL),
(195, 18, 'Fellowes White Lotus DX Sit-Stand Workstation', '8081101', 262.50, NULL, '2026-09-22 08:44:34', NULL, 99, 5, NULL, NULL, NULL, NULL, '34', NULL),
(196, 18, 'Fellowes Black Lotus DX Sit-Stand Workstation', '8081001', 274.10, NULL, '2026-09-22 08:44:34', NULL, 100, 5, NULL, NULL, NULL, NULL, '35', NULL),
(197, 18, 'Jemini 1600mm Grey Oak/Silver Cantilever Left Hand Radial Desk', 'KF807537', 180.20, NULL, '2026-09-22 08:44:34', NULL, 100, 5, NULL, NULL, NULL, NULL, '36', NULL),
(198, 18, '2 Drawer Side Filing Office Cabinet', 'drawerside', 54.17, NULL, '2026-09-22 08:44:34', NULL, 90, 5, NULL, NULL, NULL, NULL, '37', NULL),
(199, 18, 'Epson WorkForce Pro WF-3820DWF All-In-One Wireless Printer', '89502105', 99.99, NULL, '2026-09-22 08:44:34', NULL, 100, 5, NULL, NULL, NULL, NULL, '38', NULL),
(200, 18, 'Epson Expression Premium XP-6100 Wi-Fi All-In-One Printer', '89502103', 74.99, NULL, '2026-09-22 08:44:34', NULL, 100, 5, NULL, NULL, NULL, NULL, '39', NULL),
(201, 18, 'Epson EcoTank ET-M2120', 'ECOTANK M2120', 247.41, NULL, '2026-09-22 08:44:34', NULL, 100, 5, NULL, NULL, NULL, NULL, '40', NULL),
(202, 18, 'EPSON M15140 WI-FI (C11CJ41404)', 'ECOTANKM15140', 832.48, NULL, '2026-09-22 08:44:34', NULL, 100, 5, NULL, NULL, NULL, NULL, '41', NULL),
(203, 18, 'Canon - PIXMA Wireless Inkjet Printer - G3260', 'G3260', 166.66, NULL, '2026-09-22 08:44:34', NULL, 100, 5, NULL, NULL, NULL, NULL, '42', NULL),
(204, 18, 'Canon imageFORMULA DR-6010C Sheetfed Scanner - 24 bit Color - 8 bit Grayscale - USB, SCSI', 'DR-6010C', 2075.83, NULL, '2026-09-22 08:44:34', NULL, 100, 5, NULL, NULL, NULL, NULL, '43', NULL),
(205, 18, 'CANON PIXMA TS5350 AllinOne Wireless Inkjet Printer', '629373', 58.33, NULL, '2026-09-22 08:44:34', NULL, 99, 5, NULL, NULL, NULL, NULL, '44', NULL),
(206, 18, 'CANON PIXMA TS7451 AllinOne Wireless Inkjet Printer White', '780020', 66.66, NULL, '2026-09-22 08:44:34', NULL, 99, 5, NULL, NULL, NULL, NULL, '45', NULL),
(207, 18, 'BROTHER MFCL3750CDW AllinOne Laser Printer with Fax', '789669', 291.66, NULL, '2026-09-22 08:44:34', NULL, 100, 5, NULL, NULL, NULL, NULL, '46', NULL),
(208, 18, 'BROTHER HLL2310D Monochrome Laser Printer', '236124', 83.33, NULL, '2026-09-22 08:44:34', NULL, 100, 5, NULL, NULL, NULL, NULL, '47', NULL),
(209, 18, 'BROTHER DCP1612W Monochrome AllinOne Wireless Laser Printer', '131109', 116.66, NULL, '2026-09-22 08:44:34', NULL, 98, 5, NULL, NULL, NULL, NULL, '48', NULL),
(210, 18, 'BROTHER MFCL8690CDW AllinOne Wireless Laser Colour Printer with Fax', '245042', 324.17, NULL, '2026-09-22 08:44:34', NULL, 100, 5, NULL, NULL, NULL, NULL, '49', NULL),
(211, 18, 'COZY SLEEP QUEEN MATTRESS', '00GM21-01Y014-Q', 346.96, NULL, '2026-09-22 08:44:34', NULL, 2356, 5, NULL, NULL, NULL, NULL, '50', NULL),
(212, 18, 'COZY SLEEP DOUBLE MATTRESS', '00GM21-01Y014-D', 303.48, NULL, '2026-09-22 08:44:34', NULL, 2000, 5, NULL, NULL, NULL, NULL, '51', NULL),
(213, 18, 'Country 6PCS Package 1Xqb+2Xbs+1Xtb+1Xdt+1Xdm', 'PCOU04-SMKGUM', 2999.00, NULL, '2026-09-22 08:44:34', NULL, 1050, 5, NULL, NULL, NULL, NULL, '52', NULL),
(214, 18, 'JACK 5 PIECE KING BEDROOM SUITE', 'PJAC09-050093', 1390.43, NULL, '2026-09-22 08:44:34', NULL, 2038, 5, NULL, NULL, NULL, NULL, '53', NULL),
(215, 18, 'JACK 4 PIECE KING BEDROOM SUITE', 'PJAC08-0400P3', 1303.48, NULL, '2026-09-22 08:44:34', NULL, 2038, 5, NULL, NULL, NULL, NULL, '54', NULL),
(216, 18, 'HAMPTONS 5 PIECE QUEEN BEDROOM SUITE', 'PHAM02-05A201', 2346.96, NULL, '2026-09-22 08:44:34', NULL, 2129, 5, NULL, NULL, NULL, NULL, '55', NULL),
(217, 18, 'BROLLY QUILTED FITTED MATTRESS PROTECTOR, KING', '00MPKQ-010000-K', 78.22, NULL, '2026-09-22 08:44:34', NULL, 1019, 5, NULL, NULL, NULL, NULL, '56', NULL),
(218, 18, 'BROLLY QUILTED FITTED MATTRESS PROTECTOR, QUEEN', '00MPQQ-010000-Q', 69.52, NULL, '2026-09-22 08:44:34', NULL, 955, 5, NULL, NULL, NULL, NULL, '57', NULL),
(219, 18, 'FURTEX ', '22585C', 49.00, NULL, '2026-09-22 08:44:34', NULL, 22464, 5, NULL, NULL, NULL, NULL, '58', NULL),
(220, 18, 'FURTEX ', '22898C', 39.00, NULL, '2026-09-22 08:44:34', NULL, 1096, 5, NULL, NULL, NULL, NULL, '59', NULL),
(221, 18, 'FURTEX ', '23077T', 49.00, NULL, '2026-09-22 08:44:34', NULL, 1100, 5, NULL, NULL, NULL, NULL, '60', NULL),
(222, 18, 'FURTEX ', '23078T', 49.00, NULL, '2026-09-22 08:44:34', NULL, 2200, 5, NULL, NULL, NULL, NULL, '61', NULL),
(223, 18, 'OLIVIA 3 DRAWER DRESSING TABLE', 'OLIVIA-TB308DE-DT', 799.00, NULL, '2026-09-22 08:44:34', NULL, 2000, 5, NULL, NULL, NULL, NULL, '62', NULL),
(224, 18, 'CLAYTON RECLINER LOUNGE SUITE', 'CLAYTON32-R8308-BROWN', 1564.35, NULL, '2026-09-22 08:44:34', NULL, 2000, 5, NULL, NULL, NULL, NULL, '63', NULL),
(225, 18, 'CLAYTON RECLINER LOUNGE SUITE', 'CLAYTON321-R8308-BROWN', 1912.17, NULL, '2026-09-22 08:44:34', NULL, 998, 5, NULL, NULL, NULL, NULL, '64', NULL),
(226, 18, 'VERONICA 4 PIECE QUEEN BEDROOM SUITE', 'VERONICAQUEEN4-NEW', 1899.00, NULL, '2026-09-22 08:44:34', NULL, 2000, 5, NULL, NULL, NULL, NULL, '65', NULL),
(227, 18, 'MONA 2 SEATER SOFA', 'MONA-1237-RED', 546.96, NULL, '2026-09-22 08:44:34', NULL, 1000, 5, NULL, NULL, NULL, NULL, '66', NULL),
(228, 18, 'MICHAEL 7 PIECE 150 DINING SUITE', 'MICHAEL-150BLK-PACKAGE', 699.00, NULL, '2026-09-22 08:44:34', NULL, 2000, 5, NULL, NULL, NULL, NULL, '67', NULL),
(229, 18, 'CASPER 5 PIECE 120 DINING SUITE', 'CASPERPACKAGE', 999.00, NULL, '2026-09-22 08:44:34', NULL, 997, 5, NULL, NULL, NULL, NULL, '68', NULL),
(230, 18, 'JONAS 7 PIECE OUTDOOR DINING SET', 'JONAS180PACKAGE', 868.70, NULL, '2026-09-22 08:44:34', NULL, 2000, 5, NULL, NULL, NULL, NULL, '69', NULL),
(231, 18, 'JONAS 5 PIECE OUTDOOR BAR SETTING', 'JONASBARPACKAGE', 520.87, NULL, '2026-09-22 08:44:34', NULL, 996, 5, NULL, NULL, NULL, NULL, '70', NULL),
(232, 18, 'JONAS JACK AND JILL OUTDOOR SETTING', 'HUC25806', 260.00, NULL, '2026-09-22 08:44:34', NULL, 2000, 5, NULL, NULL, NULL, NULL, '71', NULL),
(233, 18, 'JONAS 4 PIECE OUTDOOR LOUNGE SETTING', 'JONASLOUNGE', 868.70, NULL, '2026-09-22 08:44:34', NULL, 2000, 5, NULL, NULL, NULL, NULL, '72', NULL),
(234, 18, 'ERIN TRUNDLE BED WITH POCKET SPRING MATTRESSES', '00TB03-NEWERIN-KS', 799.00, NULL, '2026-09-22 08:44:34', NULL, 2094, 5, NULL, NULL, NULL, NULL, '73', NULL),
(235, 18, 'BERRY 3 SEATER WITH CHAISE', '199523-BERBEI-3S/C', 694.78, NULL, '2026-09-22 08:44:34', NULL, 999, 5, NULL, NULL, NULL, NULL, '74', NULL),
(236, 18, 'ZEDS QUASAR QUEEN MATTRESS IN A BOX', 'PR539-ZEDS QUASAR-Q', 399.00, NULL, '2026-09-22 08:44:34', NULL, 2166, 5, NULL, NULL, NULL, NULL, '75', NULL),
(237, 1, 'Bluetooth Speaker', NULL, 149.00, '8527318581434', '2026-09-23 08:34:21', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(238, 1, 'laptop', '0019', 1000.00, '8524081660090', '2026-09-23 08:34:21', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(239, 1, 'laptop', '0019', 1000.00, '8538417725626', '2026-09-23 08:34:21', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(240, 1, 'Mobile Cover', 'SKU003', 500.00, '8523934630074', '2026-09-23 08:34:21', NULL, 0, 5, NULL, NULL, NULL, NULL, NULL, NULL),
(241, 1, 'test-pants', NULL, 10.00, '8531210895546', '2026-09-23 08:34:21', NULL, 220, 5, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', NULL, NULL, NULL, NULL, NULL),
(272, 16, 'Hummingbird printed t-shirt', 'demo_1', 23.90, NULL, '2026-09-24 04:56:54', NULL, 2400, 5, NULL, NULL, '1', NULL, NULL, NULL),
(273, 16, 'Hummingbird printed sweater', 'demo_3', 35.90, NULL, '2026-09-24 04:57:02', NULL, 2100, 5, NULL, NULL, '2', NULL, NULL, NULL),
(274, 16, 'The best is yet to come\' Framed poster', 'demo_6', 29.00, NULL, '2026-09-24 04:57:03', NULL, 1500, 5, NULL, NULL, '3', NULL, NULL, NULL),
(275, 16, 'The adventure begins Framed poster', 'demo_5', 29.00, NULL, '2026-09-24 04:57:03', NULL, 1500, 5, NULL, NULL, '4', NULL, NULL, NULL),
(276, 16, 'Today is a good day Framed poster', 'demo_7', 29.00, NULL, '2026-09-24 04:57:04', NULL, 900, 5, NULL, NULL, '5', NULL, NULL, NULL),
(277, 16, 'Mug The best is yet to come', 'demo_11', 11.90, NULL, '2026-09-24 04:57:04', NULL, 300, 5, NULL, NULL, '6', NULL, NULL, NULL),
(278, 16, 'Mug The adventure begins', 'demo_12', 11.90, NULL, '2026-09-24 04:57:05', NULL, 300, 5, NULL, NULL, '7', NULL, NULL, NULL),
(279, 16, 'Mug Today is a good day', 'demo_13', 11.90, NULL, '2026-09-24 04:57:05', NULL, 300, 5, NULL, NULL, '8', NULL, NULL, NULL),
(280, 16, 'Mountain fox cushion', 'demo_15', 18.90, NULL, '2026-09-24 04:57:05', NULL, 600, 5, NULL, NULL, '9', NULL, NULL, NULL),
(281, 16, 'Brown bear cushion', 'demo_16', 18.90, NULL, '2026-09-24 04:57:05', NULL, 600, 5, NULL, NULL, '10', NULL, NULL, NULL),
(282, 16, 'Hummingbird cushion', 'demo_17', 18.90, NULL, '2026-09-24 04:57:06', NULL, 600, 5, NULL, NULL, '11', NULL, NULL, NULL),
(283, 16, 'Mountain fox - Vector graphics', 'demo_18', 9.00, NULL, '2026-09-24 04:57:06', NULL, 300, 5, NULL, NULL, '12', NULL, NULL, NULL),
(284, 16, 'Brown bear - Vector graphics', 'demo_19', 9.00, NULL, '2026-09-24 04:57:06', NULL, 300, 5, NULL, NULL, '13', NULL, NULL, NULL),
(285, 16, 'Hummingbird - Vector graphics', 'demo_20', 9.00, NULL, '2026-09-24 04:57:06', NULL, 300, 5, NULL, NULL, '14', NULL, NULL, NULL),
(286, 16, 'Pack Mug + Framed poster', 'demo_21', 35.00, NULL, '2026-09-24 04:57:06', NULL, 100, 5, NULL, NULL, '15', NULL, NULL, NULL),
(287, 16, 'Mountain fox notebook', 'demo_8', 12.90, NULL, '2026-09-24 04:57:07', NULL, 1200, 5, NULL, NULL, '16', NULL, NULL, NULL),
(288, 16, 'Brown bear notebook', 'demo_9', 12.90, NULL, '2026-09-24 04:57:07', NULL, 1200, 5, NULL, NULL, '17', NULL, NULL, NULL),
(289, 16, 'Hummingbird notebook', 'demo_10', 12.90, NULL, '2026-09-24 04:57:07', NULL, 1200, 5, NULL, NULL, '18', NULL, NULL, NULL),
(290, 16, 'Customizable mug', 'demo_14', 13.90, NULL, '2026-09-24 04:57:07', NULL, 300, 5, NULL, NULL, '19', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `label` varchar(150) NOT NULL,
  `attributes` varchar(255) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `external_variant_id` varchar(100) DEFAULT NULL,
  `external_wc_variant_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `label`, `attributes`, `sku`, `price`, `image_url`, `stock_quantity`, `external_variant_id`, `external_wc_variant_id`, `created_at`) VALUES
(1, 36, 'Small / Blue', 'Size, Color', NULL, 10.00, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 60, '46620714762426', NULL, '2026-09-11 05:07:35'),
(2, 36, 'Medium / Blue', 'Size, Color', NULL, 12.00, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 61, '46620714795194', NULL, '2026-09-11 05:07:35'),
(3, 36, 'Large / Blue', 'Size, Color', NULL, 14.00, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 110, '46620714827962', NULL, '2026-09-11 05:07:35'),
(4, 36, 'X-Large / Blue', 'Size, Color', NULL, 15.50, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 150, '46620714860730', NULL, '2026-09-11 05:07:35'),
(5, 48, 'Small / Blue', 'Size, Color', NULL, 10.00, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 20, '46620714762426', NULL, '2026-09-21 03:52:11'),
(6, 48, 'Medium / Blue', 'Size, Color', NULL, 12.00, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 40, '46620714795194', NULL, '2026-09-21 03:52:11'),
(7, 48, 'Large / Blue', 'Size, Color', NULL, 14.00, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 60, '46620714827962', NULL, '2026-09-21 03:52:11'),
(8, 48, 'X-Large / Blue', 'Size, Color', NULL, 15.50, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 100, '46620714860730', NULL, '2026-09-21 03:52:11'),
(9, 102, 'Small / Blue', 'Size, Color', NULL, 10.00, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 20, '46620714762426', NULL, '2026-09-21 11:11:39'),
(10, 102, 'Medium / Blue', 'Size, Color', NULL, 12.00, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 40, '46620714795194', NULL, '2026-09-21 11:11:39'),
(11, 102, 'Large / Blue', 'Size, Color', NULL, 14.00, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 60, '46620714827962', NULL, '2026-09-21 11:11:39'),
(12, 102, 'X-Large / Blue', 'Size, Color', NULL, 15.50, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 100, '46620714860730', NULL, '2026-09-21 11:11:39'),
(13, 241, 'Small / Blue', 'Size, Color', NULL, 10.00, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 20, '46620714762426', NULL, '2026-09-23 08:34:21'),
(14, 241, 'Medium / Blue', 'Size, Color', NULL, 12.00, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 40, '46620714795194', NULL, '2026-09-23 08:34:21'),
(15, 241, 'Large / Blue', 'Size, Color', NULL, 14.00, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 60, '46620714827962', NULL, '2026-09-23 08:34:21'),
(16, 241, 'X-Large / Blue', 'Size, Color', NULL, 15.50, 'https://cdn.shopify.com/s/files/1/0779/4052/3194/files/Screenshot2026-09-11095346.png?v=1789102462', 100, '46620714860730', NULL, '2026-09-23 08:34:21');

-- --------------------------------------------------------

--
-- Table structure for table `product_warehouse_stock`
--

DROP TABLE IF EXISTS `product_warehouse_stock`;
CREATE TABLE `product_warehouse_stock` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_warehouse_stock`
--

INSERT INTO `product_warehouse_stock` (`id`, `product_id`, `warehouse_id`, `stock_quantity`) VALUES
(1, 1, 1, 200),
(2, 2, 1, 250),
(3, 3, 1, 37),
(4, 21, 1, 54),
(5, 25, 1, 480),
(6, 26, 1, 155),
(7, 27, 1, 300),
(8, 28, 1, 65),
(9, 33, 1, 1895),
(10, 34, 1, 0),
(11, 21, 2, 200),
(12, 25, 2, 450),
(30, 35, 1, 50),
(34, 38, 1, 10),
(41, 33, 2, 1),
(43, 39, 1, 48),
(44, 40, 1, 30),
(45, 41, 1, 0),
(46, 42, 1, 10),
(47, 43, 1, 10),
(48, 44, 1, 0),
(49, 45, 1, 0),
(50, 46, 1, 0),
(51, 47, 1, 0),
(52, 53, 2, 0),
(53, 54, 1, 0),
(54, 55, 1, 0),
(55, 56, 1, 0),
(56, 57, 1, 0),
(57, 58, 1, 0),
(58, 59, 1, 0),
(59, 60, 1, 0),
(60, 61, 1, 0),
(61, 62, 1, 0),
(62, 63, 1, 0),
(63, 64, 1, 0),
(64, 65, 1, 0),
(65, 66, 1, 0),
(66, 72, 1, 2400),
(67, 73, 1, 2100),
(68, 74, 1, 1500),
(69, 75, 1, 1500),
(70, 76, 1, 900),
(71, 77, 1, 300),
(72, 78, 1, 300),
(73, 79, 1, 300),
(74, 80, 1, 600),
(75, 81, 1, 600),
(76, 82, 1, 600),
(77, 83, 1, 300),
(78, 84, 1, 300),
(79, 85, 1, 300),
(80, 86, 1, 100),
(81, 87, 1, 1200),
(82, 88, 1, 1200),
(83, 89, 1, 1200),
(84, 90, 1, 300),
(85, 21, 3, 50),
(87, 34, 3, 0),
(89, 95, 1, 0),
(90, 96, 1, 0),
(91, 97, 1, 0),
(92, 98, 1, 0),
(93, 99, 1, 0),
(94, 100, 1, 0),
(95, 101, 1, 0),
(96, 105, 3, 0),
(97, 106, 3, 0),
(98, 107, 3, 0),
(99, 108, 3, 0),
(100, 109, 3, 0),
(101, 110, 3, 0),
(102, 111, 3, 0),
(103, 112, 3, 0),
(104, 113, 3, 0),
(105, 114, 3, 0),
(106, 115, 3, 0),
(107, 116, 3, 0),
(108, 117, 3, 0),
(109, 118, 3, 0),
(110, 124, 3, 2400),
(111, 125, 3, 2100),
(112, 126, 3, 1500),
(113, 127, 3, 1500),
(114, 128, 3, 900),
(115, 129, 3, 300),
(116, 130, 3, 300),
(117, 131, 3, 300),
(118, 132, 3, 600),
(119, 133, 3, 600),
(120, 134, 3, 600),
(121, 135, 3, 300),
(122, 136, 3, 300),
(123, 137, 3, 300),
(124, 138, 3, 100),
(125, 139, 3, 1200),
(126, 140, 3, 1200),
(127, 141, 3, 1200),
(128, 142, 3, 300),
(129, 143, 3, 1000),
(130, 144, 3, 995),
(131, 145, 3, 970),
(132, 146, 3, 939),
(133, 147, 3, 1000),
(134, 148, 3, 998),
(135, 149, 3, 999),
(136, 150, 3, 994),
(137, 151, 3, 1000),
(138, 152, 3, 1000),
(139, 153, 3, 999),
(140, 154, 3, 977),
(141, 155, 3, 1000),
(142, 156, 3, 1000),
(143, 157, 3, 929),
(144, 158, 3, 1000),
(145, 159, 3, 0),
(146, 160, 3, 990),
(147, 161, 3, 7),
(148, 162, 3, 99),
(149, 163, 3, 100),
(150, 164, 3, 100),
(151, 165, 3, 98),
(152, 166, 3, 100),
(153, 167, 3, 100),
(154, 168, 3, 98),
(155, 169, 3, 100),
(156, 170, 3, 98),
(157, 171, 3, 100),
(158, 172, 3, 100),
(159, 173, 3, 100),
(160, 174, 3, 100),
(161, 175, 3, 100),
(162, 176, 3, 96),
(163, 177, 3, 100),
(164, 178, 3, 100),
(165, 179, 3, 99),
(166, 180, 3, 100),
(167, 181, 3, 100),
(168, 182, 3, 100),
(169, 183, 3, 100),
(170, 184, 3, 100),
(171, 185, 3, 100),
(172, 186, 3, 100),
(173, 187, 3, 100),
(174, 188, 3, 100),
(175, 189, 3, 100),
(176, 190, 3, 100),
(177, 191, 3, 100),
(178, 192, 3, 100),
(179, 193, 3, 100),
(180, 194, 3, 100),
(181, 195, 3, 99),
(182, 196, 3, 100),
(183, 197, 3, 100),
(184, 198, 3, 90),
(185, 199, 3, 100),
(186, 200, 3, 100),
(187, 201, 3, 100),
(188, 202, 3, 100),
(189, 203, 3, 100),
(190, 204, 3, 100),
(191, 205, 3, 99),
(192, 206, 3, 99),
(193, 207, 3, 100),
(194, 208, 3, 100),
(195, 209, 3, 98),
(196, 210, 3, 100),
(197, 211, 3, 2356),
(198, 212, 3, 2000),
(199, 213, 3, 1050),
(200, 214, 3, 2038),
(201, 215, 3, 2038),
(202, 216, 3, 2129),
(203, 217, 3, 1019),
(204, 218, 3, 955),
(205, 219, 3, 22464),
(206, 220, 3, 1096),
(207, 221, 3, 1100),
(208, 222, 3, 2200),
(209, 223, 3, 2000),
(210, 224, 3, 2000),
(211, 225, 3, 998),
(212, 226, 3, 2000),
(213, 227, 3, 1000),
(214, 228, 3, 2000),
(215, 229, 3, 997),
(216, 230, 3, 2000),
(217, 231, 3, 996),
(218, 232, 3, 2000),
(219, 233, 3, 2000),
(220, 234, 3, 2094),
(221, 235, 3, 999),
(222, 236, 3, 2166),
(224, 237, 3, 0),
(225, 238, 3, 0),
(226, 239, 3, 0),
(227, 240, 3, 0),
(251, 272, 1, 2400),
(252, 273, 1, 2100),
(253, 274, 1, 1500),
(254, 275, 1, 1500),
(255, 276, 1, 900),
(256, 277, 1, 300),
(257, 278, 1, 300),
(258, 279, 1, 300),
(259, 280, 1, 600),
(260, 281, 1, 600),
(261, 282, 1, 600),
(262, 283, 1, 300),
(263, 284, 1, 300),
(264, 285, 1, 300),
(265, 286, 1, 100),
(266, 287, 1, 1200),
(267, 288, 1, 1200),
(268, 289, 1, 1200),
(269, 290, 1, 300);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `received_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `store_id`, `supplier_id`, `warehouse_id`, `status`, `notes`, `created_at`, `received_at`) VALUES
(1, 1, 1, 1, 'received', NULL, '2026-09-14 10:44:16', '2026-09-14 12:44:31');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `purchase_order_id`, `product_id`, `quantity`, `unit_cost`) VALUES
(1, 1, 2, 50, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `returns`
--

DROP TABLE IF EXISTS `returns`;
CREATE TABLE `returns` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `warehouse_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `reason` varchar(255) DEFAULT NULL,
  `condition_status` varchar(50) NOT NULL DEFAULT 'resellable',
  `status` varchar(50) NOT NULL DEFAULT 'requested',
  `refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `returns`
--

INSERT INTO `returns` (`id`, `store_id`, `order_id`, `product_id`, `variant_id`, `warehouse_id`, `quantity`, `reason`, `condition_status`, `status`, `refund_amount`, `created_at`) VALUES
(1, 1, 32, 36, 1, 1, 1, 'Defective', 'damaged', 'completed', 0.00, '2026-09-14 10:45:55'),
(2, 18, 80, 215, NULL, 3, 1, 'Damaged', 'resellable', 'completed', 1303.48, '2026-09-22 09:15:18'),
(3, 18, 78, 190, NULL, 3, 1, 'wrong size', 'resellable', 'completed', 400.00, '2026-09-22 09:30:22');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `created_at`) VALUES
(1, 'Company Admin', '2026-09-04 05:23:44'),
(2, 'Manager', '2026-09-04 05:23:44'),
(3, 'Sales Staff', '2026-09-04 05:23:44'),
(4, 'Warehouse Staff', '2026-09-04 05:23:44'),
(5, 'Company Admin', '2026-09-04 05:59:00'),
(6, 'Manager', '2026-09-04 05:59:00'),
(7, 'Sales Staff', '2026-09-04 05:59:00'),
(8, 'Warehouse Staff', '2026-09-04 05:59:00'),
(9, 'Admin', '2026-09-09 03:40:44'),
(10, 'Sales Staff', '2026-09-09 03:40:46'),
(11, 'Warehouse Staff', '2026-09-09 03:40:46'),
(12, 'Warehouse-staff', '2026-09-11 03:21:57');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shopify_store_url` varchar(255) DEFAULT NULL,
  `shopify_access_token` varchar(255) DEFAULT NULL,
  `woocommerce_store_url` varchar(255) DEFAULT NULL,
  `woocommerce_consumer_key` varchar(255) DEFAULT NULL,
  `woocommerce_consumer_secret` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

DROP TABLE IF EXISTS `shipments`;
CREATE TABLE `shipments` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `courier_name` varchar(100) NOT NULL,
  `tracking_number` varchar(150) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `dispatched_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `handover_status` enum('pending','handed_over') NOT NULL DEFAULT 'pending',
  `handover_scanned_at` timestamp NULL DEFAULT NULL,
  `handover_scanned_by` varchar(100) DEFAULT NULL,
  `cod_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remittance_status` enum('not_remitted','remitted') NOT NULL DEFAULT 'not_remitted',
  `remittance_amount` decimal(12,2) DEFAULT NULL,
  `remitted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipments`
--

INSERT INTO `shipments` (`id`, `store_id`, `courier_name`, `tracking_number`, `status`, `dispatched_at`, `delivered_at`, `notes`, `created_at`, `handover_status`, `handover_scanned_at`, `handover_scanned_by`, `cod_amount`, `remittance_status`, `remittance_amount`, `remitted_at`) VALUES
(1, 1, 'TCS', NULL, 'delivered', '2026-09-13 18:35:44', '2026-09-14 04:47:03', NULL, '2026-09-13 16:35:35', 'pending', NULL, NULL, 0.00, 'not_remitted', NULL, NULL),
(2, 1, 'TCS', NULL, 'delivered', '2026-09-14 04:54:48', '2026-09-14 04:54:48', NULL, '2026-09-14 02:54:41', 'pending', NULL, NULL, 0.00, 'not_remitted', NULL, NULL),
(3, 1, 'TCS', NULL, 'delivered', '2026-09-14 12:28:40', '2026-09-14 12:28:40', NULL, '2026-09-14 10:28:31', 'pending', NULL, NULL, 0.00, 'not_remitted', NULL, NULL),
(4, 1, 'TCS', NULL, 'delivered', '2026-09-14 12:42:28', '2026-09-14 12:42:28', NULL, '2026-09-14 10:42:19', 'pending', NULL, NULL, 0.00, 'not_remitted', NULL, NULL),
(5, 1, 'TCS', NULL, 'delivered', '2026-09-16 09:27:08', '2026-09-16 09:27:08', NULL, '2026-09-16 07:26:58', 'pending', NULL, NULL, 0.00, 'not_remitted', NULL, NULL),
(6, 1, 'TCS', NULL, 'delivered', '2026-09-16 12:02:41', '2026-09-16 12:02:41', NULL, '2026-09-16 07:27:18', 'pending', NULL, NULL, 0.00, 'not_remitted', NULL, NULL),
(7, 1, 'TCS', 'FIN-20260916-13E892', 'delivered', '2026-09-16 12:04:48', '2026-09-16 12:04:48', NULL, '2026-09-16 10:04:43', 'pending', NULL, NULL, 0.00, 'not_remitted', NULL, NULL),
(8, 1, 'DHL', '7777777770', 'packed', '2026-09-16 12:08:57', '2026-09-16 12:08:57', NULL, '2026-09-16 10:08:48', 'pending', NULL, NULL, 0.00, 'not_remitted', NULL, NULL),
(9, 5, 'TCS', 'FIN-20260917-6F53A1', 'delivered', '2026-09-17 08:26:54', '2026-09-17 08:26:54', NULL, '2026-09-17 06:26:47', 'pending', NULL, NULL, 0.00, 'not_remitted', NULL, NULL),
(10, 1, 'TCS', 'FIN-20260923-DA4A88', 'delivered', '2026-09-23 06:02:05', '2026-09-23 06:02:05', NULL, '2026-09-23 04:01:55', 'pending', NULL, NULL, 0.00, 'not_remitted', NULL, NULL),
(11, 1, 'TCS', 'FIN-20260923-4C35C1', 'delivered', '2026-09-23 06:04:48', '2026-09-23 09:35:29', NULL, '2026-09-23 04:04:34', 'handed_over', '2026-09-23 04:04:48', 'Admin User', 0.00, 'remitted', 1500.00, '2026-09-23 01:05:45'),
(12, 1, 'DHL', 'FIN-20260923-364974', 'packed', '2026-09-23 09:35:23', '2026-09-23 09:35:23', NULL, '2026-09-23 05:50:22', 'pending', NULL, NULL, 0.00, 'not_remitted', NULL, NULL),
(13, 1, 'DHL', '7777777770', 'packed', NULL, NULL, NULL, '2026-09-23 09:53:16', 'pending', NULL, NULL, 0.00, 'not_remitted', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sku_mappings`
--

DROP TABLE IF EXISTS `sku_mappings`;
CREATE TABLE `sku_mappings` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `external_product_id` varchar(150) DEFAULT NULL,
  `external_variant_id` varchar(150) DEFAULT NULL,
  `external_sku` varchar(150) DEFAULT NULL,
  `barcode` varchar(150) DEFAULT NULL,
  `mapping_state` varchar(30) NOT NULL DEFAULT 'mapped',
  `mapped_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sku_mappings`
--

INSERT INTO `sku_mappings` (`id`, `store_id`, `product_id`, `variant_id`, `external_product_id`, `external_variant_id`, `external_sku`, `barcode`, `mapping_state`, `mapped_by`, `created_at`, `updated_at`) VALUES
(1, 1, 2, NULL, '9999', NULL, 'SKU-TEST-01', NULL, 'mapped', 'Admin User', '2026-09-17 03:12:18', '2026-09-17 03:12:18'),
(2, 1, 38, NULL, '99999', NULL, 'SKU-TEST-02', NULL, 'mapped', 'Admin User', '2026-09-17 03:31:35', '2026-09-17 03:31:35');

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustments`
--

DROP TABLE IF EXISTS `stock_adjustments`;
CREATE TABLE `stock_adjustments` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `warehouse_id` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_adjustments`
--

INSERT INTO `stock_adjustments` (`id`, `product_id`, `variant_id`, `warehouse_id`, `quantity_change`, `reason`, `created_at`) VALUES
(1, 28, NULL, 1, 5, 'Damaged', '2026-09-14 10:45:22');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfers`
--

DROP TABLE IF EXISTS `stock_transfers`;
CREATE TABLE `stock_transfers` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `from_warehouse_id` int(11) NOT NULL,
  `to_warehouse_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_transfers`
--

INSERT INTO `stock_transfers` (`id`, `product_id`, `variant_id`, `from_warehouse_id`, `to_warehouse_id`, `quantity`, `notes`, `created_at`) VALUES
(1, 33, NULL, 1, 2, 1, NULL, '2026-09-14 10:44:52');

-- --------------------------------------------------------

--
-- Table structure for table `stores`
--

DROP TABLE IF EXISTS `stores`;
CREATE TABLE `stores` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `platform` varchar(50) NOT NULL DEFAULT 'manual',
  `store_url` varchar(255) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `access_token` varchar(255) DEFAULT NULL,
  `consumer_key` varchar(255) DEFAULT NULL,
  `consumer_secret` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `woocommerce_store_url` varchar(255) DEFAULT NULL,
  `woocommerce_consumer_key` varchar(255) DEFAULT NULL,
  `woocommerce_consumer_secret` varchar(255) DEFAULT NULL,
  `shopify_webhook_secret` varchar(255) DEFAULT NULL,
  `woocommerce_webhook_secret` varchar(255) DEFAULT NULL,
  `health_status` varchar(30) NOT NULL DEFAULT 'disconnected',
  `last_successful_sync` datetime DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `bridge_url` varchar(255) DEFAULT NULL,
  `bridge_api_key` varchar(255) DEFAULT NULL,
  `bridge_shared_secret` varchar(255) DEFAULT NULL,
  `bigcommerce_store_hash` varchar(50) DEFAULT NULL,
  `bigcommerce_access_token` varchar(255) DEFAULT NULL,
  `prestashop_store_url` varchar(255) DEFAULT NULL,
  `prestashop_api_key` varchar(100) DEFAULT NULL,
  `opencart_store_url` varchar(255) DEFAULT NULL,
  `opencart_api_username` varchar(100) DEFAULT NULL,
  `opencart_api_key` varchar(255) DEFAULT NULL,
  `oscommerce_store_url` varchar(255) DEFAULT NULL,
  `oscommerce_api_username` varchar(100) DEFAULT NULL,
  `oscommerce_api_key` varchar(255) DEFAULT NULL,
  `wix_site_id` varchar(100) DEFAULT NULL,
  `wix_api_key` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_warehouses`
--

DROP TABLE IF EXISTS `store_warehouses`;
CREATE TABLE `store_warehouses` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_warehouses`
--

INSERT INTO `store_warehouses` (`id`, `store_id`, `warehouse_id`, `is_default`, `created_at`) VALUES
(1, 1, 1, 0, '2026-09-11 08:29:10'),
(2, 1, 2, 0, '2026-09-11 08:29:10'),
(3, 2, 1, 1, '2026-09-11 08:35:01'),
(4, 2, 2, 0, '2026-09-11 08:35:01'),
(5, 3, 1, 1, '2026-09-12 06:17:38'),
(6, 3, 2, 0, '2026-09-12 06:17:38'),
(7, 1, 3, 1, '2026-09-16 10:07:21'),
(8, 3, 3, 0, '2026-09-16 10:07:21'),
(9, 4, 1, 1, '2026-09-17 05:09:41'),
(10, 5, 1, 1, '2026-09-17 06:23:10'),
(11, 5, 2, 0, '2026-09-17 06:23:10'),
(13, 7, 1, 1, '2026-09-21 03:41:48'),
(14, 8, 2, 1, '2026-09-21 03:43:23'),
(16, 9, 1, 1, '2026-09-21 05:39:44'),
(17, 10, 1, 1, '2026-09-21 09:57:27'),
(21, 12, 1, 1, '2026-09-21 11:11:01'),
(22, 12, 3, 0, '2026-09-21 11:11:01'),
(23, 12, 2, 0, '2026-09-21 11:11:01'),
(24, 13, 3, 1, '2026-09-21 11:12:11'),
(25, 13, 2, 0, '2026-09-21 11:12:11'),
(26, 14, 3, 1, '2026-09-21 11:15:32'),
(27, 14, 2, 0, '2026-09-21 11:15:32'),
(28, 15, 3, 1, '2026-09-21 11:29:02'),
(29, 15, 2, 0, '2026-09-21 11:29:02'),
(30, 16, 1, 1, '2026-09-22 04:05:50'),
(31, 16, 3, 0, '2026-09-22 04:05:50'),
(32, 17, 3, 1, '2026-09-22 06:23:44'),
(33, 17, 2, 0, '2026-09-22 06:23:44'),
(34, 18, 3, 1, '2026-09-22 08:43:12'),
(35, 19, 3, 1, '2026-09-24 05:05:56');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `store_id`, `name`, `contact_person`, `email`, `phone`, `created_at`) VALUES
(1, 1, 'Finovo Traders', 'Ali', 'Ali@gmail.com', '03466893562', '2026-09-14 10:43:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_otp` varchar(10) DEFAULT NULL,
  `reset_otp_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `password_hash`, `status`, `created_at`, `reset_otp`, `reset_otp_expires`) VALUES
(1, 1, 'Mahir', 'mahirprasla@gmail.com', '$2y$10$wSkWVtxXi53rZHW8D1YeNOnZmUsop8aWoFiDfouJe.mZjoLcUd/Lu', 'active', '2026-09-04 05:23:44', NULL, NULL),
(2, 5, 'rabil', 'rabil@company.com', '$2y$10$qCNpsVdyOsOEpkHSRqlNPOU4a8O/7t6.7lKedC0lPqfkqX5NIm0ei', 'active', '2026-09-04 05:59:00', NULL, NULL),
(3, 9, 'Admin User', 'admin@finovo.com', '$2b$10$zmI78q5wUDbsHoaDZXk5/OwfrYc4wxwCt7x3vInN0lIHNi1z5dQqu', 'active', '2026-09-12 04:34:40', NULL, NULL),
(4, 3, 'Rehan', 'rehan@gmail.com', '$2y$10$rmDKIeknsei1ppWrxkrhZOInQZFzC/a6uzLS2qMsbXVe9ADMA9Ys.', 'active', '2026-09-12 04:42:55', NULL, NULL),
(5, 4, 'Mahir Rahim', 'mahirrahim134@gmail.com', '$2y$10$l5AFpgfwGl6jhOCeugHcuOUE/hS.otLwjrGjFDYHiYuR4G0aK2foS', 'active', '2026-09-12 05:05:06', NULL, NULL),
(6, 10, 'Ali', 'ali@gmail.com', '$2y$10$qqUufpyq3JxXlEh/byyvn.uZGXjO.8GF3i9au4m1t5ymHzSNr7Ml.', 'active', '2026-09-14 10:47:11', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `variant_warehouse_stock`
--

DROP TABLE IF EXISTS `variant_warehouse_stock`;
CREATE TABLE `variant_warehouse_stock` (
  `id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `variant_warehouse_stock`
--

INSERT INTO `variant_warehouse_stock` (`id`, `variant_id`, `warehouse_id`, `stock_quantity`) VALUES
(1, 1, 1, 20),
(2, 2, 1, 40),
(3, 3, 1, 60),
(4, 4, 1, 100),
(5, 3, 2, 50),
(6, 2, 2, 21),
(7, 1, 2, 40),
(8, 4, 2, 50),
(15, 5, 1, 20),
(16, 6, 1, 40),
(17, 7, 1, 60),
(18, 8, 1, 100),
(19, 9, 1, 20),
(20, 10, 1, 40),
(21, 11, 1, 60),
(22, 12, 1, 100),
(23, 13, 3, 20),
(24, 14, 3, 40),
(25, 15, 3, 60),
(26, 16, 3, 100);

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE `warehouses` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `name`, `location`, `created_at`) VALUES
(1, 'Main Warehouse', 'Head Office', '2026-09-10 07:07:20'),
(2, 'Wholeseller', 'Gulistan e johar', '2026-09-10 07:26:30'),
(3, 'Seller', 'Gulzar-e-Hijri', '2026-09-16 10:07:21');

-- --------------------------------------------------------

--
-- Table structure for table `webhook_events`
--

DROP TABLE IF EXISTS `webhook_events`;
CREATE TABLE `webhook_events` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `source` varchar(30) NOT NULL,
  `external_id` varchar(150) NOT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `received_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `webhook_events`
--

INSERT INTO `webhook_events` (`id`, `store_id`, `source`, `external_id`, `event_type`, `received_at`) VALUES
(1, 1, 'woocommerce', '18', 'order.created', '2026-09-16 17:10:16'),
(2, 1, 'woocommerce', '21', 'order.created', '2026-09-16 17:18:51'),
(3, 1, 'shopify', '8591639707834', 'orders/create', '2026-09-16 17:41:54'),
(4, 1, 'shopify', '8591666413754', 'orders/create', '2026-09-16 17:46:35'),
(5, 4, 'custom_bridge', 'BRIDGE-ORD-1789624721', 'order.created', '2026-09-17 05:58:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `store_id` (`store_id`);

--
-- Indexes for table `field_mappings`
--
ALTER TABLE `field_mappings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_mapping` (`store_id`,`platform`,`entity_type`,`finovo_field`);

--
-- Indexes for table `integration_errors`
--
ALTER TABLE `integration_errors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `store_id` (`store_id`);

--
-- Indexes for table `inventory_ledger`
--
ALTER TABLE `inventory_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_store_id` (`store_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_store_id` (`store_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_source` (`source`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_shipment_id` (`shipment_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_order_group` (`order_group`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_store_id` (`store_id`),
  ADD KEY `idx_sku` (`sku`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_warehouse_stock`
--
ALTER TABLE `product_warehouse_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_warehouse_unique` (`product_id`,`warehouse_id`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `idx_product_warehouse` (`product_id`,`warehouse_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `store_id` (`store_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `idx_store_id` (`store_id`),
  ADD KEY `idx_order_id` (`order_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_store_id` (`store_id`),
  ADD KEY `idx_tracking_number` (`tracking_number`);

--
-- Indexes for table `sku_mappings`
--
ALTER TABLE `sku_mappings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `store_id` (`store_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `from_warehouse_id` (`from_warehouse_id`),
  ADD KEY `to_warehouse_id` (`to_warehouse_id`);

--
-- Indexes for table `stores`
--
ALTER TABLE `stores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_platform` (`platform`);

--
-- Indexes for table `store_warehouses`
--
ALTER TABLE `store_warehouses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `store_warehouse_unique` (`store_id`,`warehouse_id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `store_id` (`store_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `variant_warehouse_stock`
--
ALTER TABLE `variant_warehouse_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `variant_warehouse_unique` (`variant_id`,`warehouse_id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `webhook_events`
--
ALTER TABLE `webhook_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_event` (`store_id`,`source`,`external_id`,`event_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `field_mappings`
--
ALTER TABLE `field_mappings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `integration_errors`
--
ALTER TABLE `integration_errors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory_ledger`
--
ALTER TABLE `inventory_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=291;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `product_warehouse_stock`
--
ALTER TABLE `product_warehouse_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=270;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `sku_mappings`
--
ALTER TABLE `sku_mappings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stores`
--
ALTER TABLE `stores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_warehouses`
--
ALTER TABLE `store_warehouses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `variant_warehouse_stock`
--
ALTER TABLE `variant_warehouse_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `webhook_events`
--
ALTER TABLE `webhook_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `integration_errors`
--
ALTER TABLE `integration_errors`
  ADD CONSTRAINT `integration_errors_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_ledger`
--
ALTER TABLE `inventory_ledger`
  ADD CONSTRAINT `inventory_ledger_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_ledger_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_store_fk` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_warehouse_stock`
--
ALTER TABLE `product_warehouse_stock`
  ADD CONSTRAINT `product_warehouse_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_warehouse_stock_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `returns_ibfk_3` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `shipments_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sku_mappings`
--
ALTER TABLE `sku_mappings`
  ADD CONSTRAINT `sku_mappings_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sku_mappings_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD CONSTRAINT `stock_adjustments_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_adjustments_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD CONSTRAINT `stock_transfers_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transfers_ibfk_2` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transfers_ibfk_3` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_warehouses`
--
ALTER TABLE `store_warehouses`
  ADD CONSTRAINT `store_warehouses_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `store_warehouses_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `suppliers_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `variant_warehouse_stock`
--
ALTER TABLE `variant_warehouse_stock`
  ADD CONSTRAINT `variant_warehouse_stock_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `variant_warehouse_stock_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `webhook_events`
--
ALTER TABLE `webhook_events`
  ADD CONSTRAINT `webhook_events_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
