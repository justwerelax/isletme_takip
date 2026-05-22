<?php
class DashboardController {
    public function index() {
        $user = Auth::user();

        // Aktif ay: GET parametresi varsa onu kullan, yoksa kilitlenmemiş en son ayı bul
        $selectedYear  = (int)($_GET['year']  ?? 0);
        $selectedMonth = (int)($_GET['month'] ?? 0);

        if (!$selectedYear || !$selectedMonth) {
            // Kilitlenmemiş en son ay
            $latestOpen = Database::fetch(
                "SELECT year, month FROM months WHERE is_locked = 0 ORDER BY year DESC, month DESC LIMIT 1"
            );
            if ($latestOpen) {
                $selectedYear  = (int)$latestOpen['year'];
                $selectedMonth = (int)$latestOpen['month'];
            } else {
                // Tüm aylar kilitliyse en son aya git
                $latestAny = Database::fetch(
                    "SELECT year, month FROM months ORDER BY year DESC, month DESC LIMIT 1"
                );
                $selectedYear  = $latestAny ? (int)$latestAny['year']  : (int)date('Y');
                $selectedMonth = $latestAny ? (int)$latestAny['month'] : (int)date('n');
            }
        }

        // Ay kaydı var mı kontrol et
        $month = Database::fetch(
            "SELECT * FROM months WHERE year = ? AND month = ?",
            [$selectedYear, $selectedMonth]
        );

        $summary = null;
        $unpaidDebts = 0;
        if ($month) {
            $summary = Calculator::monthlySummary($month['id']);
            
            // 1. Krediler: Cari ayın bekleyen kredileri
            $loanTotal = Database::fetch("
                SELECT COALESCE(SUM(ip.amount), 0) as total 
                FROM installment_payments ip
                JOIN installments i ON ip.installment_id = i.id
                WHERE ip.year = ? AND ip.month = ? AND ip.is_paid = 0 AND i.category = 'loan'
            ", [$selectedYear, $selectedMonth])['total'];

            // 2. Taksitli Borçlar: BİR SONRAKİ ayın bekleyen taksitleri (Sizin ödeme düzeninize göre)
            $nextMonth = $selectedMonth + 1;
            $nextYear = $selectedYear;
            if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

            $installmentTotal = Database::fetch("
                SELECT COALESCE(SUM(ip.amount), 0) as total 
                FROM installment_payments ip
                JOIN installments i ON ip.installment_id = i.id
                WHERE ip.year = ? AND ip.month = ? AND ip.is_paid = 0 AND i.category = 'installment'
            ", [$nextYear, $nextMonth])['total'];

            $unpaidDebts = (float)$loanTotal + (float)$installmentTotal;
        }

        // Kalan POS bakiyesi — POS sayfasındaki KALAN POS BAKİYESİ ile aynı formül
        $posNetBalance = 0;
        if ($month) {
            $globalRate = (float)(Database::fetch("SELECT setting_value FROM settings WHERE setting_key = 'pos_default_commission'")['setting_value'] ?? '0.0199');
            $monthRate  = $month['pos_commission_rate'] !== null ? (float)$month['pos_commission_rate'] : $globalRate;

            $totDailyPos = 0;
            $totDailyComm = 0;
            $totManualPos = 0;
            $totNeg = 0;

            $activeEntries = Database::fetchAll(
                "SELECT pos_amount, pos_commission_rate FROM daily_entries WHERE month_id = ? AND pos_amount > 0",
                [$month['id']]
            );
            foreach ($activeEntries as $e) {
                $rate = $e['pos_commission_rate'] !== null ? (float)$e['pos_commission_rate'] : $monthRate;
                $totDailyPos  += (float)$e['pos_amount'];
                $totDailyComm += (float)$e['pos_amount'] * $rate;
            }

            $manualEntries = Database::fetchAll(
                "SELECT total_amount FROM pos_commissions WHERE month_id = ?",
                [$month['id']]
            );
            foreach ($manualEntries as $me) {
                $amt = (float)$me['total_amount'];
                if ($amt >= 0) $totManualPos += $amt;
                else           $totNeg       += $amt;
            }

            $posNetBalance = ($totDailyPos - $totDailyComm) + $totManualPos + $totNeg;
        }

        // Yapılacaklar özeti
        $taskSummary = Database::fetch("
            SELECT
                COUNT(*) as total,
                SUM(is_done = 0) as pending,
                SUM(is_done = 0 AND priority = 'high') as urgent
            FROM tasks
        ");

        $taskCategories = Database::fetchAll("
            SELECT tc.id, tc.name, tc.icon, tc.color,
                   COUNT(t.id) as total,
                   SUM(t.is_done = 0) as pending
            FROM task_categories tc
            LEFT JOIN tasks t ON t.category_id = tc.id
            WHERE tc.is_active = 1
            GROUP BY tc.id
            HAVING total > 0
            ORDER BY pending DESC, tc.sort_order
        ");

        $urgentTasks = Database::fetchAll("
            SELECT t.id, t.title, t.priority, tc.name as cat_name, tc.color, tc.icon
            FROM tasks t
            JOIN task_categories tc ON t.category_id = tc.id
            WHERE t.is_done = 0
            ORDER BY FIELD(t.priority,'high','medium','low'), t.created_at DESC
            LIMIT 8
        ");

        $pageTitle = 'Dashboard';
        $currentPage = 'dashboard';
        $availableMonths = Database::fetchAll("SELECT * FROM months ORDER BY year DESC, month DESC");
        
        // Layout'u yükle — layout içinden templates/dashboard/index.php include edilecek
        require BASE_PATH . '/templates/layout.php';
    }
}
