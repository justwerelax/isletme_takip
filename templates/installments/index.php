<div class="installment-page">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
        <h3 style="white-space: nowrap; display: flex; align-items: center; gap: 15px;">
            <i data-lucide="list-checks"></i> 
            Ödenecek <?= $currentPage === 'loans' ? 'Krediler' : 'Taksitler' ?> (Aylık Özet)
            <span class="badge" style="background: var(--bg-elevated); border: 1px solid var(--accent); color: var(--accent); font-size: 14px; padding: 5px 12px; border-radius: 8px;">
                Genel Toplam: <?= number_format($grandTotalUnpaid, 0, ',', '.') ?> ₺
            </span>
        </h3>
        <button class="btn btn-primary" onclick="document.getElementById('addInstallmentModal').classList.add('active')">
            <i data-lucide="plus"></i> Yeni <?= $currentPage === 'loans' ? 'Kredi' : 'Borç' ?> Ekle
        </button>
    </div>

    <!-- Tablo 1: Ödenecekler (Kart Görünümü) -->
    <div class="upcoming-months-container" style="margin-top: 20px;">
        <?php if (empty($upcomingMonths)): ?>
            <div class="empty-state">
                <i data-lucide="check-circle" style="width: 48px; height: 48px; color: var(--success); opacity: 0.5;"></i>
                <h3>Tebrikler!</h3>
                <p>Şu an için bekleyen herhangi bir taksitli borç ödemeniz bulunmuyor.</p>
            </div>
        <?php else: ?>
            <div class="month-cards-grid">
                <?php 
                $months_tr = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
                foreach ($upcomingMonths as $m): ?>
                    <div class="month-card">
                        <div class="month-card-header">
                            <div class="month-info">
                                <span class="year-label"><?= $m['year'] ?></span>
                                <h4 class="month-label"><?= $months_tr[$m['month']] ?></h4>
                            </div>
                            <div class="item-count"><?= $m['item_count'] ?> Kalem</div>
                        </div>
                        <div class="month-card-body">
                            <ul class="installment-items-list">
                                <?php foreach ($m['items'] as $item): ?>
                                    <li>
                                        <span class="item-name" style="display: flex; align-items: center; gap: 8px;">
                                            <button class="btn-icon-sm" onclick="openDetails(<?= $item['installment_id'] ?>)" title="Yönet" style="padding: 4px; background: var(--bg-elevated); border: 1px solid var(--border); border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;">
                                                <i data-lucide="settings" style="width: 12px; height: 12px; color: var(--accent);"></i>
                                            </button>
                                            <?= htmlspecialchars($item['title']) ?> 
                                            <span style="font-size: 10px; opacity: 0.6; margin-left: 4px;">
                                                <?php if($item['installment_number'] <= $item['total_installments']): ?>
                                                    <?= $item['installment_number'] ?>/<?= $item['total_installments'] ?>
                                                <?php endif; ?>
                                            </span>
                                        </span>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span class="item-val"><?= number_format($item['amount'], 0, ',', '.') ?> ₺</span>
                                            <?php if ($currentPage === 'loans'): ?>
                                                <button type="button" class="btn-icon-sm" title="Ödendi İşaretle"
                                                    style="background: var(--bg-elevated); border: 1px solid var(--border); border-radius: 6px; padding: 4px; color: var(--success); display: flex; align-items: center; justify-content: center; cursor:pointer;"
                                                    onclick="openLoanPayModal(<?= $item['id'] ?>, '<?= htmlspecialchars($item['title']) ?>', <?= $item['amount'] ?>)">
                                                    <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="month-card-footer">
                            <div class="total-row">
                                <span>Aylık Toplam:</span>
                                <strong><?= number_format($m['total_amount'], 0, ',', '.') ?> ₺</strong>
                            </div>
                            <?php if ($currentPage !== 'loans'): ?>
                                <a href="?page=<?= $currentPage ?>&action=payMonth&year=<?= $m['year'] ?>&month=<?= $m['month'] ?>" 
                                   class="btn btn-success btn-block" 
                                   onclick="return confirm('<?= $months_tr[$m['month']] ?> ayındaki TÜM ödemelerin yapıldığını onaylıyor musunuz?')">
                                    <i data-lucide="check-check"></i> Ödendi Olarak İşaretle
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="archive-section" style="width: fit-content; min-width: 300px;">
        <div class="table-header" style="margin-top: 50px; display: flex; justify-content: space-between; align-items: center; gap: 20px;">
            <h3 style="white-space: nowrap;"><i data-lucide="history"></i> <?= $currentPage === 'loans' ? 'Kredi' : 'Taksit' ?> Arşivi</h3>
            <button class="btn btn-outline-primary btn-sm" id="toggleArchiveBtn" onclick="toggleFullArchive()" style="white-space: nowrap;">
                <i data-lucide="maximize-2"></i> Tüm Geçmişi Göster
            </button>
        </div>

        <!-- Tablo 2: Genel Arşiv Grid -->
        <div class="table-card" style="margin-top: 20px;">
            <div class="table-wrapper">
                <table class="data-table installment-grid archive-grid" id="mainArchiveGrid">
                    <thead>
                        <tr>
                            <th rowspan="2" class="archive-first-col">TAKSİT GERİ ÖDEME</th>
                            <?php 
                            $totalCols = count($archiveHeader);
                            foreach ($archiveHeader as $idx => $m): 
                                $isOld = ($idx < $totalCols - 3);
                            ?>
                                <th class="text-center archive-col <?= $isOld ? 'old-month hidden' : '' ?>">
                                    <div style="font-size: 10px; opacity: 0.6; display: flex; justify-content: center; align-items: center; gap: 4px;">
                                        <?= $m['year'] ?>
                                        <a href="?page=<?= $currentPage ?>&action=unpayMonth&year=<?= $m['year'] ?>&month=<?= $m['month'] ?>" 
                                           onclick="return confirm('<?= $months_tr[$m['month']] ?> ödemelerini geri almak istediğinize emin misiniz?')"
                                           style="color: var(--danger); opacity: 0.5; transition: opacity 0.3s;"
                                           onmouseover="this.style.opacity=1"
                                           onmouseout="this.style.opacity=0.5"
                                           title="Ödemeyi Geri Al">
                                            <i data-lucide="rotate-ccw" style="width: 10px; height: 10px;"></i>
                                        </a>
                                    </div>
                                    <div><?= $months_tr[$m['month']] ?></div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                        <tr class="totals-row">
                            <?php foreach ($archiveHeader as $idx => $m): 
                                $isOld = ($idx < $totalCols - 3);
                                $key = "{$m['year']}-{$m['month']}";
                                $paidTotal = 0;
                                foreach($installments as $inst) {
                                    $p = $grid[$inst['id']][$key] ?? null;
                                    if ($p && $p['is_paid']) $paidTotal += $p['amount'];
                                }
                            ?>
                                <th class="text-center text-success archive-col <?= $isOld ? 'old-month hidden' : '' ?>">
                                    <?= number_format($paidTotal, 0, ',', '.') ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($installments as $inst): 
                            $isVisibleInMini = false;
                            foreach ($archiveHeader as $idx => $m) {
                                if ($idx >= $totalCols - 3) { 
                                    $key = "{$m['year']}-{$m['month']}";
                                    if (isset($grid[$inst['id']][$key]) && $grid[$inst['id']][$key]['is_paid']) {
                                        $isVisibleInMini = true; break;
                                    }
                                }
                            }
                        ?>
                            <tr class="<?= $inst['is_completed'] ? 'row-completed' : '' ?> <?= !$isVisibleInMini ? 'mini-hidden' : '' ?>">
                                <td class="item-title archive-first-col" style="background: var(--bg-surface);">
                                    <div class="title-wrap" style="display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <button class="btn-icon-sm" onclick="openDetails(<?= $inst['id'] ?>)" title="Yönet" style="background: var(--bg-elevated); border: 1px solid var(--border); border-radius: 4px; cursor: pointer; padding: 2px;">
                                                <i data-lucide="settings" style="width: 12px; height: 12px; color: var(--text-secondary);"></i>
                                            </button>
                                            <div style="font-weight: 600; font-size: 13px;"><?= htmlspecialchars($inst['title']) ?></div>
                                        </div>
                                        <?php if ($inst['is_completed']): ?>
                                            <span class="badge badge-success" style="font-size: 9px; padding: 2px 5px;">Bitti</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php foreach ($archiveHeader as $idx => $m): 
                                $isOld = ($idx < $totalCols - 3);
                                $key = "{$m['year']}-{$m['month']}";
                                $pay = $grid[$inst['id']][$key] ?? null;
                            ?>
                                <td class="text-center installment-cell archive-col <?= $isOld ? 'old-month hidden' : '' ?> <?= $pay ? ($pay['is_paid'] ? 'paid' : 'pending empty-archive') : 'empty' ?>">
                                    <?php if ($pay && $pay['is_paid']): ?>
                                        <span class="amount"><?= number_format($pay['amount'], 0, ',', '.') ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Taksit Detayları Modal -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal-box" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i data-lucide="settings"></i> <span id="detailsTitle">Taksit Yönetimi</span></h3>
            <button class="modal-close" onclick="document.getElementById('detailsModal').classList.remove('active')">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="modal-body" id="detailsBody" style="max-height: 400px; overflow-y: auto;">
            <!-- Dinamik içerik gelecek -->
        </div>
        <div class="modal-footer" style="display: flex; justify-content: space-between;">
            <button class="btn btn-danger btn-sm" id="deleteInstallmentBtn">
                <i data-lucide="trash-2"></i> Borcu Tamamen Sil
            </button>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('detailsModal').classList.remove('active')">Kapat</button>
        </div>
    </div>
