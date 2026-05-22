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

        $activeDailyEntries = [];
        $totalPosGross      = 0;
        $totalPosComm       = 0;
        $manualEntries      = [];

        $defaultCommissionStr = Database::fetch("SELECT setting_value FROM settings WHERE setting_key = 'pos_default_commission'")['setting_value'] ?? '0.0199';
        $globalDefaultRate    = (float)$defaultCommissionStr;

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

                $activeDailyEntries[] = $entry;
                $totalPosGross       += (float)$entry['pos_amount'];
                $totalPosComm        += $comm;
            }

            $manualEntries = Database::fetchAll(
                "SELECT * FROM pos_commissions WHERE month_id = ? ORDER BY created_at ASC",
                [$currentMonthData['id']]
            );
        }

        $commissionRate   = isset($commissionRate) ? $commissionRate : $globalDefaultRate;
        $availableMonths  = Database::fetchAll("SELECT * FROM months ORDER BY year DESC, month DESC");
        $pageTitle        = 'POS & Ekstre';
        $currentPage      = 'pos';
        require BASE_PATH . '/templates/layout.php';
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
