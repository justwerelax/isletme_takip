<?php
class InstallmentController {
    private $category;
    private $pageName;

    public function __construct() {
        $page = $_GET['page'] ?? 'installments';
        $this->category = ($page === 'loans') ? 'loan' : 'installment';
        $this->pageName = $page;
    }

    public function index() {
        $installments = Database::fetchAll("SELECT * FROM installments WHERE category = ? ORDER BY is_completed ASC, created_at DESC", [$this->category]);
        
        // Tüm ödenmiş ayları getir (Arşiv Tablosu için)
        $archiveHeader = Database::fetchAll("
            SELECT DISTINCT ip.year, ip.month 
            FROM installment_payments ip
            JOIN installments i ON ip.installment_id = i.id
            WHERE ip.is_paid = 1 AND i.category = ?
            ORDER BY ip.year ASC, ip.month ASC
        ", [$this->category]);

        // Ödenecekleri Ay Bazında Grupla (Üst kartlar için)
        $rawUpcoming = Database::fetchAll("
            SELECT ip.*, i.title, i.total_installments, i.payment_type 
            FROM installment_payments ip
            JOIN installments i ON ip.installment_id = i.id
            WHERE ip.is_paid = 0 AND i.category = ?
            ORDER BY ip.year ASC, ip.month ASC, (i.total_installments - ip.installment_number) ASC
        ", [$this->category]);

        $grouped = [];
        foreach ($rawUpcoming as $p) {
            $key = $p['year'] . '-' . $p['month'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'year' => $p['year'],
                    'month' => $p['month'],
                    'total_amount' => 0,
                    'item_count' => 0,
                    'items' => []
                ];
            }
            $grouped[$key]['total_amount'] += $p['amount'];
            $grouped[$key]['item_count']++;
            $grouped[$key]['items'][] = $p;
        }
        $upcomingMonths = array_values($grouped);

        $grandTotalUnpaid = array_sum(array_column($rawUpcoming, 'amount'));

        // Grid verilerini hazırla
        $grid = [];
        $payments = Database::fetchAll("
            SELECT ip.* FROM installment_payments ip 
            JOIN installments i ON ip.installment_id = i.id 
            WHERE i.category = ?
        ", [$this->category]);
        foreach ($payments as $p) {
            $grid[$p['installment_id']]["{$p['year']}-{$p['month']}"] = $p;
        }

        $pageTitle = ($this->category === 'loan') ? 'Krediler' : 'Taksitli Borçlar';
        $currentPage = $this->pageName;
        $viewPath = 'installments';
        $user = Auth::user();

        require BASE_PATH . '/templates/layout.php';
    }

    public function payMonth() {
        Auth::requireAdmin();
        $year = (int)($_GET['year'] ?? 0);
        $month = (int)($_GET['month'] ?? 0);

        if ($year && $month) {
            Database::query("
                UPDATE installment_payments ip
                JOIN installments i ON ip.installment_id = i.id
                SET ip.is_paid = 1, ip.paid_at = ?
                WHERE ip.year = ? AND ip.month = ? AND i.category = ?
            ", [date('Y-m-d H:i:s'), $year, $month, $this->category]);

            // Update is_completed for all affected installments
            $affectedInsts = Database::fetchAll("
                SELECT DISTINCT ip.installment_id FROM installment_payments ip
                JOIN installments i ON ip.installment_id = i.id
                WHERE ip.year = ? AND ip.month = ? AND i.category = ?
            ", [$year, $month, $this->category]);

            foreach ($affectedInsts as $row) {
                $rem = Database::fetch("SELECT COUNT(*) as c FROM installment_payments WHERE installment_id = ? AND is_paid = 0", [$row['installment_id']]);
                if ($rem['c'] == 0) {
                    Database::update('installments', ['is_completed' => 1], 'id = ?', [$row['installment_id']]);
                }
            }

            // Sadece taksit kategorisinin toplamını ayın son gününe yaz
            $this->removeExpenseFromMonth($year, $month);
            $paidTotal = $this->categoryPaidTotal($year, $month);
            if ($paidTotal > 0) {
                $this->writeExpenseToMonth($year, $month, $paidTotal);
            }

            $_SESSION['flash_success'] = "{$year} / {$month} ödemeleri tamamlandı ve gidere işlendi.";
        }
        header('Location: ?page=' . $this->pageName);
        exit;
    }

    public function unpayMonth() {
        Auth::requireAdmin();
        $year = (int)($_GET['year'] ?? 0);
        $month = (int)($_GET['month'] ?? 0);

        if ($year && $month) {
            // Önce mevcut gideri temizle (tüm kategorilerin toplamı silinecek)
            $this->removeExpenseFromMonth($year, $month);

            // Bu kategorinin ödemelerini geri al
            Database::query("
                UPDATE installment_payments ip
                JOIN installments i ON ip.installment_id = i.id
                SET ip.is_paid = 0, ip.paid_at = NULL
                WHERE ip.year = ? AND ip.month = ? AND i.category = ?
            ", [$year, $month, $this->category]);

            // Reactivate affected installments
            $affectedInsts = Database::fetchAll("
                SELECT DISTINCT ip.installment_id FROM installment_payments ip
                JOIN installments i ON ip.installment_id = i.id
                WHERE ip.year = ? AND ip.month = ? AND i.category = ?
            ", [$year, $month, $this->category]);

            foreach ($affectedInsts as $row) {
                Database::update('installments', ['is_completed' => 0], 'id = ?', [$row['installment_id']]);
            }

            // Bu kategoriden hâlâ ödendi işaretli taksit varsa yeniden yaz
            $remaining = $this->categoryPaidTotal($year, $month);
            if ($remaining > 0) {
                $this->writeExpenseToMonth($year, $month, $remaining);
            }

            $_SESSION['flash_success'] = "{$year} / {$month} ödemeleri geri alındı.";
        }
        header('Location: ?page=' . $this->pageName);
        exit;
    }

    public function store() {
        Auth::requireAdmin();
        $title = trim($_POST['title'] ?? '');
        $totalInstallments = (int)($_POST['total_installments'] ?? 1);
        $paymentType = $_POST['payment_type'] ?? 'equal';
        $startYear = (int)($_POST['start_year'] ?? date('Y'));
        $startMonth = (int)($_POST['start_month'] ?? date('n'));
        $notes = trim($_POST['notes'] ?? '');

        if ($title && $totalInstallments > 0) {
            $totalAmount = 0;
            $installmentAmounts = [];

            if ($paymentType === 'variable') {
                $installmentAmounts = $_POST['amounts'] ?? [];
                $totalAmount = array_sum($installmentAmounts);
            } else {
                $totalAmount = (float)($_POST['total_amount'] ?? 0);
                $amountPerInstallment = $totalAmount / $totalInstallments;
                for ($i = 0; $i < $totalInstallments; $i++) {
                    $installmentAmounts[] = $amountPerInstallment;
                }
            }

            $installmentId = Database::insert('installments', [
                'title' => $title,
                'total_amount' => $totalAmount,
                'total_installments' => $totalInstallments,
                'payment_type' => $paymentType,
                'category' => $this->category,
                'start_year' => $startYear,
                'start_month' => $startMonth,
                'notes' => $notes
            ]);

            // Taksitleri oluştur
            $currYear = $startYear;
            $currMonth = $startMonth;

            foreach ($installmentAmounts as $index => $amount) {
                Database::insert('installment_payments', [
                    'installment_id' => $installmentId,
                    'installment_number' => $index + 1,
                    'year' => $currYear,
                    'month' => $currMonth,
                    'amount' => (float)$amount,
                    'is_paid' => 0
                ]);

                $currMonth++;
                if ($currMonth > 12) {
                    $currMonth = 1;
                    $currYear++;
                }
            }

            $_SESSION['flash_success'] = ($this->category === 'loan' ? 'Kredi' : 'Taksitli borç') . ' başarıyla eklendi.';
        } else {
            $_SESSION['flash_error'] = 'Lütfen tüm alanları doğru doldurun.';
        }

        header('Location: ?page=' . $this->pageName);
        exit;
    }

    public function togglePayment() {
        $id = (int)($_GET['id'] ?? 0);
        $payment = Database::fetch("SELECT * FROM installment_payments WHERE id = ?", [$id]);
        
        if ($payment) {
            $newStatus = $payment['is_paid'] ? 0 : 1;
            $paidAt = $newStatus ? date('Y-m-d H:i:s') : null;
            
            Database::update('installment_payments', 
                ['is_paid' => $newStatus, 'paid_at' => $paidAt],
                'id = ?', [$id]
            );
            
            // Check if all payments for this installment are done
            $remaining = Database::fetch("
                SELECT COUNT(*) as count FROM installment_payments 
                WHERE installment_id = ? AND is_paid = 0
            ", [$payment['installment_id']]);
            
            $isCompleted = ($remaining['count'] == 0) ? 1 : 0;
            Database::update('installments', ['is_completed' => $isCompleted], 'id = ?', [$payment['installment_id']]);

            echo json_encode(['success' => true, 'is_paid' => (bool)$newStatus, 'is_completed' => (bool)$isCompleted]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Payment not found']);
        }
        exit;
    }

    public function getDetails() {
        $id = (int)($_GET['id'] ?? 0);
        $installment = Database::fetch("SELECT * FROM installments WHERE id = ?", [$id]);
        $payments = Database::fetchAll("SELECT * FROM installment_payments WHERE installment_id = ? ORDER BY year ASC, month ASC", [$id]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'installment' => $installment,
            'payments' => $payments
        ]);
        exit;
    }

    public function toggleSingle() {
        Auth::requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $payment = Database::fetch("SELECT * FROM installment_payments WHERE id = ?", [$id]);

        if ($payment) {
            // Krediler için artık markLoanPaid/markLoanUnpaid kullanılıyor.
            // Bu action yalnızca taksitler (installments) için basit toggle yapar — gider yazmaz.
            $newStatus = $payment['is_paid'] ? 0 : 1;
            $paidAt    = $newStatus ? date('Y-m-d H:i:s') : null;
            Database::update('installment_payments',
                ['is_paid' => $newStatus, 'paid_at' => $paidAt],
                'id = ?', [$id]
            );

            $rem = Database::fetch("SELECT COUNT(*) as c FROM installment_payments WHERE installment_id = ? AND is_paid = 0", [$payment['installment_id']]);
            Database::update('installments', ['is_completed' => ($rem['c'] == 0 ? 1 : 0)], 'id = ?', [$payment['installment_id']]);

            $_SESSION['flash_success'] = 'Ödeme durumu güncellendi.';
        }

        header('Location: ?page=' . $this->pageName);
        exit;
    }

    // ── Kredi Ödemesi — Tarih Seçerek Gidere Ekle ────────────────────────────
    public function markLoanPaid() {
        Auth::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=loans'); exit;
        }

        $id         = (int)$_POST['payment_id'];
        $targetDate = $_POST['target_date'] ?? '';

        $payment = Database::fetch("
            SELECT ip.*, i.title FROM installment_payments ip
            JOIN installments i ON ip.installment_id = i.id
            WHERE ip.id = ?
        ", [$id]);

        if (!$payment || $payment['is_paid']) {
            $_SESSION['flash_error'] = 'Geçersiz işlem.';
            header('Location: ?page=loans'); exit;
        }
        if (!$targetDate) {
            $_SESSION['flash_error'] = 'Lütfen bir tarih seçin.';
            header('Location: ?page=loans'); exit;
        }

        // Hedef entry bul
        $targetEntry = Database::fetch("
            SELECT de.* FROM daily_entries de
            JOIN months m ON de.month_id = m.id
            WHERE de.entry_date = ? AND m.is_locked = 0
            LIMIT 1
        ", [$targetDate]);

        if (!$targetEntry) {
            $fmt = date('d.m.Y', strtotime($targetDate));
            $_SESSION['flash_error'] = "{$fmt} tarihinde açık bir günlük giriş bulunamadı. Önce o tarihe giriş ekleyin.";
            header('Location: ?page=loans'); exit;
        }

        // Ödendi işaretle + hangi entry'e yazıldığını sakla
        Database::update('installment_payments', [
            'is_paid'          => 1,
            'paid_at'          => date('Y-m-d H:i:s'),
            'expense_entry_id' => $targetEntry['id'],
        ], 'id = ?', [$id]);

        // is_completed güncelle
        $rem = Database::fetch("SELECT COUNT(*) as c FROM installment_payments WHERE installment_id = ? AND is_paid = 0", [$payment['installment_id']]);
        Database::update('installments', ['is_completed' => ($rem['c'] == 0 ? 1 : 0)], 'id = ?', [$payment['installment_id']]);

        // Gider yaz — expense_entry_id kaydedildikten SONRA recalc çağır
        $this->recalcLoanExpenseForEntry((int)$targetEntry['id']);
        if (true) {
            $fmt = date('d.m.Y', strtotime($targetDate));
            $amtFmt = number_format((float)$payment['amount'], 2, ',', '.');
            $_SESSION['flash_success'] = "'{$payment['title']}' ödemesi ({$amtFmt} ₺) → {$fmt} tarihli girişe gider olarak eklendi.";
        }

        header('Location: ?page=loans'); exit;
    }

    public function markLoanUnpaid() {
        Auth::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=loans'); exit;
        }

        $id = (int)$_POST['payment_id'];
        $payment = Database::fetch("SELECT * FROM installment_payments WHERE id = ?", [$id]);

        if (!$payment || !$payment['is_paid']) {
            $_SESSION['flash_error'] = 'Geçersiz işlem.';
            header('Location: ?page=loans'); exit;
        }

        $oldEntryId = $payment['expense_entry_id'] ? (int)$payment['expense_entry_id'] : null;

        // Önce ödemeyi geri al (expense_entry_id temizle)
        Database::update('installment_payments', [
            'is_paid'          => 0,
            'paid_at'          => null,
            'expense_entry_id' => null,
        ], 'id = ?', [$id]);

        // Sonra o entry'deki gideri yeniden hesapla (bu payment artık is_paid=0 olduğu için dışarıda kalır)
        if ($oldEntryId) {
            $this->recalcLoanExpenseForEntry($oldEntryId);
        }

        Database::update('installments', ['is_completed' => 0], 'id = ?', [$payment['installment_id']]);

        $_SESSION['flash_success'] = 'Kredi ödemesi geri alındı, gider silindi.';
        header('Location: ?page=loans'); exit;
    }

    // ── Kredi gider yardımcıları ──────────────────────────────────────────────

    /**
     * Belirtilen entry için ödenmekte olan tüm kredileri yeniden hesaplar ve gider satırını günceller.
     * Bu fonksiyon hem ödeme hem geri alma durumunda çağrılır — her zaman doğru tutarı yazar.
     */
    private function recalcLoanExpenseForEntry(int $entryId): void {
        // Önce "kredi-odeme" alt kategorisini dene, yoksa "fabrika" ana kategorisi
        $cat = Database::fetch("SELECT id FROM expense_categories WHERE slug = 'kredi-odeme' AND is_active = 1")
            ?? Database::fetch("SELECT id FROM expense_categories WHERE slug = 'fabrika'  AND is_active = 1");
        if (!$cat) return;
        $catId = (int)$cat['id'];

        // Bu entry'e yazılmış ve hâlâ ödendi olan tüm kredileri çek
        $loans = Database::fetchAll("
            SELECT ip.amount, i.title
            FROM installment_payments ip
            JOIN installments i ON ip.installment_id = i.id
            WHERE ip.expense_entry_id = ? AND ip.is_paid = 1 AND i.category = 'loan'
        ", [$entryId]);

        if (empty($loans)) {
            // Bu entry'e yazılmış kredi kalmadı — satırı sil
            Database::delete('daily_expenses',
                "daily_entry_id = ? AND category_id = ?",
                [$entryId, $catId]
            );
        } else {
            $total = array_sum(array_column($loans, 'amount'));
            $notes = implode(' + ', array_column($loans, 'title'));
            if (strlen($notes) > 95) $notes = substr($notes, 0, 92) . '...';

            Database::query("
                INSERT INTO daily_expenses (daily_entry_id, category_id, amount, notes)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE amount = VALUES(amount), notes = VALUES(notes)
            ", [$entryId, $catId, -abs($total), $notes]);
        }
    }

    private function writeLoanExpenseToEntry(int $entryId, float $amount): bool {
        $cat = Database::fetch("SELECT id FROM expense_categories WHERE slug = 'kredi-odeme' AND is_active = 1")
            ?? Database::fetch("SELECT id FROM expense_categories WHERE slug = 'fabrika'  AND is_active = 1");
        if (!$cat) {
            $_SESSION['flash_error'] = 'Kredi Ödemesi gider kategorisi bulunamadı.';
            return false;
        }
        $this->recalcLoanExpenseForEntry($entryId);
        return true;
    }

    private function removeLoanExpenseFromEntry(int $entryId, float $amount): void {
        $this->recalcLoanExpenseForEntry($entryId);
    }

    /**
     * Tüm ödendi işaretli taksit+kredi toplamını o ayın son günündeki fabrika giderine yazar.
     * Döner: true = başarılı, false = ay kayıtlı değil veya kilitli.
     */
    private function writeExpenseToMonth(int $year, int $month, float $amount): bool {
        $fabrikaCat = Database::fetch("SELECT id FROM expense_categories WHERE slug = 'fabrika' AND is_active = 1");
        if (!$fabrikaCat) {
            $_SESSION['flash_error'] = 'Fabrika gider kategorisi bulunamadı — lütfen ayarları kontrol edin.';
            return false;
        }
        $catId = (int)$fabrikaCat['id'];

        $monthRow = Database::fetch("SELECT * FROM months WHERE year = ? AND month = ?", [$year, $month]);
        if (!$monthRow) {
            $_SESSION['flash_error'] = "{$year}/{$month} ayı henüz oluşturulmamış; gider yazılamadı.";
            return false;
        }
        if ($monthRow['is_locked']) {
            $_SESSION['flash_error'] = "{$year}/{$month} ayı kilitli; gider yazılamadı.";
            return false;
        }

        $lastDay = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
        $entry   = Database::fetch("SELECT id FROM daily_entries WHERE month_id = ? AND entry_date = ?", [$monthRow['id'], $lastDay]);

        if (!$entry) {
            $entryId = Database::insert('daily_entries', [
                'month_id'         => $monthRow['id'],
                'entry_date'       => $lastDay,
                'revenue'          => 0,
                'external_revenue' => 0,
                'pos_amount'       => 0,
                'notes'            => 'Otomatik: Taksit/Kredi ödemesi'
            ]);
        } else {
            $entryId = (int)$entry['id'];
        }

        // UNIQUE(daily_entry_id, category_id) kısıtı — ON DUPLICATE KEY UPDATE ile güvenli yaz
        Database::query("
            INSERT INTO daily_expenses (daily_entry_id, category_id, amount, notes)
            VALUES (?, ?, ?, 'otomatik_odeme')
            ON DUPLICATE KEY UPDATE amount = VALUES(amount), notes = 'otomatik_odeme'
        ", [$entryId, $catId, -abs($amount)]);

        return true;
    }

    /**
     * Otomatik oluşturulan fabrika gider satırını temizler.
     * Satır başka kaynaktan da doluysa sadece fabrika satırını siler.
     */
    private function removeExpenseFromMonth(int $year, int $month): void {
        $fabrikaCat = Database::fetch("SELECT id FROM expense_categories WHERE slug = 'fabrika' AND is_active = 1");
        if (!$fabrikaCat) return;
        $catId = (int)$fabrikaCat['id'];

        $monthRow = Database::fetch("SELECT id FROM months WHERE year = ? AND month = ?", [$year, $month]);
        if (!$monthRow) return;

        $lastDay = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
        $entry   = Database::fetch(
            "SELECT id, revenue, external_revenue, pos_amount, notes FROM daily_entries WHERE month_id = ? AND entry_date = ?",
            [$monthRow['id'], $lastDay]
        );
        if (!$entry) return;

        $entryId = (int)$entry['id'];

        // Otomatik yazılan gider satırını sil
        Database::delete('daily_expenses',
            "daily_entry_id = ? AND category_id = ? AND notes = 'otomatik_odeme'",
            [$entryId, $catId]
        );

        // Entry tamamen boşsa ve otomatik oluşturulmuşsa temizle
        $hasExpenses = (int)(Database::fetch("SELECT COUNT(*) as c FROM daily_expenses WHERE daily_entry_id = ?", [$entryId])['c'] ?? 0);
        if (
            $hasExpenses === 0 &&
            (float)$entry['revenue'] == 0 &&
            (float)$entry['external_revenue'] == 0 &&
            (float)$entry['pos_amount'] == 0 &&
            $entry['notes'] === 'Otomatik: Taksit/Kredi ödemesi'
        ) {
            Database::delete('daily_entries', 'id = ?', [$entryId]);
        }
    }

    /**
     * Belirtilen ay için sadece bu kategorinin (loan VEYA installment) ödendi toplamını döner.
     */
    private function categoryPaidTotal(int $year, int $month): float {
        return (float)(Database::fetch("
            SELECT COALESCE(SUM(ip.amount), 0) as total
            FROM installment_payments ip
            JOIN installments i ON ip.installment_id = i.id
            WHERE ip.year = ? AND ip.month = ? AND ip.is_paid = 1 AND i.category = ?
        ", [$year, $month, $this->category])['total'] ?? 0);
    }

    public function updateTotal() {
        Auth::requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        $newTotal = (float)($_POST['total_amount'] ?? 0);
        
        $inst = Database::fetch("SELECT * FROM installments WHERE id = ?", [$id]);
        if ($inst && $newTotal > 0) {
            $amountPerInst = $newTotal / $inst['total_installments'];
            
            Database::update('installments', ['total_amount' => $newTotal], 'id = ?', [$id]);
            Database::update('installment_payments', ['amount' => $amountPerInst], 'installment_id = ?', [$id]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }

    public function updatePayment() {
        Auth::requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        
        if ($id && $amount >= 0) {
            Database::update('installment_payments', ['amount' => $amount], 'id = ?', [$id]);
            
            // Re-calculate total amount in installments table
            $payment = Database::fetch("SELECT installment_id FROM installment_payments WHERE id = ?", [$id]);
            $total = Database::fetch("SELECT SUM(amount) as s FROM installment_payments WHERE installment_id = ?", [$payment['installment_id']]);
            Database::update('installments', ['total_amount' => $total['s']], 'id = ?', [$payment['installment_id']]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }

    public function delete() {
        Auth::requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        Database::delete('installment_payments', 'installment_id = ?', [$id]);
        Database::delete('installments', 'id = ?', [$id]);
        $_SESSION['flash_success'] = ($this->category === 'loan' ? 'Kredi' : 'Borç') . ' kaydı silindi.';
        header('Location: ?page=' . $this->pageName);
        exit;
    }
}