</div>
</div>

<script>
async function openDetails(id) {
    const modal = document.getElementById('detailsModal');
    const body = document.getElementById('detailsBody');
    const delBtn = document.getElementById('deleteInstallmentBtn');
    
    delBtn.onclick = () => { if(confirm('Tüm borç kaydını silmek istediğinize emin misiniz?')) window.location.href = '?page=<?= $currentPage ?>&action=delete&id=' + id; };
    body.innerHTML = '<div style="padding: 20px;">Yükleniyor...</div>';
    modal.classList.add('active');
    
    try {
        const response = await fetch('?page=<?= $currentPage ?>&action=getDetails&id=' + id);
        const data = await response.json();
        
        let html = '';
        
        if (data.installment.payment_type === 'equal') {
            html += `
                <div style="background: var(--bg-elevated); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--border);">
                    <label style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: 5px;">Toplam Borç Tutarını Güncelle (Tüm taksitlere dağıtılır)</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" id="full_total_${id}" value="${data.installment.total_amount}" step="0.01" 
                               style="flex: 1; padding: 8px; background: var(--bg-surface); border: 1px solid var(--border); border-radius: 6px; color: var(--text-primary); font-weight: 700;">
                        <button class="btn btn-primary btn-sm" onclick="saveTotal(${id})">Hepsini Güncelle</button>
                    </div>
                </div>
            `;
        }
        
        html += '<table class="data-table" style="width: 100%; border: none;">';
        const months_tr = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
        
        const isLoan = (<?= json_encode($currentPage) ?> === 'loans');
        let foundUnpaid = false;
        data.payments.forEach(item => {
            const canPay = !foundUnpaid || item.is_paid;
            if (!item.is_paid) foundUnpaid = true;

            let actionCell = '';
            if (isLoan) {
                if (item.is_paid) {
                    // Kredi — ödendi: geri al (POST)
                    actionCell = `
                        <form method="POST" action="?page=loans&action=markLoanUnpaid" style="display:inline;">
                            <input type="hidden" name="payment_id" value="${item.id}">
                            <button type="submit" class="btn btn-sm btn-danger"
                                style="font-size:10px; padding:4px 10px;"
                                onclick="return confirm('Bu kredi ödemesini geri almak ve gideri silmek istiyor musunuz?')">
                                İptal Et
                            </button>
                        </form>`;
                } else {
                    // Kredi — ödenmedi: tarih seçerek öde
                    actionCell = `
                        <button class="btn btn-sm btn-success"
                            style="font-size:10px; padding:4px 10px; ${!canPay ? 'opacity:0.3;cursor:not-allowed;pointer-events:none;' : ''}"
                            ${!canPay ? 'disabled' : ''}
                            onclick="openLoanPayModal(${item.id}, '${data.installment.title.replace(/'/g,"\\'")} ${item.installment_number}/${data.installment.total_installments}', ${item.amount})">
                            Öde
                        </button>`;
                }
            } else {
                // Taksit — basit toggle
                actionCell = `
                    <a href="${canPay ? '?page=<?= $currentPage ?>&action=toggleSingle&id=' + item.id : '#'}"
                       class="btn btn-sm ${item.is_paid ? 'btn-danger' : 'btn-success'}"
                       style="font-size:10px; padding:4px 10px; ${!canPay ? 'opacity:0.3;cursor:not-allowed;pointer-events:none;' : ''}"
                       ${!canPay ? 'disabled' : ''}>
                        ${item.is_paid ? 'İptal Et' : 'Öde'}
                    </a>`;
            }

            html += `<tr>
                <td style="padding: 12px; border-bottom: 1px solid var(--border);">
                    <div style="font-size: 10px; opacity: 0.6;">${item.installment_number}/${data.installment.total_installments}</div>
                    <div style="font-weight: 600;">${months_tr[item.month]} ${item.year}</div>
                </td>
                <td style="padding: 12px; border-bottom: 1px solid var(--border);">
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <input type="number" step="0.01" value="${item.amount}" id="amt_${item.id}"
                               style="width: 80px; padding: 4px; background: var(--bg-elevated); border: 1px solid var(--border); border-radius: 4px; color: var(--text-primary); font-weight: 700;">
                        <button class="btn btn-sm" onclick="saveAmount(${item.id})" style="padding: 4px; background: var(--bg-elevated);"><i data-lucide="save" style="width: 12px; height: 12px;"></i></button>
                    </div>
                </td>
                <td style="padding: 12px; border-bottom: 1px solid var(--border); text-align: right;">
                    ${actionCell}
                </td>
            </tr>`;
        });
        html += '</table>';
        body.innerHTML = html;
        lucide.createIcons();
        document.getElementById('detailsTitle').innerText = data.installment.title;
    } catch(e) { 
        console.error(e);
        body.innerHTML = '<div style="padding: 20px; color: var(--danger);">Veriler yüklenirken bir hata oluştu.</div>'; 
    }
}

