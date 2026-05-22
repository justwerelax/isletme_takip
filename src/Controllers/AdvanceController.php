<?php
class AdvanceController {
    public function index() {
        $selectedYear  = (int)($_GET['year']  ?? 0);
        $selectedMonth = (int)($_GET['month'] ?? 0);

        if (!$selectedYear || !$selectedMonth) {
            $latest = Database::fetch("SELECT year, month FROM months WHERE is_locked = 0 ORDER BY year DESC, month DESC LIMIT 1")
                   ?? Database::fetch("SELECT year, month FROM months ORDER BY year DESC, month DESC LIMIT 1");
            $selectedYear  = $latest ? (int)$latest['year']  : (int)date('Y');
            $selectedMonth = $latest ? (int)$latest['month'] : (int)date('n');
        }
        
        $month = Database::fetch("SELECT * FROM months WHERE year = ? AND month = ?", [$selectedYear, $selectedMonth]);
        
        $availableMonths = Database::fetchAll("SELECT * FROM months ORDER BY year DESC, month DESC");
        
        $advances = [];
        // Kasa harici ortaklar (kasa avans almaz)
        $partners = Database::fetchAll("SELECT * FROM partners WHERE is_active = 1 AND is_cash_reserve = 0 ORDER BY sort_order");
        
        if ($month) {
            $advances = Database::fetchAll(
                "SELECT a.*, p.name as partner_name 
                 FROM advances a 
                 JOIN partners p ON a.partner_id = p.id 
                 WHERE a.month_id = ? 
                 ORDER BY a.advance_date DESC, a.id DESC", 
                [$month['id']]
            );
        }

        $pageTitle = 'Avans Yönetimi';
        $currentPage = 'advances';
        require BASE_PATH . '/templates/layout.php';
    }

    public function save() {
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $monthId = (int)$_POST['month_id'];
            $month = Database::fetch("SELECT * FROM months WHERE id = ?", [$monthId]);
            
            if (!$month || $month['is_locked']) {
                $_SESSION['flash_error'] = 'Kilitli veya geçersiz ay.';
                header('Location: ?page=advances');
                exit;
            }

            $partnerId = (int)$_POST['partner_id'];
            $date = $_POST['advance_date'];
            $amountStr = str_replace(',', '.', (string)$_POST['amount']);
            $amount = (float)$amountStr;
            $description = $_POST['description'] ?? '';

            if ($amount == 0) {
                $_SESSION['flash_error'] = 'Tutar 0 olamaz.';
            } else {
                Database::insert('advances', [
                    'month_id' => $monthId,
                    'partner_id' => $partnerId,
                    'advance_date' => $date,
                    'amount' => $amount,
                    'description' => $description
                ]);
                $type = $amount < 0 ? 'İşletmeye ödeme' : 'Avans';
                $_SESSION['flash_success'] = $type . ' başarıyla kaydedildi.';
            }
            
            header("Location: ?page=advances&year={$month['year']}&month={$month['month']}");
            exit;
        }
    }

    public function delete() {
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $adv = Database::fetch("SELECT a.*, m.year, m.month, m.is_locked FROM advances a JOIN months m ON a.month_id = m.id WHERE a.id = ?", [$id]);
            
            if ($adv) {
                if ($adv['is_locked']) {
                    $_SESSION['flash_error'] = 'Kilitli bir aydan avans silemezsiniz.';
                } else {
                    Database::delete('advances', "id = ?", [$id]);
                    $_SESSION['flash_success'] = 'Avans silindi.';
                }
                header("Location: ?page=advances&year={$adv['year']}&month={$adv['month']}");
                exit;
            }
        }
        header('Location: ?page=advances');
        exit;
    }
}
