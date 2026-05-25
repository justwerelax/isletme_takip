<?php
$monthNames = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

// Kategorileri JS için hazırla
$catJson = json_encode(array_values($categories), JSON_UNESCAPED_UNICODE);

// Varsayılan kategori: "genel" veya ilki
$defaultCatId = 0;
foreach ($categories as $c) {
    if (stripos($c['name'], 'genel') !== false || stripos($c['name'], 'diğer') !== false) {
        $defaultCatId = $c['id']; break;
    }
}
if (!$defaultCatId && !empty($categories)) $defaultCatId = $categories[0]['id'];
?>

<style>
.qi-card { padding:24px; background:var(--bg-surface); border-radius:var(--radius-lg); border:1px solid var(--border); }
.qi-step { display:flex; align-items:center; gap:10px; margin-bottom:20px; }
.qi-step-num { width:26px; height:26px; background:var(--accent); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; }
.qi-step-label { font-size:14px; font-weight:600; color:var(--text-main); }

#wpText {
    width:100%; min-height:160px; resize:vertical;
    background:var(--bg-hover); color:var(--text-main);
    border:1px solid var(--border); border-radius:var(--radius-md);
    padding:14px; font-size:13px; font-family:inherit;
    line-height:1.7;
}
#wpText::placeholder { color:var(--text-muted); }

#parseBtn { margin-top:10px; }

/* Preview table */
#previewSection { display:none; margin-top:28px; }
.preview-table { width:100%; border-collapse:collapse; font-size:13px; }
.preview-table th { padding:10px 12px; background:var(--bg-hover); color:var(--text-muted); font-size:11px; text-transform:uppercase; letter-spacing:.5px; text-align:left; border-bottom:2px solid var(--border); }
.preview-table td { padding:9px 10px; border-bottom:1px solid var(--border); vertical-align:middle; }
.preview-table tr:hover td { background:rgba(14,165,233,0.04); }
.preview-table tr.excluded td { opacity:.4; }

.qi-amount-input, .qi-notes-input {
    background:var(--bg-hover); color:var(--text-main);
    border:1px solid var(--border); border-radius:var(--radius-sm);
    padding:6px 10px; font-size:13px; font-family:inherit;
}
.qi-amount-input { width:110px; text-align:right; }
.qi-notes-input  { width:100%; }
.qi-cat-select {
    background:var(--bg-hover); color:var(--text-main);
    border:1px solid var(--border); border-radius:var(--radius-sm);
    padding:6px 8px; font-size:12px; width:100%;
}

.qi-exclude-btn {
    width:28px; height:28px; border-radius:50%;
    border:1px solid var(--border); background:transparent;
    color:var(--text-muted); cursor:pointer; font-size:15px; line-height:1;
    display:flex; align-items:center; justify-content:center;
}
.qi-exclude-btn:hover { background:rgba(239,68,68,.15); color:var(--danger); border-color:var(--danger); }
.qi-exclude-btn.excluded { background:rgba(239,68,68,.12); color:var(--danger); }

.qi-summary-bar {
    display:flex; align-items:center; gap:20px; margin-top:16px;
    padding:14px 18px;
    background:rgba(14,165,233,.07);
    border:1px solid rgba(14,165,233,.2);
    border-radius:var(--radius-md);
}
.qi-summary-bar .total-label { font-size:12px; color:var(--text-muted); }
.qi-summary-bar .total-val   { font-size:18px; font-weight:800; color:var(--accent); }
.qi-summary-bar .count-val   { font-size:13px; color:var(--text-muted); }
</style>

