-- Halk Bank POS alanı: günlük girişe eklenir, komisyon otomatik gider yazılır
ALTER TABLE daily_entries
    ADD COLUMN halkbank_pos DECIMAL(15,2) DEFAULT 0.00 AFTER pos_amount,
    ADD COLUMN halkbank_pos_rate DECIMAL(5,4) DEFAULT 0.0200 AFTER halkbank_pos;

-- daily_expenses'a notes kolonu: otomatik yazılan giderleri işaretlemek için
ALTER TABLE daily_expenses
    ADD COLUMN notes VARCHAR(100) NULL DEFAULT NULL;

-- Ayarlar: Halk Bank varsayılan komisyon oranı
INSERT INTO settings (setting_key, setting_value, description)
VALUES ('halkbank_pos_commission', '0.0200', 'Halk Bank POS varsayılan komisyon oranı (%2)')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- NOT: POS KOMİSYON kategorisi (slug='pos-komisyon', id=12) zaten VERGİ VB. altında mevcut.
