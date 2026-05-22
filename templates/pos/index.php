<?php
$monthNames = ['', 'Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

$positives = [];
$negatives = [];
foreach ($manualEntries as $me) {
    if ((float)$me['total_amount'] >= 0) $positives[] = $me;
    else                                  $negatives[]  = $me;
}

$totManualPos = array_sum(array_column($positives, 'total_amount'));
$totNegAmount = array_sum(array_column($negatives,  'total_amount'));
$posNet       = ($totalPosGross - $totalPosComm) + $totManualPos + $totNegAmount;
?>

<div class="card" style="padding:24px; background:var(--bg-surface);">
    <!-- Başlık + Ay Seçici -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2 class="section-title" style="margin:0;"><i data-lucide="credit-card"></i> POS Ekstresi</h2>
        <form method="GET" style="display:flex; gap:8px; align-items:center;">
            <input type="hidden" name="page" value="pos">
            <?php
            $prevPosMonth = $selectedMonth - 1; $prevPosYear = $selectedYear;
            if ($prevPosMonth < 1) { $prevPosMonth = 12; $prevPosYear--; }
            $nextPosMonth = $selectedMonth + 1; $nextPosYear = $selectedYear;
            if ($nextPosMonth > 12) { $nextPosMonth = 1; $nextPosYear++; }
            ?>
            <a href="?page=pos&year=<?= $prevPosYear ?>&month=<?= $prevPosMonth ?>" class="btn btn-ghost btn-sm"><i data-lucide="chevron-left"></i></a>
            <select name="month" class="select-input" onchange="this.form.submit()">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m == $selectedMonth ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
                <?php endfor; ?>
            </select>
            <select name="year" class="select-input" onchange="this.form.submit()">
                <?php for ($y = 2024; $y <= 2030; $y++): ?>
                    <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <a href="?page=pos&year=<?= $nextPosYear ?>&month=<?= $nextPosMonth ?>" class="btn btn-ghost btn-sm"><i data-lucide="chevron-right"></i></a>
        </form>
    </div>

    <?php if (!$currentMonthData): ?>
        <div class="empty-state" style="padding:30px;">
            <i data-lucide="calendar-x" style="width:48px;height:48px;color:#64748b"></i>
            <p style="margin-top:10px;">Seçilen aya ait veri bulunamadı.</p>
        </div>
    <?php else: ?>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:24px;">

        <!-- Sol: Özet + Formlar -->
        <div style="display:flex; flex-direction:column; gap:16px;">

            <!-- Ay Özeti -->
            <div class="card" style="padding:18px; background:linear-gradient(135deg,var(--bg-surface),var(--bg-hover)); border:1px solid var(--border); border-radius:var(--radius-lg);">
                <h3 style="font-size:13px; margin-bottom:12px; color:var(--text-main); display:flex; align-items:center; gap:8px;">
                    <i data-lucide="bar-chart-horizontal" style="width:16px;height:16px;color:var(--accent);"></i> Ay Özeti
                </h3>
                <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">
                    <span style="color:var(--text-muted);">Brüt POS</span>
                    <span><?= Calculator::money($totalPosGross) ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">
                    <span style="color:var(--text-muted);">Hesaplanan Komisyon</span>
                    <span style="color:var(--danger);"><?= Calculator::money($totalPosComm) ?></span>
                </div>
                <?php if ($totManualPos > 0): ?>
                <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">
                    <span style="color:var(--text-muted);">Elle Girilen (+)</span>
                    <span style="color:var(--success);"><?= Calculator::money($totManualPos) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($totNegAmount < 0): ?>
                <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">
                    <span style="color:var(--text-muted);">Ödemeler / Kesintiler</span>
                    <span style="color:var(--danger);"><?= Calculator::money($totNegAmount) ?></span>
                </div>
                <?php endif; ?>
                <div style="display:flex; justify-content:space-between; font-size:15px; font-weight:700; margin-top:10px; padding-top:10px; border-top:1px solid var(--border);">
                    <span>Kalan Bakiye</span>
                    <span style="color:var(--accent);"><?= Calculator::money($posNet) ?></span>
                </div>
            </div>

            <!-- Komisyon Oranı Ayarı -->
            <?php if (Auth::isAdmin()): ?>
            <div class="card" style="padding:16px; background:var(--bg-surface); border:1px solid rgba(14,165,233,0.2); border-radius:var(--radius-md);">
                <h3 style="font-size:13px; margin-bottom:10px; display:flex; align-items:center; gap:8px;">
                    <i data-lucide="percent" style="width:15px;height:15px;color:#38bdf8;"></i>
                    Komisyon Oranı (%)
                </h3>
                <form method="POST" action="?page=pos&action=updateCommission" style="display:flex; gap:8px;">
                    <input type="hidden" name="month_id" value="<?= $currentMonthData['id'] ?>">
                    <input type="text" name="commission_rate" class="select-input"
                           value="<?= number_format($commissionRate * 100, 2, ',', '') ?>"
                           style="flex:1; padding:8px;" required>
                    <button type="submit" class="btn btn-ghost btn-sm">Güncelle</button>
                </form>
                <small style="color:var(--text-muted); display:block; margin-top:6px; font-size:11px;">* Bu oran değişimden <b>sonraki</b> girişlerde uygulanır (bilgi amaçlı).</small>
            </div>

            <!-- Elle Giriş -->
            <?php if (!$currentMonthData['is_locked']): ?>
            <div class="card" style="padding:16px; background:var(--bg-surface); border:1px solid var(--border); border-radius:var(--radius-md);">
                <h3 style="font-size:13px; margin-bottom:12px;">Elle Kalem Ekle</h3>
                <form method="POST" action="?page=pos&action=saveManualEntry" style="display:flex; flex-direction:column; gap:10px;">
                    <input type="hidden" name="month_id" value="<?= $currentMonthData['id'] ?>">
                    <div>
                        <label style="display:block; font-size:11px; color:var(--text-muted); margin-bottom:4px;">Açıklama</label>
                        <input type="text" name="description" class="select-input" style="width:100%;" placeholder="Ör: Komisyon Mayıs, Kalan bakiye…" required>
                    </div>
                    <div>
                        <label style="display:block; font-size:11px; color:var(--text-muted); margin-bottom:4px;">Tutar (₺) — eksi için başına - koy</label>
                        <input type="text" inputmode="decimal" name="total_amount" class="select-input" style="width:100%;" placeholder="Ör: 1250 veya -800" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> Ekle</button>
                </form>
            </div>
            <?php endif; ?>
            <?php endif; ?>

        </div>

        <!-- Sağ: POS Tablosu -->
        <div>
            <div class="table-wrapper" style="border:1px solid rgba(14,165,233,0.2); border-radius:var(--radius-md); background:var(--bg-surface);">
                <table class="data-table" style="font-size:13px;">
                    <thead>
                        <tr>
                            <th>Tarih / Açıklama</th>
                            <th class="text-right">Brüt</th>
                            <th class="text-right" style="color:#38bdf8;">Komisyon</th>
                            <th class="text-right">Net</th>
                            <?php if (Auth::isAdmin() && !$currentMonthData['is_locked']): ?>
                            <th class="text-right">İşlem</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>

                        <!-- Pozitif Manuel Kalemler -->
                        <?php foreach ($positives as $me): ?>
                        <tr style="background:rgba(16,185,129,0.04);">
                            <td style="font-weight:600; color:#d97706;"><?= htmlspecialchars($me['bank_name']) ?></td>
                            <td class="text-right"><?= Calculator::money($me['total_amount']) ?></td>
                            <td class="text-right text-muted">—</td>
                            <td class="text-right text-success" style="font-weight:600;"><?= Calculator::money($me['total_amount']) ?></td>
                            <?php if (Auth::isAdmin() && !$currentMonthData['is_locked']): ?>
                            <td class="text-right">
                                <form id="del-pos-<?= $me['id'] ?>" method="POST" action="?page=pos&action=deleteManualEntry" style="display:none;">
                                    <input type="hidden" name="id" value="<?= $me['id'] ?>">
                                </form>
                                <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger)"
                                    onclick="confirmDelete('del-pos-<?= $me['id'] ?>', 'Bu kaydı silmek istiyor musunuz?')">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>

                        <!-- Günlük Girişlerden Gelen POS -->
                        <?php foreach ($activeDailyEntries as $entry):
                            $gross = (float)$entry['pos_amount'];
                            $comm  = $entry['pos_comm'];
                            $net   = $gross - $comm;
                        ?>
                        <tr>
                            <td style="font-weight:600; color:var(--accent);">
                                <?= date('d.m.Y', strtotime($entry['entry_date'])) ?>
                                <small style="color:var(--text-muted); font-weight:400; font-size:10px; margin-left:4px;">
                                    %<?= number_format($entry['calculated_rate'] * 100, 2, ',', '.') ?>
                                </small>
                            </td>
                            <td class="text-right"><?= Calculator::money($gross) ?></td>
                            <td class="text-right" style="color:#38bdf8;"><?= $comm > 0 ? Calculator::money($comm) : '<span style="color:var(--text-muted)">—</span>' ?></td>
                            <td class="text-right text-success" style="font-weight:600;"><?= Calculator::money($net) ?></td>
                            <?php if (Auth::isAdmin() && !$currentMonthData['is_locked']): ?>
                            <td></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>

                        <!-- Aktif Toplam (günlük girişler) -->
                        <?php if ($totalPosGross > 0 || $totManualPos > 0): ?>
                        <tr style="background:var(--bg-hover); font-weight:700; border-top:2px solid var(--border);">
                            <td>AKTİF TOPLAM</td>
                            <td class="text-right"><?= Calculator::money($totalPosGross + $totManualPos) ?></td>
                            <td class="text-right" style="color:#38bdf8;"><?= Calculator::money($totalPosComm) ?></td>
                            <td class="text-right" style="color:#ca8a04;"><?= Calculator::money($totalPosGross - $totalPosComm + $totManualPos) ?></td>
                            <?php if (Auth::isAdmin() && !$currentMonthData['is_locked']): ?><td></td><?php endif; ?>
                        </tr>
                        <?php endif; ?>

                        <!-- Negatif Manuel Kalemler -->
                        <?php if (!empty($negatives)): ?>
                            <tr style="height:8px; background:transparent;"><td colspan="10" style="border:none;"></td></tr>
                            <?php foreach ($negatives as $me): ?>
                            <tr style="background:rgba(239,68,68,0.04);">
                                <td style="font-weight:600; color:#ea580c;"><?= htmlspecialchars($me['bank_name']) ?></td>
                                <td class="text-right" style="color:var(--danger);"><?= Calculator::money($me['total_amount']) ?></td>
                                <td class="text-right text-muted">—</td>
                                <td class="text-right" style="color:var(--danger); font-weight:600;"><?= Calculator::money($me['total_amount']) ?></td>
                                <?php if (Auth::isAdmin() && !$currentMonthData['is_locked']): ?>
                                <td class="text-right">
                                    <form id="del-neg-<?= $me['id'] ?>" method="POST" action="?page=pos&action=deleteManualEntry" style="display:none;">
                                        <input type="hidden" name="id" value="<?= $me['id'] ?>">
                                    </form>
                                    <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger)"
                                        onclick="confirmDelete('del-neg-<?= $me['id'] ?>', 'Bu kaydı silmek istiyor musunuz?')">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Kalan Bakiye -->
                        <tr style="background:rgba(14,165,233,0.07); font-weight:700; border-top:2px solid var(--border);">
                            <td colspan="2" class="text-right" style="color:#38bdf8; font-size:13px;">KALAN POS BAKİYESİ</td>
                            <td></td>
                            <td class="text-right" style="font-size:16px; color:#38bdf8;"><?= Calculator::money($posNet) ?></td>
                            <?php if (Auth::isAdmin() && !$currentMonthData['is_locked']): ?><td></td><?php endif; ?>
                        </tr>

                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /grid -->
    <?php endif; ?>
</div>
