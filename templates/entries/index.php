<?php
$monthNames = ['', 'Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
?>

<!-- Ay Seçici -->
<div class="month-selector">
    <form method="GET" class="month-form">
        <input type="hidden" name="page" value="entries">
        <div class="month-nav">
            <?php
            $prevMonth = $selectedMonth - 1;
            $prevYear = $selectedYear;
            if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
            $nextMonth = $selectedMonth + 1;
            $nextYear = $selectedYear;
            if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
            ?>
            <a href="?page=entries&year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-ghost btn-sm"><i data-lucide="chevron-left"></i></a>
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
            <a href="?page=entries&year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-ghost btn-sm"><i data-lucide="chevron-right"></i></a>
            
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
        <button type="button" class="btn btn-primary" onclick="openModal('entryModal')">
            <i data-lucide="plus"></i> Yeni Kayıt Ekle
        </button>
    </div>

    <!-- Yeni / Düzenle Modalı -->
    <div class="modal-overlay <?= $editEntry ? 'active' : '' ?>" id="entryModal">
        <div class="modal-box" style="max-width: 800px;">
            <div class="modal-header">
                <h3><i data-lucide="<?= $editEntry ? 'edit' : 'plus-circle' ?>"></i> <?= $editEntry ? 'Kaydı Düzenle' : 'Yeni Kayıt Ekle' ?></h3>
                <button type="button" class="modal-close" onclick="closeModal('entryModal')"><i data-lucide="x"></i></button>
            </div>
            <form method="POST" action="?page=entries&action=save">
                <div class="modal-body" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end;">
                    <input type="hidden" name="month_id" value="<?= $month['id'] ?>">
                    <?php if ($editEntry): ?>
                        <input type="hidden" name="id" value="<?= $editEntry['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Tarih</label>
                        <?php 
                            $defaultDate = date('Y-m-d');
                            if (date('Y-m') !== sprintf('%04d-%02d', $month['year'], $month['month'])) {
                                $defaultDate = sprintf('%04d-%02d-01', $month['year'], $month['month']);
                            }
                        ?>
                        <input type="date" name="entry_date" required 
                               min="<?= sprintf('%04d-%02d-01', $month['year'], $month['month']) ?>" 
                               max="<?= date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $month['year'], $month['month']))) ?>"
                               value="<?= $editEntry ? $editEntry['entry_date'] : $defaultDate ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Gelir (₺)</label>
                        <input type="text" inputmode="decimal" name="revenue" value="<?= $editEntry ? number_format((float)$editEntry['revenue'], 2, ',', '') : '' ?>" placeholder="0,00" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Dış Gelir (Hakan) (₺)</label>
                        <input type="text" inputmode="decimal" name="external_revenue" value="<?= $editEntry ? number_format((float)$editEntry['external_revenue'], 2, ',', '') : '' ?>" placeholder="0,00">
                    </div>
                    
                    <div style="grid-column: 1 / -1; height: 1px; background: var(--border); margin: 8px 0;"></div>

                    <?php $catGroupIdx = 0; foreach ($parentCategories as $parent):
                        $children = array_values(array_filter($allCategories, fn($c) => (int)$c['parent_id'] === (int)$parent['id']));
                        $catGroupIdx++;
                        $groupId = 'catgrp_' . $catGroupIdx;

                        // Düzenleme modunda bu grubun dolu değeri var mı?
                        $hasValue = false;
                        if ($editEntry) {
                            $checkIds = !empty($children) ? array_column($children,'id') : [$parent['id']];
                            foreach ($checkIds as $cid) {
                                if (!empty($editEntry['expenses'][$cid])) { $hasValue = true; break; }
                            }
                        }
                    ?>
                        <?php if (!empty($children)): ?>
                        <!-- Açılır/kapanır ana kategori grubu -->
                        <div style="grid-column: 1 / -1; margin-top:6px;">
                            <button type="button"
                                onclick="toggleCatGroup('<?= $groupId ?>')"
                                style="display:flex;align-items:center;gap:8px;width:100%;background:rgba(30,41,59,0.5);border:1px solid var(--border);border-radius:8px;padding:7px 12px;cursor:pointer;color:var(--text-secondary);transition:all 0.15s;"
                                id="btn_<?= $groupId ?>">
                                <i data-lucide="folder" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;"></i>
                                <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;flex:1;text-align:left;"><?= htmlspecialchars($parent['name']) ?></span>
                                <i data-lucide="chevron-down" id="chev_<?= $groupId ?>" style="width:13px;height:13px;transition:transform 0.2s;<?= $hasValue ? 'transform:rotate(180deg)' : '' ?>"></i>
                            </button>
                        </div>
                        <div id="<?= $groupId ?>" style="grid-column: 1 / -1; display:<?= $hasValue ? 'grid' : 'none' ?>; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:12px; padding:10px 4px 4px;">
                            <?php foreach ($children as $cat):
                                // Kredi Ödemesi: sistem yönetimli, düzenlenemez — oku olarak göster
                                if ($cat['slug'] === 'kredi-odeme'):
                                    $lVal = $editEntry && isset($editEntry['expenses'][$cat['id']]) ? abs($editEntry['expenses'][$cat['id']]) : 0;
                                    $lNote = $editEntry && isset($editEntry['expense_notes'][$cat['id']]) ? $editEntry['expense_notes'][$cat['id']] : '';
                                    if ($lVal > 0): ?>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label style="display:flex;align-items:center;gap:5px;">
                                            <?= htmlspecialchars($cat['name']) ?>
                                            <span style="font-size:9px;background:rgba(99,102,241,0.15);color:#818cf8;border-radius:4px;padding:1px 5px;font-weight:600;">SİSTEM</span>
                                        </label>
                                        <div style="background:rgba(30,41,59,0.6);border:1px solid var(--border);border-radius:6px;padding:6px 10px;font-size:12px;font-weight:600;color:var(--danger);">
                                            <?= Calculator::money($lVal, false) ?>
                                            <?php if ($lNote): ?><br><span style="font-size:10px;color:var(--text-muted);font-weight:400;"><?= htmlspecialchars($lNote) ?></span><?php endif; ?>
                                        </div>
                                        <small style="color:var(--text-muted);font-size:10px;">Krediler sayfasından düzenlenir</small>
                                    </div>
                                    <?php endif; ?>
                                <?php continue; endif; ?>
                                <?php
                                $val     = 0;
                                $noteVal = '';
                                if ($editEntry && isset($editEntry['expenses'][$cat['id']])) {
                                    $val = abs($editEntry['expenses'][$cat['id']]);
                                }
                                if ($editEntry && isset($editEntry['expense_notes'][$cat['id']])) {
                                    $noteVal = $editEntry['expense_notes'][$cat['id']];
                                }
                                ?>
                            <div class="form-group" style="margin-bottom:0;">
                                <label><?= htmlspecialchars($cat['name']) ?> (₺)</label>
                                <input type="text" inputmode="decimal" name="expenses[<?= $cat['id'] ?>]"
                                       value="<?= $val > 0 ? number_format($val, 2, ',', '') : '' ?>" placeholder="0,00"
                                       class="exp-amount-input">
                                <input type="text" name="expense_notes[<?= $cat['id'] ?>]"
                                       value="<?= htmlspecialchars($noteVal) ?>"
                                       placeholder="Açıklama..."
                                       class="expense-note-field"
                                       style="margin-top:4px; font-size:11px; padding:4px 8px; color:var(--text-muted); display:<?= ($val > 0 || $noteVal !== '') ? 'block' : 'none' ?>;">
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php else: ?>
                        <!-- Alt kategorisi yok — personel kategorisi mi kontrol et -->
                        <?php if ($parent['slug'] === 'personel' && !empty($activeStaff)):
                            $hasValue = false;
                            if ($editEntry && !empty($editEntry['staff_expenses'])) {
                                foreach ($editEntry['staff_expenses'] as $sv) { if ($sv) { $hasValue = true; break; } }
                            }
                        ?>
                        <div style="grid-column: 1 / -1; margin-top:6px;">
                            <button type="button"
                                onclick="toggleCatGroup('<?= $groupId ?>')"
                                style="display:flex;align-items:center;gap:8px;width:100%;background:rgba(30,41,59,0.5);border:1px solid var(--border);border-radius:8px;padding:7px 12px;cursor:pointer;color:var(--text-secondary);transition:all 0.15s;">
                                <i data-lucide="users" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;"></i>
                                <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;flex:1;text-align:left;"><?= htmlspecialchars($parent['name']) ?></span>
                                <i data-lucide="chevron-down" id="chev_<?= $groupId ?>" style="width:13px;height:13px;transition:transform 0.2s;<?= $hasValue ? 'transform:rotate(180deg)' : '' ?>"></i>
                            </button>
                        </div>
                        <div id="<?= $groupId ?>" style="grid-column: 1 / -1; display:<?= $hasValue ? 'grid' : 'none' ?>; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:12px; padding:10px 4px 4px;">
                            <?php foreach ($activeStaff as $st):
                                $sval    = 0;
                                $isTrial = false;
                                if ($editEntry && isset($editEntry['staff_expenses'][$st['id']])) {
                                    $sval    = abs($editEntry['staff_expenses'][$st['id']]);
                                    $isTrial = !empty($editEntry['staff_trial'][$st['id']]);
                                }
                            ?>
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="display:flex; align-items:center; justify-content:space-between; gap:6px;">
                                    <span><?= htmlspecialchars($st['name']) ?> (₺)</span>
                                    <?php if ($st['salary'] > 0): ?>
                                    <label style="display:flex; align-items:center; gap:4px; font-size:10px; font-weight:400; color:var(--text-muted); cursor:pointer; white-space:nowrap;">
                                        <input type="checkbox" name="staff_trial[<?= $st['id'] ?>]" value="1"
                                               <?= $isTrial ? 'checked' : '' ?>
                                               style="width:12px;height:12px;accent-color:#f59e0b;">
                                        Maaş dışı
                                    </label>
                                    <?php endif; ?>
                                </label>
                                <input type="text" inputmode="decimal" name="staff_expenses[<?= $st['id'] ?>]"
                                       value="<?= $sval > 0 ? number_format($sval, 2, ',', '') : '' ?>"
                                       placeholder="<?= $st['salary'] > 0 ? number_format((float)$st['salary'], 2, ',', '') : '0,00' ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <!-- Direkt input -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><?= htmlspecialchars($parent['name']) ?> (₺)</label>
                            <?php
                                $val     = 0;
                                $noteVal = '';
                                if ($editEntry && isset($editEntry['expenses'][$parent['id']])) {
                                    $val = abs($editEntry['expenses'][$parent['id']]);
                                }
                                if ($editEntry && isset($editEntry['expense_notes'][$parent['id']])) {
                                    $noteVal = $editEntry['expense_notes'][$parent['id']];
                                }
                            ?>
                            <input type="text" inputmode="decimal" name="expenses[<?= $parent['id'] ?>]"
                                   value="<?= $val > 0 ? number_format($val, 2, ',', '') : '' ?>" placeholder="0,00"
                                   class="exp-amount-input">
                            <input type="text" name="expense_notes[<?= $parent['id'] ?>]"
                                   value="<?= htmlspecialchars($noteVal) ?>"
                                   placeholder="Açıklama..."
                                   class="expense-note-field"
                                   style="margin-top:4px; font-size:11px; padding:4px 8px; color:var(--text-muted); display:<?= ($val > 0 || $noteVal !== '') ? 'block' : 'none' ?>;">
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <div style="grid-column: 1 / -1; height: 1px; background: var(--border); margin: 8px 0;"></div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>POS (₺) <small style="color:var(--text-muted);font-weight:normal">(Bilgi amaçlı)</small></label>
                        <input type="text" inputmode="decimal" name="pos_amount" value="<?= $editEntry ? number_format((float)$editEntry['pos_amount'], 2, ',', '') : '' ?>" placeholder="0,00">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1; margin-bottom: 0;">
                        <label>Notlar</label>
                        <input type="text" name="notes" value="<?= htmlspecialchars($editEntry['notes'] ?? '') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if ($editEntry): ?>
                        <a href="?page=entries&year=<?= $month['year'] ?>&month=<?= $month['month'] ?>" class="btn btn-ghost">İptal</a>
                    <?php else: ?>
                        <button type="button" class="btn btn-ghost" onclick="closeModal('entryModal')">İptal</button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?= $editEntry ? 'Güncelle' : 'Kaydet' ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Kayıtlar Tablosu -->
    <div class="table-card">
        <div class="table-header">
            <h3><i data-lucide="list"></i> <?= $monthNames[$month['month']] ?> <?= $month['year'] ?> Kayıtları</h3>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th>Tarih</th>
                        <th class="text-right">Gelir</th>
                        <th class="text-right">Dış Gelir</th>
                        <th class="text-right">Toplam Gider</th>
                        <th class="text-right">Net Kâr</th>
                        <th class="text-right">POS</th>
                        <?php if (Auth::isAdmin() && !$month['is_locked']): ?>
                            <th class="text-right">İşlem</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                        <tr><td colspan="<?= 7 + (Auth::isAdmin() && !$month['is_locked'] ? 1 : 0) ?>" class="text-center text-muted">Kayıt bulunamadı.</td></tr>
                    <?php else: ?>
                        <?php 
                            $totals = ['rev'=>0, 'ext'=>0, 'pos'=>0, 'exp'=>0, 'net'=>0];
                        ?>
                        <?php foreach ($entries as $entry): ?>
                        <?php
                            // Toplam gider
                            $entryTotalExp = 0;
                            foreach ($entry['expenses'] as $amt) {
                                $entryTotalExp += (float)$amt;
                            }
                            $entryCuro = (float)$entry['revenue'] + (float)$entry['external_revenue'];
                            $entryNet  = $entryCuro + $entryTotalExp; // giderler negatif

                            $totals['rev'] += (float)$entry['revenue'];
                            $totals['ext'] += (float)$entry['external_revenue'];
                            $totals['pos'] += (float)$entry['pos_amount'];
                            $totals['exp'] += $entryTotalExp;
                            $totals['net'] += $entryNet;

                            $rowId = 'detail-' . $entry['id'];
                        ?>
                        <!-- Özet Satır -->
                        <tr class="entry-summary-row" onclick="toggleDetail('<?= $rowId ?>')" style="cursor:pointer;" title="Detayı göster/gizle">
                            <td style="text-align:center; color:var(--text-muted);">
                                <i data-lucide="chevron-right" id="chev-<?= $rowId ?>" style="width:14px;height:14px;transition:transform 0.2s;"></i>
                            </td>
                            <td style="font-weight:600;"><?= date('d.m.Y', strtotime($entry['entry_date'])) ?></td>
                            <td class="text-right text-success"><?= Calculator::money((float)$entry['revenue'], false) ?></td>
                            <td class="text-right text-accent"><?= (float)$entry['external_revenue'] > 0 ? Calculator::money((float)$entry['external_revenue'], false) : '-' ?></td>
                            <td class="text-right text-danger"><?= $entryTotalExp < 0 ? Calculator::money(abs($entryTotalExp), false) : '-' ?></td>
                            <td class="text-right" style="font-weight:700; color:<?= $entryNet >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
                                <?= Calculator::money($entryNet, false) ?>
                            </td>
                            <td class="text-right" style="color:var(--text-muted);">
                                <?= (float)$entry['pos_amount'] > 0 ? Calculator::money((float)$entry['pos_amount'], false) : '-' ?>
                            </td>
                            <?php if (Auth::isAdmin() && !$month['is_locked']): ?>
                            <td class="text-right" onclick="event.stopPropagation()">
                                <div style="display:flex; justify-content:flex-end; gap:4px;">
                                    <a href="?page=entries&year=<?= $month['year'] ?>&month=<?= $month['month'] ?>&edit_id=<?= $entry['id'] ?>" class="btn btn-ghost btn-sm" title="Düzenle"><i data-lucide="edit-2"></i></a>
                                    <form id="delete-form-<?= $entry['id'] ?>" method="POST" action="?page=entries&action=delete" style="display:none;">
                                        <input type="hidden" name="id" value="<?= $entry['id'] ?>">
                                    </form>
                                    <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger)" title="Sil"
                                        onclick="confirmDelete('delete-form-<?= $entry['id'] ?>', 'Bu kaydı silmek istediğinize emin misiniz?')">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>

                        <!-- Detay Satır (gizli) -->
                        <tr id="<?= $rowId ?>" style="display:none; background:rgba(30,41,59,0.4);">
                            <td colspan="<?= 7 + (Auth::isAdmin() && !$month['is_locked'] ? 1 : 0) ?>" style="padding:0;">
                                <div style="padding:12px 20px 14px; display:flex; flex-wrap:wrap; gap:10px; align-items:flex-start;">
                                    <?php foreach ($categories as $cat):
                                        $expAmt = isset($entry['expenses'][$cat['id']]) ? (float)$entry['expenses'][$cat['id']] : 0;
                                        if ($expAmt == 0) continue;
                                    ?>
                                    <?php if (($cat['slug'] ?? '') === 'personel' && !empty($entry['staff_expenses'])): ?>
                                    <div style="background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); border-radius:8px; padding:6px 12px; min-width:160px;">
                                        <div style="font-size:10px; color:var(--text-muted); margin-bottom:6px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px;">Personel Giderleri</div>
                                        <?php foreach ($entry['staff_expenses'] as $staffData): ?>
                                        <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; font-size:12px; margin-top:3px;">
                                            <span style="color:var(--text-secondary);"><?= htmlspecialchars($staffData['name']) ?></span>
                                            <span style="font-weight:600; color:var(--danger);"><?= Calculator::money($staffData['amount'], false) ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php if (count($entry['staff_expenses']) > 1): ?>
                                        <div style="border-top:1px solid rgba(239,68,68,0.25); margin-top:5px; padding-top:5px; display:flex; justify-content:space-between; font-size:12px;">
                                            <span style="color:var(--text-muted);">Toplam</span>
                                            <span style="font-weight:700; color:var(--danger);"><?= Calculator::money(abs($expAmt), false) ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <?php
                                    // expNoteMap'ten zaten sistem notları filtrelendi;
                                    // kredi-odeme için de orijinal notes'u al (loan başlığını göster)
                                    $expNote = $entry['expense_notes'][$cat['id']] ?? '';
                                    $isLoanCat = ($cat['slug'] ?? '') === 'kredi-odeme';
                                    $bgColor  = $isLoanCat ? 'rgba(99,102,241,0.08)' : 'rgba(239,68,68,0.08)';
                                    $bdColor  = $isLoanCat ? 'rgba(99,102,241,0.25)' : 'rgba(239,68,68,0.2)';
                                    ?>
                                    <div style="background:<?= $bgColor ?>; border:1px solid <?= $bdColor ?>; border-radius:8px; padding:6px 12px; min-width:120px;">
                                        <div style="font-size:10px; color:var(--text-muted); margin-bottom:2px;">
                                            <?= htmlspecialchars($cat['name']) ?>
                                            <?php if ($isLoanCat): ?><span style="font-size:9px;background:rgba(99,102,241,0.15);color:#818cf8;border-radius:3px;padding:1px 4px;margin-left:4px;">KREDİ</span><?php endif; ?>
                                        </div>
                                        <div style="font-size:13px; font-weight:600; color:var(--danger);"><?= Calculator::money(abs($expAmt), false) ?></div>
                                        <?php if ($expNote !== ''): ?>
                                        <div style="font-size:10px; color:var(--text-muted); margin-top:3px; font-style:italic;"><?= htmlspecialchars($expNote) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php endforeach; ?>


                                    <?php if (!empty($entry['notes'])): ?>
                                    <div style="background:rgba(100,116,139,0.1); border:1px solid var(--border); border-radius:8px; padding:6px 12px;">
                                        <div style="font-size:10px; color:var(--text-muted); margin-bottom:2px;">Not</div>
                                        <div style="font-size:12px; color:var(--text-secondary);"><?= htmlspecialchars($entry['notes']) ?></div>
                                    </div>
                                    <?php endif; ?>

                                    <?php
                                    // Hiç gider yoksa mesaj göster
                                    $hasAnyDetail = false;
                                    foreach ($categories as $cat) {
                                        if (isset($entry['expenses'][$cat['id']]) && (float)$entry['expenses'][$cat['id']] != 0) { $hasAnyDetail = true; break; }
                                    }
                                    if (!$hasAnyDetail && empty($entry['notes'])): ?>
                                    <span style="font-size:12px; color:var(--text-muted); padding:6px 0;">Gider kaydı yok</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <!-- Toplam Satırı -->
                        <tr style="background: var(--bg-hover); font-weight: bold; border-top: 2px solid var(--border);">
                            <td></td>
                            <td>TOPLAM</td>
                            <td class="text-right text-success"><?= Calculator::money($totals['rev'], false) ?></td>
                            <td class="text-right text-accent"><?= Calculator::money($totals['ext'], false) ?></td>
                            <td class="text-right text-danger"><?= Calculator::money(abs($totals['exp']), false) ?></td>
                            <td class="text-right" style="color:<?= $totals['net'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
                                <?= Calculator::money($totals['net'], false) ?>
                            </td>
                            <td class="text-right"><?= Calculator::money($totals['pos'], false) ?></td>
                            <?php if (Auth::isAdmin() && !$month['is_locked']): ?><td></td><?php endif; ?>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
