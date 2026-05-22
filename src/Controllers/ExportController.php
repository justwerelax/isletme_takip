<?php
class ExportController {

    // ── Dışa Aktarma Sayfası ─────────────────────────────────────────────────
    public function index() {
        $user = Auth::user();

        $months = Database::fetchAll("SELECT * FROM months ORDER BY year DESC, month DESC");

        $pageTitle   = 'Veri Dışa Aktarma';
        $currentPage = 'export';
        require BASE_PATH . '/templates/layout.php';
    }

    // ── CSV: Günlük Girişler ──────────────────────────────────────────────────
    public function entriesCsv() {
        $year  = (int)($_GET['year']  ?? 0);
        $month = (int)($_GET['month'] ?? 0);

        // Tüm aylar veya belirli ay
        if ($year && $month) {
            $monthRows = Database::fetchAll(
                "SELECT * FROM months WHERE year = ? AND month = ? ORDER BY year DESC, month DESC",
                [$year, $month]
            );
        } else {
            $monthRows = Database::fetchAll("SELECT * FROM months ORDER BY year ASC, month ASC");
        }

        $categories = Database::fetchAll("SELECT * FROM expense_categories WHERE is_active = 1 ORDER BY sort_order");

        $filename = $year && $month
            ? "gunluk-girişler-{$year}-{$month}.csv"
            : "gunluk-girişler-tumu.csv";

        $this->csvHeaders($filename);

        $out = fopen('php://output', 'w');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM

        // Başlık satırı
        $header = ['Tarih', 'Ay', 'Yıl', 'Cüro (₺)', 'Dış Gelir (₺)', 'POS (₺)', 'Notlar'];
        foreach ($categories as $cat) {
            $header[] = $cat['name'] . ' Gideri (₺)';
        }
        $header[] = 'Toplam Gider (₺)';
        $header[] = 'Net Kâr (₺)';
        fputcsv($out, $header, ';');

        $monthNames = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran',
                       'Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

        foreach ($monthRows as $m) {
            $entries = Database::fetchAll(
                "SELECT * FROM daily_entries WHERE month_id = ? ORDER BY entry_date",
                [$m['id']]
            );

            foreach ($entries as $entry) {
                $expenses = Database::fetchAll(
                    "SELECT category_id, amount FROM daily_expenses WHERE daily_entry_id = ?",
                    [$entry['id']]
                );
                $expMap = [];
                $totalExp = 0;
                foreach ($expenses as $e) {
                    $expMap[$e['category_id']] = (float)$e['amount'];
                    $totalExp += (float)$e['amount'];
                }

                $row = [
                    $entry['entry_date'],
                    $monthNames[$m['month']],
                    $m['year'],
                    number_format((float)$entry['revenue'], 2, ',', '.'),
                    number_format((float)$entry['external_revenue'], 2, ',', '.'),
                    number_format((float)$entry['pos_amount'], 2, ',', '.'),
                    $entry['notes'] ?? '',
                ];

                foreach ($categories as $cat) {
                    $amt = $expMap[$cat['id']] ?? 0;
                    $row[] = number_format(abs($amt), 2, ',', '.');
                }

                $curo = (float)$entry['revenue'] + (float)$entry['external_revenue'];
                $row[] = number_format(abs($totalExp), 2, ',', '.');
                $row[] = number_format($curo + $totalExp, 2, ',', '.');

                fputcsv($out, $row, ';');
            }
        }

        fclose($out);
        exit;
    }

