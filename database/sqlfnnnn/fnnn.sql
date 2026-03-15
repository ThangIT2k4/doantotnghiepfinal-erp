-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: datadeploy
-- ------------------------------------------------------
-- Server version	9.4.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `amenities`
--

DROP TABLE IF EXISTS `amenities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `amenities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_code` (`key_code`),
  KEY `amenities_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `amenities_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tiện ích';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` bigint unsigned DEFAULT NULL,
  `organization_id` bigint unsigned DEFAULT NULL COMMENT 'Tổ chức của entity',
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `before_json` json DEFAULT NULL,
  `after_json` json DEFAULT NULL,
  `changes_json` json DEFAULT NULL COMMENT 'Các trường đã thay đổi (JSON)',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP address của người thực hiện',
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'User agent của người thực hiện',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `fk_audit_actor` (`actor_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_created_at` (`created_at`),
  KEY `idx_audit_organization` (`organization_id`),
  CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_audit_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit log';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `booking_deposits`
--

DROP TABLE IF EXISTS `booking_deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_deposits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `unit_id` bigint unsigned NOT NULL,
  `tenant_user_id` bigint unsigned DEFAULT NULL COMMENT 'Người thuê (nếu đã có tài khoản)',
  `lead_id` bigint unsigned DEFAULT NULL COMMENT 'Lead khách hàng tiềm năng',
  `viewing_id` bigint unsigned DEFAULT NULL,
  `agent_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL COMMENT 'Số tiền đặt cọc',
  `payment_status` enum('pending','pending_approval','paid','refunded','expired','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'Trạng thái thanh toán',
  `deposit_type` enum('booking','security','advance') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'booking' COMMENT 'Loại đặt cọc',
  `hold_until` datetime NOT NULL COMMENT 'Giữ chỗ đến ngày',
  `payment_due_date` datetime DEFAULT NULL COMMENT 'Hạn chót thanh toán đặt cọc',
  `approved_at` datetime DEFAULT NULL COMMENT 'Thời gian phê duyệt đặt cọc',
  `approved_by` bigint unsigned DEFAULT NULL COMMENT 'Người phê duyệt đặt cọc',
  `paid_at` datetime DEFAULT NULL COMMENT 'Ngày thanh toán',
  `expired_at` datetime DEFAULT NULL COMMENT 'Ngày hết hạn',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú',
  `payment_details` json DEFAULT NULL COMMENT 'Chi tiết thanh toán',
  `reference_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số tham chiếu',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_deposits_reference_number_unique` (`reference_number`),
  KEY `booking_deposits_deleted_by_foreign` (`deleted_by`),
  KEY `booking_deposits_organization_id_payment_status_index` (`organization_id`,`payment_status`),
  KEY `booking_deposits_unit_id_payment_status_index` (`unit_id`,`payment_status`),
  KEY `booking_deposits_tenant_user_id_payment_status_index` (`tenant_user_id`,`payment_status`),
  KEY `booking_deposits_lead_id_payment_status_index` (`lead_id`,`payment_status`),
  KEY `booking_deposits_agent_id_created_at_index` (`agent_id`,`created_at`),
  KEY `booking_deposits_hold_until_index` (`hold_until`),
  KEY `booking_deposits_expired_at_index` (`expired_at`),
  KEY `booking_deposits_reference_number_index` (`reference_number`),
  KEY `idx_bd_organization_id` (`organization_id`),
  KEY `idx_bd_expired_at_status` (`expired_at`,`payment_status`),
  KEY `booking_deposits_approved_by_foreign` (`approved_by`),
  KEY `idx_booking_deposits_viewing_id` (`viewing_id`),
  CONSTRAINT `booking_deposits_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `booking_deposits_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `booking_deposits_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `booking_deposits_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `booking_deposits_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_deposits_tenant_user_id_foreign` FOREIGN KEY (`tenant_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `booking_deposits_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `booking_deposits_viewing_id_foreign` FOREIGN KEY (`viewing_id`) REFERENCES `viewings` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_booking_deposit_amount_positive` CHECK ((`amount` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `capabilities`
--

DROP TABLE IF EXISTS `capabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `capabilities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `capabilities_key_code_unique` (`key_code`)
) ENGINE=InnoDB AUTO_INCREMENT=240 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cash_outflows`
--

DROP TABLE IF EXISTS `cash_outflows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_outflows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `amount` decimal(12,2) NOT NULL COMMENT 'Số tiền',
  `payment_method_id` bigint unsigned DEFAULT NULL,
  `paid_at` datetime NOT NULL COMMENT 'Thời gian thanh toán',
  `status` enum('pending','success','failed','reversed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success' COMMENT 'Trạng thái',
  `transaction_ref` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã giao dịch',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú',
  `company_invoice_id` bigint unsigned DEFAULT NULL COMMENT 'ID hóa đơn công ty',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL COMMENT 'Người xóa',
  PRIMARY KEY (`id`),
  KEY `cash_outflows_deleted_by_foreign` (`deleted_by`),
  KEY `idx_co_type_status` (`status`),
  KEY `idx_co_paid_at` (`paid_at`),
  KEY `idx_co_company_invoice` (`company_invoice_id`),
  KEY `idx_co_payment_method` (`payment_method_id`),
  CONSTRAINT `cash_outflows_company_invoice_id_foreign` FOREIGN KEY (`company_invoice_id`) REFERENCES `company_invoices` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `cash_outflows_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cash_outflows_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_cashout_amount` CHECK ((`amount` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `commission_events`
--

DROP TABLE IF EXISTS `commission_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commission_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `policy_id` bigint unsigned NOT NULL,
  `organization_id` bigint unsigned NOT NULL,
  `trigger_event` enum('deposit_paid','lease_signed','invoice_paid','viewing_done','listing_published') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_id` bigint unsigned NOT NULL,
  `lease_id` bigint unsigned DEFAULT NULL,
  `unit_id` bigint unsigned DEFAULT NULL,
  `agent_id` bigint unsigned NOT NULL,
  `occurred_at` datetime NOT NULL,
  `amount_base` decimal(12,2) NOT NULL,
  `commission_total` decimal(12,2) NOT NULL,
  `status` enum('pending','approved','paid','reversed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ce_status` (`status`),
  KEY `fk_ce_policy` (`policy_id`),
  KEY `fk_ce_lease` (`lease_id`),
  KEY `fk_ce_unit` (`unit_id`),
  KEY `fk_ce_agent` (`agent_id`),
  KEY `commission_events_deleted_by_foreign` (`deleted_by`),
  KEY `idx_ce_org_deleted_status_occurred` (`organization_id`,`deleted_at`,`status`,`occurred_at`),
  KEY `idx_ce_org_status_time` (`organization_id`,`status`,`occurred_at`),
  CONSTRAINT `commission_events_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ce_agent` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ce_lease` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_ce_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_ce_policy` FOREIGN KEY (`policy_id`) REFERENCES `commission_policies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ce_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sự kiện hoa hồng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `commission_policies`
--

DROP TABLE IF EXISTS `commission_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commission_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `trigger_event` enum('deposit_paid','lease_signed','invoice_paid','viewing_done','listing_published') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `basis` enum('cash','accrual') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'cash',
  `calc_type` enum('percent','flat','tiered') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `percent_value` decimal(5,2) DEFAULT NULL,
  `flat_amount` decimal(12,2) DEFAULT NULL,
  `apply_limit_months` tinyint DEFAULT NULL,
  `min_amount` decimal(12,2) DEFAULT NULL,
  `cap_amount` decimal(12,2) DEFAULT NULL,
  `filters_json` json DEFAULT NULL,
  `active` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_cp_org_active` (`organization_id`,`active`),
  KEY `commission_policies_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `commission_policies_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cp_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chính sách hoa hồng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_invoice_items`
--

DROP TABLE IF EXISTS `company_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_invoice_id` bigint unsigned NOT NULL,
  `item_type` enum('rent','service','meter','deposit','ticket_cost','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `unit_price` decimal(15,2) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `meta_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company_invoice_items_invoice` (`company_invoice_id`),
  KEY `idx_company_invoice_items_invoice_type` (`company_invoice_id`,`item_type`),
  CONSTRAINT `company_invoice_items_company_invoice_id_foreign` FOREIGN KEY (`company_invoice_id`) REFERENCES `company_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_invoices`
--

DROP TABLE IF EXISTS `company_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL COMMENT 'ID tổ chức',
  `invoice_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Số hóa đơn',
  `vendor_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL COMMENT 'ID người dùng (cho payroll và master lease)',
  `issue_date` date NOT NULL COMMENT 'Ngày phát hành',
  `due_date` date NOT NULL COMMENT 'Ngày đến hạn',
  `status` enum('draft','pending','approved','paid','overdue','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái hóa đơn',
  `subtotal` decimal(15,2) NOT NULL,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND' COMMENT 'Đơn vị tiền tệ',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Mô tả hóa đơn',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú',
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL COMMENT 'Người xóa',
  `master_lease_id` bigint unsigned DEFAULT NULL COMMENT 'ID hợp đồng tổng',
  `ticket_id` bigint unsigned DEFAULT NULL COMMENT 'ID ticket',
  `ticket_log_id` bigint unsigned DEFAULT NULL COMMENT 'ID nhật ký ticket',
  `deposit_refund_id` bigint unsigned DEFAULT NULL COMMENT 'ID hoàn tiền cọc',
  `payroll_payslip_id` bigint unsigned DEFAULT NULL COMMENT 'ID phiếu lương',
  `invoice_type` enum('master_lease','ticket_cost','deposit_refund','payroll_payslip','landlord_payout','user_payout','utility','maintenance','service','supply','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Loại hóa đơn',
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_invoices_invoice_no_unique` (`invoice_no`),
  KEY `company_invoices_deleted_by_foreign` (`deleted_by`),
  KEY `idx_ci_org_status` (`organization_id`,`status`),
  KEY `idx_ci_org_type` (`organization_id`),
  KEY `idx_ci_vendor` (`vendor_id`),
  KEY `idx_ci_issue_date` (`issue_date`),
  KEY `idx_ci_due_date` (`due_date`),
  KEY `idx_ci_created_by` (`created_by`),
  KEY `idx_ci_type_status` (`status`),
  KEY `idx_ci_master_lease` (`master_lease_id`),
  KEY `idx_ci_ticket` (`ticket_id`),
  KEY `idx_ci_ticket_log` (`ticket_log_id`),
  KEY `idx_ci_deposit_refund` (`deposit_refund_id`),
  KEY `idx_ci_payroll_payslip` (`payroll_payslip_id`),
  KEY `idx_ci_user` (`user_id`),
  KEY `idx_ci_due_date_status_deleted` (`due_date`,`status`,`deleted_at`),
  KEY `idx_ci_issue_date_deleted` (`issue_date`,`deleted_at`),
  KEY `idx_ci_org_status_due` (`organization_id`,`status`,`due_date`),
  KEY `idx_ci_org_deleted_status_due` (`organization_id`,`deleted_at`,`status`,`due_date`),
  CONSTRAINT `company_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `company_invoices_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_invoices_deposit_refund_id_foreign` FOREIGN KEY (`deposit_refund_id`) REFERENCES `deposit_refunds` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `company_invoices_master_lease_id_foreign` FOREIGN KEY (`master_lease_id`) REFERENCES `master_leases` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `company_invoices_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_invoices_payroll_payslip_id_foreign` FOREIGN KEY (`payroll_payslip_id`) REFERENCES `payroll_payslips` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `company_invoices_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `company_invoices_ticket_log_id_foreign` FOREIGN KEY (`ticket_log_id`) REFERENCES `ticket_logs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `company_invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_invoices_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_company_invoice_amounts_non_negative` CHECK (((`subtotal` >= 0) and (`tax_amount` >= 0) and (`discount_amount` >= 0) and (`total_amount` >= 0))),
  CONSTRAINT `chk_company_invoice_dates` CHECK (((`issue_date` is null) or (`due_date` is null) or (`due_date` >= `issue_date`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `deposit_refunds`
--

DROP TABLE IF EXISTS `deposit_refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deposit_refunds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lease_id` bigint unsigned NOT NULL COMMENT 'ID hợp đồng',
  `organization_id` bigint unsigned NOT NULL COMMENT 'ID tổ chức',
  `unit_id` bigint unsigned NOT NULL COMMENT 'ID phòng',
  `tenant_id` bigint unsigned DEFAULT NULL,
  `agent_id` bigint unsigned DEFAULT NULL,
  `original_deposit_amount` decimal(12,2) NOT NULL COMMENT 'Số tiền cọc gốc',
  `deducted_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền đã trừ từ cọc',
  `refund_amount` decimal(12,2) NOT NULL COMMENT 'Số tiền hoàn lại',
  `status` enum('pending','approved','paid','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái hoàn tiền',
  `refund_method` enum('cash','bank_transfer','wallet') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Phương thức hoàn tiền',
  `refund_reference` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã tham chiếu hoàn tiền',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú',
  `deduction_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Chi tiết các khoản trừ',
  `approved_at` datetime DEFAULT NULL COMMENT 'Thời gian phê duyệt',
  `paid_at` datetime DEFAULT NULL COMMENT 'Thời gian thanh toán',
  `approved_by` bigint unsigned DEFAULT NULL COMMENT 'Người phê duyệt',
  `paid_by` bigint unsigned DEFAULT NULL COMMENT 'Người thực hiện thanh toán',
  `created_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL COMMENT 'Người xóa',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deposit_refunds_lease_org_refund_unique` (`lease_id`,`organization_id`,`refund_reference`),
  KEY `deposit_refunds_unit_id_foreign` (`unit_id`),
  KEY `deposit_refunds_approved_by_foreign` (`approved_by`),
  KEY `deposit_refunds_paid_by_foreign` (`paid_by`),
  KEY `deposit_refunds_created_by_foreign` (`created_by`),
  KEY `deposit_refunds_deleted_by_foreign` (`deleted_by`),
  KEY `deposit_refunds_lease_id_status_index` (`lease_id`,`status`),
  KEY `deposit_refunds_organization_id_status_index` (`organization_id`,`status`),
  KEY `deposit_refunds_tenant_id_status_index` (`tenant_id`,`status`),
  KEY `deposit_refunds_agent_id_status_index` (`agent_id`,`status`),
  CONSTRAINT `deposit_refunds_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deposit_refunds_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deposit_refunds_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deposit_refunds_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deposit_refunds_lease_id_foreign` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deposit_refunds_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deposit_refunds_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deposit_refunds_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deposit_refunds_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_refund_amount_calculation` CHECK ((abs((`refund_amount` - (`original_deposit_amount` - `deducted_amount`))) < 0.01)),
  CONSTRAINT `chk_refund_amount_non_negative` CHECK ((`refund_amount` >= 0)),
  CONSTRAINT `chk_refund_deducted_non_negative` CHECK ((`deducted_amount` >= 0)),
  CONSTRAINT `chk_refund_not_exceed_original` CHECK ((`refund_amount` <= `original_deposit_amount`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_id` bigint unsigned DEFAULT NULL,
  `file_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL COMMENT 'Kích thước file (bytes)',
  `document_type` enum('image','document','avatar','photo','attachment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'document' COMMENT 'Loại tài liệu',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Có phải file chính không (cho avatar, primary image)',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT 'Thứ tự sắp xếp',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Mô tả',
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_documents_owner` (`owner_type`,`owner_id`),
  KEY `fk_docs_uploader` (`uploaded_by`),
  KEY `documents_deleted_by_foreign` (`deleted_by`),
  KEY `idx_documents_type` (`document_type`),
  KEY `idx_documents_primary` (`is_primary`),
  CONSTRAINT `documents_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_docs_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_documents_mime_type` CHECK (((`mime_type` is null) or regexp_like(`mime_type`,_utf8mb4'^[a-zA-Z0-9][a-zA-Z0-9!#$&-^_.]*/[a-zA-Z0-9][a-zA-Z0-9!#$&-^_.]*$')))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tài liệu/ảnh';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_otps`
--

DROP TABLE IF EXISTS `email_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_otps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp_code` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email_verification',
  `expires_at` timestamp NOT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_otps_user_id_type_index` (`user_id`,`type`),
  KEY `email_otps_email_otp_code_index` (`email`,`otp_code`),
  KEY `email_otps_expires_at_index` (`expires_at`),
  CONSTRAINT `email_otps_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_otp_code_format` CHECK (((length(`otp_code`) = 6) and regexp_like(`otp_code`,_utf8mb4'^[0-9]{6}$')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geo_countries`
--

DROP TABLE IF EXISTS `geo_countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `geo_countries` (
  `code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_local` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quốc gia';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geo_districts`
--

DROP TABLE IF EXISTS `geo_districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `geo_districts` (
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `province_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_local` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kind` enum('district','town','urban_district') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'district',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`code`),
  KEY `idx_gdist_province` (`province_code`),
  CONSTRAINT `fk_gdist_province` FOREIGN KEY (`province_code`) REFERENCES `geo_provinces` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quận/Huyện/Thị xã';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geo_provinces`
--

DROP TABLE IF EXISTS `geo_provinces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `geo_provinces` (
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_local` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kind` enum('province','city','municipality') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'province',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`code`),
  KEY `idx_gprov_country` (`country_code`),
  CONSTRAINT `fk_gprov_country` FOREIGN KEY (`country_code`) REFERENCES `geo_countries` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tỉnh/Thành phố';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geo_provinces_2025`
--

DROP TABLE IF EXISTS `geo_provinces_2025`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `geo_provinces_2025` (
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_local` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kind` enum('province','city','municipality') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'province',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`code`),
  KEY `geo_provinces_2025_country_code_index` (`country_code`),
  CONSTRAINT `fk_geo_provinces_country` FOREIGN KEY (`country_code`) REFERENCES `geo_countries` (`code`) ON DELETE CASCADE,
  CONSTRAINT `geo_provinces_2025_country_code_foreign` FOREIGN KEY (`country_code`) REFERENCES `geo_countries` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geo_streets`
--

DROP TABLE IF EXISTS `geo_streets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `geo_streets` (
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ward_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_local` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`),
  KEY `idx_gstreet_ward` (`ward_code`),
  CONSTRAINT `geo_streets_ward_code_foreign` FOREIGN KEY (`ward_code`) REFERENCES `geo_wards` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geo_wards`
--

DROP TABLE IF EXISTS `geo_wards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `geo_wards` (
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `district_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_local` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kind` enum('ward','commune','townlet') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'ward',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`code`),
  KEY `idx_gward_district` (`district_code`),
  CONSTRAINT `fk_gward_district` FOREIGN KEY (`district_code`) REFERENCES `geo_districts` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Phường/Xã/Thị trấn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geo_wards_2025`
--

DROP TABLE IF EXISTS `geo_wards_2025`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `geo_wards_2025` (
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `district_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_local` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kind` enum('ward','commune','townlet') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ward',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`code`),
  KEY `geo_wards_2025_district_code_index` (`district_code`),
  CONSTRAINT `fk_wards_province_by_district_code` FOREIGN KEY (`district_code`) REFERENCES `geo_provinces_2025` (`code`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `item_type` enum('rent','service','meter','deposit','other','ticket_cost') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(12,3) NOT NULL DEFAULT '1.000',
  `unit_price` decimal(12,2) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `meta_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_invoice_items_invoice` (`invoice_id`),
  KEY `idx_invoice_items_invoice_type` (`invoice_id`,`item_type`),
  CONSTRAINT `fk_item_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dòng hóa đơn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `is_auto_created` tinyint(1) NOT NULL DEFAULT '0',
  `lease_id` bigint unsigned DEFAULT NULL,
  `booking_deposit_id` bigint unsigned DEFAULT NULL,
  `invoice_no` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_type` enum('monthly_rent','first_invoice','booking_deposit','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'other',
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('draft','issued','paid','overdue','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `subtotal` decimal(12,2) DEFAULT '0.00',
  `tax_amount` decimal(12,2) DEFAULT '0.00',
  `discount_amount` decimal(12,2) DEFAULT '0.00',
  `total_amount` decimal(12,2) DEFAULT '0.00',
  `currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'VND',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL COMMENT 'Người tạo hóa đơn',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `idx_invoices_lease_status` (`lease_id`,`status`),
  KEY `idx_invoices_due` (`due_date`),
  KEY `fk_inv_org` (`organization_id`),
  KEY `invoices_deleted_by_foreign` (`deleted_by`),
  KEY `invoices_booking_deposit_id_index` (`booking_deposit_id`),
  KEY `invoices_created_by_foreign` (`created_by`),
  KEY `invoices_invoice_type_lease_id_index` (`invoice_type`,`lease_id`),
  KEY `idx_invoices_deleted_at_status` (`deleted_at`,`status`),
  KEY `idx_invoices_deleted_at_lease_status` (`deleted_at`,`lease_id`,`status`),
  KEY `idx_invoices_org_deleted_status` (`organization_id`,`deleted_at`,`status`),
  KEY `idx_invoices_org_lease_deleted` (`organization_id`,`lease_id`,`deleted_at`),
  KEY `idx_invoices_due_date_status` (`due_date`,`status`),
  KEY `idx_invoices_issue_date` (`issue_date`),
  CONSTRAINT `fk_inv_lease` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_inv_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `invoices_booking_deposit_id_foreign` FOREIGN KEY (`booking_deposit_id`) REFERENCES `booking_deposits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_invoice_amounts_non_negative` CHECK (((`subtotal` >= 0) and (`tax_amount` >= 0) and (`discount_amount` >= 0) and (`total_amount` >= 0))),
  CONSTRAINT `chk_invoice_dates` CHECK (((`issue_date` is null) or (`due_date` is null) or (`due_date` >= `issue_date`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hóa đơn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID lead CRM',
  `organization_id` bigint unsigned NOT NULL,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `source` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nguồn: web/zalo/fb/referral/...',
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `desired_city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Khu vực mong muốn',
  `budget_min` decimal(12,2) DEFAULT NULL COMMENT 'Ngân sách tối thiểu',
  `budget_max` decimal(12,2) DEFAULT NULL COMMENT 'Ngân sách tối đa',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú',
  `status` enum('new','contacted','qualified','lost','converted') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'new' COMMENT 'Trạng thái CRM',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email_per_org` (`organization_id`,`email`),
  UNIQUE KEY `unique_phone_per_org` (`organization_id`,`phone`),
  KEY `idx_leads_status_created` (`status`,`created_at`),
  KEY `idx_leads_phone` (`phone`),
  KEY `idx_leads_email` (`email`),
  KEY `leads_deleted_by_foreign` (`deleted_by`),
  KEY `leads_tenant_id_index` (`tenant_id`),
  KEY `leads_organization_id_index` (`organization_id`),
  CONSTRAINT `leads_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leads_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lead';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lease_residents`
--

DROP TABLE IF EXISTS `lease_residents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lease_residents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lease_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL COMMENT 'Nếu cư dân có tài khoản → liên kết để theo dõi hóa đơn/ticket',
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_resident_lease` (`lease_id`),
  KEY `idx_lease_residents_user` (`user_id`),
  CONSTRAINT `fk_resident_lease` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_resident_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cư dân kèm theo hợp đồng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lease_service_set_items`
--

DROP TABLE IF EXISTS `lease_service_set_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lease_service_set_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lease_service_set_id` bigint unsigned NOT NULL COMMENT 'ID bộ dịch vụ',
  `service_id` bigint unsigned NOT NULL COMMENT 'ID dịch vụ',
  `price` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Giá dịch vụ',
  `meta_json` json DEFAULT NULL COMMENT 'Thông tin bổ sung (JSON)',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT 'Thứ tự sắp xếp',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_set_service` (`lease_service_set_id`,`service_id`),
  KEY `lease_service_set_items_lease_service_set_id_index` (`lease_service_set_id`),
  KEY `lease_service_set_items_service_id_index` (`service_id`),
  CONSTRAINT `lease_service_set_items_lease_service_set_id_foreign` FOREIGN KEY (`lease_service_set_id`) REFERENCES `lease_service_sets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lease_service_set_items_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lease_service_sets`
--

DROP TABLE IF EXISTS `lease_service_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lease_service_sets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned DEFAULT NULL COMMENT 'Tổ chức sở hữu bộ dịch vụ (null = bộ dịch vụ hệ thống)',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên bộ dịch vụ',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Mô tả bộ dịch vụ',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Có phải bộ dịch vụ mặc định của tổ chức không',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_lease_service_set_org_name` (`organization_id`,`name`,`deleted_at`),
  KEY `lease_service_sets_organization_id_is_default_index` (`organization_id`),
  KEY `lease_service_sets_organization_id_index` (`organization_id`),
  CONSTRAINT `lease_service_sets_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `leases`
--

DROP TABLE IF EXISTS `leases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `unit_id` bigint unsigned NOT NULL,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `agent_id` bigint unsigned DEFAULT NULL,
  `booking_id` bigint unsigned DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `termination_date` date DEFAULT NULL,
  `termination_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rent_amount` decimal(12,2) NOT NULL,
  `deposit_amount` decimal(12,2) DEFAULT '0.00',
  `payment_cycle_id` bigint unsigned DEFAULT NULL COMMENT 'ID chu kỳ thanh toán của hợp đồng',
  `lease_services_id` bigint unsigned DEFAULT NULL COMMENT 'ID bộ dịch vụ mặc định',
  `status` enum('draft','active','terminated','expired') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `contract_no` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leases_contract_no_organization_id_unique` (`contract_no`,`organization_id`),
  KEY `idx_leases_unit_status` (`unit_id`,`status`),
  KEY `idx_leases_tenant` (`tenant_id`),
  KEY `fk_lease_org` (`organization_id`),
  KEY `fk_lease_agent` (`agent_id`),
  KEY `leases_deleted_by_foreign` (`deleted_by`),
  KEY `idx_leases_termination_date` (`termination_date`),
  KEY `idx_leases_deleted_at_status` (`deleted_at`,`status`),
  KEY `idx_leases_deleted_at_org_status` (`deleted_at`,`organization_id`,`status`),
  KEY `idx_leases_org_unit_deleted_status` (`organization_id`,`unit_id`,`deleted_at`,`status`),
  KEY `idx_leases_org_tenant_deleted` (`organization_id`,`tenant_id`,`deleted_at`),
  KEY `idx_leases_start_end_status` (`start_date`,`end_date`,`status`),
  KEY `idx_leases_end_date_deleted` (`end_date`,`deleted_at`),
  KEY `leases_payment_cycle_id_index` (`payment_cycle_id`),
  KEY `leases_lease_services_id_index` (`lease_services_id`),
  KEY `leases_booking_id_index` (`booking_id`),
  CONSTRAINT `fk_lease_agent` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lease_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_lease_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_lease_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `leases_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `booking_deposits` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `leases_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leases_lease_services_id_foreign` FOREIGN KEY (`lease_services_id`) REFERENCES `lease_service_sets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `leases_payment_cycle_id_foreign` FOREIGN KEY (`payment_cycle_id`) REFERENCES `payment_cycles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_lease_dates` CHECK ((`end_date` > `start_date`)),
  CONSTRAINT `chk_lease_rent` CHECK ((`rent_amount` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hợp đồng thuê';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'VN',
  `province_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ward_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ward` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `postal_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_loc_province` (`province_code`),
  KEY `fk_loc_district` (`district_code`),
  KEY `fk_loc_ward` (`ward_code`),
  KEY `idx_loc_codes` (`country_code`,`province_code`,`district_code`,`ward_code`),
  KEY `locations_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `fk_loc_country` FOREIGN KEY (`country_code`) REFERENCES `geo_countries` (`code`) ON DELETE SET NULL,
  CONSTRAINT `fk_loc_district` FOREIGN KEY (`district_code`) REFERENCES `geo_districts` (`code`) ON DELETE SET NULL,
  CONSTRAINT `fk_loc_province` FOREIGN KEY (`province_code`) REFERENCES `geo_provinces` (`code`) ON DELETE SET NULL,
  CONSTRAINT `fk_loc_ward` FOREIGN KEY (`ward_code`) REFERENCES `geo_wards` (`code`) ON DELETE SET NULL,
  CONSTRAINT `locations_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Địa chỉ theo chuẩn geo codes';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `locations_2025`
--

DROP TABLE IF EXISTS `locations_2025`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations_2025` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VN',
  `province_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ward_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ward` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `postal_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locations_2025_province_code_index` (`province_code`),
  KEY `locations_2025_ward_code_index` (`ward_code`),
  KEY `locations_2025_country_code_province_code_ward_code_index` (`country_code`,`province_code`,`ward_code`),
  KEY `locations_2025_deleted_by_index` (`deleted_by`),
  CONSTRAINT `fk_locations_country` FOREIGN KEY (`country_code`) REFERENCES `geo_countries` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `fk_locations_province` FOREIGN KEY (`province_code`) REFERENCES `geo_provinces_2025` (`code`) ON DELETE SET NULL,
  CONSTRAINT `fk_locations_ward` FOREIGN KEY (`ward_code`) REFERENCES `geo_wards_2025` (`code`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `master_leases`
--

DROP TABLE IF EXISTS `master_leases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_leases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `landlord_user_id` bigint unsigned DEFAULT NULL COMMENT 'Chủ nhà',
  `property_id` bigint unsigned NOT NULL,
  `contract_no` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL COMMENT 'Ngày bắt đầu',
  `end_date` date NOT NULL COMMENT 'Ngày kết thúc',
  `base_rent` decimal(12,2) NOT NULL COMMENT 'Tiền thuê cơ bản',
  `rent_currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND' COMMENT 'Loại tiền tệ',
  `deposit_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Tiền cọc',
  `billing_cycle` int NOT NULL DEFAULT '1' COMMENT 'Chu kỳ thanh toán (số tháng)',
  `billing_day` tinyint NOT NULL DEFAULT '5' COMMENT 'Ngày thanh toán',
  `due_in_days` tinyint NOT NULL DEFAULT '5' COMMENT 'Số ngày đến hạn',
  `revenue_share_pct` decimal(5,2) DEFAULT NULL COMMENT 'Tỷ lệ chia sẻ doanh thu %',
  `status` enum('draft','active','terminated','expired') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL COMMENT 'Người xóa',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_master_lease_contract_no` (`contract_no`),
  UNIQUE KEY `master_leases_contract_no_organization_id_unique` (`contract_no`,`organization_id`),
  KEY `idx_ml_org_status` (`organization_id`,`status`),
  KEY `idx_ml_property` (`property_id`),
  KEY `master_leases_contract_no_index` (`contract_no`),
  KEY `master_leases_start_date_end_date_index` (`start_date`,`end_date`),
  KEY `master_leases_landlord_user_id_index` (`landlord_user_id`),
  KEY `master_leases_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `master_leases_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `master_leases_landlord_user_id_foreign` FOREIGN KEY (`landlord_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `master_leases_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `master_leases_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_master_lease_dates` CHECK ((`end_date` > `start_date`)),
  CONSTRAINT `chk_master_lease_rent` CHECK ((`base_rent` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `meter_readings`
--

DROP TABLE IF EXISTS `meter_readings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meter_readings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `meter_id` bigint unsigned NOT NULL,
  `reading_date` date NOT NULL,
  `value` decimal(12,3) NOT NULL,
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taken_by` bigint unsigned NOT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_meter_date` (`meter_id`,`reading_date`),
  KEY `fk_mr_user` (`taken_by`),
  CONSTRAINT `fk_mr_meter` FOREIGN KEY (`meter_id`) REFERENCES `meters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mr_user` FOREIGN KEY (`taken_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_meter_reading_value` CHECK ((`value` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chỉ số công tơ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `meters`
--

DROP TABLE IF EXISTS `meters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint unsigned NOT NULL,
  `unit_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned NOT NULL,
  `serial_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `installed_at` date DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_meter_serial_no` (`serial_no`),
  UNIQUE KEY `uq_meter_service_unit_property` (`service_id`,`unit_id`,`property_id`),
  KEY `fk_meter_property` (`property_id`),
  KEY `fk_meter_service` (`service_id`),
  KEY `meters_deleted_by_foreign` (`deleted_by`),
  KEY `fk_meter_unit` (`unit_id`),
  CONSTRAINT `fk_meter_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_meter_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meters_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `meters_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Đồng hồ/công tơ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=257 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notification_channels`
--

DROP TABLE IF EXISTS `notification_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_channels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_code` (`key_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kênh thông báo';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `audit_log_id` bigint unsigned DEFAULT NULL COMMENT 'Link đến audit_logs (source of truth)',
  `channel_id` bigint unsigned NOT NULL,
  `to_user_id` bigint unsigned NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('queued','sent','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'queued',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_status` (`status`,`created_at`),
  KEY `fk_ntf_channel` (`channel_id`),
  KEY `fk_ntf_user` (`to_user_id`),
  KEY `idx_notifications_user_status_created` (`to_user_id`,`status`,`created_at`),
  KEY `idx_notification_audit_log` (`audit_log_id`),
  CONSTRAINT `fk_notification_audit_log` FOREIGN KEY (`audit_log_id`) REFERENCES `audit_logs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ntf_channel` FOREIGN KEY (`channel_id`) REFERENCES `notification_channels` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ntf_user` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Thông báo';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `organization_banking`
--

DROP TABLE IF EXISTS `organization_banking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organization_banking` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `sepay_bank_id` bigint unsigned DEFAULT NULL,
  `account_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_org_banking_account` (`organization_id`,`account_number`),
  KEY `organization_banking_organization_id_index` (`organization_id`),
  KEY `organization_banking_is_active_index` (`is_active`),
  KEY `organization_banking_is_default_index` (`is_default`),
  KEY `organization_banking_sepay_bank_id_index` (`sepay_bank_id`),
  CONSTRAINT `organization_banking_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `organization_banking_sepay_bank_id_foreign` FOREIGN KEY (`sepay_bank_id`) REFERENCES `sepay_banks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `organization_email_settings`
--

DROP TABLE IF EXISTS `organization_email_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organization_email_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL COMMENT 'ID tổ chức (1-1 relationship)',
  `mail_username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SMTP username for organization email',
  `mail_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Encrypted SMTP password for organization email',
  `mail_from_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'From address for organization emails',
  `mail_host` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SMTP host',
  `mail_port` int DEFAULT NULL COMMENT 'SMTP port',
  `mail_encryption` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SMTP encryption (ssl/tls)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organization_email_settings_organization_id_unique` (`organization_id`),
  KEY `organization_email_settings_organization_id_index` (`organization_id`),
  CONSTRAINT `organization_email_settings_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `organization_subscriptions`
--

DROP TABLE IF EXISTS `organization_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organization_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned NOT NULL,
  `status` enum('trial','active','expired','cancelled','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'trial' COMMENT 'Trạng thái',
  `current_period_start` timestamp NULL DEFAULT NULL COMMENT 'Bắt đầu chu kỳ hiện tại',
  `current_period_end` timestamp NULL DEFAULT NULL COMMENT 'Kết thúc chu kỳ hiện tại',
  `payment_cycle` enum('monthly','yearly') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Chu kỳ thanh toán',
  `payment_gateway` enum('vnpay','momo','sepay','manual') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cổng thanh toán',
  `gateway_subscription_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID subscription từ gateway',
  `gateway_customer_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID customer từ gateway',
  `auto_renew` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Tự động gia hạn',
  `cancelled_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm hủy',
  `metadata` json DEFAULT NULL COMMENT 'Dữ liệu bổ sung',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `organization_subscriptions_organization_id_index` (`organization_id`),
  KEY `organization_subscriptions_plan_id_index` (`plan_id`),
  KEY `organization_subscriptions_status_index` (`status`),
  KEY `organization_subscriptions_current_period_end_index` (`current_period_end`),
  KEY `organization_subscriptions_organization_id_status_index` (`organization_id`,`status`),
  KEY `organization_subscriptions_status_current_period_end_index` (`status`,`current_period_end`),
  CONSTRAINT `organization_subscriptions_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `organization_subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `organization_user_capabilities`
--

DROP TABLE IF EXISTS `organization_user_capabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organization_user_capabilities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_user_id` bigint unsigned NOT NULL,
  `capability_id` bigint unsigned NOT NULL,
  `granted` tinyint(1) NOT NULL DEFAULT '1',
  `granted_by` bigint unsigned DEFAULT NULL,
  `granted_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_org_user_cap` (`organization_user_id`,`capability_id`),
  KEY `organization_user_capabilities_organization_user_id_index` (`organization_user_id`),
  KEY `organization_user_capabilities_capability_id_index` (`capability_id`),
  KEY `organization_user_capabilities_granted_index` (`granted`),
  KEY `organization_user_capabilities_granted_by_foreign` (`granted_by`),
  CONSTRAINT `organization_user_capabilities_capability_id_foreign` FOREIGN KEY (`capability_id`) REFERENCES `capabilities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `organization_user_capabilities_granted_by_foreign` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `organization_user_capabilities_organization_user_id_foreign` FOREIGN KEY (`organization_user_id`) REFERENCES `organization_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `organization_users`
--

DROP TABLE IF EXISTS `organization_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organization_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_org_user_role` (`organization_id`,`user_id`,`role_id`),
  KEY `fk_ou_user` (`user_id`),
  KEY `fk_ou_role` (`role_id`),
  KEY `organization_users_deleted_at_index` (`deleted_at`),
  CONSTRAINT `fk_ou_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ou_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ou_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Thành viên theo tổ chức & vai trò';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `organizations`
--

DROP TABLE IF EXISTS `organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organizations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tax_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `first_trial_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm đầu tiên sử dụng trial period',
  `has_ever_paid` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Đã từng thanh toán thành công ít nhất 1 lần hay chưa',
  `paid_subscriptions_count` int NOT NULL DEFAULT '0' COMMENT 'Số lần đã thanh toán subscription thành công',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `organizations_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `organizations_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tổ chức/đơn vị vận hành';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_cycles`
--

DROP TABLE IF EXISTS `payment_cycles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_cycles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned DEFAULT NULL COMMENT 'Tổ chức sở hữu chu kỳ (null = chu kỳ hệ thống)',
  `cycle_type` enum('monthly','quarterly','yearly','custom') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly' COMMENT 'Loại chu kỳ',
  `billing_day` int NOT NULL,
  `custom_months` tinyint DEFAULT NULL COMMENT 'Số tháng tùy chỉnh (1-60), chỉ dùng khi cycle_type = custom',
  `notes` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ghi chú về chu kỳ thanh toán',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Có phải chu kỳ mặc định của tổ chức không',
  `payment_due_hours` int DEFAULT '4320' COMMENT 'Thời gian chờ thanh toán cho booking deposit (đơn vị: phút). Mặc định: 4320 phút = 72 giờ = 3 ngày',
  `invoice_timing` enum('start_of_cycle','end_of_cycle') COLLATE utf8mb4_unicode_ci DEFAULT 'end_of_cycle' COMMENT 'Thời điểm tạo hóa đơn: start_of_cycle = đầu chu kỳ (cộng vào hóa đơn tạo hợp đồng), end_of_cycle = cuối chu kỳ (không cộng)',
  `invoice_payment_days` int DEFAULT '30' COMMENT 'Số ngày từ issue_date đến due_date cho hóa đơn. Mặc định: 30 ngày',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_cycles_organization_id_cycle_type_index` (`organization_id`,`cycle_type`),
  KEY `payment_cycles_organization_id_is_default_index` (`organization_id`,`is_default`),
  CONSTRAINT `payment_cycles_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_cycle_billing_day` CHECK (((`billing_day` >= 1) and (`billing_day` <= 28)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_code` (`key_code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Phương thức thanh toán';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_tokens`
--

DROP TABLE IF EXISTS `payment_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_tokens_token_unique` (`token`),
  KEY `payment_tokens_token_index` (`token`),
  KEY `payment_tokens_invoice_id_index` (`invoice_id`),
  CONSTRAINT `payment_tokens_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_token_expires` CHECK ((`expires_at` > `created_at`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `method_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_at` datetime NOT NULL,
  `txn_ref` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','success','failed','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payer_user_id` bigint unsigned DEFAULT NULL,
  `lead_id` bigint unsigned DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payments_invoice` (`invoice_id`),
  KEY `idx_payments_status` (`status`),
  KEY `fk_pay_method` (`method_id`),
  KEY `fk_pay_user` (`payer_user_id`),
  KEY `payments_deleted_by_foreign` (`deleted_by`),
  KEY `idx_payments_invoice_deleted_status` (`invoice_id`,`deleted_at`,`status`),
  KEY `idx_payments_invoice_status_amount` (`invoice_id`,`status`,`amount`),
  KEY `idx_payments_paid_at_status` (`paid_at`,`status`),
  KEY `idx_payments_lead` (`lead_id`),
  CONSTRAINT `fk_pay_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_method` FOREIGN KEY (`method_id`) REFERENCES `payment_methods` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pay_user` FOREIGN KEY (`payer_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `payments_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_payment_amount_positive` CHECK ((`amount` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Thanh toán';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payroll_cycles`
--

DROP TABLE IF EXISTS `payroll_cycles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_cycles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `period_month` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','locked','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `locked_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_payroll_cycles_org_period` (`organization_id`,`period_month`,`deleted_at`),
  KEY `payroll_cycles_organization_id_period_month_index` (`organization_id`,`period_month`),
  KEY `payroll_cycles_deleted_at_index` (`deleted_at`),
  CONSTRAINT `payroll_cycles_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payroll_payslip_items`
--

DROP TABLE IF EXISTS `payroll_payslip_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_payslip_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payroll_payslip_id` bigint unsigned NOT NULL,
  `item_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sign` int NOT NULL DEFAULT '1',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `ref_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_id` bigint unsigned DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_payslip_items_payroll_payslip_id_index` (`payroll_payslip_id`),
  KEY `payroll_payslip_items_payroll_payslip_id_item_type_index` (`payroll_payslip_id`,`item_type`),
  CONSTRAINT `payroll_payslip_items_payroll_payslip_id_foreign` FOREIGN KEY (`payroll_payslip_id`) REFERENCES `payroll_payslips` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_payslip_item_amount_positive` CHECK ((`amount` >= 0)),
  CONSTRAINT `chk_payslip_item_sign` CHECK ((`sign` in (1,-(1))))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payroll_payslips`
--

DROP TABLE IF EXISTS `payroll_payslips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_payslips` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payroll_cycle_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `gross_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `deduction_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `net_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_payslip_cycle_user` (`payroll_cycle_id`,`user_id`,`deleted_at`),
  KEY `payroll_payslips_user_id_foreign` (`user_id`),
  KEY `payroll_payslips_payroll_cycle_id_user_id_index` (`payroll_cycle_id`,`user_id`),
  KEY `payroll_payslips_deleted_at_index` (`deleted_at`),
  CONSTRAINT `payroll_payslips_payroll_cycle_id_foreign` FOREIGN KEY (`payroll_cycle_id`) REFERENCES `payroll_cycles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_payslips_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_payslip_amounts_positive` CHECK (((`gross_amount` >= 0) and (`deduction_amount` >= 0) and (`net_amount` >= 0))),
  CONSTRAINT `chk_payslip_net_amount` CHECK ((`net_amount` = (`gross_amount` - `deduction_amount`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `plan_features`
--

DROP TABLE IF EXISTS `plan_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plan_features` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` bigint unsigned NOT NULL,
  `feature_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Khóa tính năng (max_properties, enable_reports)',
  `feature_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên hiển thị',
  `feature_value` json NOT NULL COMMENT 'Giá trị (limit: {"limit": 10}, boolean: {"enabled": true})',
  `feature_type` enum('limit','boolean','json') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Loại tính năng',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_plan_feature_key` (`plan_id`,`feature_key`),
  KEY `plan_features_plan_id_index` (`plan_id`),
  KEY `plan_features_feature_key_index` (`feature_key`),
  KEY `plan_features_plan_id_feature_key_index` (`plan_id`,`feature_key`),
  CONSTRAINT `plan_features_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `properties`
--

DROP TABLE IF EXISTS `properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `properties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `property_type_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `location_id_2025` bigint unsigned DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `total_floors` int DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `payment_cycle_id` bigint unsigned DEFAULT NULL COMMENT 'ID chu kỳ thanh toán của bất động sản',
  `lease_services_id` bigint unsigned DEFAULT NULL COMMENT 'ID bộ dịch vụ mặc định',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_properties_org` (`organization_id`),
  KEY `fk_properties_location` (`location_id`),
  KEY `properties_property_type_id_index` (`property_type_id`),
  KEY `properties_deleted_by_foreign` (`deleted_by`),
  KEY `fk_properties_location2025` (`location_id_2025`),
  KEY `idx_properties_deleted_at_org` (`deleted_at`,`organization_id`),
  KEY `idx_properties_org_deleted` (`organization_id`,`deleted_at`),
  KEY `properties_payment_cycle_id_index` (`payment_cycle_id`),
  KEY `properties_lease_services_id_index` (`lease_services_id`),
  CONSTRAINT `fk_properties_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_properties_location2025` FOREIGN KEY (`location_id_2025`) REFERENCES `locations_2025` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_properties_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `properties_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `properties_lease_services_id_foreign` FOREIGN KEY (`lease_services_id`) REFERENCES `lease_service_sets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `properties_payment_cycle_id_foreign` FOREIGN KEY (`payment_cycle_id`) REFERENCES `payment_cycles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `properties_property_type_id_foreign` FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tòa nhà/Tài sản';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `properties_user`
--

DROP TABLE IF EXISTS `properties_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `properties_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'agent',
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_property_user` (`property_id`,`user_id`),
  KEY `idx_property_user_user` (`user_id`),
  KEY `properties_user_updated_by_foreign` (`updated_by`),
  KEY `properties_user_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `properties_user_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `properties_user_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `properties_user_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `properties_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `property_types`
--

DROP TABLE IF EXISTS `property_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned DEFAULT NULL,
  `key_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '1: Active, 0: Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `property_types_key_code_unique` (`key_code`),
  KEY `property_types_deleted_by_foreign` (`deleted_by`),
  KEY `property_types_organization_id_index` (`organization_id`),
  CONSTRAINT `property_types_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `property_types_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `review_replies`
--

DROP TABLE IF EXISTS `review_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_replies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `review_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `parent_reply_id` bigint unsigned DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_type` enum('tenant','manager','agent','owner') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_replies_user_id_foreign` (`user_id`),
  KEY `review_replies_parent_reply_id_foreign` (`parent_reply_id`),
  KEY `review_replies_deleted_by_foreign` (`deleted_by`),
  KEY `review_replies_review_id_parent_reply_id_index` (`review_id`,`parent_reply_id`),
  KEY `idx_review_replies_review_deleted` (`review_id`,`deleted_at`),
  CONSTRAINT `review_replies_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `review_replies_parent_reply_id_foreign` FOREIGN KEY (`parent_reply_id`) REFERENCES `review_replies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_replies_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_replies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `unit_id` bigint unsigned NOT NULL,
  `lease_id` bigint unsigned NOT NULL,
  `tenant_id` bigint unsigned NOT NULL,
  `overall_rating` decimal(2,1) NOT NULL,
  `location_rating` decimal(2,1) DEFAULT NULL,
  `quality_rating` decimal(2,1) DEFAULT NULL,
  `service_rating` decimal(2,1) DEFAULT NULL,
  `price_rating` decimal(2,1) DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `highlights` json DEFAULT NULL,
  `recommend` enum('yes','maybe','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `helpful_count` int NOT NULL DEFAULT '0',
  `view_count` int NOT NULL DEFAULT '0',
  `status` enum('published','hidden') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reviews_lease_id_foreign` (`lease_id`),
  KEY `reviews_deleted_by_foreign` (`deleted_by`),
  KEY `reviews_tenant_id_status_index` (`tenant_id`,`status`),
  KEY `reviews_unit_id_status_index` (`unit_id`,`status`),
  KEY `reviews_overall_rating_index` (`overall_rating`),
  KEY `idx_reviews_deleted_at_status` (`deleted_at`,`status`),
  KEY `idx_reviews_deleted_at_unit` (`deleted_at`,`unit_id`),
  KEY `idx_reviews_org_deleted_status` (`organization_id`,`deleted_at`,`status`),
  KEY `idx_reviews_org_unit_deleted` (`organization_id`,`unit_id`,`deleted_at`),
  KEY `idx_reviews_unit_lease_deleted` (`unit_id`,`lease_id`,`deleted_at`),
  KEY `idx_reviews_tenant_deleted_status` (`tenant_id`,`deleted_at`,`status`),
  KEY `idx_reviews_unit_deleted_rating` (`unit_id`,`deleted_at`,`overall_rating`,`created_at`),
  CONSTRAINT `reviews_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `reviews_lease_id_foreign` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_review_overall_rating` CHECK (((`overall_rating` >= 0.0) and (`overall_rating` <= 5.0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_code` (`key_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Vai trò';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `salary_advances`
--

DROP TABLE IF EXISTS `salary_advances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_advances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND',
  `advance_date` date NOT NULL,
  `expected_repayment_date` date NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected','repaid','partially_repaid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `repaid_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `remaining_amount` decimal(15,2) NOT NULL,
  `repayment_method` enum('payroll_deduction','direct_payment','installment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'payroll_deduction',
  `installment_months` int DEFAULT NULL,
  `monthly_deduction` decimal(15,2) DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint unsigned DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salary_advances_approved_by_foreign` (`approved_by`),
  KEY `salary_advances_rejected_by_foreign` (`rejected_by`),
  KEY `salary_advances_organization_id_status_index` (`organization_id`,`status`),
  KEY `salary_advances_user_id_status_index` (`user_id`,`status`),
  KEY `salary_advances_advance_date_index` (`advance_date`),
  KEY `salary_advances_expected_repayment_date_index` (`expected_repayment_date`),
  KEY `salary_advances_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `salary_advances_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `salary_advances_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `salary_advances_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `salary_advances_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `salary_advances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_advance_remaining_amount` CHECK ((`remaining_amount` = (`amount` - `repaid_amount`))),
  CONSTRAINT `chk_advance_repaid_amount` CHECK (((`repaid_amount` >= 0) and (`repaid_amount` <= `amount`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `salary_contracts`
--

DROP TABLE IF EXISTS `salary_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_contracts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `base_salary` decimal(15,2) NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND',
  `pay_cycle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `pay_day` int NOT NULL DEFAULT '1',
  `allowances_json` json DEFAULT NULL,
  `kpi_target_json` json DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `status` enum('active','inactive','terminated') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salary_contracts_deleted_by_foreign` (`deleted_by`),
  KEY `salary_contracts_organization_id_status_index` (`organization_id`,`status`),
  KEY `salary_contracts_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `salary_contracts_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `salary_contracts_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `salary_contracts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sepay_banks`
--

DROP TABLE IF EXISTS `sepay_banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sepay_banks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên đầy đủ của ngân hàng',
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mã ngân hàng',
  `bin` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'BIN code của ngân hàng',
  `short_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên viết tắt của ngân hàng',
  `supported` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Có được hỗ trợ bởi SePay hay không',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sepay_banks_code_unique` (`code`),
  UNIQUE KEY `sepay_banks_bin_unique` (`bin`),
  KEY `sepay_banks_code_supported_index` (`code`,`supported`),
  KEY `sepay_banks_bin_index` (`bin`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sequences`
--

DROP TABLE IF EXISTS `sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sequences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sequence_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_value` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sequences_sequence_key_unique` (`sequence_key`),
  KEY `sequences_sequence_key_index` (`sequence_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned DEFAULT NULL,
  `key_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pricing_type` enum('fixed','per_unit','tiered') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'fixed',
  `unit_label` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `services_key_code_org_unique` (`key_code`,`organization_id`),
  KEY `services_deleted_by_foreign` (`deleted_by`),
  KEY `services_organization_id_index` (`organization_id`),
  CONSTRAINT `services_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `services_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh mục dịch vụ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subscription_invoices`
--

DROP TABLE IF EXISTS `subscription_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_subscription_id` bigint unsigned NOT NULL,
  `invoice_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Số hóa đơn',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền',
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND' COMMENT 'Tiền tệ',
  `status` enum('pending','paid','failed','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái',
  `due_date` date DEFAULT NULL COMMENT 'Ngày đáo hạn',
  `paid_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm thanh toán',
  `payment_method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Phương thức thanh toán',
  `gateway_transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID giao dịch từ gateway',
  `metadata` json DEFAULT NULL COMMENT 'Dữ liệu bổ sung',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_invoices_invoice_number_unique` (`invoice_number`),
  KEY `subscription_invoices_organization_subscription_id_index` (`organization_subscription_id`),
  KEY `subscription_invoices_invoice_number_index` (`invoice_number`),
  KEY `subscription_invoices_status_index` (`status`),
  KEY `subscription_invoices_due_date_index` (`due_date`),
  KEY `subscription_invoices_status_due_date_index` (`status`,`due_date`),
  CONSTRAINT `subscription_invoices_organization_subscription_id_foreign` FOREIGN KEY (`organization_subscription_id`) REFERENCES `organization_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subscription_plans`
--

DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mã gói (FREE, STARTER, PRO, etc.)',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên gói',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Mô tả',
  `price_monthly` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Giá tháng',
  `price_yearly` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Giá năm',
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND' COMMENT 'Tiền tệ',
  `trial_days` int NOT NULL DEFAULT '0' COMMENT 'Số ngày dùng thử',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Trạng thái kích hoạt',
  `is_custom` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Gói tùy chỉnh',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT 'Thứ tự hiển thị',
  `metadata` json DEFAULT NULL COMMENT 'Dữ liệu bổ sung',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_plans_code_unique` (`code`),
  KEY `subscription_plans_code_index` (`code`),
  KEY `subscription_plans_is_active_index` (`is_active`),
  KEY `subscription_plans_is_custom_index` (`is_custom`),
  KEY `subscription_plans_sort_order_index` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket_logs`
--

DROP TABLE IF EXISTS `ticket_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `actor_id` bigint unsigned NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cost_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Chi phí phát sinh (để có thể trừ vào cọc)',
  `cost_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mô tả chi phí',
  `warranty_period_days` int DEFAULT NULL COMMENT 'Thời hạn bảo hành (ngày)',
  `warranty_expires_at` timestamp NULL DEFAULT NULL COMMENT 'Ngày hết hạn bảo hành',
  `charge_to` enum('none','tenant_deposit','tenant_invoice','landlord','self_pay_vendor') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'none' COMMENT 'Hướng hạch toán',
  `linked_invoice_id` bigint unsigned DEFAULT NULL COMMENT 'Hóa đơn liên quan (nếu charge_to=tenant_invoice)',
  `vendor_id` bigint unsigned DEFAULT NULL COMMENT 'ID nhà cung cấp',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tkl_actor` (`actor_id`),
  KEY `fk_tkl_invoice` (`linked_invoice_id`),
  KEY `idx_tkl_ticket_created` (`ticket_id`,`created_at`),
  KEY `idx_tkl_charge_to` (`charge_to`),
  KEY `ticket_logs_vendor_id_index` (`vendor_id`),
  KEY `idx_tkl_warranty_expires` (`warranty_expires_at`),
  KEY `ticket_logs_deleted_at_index` (`deleted_at`),
  CONSTRAINT `fk_tkl_invoice` FOREIGN KEY (`linked_invoice_id`) REFERENCES `invoices` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_tkl_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ticket_logs_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nhật ký ticket + chi phí';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket_priorities`
--

DROP TABLE IF EXISTS `ticket_priorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_priorities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_priorities_key_code_unique` (`key_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `property_id` bigint unsigned DEFAULT NULL,
  `unit_id` bigint unsigned DEFAULT NULL,
  `lease_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `priority_id` bigint unsigned NOT NULL,
  `status` enum('open','in_progress','resolved','closed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tickets_status_priority` (`status`),
  KEY `fk_tk_org` (`organization_id`),
  KEY `fk_tk_unit` (`unit_id`),
  KEY `fk_tk_lease` (`lease_id`),
  KEY `fk_tk_created` (`created_by`),
  KEY `fk_tk_assigned` (`assigned_to`),
  KEY `tickets_deleted_by_foreign` (`deleted_by`),
  KEY `tickets_cancelled_by_foreign` (`cancelled_by`),
  KEY `tickets_priority_id_index` (`priority_id`),
  KEY `idx_tickets_deleted_at_status` (`deleted_at`,`status`),
  KEY `idx_tickets_deleted_at_created` (`deleted_at`,`created_at`),
  KEY `idx_tickets_deleted_at_priority_id` (`deleted_at`,`priority_id`),
  KEY `idx_tickets_org_deleted_status` (`organization_id`,`deleted_at`,`status`),
  KEY `idx_tickets_org_unit_deleted` (`organization_id`,`unit_id`,`deleted_at`),
  KEY `idx_tickets_organization_id` (`organization_id`),
  KEY `idx_tickets_unit_lease_deleted` (`unit_id`,`lease_id`,`deleted_at`),
  KEY `tickets_property_id_index` (`property_id`),
  CONSTRAINT `fk_tk_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tk_created` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_tk_lease` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_tk_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_tk_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `tickets_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_priority_id_foreign` FOREIGN KEY (`priority_id`) REFERENCES `ticket_priorities` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tickets_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ticket bảo trì/sự cố';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unit_amenities`
--

DROP TABLE IF EXISTS `unit_amenities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit_amenities` (
  `unit_id` bigint unsigned NOT NULL,
  `amenity_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`unit_id`,`amenity_id`),
  KEY `fk_ua_amen` (`amenity_id`),
  CONSTRAINT `fk_ua_amen` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ua_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tiện ích gắn cho phòng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint unsigned NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `floor` int DEFAULT NULL,
  `area_m2` decimal(10,2) DEFAULT NULL,
  `unit_type` enum('room','apartment','dorm','shared') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'room',
  `base_rent` decimal(12,2) NOT NULL,
  `deposit_amount` decimal(12,2) DEFAULT '0.00',
  `max_occupancy` int DEFAULT '1',
  `status` enum('available','reserved','occupied','maintenance') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_property_code` (`property_id`,`code`),
  KEY `idx_units_status` (`status`),
  KEY `units_deleted_by_foreign` (`deleted_by`),
  KEY `idx_units_deleted_at_status` (`deleted_at`,`status`),
  KEY `idx_units_deleted_at_property` (`deleted_at`,`property_id`),
  KEY `idx_units_property_status` (`property_id`,`status`),
  CONSTRAINT `fk_units_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `units_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_unit_base_rent` CHECK ((`base_rent` > 0)),
  CONSTRAINT `chk_unit_deposit` CHECK ((`deposit_amount` >= 0)),
  CONSTRAINT `chk_unit_max_occupancy` CHECK ((`max_occupancy` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Phòng/căn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_notification_preferences`
--

DROP TABLE IF EXISTS `user_notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_notification_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `entity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Loại entity: lease, invoice, payment, ticket, ticketlog, review',
  `email_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Bật/tắt gửi email cho loại thông báo này',
  `in_app_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Bật/tắt thông báo in-app cho loại thông báo này',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_notification_preferences_user_id_entity_type_unique` (`user_id`,`entity_type`),
  KEY `user_notification_preferences_user_id_index` (`user_id`),
  KEY `user_notification_preferences_entity_type_index` (`entity_type`),
  CONSTRAINT `user_notification_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_profiles` (
  `user_id` bigint unsigned NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ảnh đại diện',
  `dob` date DEFAULT NULL,
  `gender` enum('male','female','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'other',
  `id_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã số thuế',
  `id_issued_at` date DEFAULT NULL,
  `id_card_place` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nơi cấp CMND/CCCD',
  `id_images` json DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sepay_bank_id` bigint unsigned DEFAULT NULL COMMENT 'Ngân hàng SePay',
  `account_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số tài khoản',
  `account_holder_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tên chủ tài khoản',
  `branch_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tên chi nhánh',
  `branch_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã chi nhánh',
  `swift_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Swift Code',
  `banking_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú ngân hàng',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `idx_user_profiles_id_number` (`id_number`),
  UNIQUE KEY `idx_user_profiles_bank_account` (`sepay_bank_id`,`account_number`),
  CONSTRAINT `fk_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_profile_banking_info` CHECK (((`account_number` is null) or ((`sepay_bank_id` is not null) and (`account_holder_name` is not null))))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hồ sơ cơ bản người dùng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `google_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `users_google_id_unique` (`google_id`),
  KEY `users_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `users_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Người dùng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `sepay_bank_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên nhà cung cấp',
  `tax_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã số thuế',
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số điện thoại',
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Email',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Địa chỉ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL COMMENT 'Người xóa',
  `account_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số tài khoản',
  `account_holder_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tên chủ tài khoản',
  `branch_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tên chi nhánh',
  `branch_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã chi nhánh',
  `swift_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã SWIFT',
  `banking_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú thông tin ngân hàng',
  `contact_person` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Người liên hệ',
  `contact_phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số điện thoại liên hệ',
  `contact_email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Email liên hệ',
  `business_license` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Giấy phép kinh doanh',
  `vendor_type` enum('individual','company') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'company' COMMENT 'Loại nhà cung cấp',
  `status` enum('active','inactive','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT 'Trạng thái',
  PRIMARY KEY (`id`),
  KEY `idx_vendors_org` (`organization_id`),
  KEY `vendors_name_index` (`name`),
  KEY `vendors_tax_code_index` (`tax_code`),
  KEY `vendors_phone_index` (`phone`),
  KEY `vendors_email_index` (`email`),
  KEY `vendors_deleted_by_foreign` (`deleted_by`),
  KEY `vendors_account_number_index` (`account_number`),
  KEY `vendors_vendor_type_index` (`vendor_type`),
  KEY `vendors_status_index` (`status`),
  KEY `vendors_sepay_bank_id_index` (`sepay_bank_id`),
  CONSTRAINT `vendors_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendors_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendors_sepay_bank_id_foreign` FOREIGN KEY (`sepay_bank_id`) REFERENCES `sepay_banks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `viewings`
--

DROP TABLE IF EXISTS `viewings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `viewings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID lịch xem phòng',
  `lead_id` bigint unsigned DEFAULT NULL COMMENT 'Lead nếu khách chưa có account',
  `tenant_id` bigint unsigned DEFAULT NULL,
  `property_id` bigint unsigned DEFAULT NULL,
  `agent_id` bigint unsigned NOT NULL,
  `organization_id` bigint unsigned NOT NULL,
  `unit_id` bigint unsigned DEFAULT NULL,
  `lead_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lead_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lead_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `schedule_at` datetime NOT NULL COMMENT 'Thời điểm hẹn',
  `status` enum('requested','confirmed','done','no_show','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'requested' COMMENT 'Trạng thái',
  `result_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Kết quả buổi xem',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `checklist` json DEFAULT NULL COMMENT 'Danh sách kiểm tra khi xem phòng',
  `feedback_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Phản hồi sau khi xem',
  `feedback_rating` int DEFAULT NULL COMMENT 'Đánh giá (1-5 sao)',
  `virtual_viewing_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Link xem phòng ảo (Zoom, Meet, etc)',
  `is_virtual` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Có phải xem phòng ảo không',
  `route_optimized` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Đã tối ưu tuyến đường',
  `route_data` json DEFAULT NULL COMMENT 'Dữ liệu tuyến đường tối ưu',
  PRIMARY KEY (`id`),
  KEY `fk_view_lead` (`lead_id`),
  KEY `idx_viewings_status_time` (`status`,`schedule_at`),
  KEY `idx_viewings_agent_time` (`agent_id`,`schedule_at`),
  KEY `viewings_deleted_by_foreign` (`deleted_by`),
  KEY `viewings_organization_id_index` (`organization_id`),
  KEY `viewings_unit_id_index` (`unit_id`),
  KEY `viewings_property_id_index` (`property_id`),
  KEY `viewings_tenant_id_index` (`tenant_id`),
  KEY `idx_viewings_deleted_at_status_schedule` (`deleted_at`,`status`,`schedule_at`),
  KEY `idx_viewings_org_deleted_status` (`organization_id`,`deleted_at`,`status`),
  KEY `idx_viewings_org_agent_schedule` (`organization_id`,`agent_id`,`schedule_at`),
  KEY `idx_viewings_schedule_status` (`schedule_at`,`status`),
  CONSTRAINT `fk_view_agent` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_view_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `viewings_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `viewings_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `viewings_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `viewings_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `viewings_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch xem phòng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `webhook_logs`
--

DROP TABLE IF EXISTS `webhook_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sepay_transaction_id` bigint NOT NULL COMMENT 'ID giao dịch từ SePay',
  `gateway` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_date` datetime NOT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transfer_type` enum('in','out') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in' COMMENT 'Loại giao dịch',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền giao dịch',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Nội dung chuyển khoản',
  `reference_code` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã tham chiếu',
  `invoice_id` bigint unsigned DEFAULT NULL COMMENT 'ID hóa đơn tìm được',
  `company_invoice_id` bigint unsigned DEFAULT NULL COMMENT 'ID hóa đơn công ty',
  `cashout_id` bigint unsigned DEFAULT NULL COMMENT 'ID dòng tiền chi ra',
  `payment_id` bigint unsigned DEFAULT NULL COMMENT 'ID thanh toán được tạo',
  `status` enum('pending','matched','processed','failed','duplicate') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái xử lý',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Thông báo lỗi',
  `raw_data` json DEFAULT NULL COMMENT 'Dữ liệu đầy đủ từ webhook',
  `processed_at` datetime DEFAULT NULL COMMENT 'Thời gian xử lý',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `webhook_logs_sepay_transaction_id_unique` (`sepay_transaction_id`),
  KEY `idx_webhook_logs_sepay_txn` (`sepay_transaction_id`),
  KEY `idx_webhook_logs_invoice` (`invoice_id`),
  KEY `idx_webhook_logs_payment` (`payment_id`),
  KEY `idx_webhook_logs_status` (`status`),
  KEY `idx_webhook_logs_txn_date` (`transaction_date`),
  KEY `idx_webhook_logs_created` (`created_at`),
  KEY `idx_webhook_logs_company_invoice` (`company_invoice_id`),
  KEY `idx_webhook_logs_cashout` (`cashout_id`),
  CONSTRAINT `fk_webhook_logs_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_webhook_logs_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `webhook_logs_cashout_id_foreign` FOREIGN KEY (`cashout_id`) REFERENCES `cash_outflows` (`id`) ON DELETE SET NULL,
  CONSTRAINT `webhook_logs_company_invoice_id_foreign` FOREIGN KEY (`company_invoice_id`) REFERENCES `company_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_webhook_amount` CHECK ((`amount` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-16 17:39:29
