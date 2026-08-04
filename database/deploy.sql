-- ============================================================
-- DEPLOY.SQL — Perubahan DB yang perlu dijalankan di Live
-- Generated: 2026-08-03
-- Semua statement pakai IF NOT EXISTS / IF NOT EXISTS kolom
-- agar aman dijalankan berulang kali.
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- 1. brs_sampling_points
--    Titik sampling ENV/WE per Business Relation Site
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `brs_sampling_points` (
  `id_sp`      int unsigned     NOT NULL AUTO_INCREMENT,
  `id_site`    int              NOT NULL,
  `jenis`      enum('env','we') NOT NULL,
  `kode`       varchar(50)      DEFAULT NULL,
  `nama`       varchar(255)     NOT NULL,
  `latitude`   decimal(10,8)    DEFAULT NULL,
  `longitude`  decimal(11,8)    DEFAULT NULL,
  `gedung`     varchar(255)     DEFAULT NULL,
  `ruangan`    varchar(255)     DEFAULT NULL,
  `lantai`     varchar(50)      DEFAULT NULL,
  `keterangan` text             DEFAULT NULL,
  `is_aktif`   tinyint(1)       NOT NULL DEFAULT 1,
  `deleted_at` timestamp        NULL DEFAULT NULL,
  `created_at` timestamp        NULL DEFAULT NULL,
  `updated_at` timestamp        NULL DEFAULT NULL,
  PRIMARY KEY (`id_sp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. brs_mp
--    Man Power (karyawan) per Business Relation Site
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `brs_mp` (
  `id_mp`       int unsigned NOT NULL AUTO_INCREMENT,
  `id_site`     int          NOT NULL,
  `no_karyawan` varchar(100) NOT NULL,
  `nama`        varchar(255) NOT NULL,
  `is_aktif`    tinyint(1)   NOT NULL DEFAULT 1,
  `deleted_at`  timestamp    NULL DEFAULT NULL,
  `created_at`  timestamp    NULL DEFAULT NULL,
  `updated_at`  timestamp    NULL DEFAULT NULL,
  PRIMARY KEY (`id_mp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. br_products
--    Produk milik Business Relation (level BR)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `br_products` (
  `id_product`   int unsigned NOT NULL AUTO_INCREMENT,
  `id_br`        int unsigned NOT NULL,
  `nama_product` varchar(255) NOT NULL,
  `seri_product` varchar(100) DEFAULT NULL,
  `keterangan`   text         DEFAULT NULL,
  `is_aktif`     tinyint(1)   NOT NULL DEFAULT 1,
  `deleted_at`   timestamp    NULL DEFAULT NULL,
  `created_at`   timestamp    NULL DEFAULT NULL,
  `updated_at`   timestamp    NULL DEFAULT NULL,
  PRIMARY KEY (`id_product`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. budget_accounts
--    Hierarki 2 level: Kategori → Sub Kategori anggaran FWO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `budget_accounts` (
  `id_account` int unsigned NOT NULL AUTO_INCREMENT,
  `id_parent`  int unsigned DEFAULT NULL,
  `nama`       varchar(255) NOT NULL,
  `kode`       varchar(50)  NOT NULL,
  `keterangan` text         DEFAULT NULL,
  `is_aktif`   tinyint(1)   NOT NULL DEFAULT 1,
  `deleted_at` timestamp    NULL DEFAULT NULL,
  `created_at` timestamp    NULL DEFAULT NULL,
  `updated_at` timestamp    NULL DEFAULT NULL,
  PRIMARY KEY (`id_account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. fwo_budgets
--    Budget Plan per FWO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fwo_budgets` (
  `id_budget`          int unsigned              NOT NULL AUTO_INCREMENT,
  `id_fwo`             int                       NOT NULL,
  `label`              varchar(255)              NOT NULL,
  `keterangan`         text                      DEFAULT NULL,
  `tanggal_mulai`      date                      DEFAULT NULL,
  `tanggal_selesai`    date                      DEFAULT NULL,
  `status`             enum('open','completed')  NOT NULL DEFAULT 'open',
  `dokumen_realisasi`  varchar(500)              DEFAULT NULL,
  `deleted_at`         timestamp                 NULL DEFAULT NULL,
  `created_at`         timestamp                 NULL DEFAULT NULL,
  `updated_at`         timestamp                 NULL DEFAULT NULL,
  PRIMARY KEY (`id_budget`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 6. fwo_budget_items
--    Item anggaran per Budget Plan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fwo_budget_items` (
  `id_budget_item`  int unsigned NOT NULL AUTO_INCREMENT,
  `id_budget`       int          NOT NULL,
  `id_account`      int          NOT NULL,
  `nominal_budget`  int          NOT NULL DEFAULT 0,
  `keterangan`      text         DEFAULT NULL,
  `is_cash_advance` tinyint(1)   NOT NULL DEFAULT 0,
  `created_at`      timestamp    NULL DEFAULT NULL,
  `updated_at`      timestamp    NULL DEFAULT NULL,
  PRIMARY KEY (`id_budget_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 7. fwo_budget_actuals
--    Realisasi / pengeluaran per Budget Item
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fwo_budget_actuals` (
  `id_actual`          int unsigned                         NOT NULL AUTO_INCREMENT,
  `id_budget_item`     int                                  NOT NULL,
  `nominal_actual`     int                                  NOT NULL DEFAULT 0,
  `keterangan`         text                                 DEFAULT NULL,
  `attachments`        json                                 DEFAULT NULL,
  `status_verifikasi`  enum('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  `catatan_verifikasi` text                                 DEFAULT NULL,
  `verified_by`        int                                  DEFAULT NULL,
  `verified_at`        timestamp                            NULL DEFAULT NULL,
  `created_at`         timestamp                            NULL DEFAULT NULL,
  `updated_at`         timestamp                            NULL DEFAULT NULL,
  PRIMARY KEY (`id_actual`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 8. lab_samples
--    Slot sampel laboratorium per Fieldwork BOQ
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lab_samples` (
  `id_lab_sample`      int unsigned                                NOT NULL AUTO_INCREMENT,
  `id_fwo`             int                                         NOT NULL,
  `id_fwo_boq`         int                                         NOT NULL,
  `no_urut`            int                                         NOT NULL DEFAULT 1,
  `jenis_sample`       enum('env','we','mp','product')             DEFAULT NULL,
  `no_sample`          varchar(100)                                DEFAULT NULL,
  `tanggal_pengambilan` date                                       DEFAULT NULL,
  `titik_lokasi`       varchar(255)                                DEFAULT NULL,
  `kondisi_sample`     varchar(100)                                DEFAULT NULL,
  `keterangan`         text                                        DEFAULT NULL,
  `status`             enum('belum_diambil','diambil','dikirim')   NOT NULL DEFAULT 'belum_diambil',
  `created_at`         timestamp                                   NULL DEFAULT NULL,
  `updated_at`         timestamp                                   NULL DEFAULT NULL,
  PRIMARY KEY (`id_lab_sample`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ALTER TABLE — Kolom baru di tabel yang sudah ada
-- Gunakan prosedur stored sementara agar IF NOT EXISTS aman
-- di MySQL versi lama yang belum support ALTER ... IF NOT EXISTS
-- ============================================================

-- ------------------------------------------------------------
-- 9. business_relation_sites — tambah nama_jalan & keterangan_alamat
-- ------------------------------------------------------------
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'business_relation_sites'
    AND COLUMN_NAME  = 'nama_jalan'
);
SET @sql = IF(@exists = 0,
  'ALTER TABLE `business_relation_sites` ADD COLUMN `nama_jalan` text DEFAULT NULL AFTER `kawasan_bisnis`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'business_relation_sites'
    AND COLUMN_NAME  = 'keterangan_alamat'
);
SET @sql = IF(@exists = 0,
  'ALTER TABLE `business_relation_sites` ADD COLUMN `keterangan_alamat` varchar(255) DEFAULT NULL AFTER `nama_jalan`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 10. business_relation_sites — tambah latitude & longitude
-- ------------------------------------------------------------
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'business_relation_sites'
    AND COLUMN_NAME  = 'latitude'
);
SET @sql = IF(@exists = 0,
  'ALTER TABLE `business_relation_sites` ADD COLUMN `latitude` decimal(10,8) DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'business_relation_sites'
    AND COLUMN_NAME  = 'longitude'
);
SET @sql = IF(@exists = 0,
  'ALTER TABLE `business_relation_sites` ADD COLUMN `longitude` decimal(11,8) DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 11. work_orders — tambah kolom periode (interval_bulan, no_urut_period, id_period)
-- ------------------------------------------------------------
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'work_orders'
    AND COLUMN_NAME  = 'interval_bulan'
);
SET @sql = IF(@exists = 0,
  'ALTER TABLE `work_orders` ADD COLUMN `interval_bulan` tinyint unsigned DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'work_orders'
    AND COLUMN_NAME  = 'no_urut_period'
);
SET @sql = IF(@exists = 0,
  'ALTER TABLE `work_orders` ADD COLUMN `no_urut_period` tinyint unsigned DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'work_orders'
    AND COLUMN_NAME  = 'id_period'
);
SET @sql = IF(@exists = 0,
  'ALTER TABLE `work_orders` ADD COLUMN `id_period` int DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 12. fieldwork_boq — tambah deleted_at (soft delete)
-- ------------------------------------------------------------
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'fieldwork_boq'
    AND COLUMN_NAME  = 'deleted_at'
);
SET @sql = IF(@exists = 0,
  'ALTER TABLE `fieldwork_boq` ADD COLUMN `deleted_at` timestamp NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 13. output_pekerjaan — pastikan kolom id_fwo_boq ada
-- ------------------------------------------------------------
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'output_pekerjaan'
    AND COLUMN_NAME  = 'id_fwo_boq'
);
SET @sql = IF(@exists = 0,
  'ALTER TABLE `output_pekerjaan` ADD COLUMN `id_fwo_boq` bigint unsigned DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 14. Perbaikan: normalize status WO dari 'on-progress' → 'onprogress'
--    (jika belum dijalankan)
-- ------------------------------------------------------------
UPDATE `work_orders` SET `status` = 'onprogress' WHERE `status` = 'on-progress';

SET foreign_key_checks = 1;

-- ============================================================
-- SELESAI
-- ============================================================
