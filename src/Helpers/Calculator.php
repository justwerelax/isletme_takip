<?php
class Calculator {
    /**
     * Ay için tüm özet hesaplamaları yapar
     */
    public static function monthlySummary(int $monthId, bool $recursive = true): array {
        $month = Database::fetch("SELECT * FROM months WHERE id = ?", [$monthId]);
        if (!$month) return [];

        // Günlük girişler
        $entries = Database::fetchAll("SELECT * FROM daily_entries WHERE month_id = ? ORDER BY entry_date", [$monthId]);
        $partners = Database::fetchAll("SELECT * FROM partners WHERE is_active = 1 ORDER BY sort_order");
        // Kategoriler — sadece ana kategoriler (parent_id IS NULL)
        $categories = Database::fetchAll("SELECT * FROM expense_categories WHERE is_active = 1 AND parent_id IS NULL ORDER BY sort_order");
        // Alt kategoriler de dahil tüm aktif kategoriler (gider eşleştirme için)
        $allCategories = Database::fetchAll("SELECT * FROM expense_categories WHERE is_active = 1 ORDER BY sort_order");

        // Önceki ayı bul (Dinamiği sağlayan kısım)
        $prevMonth = Database::fetch(
            "SELECT id FROM months WHERE (year < ?) OR (year = ? AND month < ?) ORDER BY year DESC, month DESC LIMIT 1",
            [$month['year'], $month['year'], $month['month']]
        );

        // Devir değerleri: sadece o ayın veritabanına kaydedilmiş değerleri kullan.
        // Zincirleme dinamik hesap kaldırıldı — ay kapanışında elle doğru değer girilmeli.
        $cashCarryover    = (float)$month['cash_carryover'];
        $reserveCarryover = (float)$month['reserve_carryover'];

        // Toplam gelir (cüro) = gelir + dış gelir
        $totalRevenue = 0;
        $totalExternalRevenue = 0;
        $totalPos = 0;
        $workingDays = 0;

        foreach ($entries as $entry) {
            $totalRevenue += (float)$entry['revenue'];
            $totalExternalRevenue += (float)$entry['external_revenue'];
            $totalPos += (float)$entry['pos_amount'];
            if ((float)$entry['revenue'] > 0) $workingDays++;
        }

        $activeCuro = $totalRevenue + $totalExternalRevenue;

        // Toplam gider — ana kategoriler + alt kategoriler hiyerarşik
        $totalExpenses = 0;
        $expenseByCategory = [];
        foreach ($categories as $cat) {
            // Alt kategorileri bul
            $subCats = array_filter($allCategories, fn($c) => (int)$c['parent_id'] === (int)$cat['id']);
            $expenseByCategory[$cat['id']] = [
                'id'   => $cat['id'],
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'total' => 0,
                'sub'  => array_map(fn($s) => ['id'=>$s['id'],'name'=>$s['name'],'slug'=>$s['slug'],'total'=>0], array_values($subCats)),
            ];
        }
        // Alt kategorisi olmayan ama parent_id dolu olanlar için de ana kategori map'i
        $subToParent = [];
        foreach ($allCategories as $c) {
            if ($c['parent_id']) $subToParent[(int)$c['id']] = (int)$c['parent_id'];
        }

        foreach ($entries as $entry) {
            $expenses = Database::fetchAll(
                "SELECT de.*, ec.name, ec.slug, ec.parent_id FROM daily_expenses de 
                 JOIN expense_categories ec ON de.category_id = ec.id 
                 WHERE de.daily_entry_id = ?", [$entry['id']]
            );
            foreach ($expenses as $exp) {
                $totalExpenses += (float)$exp['amount'];
                $catId   = (int)$exp['category_id'];
                $parentId = $exp['parent_id'] ? (int)$exp['parent_id'] : null;

                if ($parentId && isset($expenseByCategory[$parentId])) {
                    // Alt kategori — ana kategoriye topla
                    $expenseByCategory[$parentId]['total'] += (float)$exp['amount'];
                    foreach ($expenseByCategory[$parentId]['sub'] as &$sub) {
                        if ((int)$sub['id'] === $catId) {
                            $sub['total'] += (float)$exp['amount'];
                            break;
                        }
                    }
                    unset($sub);
                } elseif (isset($expenseByCategory[$catId])) {
                    // Ana kategori direkt
                    $expenseByCategory[$catId]['total'] += (float)$exp['amount'];
                }
            }
        }

        // Personel kategorisi için staff_expenses'tan personel bazlı breakdown ekle
        $personnelCat = null;
        foreach ($expenseByCategory as $catId => &$catData) {
            if ($catData['slug'] === 'personel') {
                $personnelCat = &$catData;
                break;
            }
        }
        unset($catData);

        if ($personnelCat !== null) {
            // Tüm entry'lerin staff_expenses'larını çek
            $entryIds = array_column($entries, 'id');
            if (!empty($entryIds)) {
                $placeholders = implode(',', array_fill(0, count($entryIds), '?'));
                $staffExps = Database::fetchAll(
                    "SELECT se.staff_id, se.amount, s.name as staff_name
                     FROM staff_expenses se
                     JOIN staff s ON se.staff_id = s.id
                     WHERE se.daily_entry_id IN ($placeholders)",
                    $entryIds
                );
                // Personel bazlı topla
                $staffTotals = [];
                foreach ($staffExps as $se) {
                    $sid = (int)$se['staff_id'];
                    if (!isset($staffTotals[$sid])) {
                        $staffTotals[$sid] = ['id' => $sid, 'name' => $se['staff_name'], 'slug' => 'staff_'.$sid, 'total' => 0];
                    }
                    $staffTotals[$sid]['total'] += (float)$se['amount'];
                }
                // sub dizisine ekle (negatif olarak — gider formatı)
                foreach ($staffTotals as $st) {
                    if ($st['total'] != 0) {
                        $personnelCat['sub'][] = [
                            'id'    => $st['id'],
                            'name'  => $st['name'],
                            'slug'  => $st['slug'],
                            'total' => -abs($st['total']),
                        ];
                    }
                }
            }
        }
        
        // Taksitli ödemeler artık kasadan (sıcak paradan) otomatik düşülmüyor.
        // Bu ödemeler kendi modülünde (Borçlar/Krediler) takip edilmektedir.
        // Not: Bu sistemde giderler genellikle negatif mi saklanıyor? 
        // Bakalım: $activeProfit = $activeCuro + $totalExpenses; (line 79)
        // Demek ki $totalExpenses negatif olmalı.

        // Aktif kar
        $activeProfit = $activeCuro + $totalExpenses; // giderler negatif

        // Kasa dahil toplam
        $totalWithCash = $cashCarryover + $activeProfit;

        // Ortak hesaplamaları
        $partnerData = [];
        foreach ($partners as $partner) {
            // Eğer ay kilitliyse, kilit anındaki pay oranlarını kullan
            $share = (float)$partner['profit_share'];
            if ($month['is_locked'] && $month['profit_shares_snapshot']) {
                $snapshot = json_decode($month['profit_shares_snapshot'], true);
                if (isset($snapshot[$partner['id']])) {
                    $share = (float)$snapshot[$partner['id']];
                }
            }

            $monthlySalary = $activeProfit * $share;

            // Avanslar
            $advanceTotal = Database::fetch(
                "SELECT COALESCE(SUM(amount), 0) as total FROM advances WHERE month_id = ? AND partner_id = ?",
                [$monthId, $partner['id']]
            )['total'];

            $advances = Database::fetchAll(
                "SELECT * FROM advances WHERE month_id = ? AND partner_id = ? ORDER BY advance_date",
                [$monthId, $partner['id']]
            );

            $remainingBalance = $monthlySalary - (float)$advanceTotal;
            $dailyAvgSalary = $workingDays > 0 ? $monthlySalary / $workingDays : 0;

            $partnerData[] = [
                'id' => $partner['id'],
                'name' => $partner['name'],
                'is_cash_reserve' => $partner['is_cash_reserve'],
                'profit_share' => $share,
                'monthly_salary' => $monthlySalary,
                'advance_total' => (float)$advanceTotal,
                'remaining_balance' => $remainingBalance,
                'daily_avg_salary' => $dailyAvgSalary,
                'advances' => $advances,
            ];
        }

        // Avanslar çıkınca mevcut
        $totalAdvances = array_sum(array_column($partnerData, 'advance_total'));
        $availableAfterAdvances = $totalWithCash - $totalAdvances;

        // Günlük ortalama kar
        $dailyAvgProfit = $workingDays > 0 ? $activeProfit / $workingDays : 0;

        // Cüro/gider oranı
        $expenseRatio = $activeCuro > 0 ? (abs($totalExpenses) / $activeCuro) * 100 : 0;

        // Ay içinde gider yazılmış krediler (rapor için)
        $paidLoansThisMonth = Database::fetchAll("
            SELECT ip.amount, ip.paid_at, i.title, de.entry_date
            FROM installment_payments ip
            JOIN installments i  ON ip.installment_id  = i.id
            JOIN daily_entries de ON ip.expense_entry_id = de.id
            WHERE ip.is_paid = 1
              AND i.category = 'loan'
              AND de.month_id = ?
            ORDER BY de.entry_date, i.title
        ", [$monthId]);

        return [
            'month' => $month,
            'entries' => $entries,
            'paid_loans' => $paidLoansThisMonth,
            'active_curo' => $activeCuro,
            'total_revenue' => $totalRevenue,
            'total_external_revenue' => $totalExternalRevenue,
            'total_pos' => $totalPos,
            'total_expenses' => $totalExpenses,
            'active_profit' => $activeProfit,
            'cash_carryover' => $cashCarryover,
            'reserve_carryover' => $reserveCarryover,
            'total_with_cash' => $totalWithCash,
            'available_after_advances' => $availableAfterAdvances,
            'working_days' => $workingDays,
            'daily_avg_profit' => $dailyAvgProfit,
            'expense_ratio' => $expenseRatio,
            'partners' => $partnerData,
            'expense_by_category' => array_values($expenseByCategory),
            'categories' => $categories,
            'all_categories' => $allCategories,
        ];
    }

    /** Sayıyı Türk formatında gösterir */
    public static function money(float $amount, bool $withSymbol = true): string {
        $formatted = number_format($amount, 2, ',', '.');
        return $withSymbol ? '₺' . $formatted : $formatted;
    }

    /** Yüzde formatla */
    public static function percent(float $value): string {
        return '%' . number_format($value, 2, ',', '.');
    }
}
