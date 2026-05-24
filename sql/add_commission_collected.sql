-- POS günlük girişlerde komisyon tahsilat takibi
ALTER TABLE daily_entries
    ADD COLUMN commission_collected TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '0=komisyon henüz tahsil edilmedi, 1=tahsil edildi (arşiv)';
