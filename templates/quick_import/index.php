<?php
$monthNames = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
$catJson    = json_encode(array_values($categories), JSON_UNESCAPED_UNICODE);

// Varsayılan kategori
$defaultCatId = 0;
foreach ($categories as $c) {
    if (stripos($c['name'], 'genel') !== false || stripos($c['name'], 'diğer') !== false) {
        $defaultCatId = $c['id']; break;
    }
}
if (!$defaultCatId && !empty($categories)) $defaultCatId = $categories[0]['id'];
?>
<style>
/* ─── Layout ─── */
.qi-wrap {
    display:flex; flex-direction:column;
    height:calc(100vh - 120px); /* kalan ekran yüksekliği */
    background:var(--bg-surface);
    border-radius:var(--radius-lg);
    border:1px solid var(--border);
    overflow:hidden;
}

/* ─── Header ─── */
.qi-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 18px;
    background:var(--bg-hover);
    border-bottom:1px solid var(--border);
    flex-shrink:0;
}
.qi-header-left { display:flex; align-items:center; gap:10px; }
.qi-avatar {
    width:38px; height:38px; border-radius:50%;
    background:linear-gradient(135deg,#25d366,#128c7e);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:18px;
}
.qi-header-title  { font-size:14px; font-weight:700; }
.qi-header-sub    { font-size:11px; color:var(--text-muted); margin-top:1px; }
.qi-badge {
    display:inline-flex; align-items:center; gap:4px;
    background:rgba(14,165,233,.15); color:var(--accent);
    font-size:11px; font-weight:600;
    padding:3px 10px; border-radius:20px; cursor:default;
}
#msgCount { font-weight:800; }

/* ─── Chat area ─── */
.qi-chat {
    flex:1; overflow-y:auto; overflow-x:hidden;
    padding:16px 14px;
    display:flex; flex-direction:column; gap:6px;
    background:var(--bg-surface);
    /* WhatsApp-esque subtle pattern */
    background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.03) 1px, transparent 0);
    background-size: 24px 24px;
}
.qi-chat:empty::after {
    content:'Henüz gider yok.\nAşağıdan yazmaya başla 👇';
    white-space:pre-line;
    display:block; text-align:center;
    color:var(--text-muted); font-size:13px;
    margin:auto; padding-top:60px;
}

/* Bubble */
.qi-bubble-wrap {
    display:flex; justify-content:flex-end; align-items:flex-end; gap:6px;
}
.qi-bubble {
    max-width:72%;
    background:#005c4b;
    color:#e9edef;
    border-radius:12px 12px 2px 12px;
    padding:8px 12px 6px;
    position:relative;
    word-break:break-word;
    box-shadow:0 1px 3px rgba(0,0,0,.3);
    cursor:default;
    transition:opacity .15s;
}
.qi-bubble:hover { opacity:.95; }
.qi-bubble.no-amount { background:#1e293b; }
.qi-bubble-text { font-size:14px; line-height:1.5; }
.qi-bubble-amount {
    display:inline-block;
    font-weight:800; font-size:15px;
    color:#25d366; margin-right:5px;
}
.qi-bubble-meta {
    display:flex; align-items:center; justify-content:flex-end;
    gap:6px; margin-top:2px;
}
.qi-bubble-time { font-size:10px; color:rgba(255,255,255,.45); }
.qi-del-btn {
    width:20px; height:20px; border-radius:50%;
    background:rgba(0,0,0,.3); border:none; cursor:pointer;
    color:rgba(255,255,255,.6); font-size:12px; line-height:1;
    display:flex; align-items:center; justify-content:center;
    opacity:0; transition:opacity .15s;
}
.qi-bubble-wrap:hover .qi-del-btn { opacity:1; }
.qi-del-btn:hover { background:rgba(239,68,68,.7); color:#fff; }

/* ─── Input bar ─── */
.qi-input-bar {
    display:flex; align-items:center; gap:10px;
    padding:10px 14px;
    background:var(--bg-hover);
    border-top:1px solid var(--border);
    flex-shrink:0;
}
#msgInput {
    flex:1; background:var(--bg-surface);
    color:var(--text-main);
    border:1px solid var(--border);
    border-radius:24px;
    padding:10px 16px;
    font-size:14px; font-family:inherit;
    outline:none; resize:none;
    max-height:100px; overflow-y:auto;
    line-height:1.5;
}
#msgInput::placeholder { color:var(--text-muted); }
#msgInput:focus { border-color:var(--accent); }
.qi-send-btn {
    width:42px; height:42px; border-radius:50%; border:none; cursor:pointer;
    background:#25d366; color:#fff;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0; transition:transform .1s, background .15s;
}
.qi-send-btn:hover  { background:#22c55e; transform:scale(1.05); }
.qi-send-btn:active { transform:scale(.95); }
.qi-send-btn svg { width:18px; height:18px; }

/* ─── Giderleştir paneli (modal) ─── */
.qi-panel-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.6); z-index:2000;
    align-items:flex-end; justify-content:center;
}
.qi-panel-overlay.open { display:flex; }
.qi-panel {
    background:var(--bg-surface);
    border-radius:var(--radius-lg) var(--radius-lg) 0 0;
    width:100%; max-width:720px;
    max-height:90vh; overflow-y:auto;
    padding:24px;
    animation:slideUp .22s ease;
}
@keyframes slideUp { from { transform:translateY(100%); } to { transform:translateY(0); } }

