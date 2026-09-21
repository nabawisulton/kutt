-- =====================================================================
-- KUTT SUKA MAKMUR - database.sql (FILE GENERATE OTOMATIS)
-- =====================================================================
-- Sumber kebenaran: database/migrations/*.sql + database/seeds/*.sql.
-- JANGAN sunting file ini manual. Regenerate dengan:
--     php database/export_schema.php
--
-- Cara pakai di cPanel: import file ini lewat phpMyAdmin pada database
-- yang sudah dibuat (struktur + data seed awal terpasang sekaligus).
-- PENTING: import HANYA ke database KOSONG baru - file ini memuat
-- `DROP TABLE IF EXISTS` dan akan MENGGANTI tabel yang sudah ada.
-- =====================================================================

/*M!999999\- enable the sandbox mode */

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `role` varchar(30) DEFAULT NULL,
  `action` varchar(40) NOT NULL,
  `module` varchar(40) NOT NULL DEFAULT 'SYSTEM',
  `data_id` varchar(40) DEFAULT NULL,
  `details` varchar(1000) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_time` (`timestamp`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs_ht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs_ht` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_no` varchar(30) DEFAULT NULL,
  `user_id` varchar(40) DEFAULT NULL,
  `role` varchar(30) DEFAULT NULL,
  `action` varchar(40) DEFAULT NULL,
  `module` varchar(40) DEFAULT NULL,
  `data_id` varchar(40) DEFAULT NULL,
  `details` varchar(500) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cash_savings_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_savings_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_no` varchar(40) NOT NULL,
  `name` varchar(120) NOT NULL,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cs_accounts_no` (`account_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cash_savings_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_savings_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int(10) unsigned NOT NULL,
  `transaction_no` varchar(50) NOT NULL,
  `type` enum('SETOR','TARIK','PEMBAYARAN','REFUND','PENYESUAIAN') NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `balance_before` decimal(14,2) NOT NULL,
  `balance_after` decimal(14,2) NOT NULL,
  `method` enum('KAS','TRANSFER','LAINNYA') NOT NULL DEFAULT 'KAS',
  `description` varchar(255) DEFAULT NULL,
  `reference_type` varchar(40) DEFAULT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cs_tx_no` (`transaction_no`),
  KEY `idx_cs_tx_account_date` (`account_id`,`created_at`),
  KEY `idx_cs_tx_type` (`type`),
  CONSTRAINT `fk_cs_tx_account` FOREIGN KEY (`account_id`) REFERENCES `cash_savings_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cash_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_no` varchar(30) NOT NULL COMMENT 'Nomor voucher unik, mis. KSM-2026-0001',
  `jenis_kas` enum('MASUK','KELUAR') NOT NULL,
  `account_code` varchar(10) NOT NULL COMMENT 'Akun kas/bank (1101, 1102, ...)',
  `nominal` decimal(15,2) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `tanggal_trans` date NOT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ct_voucher_no` (`voucher_no`),
  KEY `idx_ct_date` (`tanggal_trans`),
  KEY `idx_ct_jenis` (`jenis_kas`),
  KEY `fk_ct_coa` (`account_code`),
  KEY `fk_ct_user` (`created_by`),
  CONSTRAINT `fk_ct_coa` FOREIGN KEY (`account_code`) REFERENCES `chart_of_accounts` (`account_code`),
  CONSTRAINT `fk_ct_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cash_transactions_ht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_transactions_ht` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_no` varchar(30) DEFAULT NULL,
  `jenis_kas` varchar(10) DEFAULT NULL,
  `account_code` varchar(10) DEFAULT NULL,
  `nominal` decimal(18,2) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `tanggal_trans` date DEFAULT NULL,
  `created_by` varchar(40) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chart_of_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chart_of_accounts` (
  `account_code` varchar(10) NOT NULL,
  `account_name` varchar(120) NOT NULL,
  `account_category` enum('ASSET','LIABILITY','EQUITY','REVENUE','EXPENSE') NOT NULL,
  `normal_balance` enum('DEBIT','CREDIT') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`account_code`),
  KEY `idx_coa_category` (`account_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journal_id` varchar(30) NOT NULL COMMENT 'Nomor jurnal unik, mis. JR-2026-000001',
  `ref_voucher` varchar(30) DEFAULT NULL COMMENT 'Rujukan voucher/transaksi sumber',
  `tanggal` date NOT NULL,
  `account_code` varchar(10) NOT NULL,
  `debet` decimal(15,2) NOT NULL DEFAULT 0.00,
  `kredit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_je_journal_id` (`journal_id`),
  KEY `idx_je_date` (`tanggal`),
  KEY `idx_je_account` (`account_code`),
  KEY `idx_je_ref` (`ref_voucher`),
  KEY `fk_je_user` (`created_by`),
  CONSTRAINT `fk_je_coa` FOREIGN KEY (`account_code`) REFERENCES `chart_of_accounts` (`account_code`),
  CONSTRAINT `fk_je_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `journal_entries_ht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_entries_ht` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journal_no` varchar(30) DEFAULT NULL,
  `ref_voucher` varchar(30) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `account_code` varchar(10) DEFAULT NULL,
  `debet` decimal(18,2) DEFAULT NULL,
  `kredit` decimal(18,2) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_by` varchar(40) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loan_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_no` varchar(30) NOT NULL COMMENT 'Nomor bukti pembayaran unik, mis. ANG-2026-0001',
  `loan_id` bigint(20) unsigned NOT NULL,
  `schedule_id` bigint(20) unsigned DEFAULT NULL,
  `angsuran_ke` smallint(5) unsigned NOT NULL,
  `bayar_pokok` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bayar_bunga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `denda` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_bayar` decimal(15,2) NOT NULL,
  `tanggal_bayar` date NOT NULL,
  `operator_user_id` int(10) unsigned DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lp_payment_no` (`payment_no`),
  KEY `idx_lp_loan` (`loan_id`),
  KEY `idx_lp_date` (`tanggal_bayar`),
  KEY `fk_lp_schedule` (`schedule_id`),
  KEY `fk_lp_operator` (`operator_user_id`),
  CONSTRAINT `fk_lp_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `fk_lp_operator` FOREIGN KEY (`operator_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lp_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `loan_schedules` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loan_payments_ht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_payments_ht` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_no` varchar(30) DEFAULT NULL,
  `loan_no` varchar(30) DEFAULT NULL,
  `angsuran_ke` int(11) DEFAULT NULL,
  `bayar_pokok` decimal(18,2) DEFAULT NULL,
  `bayar_bunga` decimal(18,2) DEFAULT NULL,
  `denda` decimal(18,2) DEFAULT NULL,
  `total_bayar` decimal(18,2) DEFAULT NULL,
  `tanggal_bayar` date DEFAULT NULL,
  `operator_user` varchar(40) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loan_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned NOT NULL,
  `angsuran_ke` smallint(5) unsigned NOT NULL,
  `jatuh_tempo` date NOT NULL,
  `pokok_due` decimal(15,2) NOT NULL,
  `bunga_due` decimal(15,2) NOT NULL,
  `status` enum('UNPAID','PAID') NOT NULL DEFAULT 'UNPAID',
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ls_loan_no` (`loan_id`,`angsuran_ke`),
  KEY `idx_ls_due` (`jatuh_tempo`),
  KEY `idx_ls_status` (`status`),
  CONSTRAINT `fk_ls_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loan_schedules_ht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_schedules_ht` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_no` varchar(30) DEFAULT NULL,
  `angsuran_ke` int(11) DEFAULT NULL,
  `jatuh_tempo` date DEFAULT NULL,
  `pokok_due` decimal(18,2) DEFAULT NULL,
  `bunga_due` decimal(18,2) DEFAULT NULL,
  `total_due` decimal(18,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_no` varchar(30) NOT NULL COMMENT 'Nomor pinjaman unik, mis. PJM-2026-0001',
  `member_id` int(10) unsigned NOT NULL,
  `pokok_pinjaman` decimal(15,2) NOT NULL,
  `bunga_pertahun` decimal(5,2) NOT NULL DEFAULT 12.00,
  `tenor_bulan` smallint(5) unsigned NOT NULL,
  `sistem_bunga` enum('FLAT','MENURUN') NOT NULL DEFAULT 'FLAT',
  `status` enum('PENDING','APPROVED','REJECTED','DISBURSED','LUNAS') NOT NULL DEFAULT 'PENDING',
  `tanggal_pengajuan` date NOT NULL,
  `tanggal_disburse` date DEFAULT NULL,
  `keperluan` varchar(255) DEFAULT NULL,
  `agunan` varchar(255) DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_loans_loan_no` (`loan_no`),
  KEY `idx_loans_member` (`member_id`),
  KEY `idx_loans_status` (`status`),
  KEY `idx_loans_date` (`tanggal_pengajuan`),
  KEY `fk_loans_approver` (`approved_by`),
  CONSTRAINT `fk_loans_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_loans_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loans_ht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans_ht` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_no` varchar(30) DEFAULT NULL,
  `member_no` varchar(20) DEFAULT NULL,
  `pokok_pinjaman` decimal(18,2) DEFAULT NULL,
  `bunga_pertahun` decimal(9,2) DEFAULT NULL,
  `tenor_bulan` int(11) DEFAULT NULL,
  `sistem_bunga` varchar(20) DEFAULT NULL,
  `status_appr` varchar(20) DEFAULT NULL,
  `tanggal_pengajuan` date DEFAULT NULL,
  `tanggal_cair` date DEFAULT NULL,
  `keperluan` varchar(255) DEFAULT NULL,
  `agunan` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `member_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_cards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `card_serial` varchar(30) NOT NULL COMMENT 'Nomor seri kartu unik',
  `verify_code` char(32) NOT NULL,
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `issued_by` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mc_serial` (`card_serial`),
  UNIQUE KEY `uq_mc_verify` (`verify_code`),
  UNIQUE KEY `uq_mc_member_active` (`member_id`,`is_active`),
  KEY `fk_mc_user` (`issued_by`),
  CONSTRAINT `fk_mc_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mc_user` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `member_cards_ht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_cards_ht` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `card_no` varchar(30) DEFAULT NULL,
  `member_no` varchar(20) DEFAULT NULL,
  `verify_code` varchar(64) DEFAULT NULL,
  `background_type` varchar(10) DEFAULT NULL,
  `background_value` varchar(255) DEFAULT NULL,
  `background_opacity` decimal(4,2) DEFAULT NULL,
  `issued_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `member_wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_wallets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `pin_hash` varchar(255) DEFAULT NULL COMMENT 'bcrypt dari PIN 6 digit',
  `pin_attempts` int(11) NOT NULL DEFAULT 0,
  `pin_locked_until` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wallet_member` (`member_id`),
  CONSTRAINT `fk_wallet_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_no` varchar(20) NOT NULL COMMENT 'Nomor anggota unik, mis. AGT-2026-0001',
  `nia` varchar(30) DEFAULT NULL COMMENT 'Nomor Induk Anggota (legacy)',
  `nik` char(16) DEFAULT NULL,
  `full_name` varchar(120) NOT NULL,
  `birth_place` varchar(120) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('L','P') DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `occupation` varchar(80) DEFAULT NULL,
  `jabatan_internal` varchar(60) DEFAULT NULL,
  `relasi_eksternal` enum('KARYAWAN','KONSUMEN','PEMASOK','MITRA','PIHAK_LAIN','TIDAK_ADA') NOT NULL DEFAULT 'TIDAK_ADA',
  `jabatan_eksternal` varchar(60) DEFAULT NULL,
  `group_name` varchar(120) DEFAULT NULL COMMENT 'Kelompok tani',
  `status` enum('AKTIF','CALON','NONAKTIF','KELUAR') NOT NULL DEFAULT 'CALON',
  `photo_path` varchar(255) DEFAULT NULL,
  `joined_at` date DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_members_member_no` (`member_no`),
  KEY `idx_members_nik` (`nik`),
  KEY `idx_members_name` (`full_name`),
  KEY `idx_members_status` (`status`),
  KEY `fk_members_creator` (`created_by`),
  CONSTRAINT `fk_members_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `members_ht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_ht` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_no` varchar(20) DEFAULT NULL,
  `nia` varchar(30) DEFAULT NULL,
  `nik` char(16) DEFAULT NULL,
  `nama_lengkap` varchar(120) DEFAULT NULL,
  `alamat_lengkap` varchar(255) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `pekerjaan` varchar(80) DEFAULT NULL,
  `kelompok_tani` varchar(120) DEFAULT NULL,
  `status_anggota` varchar(20) DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(190) NOT NULL,
  `batch` int(10) unsigned NOT NULL,
  `executed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_migrations_name` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `news_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `slug` varchar(80) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nc_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `news_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `body` varchar(1000) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_nc_post` (`post_id`),
  KEY `idx_nc_status` (`status`),
  CONSTRAINT `fk_nc_post` FOREIGN KEY (`post_id`) REFERENCES `news_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `news_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_likes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `user_hash` char(32) NOT NULL COMMENT 'md5(ip+ua), bukan data pribadi',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nl_post_hash` (`post_id`,`user_hash`),
  CONSTRAINT `fk_nl_post` FOREIGN KEY (`post_id`) REFERENCES `news_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `news_post_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_post_tags` (
  `post_id` bigint(20) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`),
  KEY `fk_npt_tag` (`tag_id`),
  CONSTRAINT `fk_npt_post` FOREIGN KEY (`post_id`) REFERENCES `news_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_npt_tag` FOREIGN KEY (`tag_id`) REFERENCES `news_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `news_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(160) NOT NULL,
  `title` varchar(200) NOT NULL,
  `excerpt` varchar(500) DEFAULT NULL,
  `body` mediumtext DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `category` varchar(60) DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `author_id` int(10) unsigned DEFAULT NULL,
  `author_name` varchar(120) DEFAULT NULL,
  `status` enum('DRAFT','PUBLISHED') NOT NULL DEFAULT 'DRAFT',
  `views` int(10) unsigned NOT NULL DEFAULT 0,
  `likes` int(10) unsigned NOT NULL DEFAULT 0,
  `shares` int(10) unsigned NOT NULL DEFAULT 0,
  `comments_count` int(10) unsigned NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_np_slug` (`slug`),
  KEY `idx_np_status` (`status`),
  KEY `idx_np_category` (`category`),
  KEY `idx_np_published` (`published_at`),
  KEY `fk_np_user` (`created_by`),
  CONSTRAINT `fk_np_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `news_posts_ht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_posts_ht` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` varchar(30) DEFAULT NULL,
  `judul` varchar(200) DEFAULT NULL,
  `ringkasan` varchar(500) DEFAULT NULL,
  `isi` longtext DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `kategori` varchar(60) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `penulis` varchar(120) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `views` int(11) DEFAULT NULL,
  `likes` int(11) DEFAULT NULL,
  `shares` int(11) DEFAULT NULL,
  `comments_count` int(11) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_by` varchar(40) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `news_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_shares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `platform` varchar(30) DEFAULT NULL,
  `shared_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ns_post` (`post_id`),
  CONSTRAINT `fk_ns_post` FOREIGN KEY (`post_id`) REFERENCES `news_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `news_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `slug` varchar(80) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nt_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `news_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `user_hash` char(32) DEFAULT NULL,
  `viewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_nv_post` (`post_id`),
  CONSTRAINT `fk_nv_post` FOREIGN KEY (`post_id`) REFERENCES `news_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL = broadcast ke semua user',
  `role` varchar(30) DEFAULT NULL,
  `title` varchar(160) NOT NULL,
  `message` varchar(500) DEFAULT NULL,
  `type` enum('INFO','SUCCESS','WARNING','DANGER') NOT NULL DEFAULT 'INFO',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_read` (`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications_ht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications_ht` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `notif_no` varchar(30) DEFAULT NULL,
  `recipient_role` varchar(30) DEFAULT NULL,
  `recipient_user` varchar(40) DEFAULT NULL,
  `title` varchar(160) DEFAULT NULL,
  `message` varchar(500) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prt_token` (`token`),
  KEY `fk_prt_user` (`user_id`),
  CONSTRAINT `fk_prt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(40) NOT NULL COMMENT 'Kode produk unik',
  `barcode` varchar(40) DEFAULT NULL,
  `name` varchar(160) NOT NULL,
  `category` varchar(60) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(18,2) NOT NULL DEFAULT 0.00 COMMENT 'Harga jual (Rp)',
  `cost_price` decimal(18,2) DEFAULT NULL COMMENT 'Harga pokok (opsional, utk margin)',
  `stock` int(11) NOT NULL DEFAULT 0 COMMENT 'Stok fisik saat ini',
  `min_stock` int(11) NOT NULL DEFAULT 5 COMMENT 'Batas stok minimum (alert)',
  `unit` varchar(20) NOT NULL DEFAULT 'pcs',
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Tampil di marketplace publik',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_sku` (`sku`),
  KEY `idx_products_category` (`category`),
  KEY `idx_products_active` (`is_active`),
  KEY `idx_products_barcode` (`barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL bila produk dihapus',
  `product_name` varchar(160) NOT NULL COMMENT 'Snapshot nama',
  `unit_price` decimal(18,2) NOT NULL COMMENT 'Snapshot harga saat jual',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(18,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_soi_order` (`order_id`),
  KEY `idx_soi_product` (`product_id`),
  CONSTRAINT `fk_soi_order` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_soi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(30) NOT NULL COMMENT 'JUAL-YYYYMMDD-####',
  `channel` enum('MARKETPLACE','POS') NOT NULL DEFAULT 'POS',
  `buyer_name` varchar(120) NOT NULL,
  `buyer_phone` varchar(30) DEFAULT NULL,
  `buyer_note` varchar(255) DEFAULT NULL,
  `cashier_user_id` int(10) unsigned DEFAULT NULL COMMENT 'Kasir yang melayani (POS)',
  `member_id` int(10) unsigned DEFAULT NULL,
  `savings_account_id` int(10) unsigned DEFAULT NULL,
  `payment_method` enum('CASH','TRANSFER','QRIS','COD','SALDO','TABUNGAN') NOT NULL DEFAULT 'CASH',
  `status` enum('NEW','PAID','PROCESSING','COMPLETED','CANCELLED') NOT NULL DEFAULT 'NEW',
  `stock_applied` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Stok sudah dipotong?',
  `paid_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_so_order_no` (`order_no`),
  KEY `idx_so_status` (`status`),
  KEY `idx_so_channel` (`channel`),
  KEY `idx_so_created` (`created_at`),
  KEY `idx_so_cashier` (`cashier_user_id`),
  CONSTRAINT `fk_so_cashier` FOREIGN KEY (`cashier_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `savings_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `savings_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trans_no` varchar(30) NOT NULL COMMENT 'Nomor transaksi unik, mis. SVP-2026-0001',
  `member_id` int(10) unsigned NOT NULL,
  `jenis` enum('POKOK','WAJIB','SUKARELA') NOT NULL,
  `tipe_transaksi` enum('SETOR','TARIK') NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `tanggal_trans` date NOT NULL,
  `operator_user_id` int(10) unsigned DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_st_trans_no` (`trans_no`),
  KEY `idx_st_member` (`member_id`),
  KEY `idx_st_date` (`tanggal_trans`),
  KEY `idx_st_jenis` (`jenis`),
  KEY `fk_st_operator` (`operator_user_id`),
  CONSTRAINT `fk_st_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `fk_st_operator` FOREIGN KEY (`operator_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `savings_transactions_ht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `savings_transactions_ht` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trans_no` varchar(30) DEFAULT NULL,
  `member_no` varchar(20) DEFAULT NULL,
  `jenis_simpanan` varchar(20) DEFAULT NULL,
  `tipe_transaksi` varchar(10) DEFAULT NULL,
  `nominal` decimal(18,2) DEFAULT NULL,
  `tanggal_trans` date DEFAULT NULL,
  `operator_user` varchar(40) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `setting_key` varchar(60) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`setting_key`),
  KEY `fk_settings_user` (`updated_by`),
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings_ht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings_ht` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(60) DEFAULT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shu_distributions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shu_distributions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shu_no` varchar(30) NOT NULL,
  `tahun_buku` smallint(5) unsigned NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `jasa_modal` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'SHU atas simpanan',
  `jasa_anggota` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'SHU atas transaksi/jasa',
  `total_shu` decimal(15,2) NOT NULL,
  `status_pencairan` enum('PENDING','DIBAYAR','DIBATALKAN') NOT NULL DEFAULT 'PENDING',
  `paid_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shu_no` (`shu_no`),
  UNIQUE KEY `uq_shu_year_member` (`tahun_buku`,`member_id`),
  KEY `idx_shu_status` (`status_pencairan`),
  KEY `fk_shu_member` (`member_id`),
  KEY `fk_shu_user` (`created_by`),
  CONSTRAINT `fk_shu_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `fk_shu_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shu_distributions_ht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shu_distributions_ht` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shu_no` varchar(30) DEFAULT NULL,
  `tahun_buku` int(11) DEFAULT NULL,
  `member_no` varchar(20) DEFAULT NULL,
  `jasa_modal` decimal(18,2) DEFAULT NULL,
  `jasa_anggota` decimal(18,2) DEFAULT NULL,
  `total_shu` decimal(18,2) DEFAULT NULL,
  `status_pencairan` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `change_qty` int(11) NOT NULL COMMENT '+ masuk, - keluar (penjualan/koreksi)',
  `stock_after` int(11) NOT NULL,
  `reason` enum('PURCHASE','SALE','ADJUSTMENT','CANCEL') NOT NULL DEFAULT 'ADJUSTMENT',
  `reference` varchar(40) DEFAULT NULL COMMENT 'No order/adj terkait',
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sm_product` (`product_id`),
  KEY `idx_sm_created` (`created_at`),
  KEY `fk_stkmv_user` (`user_id`),
  CONSTRAINT `fk_stkmv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stkmv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `support_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `sender_role` enum('ANGGOTA','ADMIN') NOT NULL,
  `sender_user` int(10) unsigned DEFAULT NULL,
  `sender_name` varchar(120) NOT NULL,
  `body` varchar(2000) NOT NULL,
  `is_read_by_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_read_by_member` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sm_member` (`member_id`),
  KEY `idx_sm_admin_unread` (`is_read_by_admin`),
  KEY `fk_sm_user` (`sender_user`),
  CONSTRAINT `fk_sm_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sm_user` FOREIGN KEY (`sender_user`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `topup_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `topup_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `request_no` varchar(30) NOT NULL COMMENT 'TOP-YYYYMMDD-####',
  `amount` decimal(18,2) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `processed_by` int(10) unsigned DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tr_no` (`request_no`),
  KEY `idx_tr_status` (`status`),
  KEY `fk_tr_member` (`member_id`),
  KEY `fk_tr_processor` (`processed_by`),
  CONSTRAINT `fk_tr_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tr_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL COMMENT 'Kode tampil, mis. USR-001',
  `username` varchar(40) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('SUPER_ADMIN','ADMIN','BENDAHARA','KETUA','STAFF','ANGGOTA') NOT NULL DEFAULT 'STAFF',
  `full_name` varchar(120) NOT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_user_id` (`user_id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_ht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_ht` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) DEFAULT NULL,
  `username` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` varchar(30) DEFAULT NULL,
  `full_name` varchar(120) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `transaction_no` varchar(30) NOT NULL COMMENT 'SAL-YYYYMMDD-####',
  `type` enum('TOPUP','PEMBAYARAN','REFUND','PENYESUAIAN') NOT NULL,
  `amount` decimal(18,2) NOT NULL COMMENT '+ kredit, - debit',
  `balance_before` decimal(18,2) NOT NULL,
  `balance_after` decimal(18,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `reference` varchar(40) DEFAULT NULL COMMENT 'No order / voucher terkait',
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'Kasir/admin; NULL bila oleh anggota sendiri',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wt_no` (`transaction_no`),
  KEY `idx_wt_member` (`member_id`),
  KEY `idx_wt_created` (`created_at`),
  KEY `fk_wt_order` (`order_id`),
  KEY `fk_wt_user` (`user_id`),
  CONSTRAINT `fk_wt_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wt_order` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_wt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
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

-- =====================================================================
-- SEED DATA (idempoten: INSERT IGNORE / guard WHERE NOT EXISTS)
-- =====================================================================

-- ==== SEED: 2026_09_12_000001_seed_initial_data.sql ====
-- =====================================================================
-- KUTT SUKA MAKMUR - Seeds (Tahap 1)
-- Idempotent: hanya mengisi data dasar jika belum ada.
-- Akun seed: admin / bendahara / ketua / staff - password: admin123
-- (GANTI password default setelah login pertama!)
-- =====================================================================
-- PENTING: seed yang SUDAH tercatat di tabel `migrations` tidak dijalankan
-- ulang pada instalasi lama. Untuk menambah data seed baru, buat FILE SEED
-- BARU (jangan sunting file lama), lalu `php database/migrate.php`.
-- =====================================================================

-- A. USERS ------------------------------------------------------------
-- Password semua akun seed: admin123 (GANTI setelah login pertama!)
-- (Akun demo ANGGOTA ada di seed terpisah: 2026_09_14_000001_seed_portal_demo.sql)
INSERT IGNORE INTO users (user_id, username, email, password_hash, role, full_name, is_active)
VALUES
  ('USR-001', 'admin',     'admin@kuttsukamakmur.coop',     '$2y$10$HbIWNvVX1zAhUEproGWvZeei5KFK3EQ0l8vbLCV.PuDcnqDWstVlC', 'SUPER_ADMIN', 'Administrator Utama', 1),
  ('USR-002', 'bendahara', 'bendahara@kuttsukamakmur.coop', '$2y$10$HbIWNvVX1zAhUEproGWvZeei5KFK3EQ0l8vbLCV.PuDcnqDWstVlC', 'BENDAHARA',   'Ahmad Hidayat',       1),
  ('USR-003', 'ketua',     'ketua@kuttsukamakmur.coop',     '$2y$10$HbIWNvVX1zAhUEproGWvZeei5KFK3EQ0l8vbLCV.PuDcnqDWstVlC', 'KETUA',       'H. Sutrisno',         1),
  ('USR-004', 'staff',     'staff@kuttsukamakmur.coop',     '$2y$10$HbIWNvVX1zAhUEproGWvZeei5KFK3EQ0l8vbLCV.PuDcnqDWstVlC', 'STAFF',       'Siti Rahma',          1);

-- I. CHART OF ACCOUNTS (sesuai COA legacy KUTT) ----------------------
INSERT IGNORE INTO chart_of_accounts (account_code, account_name, account_category, normal_balance)
VALUES
  ('1101', 'Kas Utama',                          'ASSET',    'DEBIT'),
  ('1102', 'Kas Bank Jatim',                     'ASSET',    'DEBIT'),
  ('1103', 'Piutang Pinjaman Anggota',           'ASSET',    'DEBIT'),
  ('2101', 'Simpanan Pokok Anggota',             'LIABILITY','CREDIT'),
  ('2102', 'Simpanan Wajib Anggota',             'LIABILITY','CREDIT'),
  ('2103', 'Simpanan Sukarela Anggota',          'LIABILITY','CREDIT'),
  ('3101', 'Modal Cadangan Koperasi',            'EQUITY',   'CREDIT'),
  ('3102', 'SHU Belum Dibagi',                   'EQUITY',   'CREDIT'),
  ('4101', 'Pendapatan Jasa Bunga Pinjaman',     'REVENUE',  'CREDIT'),
  ('4102', 'Pendapatan Operasional Unit Susu',   'REVENUE',  'CREDIT'),
  ('5101', 'Beban Operasional & Administrasi',   'EXPENSE',  'DEBIT'),
  ('5102', 'Beban Bunga & Simpanan',             'EXPENSE',  'DEBIT');

-- K. SETTINGS / CMS DEFAULTS -----------------------------------------
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('brandName',       'KUTT SUKA MAKMUR'),
  ('subBrand',        'Grati - Pasuruan'),
  ('logoIcon',        'fa-solid fa-cow'),
  ('colorPrimary',    '#0b7a3e'),
  ('colorDark',       '#052e1a'),
  ('colorGold',       '#ffc107'),
  ('footerAddress',   'Jl. Raya Grati No. 128, Kecamatan Grati, Kabupaten Pasuruan, Jawa Timur 67184.'),
  ('footerPhone',     '(0343) 481123 / WA: 0812-3456-7890'),
  ('footerCopyright', '© 2026 KUTT Suka Makmur Grati. All Rights Reserved.');

-- M. NOTIFIKASI SELAMAT DATANG (broadcast) ---------------------------
INSERT INTO notifications (user_id, title, message, type)
SELECT NULL, 'Selamat datang di KUTT SUKA MAKMUR',
       'Fondasi sistem koperasi digital telah aktif. Semua modul siap digunakan bertahap.',
       'SUCCESS'
WHERE NOT EXISTS (SELECT 1 FROM notifications WHERE title = 'Selamat datang di KUTT SUKA MAKMUR');

-- ==== SEED: 2026_09_12_000002_seed_news_categories.sql ====
-- KUTT SUKA MAKMUR - Seed: kategori berita bawaan (idempoten)
INSERT IGNORE INTO news_categories (name, slug) VALUES
  ('Berita Koperasi', 'berita-koperasi'),
  ('Kegiatan Anggota', 'kegiatan-anggota'),
  ('Informasi Pasar', 'informasi-pasar'),
  ('Pengumuman', 'pengumuman');

-- ==== SEED: 2026_09_12_000003_seed_card_settings.sql ====
-- KUTT SUKA MAKMUR - Seed: pengaturan background kartu anggota (idempoten)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('card_bg_type',     'color'),
  ('card_bg_color',    '#0b7a3e'),
  ('card_bg_image',    ''),
  ('card_bg_opacity',  '0.25');

-- ==== SEED: 2026_09_14_000001_seed_portal_demo.sql ====
-- =====================================================================
-- KUTT SUKA MAKMUR - Seed: akun demo ANGGOTA + anggota terhubung (portal)
-- Idempoten: INSERT IGNORE / guard WHERE NOT EXISTS. Aman dijalankan ulang.
-- File terpisah agar INSTALASI LAMA (yang sudah mencatat seed lama di tabel
-- migrations) tetap mendapat data ini saat menjalankan `php database/migrate.php`.
-- =====================================================================

-- Akun ANGGOTA demo (password: admin123 — GANTI setelah login pertama!)
INSERT IGNORE INTO users (user_id, username, email, password_hash, role, full_name, is_active)
VALUES ('USR-005', 'anggota', 'anggota@kuttsukamakmur.coop',
        '$2y$10$HbIWNvVX1zAhUEproGWvZeei5KFK3EQ0l8vbLCV.PuDcnqDWstVlC',
        'ANGGOTA', 'Budi Santoso (Anggota Demo)', 1);

-- Anggota demo yang tertaut ke akun di atas (users.user_id => members.user_id).
INSERT IGNORE INTO members (member_no, full_name, address, phone, email, occupation, status, joined_at)
SELECT 'AGT-2026-0001', 'Budi Santoso (Anggota Demo)', 'Dusun Grati, Pasuruan, Jawa Timur',
       '0812-0000-0001', 'anggota@kuttsukamakmur.coop', 'Peternak Sapi Perah', 'AKTIF', CURRENT_DATE
WHERE NOT EXISTS (SELECT 1 FROM members WHERE member_no = 'AGT-2026-0001');

UPDATE members SET user_id = (SELECT id FROM users WHERE username = 'anggota')
WHERE member_no = 'AGT-2026-0001' AND user_id IS NULL;
