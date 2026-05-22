<?php
class EntryController {
    public function index() {
        // Hangi aya kayıt ekleneceğini seç
        $selectedYear  = (int)($_GET['year']  ?? 0);
        $selectedMonth = (int)($_GET['month'] ?? 0);

        if (!$selectedYear || !$selectedMonth) {
            $latest = Database::fetch("SELECT year, month FROM months WHERE is_locked = 0 ORDER BY year DESC, month DESC LIMIT 1")
                   ?? Database::fetch("SELECT year, month FROM months ORDER BY year DESC, month DESC LIMIT 1");
            $selectedYear  = $latest ? (int)$latest['year']  : (int)date('Y');
            $selectedMonth = $latest ? (int)$latest['month'] : (int)date('n');
        }
        
        $month = Database::fetch(
            "SELECT * FROM months WHERE year = ? AND month = ?",
            [$selectedYear, $selectedMonth]
        );
        
        $availableMonths = Database::fetchAll("SELECT * FROM months ORDER BY year DESC, month DESC");
        
        $entries = [];
        // Ana kategoriler ve alt kategoriler ayrı ayrı
        $parentCategories = Database::fetchAll("SELECT * FROM expense_categories WHERE is_active = 1 AND parent_id IS NULL ORDER BY sort_order");
        $allCategories    = Database::fetchAll("SELECT * FROM expense_categories WHERE is_active = 1 ORDER BY sort_order");
        // Geriye dönük uyumluluk için $categories = tüm kategoriler
        $categories = $allCategories;

        // Aktif personel listesi (gider formunda seçim için)
        $activeStaff = Database::fetchAll("SELECT id, name, salary FROM staff WHERE is_active = 1 ORDER BY name");
        
        if ($month) {
            $entries = Database::fetchAll("SELECT * FROM daily_entries WHERE month_id = ? ORDER BY entry_date", [$month['id']]);
            // POS komisyon kategorisini bir kez çek
            $posCatRow = Database::fetch("SELECT id FROM expense_categories WHERE slug = 'pos-komisyon' AND is_active = 1");
            $posCatId  = $posCatRow ? (int)$posCatRow['id'] : null;

            // Her giriş için giderleri çek
            foreach ($entries as &$entry) {
                $expenses = Database::fetchAll("SELECT category_id, amount, notes FROM daily_expenses WHERE daily_entry_id = ?", [$entry['id']]);
                $expMap = [];
                $expNoteMap = [];
                $systemNotes = ['halkbank_pos_komisyon', 'pos_komisyon_manuel', 'otomatik_odeme'];
                foreach ($expenses as $exp) {
                    $expMap[$exp['category_id']] = (float)$exp['amount'];
                    if (!in_array($exp['notes'] ?? '', $systemNotes, true)) {
                        $expNoteMap[$exp['category_id']] = $exp['notes'] ?? '';
                    }
                }
                $entry['expenses']      = $expMap;
                $entry['expense_notes'] = $expNoteMap;

                // Personel gideri kırılımı (detay satırında göstermek için)
                $staffExps = Database::fetchAll(
                    "SELECT se.staff_id, se.amount, se.is_salary, s.name FROM staff_expenses se JOIN staff s ON se.staff_id = s.id WHERE se.daily_entry_id = ? ORDER BY s.name",
                    [$entry['id']]
                );
                $staffExpMap = [];
                foreach ($staffExps as $se) {
                    $staffExpMap[$se['staff_id']] = ['amount' => (float)$se['amount'], 'name' => $se['name'], 'is_salary' => (int)$se['is_salary']];
                }
                $entry['staff_expenses'] = $staffExpMap;
            }
            unset($entry); // Referansı kır — bu olmadan template'deki foreach bozulur
        }
        
        // Düzenleme modu için
        $editEntry = null;
        if (isset($_GET['edit_id'])) {
            $editEntry = Database::fetch("SELECT * FROM daily_entries WHERE id = ?", [$_GET['edit_id']]);
            if ($editEntry) {
                $expenses = Database::fetchAll("SELECT category_id, amount, notes FROM daily_expenses WHERE daily_entry_id = ?", [$editEntry['id']]);
                $expMap = [];
                $expNoteMap = [];
                $systemNotes = ['halkbank_pos_komisyon', 'pos_komisyon_manuel', 'otomatik_odeme'];
                foreach ($expenses as $exp) {
                    $expMap[$exp['category_id']] = (float)$exp['amount'];
                    if (!in_array($exp['notes'] ?? '', $systemNotes, true)) {
                        $expNoteMap[$exp['category_id']] = $exp['notes'] ?? '';
                    }
                }
                $editEntry['expenses']      = $expMap;
                $editEntry['expense_notes'] = $expNoteMap;

                // Personel giderleri
                $staffExps = Database::fetchAll("SELECT staff_id, amount, is_salary FROM staff_expenses WHERE daily_entry_id = ?", [$editEntry['id']]);
                $staffExpMap   = [];
                $staffTrialMap = []; // is_salary=0 olanlar
                foreach ($staffExps as $se) {
                    $staffExpMap[$se['staff_id']]   = (float)$se['amount'];
                    $staffTrialMap[$se['staff_id']]  = (int)$se['is_salary'] === 0; // true = deneme
                }
                $editEntry['staff_expenses']       = $staffExpMap;
                $editEntry['staff_trial']           = $staffTrialMap;
            }
        }

        $pageTitle   = 'Günlük Girişler';
        $currentPage = 'entries';
        require BASE_PATH . '/templates/layout.php';
    }

