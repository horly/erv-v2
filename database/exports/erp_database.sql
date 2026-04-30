-- EXAD ERP database export
-- Generated: 2026-04-30 00:00:00 +01:00
-- Database: erp_database
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES ('exad-erp-cache-75e3a34c001646fc27c748682c1964e7', 'i:1;', '1777386500');
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES ('exad-erp-cache-75e3a34c001646fc27c748682c1964e7:timer', 'i:1777386500;', '1777386500');

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `rccm` varchar(255) DEFAULT NULL,
  `id_nat` varchar(255) DEFAULT NULL,
  `nif` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'Congo (RDC)',
  `logo` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(32) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `companies_subscription_id_foreign` (`subscription_id`),
  KEY `companies_created_by_foreign` (`created_by`),
  CONSTRAINT `companies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `companies_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `companies` (`id`, `subscription_id`, `created_by`, `name`, `rccm`, `id_nat`, `nif`, `website`, `slogan`, `country`, `logo`, `email`, `phone_number`, `address`, `created_at`, `updated_at`) VALUES ('2', '2', '3', 'Prestavice', NULL, NULL, NULL, NULL, NULL, 'Congo (RDC)', NULL, 'contact@prestavice.com', NULL, NULL, '2026-04-25 21:08:54', '2026-04-25 21:08:54');
INSERT INTO `companies` (`id`, `subscription_id`, `created_by`, `name`, `rccm`, `id_nat`, `nif`, `website`, `slogan`, `country`, `logo`, `email`, `phone_number`, `address`, `created_at`, `updated_at`) VALUES ('3', '4', '9', 'Test Entreprise', NULL, NULL, NULL, NULL, 'test', 'Congo (RDC)', 'company-logos/iiW6TXFrFR73wEWiSWgGlWRpdsX5RuHnAq4DeFrG.jpg', 'testpro@test.loc', NULL, NULL, '2026-04-26 20:03:54', '2026-04-26 20:03:54');
INSERT INTO `companies` (`id`, `subscription_id`, `created_by`, `name`, `rccm`, `id_nat`, `nif`, `website`, `slogan`, `country`, `logo`, `email`, `phone_number`, `address`, `created_at`, `updated_at`) VALUES ('4', '2', '3', 'EXAD', NULL, NULL, NULL, NULL, NULL, 'Congo (RDC)', 'company-logos/sJkem1sKETkIF88RE0z25XXFex4UONOUApjQ0flC.jpg', 'sales@exadgroup.org', NULL, NULL, '2026-04-28 10:19:49', '2026-04-28 10:19:49');

DROP TABLE IF EXISTS `company_accounts`;
CREATE TABLE `company_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `account_number` varchar(100) NOT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `currency` varchar(12) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_accounts_company_id_foreign` (`company_id`),
  CONSTRAINT `company_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `company_phones`;
CREATE TABLE `company_phones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `phone_number` varchar(50) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_phones_company_id_foreign` (`company_id`),
  CONSTRAINT `company_phones_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `company_site_user`;
CREATE TABLE `company_site_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `module_permissions` json DEFAULT NULL,
  `can_create` tinyint(1) NOT NULL DEFAULT 0,
  `can_update` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_site_user_company_site_id_user_id_unique` (`company_site_id`,`user_id`),
  KEY `company_site_user_user_id_index` (`user_id`),
  CONSTRAINT `company_site_user_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_site_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `company_sites`;
CREATE TABLE `company_sites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `responsible_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(30) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `modules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`modules`)),
  `currency` varchar(3) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_sites_responsible_id_foreign` (`responsible_id`),
  KEY `company_sites_company_id_type_index` (`company_id`,`type`),
  KEY `company_sites_currency_index` (`currency`),
  KEY `company_sites_status_index` (`status`),
  CONSTRAINT `company_sites_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_sites_responsible_id_foreign` FOREIGN KEY (`responsible_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `accounting_client_contacts`;