<div class="qi-card">
    <!-- Başlık -->
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
        <i data-lucide="zap" style="width:22px;height:22px;color:var(--accent);"></i>
        <h2 style="margin:0; font-size:18px;">Hızlı Gider Girişi</h2>
        <span style="font-size:12px; color:var(--text-muted); background:var(--bg-hover); padding:3px 10px; border-radius:20px;">WhatsApp'tan yapıştır → kaydet</span>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px;">

        <!-- ADIM 1: Tarih seç -->
        <div>
            <div class="qi-step">
                <div class="qi-step-num">1</div>
                <div class="qi-step-label">Gün seç</div>
            </div>
            <select id="entrySelect" class="select-input" style="width:100%;" onchange="updateSaveEntryId()">
                <option value="">— Tarih seçin —</option>
                <?php
                $lastMonth = '';
                foreach ($entries as $e):
                    $mKey = $e['year'] . '-' . str_pad($e['month'], 2, '0', STR_PAD_LEFT);
                    if ($mKey !== $lastMonth):
                        if ($lastMonth !== '') echo '</optgroup>';
                        echo '<optgroup label="' . $monthNames[(int)$e['month']] . ' ' . $e['year'] . '">';
                        $lastMonth = $mKey;
                    endif;
                ?>
                <option value="<?= $e['id'] ?>">
                    <?= date('d.m.Y (l)', strtotime($e['entry_date'])) ?>
                </option>
                <?php endforeach; ?>
                <?php if ($lastMonth !== '') echo '</optgroup>'; ?>
            </select>
            <small style="color:var(--text-muted); font-size:11px; display:block; margin-top:5px;">Sadece kilitli olmayan aylar listelenir.</small>
        </div>

        <!-- ADIM 2: Varsayılan kategori -->
        <div>
            <div class="qi-step">
                <div class="qi-step-num">2</div>
                <div class="qi-step-label">Varsayılan kategori</div>
            </div>
            <select id="defaultCat" class="select-input" style="width:100%;" onchange="applyDefaultCatToAll()">
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id'] == $defaultCatId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <small style="color:var(--text-muted); font-size:11px; display:block; margin-top:5px;">Her satırda ayrıca değiştirebilirsin.</small>
        </div>
    </div>

    <!-- ADIM 3: Metin yapıştır -->
    <div class="qi-step">
        <div class="qi-step-num">3</div>
        <div class="qi-step-label">WhatsApp mesajlarını yapıştır</div>
    </div>

    <textarea id="wpText" placeholder="Örnek:
200 Alex
725 yemek
400 pazar Buğra avans
2500 kart mangal yazılmamış
4000 mazot
316,40 komisyon
10000 babama"></textarea>

    <div style="display:flex; gap:10px; margin-top:10px;">
        <button id="parseBtn" class="btn btn-primary" onclick="parseText()">
            <i data-lucide="scan-text"></i> Parse Et &amp; Önizle
        </button>
        <button class="btn btn-ghost btn-sm" onclick="document.getElementById('wpText').value=''; clearPreview();" style="color:var(--text-muted);">
            <i data-lucide="x"></i> Temizle
        </button>
    </div>

    <!-- ÖNIZLEME -->
    <div id="previewSection">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px; padding-top:4px; border-top:1px solid var(--border);">
            <h3 style="margin:0; font-size:14px;">Önizleme</h3>
            <button class="btn btn-ghost btn-sm" onclick="addRow()" style="font-size:12px;">
                <i data-lucide="plus"></i> Satır Ekle
            </button>
        </div>

        <div style="overflow-x:auto;">
            <table class="preview-table" id="previewTable">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th style="width:130px;">Tutar (₺)</th>
                        <th>Açıklama / Not</th>
                        <th style="width:200px;">Kategori</th>
                        <th style="width:36px;"></th>
                    </tr>
                </thead>
                <tbody id="previewBody"></tbody>
            </table>
        </div>

        <!-- Özet -->
        <div class="qi-summary-bar">
            <div>
                <div class="total-label">Toplam</div>
                <div class="total-val" id="totalVal">₺0</div>
            </div>
            <div class="count-val" id="countVal">0 kalem</div>
            <div style="margin-left:auto; display:flex; gap:10px; align-items:center;">
                <form id="saveForm" method="POST" action="?page=quick_import&action=save" onsubmit="return buildAndSubmit(event)">
                    <input type="hidden" name="entry_id" id="saveEntryId" value="">
                    <div id="saveItemsContainer"></div>
                    <button type="submit" class="btn btn-primary" id="saveBtn" disabled>
                        <i data-lucide="save"></i> Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const CATS = <?= $catJson ?>;
const DEFAULT_CAT_ID = <?= $defaultCatId ?>;

function catOptions(selectedId) {
    return CATS.map(c =>
        `<option value="${c.id}" ${c.id == selectedId ? 'selected' : ''}>${c.name}</option>`
    ).join('');
}

let rowCounter = 0;

