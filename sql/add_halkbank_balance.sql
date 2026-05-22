-- Halk Bank hesap hareketleri tablosu
CREATE TABLE IF NOT EXISTS halkbank_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tarih DATE NOT NULL,
    tutar DECIMAL(15,2) NOT NULL,          -- pozitif = giriş, negatif = çıkış/harcama
    aciklama VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Başlangıç bakiyesi ayarı
INSERT INTO settings (setting_key, setting_value, description)
VALUES ('halkbank_opening_balance', '0.00', 'Halk Bank başlangıç bakiyesi')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
