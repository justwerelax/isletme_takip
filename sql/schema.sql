CREATE DATABASE IF NOT EXISTS isletme_takip CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE isletme_takip;

-- Kullanıcılar
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin','viewer') DEFAULT 'viewer',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Ortaklar
CREATE TABLE partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    profit_share DECIMAL(5,4) NOT NULL,
    sort_order INT DEFAULT 0,
    is_cash_reserve TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Gider Kategorileri
CREATE TABLE expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Aylar
CREATE TABLE months (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    month INT NOT NULL,
    cash_carryover DECIMAL(15,2) DEFAULT 0.00,
    reserve_carryover DECIMAL(15,2) DEFAULT 0.00,
    is_locked TINYINT(1) DEFAULT 0,
    profit_shares_snapshot JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_month (year, month)
) ENGINE=InnoDB;

-- Günlük Girişler
CREATE TABLE daily_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month_id INT NOT NULL,
    entry_date DATE NOT NULL,
    revenue DECIMAL(15,2) DEFAULT 0.00,
    external_revenue DECIMAL(15,2) DEFAULT 0.00,
    pos_amount DECIMAL(15,2) DEFAULT 0.00,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (month_id) REFERENCES months(id),
    UNIQUE KEY unique_date (entry_date)
) ENGINE=InnoDB;

-- Günlük Giderler
CREATE TABLE daily_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    daily_entry_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(15,2) DEFAULT 0.00,
    FOREIGN KEY (daily_entry_id) REFERENCES daily_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id),
    UNIQUE KEY unique_entry_cat (daily_entry_id, category_id)
) ENGINE=InnoDB;

-- Avanslar
CREATE TABLE advances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month_id INT NOT NULL,
    partner_id INT NOT NULL,
    advance_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (month_id) REFERENCES months(id),
    FOREIGN KEY (partner_id) REFERENCES partners(id)
) ENGINE=InnoDB;

-- POS Komisyonları
CREATE TABLE pos_commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month_id INT NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    commission_rate DECIMAL(5,4) NOT NULL,
    commission_amount DECIMAL(15,2) NOT NULL,
    period_start DATE,
    period_end DATE,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (month_id) REFERENCES months(id)
) ENGINE=InnoDB;

-- Ayarlar
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255)
) ENGINE=InnoDB;

-- Seed: Ortaklar
INSERT INTO partners (name, profit_share, sort_order, is_cash_reserve) VALUES
('Muharrem', 0.4700, 1, 0),
('Buğra', 0.5000, 2, 0),
('Kasa', 0.0300, 3, 1);

-- Seed: Gider Kategorileri
INSERT INTO expense_categories (name, slug, sort_order) VALUES
('Mazot Gideri', 'mazot', 1),
('Yemek Gideri', 'yemek', 2),
('Personel Gideri', 'personel', 3),
('Fabrika Gideri', 'fabrika', 4);

-- Seed: Ayarlar
INSERT INTO settings (setting_key, setting_value, description) VALUES
('app_name', 'İşletme Takip Sistemi', 'Uygulama adı'),
('currency', 'TRY', 'Para birimi'),
('pos_default_commission', '0.0199', 'Varsayılan POS komisyon oranı');
