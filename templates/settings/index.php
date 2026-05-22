<style>
.settings-grid { display: grid; grid-template-columns: 1fr; gap: 32px; }
@media (min-width: 1024px) { .settings-grid { grid-template-columns: 1fr 1fr; } }
</style>

<div class="settings-grid">
    <!-- ORTAKLAR YÖNETİMİ -->
    <div class="card" style="background: var(--bg-surface); border: 1px solid var(--border);">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid var(--border);">
            <h2 class="section-title" style="margin: 0;"><i data-lucide="users"></i> Ortaklar</h2>
            <div style="display: flex; gap: 8px;">
                <button class="btn btn-ghost btn-sm" onclick="toggleShareDistribution()"><i data-lucide="pie-chart"></i> Pay Dağıtımı</button>
                <button class="btn btn-primary btn-sm" onclick="openPartnerModal()"><i data-lucide="plus"></i> Yeni Ortak</button>
            </div>
        </div>

        <!-- Pay Dağıtımı Arayüzü (Gizli başlar) -->
        <div id="share-dist-section" style="display: none; padding: 20px; background: var(--bg-hover); border-bottom: 1px solid var(--border);">
            <h3 style="font-size: 14px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="info" style="width:16px; color: var(--accent);"></i> Otomatik Pay Ayarlama
                <small style="font-weight: normal; color: var(--text-muted); font-size: 11px;">(Bir oranı değiştirdiğinizde diğerleri otomatik dengelenir)</small>
            </h3>
            <form method="POST" action="?page=settings&action=bulkSaveShares" id="bulk-share-form">
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php 
                    $activePartners = array_filter($partners, function($p) { return $p['is_active']; });
                    foreach ($activePartners as $p): 
                    ?>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="flex: 1; font-size: 13px; font-weight: 600;">
                            <?= htmlspecialchars($p['name']) ?>
                            <?php if($p['is_cash_reserve']): ?><small style="color: var(--accent);">[KASA]</small><?php endif; ?>
                        </span>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="number" 
                                   name="shares[<?= $p['id'] ?>]" 
                                   class="select-input share-input" 
                                   step="0.01" 
                                   value="<?= number_format($p['profit_share'] * 100, 2, '.', '') ?>" 
                                   style="width: 80px; text-align: right;" 
                                   data-id="<?= $p['id'] ?>"
                                   onfocus="this.dataset.prev = this.value"
                                   onchange="adjustShares(this)">
                            <span style="color: var(--text-muted);">%</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border); display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 13px; font-weight: 700;">TOPLAM: <span id="total-share-display">100.00</span>%</span>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleShareDistribution()">Vazgeç</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="save-shares-btn">Payları Kaydet</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>İsim</th>
                        <th class="text-right">Pay (%)</th>
                        <th class="text-right">Durum</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalShare = 0;
                    foreach ($partners as $p): 
                        if ($p['is_active']) $totalShare += (float)$p['profit_share'];
                    ?>
                    <tr>
                        <td style="font-weight: 600;">
                            <?= htmlspecialchars($p['name']) ?>
                            <?php if($p['is_cash_reserve']): ?><span class="badge" style="background:var(--accent-dim);color:var(--accent);font-size:11px;margin-left:4px;">KASA</span><?php endif; ?>
                        </td>
                        <td class="text-right text-success" style="font-weight: 600;"><?= Calculator::percent($p['profit_share'] * 100) ?></td>
                        <td class="text-right"><?= $p['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-error">Pasif</span>' ?></td>
                        <td class="text-right">
                            <button class="btn btn-ghost btn-sm" onclick='editPartner(<?= json_encode($p) ?>)'><i data-lucide="edit-2"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if (abs($totalShare - 1) > 0.001): ?>
                <tfoot>
                    <tr>
                        <td colspan="4" style="background: var(--danger-dim); color: var(--danger); font-size: 12px; text-align: center; padding: 8px;">
                            <i data-lucide="alert-triangle" style="width:14px; vertical-align: middle;"></i> 
                            Dikkat: Aktif ortakların toplam payı <b><?= Calculator::percent($totalShare * 100) ?></b>. %100 olmalıdır!
                        </td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- KULLANICI YÖNETİMİ -->
    <div class="card" style="background: var(--bg-surface); border: 1px solid var(--border);">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid var(--border);">
            <h2 class="section-title" style="margin: 0;"><i data-lucide="shield"></i> Kullanıcılar</h2>
            <button class="btn btn-primary btn-sm" onclick="openUserModal()"><i data-lucide="plus"></i> Yeni Kullanıcı</button>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ad Soyad</th>
                        <th>Kullanıcı Adı</th>
                        <th>Yetki</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="font-weight: 600;"><?= htmlspecialchars($u['full_name']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($u['username']) ?></td>
                        <td>
                            <?php if($u['role'] === 'admin'): ?>
                                <span class="badge" style="background:var(--danger-dim);color:var(--danger);">Yönetici</span>
                            <?php else: ?>
                                <span class="badge" style="background:var(--success-dim);color:var(--success);">Görüntüleyici</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <button class="btn btn-ghost btn-sm" onclick='editUser(<?= json_encode($u) ?>)'><i data-lucide="edit-2"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- GİDER KATEGORİLERİ -->
    <div class="card" style="background: var(--bg-surface); border: 1px solid var(--border); grid-column: 1 / -1;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid var(--border);">
            <h2 class="section-title" style="margin: 0;"><i data-lucide="tags"></i> Gider Kategorileri</h2>
            <button class="btn btn-primary btn-sm" onclick="openCategoryModal()"><i data-lucide="plus"></i> Yeni Kategori</button>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Sıra</th>
                        <th>Kategori Adı</th>
                        <th>Tür</th>
                        <th>Sistem Kodu</th>
                        <th class="text-right">Durum</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                    <?php $isParent = !$c['parent_id']; ?>
                    <tr style="<?= $isParent ? '' : 'background:rgba(30,41,59,0.3);' ?>">
                        <td class="text-muted"><?= $c['sort_order'] ?></td>
                        <td style="font-weight: <?= $isParent ? '700' : '400' ?>; padding-left: <?= $isParent ? '12px' : '28px' ?>;">
                            <?php if (!$isParent): ?>
                            <span style="color:var(--text-muted);margin-right:4px;">└</span>
                            <?php endif; ?>
                            <?= htmlspecialchars($c['name']) ?>
                        </td>
                        <td>
                            <?php if ($isParent): ?>
                            <span class="badge" style="background:rgba(99,102,241,0.15);color:#818cf8;font-size:10px;">Ana Kategori</span>
                            <?php else: ?>
                            <span class="badge" style="background:rgba(14,165,233,0.12);color:#0ea5e9;font-size:10px;">Alt Kategori</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($c['slug']) ?></td>
                        <td class="text-right"><?= $c['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-error">Pasif</span>' ?></td>
                        <td class="text-right">
                            <button class="btn btn-ghost btn-sm" onclick='editCategory(<?= json_encode($c) ?>)'><i data-lucide="edit-2"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- ORTAK MODALI -->
<div class="modal-overlay" id="partnerModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="partnerModalTitle"><i data-lucide="user"></i> Ortak Ekle/Düzenle</h3>
            <button type="button" class="modal-close" onclick="closeModal('partnerModal')"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" action="?page=settings&action=savePartner">
            <input type="hidden" name="id" id="partner_id" value="0">
            <div class="modal-body" style="display: flex; flex-direction: column; gap: 16px;">
                <div class="form-group" style="margin:0">
                    <label>Ortak Adı</label>
                    <input type="text" name="name" id="partner_name" class="select-input" style="width:100%" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label>Kar Payı (%) (Örn: 47 veya 0,47)</label>
                    <input type="text" inputmode="decimal" name="profit_share" id="partner_share" class="select-input" style="width:100%" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label>Sıralama</label>
                    <input type="number" name="sort_order" id="partner_sort" class="select-input" style="width:100%" value="0">
                </div>
                <div style="display: flex; gap: 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_cash_reserve" id="partner_cash" value="1"> Kasa Hesabı mı?
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_active" id="partner_active" value="1" checked> Aktif
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('partnerModal')">İptal</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- KULLANICI MODALI -->
<div class="modal-overlay" id="userModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="userModalTitle"><i data-lucide="shield"></i> Kullanıcı Ekle/Düzenle</h3>
            <button type="button" class="modal-close" onclick="closeModal('userModal')"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" action="?page=settings&action=saveUser">
            <input type="hidden" name="id" id="user_id" value="0">
            <div class="modal-body" style="display: flex; flex-direction: column; gap: 16px;">
                <div class="form-group" style="margin:0">
                    <label>Ad Soyad</label>
                    <input type="text" name="full_name" id="user_fullname" class="select-input" style="width:100%" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label>Kullanıcı Adı</label>
                    <input type="text" name="username" id="user_username" class="select-input" style="width:100%" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label>Parola <small>(Boş bırakılırsa değişmez)</small></label>
                    <input type="password" name="password" id="user_password" class="select-input" style="width:100%">
                </div>
                <div class="form-group" style="margin:0">
                    <label>Yetki</label>
                    <select name="role" id="user_role" class="select-input" style="width:100%">
                        <option value="viewer">Görüntüleyici (Sadece Okur)</option>
                        <option value="admin">Yönetici (Tam Yetki)</option>
                    </select>
                </div>
                <div style="display: flex; gap: 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_active" id="user_active" value="1" checked> Aktif (Giriş Yapabilir)
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('userModal')">İptal</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- KATEGORİ MODALI -->
<div class="modal-overlay" id="categoryModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="categoryModalTitle"><i data-lucide="tag"></i> Kategori Ekle/Düzenle</h3>
            <button type="button" class="modal-close" onclick="closeModal('categoryModal')"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" action="?page=settings&action=saveCategory">
            <input type="hidden" name="id" id="cat_id" value="0">
            <div class="modal-body" style="display: flex; flex-direction: column; gap: 16px;">
                <div class="form-group" style="margin:0">
                    <label>Kategori Adı</label>
                    <input type="text" name="name" id="cat_name" class="select-input" style="width:100%" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label>Ana Kategori <span style="color:var(--text-muted);font-weight:400;">(boş bırakılırsa ana kategori olur)</span></label>
                    <select name="parent_id" id="cat_parent" class="select-input" style="width:100%">
                        <option value="">— Ana Kategori (üst düzey) —</option>
                        <?php foreach ($parentCategories as $pc): ?>
                        <option value="<?= $pc['id'] ?>"><?= htmlspecialchars($pc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label>Sıralama</label>
                    <input type="number" name="sort_order" id="cat_sort" class="select-input" style="width:100%" value="0">
                </div>
                <div style="display: flex; gap: 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_active" id="cat_active" value="1" checked> Aktif
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('categoryModal')">İptal</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleShareDistribution() {
    const section = document.getElementById('share-dist-section');
    section.style.display = section.style.display === 'none' ? 'block' : 'none';
    if (section.style.display === 'block') {
        updateTotalDisplay();
    }
}

function adjustShares(changedInput) {
    const inputs = Array.from(document.querySelectorAll('.share-input'));
    const changedId = changedInput.dataset.id;
    const newValue = parseFloat(changedInput.value) || 0;
    const prevValue = parseFloat(changedInput.dataset.prev) || 0;
    const diff = newValue - prevValue;

    if (diff === 0) return;

    // Diğer aktif inputları bul
    const otherInputs = inputs.filter(input => input.dataset.id !== changedId);
    
    if (otherInputs.length === 0) {
        changedInput.value = (100).toFixed(2);
        updateTotalDisplay();
        return;
    }

    // Farkı diğerlerine orantılı olarak dağıt
    const currentOthersSum = otherInputs.reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
    
    if (currentOthersSum === 0) {
        // Eğer diğerleri 0 ise, farkı eşit dağıt
        const part = diff / otherInputs.length;
        otherInputs.forEach(input => {
            let val = (parseFloat(input.value) || 0) - part;
            input.value = Math.max(0, val).toFixed(2);
        });
    } else {
        // Orantılı dağıt
        otherInputs.forEach(input => {
            const currentVal = parseFloat(input.value) || 0;
            const weight = currentVal / currentOthersSum;
            let val = currentVal - (diff * weight);
            input.value = Math.max(0, val).toFixed(2);
        });
    }

    // Hassas ayar
    fixRounding();
    updateTotalDisplay();
    
    // Güncel değerleri "önceki" olarak kaydet
    inputs.forEach(input => input.dataset.prev = input.value);
}

function fixRounding() {
    const inputs = Array.from(document.querySelectorAll('.share-input'));
    let sum = inputs.reduce((s, i) => s + parseFloat(i.value), 0);
    let diff = 100 - sum;
    
    if (Math.abs(diff) > 0.0001) {
        // En büyük değerli inputu bul
        const target = inputs.reduce((prev, current) => (parseFloat(prev.value) > parseFloat(current.value)) ? prev : current);
        target.value = (parseFloat(target.value) + diff).toFixed(2);
    }
}

function updateTotalDisplay() {
    const inputs = document.querySelectorAll('.share-input');
    let sum = 0;
    inputs.forEach(input => sum += parseFloat(input.value) || 0);
    document.getElementById('total-share-display').innerText = sum.toFixed(2);
    
    const saveBtn = document.getElementById('save-shares-btn');
    if (Math.abs(sum - 100) > 0.05) { // 0.05 tolerance for rounding
        saveBtn.disabled = true;
        document.getElementById('total-share-display').style.color = 'var(--danger)';
    } else {
        saveBtn.disabled = false;
        document.getElementById('total-share-display').style.color = 'var(--success)';
    }
}

function openPartnerModal() {
    document.getElementById('partner_id').value = 0;
    document.getElementById('partner_name').value = '';
    document.getElementById('partner_share').value = '';
    document.getElementById('partner_sort').value = '0';
    document.getElementById('partner_cash').checked = false;
    document.getElementById('partner_active').checked = true;
    openModal('partnerModal');
}

function editPartner(p) {
    document.getElementById('partner_id').value = p.id;
    document.getElementById('partner_name').value = p.name;
    document.getElementById('partner_share').value = p.profit_share * 100;
    document.getElementById('partner_sort').value = p.sort_order;
    document.getElementById('partner_cash').checked = p.is_cash_reserve == 1;
    document.getElementById('partner_active').checked = p.is_active == 1;
    openModal('partnerModal');
}

function openUserModal() {
    document.getElementById('user_id').value = 0;
    document.getElementById('user_fullname').value = '';
    document.getElementById('user_username').value = '';
    document.getElementById('user_password').value = '';
    document.getElementById('user_role').value = 'viewer';
    document.getElementById('user_active').checked = true;
    openModal('userModal');
}

function editUser(u) {
    document.getElementById('user_id').value = u.id;
    document.getElementById('user_fullname').value = u.full_name;
    document.getElementById('user_username').value = u.username;
    document.getElementById('user_password').value = '';
    document.getElementById('user_role').value = u.role;
    document.getElementById('user_active').checked = u.is_active == 1;
    openModal('userModal');
}

function openCategoryModal() {
    document.getElementById('cat_id').value = 0;
    document.getElementById('cat_name').value = '';
    document.getElementById('cat_parent').value = '';
    document.getElementById('cat_sort').value = '0';
    document.getElementById('cat_active').checked = true;
    openModal('categoryModal');
}

function editCategory(c) {
    document.getElementById('cat_id').value = c.id;
    document.getElementById('cat_name').value = c.name;
    document.getElementById('cat_parent').value = c.parent_id || '';
    document.getElementById('cat_sort').value = c.sort_order;
    document.getElementById('cat_active').checked = c.is_active == 1;
    openModal('categoryModal');
}
</script>
