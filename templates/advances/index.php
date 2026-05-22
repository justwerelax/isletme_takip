<?php
$monthNames = ['', 'Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
?>

<!-- Ay Seçici -->
<div class="month-selector">
    <form method="GET" class="month-form">
        <input type="hidden" name="page" value="advances">
        <div class="month-nav">
            <?php
            $prevMonth = $selectedMonth - 1;
            $prevYear = $selectedYear;
            if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
            $nextMonth = $selectedMonth + 1;
            $nextYear = $selectedYear;
            if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
            ?>
            <a href="?page=advances&year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-ghost btn-sm"><i data-lucide="chevron-left"></i></a>
            <select name="month" class="select-input" style="width:auto" onchange="this.form.submit()">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m == $selectedMonth ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
                <?php endfor; ?>
            </select>
            <select name="year" class="select-input" style="width:auto" onchange="this.form.submit()">
                <?php for ($y = 2024; $y <= 2030; $y++): ?>
                    <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <a href="?page=advances&year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-ghost btn-sm"><i data-lucide="chevron-right"></i></a>
            
            <?php if ($month && $month['is_locked']): ?>
                <span class="badge badge-warning"><i data-lucide="lock" style="width:12px;height:12px"></i> Kilitli</span>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (!$month): ?>
    <div class="empty-state">
        <i data-lucide="calendar-x" style="width:64px;height:64px;color:#64748b"></i>
        <h3>Ay Bulunamadı</h3>
        <p>Seçilen ay için kayıt bulunamadı. Ay Yönetimi kısmından oluşturabilirsiniz.</p>
        <a href="?page=months" class="btn btn-primary">Ay Yönetimine Git</a>
    </div>
<?php else: ?>

    <?php if (Auth::isAdmin() && !$month['is_locked']): ?>
    <div style="margin-bottom: 20px; display: flex; justify-content: flex-end;">
        <button type="button" class="btn btn-primary" onclick="openModal('advanceModal')">
            <i data-lucide="plus"></i> Yeni Avans / Ödeme Ekle
        </button>
    </div>

    <!-- Yeni Avans Modalı -->
    <div class="modal-overlay" id="advanceModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i data-lucide="wallet"></i> Yeni Avans / İşletmeye Ödeme</h3>
                <button type="button" class="modal-close" onclick="closeModal('advanceModal')"><i data-lucide="x"></i></button>
            </div>
            <form method="POST" action="?page=advances&action=save">
                <div class="modal-body" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end;">
                    <input type="hidden" name="month_id" value="<?= $month['id'] ?>">
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Ortak Seçimi</label>
                        <select name="partner_id" class="select-input" required>
                            <option value="">-- Seçiniz --</option>
                            <?php foreach ($partners as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Tarih</label>
                        <?php 
                            $defaultDate = date('Y-m-d');
                            if (date('Y-m') !== sprintf('%04d-%02d', $month['year'], $month['month'])) {
                                $defaultDate = sprintf('%04d-%02d-01', $month['year'], $month['month']);
                            }
                        ?>
                        <input type="date" name="advance_date" required 
                               min="<?= sprintf('%04d-%02d-01', $month['year'], $month['month']) ?>" 
                               max="<?= date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $month['year'], $month['month']))) ?>"
                               value="<?= $defaultDate ?>">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1; margin-bottom: 0;">
                        <label>Tutar (₺) <span style="font-size:11px;color:var(--text-muted);font-weight:400;">— Negatif girmek için başına - koyun (örn: -500)</span></label>
                        <input type="text" inputmode="decimal" name="amount" placeholder="Avans için: 500 &nbsp;|&nbsp; İşletmeye ödeme için: -500" required>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1; margin-bottom: 0;">
                        <label>Açıklama</label>
                        <input type="text" name="description" placeholder="Örn: Nakit çekim, faturaya istinaden vb." required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('advanceModal')">İptal</button>
                    <button type="submit" class="btn btn-primary">Ekle</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Avans Listesi Tablosu -->
    <div class="table-card">
        <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3><i data-lucide="list"></i> Avans Listesi</h3>
            <?php 
                $totalsByPartner = [];
                foreach ($partners as $p) $totalsByPartner[$p['id']] = ['name' => $p['name'], 'total' => 0];
                foreach ($advances as $a) {
                    if (isset($totalsByPartner[$a['partner_id']])) {
                        $totalsByPartner[$a['partner_id']]['total'] += (float)$a['amount'];
                    }
                }
            ?>
            <div style="display:flex; gap: 16px; align-items:center;">
                <span id="filterAllBtn" onclick="filterPartner(null)"
                      style="cursor:pointer; font-size:12px; color:var(--text-muted); padding:4px 8px; border-radius:6px; transition:all 0.15s;"
                      class="adv-filter-all adv-filter-active">Tümü</span>
                <?php foreach ($totalsByPartner as $pid => $pt): ?>
                    <?php if($pt['total'] != 0): ?>
                    <span class="badge adv-filter-badge"
                          data-partner-id="<?= $pid ?>"
                          onclick="filterPartner(<?= $pid ?>)"
                          style="cursor:pointer; background: <?= $pt['total'] < 0 ? 'rgba(16,185,129,0.15)' : 'var(--warning-dim)' ?>; color: <?= $pt['total'] < 0 ? 'var(--success)' : 'var(--warning)' ?>; padding: 6px 12px; font-size: 13px; transition:all 0.15s; user-select:none;">
                        <?= htmlspecialchars($pt['name']) ?>: <?= $pt['total'] < 0 ? '+ ' . Calculator::money(abs($pt['total'])) . ' (ödeme)' : Calculator::money($pt['total']) ?>
                    </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Ortak</th>
                        <th>Açıklama</th>
                        <th class="text-right">Tutar</th>
                        <?php if (Auth::isAdmin() && !$month['is_locked']): ?>
                            <th class="text-right">İşlem</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($advances)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Bu ay için avans kaydı bulunamadı.</td></tr>
                    <?php else: ?>
                        <?php foreach ($advances as $a): ?>
                        <tr data-partner-id="<?= $a['partner_id'] ?>">
                            <td><?= date('d.m.Y', strtotime($a['advance_date'])) ?></td>
                            <td style="font-weight: 600; color: var(--accent);"><?= htmlspecialchars($a['partner_name']) ?></td>
                            <td><?= htmlspecialchars($a['description']) ?></td>
                            <td class="text-right" style="font-weight: 600;">
                                <?php if ((float)$a['amount'] < 0): ?>
                                <span style="color:var(--success);">
                                    <i data-lucide="arrow-down-left" style="width:13px;height:13px;vertical-align:middle;"></i>
                                    <?= Calculator::money(abs((float)$a['amount'])) ?>
                                </span>
                                <?php else: ?>
                                <span class="text-warning"><?= Calculator::money((float)$a['amount']) ?></span>
                                <?php endif; ?>
                            </td>
                            <?php if (Auth::isAdmin() && !$month['is_locked']): ?>
                            <td class="text-right">
                                <form id="delete-adv-<?= $a['id'] ?>" method="POST" action="?page=advances&action=delete" style="display:none;">
                                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                </form>
                                <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger)" title="Sil" onclick="confirmDelete('delete-adv-<?= $a['id'] ?>', 'Bu avans kaydını silmek istediğinize emin misiniz?<br><span style=\'color:var(--text-primary)\'><?= htmlspecialchars($a['partner_name']) ?> - <?= Calculator::money((float)$a['amount']) ?></span>')">
                                    <i data-lucide="trash-2"></i> Sil
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
let activeFilter = null;

function filterPartner(partnerId) {
    activeFilter = partnerId;

    // Badge aktif stilini güncelle
    document.querySelectorAll('.adv-filter-badge').forEach(b => {
        const isActive = parseInt(b.dataset.partnerId) === partnerId;
        b.style.opacity   = (partnerId === null || isActive) ? '1' : '0.4';
        b.style.transform = isActive ? 'scale(1.05)' : 'scale(1)';
        b.style.boxShadow = isActive ? '0 0 0 2px var(--accent)' : 'none';
    });

    const allBtn = document.getElementById('filterAllBtn');
    if (allBtn) {
        allBtn.style.background  = partnerId === null ? 'var(--accent)' : 'transparent';
        allBtn.style.color       = partnerId === null ? '#fff' : 'var(--text-muted)';
    }

    // Satırları filtrele
    document.querySelectorAll('tbody tr[data-partner-id]').forEach(row => {
        if (partnerId === null || parseInt(row.dataset.partnerId) === partnerId) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