.qi-panel-head {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:18px;
}
.qi-panel-head h3 { margin:0; font-size:16px; }
.qi-close-btn {
    width:30px; height:30px; border-radius:50%;
    background:var(--bg-hover); border:1px solid var(--border);
    color:var(--text-muted); font-size:18px; cursor:pointer;
    display:flex; align-items:center; justify-content:center;
}

/* Panel table */
.qi-ptable { width:100%; border-collapse:collapse; font-size:13px; margin-top:4px; }
.qi-ptable th { padding:9px 10px; background:var(--bg-hover); color:var(--text-muted); font-size:11px; text-transform:uppercase; letter-spacing:.5px; text-align:left; border-bottom:2px solid var(--border); }
.qi-ptable td { padding:8px 8px; border-bottom:1px solid var(--border); vertical-align:middle; }
.qi-ptable tr.exc td { opacity:.35; }

.pamt { width:110px; text-align:right; background:var(--bg-hover); color:var(--text-main); border:1px solid var(--border); border-radius:var(--radius-sm); padding:6px 10px; font-size:13px; }
.pnotes { width:100%; background:var(--bg-hover); color:var(--text-main); border:1px solid var(--border); border-radius:var(--radius-sm); padding:6px 10px; font-size:13px; }
.pcat { width:100%; background:var(--bg-hover); color:var(--text-main); border:1px solid var(--border); border-radius:var(--radius-sm); padding:6px 8px; font-size:12px; }
.pex { width:24px; height:24px; border-radius:50%; border:1px solid var(--border); background:transparent; color:var(--text-muted); cursor:pointer; font-size:13px; display:flex; align-items:center; justify-content:center; }
.pex:hover, .pex.on { background:rgba(239,68,68,.15); color:var(--danger); border-color:var(--danger); }

.qi-panel-footer {
    display:flex; align-items:center; gap:16px;
    margin-top:16px; padding-top:16px;
    border-top:1px solid var(--border);
}
.qi-total-big { font-size:20px; font-weight:800; color:var(--accent); }
.qi-count-lbl { font-size:12px; color:var(--text-muted); }
</style>

<!-- MAIN WRAPPER -->
<div class="qi-wrap">

    <!-- Header -->
    <div class="qi-header">
        <div class="qi-header-left">
            <div class="qi-avatar">💸</div>
            <div>
                <div class="qi-header-title">Hızlı Gider</div>
                <div class="qi-header-sub">Giderleri yazıp toplu kaydet</div>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <div class="qi-badge"><span id="msgCount">0</span> kalem</div>
            <button class="btn btn-primary btn-sm" onclick="openPanel()" id="giderBtn" disabled>
                <i data-lucide="send"></i> Giderleştir
            </button>
        </div>
    </div>

    <!-- Chat area -->
    <div class="qi-chat" id="chatArea"></div>

    <!-- Input bar -->
    <div class="qi-input-bar">
        <textarea id="msgInput" rows="1" placeholder="2500 mazot, 725 yemek…" onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
        <button class="qi-send-btn" onclick="sendMsg()" title="Gönder">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </button>
    </div>
</div>

