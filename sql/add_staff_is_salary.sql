-- Personel ödemelerinde maaş avansı / deneme-özel ödeme ayrımı
ALTER TABLE staff_expenses
    ADD COLUMN is_salary TINYINT(1) NOT NULL DEFAULT 1
        COMMENT '1=maaş avansı (hakediş hesabına dahil), 0=deneme/özel ödeme (hariç)';