function toggleCatGroup(id) {
    const el    = document.getElementById(id);
    const chev  = document.getElementById('chev_' + id);
    const isOpen = el.style.display !== 'none';
    if (isOpen) {
        el.style.display = 'none';
        if (chev) chev.style.transform = 'rotate(0deg)';
    } else {
        el.style.display = 'grid';
        if (chev) chev.style.transform = 'rotate(180deg)';
    }
    if (window.lucide) window.lucide.createIcons();
}

function toggleDetail(rowId) {
    const row  = document.getElementById(rowId);
    const chev = document.getElementById('chev-' + rowId);
    if (!row) return;
    const isOpen = row.style.display !== 'none';
    row.style.display = isOpen ? 'none' : 'table-row';
    if (chev) chev.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(90deg)';
}

// Türkçe sayı formatını parse eder: "2.000,50" → 2000.5 | "2000" → 2000 | "1.500" → 1500
function parseTRNum(str) {
    if (!str) return 0;
    str = str.trim();
    // Hem nokta hem virgül varsa: nokta = binlik, virgül = ondalık
    if (str.includes('.') && str.includes(',')) {
        str = str.replace(/\./g, '').replace(',', '.');
    } else if (str.includes(',')) {
        // Sadece virgül varsa: ondalık ayıracı
        str = str.replace(',', '.');
    } else if (str.includes('.')) {
        // Sadece nokta varsa: binlik ayıraç olarak kabul et (2.000 → 2000)
        // Eğer noktadan sonra tam 3 rakam varsa binlik; değilse ondalık
        const afterDot = str.split('.').slice(1).join('');
        if (afterDot.length === 3 || str.split('.').length > 2) {
            str = str.replace(/\./g, ''); // binlik nokta
        }
        // yoksa olduğu gibi bırak (zaten parseFloat halleder)
    }
    return parseFloat(str) || 0;
}