    // ── CSV: Avanslar ─────────────────────────────────────────────────────────
    public function advancesCsv() {
        $year  = (int)($_GET['year']  ?? 0);
        $month = (int)($_GET['month'] ?? 0);

        if ($year && $month) {
            $advances = Database::fetchAll("
                SELECT a.*, p.name as partner_name, m.year, m.month
                FROM advances a
                JOIN partners p ON a.partner_id = p.id
                JOIN months m ON a.month_id = m.id
                WHERE m.year = ? AND m.month = ?
                ORDER BY a.advance_date
            ", [$year, $month]);
        } else {
            $advances = Database::fetchAll("
                SELECT a.*, p.name as partner_name, m.year, m.month
                FROM advances a
                JOIN partners p ON a.partner_id = p.id
                JOIN months m ON a.month_id = m.id
                ORDER BY m.year ASC, m.month ASC, a.advance_date
            ");
        }

        $filename = $year && $month ? "avanslar-{$year}-{$month}.csv" : "avanslar-tumu.csv";
        $this->csvHeaders($filename);

        $out = fopen('php://output', 'w');
        echo "\xEF\xBB\xBF";

        fputcsv($out, ['Tarih', 'Yıl', 'Ay', 'Ortak', 'Tutar (₺)', 'Açıklama'], ';');

        $monthNames = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran',
                       'Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

        foreach ($advances as $a) {
            fputcsv($out, [
                $a['advance_date'],
                $a['year'],
                $monthNames[$a['month']],
                $a['partner_name'],
                number_format((float)$a['amount'], 2, ',', '.'),
                $a['description'] ?? '',
            ], ';');
        }

        fclose($out);
        exit;
    }

    // ── CSV: Taksitler / Krediler ─────────────────────────────────────────────
    public function installmentsCsv() {
        $category = $_GET['category'] ?? 'installment'; // installment | loan

        $installments = Database::fetchAll(
            "SELECT * FROM installments WHERE category = ? ORDER BY created_at DESC",
            [$category]
        );

        $filename = ($category === 'loan') ? "krediler.csv" : "taksitli-borclar.csv";
        $this->csvHeaders($filename);

        $out = fopen('php://output', 'w');
        echo "\xEF\xBB\xBF";

        fputcsv($out, ['Başlık', 'Toplam Tutar (₺)', 'Taksit Sayısı', 'Başlangıç', 'Durum',
                       'Taksit No', 'Yıl', 'Ay', 'Taksit Tutarı (₺)', 'Ödendi mi', 'Ödeme Tarihi'], ';');

        $monthNames = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran',
                       'Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

        foreach ($installments as $inst) {
            $payments = Database::fetchAll(
                "SELECT * FROM installment_payments WHERE installment_id = ? ORDER BY year ASC, month ASC",
                [$inst['id']]
            );

            foreach ($payments as $p) {
                fputcsv($out, [
                    $inst['title'],
                    number_format((float)$inst['total_amount'], 2, ',', '.'),
                    $inst['total_installments'],
                    $inst['start_year'] . '/' . $inst['start_month'],
                    $inst['is_completed'] ? 'Tamamlandı' : 'Devam Ediyor',
                    $p['installment_number'],
                    $p['year'],
                    $monthNames[$p['month']],
                    number_format((float)$p['amount'], 2, ',', '.'),
                    $p['is_paid'] ? 'Evet' : 'Hayır',
                    $p['paid_at'] ?? '',
                ], ';');
            }
        }

        fclose($out);
        exit;
    }

    // ── CSV: Aylık Özet ───────────────────────────────────────────────────────
    public function summaryCsv() {
        $months = Database::fetchAll("SELECT * FROM months ORDER BY year ASC, month ASC");

        $this->csvHeaders("aylik-ozet.csv");

        $out = fopen('php://output', 'w');
        echo "\xEF\xBB\xBF";

        fputcsv($out, [
            'Yıl', 'Ay', 'Çalışma Günü', 'Cüro (₺)', 'Dış Gelir (₺)',
            'Toplam Gelir (₺)', 'Toplam Gider (₺)', 'Net Kâr (₺)',
            'Kasa Devir (₺)', 'Rezerv Devir (₺)', 'Kilitli mi'
        ], ';');

        $monthNames = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran',
                       'Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

        foreach ($months as $m) {
            $summary = Calculator::monthlySummary($m['id'], false);
            if (empty($summary)) continue;

            fputcsv($out, [
                $m['year'],
                $monthNames[$m['month']],
                $summary['working_days'],
                number_format($summary['total_revenue'], 2, ',', '.'),
                number_format($summary['total_external_revenue'], 2, ',', '.'),
                number_format($summary['active_curo'], 2, ',', '.'),
                number_format(abs($summary['total_expenses']), 2, ',', '.'),
                number_format($summary['active_profit'], 2, ',', '.'),
                number_format($summary['cash_carryover'], 2, ',', '.'),
                number_format($summary['reserve_carryover'], 2, ',', '.'),
                $m['is_locked'] ? 'Evet' : 'Hayır',
            ], ';');
        }

        fclose($out);
        exit;
    }

    // ── JSON: Tam Yedek ───────────────────────────────────────────────────────
    public function fullBackupJson() {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="isletme-yedek-' . date('Y-m-d') . '.json"');

        $data = [
            'export_tarihi'       => date('Y-m-d H:i:s'),
            'versiyon'            => '1.0',
            'partners'            => Database::fetchAll("SELECT * FROM partners ORDER BY sort_order"),
            'expense_categories'  => Database::fetchAll("SELECT * FROM expense_categories ORDER BY sort_order"),
            'months'              => [],
            'installments'        => [],
            'pos_commissions'     => Database::fetchAll("SELECT * FROM pos_commissions ORDER BY created_at"),
        ];

        // Aylar + girişler + giderler + avanslar
        $months = Database::fetchAll("SELECT * FROM months ORDER BY year ASC, month ASC");
        foreach ($months as $m) {
            $entries = Database::fetchAll(
                "SELECT * FROM daily_entries WHERE month_id = ? ORDER BY entry_date",
                [$m['id']]
            );
            foreach ($entries as &$entry) {
                $entry['expenses'] = Database::fetchAll(
                    "SELECT * FROM daily_expenses WHERE daily_entry_id = ?",
                    [$entry['id']]
                );
            }
            unset($entry);

            $m['entries']  = $entries;
            $m['advances'] = Database::fetchAll(
                "SELECT * FROM advances WHERE month_id = ? ORDER BY advance_date",
                [$m['id']]
            );
            $data['months'][] = $m;
        }

        // Taksitler + ödemeler
        $installments = Database::fetchAll("SELECT * FROM installments ORDER BY created_at");
        foreach ($installments as &$inst) {
            $inst['payments'] = Database::fetchAll(
                "SELECT * FROM installment_payments WHERE installment_id = ? ORDER BY year ASC, month ASC",
                [$inst['id']]
            );
        }
        unset($inst);
        $data['installments'] = $installments;

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // ── Yardımcı ─────────────────────────────────────────────────────────────
    private function csvHeaders(string $filename): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
    }
}
