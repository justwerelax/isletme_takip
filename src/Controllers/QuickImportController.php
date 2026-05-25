<?php
class QuickImportController {

    public function index() {
        $user = Auth::user();

        // Aktif (kilitli olmayan) günlük girişler — son 60 gün
        $entries = Database::fetchAll(
            "SELECT de.id, de.entry_date, m.id as month_id, m.year, m.month
             FROM daily_entries de
             JOIN months m ON de.month_id = m.id
             WHERE m.is_locked = 0
             ORDER BY de.entry_date DESC
             LIMIT 60"
        );

        // Gider kategorileri
        $categories = Database::fetchAll(
            "SELECT id, name FROM expense_categories WHERE is_active = 1 ORDER BY name ASC"
        );

        // Ortak avanslıları (kasa hariç)
        $partners = Database::fetchAll(
            "SELECT id, name FROM partners WHERE is_active = 1 AND is_cash_reserve = 0 ORDER BY sort_order, name"
        );

        // Personel (aktif)
        $staff = Database::fetchAll(
            "SELECT id, name FROM staff WHERE is_active = 1 ORDER BY name ASC"
        );

        $pageTitle   = 'Hızlı Gider Girişi';
        $currentPage = 'quick_import';
        require BASE_PATH . '/templates/layout.php';
    }