DROP TABLE IF EXISTS `accounting_prospect_contacts`;
DROP TABLE IF EXISTS `accounting_prospects`;
DROP TABLE IF EXISTS `accounting_supplier_contacts`;
DROP TABLE IF EXISTS `accounting_suppliers`;
DROP TABLE IF EXISTS `accounting_stock_alerts`;
DROP TABLE IF EXISTS `accounting_stock_inventory_lines`;
DROP TABLE IF EXISTS `accounting_stock_inventories`;
DROP TABLE IF EXISTS `accounting_stock_transfers`;
DROP TABLE IF EXISTS `accounting_stock_movements`;
DROP TABLE IF EXISTS `accounting_stock_batches`;
DROP TABLE IF EXISTS `accounting_stock_items`;
DROP TABLE IF EXISTS `accounting_stock_warehouses`;
DROP TABLE IF EXISTS `accounting_stock_units`;
DROP TABLE IF EXISTS `accounting_stock_subcategories`;
DROP TABLE IF EXISTS `accounting_stock_categories`;
DROP TABLE IF EXISTS `accounting_sales_representatives`;
DROP TABLE IF EXISTS `accounting_partners`;
DROP TABLE IF EXISTS `accounting_debtors`;
DROP TABLE IF EXISTS `accounting_creditors`;
DROP TABLE IF EXISTS `accounting_clients`;
CREATE TABLE `accounting_clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `profession` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `rccm` varchar(255) DEFAULT NULL,
  `id_nat` varchar(255) DEFAULT NULL,
  `nif` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `currency` varchar(3) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_clients_reference_unique` (`reference`),
  KEY `accounting_clients_company_site_id_type_index` (`company_site_id`,`type`),
  KEY `accounting_clients_company_site_id_name_index` (`company_site_id`,`name`),
  KEY `accounting_clients_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_clients_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_clients_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_client_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `accounting_client_id` bigint(20) unsigned NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_client_contacts_accounting_client_id_full_name_index` (`accounting_client_id`,`full_name`),
  CONSTRAINT `accounting_client_contacts_accounting_client_id_foreign` FOREIGN KEY (`accounting_client_id`) REFERENCES `accounting_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `profession` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `rccm` varchar(255) DEFAULT NULL,
  `id_nat` varchar(255) DEFAULT NULL,
  `nif` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `currency` varchar(3) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_suppliers_reference_unique` (`reference`),
  KEY `accounting_suppliers_company_site_id_type_index` (`company_site_id`,`type`),
  KEY `accounting_suppliers_company_site_id_name_index` (`company_site_id`,`name`),
  KEY `accounting_suppliers_company_site_id_status_index` (`company_site_id`,`status`),
  KEY `accounting_suppliers_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_suppliers_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_suppliers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_supplier_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `accounting_supplier_id` bigint(20) unsigned NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_supplier_contacts_supplier_name_idx` (`accounting_supplier_id`,`full_name`),
  CONSTRAINT `accounting_supplier_contacts_accounting_supplier_id_foreign` FOREIGN KEY (`accounting_supplier_id`) REFERENCES `accounting_suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_prospects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `converted_client_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `profession` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `rccm` varchar(255) DEFAULT NULL,
  `id_nat` varchar(255) DEFAULT NULL,
  `nif` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `source` varchar(50) NOT NULL DEFAULT 'other',
  `status` varchar(50) NOT NULL DEFAULT 'new',
  `interest_level` varchar(30) NOT NULL DEFAULT 'warm',
  `notes` text DEFAULT NULL,
  `converted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_prospects_reference_unique` (`reference`),
  KEY `accounting_prospects_company_site_id_type_index` (`company_site_id`,`type`),
  KEY `accounting_prospects_company_site_id_status_index` (`company_site_id`,`status`),
  KEY `accounting_prospects_company_site_id_interest_level_index` (`company_site_id`,`interest_level`),
  KEY `accounting_prospects_company_site_id_name_index` (`company_site_id`,`name`),
  KEY `accounting_prospects_created_by_foreign` (`created_by`),
  KEY `accounting_prospects_converted_client_id_foreign` (`converted_client_id`),
  CONSTRAINT `accounting_prospects_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_prospects_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_prospects_converted_client_id_foreign` FOREIGN KEY (`converted_client_id`) REFERENCES `accounting_clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_prospect_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `accounting_prospect_id` bigint(20) unsigned NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_prospect_contacts_prospect_name_idx` (`accounting_prospect_id`,`full_name`),
  CONSTRAINT `accounting_prospect_contacts_accounting_prospect_id_foreign` FOREIGN KEY (`accounting_prospect_id`) REFERENCES `accounting_prospects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_creditors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(40) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `currency` varchar(3) NOT NULL,
  `initial_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_creditors_reference_unique` (`reference`),
  KEY `accounting_creditors_company_site_id_type_index` (`company_site_id`,`type`),
  KEY `accounting_creditors_company_site_id_name_index` (`company_site_id`,`name`),
  KEY `accounting_creditors_company_site_id_status_index` (`company_site_id`,`status`),
  KEY `accounting_creditors_company_site_id_due_date_index` (`company_site_id`,`due_date`),
  KEY `accounting_creditors_company_site_id_priority_index` (`company_site_id`,`priority`),
  KEY `accounting_creditors_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_creditors_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_creditors_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_debtors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(40) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `currency` varchar(3) NOT NULL,
  `initial_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `received_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_debtors_reference_unique` (`reference`),
  KEY `accounting_debtors_company_site_id_type_index` (`company_site_id`,`type`),
  KEY `accounting_debtors_company_site_id_name_index` (`company_site_id`,`name`),
  KEY `accounting_debtors_company_site_id_status_index` (`company_site_id`,`status`),
  KEY `accounting_debtors_company_site_id_due_date_index` (`company_site_id`,`due_date`),
  KEY `accounting_debtors_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_debtors_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_debtors_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_partners` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(40) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `contact_position` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `activity_domain` varchar(255) DEFAULT NULL,
  `partnership_started_at` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_partners_reference_unique` (`reference`),
  KEY `accounting_partners_company_site_id_type_index` (`company_site_id`,`type`),
  KEY `accounting_partners_company_site_id_name_index` (`company_site_id`,`name`),
  KEY `accounting_partners_company_site_id_status_index` (`company_site_id`,`status`),
  KEY `accounting_partners_company_site_id_activity_domain_index` (`company_site_id`,`activity_domain`),
  KEY `accounting_partners_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_partners_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_partners_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_sales_representatives` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(40) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `sales_area` varchar(255) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'CDF',
  `monthly_target` decimal(15,2) NOT NULL DEFAULT 0.00,
  `annual_target` decimal(15,2) NOT NULL DEFAULT 0.00,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_sales_representatives_reference_unique` (`reference`),
  KEY `acct_sales_rep_site_type_idx` (`company_site_id`,`type`),
  KEY `acct_sales_rep_site_name_idx` (`company_site_id`,`name`),
  KEY `acct_sales_rep_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_sales_rep_site_area_idx` (`company_site_id`,`sales_area`),
  KEY `accounting_sales_representatives_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_sales_representatives_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_sales_representatives_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_stock_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_stock_categories_reference_unique` (`reference`),
  KEY `acct_stock_cat_site_name_idx` (`company_site_id`,`name`),
  KEY `acct_stock_cat_site_status_idx` (`company_site_id`,`status`),
  KEY `accounting_stock_categories_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_stock_categories_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_categories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_stock_subcategories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_stock_subcategories_reference_unique` (`reference`),
  KEY `acct_stock_subcat_site_cat_idx` (`company_site_id`,`category_id`),
  KEY `acct_stock_subcat_site_name_idx` (`company_site_id`,`name`),
  KEY `accounting_stock_subcategories_category_id_foreign` (`category_id`),
  KEY `accounting_stock_subcategories_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_stock_subcategories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `accounting_stock_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_subcategories_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_subcategories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_stock_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'unit',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_stock_units_reference_unique` (`reference`),
  KEY `acct_stock_unit_site_name_idx` (`company_site_id`,`name`),
  KEY `accounting_stock_units_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_stock_units_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_units_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_stock_warehouses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(60) DEFAULT NULL,
  `manager_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_stock_warehouses_reference_unique` (`reference`),
  KEY `acct_stock_wh_site_name_idx` (`company_site_id`,`name`),
  KEY `acct_stock_wh_site_status_idx` (`company_site_id`,`status`),
  KEY `accounting_stock_warehouses_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_stock_warehouses_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_warehouses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_stock_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `subcategory_id` bigint(20) unsigned DEFAULT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `default_warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'product',
  `purchase_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_stock` decimal(15,2) NOT NULL DEFAULT 0.00,
  `min_stock` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'CDF',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_stock_items_reference_unique` (`reference`),
  KEY `acct_stock_item_site_name_idx` (`company_site_id`,`name`),
  KEY `acct_stock_item_site_cat_idx` (`company_site_id`,`category_id`),
  KEY `acct_stock_item_site_status_idx` (`company_site_id`,`status`),
  KEY `accounting_stock_items_subcategory_id_foreign` (`subcategory_id`),
  KEY `accounting_stock_items_unit_id_foreign` (`unit_id`),
  KEY `accounting_stock_items_default_warehouse_id_foreign` (`default_warehouse_id`),
  KEY `accounting_stock_items_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_stock_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `accounting_stock_categories` (`id`),
  CONSTRAINT `accounting_stock_items_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_items_default_warehouse_id_foreign` FOREIGN KEY (`default_warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_items_subcategory_id_foreign` FOREIGN KEY (`subcategory_id`) REFERENCES `accounting_stock_subcategories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_items_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `accounting_stock_units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_stock_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `batch_number` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_stock_batches_reference_unique` (`reference`),
  KEY `acct_stock_batch_site_item_idx` (`company_site_id`,`item_id`),
  KEY `acct_stock_batch_wh_item_idx` (`warehouse_id`,`item_id`),
  KEY `accounting_stock_batches_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_stock_batches_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_batches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_batches_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_batches_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `movement_date` date DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_stock_movements_reference_unique` (`reference`),
  KEY `acct_stock_move_site_type_idx` (`company_site_id`,`type`),
  KEY `acct_stock_move_site_item_idx` (`company_site_id`,`item_id`),
  KEY `accounting_stock_movements_warehouse_id_foreign` (`warehouse_id`),
  KEY `accounting_stock_movements_batch_id_foreign` (`batch_id`),
  KEY `accounting_stock_movements_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_stock_movements_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `accounting_stock_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_movements_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_movements_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_movements_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_stock_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `from_warehouse_id` bigint(20) unsigned NOT NULL,
  `to_warehouse_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `transfer_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_stock_transfers_reference_unique` (`reference`),
  KEY `acct_stock_transfer_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_stock_transfer_site_item_idx` (`company_site_id`,`item_id`),
  KEY `accounting_stock_transfers_from_warehouse_id_foreign` (`from_warehouse_id`),
  KEY `accounting_stock_transfers_to_warehouse_id_foreign` (`to_warehouse_id`),
  KEY `accounting_stock_transfers_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_stock_transfers_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_transfers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_transfers_from_warehouse_id_foreign` FOREIGN KEY (`from_warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`),
  CONSTRAINT `accounting_stock_transfers_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_transfers_to_warehouse_id_foreign` FOREIGN KEY (`to_warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_stock_inventories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `counted_at` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_stock_inventories_reference_unique` (`reference`),
  KEY `acct_stock_inv_site_status_idx` (`company_site_id`,`status`),
  KEY `accounting_stock_inventories_warehouse_id_foreign` (`warehouse_id`),
  KEY `accounting_stock_inventories_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_stock_inventories_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_inventories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_inventories_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_stock_inventory_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `expected_quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `counted_quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `difference_quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_stock_inv_line_unique` (`inventory_id`,`item_id`),
  KEY `accounting_stock_inventory_lines_item_id_foreign` (`item_id`),
  CONSTRAINT `accounting_stock_inventory_lines_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `accounting_stock_inventories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_inventory_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounting_stock_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'low_stock',
  `threshold_quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_stock_alerts_reference_unique` (`reference`),
  KEY `acct_stock_alert_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_stock_alert_site_item_idx` (`company_site_id`,`item_id`),
  KEY `accounting_stock_alerts_warehouse_id_foreign` (`warehouse_id`),
  KEY `accounting_stock_alerts_created_by_foreign` (`created_by`),
  CONSTRAINT `accounting_stock_alerts_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_alerts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_alerts_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_alerts_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `company_user`;
CREATE TABLE `company_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 1,
  `can_create` tinyint(1) NOT NULL DEFAULT 0,
  `can_update` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_user_company_id_user_id_unique` (`company_id`,`user_id`),
  KEY `company_user_user_id_foreign` (`user_id`),
  CONSTRAINT `company_user_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `company_user` (`id`, `company_id`, `user_id`, `can_view`, `can_create`, `can_update`, `can_delete`, `created_at`, `updated_at`) VALUES ('1', '2', '4', '1', '0', '0', '0', '2026-04-25 21:08:54', '2026-04-25 21:49:13');
