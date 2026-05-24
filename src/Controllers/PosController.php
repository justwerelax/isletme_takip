<?php
class PosController {
    public function index() {
        $user = Auth::user();

        $selectedYear  = (int)($_GET['year']  ?? 0);
        $selectedMonth = (int)($_GET['month'] ?? 0);

        if (!$selectedYear || !$selectedMonth) {
            $latest = Database::fetch("SELECT year, month FROM months WHERE is_locked = 0 ORDER BY year DESC, month DESC LIMIT 1")
                   ?? Database::fetch("SELECT year, month FROM months ORDER BY year DESC, month DESC LIMIT 1");
            $selectedYear  = $latest ? (int)$latest['year']  : (int)date('Y');
            $selectedMonth = $latest ? (int)$latest['month'] : (int)date('n');
        }

        $currentMonthData = Database::fetch("SELECT * FROM months WHERE year = ? AND month = ?", [$selectedYear, $selectedMonth]);

        $activeDailyEntries    = []; // komisyon tahsil edilmemiş
        $collectedEntries      = []; // komisyon tahsil edilmiş (arşiv)
        $totalPosGross         = 0;
        $totalPosComm          = 0;
        $manualEntries         = [];

        $defaultCommissionStr = Database::fetch("SELECT setting_value FROM settings WHERE setting_key = 'pos_default_commission'")['setting_value'] ?? '0.0199';
        $globalDefaultRate    = (float)$defaultCommissionStr;

        // Seçilen ay mevcut ay mı? (checkbox sadece mevcut ayda gösterilir)
        $isCurrentMonth = ($selectedYear === (int)date('Y') && $selectedMonth === (int)date('n'));

        if ($currentMonthData) {
            $commissionRate = $currentMonthData['pos_commission_rate'] !== null
                ? (float)$currentMonthData['pos_commission_rate']
                : $globalDefaultRate;

            $entries = Database::fetchAll(
                "SELECT * FROM daily_entries WHERE month_id = ? AND pos_amount > 0 ORDER BY entry_date ASC",
                [$currentMonthData['id']]
            );

            foreach ($entries as $entry) {
                $rate    = $entry['pos_commission_rate'] !== null ? (float)$entry['pos_commission_rate'] : $commissionRate;
                $comm    = round((float)$entry['pos_amount'] * $rate, 2);
                $entry['calculated_rate'] = $rate;
                $entry['pos_comm']        = $comm;

                if ((int)$entry['commission_collected'] === 1) {
                    $collectedEntries[] = $entry;
                } else {
                    $activeDailyEntries[] = $entry;
                    $totalPosGross       += (float)$entry['pos_amount'];
                    $totalPosComm        += $comm;
                }
            }

            $manualEntries = Database::fetchAll(
                "SELECT * FROM pos_commissions WHERE month_id = ? ORDER BY created_at ASC",
                [$currentMonthData['id']]
            );

            // Hedef tarih seçici için: ayın içinde daily_entry olan günler
            $availableTargetDates = Database::fetchAll(
                "SELECT id, entry_date FROM daily_entries WHERE month_id = ? ORDER BY entry_date DESC",
                [$currentMonthData['id']]
            );
        }

        $commissionRate       = isset($commissionRate) ? $commissionRate : $globalDefaultRate;
        $availableTargetDates = $availableTargetDates ?? [];
        $availableMonths      = Database::fetchAll("SELECT * FROM months ORDER BY year DESC, month DESC");
        $pageTitle            = 'POS & Ekstre';
        $currentPage          = 'pos';
        require BASE_PATH . '/templates/layout.php';
    }

