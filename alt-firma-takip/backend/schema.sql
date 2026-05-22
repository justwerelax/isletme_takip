-- Alt Firma Takip Sistemi - Database Schema
-- Standalone PWA Application
-- This file creates all required tables for the system

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- Users Table (Authentication)
-- ============================================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================================
-- Alt Firma Table (Subcontractors)
-- ============================================================================
CREATE TABLE IF NOT EXISTS alt_firma (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100) NOT NULL,
    telefon VARCHAR(20),
    adres TEXT,
    notlar TEXT,
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_durum (durum)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================================
-- Yıkama İşleri Table (Washing Jobs)
-- ============================================================================
CREATE TABLE IF NOT EXISTS yikama_isleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alt_firma_id INT NOT NULL,
    tarih DATE NOT NULL,
    metrekare DECIMAL(10,2) NOT NULL,
    birim_fiyat DECIMAL(10,2) NOT NULL,
    toplam_tutar DECIMAL(15,2) NOT NULL,
    teslimat_tipi ENUM('alt_firma_teslim', 'ana_firma_teslim') NOT NULL,
    komisyon_orani DECIMAL(5,4) NOT NULL,
    komisyon_tutari DECIMAL(15,2) NOT NULL,
    aciklama TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (alt_firma_id) REFERENCES alt_firma(id) ON DELETE CASCADE,
    INDEX idx_alt_firma (alt_firma_id),
    INDEX idx_tarih (tarih),
    CONSTRAINT chk_metrekare CHECK (metrekare > 0),
    CONSTRAINT chk_birim_fiyat CHECK (birim_fiyat > 0)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================================
-- Para Hareketleri Table (Payment Transactions)
-- ============================================================================
CREATE TABLE IF NOT EXISTS para_hareketleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alt_firma_id INT NOT NULL,
    tarih DATE NOT NULL,
    tutar DECIMAL(15,2) NOT NULL,
    hareket_tipi ENUM('odeme', 'tahsilat') NOT NULL,
    aciklama TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (alt_firma_id) REFERENCES alt_firma(id) ON DELETE CASCADE,
    INDEX idx_alt_firma (alt_firma_id),
    INDEX idx_tarih (tarih),
    CONSTRAINT chk_tutar CHECK (tutar > 0)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Schema Notes
-- ============================================================================
-- 
-- users table:
--   - Stores authentication credentials for system access
--   - password_hash uses PHP password_hash() function
--   - username is unique and indexed for fast lookups
--
-- alt_firma table:
--   - Stores subcontractor information
--   - durum (status) can be 'aktif' or 'pasif'
--   - Indexed on durum for filtering active/inactive subcontractors
--
-- yikama_isleri table:
--   - Stores carpet washing job records
--   - metrekare (square meters) must be positive
--   - birim_fiyat (unit price) must be positive
--   - toplam_tutar = metrekare × birim_fiyat
--   - komisyon_orani: 0.4000 for alt_firma_teslim, 0.3000 for ana_firma_teslim
--   - komisyon_tutari = toplam_tutar × komisyon_orani
--   - CASCADE delete: deleting a subcontractor deletes all their jobs
--
-- para_hareketleri table:
--   - Stores payment transactions between main company and subcontractors
--   - tutar (amount) must be positive
--   - hareket_tipi: 'odeme' (payment to subcontractor) or 'tahsilat' (collection from subcontractor)
--   - CASCADE delete: deleting a subcontractor deletes all their transactions
--
-- Balance Calculation:
--   Balance = SUM(komisyon_tutari from yikama_isleri)
--           - SUM(tutar from para_hareketleri WHERE hareket_tipi = 'odeme')
--           + SUM(tutar from para_hareketleri WHERE hareket_tipi = 'tahsilat')
--
--   Positive balance: Main company owes subcontractor
--   Negative balance: Subcontractor owes main company
--   Zero balance: Accounts are balanced
--
