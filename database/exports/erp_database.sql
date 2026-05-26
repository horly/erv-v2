-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: erp_database
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `erp_database`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `erp_database` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `erp_database`;

--
-- Table structure for table `accounting_bank_reconciliation_matches`
--

DROP TABLE IF EXISTS `accounting_bank_reconciliation_matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_bank_reconciliation_matches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `statement_line_id` bigint(20) unsigned NOT NULL,
  `treasury_movement_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `matched_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_bank_match_unique_movement` (`treasury_movement_id`),
  KEY `accounting_bank_reconciliation_matches_created_by_foreign` (`created_by`),
  KEY `acct_bank_match_line_idx` (`statement_line_id`),
  CONSTRAINT `accounting_bank_reconciliation_matches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_bank_reconciliation_matches_statement_line_id_foreign` FOREIGN KEY (`statement_line_id`) REFERENCES `accounting_bank_statement_lines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_bank_match_movement_fk` FOREIGN KEY (`treasury_movement_id`) REFERENCES `accounting_treasury_movements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_bank_reconciliation_matches`
--

LOCK TABLES `accounting_bank_reconciliation_matches` WRITE;
/*!40000 ALTER TABLE `accounting_bank_reconciliation_matches` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_bank_reconciliation_matches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_bank_reconciliations`
--

DROP TABLE IF EXISTS `accounting_bank_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_bank_reconciliations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `statement_opening_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `statement_closing_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `erp_closing_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `difference` decimal(18,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL,
  `status` varchar(25) NOT NULL DEFAULT 'in_progress',
  `notes` text DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_bank_reconciliations_reference_unique` (`reference`),
  KEY `accounting_bank_reconciliations_created_by_foreign` (`created_by`),
  KEY `accounting_bank_reconciliations_closed_by_foreign` (`closed_by`),
  KEY `acct_bank_rec_site_period_idx` (`company_site_id`,`period_end`),
  KEY `acct_bank_rec_method_status_idx` (`payment_method_id`,`status`),
  CONSTRAINT `accounting_bank_reconciliations_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_bank_reconciliations_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_bank_reconciliations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_bank_reconciliations_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `accounting_payment_methods` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_bank_reconciliations`
--

LOCK TABLES `accounting_bank_reconciliations` WRITE;
/*!40000 ALTER TABLE `accounting_bank_reconciliations` DISABLE KEYS */;
INSERT INTO `accounting_bank_reconciliations` VALUES (1,'RAP-000001',1,2,3,NULL,'2026-05-01','2026-05-25',1000.00,1000.00,1392.00,-392.00,'USD','in_progress',NULL,NULL,'2026-05-25 12:24:36','2026-05-25 12:24:36');
/*!40000 ALTER TABLE `accounting_bank_reconciliations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_bank_statement_lines`
--

DROP TABLE IF EXISTS `accounting_bank_statement_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_bank_statement_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bank_reconciliation_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `value_date` date DEFAULT NULL,
  `bank_reference` varchar(255) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `direction` varchar(20) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'unmatched',
  `import_batch` varchar(60) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_bank_statement_lines_created_by_foreign` (`created_by`),
  KEY `acct_bank_line_rec_status_idx` (`bank_reconciliation_id`,`status`),
  KEY `acct_bank_line_rec_date_idx` (`bank_reconciliation_id`,`transaction_date`),
  CONSTRAINT `accounting_bank_statement_lines_bank_reconciliation_id_foreign` FOREIGN KEY (`bank_reconciliation_id`) REFERENCES `accounting_bank_reconciliations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_bank_statement_lines_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_bank_statement_lines`
--

LOCK TABLES `accounting_bank_statement_lines` WRITE;
/*!40000 ALTER TABLE `accounting_bank_statement_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_bank_statement_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_cash_register_sessions`
--

DROP TABLE IF EXISTS `accounting_cash_register_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_cash_register_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `opened_by` bigint(20) unsigned DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `closure_validated_by` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `opening_float` decimal(18,2) NOT NULL DEFAULT 0.00,
  `opened_at` datetime NOT NULL,
  `closed_at` datetime DEFAULT NULL,
  `expected_cash_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `expected_other_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `expected_total_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `counted_cash_amount` decimal(18,2) DEFAULT NULL,
  `counted_other_amount` decimal(18,2) DEFAULT NULL,
  `counted_total_amount` decimal(18,2) DEFAULT NULL,
  `difference_amount` decimal(18,2) DEFAULT NULL,
  `opening_notes` text DEFAULT NULL,
  `closing_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_cash_register_sessions_reference_unique` (`reference`),
  KEY `accounting_cash_register_sessions_opened_by_foreign` (`opened_by`),
  KEY `accounting_cash_register_sessions_closed_by_foreign` (`closed_by`),
  KEY `accounting_cash_register_sessions_closure_validated_by_foreign` (`closure_validated_by`),
  KEY `acct_cash_session_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_cash_session_site_opened_idx` (`company_site_id`,`opened_at`),
  CONSTRAINT `accounting_cash_register_sessions_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_cash_register_sessions_closure_validated_by_foreign` FOREIGN KEY (`closure_validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_cash_register_sessions_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_cash_register_sessions_opened_by_foreign` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_cash_register_sessions`
--

LOCK TABLES `accounting_cash_register_sessions` WRITE;
/*!40000 ALTER TABLE `accounting_cash_register_sessions` DISABLE KEYS */;
INSERT INTO `accounting_cash_register_sessions` VALUES (1,'CAI-000001',1,3,3,3,'closed',0.00,'2026-05-08 15:33:11','2026-05-08 15:36:00',1809.60,0.00,1809.60,1809.60,0.00,1809.60,0.00,NULL,NULL,'2026-05-08 14:33:11','2026-05-08 14:36:00'),(2,'CAI-000002',1,3,3,3,'closed',0.00,'2026-05-08 15:42:04','2026-05-08 17:58:59',1740.00,0.00,1740.00,1740.00,0.00,1740.00,0.00,NULL,NULL,'2026-05-08 14:42:04','2026-05-08 16:58:59');
/*!40000 ALTER TABLE `accounting_cash_register_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_client_contacts`
--

DROP TABLE IF EXISTS `accounting_client_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_client_contacts`
--

LOCK TABLES `accounting_client_contacts` WRITE;
/*!40000 ALTER TABLE `accounting_client_contacts` DISABLE KEYS */;
INSERT INTO `accounting_client_contacts` VALUES (6,1,'Ralph Bekonda','Chef de Service','Network','Ralphbekonda@orange.cd',NULL,'2026-05-06 07:32:29','2026-05-06 07:32:29'),(7,1,'Cosmos',NULL,NULL,'cosmosbisimwa@orange.cd',NULL,'2026-05-06 07:32:29','2026-05-06 07:32:29');
/*!40000 ALTER TABLE `accounting_client_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_clients`
--

DROP TABLE IF EXISTS `accounting_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `account_number` varchar(255) DEFAULT NULL,
  `currency` varchar(3) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_clients_reference_unique` (`reference`),
  KEY `accounting_clients_created_by_foreign` (`created_by`),
  KEY `accounting_clients_company_site_id_type_index` (`company_site_id`,`type`),
  KEY `accounting_clients_company_site_id_name_index` (`company_site_id`,`name`),
  CONSTRAINT `accounting_clients_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_clients_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_clients`
--

LOCK TABLES `accounting_clients` WRITE;
/*!40000 ALTER TABLE `accounting_clients` DISABLE KEYS */;
INSERT INTO `accounting_clients` VALUES (1,'CLT-000001',1,3,'company','Orange RDC',NULL,NULL,NULL,'Kinshasa-Gombe',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-30 10:47:14','2026-05-06 07:32:29'),(2,'CLT-000002',1,3,'individual','José Mambu','Architecte',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-30 10:47:58','2026-04-30 10:47:58'),(4,'CLT-000004',1,3,'company','Vodacom DRC',NULL,NULL,NULL,NULL,'142425','25423','5244',NULL,NULL,NULL,NULL,'2026-04-30 11:02:40','2026-04-30 11:02:40'),(5,'CLT-000005',1,3,'individual','Client comptoir',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-08 11:40:27','2026-05-08 11:40:27');
/*!40000 ALTER TABLE `accounting_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_credit_note_lines`
--

DROP TABLE IF EXISTS `accounting_credit_note_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_credit_note_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `credit_note_id` bigint(20) unsigned NOT NULL,
  `sales_invoice_line_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `quantity` decimal(18,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(18,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_credit_note_lines_sales_invoice_line_id_foreign` (`sales_invoice_line_id`),
  KEY `acct_credit_note_line_invoice_line_idx` (`credit_note_id`,`sales_invoice_line_id`),
  CONSTRAINT `accounting_credit_note_lines_credit_note_id_foreign` FOREIGN KEY (`credit_note_id`) REFERENCES `accounting_credit_notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_credit_note_lines_sales_invoice_line_id_foreign` FOREIGN KEY (`sales_invoice_line_id`) REFERENCES `accounting_sales_invoice_lines` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_credit_note_lines`
--

LOCK TABLES `accounting_credit_note_lines` WRITE;
/*!40000 ALTER TABLE `accounting_credit_note_lines` DISABLE KEYS */;
INSERT INTO `accounting_credit_note_lines` VALUES (1,1,10,'Fortigate 40F','ART-000002',1.00,500.00,500.00,'2026-05-12 10:55:04','2026-05-12 10:55:04');
/*!40000 ALTER TABLE `accounting_credit_note_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_credit_notes`
--

DROP TABLE IF EXISTS `accounting_credit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_credit_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `credit_date` date NOT NULL,
  `currency` varchar(3) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `reason` text DEFAULT NULL,
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_ttc` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_credit_notes_reference_unique` (`reference`),
  KEY `accounting_credit_notes_client_id_foreign` (`client_id`),
  KEY `accounting_credit_notes_created_by_foreign` (`created_by`),
  KEY `acct_credit_note_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_credit_note_site_date_idx` (`company_site_id`,`credit_date`),
  KEY `acct_credit_note_invoice_status_idx` (`sales_invoice_id`,`status`),
  CONSTRAINT `accounting_credit_notes_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `accounting_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_credit_notes_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_credit_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_credit_notes_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `accounting_sales_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_credit_notes`
--

LOCK TABLES `accounting_credit_notes` WRITE;
/*!40000 ALTER TABLE `accounting_credit_notes` DISABLE KEYS */;
INSERT INTO `accounting_credit_notes` VALUES (1,'AVR-000001',1,7,5,3,'2026-05-12','USD','validated',NULL,500.00,16.00,80.00,580.00,'2026-05-12 10:55:04','2026-05-12 10:55:04');
/*!40000 ALTER TABLE `accounting_credit_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_creditor_payments`
--

DROP TABLE IF EXISTS `accounting_creditor_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_creditor_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `creditor_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned NOT NULL,
  `paid_by` bigint(20) unsigned DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_creditor_payments_paid_by_foreign` (`paid_by`),
  KEY `accounting_creditor_payments_creditor_id_payment_date_index` (`creditor_id`,`payment_date`),
  KEY `acp_creditor_date_idx` (`creditor_id`,`payment_date`),
  KEY `acp_method_date_idx` (`payment_method_id`,`payment_date`),
  CONSTRAINT `accounting_creditor_payments_creditor_id_foreign` FOREIGN KEY (`creditor_id`) REFERENCES `accounting_creditors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_creditor_payments_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_creditor_payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `accounting_payment_methods` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_creditor_payments`
--

LOCK TABLES `accounting_creditor_payments` WRITE;
/*!40000 ALTER TABLE `accounting_creditor_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_creditor_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_creditors`
--

DROP TABLE IF EXISTS `accounting_creditors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `accounting_creditors_created_by_foreign` (`created_by`),
  KEY `accounting_creditors_company_site_id_type_index` (`company_site_id`,`type`),
  KEY `accounting_creditors_company_site_id_name_index` (`company_site_id`,`name`),
  KEY `accounting_creditors_company_site_id_status_index` (`company_site_id`,`status`),
  KEY `accounting_creditors_company_site_id_due_date_index` (`company_site_id`,`due_date`),
  KEY `accounting_creditors_company_site_id_priority_index` (`company_site_id`,`priority`),
  CONSTRAINT `accounting_creditors_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_creditors_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_creditors`
--

LOCK TABLES `accounting_creditors` WRITE;
/*!40000 ALTER TABLE `accounting_creditors` DISABLE KEYS */;
INSERT INTO `accounting_creditors` VALUES (1,'CRE-000001',1,3,'supplier','Adefisa atala te',NULL,NULL,NULL,'USD',100.00,0.00,'2026-04-30',NULL,'normal','active','2026-04-30 13:25:21','2026-04-30 13:35:53'),(2,'CRE-000002',1,3,'bank','Equity',NULL,NULL,NULL,'USD',250.00,0.00,NULL,NULL,'normal','active','2026-05-14 11:44:30','2026-05-14 12:02:34'),(4,'CRE-000004',1,3,'bank','Equity',NULL,NULL,NULL,'USD',120.00,0.00,NULL,NULL,'normal','active','2026-05-14 12:08:37','2026-05-14 12:08:37');
/*!40000 ALTER TABLE `accounting_creditors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_currencies`
--

DROP TABLE IF EXISTS `accounting_currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_currencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(3) NOT NULL,
  `name` varchar(255) NOT NULL,
  `symbol` varchar(20) DEFAULT NULL,
  `exchange_rate` decimal(18,6) NOT NULL DEFAULT 1.000000,
  `is_base` tinyint(1) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_currency_site_code_unique` (`company_site_id`,`code`),
  UNIQUE KEY `accounting_currencies_reference_unique` (`reference`),
  KEY `accounting_currencies_created_by_foreign` (`created_by`),
  KEY `acct_currency_site_base_idx` (`company_site_id`,`is_base`),
  KEY `acct_currency_site_default_idx` (`company_site_id`,`is_default`),
  CONSTRAINT `accounting_currencies_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_currencies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_currencies`
--

LOCK TABLES `accounting_currencies` WRITE;
/*!40000 ALTER TABLE `accounting_currencies` DISABLE KEYS */;
INSERT INTO `accounting_currencies` VALUES (1,'CUR-000001',1,3,'USD','Dollar américain','$',1.000000,1,1,'active','2026-04-30 20:34:23','2026-04-30 20:34:23'),(2,'CUR-000002',1,3,'CDF','Franc congolais','FC',2320.000000,0,0,'active','2026-05-01 05:18:28','2026-05-01 05:18:28');
/*!40000 ALTER TABLE `accounting_currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_customer_order_lines`
--

DROP TABLE IF EXISTS `accounting_customer_order_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_customer_order_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_order_id` bigint(20) unsigned NOT NULL,
  `line_type` varchar(30) NOT NULL,
  `item_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `quantity` decimal(18,2) NOT NULL DEFAULT 1.00,
  `cost_price` decimal(18,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(18,2) NOT NULL DEFAULT 0.00,
  `margin_type` varchar(20) NOT NULL DEFAULT 'fixed',
  `margin_value` decimal(18,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(20) NOT NULL DEFAULT 'fixed',
  `discount_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cost_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `margin_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_customer_order_lines_item_id_foreign` (`item_id`),
  KEY `accounting_customer_order_lines_service_id_foreign` (`service_id`),
  KEY `acct_customer_order_line_type_idx` (`customer_order_id`,`line_type`),
  CONSTRAINT `accounting_customer_order_lines_customer_order_id_foreign` FOREIGN KEY (`customer_order_id`) REFERENCES `accounting_customer_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_customer_order_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_customer_order_lines_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `accounting_services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_customer_order_lines`
--

LOCK TABLES `accounting_customer_order_lines` WRITE;
/*!40000 ALTER TABLE `accounting_customer_order_lines` DISABLE KEYS */;
INSERT INTO `accounting_customer_order_lines` VALUES (1,1,'item',3,NULL,'Serveur HP Proliant380 gen10',NULL,1.00,0.00,6000.00,'percent',0.00,'fixed',0.00,0.00,6000.00,6000.00,'2026-05-07 08:01:50','2026-05-07 08:01:50'),(2,2,'item',2,NULL,'Fortigate 40F',NULL,1.00,300.00,390.00,'percent',30.00,'fixed',0.00,300.00,90.00,390.00,'2026-05-07 08:06:12','2026-05-07 08:06:12'),(3,2,'item',1,NULL,'Nexus 9300K',NULL,1.00,5000.00,7000.00,'percent',40.00,'fixed',0.00,5000.00,2000.00,7000.00,'2026-05-07 08:06:12','2026-05-07 08:06:12'),(5,4,'item',4,NULL,'AMD Ryzen 9 9950X - 16 cœurs / 32 threads, socket AM5, orienté calcul et création',NULL,2.00,780.00,780.00,'fixed',0.00,'fixed',0.00,1560.00,0.00,1560.00,'2026-05-07 13:35:02','2026-05-07 13:35:02'),(6,4,'item',11,NULL,'ASUS ROG Strix / ASUS TUF GeForce RTX 4090 24 Go GDDR6X - modèle à confirmer selon stock et compatibilité exacte du waterblock',NULL,1.00,1440.00,1440.00,'fixed',0.00,'fixed',0.00,1440.00,0.00,1440.00,'2026-05-07 13:35:02','2026-05-07 13:35:02'),(7,3,'item',2,NULL,'Fortigate 40F',NULL,1.00,300.00,500.00,'fixed',200.00,'fixed',0.00,300.00,200.00,500.00,'2026-05-07 17:53:40','2026-05-07 17:53:40'),(8,3,'item',11,NULL,'ASUS ROG Strix / ASUS TUF GeForce RTX 4090 24 Go GDDR6X - modèle à confirmer selon stock et compatibilité exacte du waterblock',NULL,1.00,1440.00,1440.00,'fixed',0.00,'fixed',0.00,1440.00,0.00,1440.00,'2026-05-07 17:53:40','2026-05-07 17:53:40');
/*!40000 ALTER TABLE `accounting_customer_order_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_customer_orders`
--

DROP TABLE IF EXISTS `accounting_customer_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_customer_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `proforma_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `currency` varchar(3) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `payment_terms` varchar(30) DEFAULT NULL,
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cost_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `margin_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `margin_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_ht` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_ttc` decimal(18,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_customer_orders_reference_unique` (`reference`),
  KEY `accounting_customer_orders_client_id_foreign` (`client_id`),
  KEY `accounting_customer_orders_proforma_invoice_id_foreign` (`proforma_invoice_id`),
  KEY `accounting_customer_orders_created_by_foreign` (`created_by`),
  KEY `acct_customer_order_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_customer_order_site_date_idx` (`company_site_id`,`order_date`),
  KEY `acct_customer_order_site_client_idx` (`company_site_id`,`client_id`),
  CONSTRAINT `accounting_customer_orders_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `accounting_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_customer_orders_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_customer_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_customer_orders_proforma_invoice_id_foreign` FOREIGN KEY (`proforma_invoice_id`) REFERENCES `accounting_proforma_invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_customer_orders`
--

LOCK TABLES `accounting_customer_orders` WRITE;
/*!40000 ALTER TABLE `accounting_customer_orders` DISABLE KEYS */;
INSERT INTO `accounting_customer_orders` VALUES (1,'CMD-000001',1,1,NULL,3,NULL,'2026-05-07',NULL,'USD','delivered','to_discuss',6000.00,0.00,6000.00,0.00,0.00,6000.00,16.00,960.00,6960.00,NULL,NULL,'2026-05-07 08:01:49','2026-05-07 13:03:20'),(2,'CMD-000002',1,4,NULL,3,NULL,'2026-05-07',NULL,'USD','draft','to_discuss',7390.00,5300.00,2090.00,39.43,0.00,7390.00,16.00,1182.40,8572.40,NULL,NULL,'2026-05-07 08:06:12','2026-05-07 08:06:12'),(3,'CMD-000003',1,2,2,3,NULL,'2026-05-07','2026-05-07','USD','confirmed','to_discuss',1940.00,1740.00,200.00,11.49,0.00,1940.00,16.00,310.40,2250.40,NULL,NULL,'2026-05-07 08:32:50','2026-05-07 17:53:40'),(4,'CMD-000004',1,1,4,3,NULL,'2026-05-07','2026-05-07','USD','delivered','to_discuss',3000.00,3000.00,0.00,0.00,0.00,3000.00,16.00,480.00,3480.00,NULL,NULL,'2026-05-07 13:35:02','2026-05-07 13:46:52');
/*!40000 ALTER TABLE `accounting_customer_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_debtor_payments`
--

DROP TABLE IF EXISTS `accounting_debtor_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_debtor_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `debtor_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned NOT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_debtor_payments_received_by_foreign` (`received_by`),
  KEY `adp_debtor_date_idx` (`debtor_id`,`payment_date`),
  KEY `adp_method_date_idx` (`payment_method_id`,`payment_date`),
  CONSTRAINT `accounting_debtor_payments_debtor_id_foreign` FOREIGN KEY (`debtor_id`) REFERENCES `accounting_debtors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_debtor_payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `accounting_payment_methods` (`id`),
  CONSTRAINT `accounting_debtor_payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_debtor_payments`
--

LOCK TABLES `accounting_debtor_payments` WRITE;
/*!40000 ALTER TABLE `accounting_debtor_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_debtor_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_debtors`
--

DROP TABLE IF EXISTS `accounting_debtors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `accounting_debtors_created_by_foreign` (`created_by`),
  KEY `accounting_debtors_company_site_id_type_index` (`company_site_id`,`type`),
  KEY `accounting_debtors_company_site_id_name_index` (`company_site_id`,`name`),
  KEY `accounting_debtors_company_site_id_status_index` (`company_site_id`,`status`),
  KEY `accounting_debtors_company_site_id_due_date_index` (`company_site_id`,`due_date`),
  CONSTRAINT `accounting_debtors_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_debtors_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_debtors`
--

LOCK TABLES `accounting_debtors` WRITE;
/*!40000 ALTER TABLE `accounting_debtors` DISABLE KEYS */;
INSERT INTO `accounting_debtors` VALUES (1,'DEB-000001',1,3,'employee','Adefa atala te',NULL,NULL,NULL,'USD',200.00,0.00,NULL,NULL,'active','2026-04-30 13:41:35','2026-04-30 13:41:35');
/*!40000 ALTER TABLE `accounting_debtors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_delivery_note_lines`
--

DROP TABLE IF EXISTS `accounting_delivery_note_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_delivery_note_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_note_id` bigint(20) unsigned NOT NULL,
  `customer_order_line_id` bigint(20) unsigned DEFAULT NULL,
  `line_type` varchar(30) NOT NULL,
  `item_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ordered_quantity` decimal(18,2) NOT NULL DEFAULT 0.00,
  `already_delivered_quantity` decimal(18,2) NOT NULL DEFAULT 0.00,
  `quantity` decimal(18,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(18,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_delivery_note_lines_item_id_foreign` (`item_id`),
  KEY `accounting_delivery_note_lines_service_id_foreign` (`service_id`),
  KEY `acct_delivery_note_line_type_idx` (`delivery_note_id`,`line_type`),
  KEY `acct_delivery_note_line_order_line_idx` (`customer_order_line_id`),
  CONSTRAINT `accounting_delivery_note_lines_customer_order_line_id_foreign` FOREIGN KEY (`customer_order_line_id`) REFERENCES `accounting_customer_order_lines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_delivery_note_lines_delivery_note_id_foreign` FOREIGN KEY (`delivery_note_id`) REFERENCES `accounting_delivery_notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_delivery_note_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_delivery_note_lines_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `accounting_services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_delivery_note_lines`
--

LOCK TABLES `accounting_delivery_note_lines` WRITE;
/*!40000 ALTER TABLE `accounting_delivery_note_lines` DISABLE KEYS */;
INSERT INTO `accounting_delivery_note_lines` VALUES (4,4,1,'item',3,NULL,'Serveur HP Proliant380 gen10',NULL,1.00,0.00,1.00,6000.00,6000.00,'2026-05-07 13:03:20','2026-05-07 13:03:20'),(7,6,5,'item',4,NULL,'AMD Ryzen 9 9950X - 16 cœurs / 32 threads, socket AM5, orienté calcul et création',NULL,2.00,0.00,2.00,780.00,1560.00,'2026-05-07 13:46:52','2026-05-07 13:46:52'),(8,6,6,'item',11,NULL,'ASUS ROG Strix / ASUS TUF GeForce RTX 4090 24 Go GDDR6X - modèle à confirmer selon stock et compatibilité exacte du waterblock',NULL,1.00,0.00,1.00,1440.00,1440.00,'2026-05-07 13:46:52','2026-05-07 13:46:52');
/*!40000 ALTER TABLE `accounting_delivery_note_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_delivery_note_serials`
--

DROP TABLE IF EXISTS `accounting_delivery_note_serials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_delivery_note_serials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_note_line_id` bigint(20) unsigned NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `position` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_delivery_serial_line_unique` (`delivery_note_line_id`,`serial_number`),
  KEY `acct_delivery_serial_line_position_idx` (`delivery_note_line_id`,`position`),
  CONSTRAINT `accounting_delivery_note_serials_delivery_note_line_id_foreign` FOREIGN KEY (`delivery_note_line_id`) REFERENCES `accounting_delivery_note_lines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_delivery_note_serials`
--

LOCK TABLES `accounting_delivery_note_serials` WRITE;
/*!40000 ALTER TABLE `accounting_delivery_note_serials` DISABLE KEYS */;
INSERT INTO `accounting_delivery_note_serials` VALUES (4,7,'SK93876464646',1,'2026-05-07 13:46:52','2026-05-07 13:46:52'),(5,7,'SK83838939399',2,'2026-05-07 13:46:52','2026-05-07 13:46:52'),(6,8,'FG3774477474',1,'2026-05-07 13:46:52','2026-05-07 13:46:52');
/*!40000 ALTER TABLE `accounting_delivery_note_serials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_delivery_notes`
--

DROP TABLE IF EXISTS `accounting_delivery_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_delivery_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `customer_order_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `delivery_date` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `delivered_by` varchar(255) DEFAULT NULL,
  `carrier` varchar(255) DEFAULT NULL,
  `stock_released_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_delivery_notes_reference_unique` (`reference`),
  KEY `accounting_delivery_notes_client_id_foreign` (`client_id`),
  KEY `accounting_delivery_notes_created_by_foreign` (`created_by`),
  KEY `acct_delivery_note_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_delivery_note_site_date_idx` (`company_site_id`,`delivery_date`),
  KEY `acct_delivery_note_site_client_idx` (`company_site_id`,`client_id`),
  KEY `acct_delivery_note_order_status_idx` (`customer_order_id`,`status`),
  CONSTRAINT `accounting_delivery_notes_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `accounting_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_delivery_notes_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_delivery_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_delivery_notes_customer_order_id_foreign` FOREIGN KEY (`customer_order_id`) REFERENCES `accounting_customer_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_delivery_notes`
--

LOCK TABLES `accounting_delivery_notes` WRITE;
/*!40000 ALTER TABLE `accounting_delivery_notes` DISABLE KEYS */;
INSERT INTO `accounting_delivery_notes` VALUES (4,'BL-000004',1,1,1,3,NULL,'2026-05-07','delivered','Horly Andelo',NULL,'2026-05-07 13:03:20',NULL,'2026-05-07 13:03:20','2026-05-07 13:03:20'),(6,'BL-000006',1,1,4,3,NULL,'2026-05-07','delivered',NULL,NULL,'2026-05-07 13:46:52',NULL,'2026-05-07 13:46:52','2026-05-07 13:46:52');
/*!40000 ALTER TABLE `accounting_delivery_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_expense_categories`
--

DROP TABLE IF EXISTS `accounting_expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_expense_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_expense_categories_company_site_id_slug_unique` (`company_site_id`,`slug`),
  KEY `accounting_expense_categories_company_site_id_status_index` (`company_site_id`,`status`),
  CONSTRAINT `accounting_expense_categories_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_expense_categories`
--

LOCK TABLES `accounting_expense_categories` WRITE;
/*!40000 ALTER TABLE `accounting_expense_categories` DISABLE KEYS */;
INSERT INTO `accounting_expense_categories` VALUES (1,1,'Loyer','loyer',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29'),(2,1,'Transport','transport',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29'),(3,1,'Carburant','carburant',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29'),(4,1,'Communication','communication',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29'),(5,1,'Internet','internet',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29'),(6,1,'Electricite','electricite',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29'),(7,1,'Eau','eau',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29'),(8,1,'Frais bancaires','frais-bancaires',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29'),(9,1,'Frais administratifs','frais-administratifs',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29'),(10,1,'Entretien et maintenance','entretien',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29'),(11,1,'Mission et déplacement','mission',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29'),(12,1,'Restauration','restauration',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29'),(13,1,'Avances sur salaires','avances-salaires',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29'),(14,1,'Taxes et impôts','taxes',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29'),(15,1,'Autres charges','autres-charges',NULL,1,'active','2026-05-14 07:49:29','2026-05-14 07:49:29');
/*!40000 ALTER TABLE `accounting_expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_expenses`
--

DROP TABLE IF EXISTS `accounting_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `expense_category_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `label` varchar(255) NOT NULL,
  `beneficiary` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_expenses_reference_unique` (`reference`),
  KEY `accounting_expenses_expense_category_id_foreign` (`expense_category_id`),
  KEY `accounting_expenses_created_by_foreign` (`created_by`),
  KEY `accounting_expenses_company_site_id_status_index` (`company_site_id`,`status`),
  KEY `accounting_expenses_company_site_id_expense_date_index` (`company_site_id`,`expense_date`),
  KEY `accounting_expenses_payment_method_id_status_index` (`payment_method_id`,`status`),
  CONSTRAINT `accounting_expenses_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_expenses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_expenses_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `accounting_expense_categories` (`id`),
  CONSTRAINT `accounting_expenses_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `accounting_payment_methods` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_expenses`
--

LOCK TABLES `accounting_expenses` WRITE;
/*!40000 ALTER TABLE `accounting_expenses` DISABLE KEYS */;
INSERT INTO `accounting_expenses` VALUES (1,1,6,1,3,'DEP-000001','2026-05-14','Facture electricité','SNEL',NULL,30.00,'USD',NULL,'validated','2026-05-14 08:03:08','2026-05-14 08:03:08');
/*!40000 ALTER TABLE `accounting_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_menu_permissions`
--

DROP TABLE IF EXISTS `accounting_menu_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_menu_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `menu_key` varchar(64) NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_menu_permissions_site_user_key_unique` (`company_site_id`,`user_id`,`menu_key`),
  KEY `accounting_menu_permissions_user_id_foreign` (`user_id`),
  CONSTRAINT `accounting_menu_permissions_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_menu_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_menu_permissions`
--

LOCK TABLES `accounting_menu_permissions` WRITE;
/*!40000 ALTER TABLE `accounting_menu_permissions` DISABLE KEYS */;
INSERT INTO `accounting_menu_permissions` VALUES (45,1,11,'dashboard',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(46,1,11,'prospects',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(47,1,11,'clients',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(48,1,11,'suppliers',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(49,1,11,'creditors',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(50,1,11,'debtors',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(51,1,11,'partners',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(52,1,11,'sales-representatives',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(53,1,11,'stock-items',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(54,1,11,'stock-categories',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(55,1,11,'stock-subcategories',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(56,1,11,'stock-warehouses',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(57,1,11,'stock-movements',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(58,1,11,'stock-inventories',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(59,1,11,'stock-alerts',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(60,1,11,'stock-units',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(61,1,11,'stock-batches',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(62,1,11,'stock-transfers',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(63,1,11,'service-price-list',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(64,1,11,'service-categories',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(65,1,11,'service-subcategories',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(66,1,11,'service-units',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(67,1,11,'service-recurring',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(68,1,11,'currencies',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(69,1,11,'payment-methods',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(70,1,11,'taxes',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(71,1,11,'proforma-invoices',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(72,1,11,'customer-orders',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(73,1,11,'delivery-notes',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(74,1,11,'sales-invoices',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(75,1,11,'credit-notes',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(76,1,11,'receipts',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(77,1,11,'cash-register',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(78,1,11,'other-incomes',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(79,1,11,'purchases',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(80,1,11,'purchase-orders',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(81,1,11,'expenses',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(82,1,11,'debts',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(83,1,11,'receivables',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(84,1,11,'treasury',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(85,1,11,'bank-reconciliations',1,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(86,1,11,'payment-reminders',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(87,1,11,'tasks',0,'2026-05-26 13:02:14','2026-05-26 13:02:14'),(88,1,11,'reports',0,'2026-05-26 13:02:14','2026-05-26 13:02:14');
/*!40000 ALTER TABLE `accounting_menu_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_module_settings`
--

DROP TABLE IF EXISTS `accounting_module_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_module_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `pdf_primary_color` varchar(7) NOT NULL DEFAULT '#2F70C8',
  `pdf_accent_color` varchar(7) NOT NULL DEFAULT '#40AEF4',
  `pdf_tint_color` varchar(7) NOT NULL DEFAULT '#D7EEF8',
  `pdf_show_qr_code` tinyint(1) NOT NULL DEFAULT 1,
  `pdf_show_footer_branding` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_module_settings_company_site_id_unique` (`company_site_id`),
  CONSTRAINT `accounting_module_settings_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_module_settings`
--

LOCK TABLES `accounting_module_settings` WRITE;
/*!40000 ALTER TABLE `accounting_module_settings` DISABLE KEYS */;
INSERT INTO `accounting_module_settings` VALUES (1,1,'#2F70C8','#40AEF4','#D7EEF8',1,1,'2026-05-26 12:52:27','2026-05-26 12:52:27');
/*!40000 ALTER TABLE `accounting_module_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_notification_reads`
--

DROP TABLE IF EXISTS `accounting_notification_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_notification_reads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `accounting_notification_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_notification_reads_unique` (`accounting_notification_id`,`user_id`),
  KEY `accounting_notification_reads_user_id_foreign` (`user_id`),
  CONSTRAINT `accounting_notification_reads_accounting_notification_id_foreign` FOREIGN KEY (`accounting_notification_id`) REFERENCES `accounting_notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_notification_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_notification_reads`
--

LOCK TABLES `accounting_notification_reads` WRITE;
/*!40000 ALTER TABLE `accounting_notification_reads` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_notification_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_notifications`
--

DROP TABLE IF EXISTS `accounting_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `action_key` varchar(80) NOT NULL,
  `module_key` varchar(80) NOT NULL DEFAULT 'dashboard',
  `subject_type` varchar(255) NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `subject_reference` varchar(255) DEFAULT NULL,
  `icon` varchar(80) NOT NULL DEFAULT 'bi-bell',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `occurred_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_notifications_action_subject_unique` (`action_key`,`subject_type`,`subject_id`),
  KEY `accounting_notifications_actor_id_foreign` (`actor_id`),
  KEY `accounting_notifications_company_site_id_occurred_at_index` (`company_site_id`,`occurred_at`),
  CONSTRAINT `accounting_notifications_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_notifications_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_notifications`
--

LOCK TABLES `accounting_notifications` WRITE;
/*!40000 ALTER TABLE `accounting_notifications` DISABLE KEYS */;
INSERT INTO `accounting_notifications` VALUES (1,1,3,'added_invoice','sales-invoices','App\\Models\\AccountingSalesInvoice',7,'FAC-000007','bi-receipt','a ajouté une facture','a ajouté une facture : __cash_register_sale__','2026-05-08 15:01:07','2026-05-26 13:46:47','2026-05-26 13:46:47'),(2,1,3,'added_invoice','sales-invoices','App\\Models\\AccountingSalesInvoice',5,'FAC-000005','bi-receipt','a ajouté une facture','a ajouté une facture : __cash_register_sale__','2026-05-08 14:35:33','2026-05-26 13:46:47','2026-05-26 13:46:47'),(3,1,3,'added_invoice','sales-invoices','App\\Models\\AccountingSalesInvoice',4,'FAC-000004','bi-receipt','a ajouté une facture','a ajouté une facture : __cash_register_sale__','2026-05-08 11:40:27','2026-05-26 13:46:47','2026-05-26 13:46:47'),(4,1,3,'added_invoice','sales-invoices','App\\Models\\AccountingSalesInvoice',3,'FAC-000003','bi-receipt','a ajouté une facture','a ajouté une facture : FAC-000003','2026-05-08 07:58:02','2026-05-26 13:46:47','2026-05-26 13:46:47'),(5,1,3,'added_invoice','sales-invoices','App\\Models\\AccountingSalesInvoice',2,'FAC-000002','bi-receipt','a ajouté une facture','a ajouté une facture : FAC-000002','2026-05-08 07:47:37','2026-05-26 13:46:47','2026-05-26 13:46:47'),(6,1,3,'added_invoice','sales-invoices','App\\Models\\AccountingSalesInvoice',1,'FAC-000001','bi-receipt','a ajouté une facture','a ajouté une facture : FAC-000001','2026-05-08 07:41:50','2026-05-26 13:46:47','2026-05-26 13:46:47'),(7,1,3,'added_purchase','purchases','App\\Models\\AccountingPurchase',1,'ACH-000001','bi-bag-check','a ajouté un achat','a ajouté un achat : ACH-000001','2026-05-13 13:00:51','2026-05-26 13:46:47','2026-05-26 13:46:47'),(8,1,3,'added_purchase_order','purchase-orders','App\\Models\\AccountingPurchaseOrder',2,'BCF-000002','bi-clipboard-check','a ajouté un bon de commande','a ajouté un bon de commande : BCF-000002','2026-05-13 13:51:28','2026-05-26 13:46:47','2026-05-26 13:46:47'),(9,1,3,'added_credit_note','credit-notes','App\\Models\\AccountingCreditNote',1,'AVR-000001','bi-arrow-counterclockwise','a ajouté un avoir','a ajouté un avoir : AVR-000001','2026-05-12 10:55:04','2026-05-26 13:46:47','2026-05-26 13:46:47'),(10,1,3,'added_expense','expenses','App\\Models\\AccountingExpense',1,'DEP-000001','bi-wallet2','a ajouté une dépense','a ajouté une dépense : Facture electricité','2026-05-14 08:03:08','2026-05-26 13:46:47','2026-05-26 13:46:47'),(11,1,3,'added_other_income','other-incomes','App\\Models\\AccountingOtherIncome',1,'ENT-000001','bi-plus-circle','a ajouté une entrée','a ajouté une entrée : Rembourssement','2026-05-12 11:38:40','2026-05-26 13:46:47','2026-05-26 13:46:47'),(12,1,3,'added_task','tasks','App\\Models\\AccountingTask',2,'TAC-000002','bi-check2-square','a ajouté une tâche','a ajouté une tâche : Relance client','2026-05-26 07:25:26','2026-05-26 13:46:47','2026-05-26 13:46:47'),(13,1,3,'added_task','tasks','App\\Models\\AccountingTask',1,'TAC-000001','bi-check2-square','a ajouté une tâche','a ajouté une tâche : Certification','2026-05-26 07:24:29','2026-05-26 13:46:47','2026-05-26 13:46:47'),(14,1,3,'added_bank_reconciliation','bank-reconciliations','App\\Models\\AccountingBankReconciliation',1,'RAP-000001','bi-bank','a créé un rapprochement bancaire','a créé un rapprochement bancaire : RAP-000001','2026-05-25 12:24:36','2026-05-26 13:46:47','2026-05-26 13:46:47'),(15,1,3,'added_treasury_movement','treasury','App\\Models\\AccountingTreasuryMovement',1,'TRS-000001','bi-activity','a ajouté un mouvement de trésorerie','a ajouté un mouvement de trésorerie : FAC-000001 - José Mambu','2026-05-25 08:20:09','2026-05-26 13:46:47','2026-05-26 13:46:47'),(16,1,3,'added_treasury_movement','treasury','App\\Models\\AccountingTreasuryMovement',2,'TRS-000002','bi-activity','a ajouté un mouvement de trésorerie','a ajouté un mouvement de trésorerie : FAC-000001 - José Mambu','2026-05-25 08:20:09','2026-05-26 13:46:47','2026-05-26 13:46:47'),(17,1,3,'added_treasury_movement','treasury','App\\Models\\AccountingTreasuryMovement',3,'TRS-000003','bi-activity','a ajouté un mouvement de trésorerie','a ajouté un mouvement de trésorerie : FAC-000004 - Client comptoir','2026-05-25 08:20:09','2026-05-26 13:46:47','2026-05-26 13:46:47'),(18,1,3,'added_treasury_movement','treasury','App\\Models\\AccountingTreasuryMovement',4,'TRS-000004','bi-activity','a ajouté un mouvement de trésorerie','a ajouté un mouvement de trésorerie : FAC-000005 - Client comptoir','2026-05-25 08:20:09','2026-05-26 13:46:47','2026-05-26 13:46:47'),(19,1,3,'added_treasury_movement','treasury','App\\Models\\AccountingTreasuryMovement',5,'TRS-000005','bi-activity','a ajouté un mouvement de trésorerie','a ajouté un mouvement de trésorerie : FAC-000007 - Client comptoir','2026-05-25 08:20:09','2026-05-26 13:46:47','2026-05-26 13:46:47'),(20,1,3,'added_treasury_movement','treasury','App\\Models\\AccountingTreasuryMovement',6,'TRS-000006','bi-activity','a ajouté un mouvement de trésorerie','a ajouté un mouvement de trésorerie : ENT-000001 - Rembourssement','2026-05-25 08:20:09','2026-05-26 13:46:47','2026-05-26 13:46:47'),(21,1,3,'added_treasury_movement','treasury','App\\Models\\AccountingTreasuryMovement',7,'TRS-000007','bi-activity','a ajouté un mouvement de trésorerie','a ajouté un mouvement de trésorerie : ACH-000001 - Fortinet','2026-05-25 08:20:09','2026-05-26 13:46:47','2026-05-26 13:46:47'),(22,1,3,'added_treasury_movement','treasury','App\\Models\\AccountingTreasuryMovement',8,'TRS-000008','bi-activity','a ajouté un mouvement de trésorerie','a ajouté un mouvement de trésorerie : DEP-000001 - Facture electricité','2026-05-25 08:20:09','2026-05-26 13:46:47','2026-05-26 13:46:47'),(23,1,3,'validated_expense','expenses','App\\Models\\AccountingExpense',1,'DEP-000001','bi-check2-circle','a validé une dépense','a validé une dépense : Facture electricité','2026-05-14 08:03:08','2026-05-26 13:46:47','2026-05-26 13:46:47'),(24,1,3,'validated_other_income','other-incomes','App\\Models\\AccountingOtherIncome',1,'ENT-000001','bi-check2-circle','a validé une entrée','a validé une entrée : Rembourssement','2026-05-12 11:38:40','2026-05-26 13:46:47','2026-05-26 13:46:47'),(25,1,3,'validated_credit_note','credit-notes','App\\Models\\AccountingCreditNote',1,'AVR-000001','bi-check2-circle','a validé un avoir','a validé un avoir : AVR-000001','2026-05-12 10:55:04','2026-05-26 13:46:47','2026-05-26 13:46:47'),(26,1,3,'completed_task','tasks','App\\Models\\AccountingTask',2,'TAC-000002','bi-check2-square','a clôturé une tâche','a clôturé une tâche : Relance client','2026-05-26 07:25:42','2026-05-26 13:46:47','2026-05-26 13:46:47');
/*!40000 ALTER TABLE `accounting_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_other_incomes`
--

DROP TABLE IF EXISTS `accounting_other_incomes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_other_incomes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `income_date` date NOT NULL,
  `type` varchar(50) NOT NULL,
  `label` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_other_incomes_reference_unique` (`reference`),
  KEY `accounting_other_incomes_created_by_foreign` (`created_by`),
  KEY `acct_other_income_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_other_income_site_date_idx` (`company_site_id`,`income_date`),
  KEY `acct_other_income_payment_status_idx` (`payment_method_id`,`status`),
  CONSTRAINT `accounting_other_incomes_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_other_incomes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_other_incomes_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `accounting_payment_methods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_other_incomes`
--

LOCK TABLES `accounting_other_incomes` WRITE;
/*!40000 ALTER TABLE `accounting_other_incomes` DISABLE KEYS */;
INSERT INTO `accounting_other_incomes` VALUES (1,'ENT-000001',1,1,3,'2026-05-12','owner_contribution','Rembourssement',NULL,100.00,'USD',NULL,'validated','2026-05-12 11:38:40','2026-05-12 11:38:40');
/*!40000 ALTER TABLE `accounting_other_incomes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_partners`
--

DROP TABLE IF EXISTS `accounting_partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `accounting_partners_created_by_foreign` (`created_by`),
  KEY `accounting_partners_company_site_id_type_index` (`company_site_id`,`type`),
  KEY `accounting_partners_company_site_id_name_index` (`company_site_id`,`name`),
  KEY `accounting_partners_company_site_id_status_index` (`company_site_id`,`status`),
  KEY `accounting_partners_company_site_id_activity_domain_index` (`company_site_id`,`activity_domain`),
  CONSTRAINT `accounting_partners_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_partners_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_partners`
--

LOCK TABLES `accounting_partners` WRITE;
/*!40000 ALTER TABLE `accounting_partners` DISABLE KEYS */;
INSERT INTO `accounting_partners` VALUES (1,'PAR-000001',1,3,'business_referrer','Mukupu',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-04-30 14:11:48','2026-04-30 14:11:48');
/*!40000 ALTER TABLE `accounting_partners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_payment_methods`
--

DROP TABLE IF EXISTS `accounting_payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_payment_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(30) NOT NULL,
  `currency_code` varchar(3) NOT NULL,
  `code` varchar(60) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_holder` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `iban` varchar(255) DEFAULT NULL,
  `bic_swift` varchar(255) DEFAULT NULL,
  `bank_address` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_system_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_payment_methods_reference_unique` (`reference`),
  KEY `accounting_payment_methods_created_by_foreign` (`created_by`),
  KEY `acct_pay_method_site_name_idx` (`company_site_id`,`name`),
  KEY `acct_pay_method_site_type_idx` (`company_site_id`,`type`),
  KEY `acct_pay_method_site_default_idx` (`company_site_id`,`is_default`),
  KEY `acct_pay_method_site_system_idx` (`company_site_id`,`is_system_default`),
  CONSTRAINT `accounting_payment_methods_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_payment_methods_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_payment_methods`
--

LOCK TABLES `accounting_payment_methods` WRITE;
/*!40000 ALTER TABLE `accounting_payment_methods` DISABLE KEYS */;
INSERT INTO `accounting_payment_methods` VALUES (1,'PAY-000001',1,3,'Espèces','cash','USD',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'active','2026-05-01 05:42:27','2026-05-01 05:42:27'),(2,'PAY-000002',1,3,'EQUITY BCDC','bank','USD',NULL,'EQUITY BCDC','EXAD SARL',NULL,NULL,NULL,NULL,NULL,0,0,'active','2026-05-01 05:47:12','2026-05-01 05:47:12');
/*!40000 ALTER TABLE `accounting_payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_payment_promises`
--

DROP TABLE IF EXISTS `accounting_payment_promises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_payment_promises` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_reminder_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `promised_date` date NOT NULL,
  `status` varchar(25) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_payment_promises_created_by_foreign` (`created_by`),
  KEY `acct_payment_promise_status_idx` (`payment_reminder_id`,`status`),
  KEY `acct_payment_promise_due_idx` (`promised_date`,`status`),
  CONSTRAINT `accounting_payment_promises_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_payment_promises_payment_reminder_id_foreign` FOREIGN KEY (`payment_reminder_id`) REFERENCES `accounting_payment_reminders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_payment_promises`
--

LOCK TABLES `accounting_payment_promises` WRITE;
/*!40000 ALTER TABLE `accounting_payment_promises` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_payment_promises` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_payment_reminder_actions`
--

DROP TABLE IF EXISTS `accounting_payment_reminder_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_payment_reminder_actions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_reminder_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `action_type` varchar(30) NOT NULL,
  `channel` varchar(25) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `next_reminder_date` date DEFAULT NULL,
  `action_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_payment_reminder_actions_created_by_foreign` (`created_by`),
  KEY `acct_payment_reminder_action_date_idx` (`payment_reminder_id`,`action_at`),
  CONSTRAINT `accounting_payment_reminder_actions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_payment_reminder_actions_payment_reminder_id_foreign` FOREIGN KEY (`payment_reminder_id`) REFERENCES `accounting_payment_reminders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_payment_reminder_actions`
--

LOCK TABLES `accounting_payment_reminder_actions` WRITE;
/*!40000 ALTER TABLE `accounting_payment_reminder_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_payment_reminder_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_payment_reminders`
--

DROP TABLE IF EXISTS `accounting_payment_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_payment_reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `sales_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `debtor_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `level` varchar(30) NOT NULL,
  `channel` varchar(25) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'sent',
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `next_reminder_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_payment_reminder_invoice_unique` (`sales_invoice_id`),
  UNIQUE KEY `acct_payment_reminder_debtor_unique` (`debtor_id`),
  UNIQUE KEY `accounting_payment_reminders_reference_unique` (`reference`),
  KEY `accounting_payment_reminders_client_id_foreign` (`client_id`),
  KEY `accounting_payment_reminders_created_by_foreign` (`created_by`),
  KEY `acct_payment_reminder_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_payment_reminder_site_next_idx` (`company_site_id`,`next_reminder_date`),
  CONSTRAINT `accounting_payment_reminders_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `accounting_clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_payment_reminders_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_payment_reminders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_payment_reminders_debtor_id_foreign` FOREIGN KEY (`debtor_id`) REFERENCES `accounting_debtors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_payment_reminders_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `accounting_sales_invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_payment_reminders`
--

LOCK TABLES `accounting_payment_reminders` WRITE;
/*!40000 ALTER TABLE `accounting_payment_reminders` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_payment_reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_proforma_invoice_lines`
--

DROP TABLE IF EXISTS `accounting_proforma_invoice_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_proforma_invoice_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proforma_invoice_id` bigint(20) unsigned NOT NULL,
  `line_type` varchar(30) NOT NULL,
  `item_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `quantity` decimal(18,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(18,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(20) NOT NULL DEFAULT 'fixed',
  `discount_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_proforma_invoice_lines_item_id_foreign` (`item_id`),
  KEY `accounting_proforma_invoice_lines_service_id_foreign` (`service_id`),
  KEY `acct_proforma_line_type_idx` (`proforma_invoice_id`,`line_type`),
  CONSTRAINT `accounting_proforma_invoice_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_proforma_invoice_lines_proforma_invoice_id_foreign` FOREIGN KEY (`proforma_invoice_id`) REFERENCES `accounting_proforma_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_proforma_invoice_lines_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `accounting_services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_proforma_invoice_lines`
--

LOCK TABLES `accounting_proforma_invoice_lines` WRITE;
/*!40000 ALTER TABLE `accounting_proforma_invoice_lines` DISABLE KEYS */;
INSERT INTO `accounting_proforma_invoice_lines` VALUES (7,1,'item',2,NULL,'Fortigate 40F',NULL,1.00,500.00,'percent',10.00,450.00,'2026-05-07 08:13:15','2026-05-07 08:13:15'),(8,1,'item',1,NULL,'Nexus 9300K',NULL,1.00,6000.00,'fixed',0.00,6000.00,'2026-05-07 08:13:15','2026-05-07 08:13:15'),(10,2,'item',2,NULL,'Fortigate 40F',NULL,1.00,500.00,'fixed',0.00,500.00,'2026-05-07 08:20:26','2026-05-07 08:20:26'),(11,3,'item',4,NULL,'AMD Ryzen 9 9950X - 16 cœurs / 32 threads, socket AM5, orienté calcul et création',NULL,10.00,780.00,'fixed',0.00,7800.00,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(12,3,'item',5,NULL,'Corsair Vengeance DDR5 192 Go - kit 4 x 48 Go, 5200 MT/s, CL38',NULL,10.00,4320.00,'fixed',0.00,43200.00,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(13,3,'item',6,NULL,'ZOTAC Gaming GeForce RTX 5090 Solid OC - 32 Go GDDR7, GPU NVIDIA Blackwell',NULL,20.00,7200.00,'fixed',0.00,144000.00,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(14,3,'item',7,NULL,'Samsung 9100 PRO 2 To NVMe PCIe 5.0 - OS, pilotes, environnement IA',NULL,10.00,1080.00,'fixed',0.00,10800.00,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(15,3,'item',8,NULL,'Samsung 9100 PRO 4 To NVMe PCIe 5.0 - stockage modèles, datasets et checkpoints',NULL,20.00,1440.00,'fixed',0.00,28800.00,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(16,3,'item',9,NULL,'Intel Core Ultra 9 285K - 24 cœurs, socket LGA1851, fréquence turbo jusqu’à 5,7 GHz, adapté création / développement / IA locale',NULL,10.00,720.00,'fixed',0.00,7200.00,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(17,3,'item',10,NULL,'Corsair Vengeance RGB 128 Go DDR5 6000 MT/s CL40 - kit 2 x 64 Go, Intel XMP, non-ECC, adapté workstation créative',NULL,10.00,4800.00,'fixed',0.00,48000.00,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(18,3,'item',11,NULL,'ASUS ROG Strix / ASUS TUF GeForce RTX 4090 24 Go GDDR6X - modèle à confirmer selon stock et compatibilité exacte du waterblock',NULL,10.00,1440.00,'fixed',0.00,14400.00,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(19,3,'item',12,NULL,'Samsung 990 PRO 2 To NVMe PCIe 4.0 - système, outils de développement, drivers et environnements IA',NULL,10.00,780.00,'fixed',0.00,7800.00,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(20,3,'item',13,NULL,'Samsung 990 PRO 4 To NVMe PCIe 4.0',NULL,20.00,756.00,'fixed',0.00,15120.00,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(25,4,'item',4,NULL,'AMD Ryzen 9 9950X - 16 cœurs / 32 threads, socket AM5, orienté calcul et création',NULL,2.00,780.00,'fixed',0.00,1560.00,'2026-05-07 13:34:45','2026-05-07 13:34:45'),(26,4,'item',11,NULL,'ASUS ROG Strix / ASUS TUF GeForce RTX 4090 24 Go GDDR6X - modèle à confirmer selon stock et compatibilité exacte du waterblock',NULL,1.00,1440.00,'fixed',0.00,1440.00,'2026-05-07 13:34:45','2026-05-07 13:34:45');
/*!40000 ALTER TABLE `accounting_proforma_invoice_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_proforma_invoices`
--

DROP TABLE IF EXISTS `accounting_proforma_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_proforma_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `expiration_date` date DEFAULT NULL,
  `currency` varchar(3) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `payment_terms` varchar(40) DEFAULT NULL,
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_ht` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_ttc` decimal(18,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_proforma_invoices_reference_unique` (`reference`),
  KEY `accounting_proforma_invoices_client_id_foreign` (`client_id`),
  KEY `accounting_proforma_invoices_created_by_foreign` (`created_by`),
  KEY `acct_proforma_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_proforma_site_issue_idx` (`company_site_id`,`issue_date`),
  KEY `acct_proforma_site_client_idx` (`company_site_id`,`client_id`),
  CONSTRAINT `accounting_proforma_invoices_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `accounting_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_proforma_invoices_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_proforma_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_proforma_invoices`
--

LOCK TABLES `accounting_proforma_invoices` WRITE;
/*!40000 ALTER TABLE `accounting_proforma_invoices` DISABLE KEYS */;
INSERT INTO `accounting_proforma_invoices` VALUES (1,'PRO-000001',1,1,3,'Installation réseau','2026-05-05','2026-05-05','USD','converted','full_order',6500.00,50.00,6450.00,0.00,0.00,6450.00,NULL,'- Validité de l\'offre 45 jours \r\n- mode paiement à discuter avec le clien','2026-05-05 09:01:27','2026-05-07 08:13:15'),(2,'PRO-000002',1,2,3,NULL,'2026-05-07','2026-05-07','USD','converted','to_discuss',500.00,0.00,500.00,16.00,80.00,580.00,NULL,NULL,'2026-05-07 08:20:16','2026-05-07 08:32:50'),(3,'PRO-000003',1,1,3,NULL,'2026-05-07','2026-05-07','USD','draft','to_discuss',327120.00,0.00,327120.00,16.00,52339.20,379459.20,NULL,NULL,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(4,'PRO-000004',1,1,3,NULL,'2026-05-07','2026-05-07','USD','converted','to_discuss',3000.00,0.00,3000.00,16.00,480.00,3480.00,NULL,NULL,'2026-05-07 13:34:24','2026-05-07 13:35:02');
/*!40000 ALTER TABLE `accounting_proforma_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_prospect_contacts`
--

DROP TABLE IF EXISTS `accounting_prospect_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_prospect_contacts`
--

LOCK TABLES `accounting_prospect_contacts` WRITE;
/*!40000 ALTER TABLE `accounting_prospect_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_prospect_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_prospects`
--

DROP TABLE IF EXISTS `accounting_prospects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `accounting_prospects_created_by_foreign` (`created_by`),
  KEY `accounting_prospects_converted_client_id_foreign` (`converted_client_id`),
  KEY `accounting_prospects_company_site_id_type_index` (`company_site_id`,`type`),
  KEY `accounting_prospects_company_site_id_status_index` (`company_site_id`,`status`),
  KEY `accounting_prospects_company_site_id_interest_level_index` (`company_site_id`,`interest_level`),
  KEY `accounting_prospects_company_site_id_name_index` (`company_site_id`,`name`),
  CONSTRAINT `accounting_prospects_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_prospects_converted_client_id_foreign` FOREIGN KEY (`converted_client_id`) REFERENCES `accounting_clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_prospects_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_prospects`
--

LOCK TABLES `accounting_prospects` WRITE;
/*!40000 ALTER TABLE `accounting_prospects` DISABLE KEYS */;
INSERT INTO `accounting_prospects` VALUES (1,'PRS-000001',1,3,NULL,'company','Mosolo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'referral','new','warm',NULL,NULL,'2026-04-30 12:39:11','2026-04-30 12:39:11');
/*!40000 ALTER TABLE `accounting_prospects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_purchase_lines`
--

DROP TABLE IF EXISTS `accounting_purchase_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_purchase_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `line_type` varchar(30) NOT NULL,
  `item_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `quantity` decimal(18,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(18,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(20) NOT NULL DEFAULT 'fixed',
  `discount_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_purchase_lines_item_id_foreign` (`item_id`),
  KEY `accounting_purchase_lines_service_id_foreign` (`service_id`),
  KEY `acct_purchase_line_type_idx` (`purchase_id`,`line_type`),
  CONSTRAINT `accounting_purchase_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_purchase_lines_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `accounting_purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_purchase_lines_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `accounting_services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_purchase_lines`
--

LOCK TABLES `accounting_purchase_lines` WRITE;
/*!40000 ALTER TABLE `accounting_purchase_lines` DISABLE KEYS */;
INSERT INTO `accounting_purchase_lines` VALUES (2,1,'item',2,NULL,'Fortigate 40F',NULL,1.00,300.00,'fixed',0.00,300.00,'2026-05-13 13:01:20','2026-05-13 13:01:20');
/*!40000 ALTER TABLE `accounting_purchase_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_purchase_order_lines`
--

DROP TABLE IF EXISTS `accounting_purchase_order_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_purchase_order_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `line_type` varchar(30) NOT NULL,
  `item_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `quantity` decimal(18,2) NOT NULL DEFAULT 1.00,
  `received_quantity` decimal(18,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(18,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(20) NOT NULL DEFAULT 'fixed',
  `discount_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_purchase_order_lines_item_id_foreign` (`item_id`),
  KEY `accounting_purchase_order_lines_service_id_foreign` (`service_id`),
  KEY `acct_po_line_type_idx` (`purchase_order_id`,`line_type`),
  CONSTRAINT `accounting_purchase_order_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_purchase_order_lines_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `accounting_purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_purchase_order_lines_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `accounting_services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_purchase_order_lines`
--

LOCK TABLES `accounting_purchase_order_lines` WRITE;
/*!40000 ALTER TABLE `accounting_purchase_order_lines` DISABLE KEYS */;
INSERT INTO `accounting_purchase_order_lines` VALUES (2,2,'item',4,NULL,'AMD Ryzen 9 9950X - 16 cœurs / 32 threads, socket AM5, orienté calcul et création',NULL,1.00,0.00,780.00,'fixed',0.00,780.00,'2026-05-13 13:51:28','2026-05-13 13:51:28'),(3,2,'item',11,NULL,'ASUS ROG Strix / ASUS TUF GeForce RTX 4090 24 Go GDDR6X - modèle à confirmer selon stock et compatibilité exacte du waterblock',NULL,1.00,0.00,1440.00,'fixed',0.00,1440.00,'2026-05-13 13:51:28','2026-05-13 13:51:28');
/*!40000 ALTER TABLE `accounting_purchase_order_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_purchase_orders`
--

DROP TABLE IF EXISTS `accounting_purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `purchase_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_reference` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `currency` varchar(3) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_ht` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_ttc` decimal(18,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `converted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_purchase_orders_reference_unique` (`reference`),
  KEY `accounting_purchase_orders_supplier_id_foreign` (`supplier_id`),
  KEY `accounting_purchase_orders_created_by_foreign` (`created_by`),
  KEY `accounting_purchase_orders_purchase_id_foreign` (`purchase_id`),
  KEY `acct_purchase_orders_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_purchase_orders_site_date_idx` (`company_site_id`,`order_date`),
  KEY `acct_purchase_orders_supplier_idx` (`company_site_id`,`supplier_id`),
  CONSTRAINT `accounting_purchase_orders_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_purchase_orders_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `accounting_purchases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `accounting_suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_purchase_orders`
--

LOCK TABLES `accounting_purchase_orders` WRITE;
/*!40000 ALTER TABLE `accounting_purchase_orders` DISABLE KEYS */;
INSERT INTO `accounting_purchase_orders` VALUES (2,'BCF-000002',1,1,3,NULL,NULL,NULL,'2026-05-13','2026-05-20','USD','draft',2220.00,0.00,2220.00,16.00,355.20,2575.20,NULL,NULL,NULL,'2026-05-13 13:51:28','2026-05-13 13:51:28');
/*!40000 ALTER TABLE `accounting_purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_purchase_payments`
--

DROP TABLE IF EXISTS `accounting_purchase_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_purchase_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned NOT NULL,
  `paid_by` bigint(20) unsigned DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_purchase_payments_paid_by_foreign` (`paid_by`),
  KEY `acct_purchase_payment_date_idx` (`purchase_id`,`payment_date`),
  KEY `acct_purchase_payment_method_date_idx` (`payment_method_id`,`payment_date`),
  CONSTRAINT `accounting_purchase_payments_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_purchase_payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `accounting_payment_methods` (`id`),
  CONSTRAINT `accounting_purchase_payments_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `accounting_purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_purchase_payments`
--

LOCK TABLES `accounting_purchase_payments` WRITE;
/*!40000 ALTER TABLE `accounting_purchase_payments` DISABLE KEYS */;
INSERT INTO `accounting_purchase_payments` VALUES (1,1,2,3,'2026-05-13',348.00,'USD',NULL,NULL,'2026-05-13 13:01:46','2026-05-13 13:01:46');
/*!40000 ALTER TABLE `accounting_purchase_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_purchases`
--

DROP TABLE IF EXISTS `accounting_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_purchases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `supplier_invoice_reference` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `currency` varchar(3) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_ht` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_ttc` decimal(18,2) NOT NULL DEFAULT 0.00,
  `paid_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `balance_due` decimal(18,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_purchases_reference_unique` (`reference`),
  KEY `accounting_purchases_supplier_id_foreign` (`supplier_id`),
  KEY `accounting_purchases_created_by_foreign` (`created_by`),
  KEY `acct_purchases_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_purchases_site_date_idx` (`company_site_id`,`purchase_date`),
  KEY `acct_purchases_site_supplier_idx` (`company_site_id`,`supplier_id`),
  CONSTRAINT `accounting_purchases_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_purchases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `accounting_suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_purchases`
--

LOCK TABLES `accounting_purchases` WRITE;
/*!40000 ALTER TABLE `accounting_purchases` DISABLE KEYS */;
INSERT INTO `accounting_purchases` VALUES (1,'ACH-000001',1,1,3,NULL,NULL,'2026-05-13','2026-06-12','USD','paid',300.00,0.00,300.00,16.00,48.00,348.00,348.00,0.00,NULL,NULL,'2026-05-13 13:00:51','2026-05-13 13:01:46');
/*!40000 ALTER TABLE `accounting_purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_recurring_services`
--

DROP TABLE IF EXISTS `accounting_recurring_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_recurring_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `service_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `frequency` varchar(30) NOT NULL DEFAULT 'monthly',
  `start_date` date DEFAULT NULL,
  `next_invoice_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_recurring_services_reference_unique` (`reference`),
  KEY `accounting_recurring_services_service_id_foreign` (`service_id`),
  KEY `accounting_recurring_services_created_by_foreign` (`created_by`),
  KEY `acct_rec_srv_site_freq_idx` (`company_site_id`,`frequency`),
  KEY `acct_rec_srv_site_status_idx` (`company_site_id`,`status`),
  CONSTRAINT `accounting_recurring_services_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_recurring_services_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_recurring_services_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `accounting_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_recurring_services`
--

LOCK TABLES `accounting_recurring_services` WRITE;
/*!40000 ALTER TABLE `accounting_recurring_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_recurring_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_sales_invoice_lines`
--

DROP TABLE IF EXISTS `accounting_sales_invoice_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_sales_invoice_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `line_type` varchar(30) NOT NULL,
  `item_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `customer_order_line_id` bigint(20) unsigned DEFAULT NULL,
  `delivery_note_line_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `quantity` decimal(18,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(18,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(20) NOT NULL DEFAULT 'fixed',
  `discount_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_sales_invoice_lines_item_id_foreign` (`item_id`),
  KEY `accounting_sales_invoice_lines_service_id_foreign` (`service_id`),
  KEY `accounting_sales_invoice_lines_customer_order_line_id_foreign` (`customer_order_line_id`),
  KEY `accounting_sales_invoice_lines_delivery_note_line_id_foreign` (`delivery_note_line_id`),
  KEY `acct_sales_invoice_line_type_idx` (`sales_invoice_id`,`line_type`),
  CONSTRAINT `accounting_sales_invoice_lines_customer_order_line_id_foreign` FOREIGN KEY (`customer_order_line_id`) REFERENCES `accounting_customer_order_lines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_sales_invoice_lines_delivery_note_line_id_foreign` FOREIGN KEY (`delivery_note_line_id`) REFERENCES `accounting_delivery_note_lines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_sales_invoice_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_sales_invoice_lines_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `accounting_sales_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_sales_invoice_lines_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `accounting_services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_sales_invoice_lines`
--

LOCK TABLES `accounting_sales_invoice_lines` WRITE;
/*!40000 ALTER TABLE `accounting_sales_invoice_lines` DISABLE KEYS */;
INSERT INTO `accounting_sales_invoice_lines` VALUES (1,1,'item',4,NULL,NULL,NULL,'AMD Ryzen 9 9950X - 16 cœurs / 32 threads, socket AM5, orienté calcul et création',NULL,1.00,780.00,'fixed',0.00,780.00,'2026-05-08 07:41:50','2026-05-08 07:41:50'),(2,1,'item',9,NULL,NULL,NULL,'Intel Core Ultra 9 285K - 24 cœurs, socket LGA1851, fréquence turbo jusqu’à 5,7 GHz, adapté création / développement / IA locale',NULL,1.00,720.00,'fixed',0.00,720.00,'2026-05-08 07:41:50','2026-05-08 07:41:50'),(3,2,'item',1,NULL,NULL,NULL,'Nexus 9300K',NULL,1.00,6000.00,'fixed',0.00,6000.00,'2026-05-08 07:47:37','2026-05-08 07:47:37'),(4,3,'item',4,NULL,5,7,'AMD Ryzen 9 9950X - 16 cœurs / 32 threads, socket AM5, orienté calcul et création',NULL,2.00,780.00,'fixed',0.00,1560.00,'2026-05-08 07:58:02','2026-05-08 07:58:02'),(5,3,'item',11,NULL,6,8,'ASUS ROG Strix / ASUS TUF GeForce RTX 4090 24 Go GDDR6X - modèle à confirmer selon stock et compatibilité exacte du waterblock',NULL,1.00,1440.00,'fixed',0.00,1440.00,'2026-05-08 07:58:02','2026-05-08 07:58:02'),(6,4,'item',4,NULL,NULL,NULL,'AMD Ryzen 9 9950X - 16 cœurs / 32 threads, socket AM5, orienté calcul et création','ART-000004',3.00,780.00,'fixed',0.00,2340.00,'2026-05-08 11:40:27','2026-05-08 11:40:27'),(7,4,'item',11,NULL,NULL,NULL,'ASUS ROG Strix / ASUS TUF GeForce RTX 4090 24 Go GDDR6X - modèle à confirmer selon stock et compatibilité exacte du waterblock','ART-000011',3.00,1440.00,'fixed',0.00,4320.00,'2026-05-08 11:40:27','2026-05-08 11:40:27'),(8,5,'item',4,NULL,NULL,NULL,'AMD Ryzen 9 9950X - 16 cœurs / 32 threads, socket AM5, orienté calcul et création','ART-000004',2.00,780.00,'fixed',0.00,1560.00,'2026-05-08 14:35:33','2026-05-08 14:35:33'),(10,7,'item',2,NULL,NULL,NULL,'Fortigate 40F','ART-000002',3.00,500.00,'fixed',0.00,1500.00,'2026-05-08 15:01:07','2026-05-08 15:01:07');
/*!40000 ALTER TABLE `accounting_sales_invoice_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_sales_invoice_payments`
--

DROP TABLE IF EXISTS `accounting_sales_invoice_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_sales_invoice_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `amount_received` decimal(18,2) DEFAULT NULL,
  `change_due` decimal(18,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_sales_invoice_payments_payment_method_id_foreign` (`payment_method_id`),
  KEY `accounting_sales_invoice_payments_received_by_foreign` (`received_by`),
  KEY `acct_sales_invoice_payment_date_idx` (`sales_invoice_id`,`payment_date`),
  CONSTRAINT `accounting_sales_invoice_payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `accounting_payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_sales_invoice_payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_sales_invoice_payments_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `accounting_sales_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_sales_invoice_payments`
--

LOCK TABLES `accounting_sales_invoice_payments` WRITE;
/*!40000 ALTER TABLE `accounting_sales_invoice_payments` DISABLE KEYS */;
INSERT INTO `accounting_sales_invoice_payments` VALUES (1,1,2,3,'2026-05-08',1040.00,NULL,NULL,'USD',NULL,NULL,'2026-05-08 07:50:40','2026-05-08 07:50:40'),(3,1,2,3,'2026-05-08',700.00,NULL,NULL,'USD',NULL,NULL,'2026-05-08 08:14:01','2026-05-08 08:14:01'),(4,4,1,3,'2026-05-08',7725.60,NULL,NULL,'USD',NULL,'Paiement caisse de la facture FAC-000004','2026-05-08 11:40:27','2026-05-08 11:40:27'),(5,5,1,3,'2026-05-08',1809.60,NULL,NULL,'USD',NULL,'Paiement caisse de la facture FAC-000005','2026-05-08 14:35:33','2026-05-08 14:35:33'),(6,7,1,3,'2026-05-08',1740.00,2000.00,260.00,'USD',NULL,'Paiement caisse de la facture FAC-000007','2026-05-08 15:01:07','2026-05-08 15:01:07');
/*!40000 ALTER TABLE `accounting_sales_invoice_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_sales_invoices`
--

DROP TABLE IF EXISTS `accounting_sales_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_sales_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `cash_register_session_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `customer_order_id` bigint(20) unsigned DEFAULT NULL,
  `delivery_note_id` bigint(20) unsigned DEFAULT NULL,
  `proforma_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `currency` varchar(3) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `payment_terms` varchar(30) DEFAULT NULL,
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_ht` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_ttc` decimal(18,2) NOT NULL DEFAULT 0.00,
  `paid_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `credit_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `balance_due` decimal(18,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_sales_invoices_reference_unique` (`reference`),
  KEY `accounting_sales_invoices_client_id_foreign` (`client_id`),
  KEY `accounting_sales_invoices_customer_order_id_foreign` (`customer_order_id`),
  KEY `accounting_sales_invoices_delivery_note_id_foreign` (`delivery_note_id`),
  KEY `accounting_sales_invoices_proforma_invoice_id_foreign` (`proforma_invoice_id`),
  KEY `accounting_sales_invoices_created_by_foreign` (`created_by`),
  KEY `acct_sales_invoice_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_sales_invoice_site_date_idx` (`company_site_id`,`invoice_date`),
  KEY `acct_sales_invoice_site_client_idx` (`company_site_id`,`client_id`),
  KEY `acct_sales_invoice_cash_session_idx` (`cash_register_session_id`),
  CONSTRAINT `accounting_sales_invoices_cash_register_session_id_foreign` FOREIGN KEY (`cash_register_session_id`) REFERENCES `accounting_cash_register_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_sales_invoices_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `accounting_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_sales_invoices_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_sales_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_sales_invoices_customer_order_id_foreign` FOREIGN KEY (`customer_order_id`) REFERENCES `accounting_customer_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_sales_invoices_delivery_note_id_foreign` FOREIGN KEY (`delivery_note_id`) REFERENCES `accounting_delivery_notes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_sales_invoices_proforma_invoice_id_foreign` FOREIGN KEY (`proforma_invoice_id`) REFERENCES `accounting_proforma_invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_sales_invoices`
--

LOCK TABLES `accounting_sales_invoices` WRITE;
/*!40000 ALTER TABLE `accounting_sales_invoices` DISABLE KEYS */;
INSERT INTO `accounting_sales_invoices` VALUES (1,'FAC-000001',1,NULL,2,NULL,NULL,NULL,3,NULL,'2026-05-08','2026-06-07','USD','paid','to_discuss',1500.00,0.00,1500.00,16.00,240.00,1740.00,1740.00,0.00,0.00,NULL,NULL,'2026-05-08 07:41:50','2026-05-08 08:14:02'),(2,'FAC-000002',1,NULL,1,NULL,NULL,NULL,3,NULL,'2026-05-08','2026-06-07','USD','draft','to_discuss',6000.00,0.00,6000.00,16.00,960.00,6960.00,0.00,0.00,6960.00,NULL,NULL,'2026-05-08 07:47:37','2026-05-08 07:47:37'),(3,'FAC-000003',1,NULL,1,4,6,4,3,NULL,'2026-05-08','2026-06-07','USD','draft','to_discuss',3000.00,0.00,3000.00,16.00,480.00,3480.00,0.00,0.00,3480.00,NULL,NULL,'2026-05-08 07:58:02','2026-05-08 07:58:02'),(4,'FAC-000004',1,NULL,5,NULL,NULL,NULL,3,'__cash_register_sale__','2026-05-08','2026-05-08','USD','paid','full_order',6660.00,0.00,6660.00,16.00,1065.60,7725.60,7725.60,0.00,0.00,NULL,'Vente comptoir réglée immédiatement.','2026-05-08 11:40:27','2026-05-08 11:40:27'),(5,'FAC-000005',1,1,5,NULL,NULL,NULL,3,'__cash_register_sale__','2026-05-08','2026-05-08','USD','paid','full_order',1560.00,0.00,1560.00,16.00,249.60,1809.60,1809.60,0.00,0.00,NULL,'Vente comptoir réglée immédiatement.','2026-05-08 14:35:33','2026-05-08 14:35:33'),(7,'FAC-000007',1,2,5,NULL,NULL,NULL,3,'__cash_register_sale__','2026-05-08','2026-05-08','USD','paid','full_order',1500.00,0.00,1500.00,16.00,240.00,1740.00,1740.00,580.00,0.00,NULL,'Vente comptoir réglée immédiatement.','2026-05-08 15:01:07','2026-05-12 10:55:04');
/*!40000 ALTER TABLE `accounting_sales_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_sales_representatives`
--

DROP TABLE IF EXISTS `accounting_sales_representatives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `accounting_sales_representatives_created_by_foreign` (`created_by`),
  KEY `acct_sales_rep_site_type_idx` (`company_site_id`,`type`),
  KEY `acct_sales_rep_site_name_idx` (`company_site_id`,`name`),
  KEY `acct_sales_rep_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_sales_rep_site_area_idx` (`company_site_id`,`sales_area`),
  CONSTRAINT `accounting_sales_representatives_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_sales_representatives_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_sales_representatives`
--

LOCK TABLES `accounting_sales_representatives` WRITE;
/*!40000 ALTER TABLE `accounting_sales_representatives` DISABLE KEYS */;
INSERT INTO `accounting_sales_representatives` VALUES (1,'COM-000001',1,3,'internal','Mabekos',NULL,NULL,NULL,'Kinshasa Ouest','USD',5000.00,6000.00,7.00,'active',NULL,'2026-04-30 14:33:08','2026-04-30 14:33:08');
/*!40000 ALTER TABLE `accounting_sales_representatives` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_service_categories`
--

DROP TABLE IF EXISTS `accounting_service_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_service_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_service_categories_reference_unique` (`reference`),
  KEY `accounting_service_categories_created_by_foreign` (`created_by`),
  KEY `acct_srv_cat_site_name_idx` (`company_site_id`,`name`),
  KEY `acct_srv_cat_site_default_idx` (`company_site_id`,`is_default`),
  CONSTRAINT `accounting_service_categories_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_service_categories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_service_categories`
--

LOCK TABLES `accounting_service_categories` WRITE;
/*!40000 ALTER TABLE `accounting_service_categories` DISABLE KEYS */;
INSERT INTO `accounting_service_categories` VALUES (1,'SCA-000001',1,3,'Services generaux','Categorie de services creee automatiquement par le systeme.','active',1,'2026-04-30 20:16:44','2026-04-30 20:16:44');
/*!40000 ALTER TABLE `accounting_service_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_service_subcategories`
--

DROP TABLE IF EXISTS `accounting_service_subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_service_subcategories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_service_subcategories_reference_unique` (`reference`),
  KEY `accounting_service_subcategories_category_id_foreign` (`category_id`),
  KEY `accounting_service_subcategories_created_by_foreign` (`created_by`),
  KEY `acct_srv_subcat_site_cat_idx` (`company_site_id`,`category_id`),
  KEY `acct_srv_subcat_site_default_idx` (`company_site_id`,`is_default`),
  CONSTRAINT `accounting_service_subcategories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `accounting_service_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_service_subcategories_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_service_subcategories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_service_subcategories`
--

LOCK TABLES `accounting_service_subcategories` WRITE;
/*!40000 ALTER TABLE `accounting_service_subcategories` DISABLE KEYS */;
INSERT INTO `accounting_service_subcategories` VALUES (1,'SSC-000001',1,1,3,'Prestations generales','Sous-categorie de services creee automatiquement par le systeme.','active',1,'2026-04-30 20:16:44','2026-04-30 20:16:44');
/*!40000 ALTER TABLE `accounting_service_subcategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_service_units`
--

DROP TABLE IF EXISTS `accounting_service_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_service_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `symbol` varchar(30) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_service_units_reference_unique` (`reference`),
  KEY `accounting_service_units_created_by_foreign` (`created_by`),
  KEY `acct_srv_unit_site_name_idx` (`company_site_id`,`name`),
  KEY `acct_srv_unit_site_default_idx` (`company_site_id`,`is_default`),
  CONSTRAINT `accounting_service_units_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_service_units_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_service_units`
--

LOCK TABLES `accounting_service_units` WRITE;
/*!40000 ALTER TABLE `accounting_service_units` DISABLE KEYS */;
INSERT INTO `accounting_service_units` VALUES (1,'SUN-000001',1,3,'Forfait','forfait','active',1,'2026-04-30 20:16:44','2026-04-30 20:16:44');
/*!40000 ALTER TABLE `accounting_service_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_services`
--

DROP TABLE IF EXISTS `accounting_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `subcategory_id` bigint(20) unsigned DEFAULT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `billing_type` varchar(30) NOT NULL DEFAULT 'fixed',
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'CDF',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `estimated_duration` int(10) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `description` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_services_reference_unique` (`reference`),
  KEY `accounting_services_category_id_foreign` (`category_id`),
  KEY `accounting_services_subcategory_id_foreign` (`subcategory_id`),
  KEY `accounting_services_unit_id_foreign` (`unit_id`),
  KEY `accounting_services_created_by_foreign` (`created_by`),
  KEY `acct_srv_site_name_idx` (`company_site_id`,`name`),
  KEY `acct_srv_site_cat_idx` (`company_site_id`,`category_id`),
  KEY `acct_srv_site_status_idx` (`company_site_id`,`status`),
  CONSTRAINT `accounting_services_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `accounting_service_categories` (`id`),
  CONSTRAINT `accounting_services_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_services_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_services_subcategory_id_foreign` FOREIGN KEY (`subcategory_id`) REFERENCES `accounting_service_subcategories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_services_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `accounting_service_units` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_services`
--

LOCK TABLES `accounting_services` WRITE;
/*!40000 ALTER TABLE `accounting_services` DISABLE KEYS */;
INSERT INTO `accounting_services` VALUES (1,'SRV-000001',1,1,1,1,3,'Installation windows','fixed',100.00,'USD',0.00,NULL,'active',NULL,NULL,'2026-05-05 08:34:03','2026-05-05 08:34:03'),(2,'SRV-000002',1,1,1,1,3,'Configuration parefeu bas de gamme','fixed',1500.00,'USD',0.00,NULL,'active',NULL,NULL,'2026-05-05 08:34:38','2026-05-05 08:34:38');
/*!40000 ALTER TABLE `accounting_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_stock_alerts`
--

DROP TABLE IF EXISTS `accounting_stock_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `accounting_stock_alerts_item_id_foreign` (`item_id`),
  KEY `accounting_stock_alerts_warehouse_id_foreign` (`warehouse_id`),
  KEY `accounting_stock_alerts_created_by_foreign` (`created_by`),
  KEY `acct_stock_alert_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_stock_alert_site_item_idx` (`company_site_id`,`item_id`),
  CONSTRAINT `accounting_stock_alerts_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_alerts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_alerts_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_alerts_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_stock_alerts`
--

LOCK TABLES `accounting_stock_alerts` WRITE;
/*!40000 ALTER TABLE `accounting_stock_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_stock_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_stock_batches`
--

DROP TABLE IF EXISTS `accounting_stock_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `accounting_stock_batches_item_id_foreign` (`item_id`),
  KEY `accounting_stock_batches_created_by_foreign` (`created_by`),
  KEY `acct_stock_batch_site_item_idx` (`company_site_id`,`item_id`),
  KEY `acct_stock_batch_wh_item_idx` (`warehouse_id`,`item_id`),
  CONSTRAINT `accounting_stock_batches_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_batches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_batches_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_batches_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_stock_batches`
--

LOCK TABLES `accounting_stock_batches` WRITE;
/*!40000 ALTER TABLE `accounting_stock_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_stock_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_stock_categories`
--

DROP TABLE IF EXISTS `accounting_stock_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_stock_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_stock_categories_reference_unique` (`reference`),
  KEY `accounting_stock_categories_created_by_foreign` (`created_by`),
  KEY `acct_stock_cat_site_name_idx` (`company_site_id`,`name`),
  KEY `acct_stock_cat_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_stock_cat_site_default_idx` (`company_site_id`,`is_default`),
  KEY `accounting_stock_categories_warehouse_id_foreign` (`warehouse_id`),
  KEY `acct_stock_cat_site_wh_idx` (`company_site_id`,`warehouse_id`),
  CONSTRAINT `accounting_stock_categories_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_categories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_categories_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_stock_categories`
--

LOCK TABLES `accounting_stock_categories` WRITE;
/*!40000 ALTER TABLE `accounting_stock_categories` DISABLE KEYS */;
INSERT INTO `accounting_stock_categories` VALUES (1,'CAT-000001',1,1,3,'Categorie generale','Categorie stock creee automatiquement par le systeme.','active',1,'2026-04-30 19:27:12','2026-04-30 19:38:57');
/*!40000 ALTER TABLE `accounting_stock_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_stock_inventories`
--

DROP TABLE IF EXISTS `accounting_stock_inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `accounting_stock_inventories_warehouse_id_foreign` (`warehouse_id`),
  KEY `accounting_stock_inventories_created_by_foreign` (`created_by`),
  KEY `acct_stock_inv_site_status_idx` (`company_site_id`,`status`),
  CONSTRAINT `accounting_stock_inventories_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_inventories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_inventories_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_stock_inventories`
--

LOCK TABLES `accounting_stock_inventories` WRITE;
/*!40000 ALTER TABLE `accounting_stock_inventories` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_stock_inventories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_stock_inventory_lines`
--

DROP TABLE IF EXISTS `accounting_stock_inventory_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_stock_inventory_lines`
--

LOCK TABLES `accounting_stock_inventory_lines` WRITE;
/*!40000 ALTER TABLE `accounting_stock_inventory_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_stock_inventory_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_stock_items`
--

DROP TABLE IF EXISTS `accounting_stock_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `accounting_stock_items_category_id_foreign` (`category_id`),
  KEY `accounting_stock_items_subcategory_id_foreign` (`subcategory_id`),
  KEY `accounting_stock_items_unit_id_foreign` (`unit_id`),
  KEY `accounting_stock_items_default_warehouse_id_foreign` (`default_warehouse_id`),
  KEY `accounting_stock_items_created_by_foreign` (`created_by`),
  KEY `acct_stock_item_site_name_idx` (`company_site_id`,`name`),
  KEY `acct_stock_item_site_cat_idx` (`company_site_id`,`category_id`),
  KEY `acct_stock_item_site_status_idx` (`company_site_id`,`status`),
  CONSTRAINT `accounting_stock_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `accounting_stock_categories` (`id`),
  CONSTRAINT `accounting_stock_items_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_items_default_warehouse_id_foreign` FOREIGN KEY (`default_warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_items_subcategory_id_foreign` FOREIGN KEY (`subcategory_id`) REFERENCES `accounting_stock_subcategories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_items_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `accounting_stock_units` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_stock_items`
--

LOCK TABLES `accounting_stock_items` WRITE;
/*!40000 ALTER TABLE `accounting_stock_items` DISABLE KEYS */;
INSERT INTO `accounting_stock_items` VALUES (1,'ART-000001',1,1,1,2,1,3,NULL,NULL,'Nexus 9300K','product',5000.00,6000.00,10.00,10.00,'USD','active',NULL,'2026-05-01 07:09:49','2026-05-01 07:10:06'),(2,'ART-000002',1,1,1,2,1,3,NULL,NULL,'Fortigate 40F','product',300.00,500.00,0.00,5.00,'USD','active',NULL,'2026-05-01 07:11:02','2026-05-08 15:01:07'),(3,'ART-000003',1,1,1,2,1,3,NULL,NULL,'Serveur HP Proliant380 gen10','product',0.00,6000.00,9.00,0.00,'USD','active',NULL,'2026-05-07 08:01:50','2026-05-07 13:03:20'),(4,'ART-000004',1,1,1,2,1,3,NULL,NULL,'AMD Ryzen 9 9950X - 16 cœurs / 32 threads, socket AM5, orienté calcul et création','product',780.00,780.00,3.00,0.00,'USD','active',NULL,'2026-05-07 09:21:09','2026-05-08 14:35:33'),(5,'ART-000005',1,1,1,2,1,3,NULL,NULL,'Corsair Vengeance DDR5 192 Go - kit 4 x 48 Go, 5200 MT/s, CL38','product',4320.00,4320.00,0.00,0.00,'USD','active',NULL,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(6,'ART-000006',1,1,1,2,1,3,NULL,NULL,'ZOTAC Gaming GeForce RTX 5090 Solid OC - 32 Go GDDR7, GPU NVIDIA Blackwell','product',7200.00,7200.00,0.00,0.00,'USD','active',NULL,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(7,'ART-000007',1,1,1,2,1,3,NULL,NULL,'Samsung 9100 PRO 2 To NVMe PCIe 5.0 - OS, pilotes, environnement IA','product',1080.00,1080.00,0.00,0.00,'USD','active',NULL,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(8,'ART-000008',1,1,1,2,1,3,NULL,NULL,'Samsung 9100 PRO 4 To NVMe PCIe 5.0 - stockage modèles, datasets et checkpoints','product',1440.00,1440.00,0.00,0.00,'USD','active',NULL,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(9,'ART-000009',1,1,1,2,1,3,NULL,NULL,'Intel Core Ultra 9 285K - 24 cœurs, socket LGA1851, fréquence turbo jusqu’à 5,7 GHz, adapté création / développement / IA locale','product',720.00,720.00,0.00,0.00,'USD','active',NULL,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(10,'ART-000010',1,1,1,2,1,3,NULL,NULL,'Corsair Vengeance RGB 128 Go DDR5 6000 MT/s CL40 - kit 2 x 64 Go, Intel XMP, non-ECC, adapté workstation créative','product',4800.00,4800.00,0.00,0.00,'USD','active',NULL,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(11,'ART-000011',1,1,1,2,1,3,NULL,NULL,'ASUS ROG Strix / ASUS TUF GeForce RTX 4090 24 Go GDDR6X - modèle à confirmer selon stock et compatibilité exacte du waterblock','product',1440.00,1440.00,5.98,0.00,'USD','active',NULL,'2026-05-07 09:21:09','2026-05-08 11:40:27'),(12,'ART-000012',1,1,1,2,1,3,NULL,NULL,'Samsung 990 PRO 2 To NVMe PCIe 4.0 - système, outils de développement, drivers et environnements IA','product',780.00,780.00,0.00,0.00,'USD','active',NULL,'2026-05-07 09:21:09','2026-05-07 09:21:09'),(13,'ART-000013',1,1,1,2,1,3,NULL,NULL,'Samsung 990 PRO 4 To NVMe PCIe 4.0','product',756.00,756.00,0.00,0.00,'USD','active',NULL,'2026-05-07 09:21:09','2026-05-07 09:21:09');
/*!40000 ALTER TABLE `accounting_stock_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_stock_movements`
--

DROP TABLE IF EXISTS `accounting_stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `accounting_stock_movements_item_id_foreign` (`item_id`),
  KEY `accounting_stock_movements_warehouse_id_foreign` (`warehouse_id`),
  KEY `accounting_stock_movements_batch_id_foreign` (`batch_id`),
  KEY `accounting_stock_movements_created_by_foreign` (`created_by`),
  KEY `acct_stock_move_site_type_idx` (`company_site_id`,`type`),
  KEY `acct_stock_move_site_item_idx` (`company_site_id`,`item_id`),
  CONSTRAINT `accounting_stock_movements_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `accounting_stock_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_movements_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_movements_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_movements_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_stock_movements`
--

LOCK TABLES `accounting_stock_movements` WRITE;
/*!40000 ALTER TABLE `accounting_stock_movements` DISABLE KEYS */;
INSERT INTO `accounting_stock_movements` VALUES (1,'MVT-000001',1,3,1,NULL,3,'exit',1.00,'2026-05-07','Sortie de stock liée au bon de livraison BL-000004',NULL,'2026-05-07 13:03:20','2026-05-07 13:03:20'),(2,'MVT-000002',1,4,1,NULL,3,'exit',2.00,'2026-05-07','Sortie de stock liée au bon de livraison BL-000006',NULL,'2026-05-07 13:46:52','2026-05-07 13:46:52'),(3,'MVT-000003',1,11,1,NULL,3,'exit',1.00,'2026-05-07','Sortie de stock liée au bon de livraison BL-000006',NULL,'2026-05-07 13:46:52','2026-05-07 13:46:52'),(4,'MVT-000004',1,4,1,NULL,3,'exit',3.00,'2026-05-08','Vente caisse FAC-000004',NULL,'2026-05-08 11:40:27','2026-05-08 11:40:27'),(5,'MVT-000005',1,11,1,NULL,3,'exit',3.00,'2026-05-08','Vente caisse FAC-000004',NULL,'2026-05-08 11:40:27','2026-05-08 11:40:27'),(6,'MVT-000006',1,4,1,NULL,3,'exit',2.00,'2026-05-08','Vente caisse FAC-000005',NULL,'2026-05-08 14:35:33','2026-05-08 14:35:33'),(8,'MVT-000008',1,2,1,NULL,3,'exit',3.00,'2026-05-08','Vente caisse FAC-000007',NULL,'2026-05-08 15:01:07','2026-05-08 15:01:07');
/*!40000 ALTER TABLE `accounting_stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_stock_subcategories`
--

DROP TABLE IF EXISTS `accounting_stock_subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_stock_subcategories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_stock_subcategories_reference_unique` (`reference`),
  KEY `accounting_stock_subcategories_category_id_foreign` (`category_id`),
  KEY `accounting_stock_subcategories_created_by_foreign` (`created_by`),
  KEY `acct_stock_subcat_site_cat_idx` (`company_site_id`,`category_id`),
  KEY `acct_stock_subcat_site_name_idx` (`company_site_id`,`name`),
  KEY `acct_stock_subcat_site_default_idx` (`company_site_id`,`is_default`),
  CONSTRAINT `accounting_stock_subcategories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `accounting_stock_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_subcategories_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_subcategories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_stock_subcategories`
--

LOCK TABLES `accounting_stock_subcategories` WRITE;
/*!40000 ALTER TABLE `accounting_stock_subcategories` DISABLE KEYS */;
INSERT INTO `accounting_stock_subcategories` VALUES (1,'SCA-000001',1,1,3,'Sous-categorie generale','Sous-categorie stock creee automatiquement par le systeme.','active',1,'2026-04-30 19:27:12','2026-04-30 19:27:12');
/*!40000 ALTER TABLE `accounting_stock_subcategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_stock_transfers`
--

DROP TABLE IF EXISTS `accounting_stock_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `accounting_stock_transfers_item_id_foreign` (`item_id`),
  KEY `accounting_stock_transfers_from_warehouse_id_foreign` (`from_warehouse_id`),
  KEY `accounting_stock_transfers_to_warehouse_id_foreign` (`to_warehouse_id`),
  KEY `accounting_stock_transfers_created_by_foreign` (`created_by`),
  KEY `acct_stock_transfer_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_stock_transfer_site_item_idx` (`company_site_id`,`item_id`),
  CONSTRAINT `accounting_stock_transfers_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_transfers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_stock_transfers_from_warehouse_id_foreign` FOREIGN KEY (`from_warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`),
  CONSTRAINT `accounting_stock_transfers_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `accounting_stock_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_transfers_to_warehouse_id_foreign` FOREIGN KEY (`to_warehouse_id`) REFERENCES `accounting_stock_warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_stock_transfers`
--

LOCK TABLES `accounting_stock_transfers` WRITE;
/*!40000 ALTER TABLE `accounting_stock_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_stock_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_stock_units`
--

DROP TABLE IF EXISTS `accounting_stock_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_stock_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'unit',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_stock_units_reference_unique` (`reference`),
  KEY `accounting_stock_units_created_by_foreign` (`created_by`),
  KEY `acct_stock_unit_site_name_idx` (`company_site_id`,`name`),
  KEY `acct_stock_unit_site_default_idx` (`company_site_id`,`is_default`),
  CONSTRAINT `accounting_stock_units_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_units_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_stock_units`
--

LOCK TABLES `accounting_stock_units` WRITE;
/*!40000 ALTER TABLE `accounting_stock_units` DISABLE KEYS */;
INSERT INTO `accounting_stock_units` VALUES (2,'UNT-000002',1,3,'Pièce','pc','quantity','active',1,'2026-05-01 06:43:50','2026-05-01 06:43:50');
/*!40000 ALTER TABLE `accounting_stock_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_stock_warehouses`
--

DROP TABLE IF EXISTS `accounting_stock_warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_stock_warehouses_reference_unique` (`reference`),
  KEY `accounting_stock_warehouses_created_by_foreign` (`created_by`),
  KEY `acct_stock_wh_site_name_idx` (`company_site_id`,`name`),
  KEY `acct_stock_wh_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_stock_wh_site_default_idx` (`company_site_id`,`is_default`),
  CONSTRAINT `accounting_stock_warehouses_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_stock_warehouses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_stock_warehouses`
--

LOCK TABLES `accounting_stock_warehouses` WRITE;
/*!40000 ALTER TABLE `accounting_stock_warehouses` DISABLE KEYS */;
INSERT INTO `accounting_stock_warehouses` VALUES (1,'DEP-000001',1,3,'Entrepot principal','DEP-DEFAULT',NULL,NULL,'active',1,'2026-04-30 19:27:12','2026-04-30 19:27:12');
/*!40000 ALTER TABLE `accounting_stock_warehouses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_supplier_contacts`
--

DROP TABLE IF EXISTS `accounting_supplier_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_supplier_contacts`
--

LOCK TABLES `accounting_supplier_contacts` WRITE;
/*!40000 ALTER TABLE `accounting_supplier_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_supplier_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_suppliers`
--

DROP TABLE IF EXISTS `accounting_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `accounting_suppliers_created_by_foreign` (`created_by`),
  KEY `accounting_suppliers_company_site_id_type_index` (`company_site_id`,`type`),
  KEY `accounting_suppliers_company_site_id_name_index` (`company_site_id`,`name`),
  KEY `accounting_suppliers_company_site_id_status_index` (`company_site_id`,`status`),
  CONSTRAINT `accounting_suppliers_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_suppliers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_suppliers`
--

LOCK TABLES `accounting_suppliers` WRITE;
/*!40000 ALTER TABLE `accounting_suppliers` DISABLE KEYS */;
INSERT INTO `accounting_suppliers` VALUES (1,'FRS-000001',1,3,'company','Fortinet',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-04-30 11:52:51','2026-04-30 11:52:51');
/*!40000 ALTER TABLE `accounting_suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_task_activities`
--

DROP TABLE IF EXISTS `accounting_task_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_task_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `accounting_task_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `action_type` varchar(30) NOT NULL,
  `from_status` varchar(25) DEFAULT NULL,
  `to_status` varchar(25) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_task_activities_created_by_foreign` (`created_by`),
  KEY `acct_task_activity_date_idx` (`accounting_task_id`,`created_at`),
  CONSTRAINT `accounting_task_activities_accounting_task_id_foreign` FOREIGN KEY (`accounting_task_id`) REFERENCES `accounting_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_task_activities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_task_activities`
--

LOCK TABLES `accounting_task_activities` WRITE;
/*!40000 ALTER TABLE `accounting_task_activities` DISABLE KEYS */;
INSERT INTO `accounting_task_activities` VALUES (1,1,3,'created',NULL,'todo',NULL,'2026-05-26 07:24:29','2026-05-26 07:24:29'),(2,2,3,'created',NULL,'in_progress',NULL,'2026-05-26 07:25:26','2026-05-26 07:25:26'),(3,2,3,'completed','in_progress','completed',NULL,'2026-05-26 07:25:42','2026-05-26 07:25:42');
/*!40000 ALTER TABLE `accounting_task_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_tasks`
--

DROP TABLE IF EXISTS `accounting_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `status` varchar(25) NOT NULL DEFAULT 'todo',
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completion_notes` text DEFAULT NULL,
  `source_type` varchar(40) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `source_reference` varchar(50) DEFAULT NULL,
  `source_label` varchar(255) DEFAULT NULL,
  `is_automatic` tinyint(1) NOT NULL DEFAULT 0,
  `automation_key` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_tasks_site_automation_unique` (`company_site_id`,`automation_key`),
  UNIQUE KEY `accounting_tasks_reference_unique` (`reference`),
  KEY `accounting_tasks_assigned_to_foreign` (`assigned_to`),
  KEY `accounting_tasks_created_by_foreign` (`created_by`),
  KEY `accounting_tasks_completed_by_foreign` (`completed_by`),
  KEY `accounting_tasks_client_id_foreign` (`client_id`),
  KEY `accounting_tasks_supplier_id_foreign` (`supplier_id`),
  KEY `acct_tasks_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_tasks_site_due_idx` (`company_site_id`,`due_date`),
  KEY `acct_tasks_site_assignee_idx` (`company_site_id`,`assigned_to`),
  KEY `acct_tasks_site_priority_idx` (`company_site_id`,`priority`),
  CONSTRAINT `accounting_tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_tasks_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `accounting_clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_tasks_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_tasks_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_tasks_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `accounting_suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_tasks`
--

LOCK TABLES `accounting_tasks` WRITE;
/*!40000 ALTER TABLE `accounting_tasks` DISABLE KEYS */;
INSERT INTO `accounting_tasks` VALUES (1,'TAC-000001',1,4,3,NULL,NULL,NULL,'Certification','Certifications cisco','other','high','todo','2026-05-26',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-05-26 07:24:29','2026-05-26 07:24:29'),(2,'TAC-000002',1,11,3,3,NULL,NULL,'Relance client',NULL,'reminder','high','completed','2026-05-29','2026-05-26 07:25:42',NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-05-26 07:25:26','2026-05-26 07:25:42');
/*!40000 ALTER TABLE `accounting_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_taxes`
--

DROP TABLE IF EXISTS `accounting_taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_taxes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(60) NOT NULL,
  `name` varchar(255) NOT NULL,
  `kind` varchar(30) NOT NULL DEFAULT 'vat',
  `calculation_type` varchar(20) NOT NULL DEFAULT 'percentage',
  `value` decimal(18,2) NOT NULL DEFAULT 0.00,
  `nature` varchar(20) NOT NULL DEFAULT 'collected',
  `applies_to` varchar(20) NOT NULL DEFAULT 'both',
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_system_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_tax_site_code_unique` (`company_site_id`,`code`),
  UNIQUE KEY `accounting_taxes_reference_unique` (`reference`),
  KEY `accounting_taxes_created_by_foreign` (`created_by`),
  KEY `acct_tax_site_status_idx` (`company_site_id`,`status`),
  KEY `acct_tax_site_default_idx` (`company_site_id`,`is_default`),
  CONSTRAINT `accounting_taxes_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_taxes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_taxes`
--

LOCK TABLES `accounting_taxes` WRITE;
/*!40000 ALTER TABLE `accounting_taxes` DISABLE KEYS */;
INSERT INTO `accounting_taxes` VALUES (1,'TAX-000001',1,3,'TVA','TVA','vat','percentage',16.00,'collected','both',NULL,1,1,'active','2026-05-25 07:41:07','2026-05-25 07:41:07'),(2,'TAX-000002',1,3,'EXONERE','Exonération','exemption','percentage',0.00,'collected','both',NULL,0,0,'active','2026-05-25 07:41:07','2026-05-25 07:41:07');
/*!40000 ALTER TABLE `accounting_taxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_treasury_movements`
--

DROP TABLE IF EXISTS `accounting_treasury_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_treasury_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) DEFAULT NULL,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `movement_type` varchar(40) NOT NULL,
  `source_type` varchar(80) NOT NULL,
  `source_id` bigint(20) unsigned NOT NULL,
  `source_reference` varchar(255) DEFAULT NULL,
  `direction` varchar(20) NOT NULL,
  `label` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `movement_date` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'validated',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_treasury_unique_source` (`company_site_id`,`source_type`,`source_id`),
  UNIQUE KEY `accounting_treasury_movements_reference_unique` (`reference`),
  KEY `accounting_treasury_movements_created_by_foreign` (`created_by`),
  KEY `acct_treasury_site_date_idx` (`company_site_id`,`movement_date`),
  KEY `acct_treasury_site_currency_status_idx` (`company_site_id`,`currency`,`status`),
  KEY `acct_treasury_method_status_idx` (`payment_method_id`,`status`),
  CONSTRAINT `accounting_treasury_movements_company_site_id_foreign` FOREIGN KEY (`company_site_id`) REFERENCES `company_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_treasury_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_treasury_movements_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `accounting_payment_methods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_treasury_movements`
--

LOCK TABLES `accounting_treasury_movements` WRITE;
/*!40000 ALTER TABLE `accounting_treasury_movements` DISABLE KEYS */;
INSERT INTO `accounting_treasury_movements` VALUES (1,'TRS-000001',1,2,3,'sales_payment','App\\Models\\AccountingSalesInvoicePayment',1,'FAC-000001','inflow','FAC-000001 - José Mambu',NULL,1040.00,'USD','2026-05-08','validated','2026-05-25 08:20:09','2026-05-25 08:20:09'),(2,'TRS-000002',1,2,3,'sales_payment','App\\Models\\AccountingSalesInvoicePayment',3,'FAC-000001','inflow','FAC-000001 - José Mambu',NULL,700.00,'USD','2026-05-08','validated','2026-05-25 08:20:09','2026-05-25 08:20:09'),(3,'TRS-000003',1,1,3,'sales_payment','App\\Models\\AccountingSalesInvoicePayment',4,'FAC-000004','inflow','FAC-000004 - Client comptoir','Paiement caisse de la facture FAC-000004',7725.60,'USD','2026-05-08','validated','2026-05-25 08:20:09','2026-05-25 08:20:09'),(4,'TRS-000004',1,1,3,'sales_payment','App\\Models\\AccountingSalesInvoicePayment',5,'FAC-000005','inflow','FAC-000005 - Client comptoir','Paiement caisse de la facture FAC-000005',1809.60,'USD','2026-05-08','validated','2026-05-25 08:20:09','2026-05-25 08:20:09'),(5,'TRS-000005',1,1,3,'sales_payment','App\\Models\\AccountingSalesInvoicePayment',6,'FAC-000007','inflow','FAC-000007 - Client comptoir','Paiement caisse de la facture FAC-000007',1740.00,'USD','2026-05-08','validated','2026-05-25 08:20:09','2026-05-25 08:20:09'),(6,'TRS-000006',1,1,3,'other_income','App\\Models\\AccountingOtherIncome',1,'ENT-000001','inflow','ENT-000001 - Rembourssement',NULL,100.00,'USD','2026-05-12','validated','2026-05-25 08:20:09','2026-05-25 08:20:09'),(7,'TRS-000007',1,2,3,'purchase_payment','App\\Models\\AccountingPurchasePayment',1,'ACH-000001','outflow','ACH-000001 - Fortinet',NULL,348.00,'USD','2026-05-13','validated','2026-05-25 08:20:09','2026-05-25 08:20:09'),(8,'TRS-000008',1,1,3,'expense','App\\Models\\AccountingExpense',1,'DEP-000001','outflow','DEP-000001 - Facture electricité',NULL,30.00,'USD','2026-05-14','validated','2026-05-25 08:20:09','2026-05-25 08:20:09');
/*!40000 ALTER TABLE `accounting_treasury_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `application_settings`
--

DROP TABLE IF EXISTS `application_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `application_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application_settings`
--

LOCK TABLES `application_settings` WRITE;
/*!40000 ALTER TABLE `application_settings` DISABLE KEYS */;
INSERT INTO `application_settings` VALUES (1,'app_name','EXAD ERP','2026-05-25 07:03:08','2026-05-26 10:10:23'),(2,'short_name','EXAD ERP','2026-05-25 07:03:08','2026-05-26 10:10:23'),(3,'tagline','La technologie avant tout','2026-05-25 07:03:08','2026-05-25 07:04:58'),(4,'description','Une plateforme ERP unifiée pour la finance, les ressources humaines, les opérations et la relation client.','2026-05-25 07:03:08','2026-05-25 07:03:08'),(5,'support_email',NULL,'2026-05-25 07:03:08','2026-05-25 07:03:08'),(6,'support_phone',NULL,'2026-05-25 07:03:08','2026-05-25 07:03:08'),(7,'website',NULL,'2026-05-25 07:03:08','2026-05-25 07:03:08'),(8,'copyright','© 2026 ERP PLUS - Tous droits réservés.','2026-05-25 07:03:08','2026-05-25 07:04:58'),(9,'logo_path','application-branding/logo-6a157f9f079ae.png','2026-05-25 07:03:08','2026-05-26 10:10:23'),(10,'favicon_path','application-branding/favicon-6a157f9f0cb39.png','2026-05-25 07:03:08','2026-05-26 10:10:23');
/*!40000 ALTER TABLE `application_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('exad-erp-cache-application_branding','a:10:{s:8:\"app_name\";s:8:\"EXAD ERP\";s:10:\"short_name\";s:8:\"EXAD ERP\";s:7:\"tagline\";s:25:\"La technologie avant tout\";s:11:\"description\";s:108:\"Une plateforme ERP unifiée pour la finance, les ressources humaines, les opérations et la relation client.\";s:9:\"logo_path\";s:43:\"application-branding/logo-6a157f9f079ae.png\";s:12:\"favicon_path\";s:46:\"application-branding/favicon-6a157f9f0cb39.png\";s:13:\"support_email\";N;s:13:\"support_phone\";N;s:7:\"website\";N;s:9:\"copyright\";s:42:\"© 2026 ERP PLUS - Tous droits réservés.\";}',2095166120);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (2,2,3,'Prestavice',NULL,NULL,NULL,NULL,NULL,'Congo (RDC)',NULL,'contact@prestavice.com',NULL,NULL,'2026-04-25 20:08:54','2026-04-25 20:08:54'),(3,4,9,'Test Entreprise',NULL,NULL,NULL,NULL,'test','Congo (RDC)','company-logos/iiW6TXFrFR73wEWiSWgGlWRpdsX5RuHnAq4DeFrG.jpg','testpro@test.loc',NULL,NULL,'2026-04-26 19:03:54','2026-04-26 19:03:54'),(4,2,3,'EXAD',NULL,NULL,NULL,NULL,NULL,'Congo (RDC)','company-logos/sJkem1sKETkIF88RE0z25XXFex4UONOUApjQ0flC.jpg','sales@exadgroup.org',NULL,NULL,'2026-04-28 09:19:49','2026-04-28 09:19:49');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_accounts`
--

DROP TABLE IF EXISTS `company_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_accounts`
--

LOCK TABLES `company_accounts` WRITE;
/*!40000 ALTER TABLE `company_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `company_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_phones`
--

DROP TABLE IF EXISTS `company_phones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_phones`
--

LOCK TABLES `company_phones` WRITE;
/*!40000 ALTER TABLE `company_phones` DISABLE KEYS */;
/*!40000 ALTER TABLE `company_phones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_site_user`
--

DROP TABLE IF EXISTS `company_site_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_site_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_site_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `module_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`module_permissions`)),
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_site_user`
--

LOCK TABLES `company_site_user` WRITE;
/*!40000 ALTER TABLE `company_site_user` DISABLE KEYS */;
INSERT INTO `company_site_user` VALUES (1,1,3,NULL,0,0,0,'2026-04-28 15:29:52','2026-04-28 15:29:52'),(3,1,10,'{\"accounting\":{\"can_create\":true,\"can_update\":true,\"can_delete\":true},\"human_resources\":{\"can_create\":true,\"can_update\":true,\"can_delete\":true},\"archiving\":{\"can_create\":true,\"can_update\":true,\"can_delete\":true},\"document_management\":{\"can_create\":true,\"can_update\":true,\"can_delete\":true}}',1,1,1,'2026-04-28 19:01:54','2026-04-28 19:01:54'),(4,1,4,'{\"accounting\":{\"can_create\":true,\"can_update\":true,\"can_delete\":true}}',1,1,1,'2026-04-28 19:03:15','2026-04-28 19:03:15'),(5,1,11,'{\"accounting\":{\"can_create\":false,\"can_update\":false,\"can_delete\":false}}',0,0,0,'2026-04-28 19:04:47','2026-04-28 19:04:47');
/*!40000 ALTER TABLE `company_site_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_sites`
--

DROP TABLE IF EXISTS `company_sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_sites`
--

LOCK TABLES `company_sites` WRITE;
/*!40000 ALTER TABLE `company_sites` DISABLE KEYS */;
INSERT INTO `company_sites` VALUES (1,4,3,'EXAD Kinshasa','office','EXAD1505',NULL,NULL,'sales@exadgroup.org',NULL,'[\"accounting\",\"human_resources\",\"archiving\",\"document_management\"]','USD','active','2026-04-28 15:29:52','2026-04-28 15:30:07');
/*!40000 ALTER TABLE `company_sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_user`
--

DROP TABLE IF EXISTS `company_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_user`
--

LOCK TABLES `company_user` WRITE;
/*!40000 ALTER TABLE `company_user` DISABLE KEYS */;
INSERT INTO `company_user` VALUES (2,3,9,1,1,1,1,'2026-04-26 19:03:55','2026-04-26 19:03:55'),(3,4,3,1,1,1,1,'2026-04-28 09:19:49','2026-04-28 09:19:49'),(5,2,3,1,1,1,1,'2026-04-28 13:28:07','2026-04-28 13:28:07'),(11,4,10,1,1,1,1,'2026-04-28 19:01:54','2026-04-28 19:01:54'),(12,4,4,1,1,1,1,'2026-04-28 19:03:15','2026-04-28 19:03:15'),(13,4,11,1,0,0,0,'2026-04-28 19:04:47','2026-04-28 19:04:47');
/*!40000 ALTER TABLE `company_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_04_25_000001_create_erp_user_access_tables',1),(5,'2026_04_25_183442_add_two_factor_columns_to_users_table',1),(6,'2026_04_25_235959_add_plan_fields_to_subscriptions_table',2),(7,'2026_04_26_190617_update_companies_business_fields',3),(8,'2026_04_28_000001_create_company_sites_table',4),(9,'2026_04_28_000002_add_status_and_user_assignments_to_company_sites',5),(10,'2026_04_28_000003_update_pro_company_limit_to_two',6),(11,'2026_04_28_000004_add_permissions_to_company_site_user',7),(12,'2026_04_28_000005_create_user_login_histories_table',8),(13,'2026_04_29_000001_add_profile_photo_path_to_users_table',9),(14,'2026_04_30_000001_create_accounting_clients_tables',10),(15,'2026_04_30_000002_add_reference_to_accounting_clients_table',11),(16,'2026_04_30_000003_add_bank_and_currency_to_accounting_clients_table',12),(17,'2026_04_30_000004_create_accounting_suppliers_tables',13),(18,'2026_04_30_000005_create_accounting_prospects_tables',14),(19,'2026_04_30_000006_create_accounting_creditors_table',15),(20,'2026_04_30_000007_create_accounting_debtors_table',16),(21,'2026_04_30_000008_create_accounting_partners_table',17),(22,'2026_04_30_000009_create_accounting_sales_representatives_table',18),(23,'2026_04_30_000010_create_accounting_stock_tables',19),(24,'2026_04_30_000011_add_default_stock_records',20),(25,'2026_04_30_000012_add_warehouse_to_stock_categories',21),(26,'2026_04_30_000013_create_accounting_services_tables',22),(27,'2026_04_30_000014_create_accounting_currencies_table',23),(28,'2026_05_01_000001_create_accounting_payment_methods_table',24),(29,'2026_05_01_000002_create_accounting_proforma_invoices_tables',25),(30,'2026_05_01_000003_add_default_stock_unit',26),(31,'2026_05_05_000001_add_discount_type_to_accounting_proforma_invoice_lines_table',27),(32,'2026_05_05_000002_add_payment_terms_to_accounting_proforma_invoices_table',28),(33,'2026_05_06_000001_fill_proforma_offer_validity_dates',29),(34,'2026_05_07_000001_create_accounting_customer_orders_tables',30),(35,'2026_05_07_000002_create_accounting_delivery_notes_tables',31),(36,'2026_05_07_000003_create_accounting_delivery_note_serials_table',32),(37,'2026_05_07_000004_create_accounting_sales_invoices_tables',33),(38,'2026_05_08_000001_create_accounting_cash_register_sessions_table',34),(39,'2026_05_08_000002_add_cash_received_fields_to_sales_invoice_payments',35),(40,'2026_05_12_000001_create_accounting_credit_notes_tables',36),(41,'2026_05_12_000002_create_accounting_other_incomes_table',37),(42,'2026_05_12_000003_create_accounting_purchases_tables',38),(43,'2026_05_13_000001_create_accounting_purchase_orders_tables',39),(44,'2026_05_14_000001_create_accounting_expenses_tables',40),(45,'2026_05_14_000002_create_accounting_creditor_payments_table',41),(46,'2026_05_14_000003_create_accounting_debtor_payments_table',42),(47,'2026_05_14_000004_create_application_settings_table',43),(48,'2026_05_25_000001_create_accounting_taxes_table',44),(49,'2026_05_25_000002_create_accounting_treasury_movements_table',45),(50,'2026_05_25_000003_create_accounting_bank_reconciliation_tables',46),(51,'2026_05_25_000004_create_accounting_payment_reminders_tables',47),(52,'2026_05_25_000005_create_accounting_tasks_tables',48),(53,'2026_05_26_000001_create_accounting_module_settings_tables',49),(54,'2026_05_26_000002_create_accounting_notifications_tables',50);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('8zYYLocnoqVddBNJCrLIVksanPsagZf8BYbo8YBh',11,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoickVub0toYlh2MVFuUU9QWnoyRkZ5R3Y4N3RvdkdVR1lQN0FKejV2eCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9tYWluL2NvbXBhbmllcy80L3NpdGVzLzEvbW9kdWxlcy9hY2NvdW50aW5nL3Byb3NwZWN0cyI7czo1OiJyb3V0ZSI7czoyNToibWFpbi5hY2NvdW50aW5nLnByb3NwZWN0cyI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjExO30=',1779806794),('ehvjxPWKUcc9zOYZQWZHsQGtz9LUa7lFlAugeQ6t',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiNklNa1RtR2ZNQk5HVzh0ejJyTkxvWnZWNFoxMHVUekZ4Ukl4TDV1ciI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9tYWluL2NvbXBhbmllcy80L3NpdGVzLzEvbW9kdWxlcy9hY2NvdW50aW5nIjtzOjU6InJvdXRlIjtzOjMzOiJtYWluLmNvbXBhbmllcy5zaXRlcy5tb2R1bGVzLnNob3ciO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTozO3M6NjoibG9jYWxlIjtzOjI6ImZyIjt9',1779806807),('yaSaKt3rRhPTYCEWG5OGmPjqogr1H9Sgt3FYpT6H',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','YTozOntzOjY6Il90b2tlbiI7czo0MDoiR3JBSXVhTFNOY1dXY243OURTeDFxa1dZRGEzbVc1ZjUzamxIUmZyViI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1779803011);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
INSERT INTO `subscriptions` VALUES (2,'EXAD','EXAD-DEFAULT','business',NULL,'active','2027-04-25','2026-04-25 20:08:53','2026-04-28 09:18:40'),(3,'Prestavice','PRESTAVICE','business',NULL,'active','2027-04-25','2026-04-25 21:09:35','2026-04-25 21:09:35'),(4,'Test Pro','TEST_PRO','pro',2,'active','2027-04-25','2026-04-25 21:12:51','2026-04-25 21:47:34'),(6,'test4','TEST4','standard',1,'active','2027-04-26','2026-04-26 07:16:23','2026-04-26 07:16:23'),(7,'test5','TEST5','standard',1,'active','2027-04-26','2026-04-26 07:16:29','2026-04-26 07:16:29'),(8,'test6','TEST6','standard',1,'active','2027-04-26','2026-04-26 07:17:00','2026-04-26 07:17:00'),(11,'Test 8','TEST_8','standard',1,'active','2025-06-26','2026-04-26 08:12:36','2026-04-26 08:12:36'),(12,'Test 9','TEST_9','pro',2,'expired','2025-05-26','2026-04-26 08:20:37','2026-04-26 08:20:37');
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_login_histories`
--

DROP TABLE IF EXISTS `user_login_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_login_histories`
--

LOCK TABLES `user_login_histories` WRITE;
/*!40000 ALTER TABLE `user_login_histories` DISABLE KEYS */;
INSERT INTO `user_login_histories` VALUES (1,3,'Edge on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-28 19:45:14','2026-04-28 19:45:14','2026-04-28 19:45:14'),(2,5,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-29 12:44:54','2026-04-29 12:44:54','2026-04-29 12:44:54'),(3,3,'Edge on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-29 12:45:54','2026-04-29 12:45:54','2026-04-29 12:45:54'),(4,5,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-29 15:13:41','2026-04-29 15:13:41','2026-04-29 15:13:41'),(5,5,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-29 15:19:43','2026-04-29 15:19:43','2026-04-29 15:19:43'),(6,5,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-29 18:10:09','2026-04-29 18:10:09','2026-04-29 18:10:09'),(7,5,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-29 18:10:09','2026-04-29 18:10:09','2026-04-29 18:10:09'),(8,5,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-29 18:10:45','2026-04-29 18:10:45','2026-04-29 18:10:45'),(9,5,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-29 18:10:53','2026-04-29 18:10:53','2026-04-29 18:10:53'),(10,3,'Edge on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-29 18:15:40','2026-04-29 18:15:40','2026-04-29 18:15:40'),(11,11,'Firefox on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-04-29 18:20:42','2026-04-29 18:20:42','2026-04-29 18:20:42'),(12,3,'Browser on Unknown','127.0.0.1','Symfony','2026-04-29 18:36:06','2026-04-29 18:36:06','2026-04-29 18:36:06'),(13,3,'Browser on Unknown','127.0.0.1','Symfony','2026-04-29 18:36:19','2026-04-29 18:36:19','2026-04-29 18:36:19'),(14,3,'Browser on Unknown','127.0.0.1','Symfony','2026-04-29 18:37:18','2026-04-29 18:37:18','2026-04-29 18:37:18'),(15,11,'Firefox on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-04-29 19:21:30','2026-04-29 19:21:30','2026-04-29 19:21:30'),(16,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-30 09:36:31','2026-04-30 09:36:31','2026-04-30 09:36:31'),(17,3,'Edge on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-30 09:36:43','2026-04-30 09:36:43','2026-04-30 09:36:43'),(18,3,'Edge on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-30 09:36:43','2026-04-30 09:36:43','2026-04-30 09:36:43'),(19,5,'Edge on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-30 09:37:39','2026-04-30 09:37:39','2026-04-30 09:37:39'),(20,5,'Edge on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-30 15:06:21','2026-04-30 15:06:21','2026-04-30 15:06:21'),(21,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-30 19:08:45','2026-04-30 19:08:45','2026-04-30 19:08:45'),(22,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-01 05:07:49','2026-05-01 05:07:49','2026-05-01 05:07:49'),(23,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 07:46:11','2026-05-05 07:46:11','2026-05-05 07:46:11'),(24,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 07:46:11','2026-05-05 07:46:11','2026-05-05 07:46:11'),(25,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 11:26:09','2026-05-05 11:26:09','2026-05-05 11:26:09'),(26,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 18:26:06','2026-05-05 18:26:06','2026-05-05 18:26:06'),(27,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 18:26:07','2026-05-05 18:26:07','2026-05-05 18:26:07'),(28,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-06 07:32:11','2026-05-06 07:32:11','2026-05-06 07:32:11'),(29,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-06 13:11:04','2026-05-06 13:11:04','2026-05-06 13:11:04'),(30,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-07 06:56:36','2026-05-07 06:56:36','2026-05-07 06:56:36'),(31,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-07 06:56:36','2026-05-07 06:56:36','2026-05-07 06:56:36'),(32,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-07 12:21:17','2026-05-07 12:21:17','2026-05-07 12:21:17'),(33,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-07 17:34:05','2026-05-07 17:34:05','2026-05-07 17:34:05'),(34,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-08 07:24:03','2026-05-08 07:24:03','2026-05-08 07:24:03'),(35,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-12 10:02:02','2026-05-12 10:02:02','2026-05-12 10:02:02'),(36,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-13 12:23:33','2026-05-13 12:23:33','2026-05-13 12:23:33'),(37,3,'Browser on Unknown','127.0.0.1','Symfony','2026-05-13 12:57:05','2026-05-13 12:57:05','2026-05-13 12:57:05'),(38,3,'Browser on Unknown','127.0.0.1','Symfony','2026-05-13 12:57:27','2026-05-13 12:57:27','2026-05-13 12:57:27'),(39,3,'Browser on Unknown','127.0.0.1','Symfony','2026-05-13 12:58:52','2026-05-13 12:58:52','2026-05-13 12:58:52'),(40,3,'Browser on Unknown','127.0.0.1','Symfony','2026-05-13 13:45:37','2026-05-13 13:45:37','2026-05-13 13:45:37'),(41,3,'Browser on Unknown','127.0.0.1','Symfony','2026-05-13 13:46:01','2026-05-13 13:46:01','2026-05-13 13:46:01'),(42,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-14 06:47:36','2026-05-14 06:47:36','2026-05-14 06:47:36'),(43,5,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-14 12:58:38','2026-05-14 12:58:38','2026-05-14 12:58:38'),(44,5,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-25 06:42:41','2026-05-25 06:42:41','2026-05-25 06:42:41'),(45,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-25 07:16:24','2026-05-25 07:16:24','2026-05-25 07:16:24'),(46,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-25 07:17:02','2026-05-25 07:17:02','2026-05-25 07:17:02'),(47,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-25 12:16:04','2026-05-25 12:16:04','2026-05-25 12:16:04'),(48,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-26 07:10:21','2026-05-26 07:10:21','2026-05-26 07:10:21'),(49,5,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-26 10:08:55','2026-05-26 10:08:55','2026-05-26 10:08:55'),(50,3,'Chrome on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-26 11:18:50','2026-05-26 11:18:50','2026-05-26 11:18:50'),(51,11,'Edge on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-05-26 12:49:34','2026-05-26 12:49:34','2026-05-26 12:49:34'),(52,11,'Edge on Windows','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-05-26 13:01:13','2026-05-26 13:01:13','2026-05-26 13:01:13');
/*!40000 ALTER TABLE `user_login_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (3,2,'admin','admin@erp.loc',NULL,'$2y$12$SNpTPsgEa0Y8Z7Wiy/nd..kdXlizk06/ZaRGQKqw.UbMely6KMX/W',NULL,NULL,NULL,'admin',NULL,NULL,NULL,'profile-photos/3-69f21616abf45.jpg','TvkfL0URLBhbg5fOAf1eqsTVWtEko3hV1xUFpV8YC3FBql3a99Dq2LuRCXH7','2026-04-25 20:08:53','2026-04-29 13:30:46'),(4,2,'user1','user1@erp.loc',NULL,'$2y$12$uvxmnRyY9pYsXfg7CPX8meKR73LCDXm5eRStSphqI4juy4Dk7iJgO',NULL,NULL,NULL,'user',NULL,NULL,NULL,NULL,'bLm2YRf3rn7DxqZzXX1UUZQmzpeeCePSUDXI8JgRCcpMoLZDtHVLvG9fs1cZ','2026-04-25 20:08:54','2026-04-25 20:49:12'),(5,NULL,'superadmin','superadmin@erp.loc',NULL,'$2y$12$sIYrLZrjHbGknvfL9w5E2.8Es2glSsJQKtrG6vDWMjLUKuhHbwvri','eyJpdiI6IjJtSGZpdnpWQ1kzcDhobFVJaW53b1E9PSIsInZhbHVlIjoiamFOL0kyd25GVWo3YkhWYVdkaGc3UThFcktSN3NBNHlmS0oxRUFNZDMrdz0iLCJtYWMiOiI1ZTZkYjdmOTE0NmM4NmE5ZjIwOGZiMDQ1MzY3MGJmZGI5NDgxOTQ2NjdlNzgxZGQyMTU2M2YxM2U3NTY4ZDkyIiwidGFnIjoiIn0=','eyJpdiI6IktncDkvSEF2ZFRhZUtUMmc1MXZSTUE9PSIsInZhbHVlIjoiTEtwSzhDc0pkZ0FjRFd5UE9HZi82Q3RQTCs4MFMwK2pjS2FkZ1NZckxRbkQxY05tdVo5dU0wU3JRUklGekZ1VVZWcldtOVZjODhMajFFOUYybDhoVVJKZW5DS3Awa0d1TUk5OGtQL2V6SldVSk80T3RHSTlWa1Q1V2JBQjMraUI0NUdjdzk0RHhWQ1d1RHJjcjk0ekRwVEFtTWp4WS9EOHZDU09JMUJNZFJJYk43RkRvOVorbER2eElodmpjVktBdmxvaDdqUWpWNmRWV212N2RHb0dwVDh1WGczcDZ6QStBUGozRWhFbTViWGhlUXVTZnlzOXZORFpZVThhV0tzcVZTSW9rc1pVc2hLSDl4bk1CVFhxRHc9PSIsIm1hYyI6ImNiYmFmNTgyNDYwOTc1YTMwNDgwMDIxYzk3OGI5ODBkZDFkMzAzNDVjYTAzODYzN2I1NDdmOWQ4YjlhZjEwYTIiLCJ0YWciOiIifQ==','2026-04-29 15:12:11','superadmin',NULL,NULL,NULL,'profile-photos/5-69f2140c84be3.jpg','xR63CfPNqeTbdWWW8wXwterm5FAhpIsqykUhrSssXH90A9pWztEZAH1cQFv8','2026-04-25 20:08:54','2026-04-29 18:12:23'),(6,11,'admin1','admin1@erp.loc',NULL,'$2y$12$O.c1xuOOohV2Mz/l77y.0egBNVwyuURvoBJRnMZ1LgYbCy5ZF/xQK',NULL,NULL,NULL,'admin',NULL,NULL,NULL,NULL,NULL,'2026-04-26 08:15:52','2026-04-26 08:15:52'),(7,11,'user2','user2@erp.loc',NULL,'$2y$12$nNZrX0dxIw5/aWK9hwu3R.tS6MiMq0s2K0fbLIof8qv8WTPgHNsUK',NULL,NULL,NULL,'user',NULL,NULL,NULL,NULL,NULL,'2026-04-26 16:24:39','2026-04-26 16:24:39'),(9,4,'Test pro user','testprouser@erp.loc',NULL,'$2y$12$5p4d0ATu4vs6xnfAk0FGtujvCenJb8pr.4bjnrxW.q.khUl7QSfKO',NULL,NULL,NULL,'admin',NULL,NULL,NULL,NULL,NULL,'2026-04-26 19:02:55','2026-04-26 19:02:55'),(10,2,'userexad','userexad@erp.loc',NULL,'$2y$12$a3jdMF0gtwkzWn5.JDHNOOwl0oFsAybn7Lq57MmrfQyBIoY.mgGK6',NULL,NULL,NULL,'admin',NULL,NULL,NULL,NULL,NULL,'2026-04-28 13:25:23','2026-04-28 19:01:54'),(11,2,'user3','user3@erp.loc',NULL,'$2y$12$hcrUXlwO3BTbyXHhBqh8/OTLlzZsp2BiR.bu6B/d9I8ZySP6pq60O',NULL,NULL,NULL,'user',NULL,NULL,NULL,NULL,'xMHAHHbzRsIphBCSTA35Mbk8qvGVNWWhfkf6im0AlPuzLkQ2xahRhjGPq4aY','2026-04-28 19:04:47','2026-04-28 19:04:47');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'erp_database'
--

--
-- Dumping routines for database 'erp_database'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-26 15:47:07