    public function applyCommissions() {
        Auth::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=pos'); exit; }

        $monthId    = (int)$_POST['month_id'];
        $entryIds   = array_map('intval', (array)($_POST['entry_ids'] ?? []));
        $targetDate = trim($_POST['target_date'] ?? '');

        $month = Database::fetch("SELECT * FROM months WHERE id = ?", [$monthId]);
        if (!$month) {
            $_SESSION['flash_error'] = 'Ay bulunamadı.';
            header("Location: ?page=pos"); exit;
        }
        if ($month['is_locked']) {
            $_SESSION['flash_error'] = 'Bu ay kilitli.';
            header("Location: ?page=pos&year={$month['year']}&month={$month['month']}"); exit;
        }
        if (empty($entryIds) || !$targetDate) {
            $_SESSION['flash_error'] = 'En az bir gün seçin ve hedef tarih belirleyin.';
            header("Location: ?page=pos&year={$month['year']}&month={$month['month']}"); exit;
        }

        // Global ve ay komisyon oranı
        $globalRate = (float)(Database::fetch("SELECT setting_value FROM settings WHERE setting_key = 'pos_default_commission'")['setting_value'] ?? '0.0199');
        $monthRate  = $month['pos_commission_rate'] !== null ? (float)$month['pos_commission_rate'] : $globalRate;

        // Seçili girişlerin komisyonunu hesapla
        $totalComm  = 0;
        $validIds   = [];
        foreach ($entryIds as $eid) {
            $entry = Database::fetch(
                "SELECT id, pos_amount, pos_commission_rate, month_id, commission_collected FROM daily_entries WHERE id = ?",
                [$eid]
            );
            if (!$entry || (int)$entry['month_id'] !== $monthId || (int)$entry['commission_collected'] === 1) continue;
            $rate      = $entry['pos_commission_rate'] !== null ? (float)$entry['pos_commission_rate'] : $monthRate;
            $totalComm += round((float)$entry['pos_amount'] * $rate, 2);
            $validIds[] = $eid;
        }

        if (empty($validIds)) {
            $_SESSION['flash_error'] = 'Geçerli seçim bulunamadı.';
            header("Location: ?page=pos&year={$month['year']}&month={$month['month']}"); exit;
        }

        // Hedef günlük giriş
        $targetEntry = Database::fetch(
            "SELECT id FROM daily_entries WHERE entry_date = ? AND month_id = ?",
            [$targetDate, $monthId]
        );
        if (!$targetEntry) {
            $_SESSION['flash_error'] = 'Seçilen tarihte günlük giriş bulunamadı.';
            header("Location: ?page=pos&year={$month['year']}&month={$month['month']}"); exit;
        }

        // POS komisyon kategorisi
        $posCat = Database::fetch("SELECT id FROM expense_categories WHERE slug = 'pos-komisyon' AND is_active = 1");
        if (!$posCat) {
            $_SESSION['flash_error'] = 'POS Komisyon gider kategorisi bulunamadı.';
            header("Location: ?page=pos&year={$month['year']}&month={$month['month']}"); exit;
        }

        // Gider kaydı — mevcut varsa üstüne ekle
        $existing = Database::fetch(
            "SELECT id, amount FROM daily_expenses WHERE daily_entry_id = ? AND category_id = ? AND notes = 'pos_komisyon_manuel'",
            [$targetEntry['id'], $posCat['id']]
        );
        if ($existing) {
            Database::query(
                "UPDATE daily_expenses SET amount = amount - ? WHERE id = ?",
                [$totalComm, $existing['id']]
            );
        } else {
            Database::insert('daily_expenses', [
                'daily_entry_id' => $targetEntry['id'],
                'category_id'    => $posCat['id'],
                'amount'         => -$totalComm,
                'notes'          => 'pos_komisyon_manuel',
            ]);
        }

        // Seçili girişleri arşivle
        $placeholders = implode(',', array_fill(0, count($validIds), '?'));
        Database::query(
            "UPDATE daily_entries SET commission_collected = 1 WHERE id IN ($placeholders)",
            $validIds
        );

        $dateLabel = date('d.m.Y', strtotime($targetDate));
        $_SESSION['flash_success'] = count($validIds) . " günün komisyonu (" . Calculator::money($totalComm) . ") {$dateLabel} tarihine eklendi.";
        header("Location: ?page=pos&year={$month['year']}&month={$month['month']}");
        exit;
    }

    public function updateCommission() {
        Auth::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $monthId = (int)$_POST['month_id'];
            $rate    = (float)str_replace(',', '.', (string)$_POST['commission_rate']) / 100;
            Database::update('months', ['pos_commission_rate' => $rate], "id = ?", [$monthId]);
            $_SESSION['flash_success'] = 'Komisyon oranı güncellendi.';
            $m = Database::fetch("SELECT year, month FROM months WHERE id = ?", [$monthId]);
            header("Location: ?page=pos&year={$m['year']}&month={$m['month']}");
            exit;
        }
    }

    public function saveManualEntry() {
        Auth::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $monthId     = (int)$_POST['month_id'];
            $description = trim($_POST['description']);
            $totalAmount = (float)str_replace(',', '.', (string)$_POST['total_amount']);

            Database::insert('pos_commissions', [
                'month_id'          => $monthId,
                'bank_name'         => $description,
                'total_amount'      => $totalAmount,
                'commission_rate'   => 0,
                'commission_amount' => 0,
                'notes'             => $_POST['notes'] ?? '',
            ]);

            $_SESSION['flash_success'] = 'POS kalemi eklendi.';
            $m = Database::fetch("SELECT year, month FROM months WHERE id = ?", [$monthId]);
            header("Location: ?page=pos&year={$m['year']}&month={$m['month']}");
            exit;
        }
    }

    public function deleteManualEntry() {
        Auth::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id  = (int)$_POST['id'];
            $pos = Database::fetch(
                "SELECT p.*, m.year, m.month FROM pos_commissions p JOIN months m ON p.month_id = m.id WHERE p.id = ?",
                [$id]
            );
            if ($pos) {
                Database::delete('pos_commissions', "id = ?", [$id]);
                $_SESSION['flash_success'] = 'Kayıt silindi.';
                header("Location: ?page=pos&year={$pos['year']}&month={$pos['month']}");
                exit;
            }
        }
        header("Location: ?page=pos");
    }
}
