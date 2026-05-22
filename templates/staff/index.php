<?php
$monthNames = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
$trDays     = ['Mon'=>'Pzt','Tue'=>'Sal','Wed'=>'Çar','Thu'=>'Per','Fri'=>'Cum','Sat'=>'Cmt','Sun'=>'Paz'];
$prevM = $selectedMonth - 1; $prevY = $selectedYear; if ($prevM < 1)  { $prevM = 12; $prevY--; }
$nextM = $selectedMonth + 1; $nextY = $selectedYear; if ($nextM > 12) { $nextM = 1;  $nextY++; }
?>

<style>
.staff-detail-row { display:none; }
.staff-detail-row.open { display:table-row; }
.staff-day-table { width:100%; font-size:12px; border-collapse:collapse; }
.staff-day-table th, .staff-day-table td { padding:5px 10px; border-bottom:1px solid var(--border); }
.staff-day-table th { color:var(--text-muted); font-weight:600; background:var(--bg-hover); }
.balance-pill {
    display:inline-block; padding:2px 10px; border-radius:20px;
    font-size:11px; font-weight:700;
}
.balance-pill.positive { background:rgba(16,185,129,.12); color:#10b981; }
.balance-pill.negative { background:rgba(239,68,68,.12); color:#ef4444; }
.balance-pill.zero     { background:rgba(100,116,139,.12); color:#64748b; }
</style>

<!-- Başlık + Ay Seçici -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
    <h2 class="section-title" style="margin:0;"><i data-lucide="users"></i> Personel</h2>

    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
        <!-- Ay Navigatörü -->
        <a href="?page=staff&year=<?= $prevY ?>&month=<?= $prevM ?>" class="btn btn-ghost btn-sm">
            <i data-lucide="chevron-left"></i>
        </a>
        <form method="GET" style="display:flex; gap:8px;">
            <input type="hidden" name="page" value="staff">
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
        </form>
        <a href="?page=staff&year=<?= $nextY ?>&month=<?= $nextM ?>" class="btn btn-ghost btn-sm">
            <i data-lucide="chevron-right"></i>
        </a>

        <?php if (Auth::isAdmin()): ?>
        <button class="btn btn-primary" onclick="openModal('staffModal')">
            <i data-lucide="user-plus"></i> Personel Ekle
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Ay özet banner -->
<?php
$totalPaidThisMonth = array_sum(array_column($activeStaff, 'month_total'));
$totalSalary = array_sum(array_column(array_filter($activeStaff, fn($s) => $s['salary'] > 0), 'salary'));
?>
<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-bottom:20px;">
    <div class="card" style="padding:14px 18px; display:flex; flex-direction:column; gap:4px;">
        <span style="font-size:11px; color:var(--text-muted);">Aktif Personel</span>
        <span style="font-size:22px; font-weight:700;"><?= count($activeStaff) ?></span>
    </div>
    <div class="card" style="padding:14px 18px; display:flex; flex-direction:column; gap:4px;">
        <span style="font-size:11px; color:var(--text-muted);"><?= $monthNames[$selectedMonth] ?> Toplam Ödeme</span>
        <span style="font-size:22px; font-weight:700; color:#f59e0b;"><?= Calculator::money($totalPaidThisMonth) ?></span>
    </div>
    <?php if ($totalSalary > 0): ?>
    <div class="card" style="padding:14px 18px; display:flex; flex-direction:column; gap:4px;">
        <span style="font-size:11px; color:var(--text-muted);">Toplam Maaş Bordrosu</span>
        <span style="font-size:22px; font-weight:700; color:#6366f1;"><?= Calculator::money($totalSalary) ?></span>
    </div>
    <div class="card" style="padding:14px 18px; display:flex; flex-direction:column; gap:4px;">
        <span style="font-size:11px; color:var(--text-muted);">Hakediş Kalan Bakiye</span>
        <?php
        $remaining = 0;
        foreach ($activeStaff as $s) { if ($s['balance'] !== null) $remaining += $s['balance']; }
        ?>
        <span style="font-size:22px; font-weight:700; color:<?= $remaining >= 0 ? '#10b981' : '#ef4444' ?>;"><?= Calculator::money($remaining) ?></span>
    </div>
    <?php endif; ?>
</div>

<!-- Aktif Personel Tablosu -->
<div class="table-card" style="margin-bottom:24px;">
    <div class="table-header">
        <h3><i data-lucide="users"></i> Aktif Personel — <?= $monthNames[$selectedMonth] ?> <?= $selectedYear ?></h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:32px;"></th>
                    <th>Ad Soyad</th>
                    <th>Pozisyon</th>
                    <th class="text-right">Maaş</th>
                    <th class="text-right"><?= $monthNames[$selectedMonth] ?> Ödeme</th>
                    <th class="text-right">Hakediş Kalan</th>
                    <th class="text-right">Tüm Zamanlar</th>
                    <?php if (Auth::isAdmin()): ?>
                    <th class="text-right">İşlem</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activeStaff)): ?>
                <tr><td colspan="8" class="text-center text-muted">Aktif personel yok.</td></tr>
                <?php else: ?>
                <?php foreach ($activeStaff as $s):
                    $hasDays = !empty($s['daily_payments']);
                ?>
                <!-- Ana Satır -->
                <tr id="staff-row-<?= $s['id'] ?>" style="cursor:pointer;" onclick="toggleStaffDetail(<?= $s['id'] ?>)">
                    <td style="text-align:center; color:var(--text-muted); font-size:13px;">
                        <i data-lucide="chevron-right" id="staff-icon-<?= $s['id'] ?>" style="width:16px;height:16px;transition:transform .2s;"></i>
                    </td>
                    <td style="font-weight:600;"><?= htmlspecialchars($s['name']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($s['position'] ?: '—') ?></td>
                    <td class="text-right">
                        <?php if ($s['salary'] > 0): ?>
                            <span style="color:#6366f1; font-weight:600;"><?= Calculator::money((float)$s['salary']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">Gündelikçi</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <?php if ($s['month_total'] > 0): ?>
                            <span style="color:#f59e0b; font-weight:600;"><?= Calculator::money($s['month_total']) ?></span>
                            <small class="text-muted" style="font-size:10px; display:block;">
                                <?php if ($s['salary'] > 0): ?>
                                    <?= count($s['salary_payments']) ?> avans<?= !empty($s['trial_payments']) ? ' + ' . count($s['trial_payments']) . ' maaş dışı' : '' ?>
                                <?php else: ?>
                                    <?= count($s['daily_payments']) ?> gün
                                <?php endif; ?>
                            </small>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <?php if ($s['balance'] !== null): ?>
                            <?php
                            $balClass = $s['balance'] > 0 ? 'positive' : ($s['balance'] < 0 ? 'negative' : 'zero');
                            $balLabel = $s['balance'] > 0 ? 'Hakediş Kalan' : ($s['balance'] < 0 ? 'Fazla Ödendi' : 'Eşit');
                            ?>
                            <span class="balance-pill <?= $balClass ?>"><?= $balLabel ?></span>
                            <span style="font-weight:700; font-size:13px; display:block; margin-top:2px; color:<?= $balClass === 'positive' ? '#10b981' : ($balClass === 'negative' ? '#ef4444' : 'var(--text-muted)') ?>">
                                <?= Calculator::money(abs($s['balance'])) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right text-muted" style="font-size:12px;"><?= $s['all_time_total'] > 0 ? Calculator::money($s['all_time_total']) : '—' ?></td>
                    <?php if (Auth::isAdmin()): ?>
                    <td class="text-right" onclick="event.stopPropagation()">
                        <div style="display:flex;justify-content:flex-end;gap:6px;">
                            <button class="btn btn-ghost btn-sm" onclick='editStaff(<?= json_encode($s) ?>)'>
                                <i data-lucide="edit-2"></i>
                            </button>
                            <button class="btn btn-ghost btn-sm" style="color:var(--warning);"
                                    onclick="openArchiveModal(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>')">
                                <i data-lucide="archive"></i>
                            </button>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>

                <!-- Detay Satırı -->
                <tr class="staff-detail-row" id="staff-detail-<?= $s['id'] ?>">
                    <td colspan="<?= Auth::isAdmin() ? 8 : 7 ?>" style="padding:0; background:var(--bg-hover);">
                        <div style="padding:16px 24px;">
                            <div style="display:grid; grid-template-columns:1fr<?= $s['salary'] > 0 ? ' 280px' : '' ?>; gap:20px; align-items:start;">

                                <!-- Günlük Kırılım -->
                                <div>
                                    <div style="font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px;">
                                        <?= $monthNames[$selectedMonth] ?> <?= $selectedYear ?> — Günlük Ödemeler
                                    </div>
                                    <?php if ($hasDays): ?>
                                    <table class="staff-day-table">
                                        <thead>
                                            <tr>
                                                <th>Tarih</th>
                                                <th class="text-right">Ödeme</th>
                                                <th class="text-right">Tür</th>
                                                <th class="text-right">Kümülatif (Avans)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $cumSalary = 0; foreach ($s['daily_payments'] as $day):
                                                $dayIsSalary = (int)$day['is_salary'] === 1;
                                                if ($dayIsSalary) $cumSalary += (float)$day['amount'];
                                            ?>
                                            <tr style="<?= !$dayIsSalary ? 'opacity:0.65;' : '' ?>">
                                                <td><?= date('d.m.Y', strtotime($day['entry_date'])) . ' (' . ($trDays[date('D', strtotime($day['entry_date']))] ?? '') . ')' ?></td>
                                                <td class="text-right" style="color:<?= $dayIsSalary ? '#f59e0b' : '#94a3b8' ?>; font-weight:600;">
                                                    <?= Calculator::money($day['amount']) ?>
                                                </td>
                                                <td class="text-right">
                                                    <?php if ($dayIsSalary): ?>
                                                        <span style="font-size:10px; background:rgba(245,158,11,.12); color:#f59e0b; padding:1px 6px; border-radius:10px;">Avans</span>
                                                    <?php else: ?>
                                                        <span style="font-size:10px; background:rgba(100,116,139,.12); color:#94a3b8; padding:1px 6px; border-radius:10px;">Maaş Dışı</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right text-muted"><?= $dayIsSalary ? Calculator::money($cumSalary) : '—' ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <?php if (!empty($s['trial_payments'])): ?>
                                            <tr style="font-size:11px; color:var(--text-muted);">
                                                <td colspan="2">Maaş dışı ödemeler (hakediş dışı)</td>
                                                <td></td>
                                                <td class="text-right"><?= Calculator::money($s['trial_total']) ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr style="font-weight:700; border-top:2px solid var(--border);">
                                                <td>
                                                    <?php if ($s['salary'] > 0): ?>
                                                        <?= count($s['salary_payments']) ?> avans ödemesi
                                                    <?php else: ?>
                                                        <?= count($s['daily_payments']) ?> gün çalışma
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right" style="color:#f59e0b;"><?= Calculator::money($s['month_total']) ?></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    <?php else: ?>
                                    <div style="color:var(--text-muted); font-size:13px; padding:12px 0;">
                                        Bu ayda ödeme kaydı bulunmuyor.
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Maaş Özeti (sadece maaşlı personel) -->
                                <?php if ($s['salary'] > 0): ?>
                                <div style="background:var(--bg-surface); border:1px solid var(--border); border-radius:var(--radius-md); padding:16px;">
                                    <div style="font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:12px; text-transform:uppercase; letter-spacing:.5px;">
                                        Hakediş Hesabı <span style="font-size:10px; font-weight:400;">(30 gün baz)</span>
                                    </div>

                                    <!-- Formül: hakediş = (geçen takvim günü / 30) × maaş -->
                                    <div style="display:flex; flex-direction:column; gap:8px;">

                                        <!-- Aylık Maaş -->
                                        <div style="display:flex; justify-content:space-between; font-size:12px;">
                                            <span style="color:var(--text-muted);">Aylık Maaş</span>
                                            <span style="color:#6366f1; font-weight:600;"><?= Calculator::money((float)$s['salary']) ?></span>
                                        </div>

                                        <!-- Günlük oran -->
                                        <div style="display:flex; justify-content:space-between; font-size:12px;">
                                            <span style="color:var(--text-muted);">Günlük oran (÷30)</span>
                                            <span><?= Calculator::money($s['daily_rate']) ?>/gün</span>
                                        </div>

                                        <!-- Hakediş: takvim günü bazlı (işe giriş tarihinden itibaren) -->
                                        <div style="display:flex; justify-content:space-between; font-size:13px; padding:6px 0; border-top:1px dashed var(--border); border-bottom:1px dashed var(--border); margin:2px 0;">
                                            <span style="color:var(--text-muted);">
                                                Hakediş
                                                <small>
                                                    (<?= $s['days_elapsed'] ?> gün
                                                    <?php if ($s['start_date'] && date('Ym', strtotime($s['start_date'])) === sprintf('%04d%02d', $selectedYear, $selectedMonth)): ?>
                                                        · <?= date('d.m', strtotime($s['start_date'])) ?>'den itibaren
                                                    <?php endif; ?>
                                                    × günlük oran)
                                                </small>
                                            </span>
                                            <span style="font-weight:700; color:#f59e0b;"><?= Calculator::money($s['hakedis']) ?></span>
                                        </div>

                                        <!-- Ödenen Avanslar -->
                                        <div style="display:flex; justify-content:space-between; font-size:12px;">
                                            <span style="color:var(--text-muted);">
                                                Ödenen Avanslar
                                                <small style="color:var(--text-muted);">(<?= count($s['daily_payments']) ?> ödeme)</small>
                                            </span>
                                            <span style="color:#94a3b8;">-<?= Calculator::money($s['month_total']) ?></span>
                                        </div>

                                        <!-- Bakiye = hakediş - ödenen -->
                                        <div style="display:flex; justify-content:space-between; font-size:15px; font-weight:700; padding-top:8px; border-top:2px solid var(--border); margin-top:2px;">
                                            <span>Kalan Bakiye</span>
                                            <span style="color:<?= $s['balance'] >= 0 ? '#10b981' : '#ef4444' ?>;">
                                                <?= $s['balance'] >= 0 ? '' : '-' ?><?= Calculator::money(abs($s['balance'])) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <?php if ($s['balance'] < 0): ?>
                                    <div style="margin-top:10px; padding:8px 10px; background:rgba(239,68,68,.08); border-radius:var(--radius-sm); font-size:11px; color:#ef4444;">
                                        <i data-lucide="alert-triangle" style="width:12px;height:12px;display:inline;"></i>
                                        Hakedişten <?= Calculator::money(abs($s['balance'])) ?> fazla avans ödendi.
                                    </div>
                                    <?php elseif ($s['balance'] == 0): ?>
                                    <div style="margin-top:10px; padding:8px 10px; background:rgba(100,116,139,.08); border-radius:var(--radius-sm); font-size:11px; color:#64748b;">
                                        Hakediş ile ödeme eşit.
                                    </div>
                                    <?php else: ?>
                                    <div style="margin-top:10px; padding:8px 10px; background:rgba(16,185,129,.08); border-radius:var(--radius-sm); font-size:11px; color:#10b981;">
                                        <i data-lucide="clock" style="width:12px;height:12px;display:inline;"></i>
                                        <?= Calculator::money($s['balance']) ?> hakediş ödemesi bekliyor.
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Arşiv -->
<?php if (!empty($archivedStaff)): ?>
<div class="table-card" style="opacity:0.8;">
    <div class="table-header">
        <h3><i data-lucide="archive"></i> Arşiv (Ayrılan Personel)</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ad Soyad</th>
                    <th>Pozisyon</th>
                    <th class="text-right">Maaş</th>
                    <th>Başlangıç</th>
                    <th>Ayrılış</th>
                    <th class="text-right">Tüm Zamanlar Toplam</th>
                    <?php if (Auth::isAdmin()): ?>
                    <th class="text-right">İşlem</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($archivedStaff as $s):
                    $allTime = Database::fetch("SELECT COALESCE(SUM(amount),0) AS total FROM staff_expenses WHERE staff_id = ?", [$s['id']]);
                ?>
                <tr>
                    <td style="font-weight:600;color:var(--text-muted);"><?= htmlspecialchars($s['name']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($s['position'] ?: '—') ?></td>
                    <td class="text-right"><?= $s['salary'] > 0 ? Calculator::money((float)$s['salary']) : '—' ?></td>
                    <td class="text-muted"><?= $s['start_date'] ? date('d.m.Y', strtotime($s['start_date'])) : '—' ?></td>
                    <td class="text-muted"><?= $s['end_date'] ? date('d.m.Y', strtotime($s['end_date'])) : '—' ?></td>
                    <td class="text-right text-muted"><?= (float)$allTime['total'] > 0 ? Calculator::money((float)$allTime['total']) : '—' ?></td>
                    <?php if (Auth::isAdmin()): ?>
                    <td class="text-right">
                        <form method="POST" action="?page=staff&action=reactivate" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--success);">
                                <i data-lucide="user-check"></i> Geri Al
                            </button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Personel Ekle/Düzenle Modal -->
<div class="modal-overlay" id="staffModal">
    <div class="modal-box" style="max-width:500px;">
        <div class="modal-header">
            <h3 id="staffModalTitle"><i data-lucide="user-plus"></i> Personel Ekle</h3>
            <button type="button" class="modal-close" onclick="closeModal('staffModal')"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" action="?page=staff&action=save">
            <input type="hidden" name="id" id="staff_id" value="0">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:14px;">
                <div class="form-group" style="margin:0;">
                    <label>Ad Soyad <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="name" id="staff_name" class="form-input" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group" style="margin:0;">
                        <label>Pozisyon / Görev</label>
                        <input type="text" name="position" id="staff_position" class="form-input" placeholder="Şoför, Operatör...">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label>Maaş (₺) <small style="color:var(--text-muted);">— gündelikçi için 0</small></label>
                        <input type="text" inputmode="decimal" name="salary" id="staff_salary" class="form-input" placeholder="0,00">
                    </div>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>İşe Başlama Tarihi</label>
                    <input type="date" name="start_date" id="staff_start" class="form-input">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Notlar</label>
                    <textarea name="notes" id="staff_notes" class="form-input" rows="2" style="resize:vertical;"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('staffModal')">İptal</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- İşten Çıkarma Modal -->
<div class="modal-overlay" id="archiveModal">
    <div class="modal-box" style="max-width:400px;">
        <div class="modal-header">
            <h3><i data-lucide="archive"></i> İşten Çıkar</h3>
            <button type="button" class="modal-close" onclick="closeModal('archiveModal')"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" action="?page=staff&action=archive">
            <input type="hidden" name="id" id="archive_staff_id">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:14px;">
                <p style="margin:0;color:var(--text-secondary);">
                    <strong id="archive_staff_name"></strong> arşive alınacak.
                </p>
                <div class="form-group" style="margin:0;">
                    <label>Ayrılış Tarihi</label>
                    <input type="date" name="end_date" id="archive_end_date" class="form-input" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('archiveModal')">İptal</button>
                <button type="submit" class="btn btn-warning">Arşive Al</button>
            </div>
        </form>
    </div>
</div>

<script>
function editStaff(s) {
    document.getElementById('staffModalTitle').innerHTML = '<i data-lucide="edit-2"></i> Personel Düzenle';
    document.getElementById('staff_id').value       = s.id;
    document.getElementById('staff_name').value     = s.name;
    document.getElementById('staff_position').value = s.position || '';
    document.getElementById('staff_salary').value   = s.salary > 0 ? parseFloat(s.salary).toLocaleString('tr-TR', {minimumFractionDigits:2}) : '';
    document.getElementById('staff_start').value    = s.start_date || '';
    document.getElementById('staff_notes').value    = s.notes || '';
    openModal('staffModal');
    if (window.lucide) window.lucide.createIcons();
}

function openArchiveModal(id, name) {
    document.getElementById('archive_staff_id').value = id;
    document.getElementById('archive_staff_name').textContent = name;
    openModal('archiveModal');
}

function toggleStaffDetail(id) {
    const detail = document.getElementById('staff-detail-' + id);
    const icon   = document.getElementById('staff-icon-' + id);
    const isOpen = detail.classList.toggle('open');
    icon.style.transform = isOpen ? 'rotate(90deg)' : '';
    if (isOpen && window.lucide) window.lucide.createIcons();
}
</script>