function makeRow(amount, notes, catId) {
    catId = catId || parseInt(document.getElementById('defaultCat').value) || DEFAULT_CAT_ID;
    const id = ++rowCounter;
    return `<tr id="row-${id}" data-excluded="0">
        <td style="text-align:center; color:var(--text-muted); font-size:11px;">${id}</td>
        <td><input type="text" class="qi-amount-input" value="${amount}" oninput="recalc()" inputmode="decimal"></td>
        <td><input type="text" class="qi-notes-input" value="${escHtml(notes)}" placeholder="açıklama…"></td>
        <td><select class="qi-cat-select">${catOptions(catId)}</select></td>
        <td>
            <button type="button" class="qi-exclude-btn" title="Hariç tut / dahil et" onclick="toggleExclude(${id})">×</button>
        </td>
    </tr>`;
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function toggleExclude(id) {
    const tr  = document.getElementById('row-' + id);
    const btn = tr.querySelector('.qi-exclude-btn');
    const ex  = tr.dataset.excluded === '1';
    tr.dataset.excluded = ex ? '0' : '1';
    tr.classList.toggle('excluded', !ex);
    btn.classList.toggle('excluded', !ex);
    btn.title = ex ? 'Hariç tut' : 'Dahil et';
    recalc();
}

function parseTR(s) {
    s = String(s).trim().replace(/^[+]/, '');
    if (s.includes(',') && s.includes('.')) {
        s = s.replace(/\./g, '').replace(',', '.');
    } else if (s.includes(',')) {
        s = s.replace(',', '.');
    } else if (s.includes('.')) {
        const parts = s.split('.');
        const last  = parts[parts.length - 1];
        if (parts.length > 2 || last.length === 3) s = s.replace(/\./g, '');
    }
    return parseFloat(s) || 0;
}

function fmtMoney(n) {
    return '₺' + n.toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function parseText() {
    const raw = document.getElementById('wpText').value.trim();
    if (!raw) return;

    const lines = raw.split('\n');
    const parsed = [];

    lines.forEach(line => {
        line = line.trim();
        if (!line) return;

        // Satır başında sayı varsa: "2500 mazot" veya "+316,40 komisyon"
        const m = line.match(/^([+]?[\d.,]+)\s+(.+)$/);
        if (m) {
            const amount = parseTR(m[1]);
            const notes  = m[2].trim();
            if (amount > 0) parsed.push({amount, notes});
        }
        // Sadece sayıysa ekle
        else {
            const onlyNum = line.match(/^([+]?[\d.,]+)$/);
            if (onlyNum) {
                const amount = parseTR(onlyNum[1]);
                if (amount > 0) parsed.push({amount, notes: ''});
            }
        }
    });

    if (parsed.length === 0) {
        alert('Ayrıştırılabilecek satır bulunamadı.\n\nFormat: "2500 mazot" gibi sayı + açıklama olmalı.');
        return;
    }

    renderRows(parsed);
}

function renderRows(items) {
    const tbody = document.getElementById('previewBody');
    tbody.innerHTML = items.map(i => makeRow(i.amount, i.notes, 0)).join('');
    document.getElementById('previewSection').style.display = 'block';
    recalc();
    lucide.createIcons();
}

function addRow() {
    const tbody = document.getElementById('previewBody');
    tbody.insertAdjacentHTML('beforeend', makeRow('', '', 0));
    recalc();
    lucide.createIcons();
}

function clearPreview() {
    document.getElementById('previewSection').style.display = 'none';
    document.getElementById('previewBody').innerHTML = '';
    rowCounter = 0;
    recalc();
}

function recalc() {
    let total = 0, count = 0;
    document.querySelectorAll('#previewBody tr').forEach(tr => {
        if (tr.dataset.excluded === '1') return;
        const amt = parseTR(tr.querySelector('.qi-amount-input')?.value || '0');
        if (amt > 0) { total += amt; count++; }
    });
    document.getElementById('totalVal').textContent  = fmtMoney(total);
    document.getElementById('countVal').textContent  = count + ' kalem';
    const saveBtn = document.getElementById('saveBtn');
    if (saveBtn) saveBtn.disabled = (count === 0 || !document.getElementById('saveEntryId').value);
}

function applyDefaultCatToAll() {
    const catId = document.getElementById('defaultCat').value;
    document.querySelectorAll('#previewBody .qi-cat-select').forEach(sel => {
        sel.value = catId;
    });
}

function updateSaveEntryId() {
    const val = document.getElementById('entrySelect').value;
    document.getElementById('saveEntryId').value = val;
    recalc();
}

function buildAndSubmit(e) {
    e.preventDefault();
    const entryId = document.getElementById('saveEntryId').value;
    if (!entryId) { alert('Lütfen bir tarih seçin.'); return false; }

    const container = document.getElementById('saveItemsContainer');
    container.innerHTML = '';
    let idx = 0;

    document.querySelectorAll('#previewBody tr').forEach(tr => {
        if (tr.dataset.excluded === '1') return;
        const amount = parseTR(tr.querySelector('.qi-amount-input')?.value || '0');
        const catId  = tr.querySelector('.qi-cat-select')?.value || '';
        const notes  = tr.querySelector('.qi-notes-input')?.value?.trim() || '';
        if (amount <= 0 || !catId) return;

        const mkInput = (name, val) => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = name;
            inp.value = val;
            container.appendChild(inp);
        };
        mkInput(`items[${idx}][amount]`,      amount);
        mkInput(`items[${idx}][category_id]`, catId);
        mkInput(`items[${idx}][notes]`,       notes);
        idx++;
    });

    if (idx === 0) { alert('Kaydedilecek geçerli satır yok.'); return false; }
    document.getElementById('saveForm').submit();
}

// Lucide refresh after dynamic content
document.addEventListener('DOMContentLoaded', () => { lucide.createIcons(); });
</script>
