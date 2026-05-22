-- Yapılacaklar Listesi Tabloları
-- Bu dosyayı veritabanında çalıştırın

CREATE TABLE IF NOT EXISTS task_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT 'folder',
    color VARCHAR(20) DEFAULT '#818cf8',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('low','medium','high') DEFAULT 'medium',
    is_done TINYINT(1) DEFAULT 0,
    done_at DATETIME NULL,
    sort_order INT DEFAULT 0,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES task_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Seed: Varsayılan kategoriler
SET NAMES utf8mb4;
INSERT INTO task_categories (name, slug, icon, color, sort_order) VALUES
('Tadilat İşleri',     'tadilat',       'hammer',          '#f59e0b', 1),
('İş Geliştirme',      'is-gelistirme', 'trending-up',     '#10b981', 2),
('Resmi İşler',        'resmi',         'landmark',        '#818cf8', 3),
('Diğer',              'diger',         'more-horizontal', '#64748b', 4);
