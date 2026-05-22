<?php
class ReportController {
    public function index() {
        $user = Auth::user();

        // Son 12 ayın özetini çek
        $months = Database::fetchAll("SELECT * FROM months ORDER BY year DESC, month DESC LIMIT 12");
        
        $comparisons = [];
        foreach ($months as $m) {
            $summary = Calculator::monthlySummary($m['id']);
            $comparisons[] = [
                'month_name' => $this->getMonthName($m['month']) . ' ' . $m['year'],
                'curo' => $summary['active_curo'],
                'profit' => $summary['active_profit'],
                'expense' => $summary['total_expenses'],
                'working_days' => $summary['working_days']
            ];
        }

        $chartLabels  = array_reverse(array_column($comparisons, 'month_name'));
        $chartCuro    = array_reverse(array_column($comparisons, 'curo'));
        $chartProfit  = array_reverse(array_column($comparisons, 'profit'));
        $chartExpense = array_reverse(array_column($comparisons, 'expense'));

        $pageTitle   = 'Raporlar & Analizler';
        $currentPage = 'reports';
        require BASE_PATH . '/templates/layout.php';
    }

    // -------------------------------------------------------
    // Ay Sonu Raporu — yazdırılabilir sayfa
    // -------------------------------------------------------
    public function monthlyReport() {
        $user = Auth::user();

        $year  = (int)($_GET['year']  ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $monthRow = Database::fetch(
            "SELECT * FROM months WHERE year = ? AND month = ?",
            [$year, $month]
        );

        if (!$monthRow) {
            $_SESSION['flash_error'] = 'Bu ay için kayıt bulunamadı.';
            header("Location: ?page=reports");
            exit;
        }

        $summary = Calculator::monthlySummary($monthRow['id']);

        // Avans detayları (her ortak için)
        foreach ($summary['partners'] as &$p) {
            $p['advance_list'] = Database::fetchAll(
                "SELECT * FROM advances WHERE month_id = ? AND partner_id = ? ORDER BY advance_date",
                [$monthRow['id'], $p['id']]
            );
        }
        unset($p);

        // Önümüzdeki 3 ay ayrı ayrı kredi ve taksit borçları
        $upcomingDebts = [];
        $upcomingLoans = 0;
        $upcomingInstallments = 0;
        for ($i = 1; $i <= 3; $i++) {
            $futureMonth = $month + $i;
            $futureYear  = $year;
            while ($futureMonth > 12) { $futureMonth -= 12; $futureYear++; }

            $loanAmt = (float)Database::fetch("
                SELECT COALESCE(SUM(ip.amount),0) as total
                FROM installment_payments ip
                JOIN installments i ON ip.installment_id = i.id
                WHERE ip.year = ? AND ip.month = ? AND ip.is_paid = 0 AND i.category = 'loan'
            ", [$futureYear, $futureMonth])['total'];

            $instAmt = (float)Database::fetch("
                SELECT COALESCE(SUM(ip.amount),0) as total
                FROM installment_payments ip
                JOIN installments i ON ip.installment_id = i.id
                WHERE ip.year = ? AND ip.month = ? AND ip.is_paid = 0 AND i.category = 'installment'
            ", [$futureYear, $futureMonth])['total'];

            $upcomingDebts[] = [
                'year'         => $futureYear,
                'month'        => $futureMonth,
                'loans'        => $loanAmt,
                'installments' => $instAmt,
                'total'        => $loanAmt + $instAmt,
            ];
            $upcomingLoans        += $loanAmt;
            $upcomingInstallments += $instAmt;
        }
        $upcomingDebtsTotal = $upcomingLoans + $upcomingInstallments;

        // Kasa rezerv taşıması: bu ayki rezerv + bu ayki kasa payı
        $cashPartner = null;
        foreach ($summary['partners'] as $p) {
            if ($p['is_cash_reserve']) { $cashPartner = $p; break; }
        }
        $nextReserve = $summary['reserve_carryover'] + ($cashPartner ? $cashPartner['monthly_salary'] : 0);

        $monthNames = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran',
                       'Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

        $pageTitle   = $monthNames[$month] . ' ' . $year . ' — Ay Sonu Raporu';
        $currentPage = 'reports';
        $viewPath    = 'reports/monthly_report.php';
        require BASE_PATH . '/templates/layout.php';
    }

    private function getMonthName($m) {
        $names = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran',
                  'Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
        return $names[(int)$m] ?? '';
    }
}