async function saveTotal(id) {
    const total = document.getElementById('full_total_' + id).value;
    const formData = new FormData();
    formData.append('id', id);
    formData.append('total_amount', total);

    try {
        const response = await fetch('?page=<?= $currentPage ?>&action=updateTotal', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            alert('Tüm taksitler yeni tutara göre güncellendi.');
            window.location.reload();
        }
    } catch(e) { alert('Hata oluştu.'); }
}

async function saveAmount(id) {
    const amt = document.getElementById('amt_' + id).value;
    const formData = new FormData();
    formData.append('id', id);
    formData.append('amount', amt);

    try {
        const response = await fetch('?page=<?= $currentPage ?>&action=updatePayment', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            alert('Tutar güncellendi.');
            window.location.reload();
        }
    } catch(e) { alert('Hata oluştu.'); }
}
</script>

<!-- Add Installment Modal -->
<div class="modal-overlay" id="addInstallmentModal">
    <div class="modal-box" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i data-lucide="plus-circle"></i> Yeni Taksitli Borç Ekle</h3>
            <button class="modal-close" onclick="closeAddModal()">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form action="?page=<?= $currentPage ?>&action=store" method="POST" id="addInstallmentForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Borç Açıklaması</label>
                    <input type="text" name="title" id="inst_title" placeholder="Örn: Araç Bakımı" required>
                    <small style="color: var(--text-muted);">Sistem sonuna otomatik olarak "/Taksit Sayısı" ekleyecektir.</small>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Taksit Sayısı</label>
                        <input type="number" name="total_installments" id="inst_count" min="1" value="1" onchange="generateVariableFields()" required>
                    </div>
                    <div class="form-group">
                        <label>Ödeme Tipi</label>
                        <select name="payment_type" id="payment_type" class="select-input" onchange="togglePaymentType()">
                            <option value="equal">Eşit Taksitli</option>
                            <option value="variable">Değişken Taksitli</option>
                        </select>
                    </div>
                </div>

                <!-- Eşit Taksit Alanı -->
                <div id="equal_amount_area" class="form-group">
                    <label>Toplam Tutar (₺)</label>
                    <input type="number" name="total_amount" id="total_amount" step="0.01" oninput="updateMonthlyHint()">
                    <div id="monthly_hint" style="font-size: 11px; color: var(--accent); margin-top: 5px; font-weight: 600;"></div>
                </div>

                <!-- Değişken Taksit Alanı (Gizli başlar) -->
                <div id="variable_amount_area" style="display: none; border: 1px solid var(--border); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <label style="margin-bottom: 10px; display: block; font-weight: 700;">Taksit Tutarlarını Girin</label>
                    <div id="variable_inputs_grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <!-- JS ile dolacak -->
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Başlangıç Yılı</label>
                        <select name="start_year" class="select-input">
                            <?php for($y = date('Y')-1; $y <= date('Y')+2; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Başlangıç Ayı</label>
                        <select name="start_month" id="start_month" class="select-input" onchange="generateVariableFields()">
                            <?php foreach($months_tr as $num => $name): if($num == 0) continue; ?>
                                <option value="<?= $num ?>" <?= $num == date('n') ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeAddModal()">İptal</button>
                <button type="submit" class="btn btn-primary">Borcu Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeAddModal() {
    document.getElementById('addInstallmentModal').classList.remove('active');
}

function togglePaymentType() {
    const type = document.getElementById('payment_type').value;
    const equalArea = document.getElementById('equal_amount_area');
    const variableArea = document.getElementById('variable_amount_area');
    
    if (type === 'equal') {
        equalArea.style.display = 'block';
        variableArea.style.display = 'none';
    } else {
        equalArea.style.display = 'none';
        variableArea.style.display = 'block';
        generateVariableFields();
    }
}

function updateMonthlyHint() {
    const total = parseFloat(document.getElementById('total_amount').value) || 0;
    const count = parseInt(document.getElementById('inst_count').value) || 1;
    const hint = document.getElementById('monthly_hint');
    
    if (total > 0 && count > 0) {
        const monthly = (total / count).toLocaleString('tr-TR', { minimumFractionDigits: 2 });
        hint.innerText = `Aylık Taksit: ${monthly} ₺`;
    } else {
        hint.innerText = '';
    }
}

function generateVariableFields() {
    const type = document.getElementById('payment_type').value;
    if (type !== 'variable') return;

    const count = parseInt(document.getElementById('inst_count').value) || 0;
    const container = document.getElementById('variable_inputs_grid');
    const startMonth = parseInt(document.getElementById('start_month').value);
    const months_tr = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    
    // Mevcut değerleri sakla
    const existingValues = Array.from(container.querySelectorAll('input')).map(input => input.value);
    
    container.innerHTML = '';
    let currentMonth = startMonth;
    
    for (let i = 1; i <= count; i++) {
        const monthName = months_tr[currentMonth];
        const div = document.createElement('div');
        div.className = 'form-group';
        const val = existingValues[i-1] || '';
        div.innerHTML = `
            <label style="font-size: 11px;">${i}. Taksit (${monthName})</label>
            <input type="number" name="amounts[]" step="0.01" placeholder="0.00" value="${val}" required>
        `;
        container.appendChild(div);
        
        currentMonth++;
        if (currentMonth > 12) currentMonth = 1;
    }
}
</script>

<style>
/* --- Month Cards --- */
.month-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}
.month-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 24px;
    display: flex;
    flex-direction: column;
    transition: all var(--transition);
}
.month-card:hover {
    transform: translateY(-4px);
    border-color: var(--border-hover);
    box-shadow: var(--shadow-lg);
}
.month-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}
.month-info { display: flex; flex-direction: column; }
.year-label { font-size: 11px; color: var(--text-muted); font-weight: 600; letter-spacing: 1px; }
.month-label { font-size: 20px; font-weight: 800; color: var(--accent); }
.item-count {
    background: var(--bg-elevated);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    color: var(--text-secondary);
}
.installment-items-list {
    list-style: none;
    margin-bottom: 24px;
    flex: 1;
}
.installment-items-list li {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
    font-size: 13px;
}
.item-name { color: var(--text-secondary); }
.item-val { font-weight: 600; }
.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-top: 12px;
}
.total-row span { font-size: 14px; color: var(--text-muted); }
.total-row strong { font-size: 18px; color: var(--text-primary); }

