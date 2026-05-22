<?php
class MonthController {
    public function index() {
        $months = Database::fetchAll("SELECT * FROM months ORDER BY year DESC, month DESC");
        
        $pageTitle = 'Ay Yönetimi';
        $currentPage = 'months';
        
        // Önceki aydan devirleri hesaplamak için en son ayı bul
        $lastMonth = Database::fetch("SELECT * FROM months ORDER BY year DESC, month DESC LIMIT 1");
        $defaultCashCarryover = 0;
        $defaultReserveCarryover = 0;
        $nextMonth = (int)date('n');
        $nextYear = (int)date('Y');
        
        if ($lastMonth) {
            $summary = Calculator::monthlySummary($lastMonth['id']);
            $defaultCashCarryover = $summary['available_after_advances'] > 0 ? $summary['available_after_advances'] : 0;
            
            // Kasa payı ortağını bul ve reserve carryover'a ekle
            $cashPartner = array_filter($summary['partners'], fn($p) => $p['is_cash_reserve']);
            $cashPartner = reset($cashPartner);
            $cashPartnerShare = $cashPartner ? $cashPartner['monthly_salary'] : 0;
            
            $defaultReserveCarryover = $summary['reserve_carryover'] + $cashPartnerShare;
            
            $nextMonth = $lastMonth['month'] + 1;
            $nextYear = $lastMonth['year'];
            if ($nextMonth > 12) {
                $nextMonth = 1;
                $nextYear++;
            }
        }
        
        require BASE_PATH . '/templates/layout.php';
    }

    public function save() {
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $year = (int)$_POST['year'];
            $month = (int)$_POST['month'];
            $cashCarryover = (float)$_POST['cash_carryover'];
            $reserveCarryover = (float)$_POST['reserve_carryover'];
            
            if ($id > 0) {
                Database::update('months', [
                    'year' => $year,
                    'month' => $month,
                    'cash_carryover' => $cashCarryover,
                    'reserve_carryover' => $reserveCarryover
                ], "id = ?", [$id]);
                $_SESSION['flash_success'] = 'Ay bilgileri güncellendi.';
            } else {
                // Ay zaten var mı?
                $exists = Database::fetch("SELECT id FROM months WHERE year = ? AND month = ?", [$year, $month]);
                if ($exists) {
                    $_SESSION['flash_error'] = 'Bu ay zaten oluşturulmuş.';
                } else {
                    Database::insert('months', [
                        'year' => $year,
                        'month' => $month,
                        'cash_carryover' => $cashCarryover,
                        'reserve_carryover' => $reserveCarryover,
                        'is_locked' => 0
                    ]);
                    $_SESSION['flash_success'] = 'Yeni ay başarıyla oluşturuldu.';
                }
            }
        }
        
        header('Location: ?page=months');
        exit;
    }

    public function delete() {
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $month = Database::fetch("SELECT * FROM months WHERE id = ?", [$id]);
            
            if ($month) {
                if ($month['is_locked']) {
                    $_SESSION['flash_error'] = 'Kilitli bir ayı silemezsiniz.';
                } else {
                    // İlişkili tüm verileri sil (Cascase silme manuel simülasyonu)
                    // Not: Bazı DB'lerde ON DELETE CASCADE olabilir ama biz manuel yapalım
                    
                    // Günlük girişleri ve onların giderlerini sil
                    $dailyEntries = Database::fetchAll("SELECT id FROM daily_entries WHERE month_id = ?", [$id]);
                    foreach ($dailyEntries as $de) {
                        Database::query("DELETE FROM daily_expenses WHERE daily_entry_id = ?", [$de['id']]);
                    }
                    Database::query("DELETE FROM daily_entries WHERE month_id = ?", [$id]);
                    
                    // Avansları sil
                    Database::query("DELETE FROM advances WHERE month_id = ?", [$id]);
                    
                    // POS Manuel girişleri sil
                    Database::query("DELETE FROM pos_commissions WHERE month_id = ?", [$id]);
                    
                    // Ayı sil
                    Database::query("DELETE FROM months WHERE id = ?", [$id]);
                    
                    $_SESSION['flash_success'] = 'Ay ve ilişkili tüm veriler başarıyla silindi.';
                }
            }
        }
        
        header('Location: ?page=months');
        exit;
    }

    public function toggleLock() {
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $month = Database::fetch("SELECT * FROM months WHERE id = ?", [$id]);
            
            if ($month) {
                $newStatus = $month['is_locked'] ? 0 : 1;
                $data = ['is_locked' => $newStatus];
                
                // Kilitleniyorsa güncel pay oranlarını snapshot al
                if ($newStatus == 1) {
                    $partners = Database::fetchAll("SELECT id, profit_share FROM partners WHERE is_active = 1");
                    $shares = [];
                    foreach ($partners as $p) {
                        $shares[$p['id']] = (float)$p['profit_share'];
                    }
                    $data['profit_shares_snapshot'] = json_encode($shares);
                }
                
                Database::update('months', $data, "id = ?", [$id]);
                $_SESSION['flash_success'] = $newStatus ? 'Ay kilitlendi.' : 'Ay kilidi açıldı.';
            }
        }
        
        header('Location: ?page=months');
        exit;
    }
}