    /**
     * Türkçe formatlı sayıyı float'a çevirir.
     * "2.356,04" → 2356.04 | "2356,04" → 2356.04
     * "2.356"    → 2356    | "2356.04" → 2356.04
     * "2.000"    → 2000    (sadece nokta + 3 rakam → binlik)
     */
    private function parseTRAmount(string $val): float {
        $val = trim($val);
        if ($val === '' || $val === '-') return 0.0;

        if (strpos($val, ',') !== false && strpos($val, '.') !== false) {
            // "2.356,04" — nokta binlik, virgül ondalık
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
        } elseif (strpos($val, ',') !== false) {
            // "2356,04" — sadece virgül → ondalık ayraç
            $val = str_replace(',', '.', $val);
        } elseif (strpos($val, '.') !== false) {
            // Sadece nokta var: "2.000" (binlik) mi, "2.04" (ondalık) mi?
            $parts = explode('.', $val);
            $lastPart = end($parts);
            if (count($parts) > 2 || strlen($lastPart) === 3) {
                // Birden fazla nokta VEYA noktadan sonra tam 3 hane → binlik ayraç
                $val = str_replace('.', '', $val);
            }
            // Aksi hâlde ("2.04") doğrudan float
        }
        return (float)$val;
    }

    public function save() {
        Auth::requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $monthId = (int)$_POST['month_id'];
            $id = (int)($_POST['id'] ?? 0);
            
            $month = Database::fetch("SELECT * FROM months WHERE id = ?", [$monthId]);
            if (!$month) {
                $_SESSION['flash_error'] = 'Ay bulunamadı.';
                header('Location: ?page=entries');
                exit;
            }
            if ($month['is_locked']) {
                $_SESSION['flash_error'] = 'Bu ay kilitli olduğu için işlem yapılamaz.';
                header("Location: ?page=entries&year={$month['year']}&month={$month['month']}");
                exit;
            }

            $date     = $_POST['entry_date'];
            $revenue  = $this->parseTRAmount($_POST['revenue'] ?? '0');
            $external = $this->parseTRAmount($_POST['external_revenue'] ?? '0');
            $pos      = $this->parseTRAmount($_POST['pos_amount'] ?? '0');
            $notes    = $_POST['notes'] ?? '';

            try {
                // Mevcut POS komisyon oranını bul
                $defaultCommissionStr = Database::fetch("SELECT setting_value FROM settings WHERE setting_key = 'pos_default_commission'")['setting_value'] ?? '0.0199';
                $currentRate = $month['pos_commission_rate'] !== null ? (float)$month['pos_commission_rate'] : (float)$defaultCommissionStr;

                // Sistem tarafından yönetilen kategoriler — edit sırasında silinmez
                $loanCatRow = Database::fetch("SELECT id FROM expense_categories WHERE slug = 'kredi-odeme' AND is_active = 1");
                $loanCatId  = $loanCatRow ? (int)$loanCatRow['id'] : 0;

                if ($id > 0) {
                    // Update
                    $entryData = [
                        'entry_date'       => $date,
                        'revenue'          => $revenue,
                        'external_revenue' => $external,
                        'pos_amount'       => $pos,
                        'notes'            => $notes,
                    ];

                    $existing = Database::fetch("SELECT pos_commission_rate FROM daily_entries WHERE id = ?", [$id]);
                    if (!$existing || $existing['pos_commission_rate'] === null) {
                        $entryData['pos_commission_rate'] = $currentRate;
                    }

                    Database::update('daily_entries', $entryData, "id = ?", [$id]);

                    // Giderleri sil — sistem yönetimli satırlara dokunma:
                    //   halkbank_pos_komisyon, pos_komisyon_manuel, otomatik_odeme (taksit), kredi-odeme kategorisi (kredi)
                    Database::delete('daily_expenses',
                        "daily_entry_id = ?
                         AND (" . ($loanCatId ? "category_id != $loanCatId AND " : "") . "1=1)
                         AND (notes IS NULL OR notes NOT IN ('halkbank_pos_komisyon','pos_komisyon_manuel','otomatik_odeme'))",
                        [$id]);
                } else {
                    // Insert
                    $id = Database::insert('daily_entries', [
                        'month_id'            => $monthId,
                        'entry_date'          => $date,
                        'revenue'             => $revenue,
                        'external_revenue'    => $external,
                        'pos_amount'          => $pos,
                        'pos_commission_rate' => $currentRate,
                        'notes'               => $notes,
                    ]);
                }

                // Giderleri ekle — ON DUPLICATE KEY UPDATE ile güvenli yaz
                // (formda gönderilmeyen kapalı gruplar mevcut değerlerini korur)
                if (isset($_POST['expenses']) && is_array($_POST['expenses'])) {
                    foreach ($_POST['expenses'] as $catId => $amount) {
                        $catIdInt = (int)$catId;
                        // Kredi Ödemesi kategorisi sistem tarafından yönetiliyor — formdan gelen değeri yoksay
                        if ($loanCatId && $catIdInt === $loanCatId) continue;

                        $amt = $this->parseTRAmount((string)$amount);
                        $expNote = trim($_POST['expense_notes'][$catIdInt] ?? '');
                        if ($amt != 0) {
                            if ($amt > 0) $amt = -$amt;
                            Database::query("
                                INSERT INTO daily_expenses (daily_entry_id, category_id, amount, notes)
                                VALUES (?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE amount = VALUES(amount), notes = VALUES(notes)
                            ", [$id, $catIdInt, $amt, $expNote ?: null]);
                        }
                        // Sıfır girilmişse o kategoriyi sil (sistem kayıtlarına dokunma)
                        elseif ($amt == 0) {
                            Database::delete('daily_expenses',
                                "daily_entry_id = ? AND category_id = ? AND (notes IS NULL OR notes NOT IN ('halkbank_pos_komisyon','pos_komisyon_manuel','otomatik_odeme'))",
                                [$id, $catIdInt]
                            );
                        }
                    }
                }

                // Personel giderlerini kaydet
                Database::delete('staff_expenses', 'daily_entry_id = ?', [$id]);
                if (isset($_POST['staff_expenses']) && is_array($_POST['staff_expenses'])) {
                    $personnelCat   = Database::fetch("SELECT id FROM expense_categories WHERE slug = 'personel' AND is_active = 1");
                    $personnelCatId = $personnelCat ? (int)$personnelCat['id'] : null;
                    $totalStaffExp  = 0;

                    foreach ($_POST['staff_expenses'] as $staffId => $amount) {
                        $amt      = $this->parseTRAmount((string)$amount);
                        $isSalary = isset($_POST['staff_trial'][(int)$staffId]) ? 0 : 1; // checkbox işaretliyse deneme = 0
                        if ($amt > 0) {
                            Database::query("
                                INSERT INTO staff_expenses (daily_entry_id, staff_id, amount, is_salary)
                                VALUES (?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE amount = VALUES(amount), is_salary = VALUES(is_salary)
                            ", [$id, (int)$staffId, $amt, $isSalary]);
                            $totalStaffExp += $amt;
                        } else {
                            // Sıfır girilmişse o personeli sil
                            Database::delete('staff_expenses', 'daily_entry_id = ? AND staff_id = ?', [$id, (int)$staffId]);
                        }
                    }

                    // Personel kategorisi toplamını güncelle
                    if ($personnelCatId) {
                        // Tüm personel giderlerini topla (formda gönderilmeyenler dahil)
                        $realTotal = (float)(Database::fetch(
                            "SELECT COALESCE(SUM(amount), 0) as t FROM staff_expenses WHERE daily_entry_id = ?",
                            [$id]
                        )['t'] ?? 0);

                        if ($realTotal > 0) {
                            Database::query("
                                INSERT INTO daily_expenses (daily_entry_id, category_id, amount)
                                VALUES (?, ?, ?)
                                ON DUPLICATE KEY UPDATE amount = VALUES(amount)
                            ", [$id, $personnelCatId, -$realTotal]);
                        } else {
                            Database::delete('daily_expenses',
                                "daily_entry_id = ? AND category_id = ? AND (notes IS NULL OR (notes != 'halkbank_pos_komisyon' AND notes != 'pos_komisyon_manuel'))",
                                [$id, $personnelCatId]
                            );
                        }
                    }
                }

                $_SESSION['flash_success'] = 'Kayıt başarıyla kaydedildi.';
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Kayıt sırasında hata oluştu. (Aynı tarihe 2 kayıt eklenemez)';
            }
            
            header("Location: ?page=entries&year={$month['year']}&month={$month['month']}");
            exit;
        }
    }

    public function delete() {
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $entry = Database::fetch("SELECT e.*, m.year, m.month, m.is_locked FROM daily_entries e JOIN months m ON e.month_id = m.id WHERE e.id = ?", [$id]);
            
            if ($entry) {
                if ($entry['is_locked']) {
                    $_SESSION['flash_error'] = 'Kilitli bir aydan kayıt silemezsiniz.';
                } else {
                    Database::delete('daily_entries', "id = ?", [$id]);
                    $_SESSION['flash_success'] = 'Kayıt silindi.';
                }
                header("Location: ?page=entries&year={$entry['year']}&month={$entry['month']}");
                exit;
            }
        }
        header('Location: ?page=entries');
        exit;
    }
}