/* --- Grid styles --- */
.installment-grid {
    border-collapse: separate;
    border-spacing: 0;
}
.installment-grid th {
    padding: 12px 8px;
    background: var(--bg-elevated);
    border-bottom: 2px solid var(--border);
}
.installment-grid td {
    padding: 10px 8px;
    border-right: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
}
.archive-first-col {
    width: 110px;
    min-width: 110px;
    position: sticky;
    left: 0;
    background: var(--bg-elevated);
    z-index: 10;
    transition: all 0.3s ease;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 10px;
}
.full-archive .archive-first-col {
    width: 250px;
    min-width: 250px;
    white-space: normal;
    font-size: 13px;
}
.archive-col {
    width: 60px;
    min-width: 60px;
    transition: all 0.3s ease;
}
.full-archive .archive-col {
    width: 100px;
    min-width: 100px;
}
.installment-cell {
    font-family: 'Inter', monospace;
    font-size: 9px;
}
.full-archive .installment-cell {
    font-size: 12px;
}
.installment-grid td {
    padding: 4px 6px;
    border-right: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
}
.installment-cell.empty {
    background: rgba(255,255,255,0.02);
}
.installment-cell.paid {
    color: var(--success);
    font-weight: 600;
}
.archive-grid { opacity: 0.9; }
.archive-grid .item-title { opacity: 0.8; }
.archive-grid {
    width: auto !important;
    min-width: unset !important;
    table-layout: auto !important;
}
.old-month.hidden {
    display: none !important;
}
.archive-grid:not(.full-archive) tr.mini-hidden {
    display: none !important;
}
</style>

