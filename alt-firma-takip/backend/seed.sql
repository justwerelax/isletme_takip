-- Alt Firma Takip Sistemi - Seed Data
-- Sample data for testing and development
-- This file populates the database with test data

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- Clear existing data (for fresh seed)
-- ============================================================================
TRUNCATE TABLE para_hareketleri;
TRUNCATE TABLE yikama_isleri;
TRUNCATE TABLE alt_firma;
TRUNCATE TABLE users;

-- ============================================================================
-- Users (Authentication)
-- ============================================================================
-- Default admin user
-- Username: admin
-- Password: admin123
-- Password hash generated with: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (username, password_hash, name) VALUES
('admin', '$2y$10$ruxilYV7DgzCE2IL1yWpveH/HnHoeIPB5zs4NIdMk0sFiAnPn5XzW', 'Admin User');

-- ============================================================================
-- Alt Firma (Subcontractors)
-- ============================================================================
INSERT INTO alt_firma (ad, telefon, adres, notlar, durum) VALUES
('Yıldız Halı', '0555 111 2233', 'Kadıköy, İstanbul', 'Güvenilir alt firma, zamanında teslimat yapar', 'aktif'),
('Anadolu Temizlik', '0532 444 5566', 'Çankaya, Ankara', 'Yeni alt firma, deneme aşamasında', 'aktif'),
('Ege Halı Yıkama', '0543 777 8899', 'Karşıyaka, İzmir', 'Eski ortağımız, kaliteli iş çıkarır', 'aktif'),
('Marmara Temizlik', '0505 222 3344', 'Bursa', 'Pasif durumda, artık çalışmıyoruz', 'pasif');

-- ============================================================================
-- Yıkama İşleri (Washing Jobs)
-- ============================================================================
-- Jobs for Yıldız Halı (id: 1)
INSERT INTO yikama_isleri (alt_firma_id, tarih, metrekare, birim_fiyat, toplam_tutar, teslimat_tipi, komisyon_orani, komisyon_tutari, aciklama) VALUES
(1, '2024-01-15', 50.00, 25.00, 1250.00, 'alt_firma_teslim', 0.4000, 500.00, 'Salon halısı - Müşteri: Ahmet Yılmaz'),
(1, '2024-01-18', 30.00, 25.00, 750.00, 'ana_firma_teslim', 0.3000, 225.00, 'Yatak odası halısı - Müşteri: Ayşe Demir'),
(1, '2024-01-22', 75.00, 30.00, 2250.00, 'alt_firma_teslim', 0.4000, 900.00, 'Büyük salon halısı - Müşteri: Mehmet Kaya'),
(1, '2024-02-05', 40.00, 25.00, 1000.00, 'alt_firma_teslim', 0.4000, 400.00, 'Oturma odası - Müşteri: Fatma Şahin'),
(1, '2024-02-10', 60.00, 28.00, 1680.00, 'ana_firma_teslim', 0.3000, 504.00, 'İki oda halısı - Müşteri: Ali Çelik');

-- Jobs for Anadolu Temizlik (id: 2)
INSERT INTO yikama_isleri (alt_firma_id, tarih, metrekare, birim_fiyat, toplam_tutar, teslimat_tipi, komisyon_orani, komisyon_tutari, aciklama) VALUES
(2, '2024-01-20', 45.00, 24.00, 1080.00, 'alt_firma_teslim', 0.4000, 432.00, 'Salon halısı - Müşteri: Zeynep Aydın'),
(2, '2024-01-25', 35.00, 24.00, 840.00, 'alt_firma_teslim', 0.4000, 336.00, 'Yatak odası - Müşteri: Hasan Öztürk'),
(2, '2024-02-08', 55.00, 26.00, 1430.00, 'ana_firma_teslim', 0.3000, 429.00, 'Büyük oda - Müşteri: Elif Yıldız');

