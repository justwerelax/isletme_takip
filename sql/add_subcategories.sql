-- Alt kategori desteği ekle
ALTER TABLE expense_categories 
    ADD COLUMN parent_id INT NULL DEFAULT NULL AFTER id,
    ADD FOREIGN KEY (parent_id) REFERENCES expense_categories(id) ON DELETE CASCADE;

-- Mevcut kategoriler ana kategori olarak kalır (parent_id = NULL)
