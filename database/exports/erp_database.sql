-- EXAD ERP database export
-- Generated: 2026-04-28 18:05:24 +02:00
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
  `company_site_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_subscription_id_foreign` (`subscription_id`),
  CONSTRAINT `users_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `subscription_id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `address`, `phone_number`, `grade`, `remember_token`, `created_at`, `updated_at`) VALUES ('3', '2', 'admin', 'admin@erp.loc', NULL, '$2y$12$SNpTPsgEa0Y8Z7Wiy/nd..kdXlizk06/ZaRGQKqw.UbMely6KMX/W', NULL, NULL, NULL, 'admin', NULL, NULL, NULL, 'mH2I6cSkrwebAT0oY8ZMQ6hLjWhqRy4U4JerWCbvkBwnggCsI9T5rTaAPq2j', '2026-04-25 21:08:53', '2026-04-25 21:49:12');
INSERT INTO `users` (`id`, `subscription_id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `address`, `phone_number`, `grade`, `remember_token`, `created_at`, `updated_at`) VALUES ('4', '2', 'user1', 'user1@erp.loc', NULL, '$2y$12$uvxmnRyY9pYsXfg7CPX8meKR73LCDXm5eRStSphqI4juy4Dk7iJgO', NULL, NULL, NULL, 'user', NULL, NULL, NULL, NULL, '2026-04-25 21:08:54', '2026-04-25 21:49:12');
INSERT INTO `users` (`id`, `subscription_id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `address`, `phone_number`, `grade`, `remember_token`, `created_at`, `updated_at`) VALUES ('5', NULL, 'superadmin', 'superadmin@erp.loc', NULL, '$2y$12$sIYrLZrjHbGknvfL9w5E2.8Es2glSsJQKtrG6vDWMjLUKuhHbwvri', NULL, NULL, NULL, 'superadmin', NULL, NULL, NULL, 'mHgfZwv3TEsEQFrPaqDhZcksFQ3kQ9viEvc2Qa5pfnAYWtmVvGnb5AiteRuI', '2026-04-25 21:08:54', '2026-04-25 21:49:13');
INSERT INTO `users` (`id`, `subscription_id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `address`, `phone_number`, `grade`, `remember_token`, `created_at`, `updated_at`) VALUES ('6', '11', 'admin1', 'admin1@erp.loc', NULL, '$2y$12$O.c1xuOOohV2Mz/l77y.0egBNVwyuURvoBJRnMZ1LgYbCy5ZF/xQK', NULL, NULL, NULL, 'admin', NULL, NULL, NULL, NULL, '2026-04-26 09:15:52', '2026-04-26 09:15:52');
INSERT INTO `users` (`id`, `subscription_id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `address`, `phone_number`, `grade`, `remember_token`, `created_at`, `updated_at`) VALUES ('7', '11', 'user2', 'user2@erp.loc', NULL, '$2y$12$nNZrX0dxIw5/aWK9hwu3R.tS6MiMq0s2K0fbLIof8qv8WTPgHNsUK', NULL, NULL, NULL, 'user', NULL, NULL, NULL, NULL, '2026-04-26 17:24:39', '2026-04-26 17:24:39');
INSERT INTO `users` (`id`, `subscription_id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `address`, `phone_number`, `grade`, `remember_token`, `created_at`, `updated_at`) VALUES ('9', '4', 'Test pro user', 'testprouser@erp.loc', NULL, '$2y$12$5p4d0ATu4vs6xnfAk0FGtujvCenJb8pr.4bjnrxW.q.khUl7QSfKO', NULL, NULL, NULL, 'admin', NULL, NULL, NULL, NULL, '2026-04-26 20:02:55', '2026-04-26 20:02:55');
INSERT INTO `users` (`id`, `subscription_id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `role`, `address`, `phone_number`, `grade`, `remember_token`, `created_at`, `updated_at`) VALUES ('10', '2', 'userexad', 'userexad@erp.loc', NULL, '$2y$12$a3jdMF0gtwkzWn5.JDHNOOwl0oFsAybn7Lq57MmrfQyBIoY.mgGK6', NULL, NULL, NULL, 'user', NULL, NULL, NULL, NULL, '2026-04-28 14:25:23', '2026-04-28 14:25:23');

SET FOREIGN_KEY_CHECKS = 1;