-- Jobs for Ege Halı Yıkama (id: 3)
INSERT INTO yikama_isleri (alt_firma_id, tarih, metrekare, birim_fiyat, toplam_tutar, teslimat_tipi, komisyon_orani, komisyon_tutari, aciklama) VALUES
(3, '2024-01-12', 80.00, 30.00, 2400.00, 'alt_firma_teslim', 0.4000, 960.00, 'Villa halıları - Müşteri: Murat Arslan'),
(3, '2024-01-28', 65.00, 28.00, 1820.00, 'alt_firma_teslim', 0.4000, 728.00, 'Daire halıları - Müşteri: Selin Koç'),
(3, '2024-02-15', 90.00, 32.00, 2880.00, 'ana_firma_teslim', 0.3000, 864.00, 'Büyük villa - Müşteri: Can Yılmaz');

-- ============================================================================
-- Para Hareketleri (Payment Transactions)
-- ============================================================================
-- Payments for Yıldız Halı (id: 1)
INSERT INTO para_hareketleri (alt_firma_id, tarih, tutar, hareket_tipi, aciklama) VALUES
(1, '2024-01-20', 500.00, 'odeme', 'İlk iş ödemesi'),
(1, '2024-01-25', 1000.00, 'odeme', 'Ocak ayı ara ödemesi'),
(1, '2024-02-01', 200.00, 'tahsilat', 'Fazla ödeme iadesi'),
(1, '2024-02-12', 800.00, 'odeme', 'Şubat ayı ödemesi');

-- Payments for Anadolu Temizlik (id: 2)
INSERT INTO para_hareketleri (alt_firma_id, tarih, tutar, hareket_tipi, aciklama) VALUES
(2, '2024-01-30', 600.00, 'odeme', 'İlk ödeme'),
(2, '2024-02-10', 400.00, 'odeme', 'İkinci ödeme');

-- Payments for Ege Halı Yıkama (id: 3)
INSERT INTO para_hareketleri (alt_firma_id, tarih, tutar, hareket_tipi, aciklama) VALUES
(3, '2024-01-18', 1500.00, 'odeme', 'Ocak ayı ödemesi'),
(3, '2024-02-01', 1000.00, 'odeme', 'Şubat ayı ödemesi'),
(3, '2024-02-18', 500.00, 'tahsilat', 'Avans tahsilatı');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Seed Data Summary
-- ============================================================================
-- 
-- Users:
--   - 1 admin user (username: admin, password: admin123)
--
-- Subcontractors:
--   - Yıldız Halı (aktif) - 5 jobs, 4 payments
--   - Anadolu Temizlik (aktif) - 3 jobs, 2 payments
--   - Ege Halı Yıkama (aktif) - 3 jobs, 3 payments
--   - Marmara Temizlik (pasif) - no jobs or payments
--
-- Jobs:
--   - Total: 11 jobs
--   - Mix of 'alt_firma_teslim' (40% commission) and 'ana_firma_teslim' (30% commission)
--   - Various dates in January and February 2024
--   - Different square meters and unit prices
--
-- Payments:
--   - Total: 9 payment transactions
--   - Mix of 'odeme' (payments to subcontractors) and 'tahsilat' (collections from subcontractors)
--   - Various dates and amounts
--
-- Expected Balances (approximate):
--   - Yıldız Halı: ~1,329 TL (main company owes subcontractor)
--   - Anadolu Temizlik: ~197 TL (main company owes subcontractor)
--   - Ege Halı Yıkama: ~52 TL (main company owes subcontractor)
--   - Marmara Temizlik: 0 TL (no transactions)
--
-- Balance Calculation Formula:
--   Balance = SUM(komisyon_tutari) - SUM(odeme) + SUM(tahsilat)
--
-- Example for Yıldız Halı:
--   Commissions: 500 + 225 + 900 + 400 + 504 = 2,529 TL
--   Payments (odeme): 500 + 1,000 + 800 = 2,300 TL
--   Collections (tahsilat): 200 TL
--   Balance: 2,529 - 2,300 + 200 = 429 TL (main company owes subcontractor)
--
-- Note: Password hash is for 'admin123' using PHP's password_hash() function
-- In production, change the default password immediately after first login
--