    // AJAX: seçilen tarih+kategoriler için çakışma kontrolü
    public function checkConflicts() {
        Auth::requireAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $entryIdRaw  = trim($_POST['entry_id']    ?? '');
        $categoryIds = array_map('intval', (array)($_POST['category_ids'] ?? []));
        $categoryIds = array_filter($categoryIds);

        if (empty($categoryIds)) { echo json_encode(['conflicts' => []]); exit; }

        // entry_id → gerçek daily_entries id'yi bul (tarih string'i gelebilir)
        $entryId = 0;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $entryIdRaw)) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $entryIdRaw);
            if ($dateObj) {
                $month = Database::fetch(
                    "SELECT id FROM months WHERE year = ? AND month = ? AND is_locked = 0",
                    [(int)$dateObj->format('Y'), (int)$dateObj->format('n')]
                );
                if ($month) {
                    $existing = Database::fetch(
                        "SELECT id FROM daily_entries WHERE entry_date = ? AND month_id = ?",
                        [$entryIdRaw, $month['id']]
                    );
                    $entryId = $existing ? (int)$existing['id'] : 0;
                }
            }
        } else {
            $entryId = (int)$entryIdRaw;
        }

        if (!$entryId) { echo json_encode(['conflicts' => []]); exit; }

        // Bu giriş + kategoriler için mevcut harcamalar
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $params       = array_merge([$entryId], $categoryIds);
        $rows = Database::fetchAll(
            "SELECT ec.id as category_id, ec.name as category_name,
                    SUM(de.amount) as existing_amount
             FROM daily_expenses de
             JOIN expense_categories ec ON de.category_id = ec.id
             WHERE de.daily_entry_id = ? AND de.category_id IN ($placeholders)
             GROUP BY ec.id, ec.name",
            $params
        );

        echo json_encode(['conflicts' => $rows]);
        exit;
    }

    public function save() {
        Auth::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=quick_import'); exit;
        }

        $entryIdRaw = trim($_POST['entry_id'] ?? '');
        $items      = $_POST['items'] ?? [];

        // entry_id bir tarih string'i mi? (YYYY-MM-DD) → bul veya oluştur
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $entryIdRaw)) {
            $targetDate = $entryIdRaw;
            $dateObj    = \DateTime::createFromFormat('Y-m-d', $targetDate);
            if (!$dateObj) {
                $_SESSION['flash_error'] = 'Geçersiz tarih.';
                header('Location: ?page=quick_import'); exit;
            }
            $targetMonth = Database::fetch(
                "SELECT id FROM months WHERE year = ? AND month = ? AND is_locked = 0",
                [(int)$dateObj->format('Y'), (int)$dateObj->format('n')]
            );
            if (!$targetMonth) {
                $_SESSION['flash_error'] = 'Seçilen tarihin ayı sistemde yok ya da kilitli. Önce Ay Yönetimi\'nden ay ekleyin.';
                header('Location: ?page=quick_import'); exit;
            }
            $existing = Database::fetch(
                "SELECT id FROM daily_entries WHERE entry_date = ? AND month_id = ?",
                [$targetDate, $targetMonth['id']]
            );
            if ($existing) {
                $entryId = (int)$existing['id'];
            } else {
                $entryId = (int)Database::insert('daily_entries', [
                    'month_id'         => $targetMonth['id'],
                    'entry_date'       => $targetDate,
                    'revenue'          => 0,
                    'external_revenue' => 0,
                    'pos_amount'       => 0,
                    'notes'            => '',
                ]);
            }
        } else {
            $entryId = (int)$entryIdRaw;
        }

        if (!$entryId) {
            $_SESSION['flash_error'] = 'Günlük giriş seçilmedi.';
            header('Location: ?page=quick_import'); exit;
        }

        $entry = Database::fetch(
            "SELECT de.*, m.id as month_id, m.is_locked, m.year, m.month
             FROM daily_entries de
             JOIN months m ON de.month_id = m.id
             WHERE de.id = ?",
            [$entryId]
        );

        if (!$entry || (int)$entry['is_locked'] === 1) {
            $_SESSION['flash_error'] = 'Geçersiz veya kilitli giriş.';
            header('Location: ?page=quick_import'); exit;
        }

        $count = 0;
        foreach ($items as $item) {
            $amount     = $this->parseTRAmount((string)($item['amount'] ?? ''));
            $type       = $item['type'] ?? 'expense';  // expense | advance_partner | advance_staff
            $notes      = trim($item['notes'] ?? '');

            if ($amount <= 0) continue;

            if ($type === 'advance_partner') {
                $partnerId = (int)($item['person_id'] ?? 0);
                if (!$partnerId) continue;
                Database::insert('advances', [
                    'month_id'     => $entry['month_id'],
                    'partner_id'   => $partnerId,
                    'advance_date' => $entry['entry_date'],
                    'amount'       => $amount,
                    'description'  => $notes,
                ]);

            } elseif ($type === 'advance_staff') {
                $staffId = (int)($item['person_id'] ?? 0);
                if (!$staffId) continue;
                // Mevcut kayıt var mı?
                $existing = Database::fetch(
                    "SELECT id FROM staff_expenses WHERE daily_entry_id = ? AND staff_id = ? AND is_salary = 1",
                    [$entryId, $staffId]
                );
                if ($existing) {
                    Database::query(
                        "UPDATE staff_expenses SET amount = amount + ? WHERE id = ?",
                        [$amount, $existing['id']]
                    );
                } else {
                    Database::insert('staff_expenses', [
                        'daily_entry_id' => $entryId,
                        'staff_id'       => $staffId,
                        'amount'         => $amount,
                        'is_salary'      => 1,
                    ]);
                }

            } else {
                $catId = (int)($item['category_id'] ?? 0);
                if (!$catId) continue;
                Database::insert('daily_expenses', [
                    'daily_entry_id' => $entryId,
                    'category_id'    => $catId,
                    'amount'         => $amount,
                    'notes'          => $notes,
                ]);
            }
            $count++;
        }

        if ($count === 0) {
            $_SESSION['flash_error'] = 'Kaydedilecek geçerli giriş bulunamadı.';
            header('Location: ?page=quick_import'); exit;
        }

        $_SESSION['flash_success'] = $count . ' kayıt başarıyla eklendi.';
        header('Location: ?page=entries');
        exit;
    }

    private function parseTRAmount(string $val): float {
        $val = trim($val);
        if ($val === '' || $val === '-') return 0.0;
        $val = ltrim($val, '+');
        if (strpos($val, ',') !== false && strpos($val, '.') !== false) {
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
        } elseif (strpos($val, ',') !== false) {
            $val = str_replace(',', '.', $val);
        } elseif (strpos($val, '.') !== false) {
            $parts = explode('.', $val);
            $last  = end($parts);
            if (count($parts) > 2 || strlen($last) === 3) {
                $val = str_replace('.', '', $val);
            }
        }
        return (float)$val;
    }
}