function setupSumInputs() {
    const modal = document.getElementById('entryModal');
    if (!modal) return;

    modal.querySelectorAll('input[type="text"][inputmode="decimal"]').forEach(input => {
        if (input._sumSetup) return;
        input._sumSetup = true;

        // Önizleme elementi oluştur
        const preview = document.createElement('small');
        preview.style.cssText = 'color:var(--accent);display:none;margin-top:3px;font-size:11px;';
        input.after(preview);

        // Açıklama alanı göster/gizle
        const noteField = input.parentElement ? input.parentElement.querySelector('.expense-note-field') : null;
        if (noteField) {
            input.addEventListener('input', () => {
                noteField.style.display = input.value.trim() ? 'block' : 'none';
            });
        }

        input.addEventListener('input', () => {
            const raw = input.value;
            if (!raw.includes('+')) { preview.style.display = 'none'; return; }

            const parts = raw.split('+').map(p => parseTRNum(p.trim()));
            const sum = parts.reduce((a, b) => a + b, 0);
            preview.textContent = '= ' + sum.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ₺';
            preview.style.display = 'block';
        });

        input.addEventListener('blur', () => {
            const raw = input.value;
            if (!raw.includes('+')) return;

            const parts = raw.split('+').map(p => parseTRNum(p.trim()));
            const sum = parts.reduce((a, b) => a + b, 0);
            if (sum > 0) {
                // useGrouping:false → "2356,04" (binlik nokta YOK) — PHP str_replace(',','.') güvenli okur
                input.value = sum.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2, useGrouping: false});
            } else {
                input.value = '';
            }
            preview.style.display = 'none';
        });
    });
}

// Modal açıldığında ve sayfa yüklendiğinde kur
document.addEventListener('DOMContentLoaded', setupSumInputs);
const _origOpenModal = window.openModal;
window.openModal = function(id) { if (_origOpenModal) _origOpenModal(id); setTimeout(setupSumInputs, 50); };

// Özet satır hover efekti
document.querySelectorAll('.entry-summary-row').forEach(row => {
    row.addEventListener('mouseenter', () => row.style.background = 'var(--bg-hover)');
    row.addEventListener('mouseleave', () => row.style.background = '');
});
</script>