<!-- ── Giderleştir Paneli ── -->
<div class="qi-panel-overlay" id="panelOverlay" onclick="if(event.target===this)closePanel()">
<div class="qi-panel">
    <div class="qi-panel-head">
        <h3><i data-lucide="list-checks" style="width:18px;height:18px;vertical-align:middle;margin-right:6px;"></i>Giderleştir</h3>
        <button class="qi-close-btn" onclick="closePanel()">×</button>
    </div>

    <!-- Tarih + Varsayılan Kategori -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
        <div>
            <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:5px;">Tarih</label>
            <select id="pEntrySelect" class="select-input" style="width:100%;" onchange="document.getElementById('pEntryId').value=this.value; recalc();">
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
                <option value="<?= $e['id'] ?>"><?= date('d.m.Y', strtotime($e['entry_date'])) ?></option>
                <?php endforeach; ?>
                <?php if ($lastMonth !== '') echo '</optgroup>'; ?>
            </select>
        </div>
        <div>
            <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:5px;">Varsayılan Kategori</label>
            <select id="pDefaultCat" class="select-input" style="width:100%;" onchange="applyDefaultCat()">
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id'] == $defaultCatId ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Items table -->
    <div style="overflow-x:auto;">
        <table class="qi-ptable">
            <thead><tr>
                <th style="width:34px;"></th>
                <th style="width:120px;">Tutar (₺)</th>
                <th>Açıklama</th>
                <th style="width:190px;">Kategori</th>
                <th style="width:32px;"></th>
            </tr></thead>
            <tbody id="panelBody"></tbody>
        </table>
    </div>

    <!-- Footer -->
    <form id="saveForm" method="POST" action="?page=quick_import&action=save" onsubmit="return buildAndSubmit(event)">
        <input type="hidden" name="entry_id" id="pEntryId" value="">
        <div id="saveContainer"></div>
        <div class="qi-panel-footer">
            <div>
                <div class="qi-count-lbl" id="pCount">0 kalem</div>
                <div class="qi-total-big" id="pTotal">₺0</div>
            </div>
            <div style="margin-left:auto; display:flex; gap:10px;">
                <button type="button" class="btn btn-ghost" onclick="closePanel()">İptal</button>
                <button type="submit" class="btn btn-primary" id="pSaveBtn" disabled>
                    <i data-lucide="save"></i> Kaydet
                </button>
            </div>
        </div>
    </form>
</div>
</div>

<script>
const CATS         = <?= $catJson ?>;
const DEF_CAT_ID   = <?= $defaultCatId ?>;
const STORAGE_KEY  = 'qi_messages_v1';

let messages = []; // [{id, text, amount, notes, ts}]
let msgIdSeq = 0;

/* ─── Persist ─── */
function save()    { localStorage.setItem(STORAGE_KEY, JSON.stringify(messages)); }
function load()    {
    try { messages = JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; } catch(e) { messages = []; }
    msgIdSeq = messages.reduce((m,x) => Math.max(m, x.id), 0);
    renderAll();
}

/* ─── Parse ─── */
function parseTR(s) {
    s = String(s).trim().replace(/^[+]/, '');
    if (s.includes(',') && s.includes('.')) { s = s.replace(/\./g,'').replace(',','.'); }
    else if (s.includes(','))               { s = s.replace(',','.'); }
    else if (s.includes('.'))               { const p=s.split('.'); if(p.length>2||p[p.length-1].length===3) s=s.replace(/\./g,''); }
    return parseFloat(s) || 0;
}

function parseMsg(text) {
    // Virgülle ayrılmış çoklu gider: "2500 mazot, 400 yemek"
    // veya tek satır: "2500 mazot"
    const m = text.match(/^([+]?[\d.,]+)\s+(.+)$/);
    if (m) return { amount: parseTR(m[1]), notes: m[2].trim() };
    // Sadece sayı
    const n = text.match(/^([+]?[\d.,]+)$/);
    if (n) return { amount: parseTR(n[1]), notes: '' };
    return { amount: 0, notes: text.trim() };
}

/* ─── Send ─── */
function sendMsg() {
    const input = document.getElementById('msgInput');
    const raw   = input.value.trim();
    if (!raw) return;

    // Virgülle ayrılmış birden fazla gider desteği: "2500 mazot, 400 yemek"
    const parts = raw.split(/\s*,\s*(?=\S)/);
    parts.forEach(part => {
        part = part.trim();
        if (!part) return;
        const parsed = parseMsg(part);
        messages.push({ id: ++msgIdSeq, text: part, amount: parsed.amount, notes: parsed.notes, ts: Date.now() });
    });

    save();
    renderAll();
    input.value   = '';
    input.style.height = '';
    input.focus();
}

function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); }
}
function autoResize(el) {
    el.style.height = '';
    el.style.height = Math.min(el.scrollHeight, 100) + 'px';
}

/* ─── Delete ─── */
function delMsg(id) {
    messages = messages.filter(m => m.id !== id);
    save(); renderAll();
}