<script>
function toggleFullArchive() {
    const grid = document.getElementById('mainArchiveGrid');
    const cols = document.querySelectorAll('.old-month');
    const btn = document.getElementById('toggleArchiveBtn');
    const isHidden = cols[0].classList.contains('hidden');

    grid.classList.toggle('full-archive');

    cols.forEach(col => {
        if (isHidden) {
            col.classList.remove('hidden');
        } else {
            col.classList.add('hidden');
        }
    });

    if (isHidden) {
        btn.innerHTML = '<i data-lucide="minimize-2"></i> Geçmişi Gizle';
    } else {
        btn.innerHTML = '<i data-lucide="maximize-2"></i> Tüm Geçmişi Göster';
    }
    lucide.createIcons();
}

function togglePayment(id, cell) {
    fetch(`?page=installments&action=togglePayment&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
}

// ── Kredi Ödeme Modalı ────────────────────────────────────────────────────────
function openLoanPayModal(paymentId, title, amount) {
    document.getElementById('lpmPaymentId').value = paymentId;
    document.getElementById('lpmTitle').textContent = title;
    document.getElementById('lpmAmount').textContent = amount.toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺';
    // Varsayılan tarih: bugün
    document.getElementById('lpmDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('loanPayModal').classList.add('active');
}
</script>

<!-- Kredi Ödeme Tarih Seçim Modalı -->
<div class="modal-overlay" id="loanPayModal">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-header">
            <h3><i data-lucide="calendar-check"></i> Kredi Ödemesi — Gider Tarihi Seç</h3>
            <button class="modal-close" onclick="document.getElementById('loanPayModal').classList.remove('active')">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form method="POST" action="?page=loans&action=markLoanPaid">
            <div class="modal-body">
                <input type="hidden" name="payment_id" id="lpmPaymentId">
                <div style="background:var(--bg-elevated); border:1px solid var(--border); border-radius:8px; padding:14px; margin-bottom:16px;">
                    <div style="font-size:12px; color:var(--text-muted); margin-bottom:4px;">Ödeme Kalemi</div>
                    <div style="font-weight:700; font-size:14px;" id="lpmTitle"></div>
                    <div style="font-weight:700; font-size:18px; color:var(--danger); margin-top:4px;" id="lpmAmount"></div>
                </div>
                <div class="form-group">
                    <label style="font-size:13px; font-weight:600;">Gider hangi güne yazılsın?</label>
                    <small style="color:var(--text-muted); display:block; margin-bottom:8px;">Seçtiğiniz tarihte kayıtlı açık bir günlük giriş olmalıdır.</small>
                    <input type="date" name="target_date" id="lpmDate" class="select-input" style="width:100%;" required>
                </div>
            </div>
            <div class="modal-footer" style="justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('loanPayModal').classList.remove('active')">İptal</button>
                <button type="submit" class="btn btn-success">
                    <i data-lucide="check"></i> Ödendi — Gidere Ekle
                </button>
            </div>
        </form>
    </div>
</div>
