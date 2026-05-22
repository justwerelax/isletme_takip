<?php
class StaffController {

    public function index() {
        Auth::requireLogin();

        // Ay seçici
        $selectedYear  = (int)($_GET['year']  ?? date('Y'));
        $selectedMonth = (int)($_GET['month'] ?? date('n'));

        $activeStaff   = Database::fetchAll("SELECT * FROM staff WHERE is_active = 1 ORDER BY name");
        $archivedStaff = Database::fetchAll("SELECT * FROM staff WHERE is_active = 0 ORDER BY name");

        // Seçili aya ait month kaydı
        $monthRow = Database::fetch(
            "SELECT id FROM months WHERE year = ? AND month = ?",
            [$selectedYear, $selectedMonth]
        );

        // Her personel için: o aydaki günlük ödemeler + toplam
        foreach ($activeStaff as &$s) {
            if ($monthRow) {
                $s['daily_payments'] = Database::fetchAll(
                    "SELECT de.entry_date, se.amount
                     FROM staff_expenses se
                     JOIN daily_entries de ON se.daily_entry_id = de.id
                     WHERE se.staff_id = ? AND de.month_id = ?
                     ORDER BY de.entry_date",
                    [$s['id'], $monthRow['id']]
                );
            } else {
                $s['daily_payments'] = [];
            }
            $s['month_total'] = array_sum(array_column($s['daily_payments'], 'amount'));

            // Geçen takvim günü hesabı — start_date dikkate alınır:
            // Sayım başlangıcı = max(ayın ilk günü, start_date)
            // Sayım sonu      = geçmiş ayda ayın son günü (30), bu ayda bugün
            $today    = new \DateTime('today');
            $selFirst = new \DateTime(sprintf('%04d-%02d-01', $selectedYear, $selectedMonth));
            $selLast  = new \DateTime(sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth,
                            (int)date('t', mktime(0,0,0,$selectedMonth,1,$selectedYear))));
            $selYm    = (int)$selFirst->format('Ym');
            $todayYm  = (int)$today->format('Ym');

            // Başlangıç: personelin işe giriş tarihi varsa ve o ay içindeyse onu kullan
            $startDate = $s['start_date'] ? new \DateTime($s['start_date']) : null;
            $countFrom = clone $selFirst; // varsayılan: ayın 1'i
            if ($startDate) {
                $startYm = (int)$startDate->format('Ym');
                if ($startYm === $selYm) {
                    // Bu seçilen ay içinde işe girdi → start_date'ten say
                    $countFrom = clone $startDate;
                } elseif ($startYm > $selYm) {
                    // Seçilen aydan sonra işe girdi → bu ayda hiç çalışmadı
                    $countFrom = null;
                }
                // $startYm < $selYm → daha önceden çalışıyor, ayın başından say (zaten varsayılan)
            }

            if ($countFrom === null) {
                $daysElapsed = 0; // bu ay henüz işe girmedi
            } elseif ($selYm < $todayYm) {
                // Geçmiş ay: countFrom'dan ay sonuna kadar (max 30)
                $diff = $countFrom->diff($selLast);
                $daysElapsed = min($diff->days + 1, 30);
            } elseif ($selYm === $todayYm) {
                // Bu ay: countFrom'dan bugüne kadar (max 30)
                $diff = $countFrom->diff($today);
                $daysElapsed = min($diff->days + 1, 30);
            } else {
                $daysElapsed = 0; // gelecek ay
            }
            $s['days_elapsed'] = $daysElapsed;

            // Hakediş = (geçen takvim günü / 30) × maaş
            // Ödenen avanslar = staff_expenses toplamı (maaştan yapılan avans ödemeleri)
            // Kalan bakiye = hakediş - ödenen avanslar
            if ($s['salary'] > 0) {
                $dailyRate       = (float)$s['salary'] / 30;
                $s['daily_rate'] = $dailyRate;
                $s['hakedis']    = $daysElapsed * $dailyRate;
                $s['balance']    = $s['hakedis'] - (float)$s['month_total'];
            } else {
                $s['daily_rate'] = null;
                $s['hakedis']    = null;
                $s['balance']    = null;
            }

            // Tüm zamanlar toplam (genel bakış için)
            $allTime = Database::fetch(
                "SELECT COALESCE(SUM(amount),0) AS total FROM staff_expenses WHERE staff_id = ?",
                [$s['id']]
            );
            $s['all_time_total'] = (float)($allTime['total'] ?? 0);
        }
        unset($s);

        $availableMonths = Database::fetchAll("SELECT * FROM months ORDER BY year DESC, month DESC");

        $pageTitle   = 'Personel';
        $currentPage = 'staff';
        require BASE_PATH . '/templates/layout.php';
    }

    public function save() {
        Auth::requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=staff'); exit; }

        $id        = (int)($_POST['id'] ?? 0);
        $name      = trim($_POST['name'] ?? '');
        $position  = trim($_POST['position'] ?? '');
        $salary    = (float)str_replace(',', '.', $_POST['salary'] ?? '0');
        $startDate = $_POST['start_date'] ?: null;
        $notes     = trim($_POST['notes'] ?? '');

        if (!$name) {
            $_SESSION['flash_error'] = 'İsim zorunludur.';
            header('Location: ?page=staff'); exit;
        }

        $data = [
            'name'       => $name,
            'position'   => $position,
            'salary'     => $salary,
            'start_date' => $startDate,
            'notes'      => $notes,
            'is_active'  => 1,
        ];

        if ($id > 0) {
            Database::update('staff', $data, 'id = ?', [$id]);
            $_SESSION['flash_success'] = 'Personel güncellendi.';
        } else {
            Database::insert('staff', $data);
            $_SESSION['flash_success'] = 'Personel eklendi.';
        }

        header('Location: ?page=staff'); exit;
    }

    public function archive() {
        Auth::requireAdmin();
        $id      = (int)($_POST['id'] ?? 0);
        $endDate = $_POST['end_date'] ?: date('Y-m-d');

        if ($id) {
            Database::update('staff', [
                'is_active' => 0,
                'end_date'  => $endDate,
            ], 'id = ?', [$id]);
            $_SESSION['flash_success'] = 'Personel arşive alındı.';
        }
        header('Location: ?page=staff'); exit;
    }

    public function reactivate() {
        Auth::requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            Database::update('staff', ['is_active' => 1, 'end_date' => null], 'id = ?', [$id]);
            $_SESSION['flash_success'] = 'Personel tekrar aktif edildi.';
        }
        header('Location: ?page=staff'); exit;
    }
}