/* ─── Render chat ─── */
function fmtTime(ts) {
    const d = new Date(ts);
    return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
}
function fmtMoney(n) {
    return '₺' + n.toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function renderAll() {
    const area = document.getElementById('chatArea');
    area.innerHTML = messages.map(m => {
        const hasAmt = m.amount > 0;
        const amtHtml = hasAmt ? `<span class="qi-bubble-amount">${fmtMoney(m.amount)}</span>` : '';
        const notesTxt = hasAmt && m.notes ? escHtml(m.notes) : escHtml(m.text);
        return `<div class="qi-bubble-wrap">
            <button class="qi-del-btn" onclick="delMsg(${m.id})" title="Sil">×</button>
            <div class="qi-bubble ${hasAmt ? '' : 'no-amount'}">
                <div class="qi-bubble-text">${amtHtml}${notesTxt}</div>
                <div class="qi-bubble-meta">
                    <span class="qi-bubble-time">${fmtTime(m.ts)}</span>
                </div>
            </div>
        </div>`;
    }).join('');

    // Scroll to bottom
    area.scrollTop = area.scrollHeight;

    // Badge & button
    const cnt = messages.length;
    document.getElementById('msgCount').textContent = cnt;
    document.getElementById('giderBtn').disabled    = cnt === 0;
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

/* ─── Panel ─── */
let panelRowSeq = 0;

function catOptions(selId) {
    return CATS.map(c => `<option value="${c.id}" ${c.id==selId?'selected':''}>${c.name}</option>`).join('');
}

function openPanel() {
    if (!messages.length) return;
    panelRowSeq = 0;
    const tbody = document.getElementById('panelBody');
    tbody.innerHTML = messages.map(m => {
        const id = ++panelRowSeq;
        return `<tr id="pr-${id}" data-excluded="0">
            <td style="color:var(--text-muted);font-size:11px;text-align:center;">${id}</td>
            <td><input class="pamt" type="text" value="${m.amount > 0 ? m.amount : ''}" oninput="recalc()" inputmode="decimal" placeholder="0"></td>
            <td><input class="pnotes" type="text" value="${escHtml(m.notes || (m.amount ? '' : m.text))}"></td>
            <td><select class="pcat">${catOptions(DEF_CAT_ID)}</select></td>
            <td><button type="button" class="pex" onclick="toggleExc(${id})" title="Hariç tut">×</button></td>
        </tr>`;
    }).join('');
    recalc();
    document.getElementById('panelOverlay').classList.add('open');
    lucide.createIcons();
}

function closePanel() { document.getElementById('panelOverlay').classList.remove('open'); }

function toggleExc(id) {
    const tr  = document.getElementById('pr-' + id);
    const btn = tr.querySelector('.pex');
    const ex  = tr.dataset.excluded === '1';
    tr.dataset.excluded = ex ? '0' : '1';
    tr.classList.toggle('exc', !ex);
    btn.classList.toggle('on', !ex);
    recalc();
}

function applyDefaultCat() {
    const v = document.getElementById('pDefaultCat').value;
    document.querySelectorAll('#panelBody .pcat').forEach(s => s.value = v);
}

function recalc() {
    let total = 0, count = 0;
    document.querySelectorAll('#panelBody tr').forEach(tr => {
        if (tr.dataset.excluded === '1') return;
        const amt = parseTR(tr.querySelector('.pamt')?.value || '0');
        if (amt > 0) { total += amt; count++; }
    });
    document.getElementById('pTotal').textContent = fmtMoney(total);
    document.getElementById('pCount').textContent = count + ' kalem';
    const entryOk = !!document.getElementById('pEntryId').value;
    document.getElementById('pSaveBtn').disabled = (count === 0 || !entryOk);
}

function buildAndSubmit(e) {
    e.preventDefault();
    const entryId = document.getElementById('pEntryId').value;
    if (!entryId) { alert('Lütfen bir tarih seçin.'); return false; }

    const container = document.getElementById('saveContainer');
    container.innerHTML = '';
    let idx = 0;

    document.querySelectorAll('#panelBody tr').forEach(tr => {
        if (tr.dataset.excluded === '1') return;
        const amount = parseTR(tr.querySelector('.pamt')?.value || '0');
        const catId  = tr.querySelector('.pcat')?.value || '';
        const notes  = tr.querySelector('.pnotes')?.value?.trim() || '';
        if (amount <= 0 || !catId) return;

        const mk = (name, val) => { const i=document.createElement('input'); i.type='hidden'; i.name=name; i.value=val; container.appendChild(i); };
        mk(`items[${idx}][amount]`, amount);
        mk(`items[${idx}][category_id]`, catId);
        mk(`items[${idx}][notes]`, notes);
        idx++;
    });

    if (idx === 0) { alert('Kaydedilecek geçerli satır yok.'); return false; }

    // Kaydedilen mesajları temizle
    messages = [];
    save();

    document.getElementById('saveForm').submit();
}

/* ─── Boot ─── */
document.addEventListener('DOMContentLoaded', () => { load(); lucide.createIcons(); });
</script>
