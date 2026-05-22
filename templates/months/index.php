<?php
// Ay Yönetimi template
$monthNames = ['', 'Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
?>

<?php if (Auth::isAdmin()): ?>
<div class="card" style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 28px;">
    <h2 class="section-title" id="month-form-title"><i data-lucide="plus-circle"></i> Yeni Ay Oluştur</h2>
    <form method="POST" action="?page=months&action=save" id="month-form" class="month-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; align-items: end;">
        <input type="hidden" name="id" id="month_id" value="0">
        <div class="form-group" style="margin-bottom: 0;">
            <label>Yıl</label>
            <input type="number" name="year" id="month_year" value="<?= $nextYear ?>" required min="2020" max="2050">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Ay</label>
            <select name="month" id="month_month" class="select-input" required>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m == $nextMonth ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Kasa Devir (₺)</label>
            <input type="number" name="cash_carryover" id="month_cash" value="<?= number_format($defaultCashCarryover, 2, '.', '') ?>" step="0.01" required>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Kasa Payı Devir (₺)</label>
            <input type="number" name="reserve_carryover" id="month_reserve" value="<?= number_format($defaultReserveCarryover, 2, '.', '') ?>" step="0.01" required>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" id="month-submit-btn" style="flex: 1; padding: 12px 10px;">Oluştur</button>
            <button type="button" class="btn btn-ghost" id="month-cancel-btn" style="display: none;" onclick="cancelMonthEdit()">İptal</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="table-card">
    <div class="table-header">
        <h3><i data-lucide="calendar"></i> Mevcut Aylar</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Dönem</th>
                    <th class="text-right">Kasa Devir</th>
                    <th class="text-right">Kasa Payı Devir</th>
                    <th class="text-center">Durum</th>
                    <th class="text-right">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($months)): ?>
                    <tr><td colspan="5" class="text-center text-muted">Henüz ay oluşturulmadı</td></tr>
                <?php else: ?>
                    <?php foreach ($months as $m): ?>
                    <tr>
                        <td style="font-weight: 600;">
                            <?= $monthNames[$m['month']] ?> <?= $m['year'] ?>
                        </td>
                        <td class="text-right"><?= Calculator::money((float)$m['cash_carryover']) ?></td>
                        <td class="text-right text-success"><?= Calculator::money((float)$m['reserve_carryover']) ?></td>
                        <td class="text-center">
                            <?php if ($m['is_locked']): ?>
                                <span class="badge badge-warning"><i data-lucide="lock" style="width:12px;height:12px"></i> Kilitli</span>
                            <?php else: ?>
                                <span class="badge badge-success"><i data-lucide="unlock" style="width:12px;height:12px"></i> Açık</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div style="display:flex; justify-content:flex-end; gap:6px;">
                                <a href="?page=dashboard&year=<?= $m['year'] ?>&month=<?= $m['month'] ?>" class="btn btn-ghost btn-sm" title="Görüntüle">
                                    <i data-lucide="eye"></i>
                                </a>
                                <?php if (Auth::isAdmin()): ?>
                                <button type="button" class="btn btn-ghost btn-sm" title="Düzenle" onclick='editMonth(<?= json_encode($m) ?>)'>
                                    <i data-lucide="edit-2"></i>
                                </button>
                                <form method="POST" action="?page=months&action=toggleLock" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    <button type="submit" class="btn btn-ghost btn-sm" style="color: <?= $m['is_locked'] ? 'var(--info)' : 'var(--warning)' ?>" title="<?= $m['is_locked'] ? 'Kilidi Aç' : 'Kilitle' ?>">
                                        <i data-lucide="<?= $m['is_locked'] ? 'unlock' : 'lock' ?>"></i> 
                                    </button>
                                </form>
                                <?php if (!$m['is_locked']): ?>
                                <form id="del-month-<?= $m['id'] ?>" method="POST" action="?page=months&action=delete" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger)" title="Sil" onclick="confirmDeleteDouble('del-month-<?= $m['id'] ?>')">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function editMonth(m) {
    document.getElementById('month-form-title').innerHTML = '<i data-lucide="edit"></i> Ay Düzenle: ' + m.month + '/' + m.year;
    document.getElementById('month_id').value = m.id;
    document.getElementById('month_year').value = m.year;
    document.getElementById('month_month').value = m.month;
    document.getElementById('month_cash').value = parseFloat(m.cash_carryover).toFixed(2);
    document.getElementById('month_reserve').value = parseFloat(m.reserve_carryover).toFixed(2);
    document.getElementById('month-submit-btn').innerText = 'Güncelle';
    document.getElementById('month-cancel-btn').style.display = 'inline-flex';
    lucide.createIcons();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cancelMonthEdit() {
    document.getElementById('month-form-title').innerHTML = '<i data-lucide="plus-circle"></i> Yeni Ay Oluştur';
    document.getElementById('month_id').value = 0;
    document.getElementById('month-submit-btn').innerText = 'Oluştur';
    document.getElementById('month-cancel-btn').style.display = 'none';
    lucide.createIcons();
}

function confirmDeleteDouble(formId) {
    if (confirm('DİKKAT: Bu ayı silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')) {
        if (confirm('SON UYARI: Bu aya ait TÜM günlük girişler, giderler ve avanslar da silinecektir. Devam etmek istiyor musunuz?')) {
            document.getElementById(formId).submit();
        }
    }
}
</script>
