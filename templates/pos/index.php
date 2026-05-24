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

$showCheckboxes = $isCurrentMonth && Auth::isAdmin() && $currentMonthData && !$currentMonthData['is_locked'];
?>

<style>
.pos-check-col { width:36px; text-align:center; }
.pos-check-col input[type=checkbox] { width:16px; height:16px; cursor:pointer; accent-color:var(--accent); }
.pos-check-row.selected-row { background:rgba(14,165,233,0.08) !important; }

#commPanel {
    position:fixed; bottom:0; left:var(--sidebar-width, 260px); right:0;
    background:var(--bg-surface);
    border-top:2px solid var(--accent);
    padding:14px 24px;
    display:none;
    z-index:1000;
    box-shadow:0 -4px 20px rgba(0,0,0,0.3);
    align-items:center;
    gap:20px;
    flex-wrap:wrap;
}
#commPanel.active { display:flex; }
.comm-panel-stat { display:flex; flex-direction:column; gap:2px; }
.comm-panel-stat label { font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; }
.comm-panel-stat span { font-size:16px; font-weight:700; color:var(--text-main); }
.comm-panel-stat span.accent { color:var(--accent); }
.comm-panel-stat span.danger { color:var(--danger); }
.comm-panel-actions { margin-left:auto; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.comm-panel-actions select { padding:8px 10px; font-size:13px; background:var(--bg-hover); color:var(--text-main); border:1px solid var(--border); border-radius:var(--radius-sm); cursor:pointer; }
.arch-badge {
    display:inline-flex; align-items:center; gap:4px;
    background:rgba(99,102,241,0.15); color:#818cf8;
    font-size:10px; font-weight:600; padding:2px 7px; border-radius:20px;
}
</style>

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
            <?php if ($showCheckboxes): ?>
            <!-- Tümünü Seç -->
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px; padding:8px 12px; background:rgba(14,165,233,0.06); border-radius:var(--radius-sm); border:1px solid rgba(14,165,233,0.15);">
                <input type="checkbox" id="checkAll" style="width:16px;height:16px;cursor:pointer;accent-color:var(--accent);"
                       onchange="toggleAll(this.checked)">
                <label for="checkAll" style="font-size:12px; color:var(--text-muted); cursor:pointer; user-select:none;">
                    Tümünü Seç / Bırak
                </label>
                <span id="selCount" style="font-size:12px; color:var(--accent); font-weight:600; margin-left:4px;"></span>
            </div>
            <?php endif; ?>

            <div class="table-wrapper" style="border:1px solid rgba(14,165,233,0.2); border-radius:var(--radius-md); background:var(--bg-surface);">
                <table class="data-table" style="font-size:13px;">
                    <thead>
                        <tr>
                            <?php if ($showCheckboxes): ?><th class="pos-check-col"></th><?php endif; ?>
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
                            <?php if ($showCheckboxes): ?><td class="pos-check-col"></td><?php endif; ?>
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

                        <!-- Günlük Girişlerden Gelen POS (Aktif / Komisyon Eklenmemiş) -->
                        <?php foreach ($activeDailyEntries as $entry):
                            $gross = (float)$entry['pos_amount'];
                            $comm  = $entry['pos_comm'];
                            $net   = $gross - $comm;
                        ?>
                        <tr class="pos-check-row" data-entry-id="<?= $entry['id'] ?>">
                            <?php if ($showCheckboxes): ?>
                            <td class="pos-check-col">
                                <input type="checkbox" class="pos-entry-check"
                                       data-id="<?= $entry['id'] ?>"
                                       data-gross="<?= $gross ?>"
                                       data-comm="<?= $comm ?>"
                                       onchange="updatePanel()">
                            </td>
                            <?php endif; ?>
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

                        <!-- Aktif Toplam -->
                        <?php if ($totalPosGross > 0 || $totManualPos > 0): ?>
                        <tr style="background:var(--bg-hover); font-weight:700; border-top:2px solid var(--border);">
                            <?php if ($showCheckboxes): ?><td></td><?php endif; ?>
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
                                <?php if ($showCheckboxes): ?><td class="pos-check-col"></td><?php endif; ?>
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
                            <?php if ($showCheckboxes): ?><td></td><?php endif; ?>
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

    <!-- ── Arşiv: Komisyonu Tahsil Edilmiş Günler ── -->
    <?php if (!empty($collectedEntries)): ?>
    <div style="margin-top:32px;">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
            <h3 style="margin:0; font-size:14px; color:var(--text-muted); display:flex; align-items:center; gap:8px;">
                <i data-lucide="archive" style="width:16px;height:16px;color:#818cf8;"></i>
                Komisyon Arşivi
            </h3>
            <span class="arch-badge"><i data-lucide="check-circle" style="width:11px;height:11px;"></i> Tahsil Edildi</span>
            <small style="color:var(--text-muted); font-size:11px;"><?= count($collectedEntries) ?> gün</small>
        </div>
        <div class="table-wrapper" style="border:1px solid rgba(99,102,241,0.2); border-radius:var(--radius-md); background:var(--bg-surface); opacity:.85;">
            <table class="data-table" style="font-size:12px;">
                <thead>
                    <tr style="background:rgba(99,102,241,0.06);">
                        <th style="color:#818cf8;">Tarih</th>
                        <th class="text-right" style="color:#818cf8;">Brüt POS</th>
                        <th class="text-right" style="color:#818cf8;">Komisyon</th>
                        <th class="text-right" style="color:#818cf8;">Net</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $archGross = 0; $archComm = 0;
                foreach ($collectedEntries as $ce):
                    $cg = (float)$ce['pos_amount'];
                    $cc = (float)$ce['pos_comm'];
                    $cn = $cg - $cc;
                    $archGross += $cg; $archComm += $cc;
                ?>
                <tr style="color:var(--text-muted);">
                    <td>
                        <?= date('d.m.Y', strtotime($ce['entry_date'])) ?>
                        <small style="font-size:10px; margin-left:4px;">%<?= number_format($ce['calculated_rate'] * 100, 2, ',', '.') ?></small>
                    </td>
                    <td class="text-right"><?= Calculator::money($cg) ?></td>
                    <td class="text-right" style="color:#818cf8;"><?= Calculator::money($cc) ?></td>
                    <td class="text-right"><?= Calculator::money($cn) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight:700; background:rgba(99,102,241,0.08); border-top:2px solid rgba(99,102,241,0.2);">
                    <td style="color:#818cf8;">ARŞİV TOPLAMI</td>
                    <td class="text-right" style="color:#818cf8;"><?= Calculator::money($archGross) ?></td>
                    <td class="text-right" style="color:#818cf8;"><?= Calculator::money($archComm) ?></td>
                    <td class="text-right" style="color:#818cf8;"><?= Calculator::money($archGross - $archComm) ?></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; // currentMonthData ?>
</div>

<!-- ── Sticky Komisyon Uygulama Paneli ── -->
<?php if ($showCheckboxes): ?>
<div id="commPanel">
    <div class="comm-panel-stat">
        <label>Seçili</label>
        <span id="panelCount">0 gün</span>
    </div>
    <div class="comm-panel-stat">
        <label>Brüt POS</label>
        <span class="accent" id="panelGross">₺0</span>
    </div>
    <div class="comm-panel-stat">
        <label>Toplam Komisyon</label>
        <span class="danger" id="panelComm">₺0</span>
    </div>

    <div class="comm-panel-actions">
        <select id="panelTarget" style="">
            <option value="">— Hedef Tarih Seç —</option>
            <?php foreach ($availableTargetDates as $td): ?>
            <option value="<?= htmlspecialchars($td['entry_date']) ?>">
                <?= date('d.m.Y', strtotime($td['entry_date'])) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <form id="applyForm" method="POST" action="?page=pos&action=applyCommissions">
            <input type="hidden" name="month_id" value="<?= $currentMonthData['id'] ?>">
            <input type="hidden" name="target_date" id="applyTargetDate" value="">
            <div id="applyEntryIds"></div>
            <button type="button" class="btn btn-primary" onclick="confirmApply()">
                <i data-lucide="check-circle"></i> Komisyon Ekle
            </button>
        </form>

        <button type="button" class="btn btn-ghost btn-sm" onclick="clearSelection()" style="color:var(--text-muted);">
            <i data-lucide="x"></i> İptal
        </button>
    </div>
</div>

<script>
function fmtMoney(n) {
    return '₺' + n.toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function updatePanel() {
    const checks = document.querySelectorAll('.pos-entry-check:checked');
    let totalGross = 0, totalComm = 0;
    checks.forEach(c => {
        totalGross += parseFloat(c.dataset.gross) || 0;
        totalComm  += parseFloat(c.dataset.comm)  || 0;
    });

    const panel = document.getElementById('commPanel');
    if (checks.length > 0) {
        panel.classList.add('active');
    } else {
        panel.classList.remove('active');
    }

    document.getElementById('panelCount').textContent = checks.length + ' gün';
    document.getElementById('panelGross').textContent = fmtMoney(totalGross);
    document.getElementById('panelComm').textContent  = fmtMoney(totalComm);

    // selCount above table
    const sc = document.getElementById('selCount');
    if (sc) sc.textContent = checks.length > 0 ? checks.length + ' seçili' : '';

    // checkAll indeterminate state
    const allChecks = document.querySelectorAll('.pos-entry-check');
    const ca = document.getElementById('checkAll');
    if (ca) {
        ca.indeterminate = checks.length > 0 && checks.length < allChecks.length;
        ca.checked = allChecks.length > 0 && checks.length === allChecks.length;
    }

    // highlight rows
    document.querySelectorAll('.pos-check-row').forEach(row => {
        const cb = row.querySelector('.pos-entry-check');
        if (cb) row.classList.toggle('selected-row', cb.checked);
    });
}

function toggleAll(checked) {
    document.querySelectorAll('.pos-entry-check').forEach(c => { c.checked = checked; });
    updatePanel();
}

function clearSelection() {
    document.querySelectorAll('.pos-entry-check').forEach(c => { c.checked = false; });
    const ca = document.getElementById('checkAll');
    if (ca) { ca.checked = false; ca.indeterminate = false; }
    updatePanel();
}

function confirmApply() {
    const checks = document.querySelectorAll('.pos-entry-check:checked');
    if (checks.length === 0) { alert('Lütfen en az bir gün seçin.'); return; }

    const targetDate = document.getElementById('panelTarget').value;
    if (!targetDate) { alert('Lütfen hedef tarih seçin.'); return; }

    const comm = document.getElementById('panelComm').textContent;
    const cnt  = checks.length;
    const dateFormatted = new Date(targetDate).toLocaleDateString('tr-TR', {day:'2-digit', month:'2-digit', year:'numeric'});

    if (!confirm(cnt + ' günün komisyonu (' + comm + '), ' + dateFormatted + ' tarihine gider olarak eklenecek.\n\nOnaylıyor musunuz?')) return;

    // fill hidden fields
    document.getElementById('applyTargetDate').value = targetDate;
    const container = document.getElementById('applyEntryIds');
    container.innerHTML = '';
    checks.forEach(c => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'entry_ids[]';
        inp.value = c.dataset.id;
        container.appendChild(inp);
    });

    document.getElementById('applyForm').submit();
}

// Sync target date select → hidden input live
document.getElementById('panelTarget').addEventListener('change', function() {
    document.getElementById('applyTargetDate').value = this.value;
});
</script>
<?php endif; ?>
