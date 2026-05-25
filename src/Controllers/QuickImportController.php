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

        // Avans ortakları (kasa hariç)
        $partners = Database::fetchAll(
            "SELECT id, name FROM partners WHERE is_active = 1 AND is_cash_reserve = 0 ORDER BY sort_order, name"
        );

        $pageTitle   = 'Hızlı Gider Girişi';
        $currentPage = 'quick_import';
        require BASE_PATH . '/templates/layout.php';
    }

    public function save() {
        Auth::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=quick_import'); exit;
        }

        $entryId = (int)($_POST['entry_id'] ?? 0);
        $items   = $_POST['items'] ?? [];

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
            $amount  = $this->parseTRAmount((string)($item['amount'] ?? ''));
            $type    = $item['type'] ?? 'expense'; // 'expense' | 'advance'
            $notes   = trim($item['notes'] ?? '');

            if ($amount <= 0) continue;

            if ($type === 'advance') {
                $partnerId = (int)($item['partner_id'] ?? 0);
                if (!$partnerId) continue;
                Database::insert('advances', [
                    'month_id'     => $entry['month_id'],
                    'partner_id'   => $partnerId,
                    'advance_date' => $entry['entry_date'],
                    'amount'       => $amount,
                    'description'  => $notes,
                ]);
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
