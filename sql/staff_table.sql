SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(100),
    salary DECIMAL(15,2) DEFAULT 0.00,
    start_date DATE,
    end_date DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Personel bazlı günlük gider takibi
CREATE TABLE IF NOT EXISTS staff_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    daily_entry_id INT NOT NULL,
    staff_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (daily_entry_id) REFERENCES daily_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id),
    UNIQUE KEY unique_entry_staff (daily_entry_id, staff_id)
) ENGINE=InnoDB;