INSERT INTO `company_user` (`id`, `company_id`, `user_id`, `can_view`, `can_create`, `can_update`, `can_delete`, `created_at`, `updated_at`) VALUES ('2', '3', '9', '1', '1', '1', '1', '2026-04-26 20:03:55', '2026-04-26 20:03:55');
INSERT INTO `company_user` (`id`, `company_id`, `user_id`, `can_view`, `can_create`, `can_update`, `can_delete`, `created_at`, `updated_at`) VALUES ('3', '4', '3', '1', '1', '1', '1', '2026-04-28 10:19:49', '2026-04-28 10:19:49');
INSERT INTO `company_user` (`id`, `company_id`, `user_id`, `can_view`, `can_create`, `can_update`, `can_delete`, `created_at`, `updated_at`) VALUES ('5', '2', '3', '1', '1', '1', '1', '2026-04-28 14:28:07', '2026-04-28 14:28:07');

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('1', '0001_01_01_000000_create_users_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('2', '0001_01_01_000001_create_cache_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('3', '0001_01_01_000002_create_jobs_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('4', '2026_04_25_000001_create_erp_user_access_tables', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('5', '2026_04_25_183442_add_two_factor_columns_to_users_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('6', '2026_04_25_235959_add_plan_fields_to_subscriptions_table', '2');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('7', '2026_04_26_190617_update_companies_business_fields', '3');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('8', '2026_04_28_000001_create_company_sites_table', '4');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('9', '2026_04_28_000002_add_status_and_user_assignments_to_company_sites', '5');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('10', '2026_04_28_000003_update_pro_company_limit_to_two', '6');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('11', '2026_04_28_000004_add_permissions_to_company_site_user', '7');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('12', '2026_04_28_000005_create_user_login_histories_table', '8');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('13', '2026_04_29_000001_add_profile_photo_path_to_users_table', '9');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('14', '2026_04_30_000001_create_accounting_clients_tables', '10');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('15', '2026_04_30_000002_add_reference_to_accounting_clients_table', '11');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('16', '2026_04_30_000003_add_bank_and_currency_to_accounting_clients_table', '12');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('17', '2026_04_30_000004_create_accounting_suppliers_tables', '12');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('18', '2026_04_30_000005_create_accounting_prospects_tables', '13');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('19', '2026_04_30_000006_create_accounting_creditors_table', '14');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('20', '2026_04_30_000007_create_accounting_debtors_table', '15');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('21', '2026_04_30_000008_create_accounting_partners_table', '16');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('22', '2026_04_30_000009_create_accounting_sales_representatives_table', '17');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('23', '2026_04_30_000010_create_accounting_stock_tables', '18');

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES ('Lk1cdbqWtGKkYgyMchn2c9qevpU60i3lD29OEZqF', '5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiblhTMTJ5M0VrMjBBRlJVWUMzaU5sWkhZcGtEc0ZvMmxSZkJ4ZHBoZSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9jb21wYW5pZXMiO3M6NToicm91dGUiO3M6MTU6ImFkbWluLmNvbXBhbmllcyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjU7czo2OiJsb2NhbGUiO3M6MjoiZnIiO30=', '1777391868');
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES ('xshZNPEdNlwnExiJX13gaWPDYf1ouF9mWod3tvVN', '3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiMW51Y2hpMGtGUzl6elgxQ0JUTnZaS3FyUk1PRHZkV3RNRFVaeUx0UyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9tYWluL2NvbXBhbmllcy80L3NpdGVzIjtzOjU6InJvdXRlIjtzOjIwOiJtYWluLmNvbXBhbmllcy5zaXRlcyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjM7czo2OiJsb2NhbGUiO3M6MjoiZnIiO30=', '1777392020');

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'standard',
  `company_limit` int(10) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `expires_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriptions_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `subscriptions` (`id`, `name`, `code`, `type`, `company_limit`, `status`, `expires_at`, `created_at`, `updated_at`) VALUES ('2', 'EXAD', 'EXAD-DEFAULT', 'business', NULL, 'active', '2027-04-25', '2026-04-25 21:08:53', '2026-04-28 10:18:40');
INSERT INTO `subscriptions` (`id`, `name`, `code`, `type`, `company_limit`, `status`, `expires_at`, `created_at`, `updated_at`) VALUES ('3', 'Prestavice', 'PRESTAVICE', 'business', NULL, 'active', '2027-04-25', '2026-04-25 22:09:35', '2026-04-25 22:09:35');
INSERT INTO `subscriptions` (`id`, `name`, `code`, `type`, `company_limit`, `status`, `expires_at`, `created_at`, `updated_at`) VALUES ('4', 'Test Pro', 'TEST_PRO', 'pro', '2', 'active', '2027-04-25', '2026-04-25 22:12:51', '2026-04-25 22:47:34');
INSERT INTO `subscriptions` (`id`, `name`, `code`, `type`, `company_limit`, `status`, `expires_at`, `created_at`, `updated_at`) VALUES ('6', 'test4', 'TEST4', 'standard', '1', 'active', '2027-04-26', '2026-04-26 08:16:23', '2026-04-26 08:16:23');
INSERT INTO `subscriptions` (`id`, `name`, `code`, `type`, `company_limit`, `status`, `expires_at`, `created_at`, `updated_at`) VALUES ('7', 'test5', 'TEST5', 'standard', '1', 'active', '2027-04-26', '2026-04-26 08:16:29', '2026-04-26 08:16:29');
INSERT INTO `subscriptions` (`id`, `name`, `code`, `type`, `company_limit`, `status`, `expires_at`, `created_at`, `updated_at`) VALUES ('8', 'test6', 'TEST6', 'standard', '1', 'active', '2027-04-26', '2026-04-26 08:17:00', '2026-04-26 08:17:00');
INSERT INTO `subscriptions` (`id`, `name`, `code`, `type`, `company_limit`, `status`, `expires_at`, `created_at`, `updated_at`) VALUES ('11', 'Test 8', 'TEST_8', 'standard', '1', 'active', '2025-06-26', '2026-04-26 09:12:36', '2026-04-26 09:12:36');
INSERT INTO `subscriptions` (`id`, `name`, `code`, `type`, `company_limit`, `status`, `expires_at`, `created_at`, `updated_at`) VALUES ('12', 'Test 9', 'TEST_9', 'pro', '2', 'expired', '2025-05-26', '2026-04-26 09:20:37', '2026-04-26 09:20:37');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'user',
  `address` text DEFAULT NULL,
  `phone_number` varchar(32) DEFAULT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_subscription_id_foreign` (`subscription_id`),
  CONSTRAINT `users_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `subscription_id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `address`, `phone_number`, `grade`, `profile_photo_path`, `remember_token`, `created_at`, `updated_at`) VALUES ('3', '2', 'admin', 'admin@erp.loc', NULL, '$2y$12$SNpTPsgEa0Y8Z7Wiy/nd..kdXlizk06/ZaRGQKqw.UbMely6KMX/W', NULL, NULL, NULL, 'admin', NULL, NULL, NULL, NULL, 'mH2I6cSkrwebAT0oY8ZMQ6hLjWhqRy4U4JerWCbvkBwnggCsI9T5rTaAPq2j', '2026-04-25 21:08:53', '2026-04-25 21:49:12');
INSERT INTO `users` (`id`, `subscription_id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `address`, `phone_number`, `grade`, `profile_photo_path`, `remember_token`, `created_at`, `updated_at`) VALUES ('4', '2', 'user1', 'user1@erp.loc', NULL, '$2y$12$uvxmnRyY9pYsXfg7CPX8meKR73LCDXm5eRStSphqI4juy4Dk7iJgO', NULL, NULL, NULL, 'user', NULL, NULL, NULL, NULL, NULL, '2026-04-25 21:08:54', '2026-04-25 21:49:12');
INSERT INTO `users` (`id`, `subscription_id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `address`, `phone_number`, `grade`, `profile_photo_path`, `remember_token`, `created_at`, `updated_at`) VALUES ('5', NULL, 'superadmin', 'superadmin@erp.loc', NULL, '$2y$12$sIYrLZrjHbGknvfL9w5E2.8Es2glSsJQKtrG6vDWMjLUKuhHbwvri', NULL, NULL, NULL, 'superadmin', NULL, NULL, NULL, NULL, 'mHgfZwv3TEsEQFrPaqDhZcksFQ3kQ9viEvc2Qa5pfnAYWtmVvGnb5AiteRuI', '2026-04-25 21:08:54', '2026-04-25 21:49:13');
INSERT INTO `users` (`id`, `subscription_id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `address`, `phone_number`, `grade`, `profile_photo_path`, `remember_token`, `created_at`, `updated_at`) VALUES ('6', '11', 'admin1', 'admin1@erp.loc', NULL, '$2y$12$O.c1xuOOohV2Mz/l77y.0egBNVwyuURvoBJRnMZ1LgYbCy5ZF/xQK', NULL, NULL, NULL, 'admin', NULL, NULL, NULL, NULL, NULL, '2026-04-26 09:15:52', '2026-04-26 09:15:52');
INSERT INTO `users` (`id`, `subscription_id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `address`, `phone_number`, `grade`, `profile_photo_path`, `remember_token`, `created_at`, `updated_at`) VALUES ('7', '11', 'user2', 'user2@erp.loc', NULL, '$2y$12$nNZrX0dxIw5/aWK9hwu3R.tS6MiMq0s2K0fbLIof8qv8WTPgHNsUK', NULL, NULL, NULL, 'user', NULL, NULL, NULL, NULL, NULL, '2026-04-26 17:24:39', '2026-04-26 17:24:39');
INSERT INTO `users` (`id`, `subscription_id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `address`, `phone_number`, `grade`, `profile_photo_path`, `remember_token`, `created_at`, `updated_at`) VALUES ('9', '4', 'Test pro user', 'testprouser@erp.loc', NULL, '$2y$12$5p4d0ATu4vs6xnfAk0FGtujvCenJb8pr.4bjnrxW.q.khUl7QSfKO', NULL, NULL, NULL, 'admin', NULL, NULL, NULL, NULL, NULL, '2026-04-26 20:02:55', '2026-04-26 20:02:55');
INSERT INTO `users` (`id`, `subscription_id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `address`, `phone_number`, `grade`, `profile_photo_path`, `remember_token`, `created_at`, `updated_at`) VALUES ('10', '2', 'userexad', 'userexad@erp.loc', NULL, '$2y$12$a3jdMF0gtwkzWn5.JDHNOOwl0oFsAybn7Lq57MmrfQyBIoY.mgGK6', NULL, NULL, NULL, 'user', NULL, NULL, NULL, NULL, NULL, '2026-04-28 14:25:23', '2026-04-28 14:25:23');

DROP TABLE IF EXISTS `user_login_histories`;
CREATE TABLE `user_login_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `device` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `logged_in_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_login_histories_user_id_logged_in_at_index` (`user_id`,`logged_in_at`),
  CONSTRAINT `user_login_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
