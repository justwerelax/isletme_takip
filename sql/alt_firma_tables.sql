-- Alt Firma Takip Sistemi Tabloları
-- Bu dosyayı veritabanında çalıştırın

SET NAMES utf8mb4;

-- Alt Firma Tablosu
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

-- Yıkama İşleri Tablosu
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

-- Para Hareketleri Tablosu
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
