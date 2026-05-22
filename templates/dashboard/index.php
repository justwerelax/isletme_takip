<?php
$monthNames = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
?>

<!-- Ay Seçici -->
<div class="month-selector">
    <form method="GET" class="month-form">
        <input type="hidden" name="page" value="dashboard">
        <div class="month-nav">
            <?php
            $prevMonth = $selectedMonth - 1; $prevYear = $selectedYear;
            if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
            $nextMonth = $selectedMonth + 1; $nextYear = $selectedYear;
            if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
            ?>
            <a href="?page=dashboard&year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-ghost btn-sm"><i data-lucide="chevron-left"></i></a>
            <select name="month" class="select-input" style="width:auto" onchange="this.form.submit()">
                <?php for ($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $m==$selectedMonth?'selected':'' ?>><?= $monthNames[$m] ?></option>
                <?php endfor; ?>
            </select>
            <select name="year" class="select-input" style="width:auto" onchange="this.form.submit()">
                <?php for ($y=2024;$y<=2030;$y++): ?>
                <option value="<?= $y ?>" <?= $y==$selectedYear?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <a href="?page=dashboard&year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-ghost btn-sm"><i data-lucide="chevron-right"></i></a>
            <?php if ($month && $month['is_locked']): ?>
            <span class="badge badge-warning"><i data-lucide="lock" style="width:12px;height:12px"></i> Kilitli</span>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- YAPILACAKLAR MİNİ ŞERİDİ -->
<?php if (($taskSummary['total'] ?? 0) > 0): ?>
<div class="db-task-strip">
    <div class="db-task-strip-left">
        <i data-lucide="clipboard-list" style="width:15px;height:15px;color:#818cf8;flex-shrink:0;"></i>
        <span style="font-size:13px;font-weight:600;color:var(--text-main);">Yapılacaklar</span>
        <?php if (($taskSummary['pending'] ?? 0) > 0): ?>
        <span class="db-strip-pill"><?= (int)$taskSummary['pending'] ?> bekliyor</span>
        <?php endif; ?>
        <?php if (($taskSummary['urgent'] ?? 0) > 0): ?>
        <span class="db-strip-pill db-strip-pill-hot">
            <i data-lucide="flame" style="width:11px;height:11px;"></i>
            <?= (int)$taskSummary['urgent'] ?> acil
        </span>
        <?php endif; ?>
        <span style="color:var(--border);margin:0 2px;">|</span>
        <?php foreach ($urgentTasks as $t): ?>
        <form method="POST" action="?page=tasks&action=toggleTask" style="display:contents;">
            <input type="hidden" name="id" value="<?= $t['id'] ?>">
            <button type="submit" class="db-strip-task" title="Tamamlandı işaretle">
                <span class="db-strip-dot" style="background:<?= htmlspecialchars($t['color']) ?>;"></span>
                <span><?= htmlspecialchars($t['title']) ?></span>
                <?php if ($t['priority'] === 'high'): ?>
                <i data-lucide="flame" style="width:11px;height:11px;color:#f97316;flex-shrink:0;"></i>
                <?php endif; ?>
            </button>
        </form>
        <?php endforeach; ?>
    </div>
    <a href="?page=tasks" class="db-strip-link">
        Tümü <i data-lucide="arrow-right" style="width:13px;height:13px;"></i>
    </a>
</div>
<?php endif; ?>

<!-- ===== KASA HESAPLAYICI ===== -->
<?php if ($summary): ?>
<div class="calc-bar">
    <button class="calc-toggle" onclick="toggleCalc()" id="calcToggleBtn">
        <i data-lucide="calculator" style="width:14px;height:14px;"></i>
        <span>Kasa Hesaplayıcı</span>
        <i data-lucide="chevron-down" id="calcChevron" style="width:13px;height:13px;transition:transform 0.2s;"></i>
    </button>

    <div id="calcBody" style="display:none;">
        <div class="calc-grid">
            <!-- Avanslar çıkınca mevcut — otomatik -->
            <div class="calc-field calc-field-auto">
                <label>Avanslar Çıkınca Mevcut</label>
                <div class="calc-auto-val" id="calcBase">
                    <?= Calculator::money($summary['available_after_advances']) ?>
                </div>
            </div>

            <!-- POS bakiyesi — otomatik -->
            <div class="calc-field calc-field-auto">
                <label>Kalan POS Bakiyesi</label>
                <div class="calc-auto-val" id="calcPosVal">
                    <?= Calculator::money($posNetBalance) ?>
                </div>
            </div>

            <!-- Banka -->
            <div class="calc-field">
                <label>Banka Hesabı 1</label>
                <input type="text" id="calcBank" class="calc-input" placeholder="0,00"
                       oninput="calcUpdate()" inputmode="decimal">
            </div>


            <!-- Nakit -->
            <div class="calc-field">
                <label>Nakit</label>
                <input type="text" id="calcCash" class="calc-input" placeholder="0,00"
                       oninput="calcUpdate()" inputmode="decimal">
            </div>

            <!-- Ekstra -->
            <div class="calc-field">
                <label>Ekstra <small style="color:var(--text-muted);font-weight:400;">(1.000+500+200 girebilirsin)</small></label>
                <input type="text" id="calcExtra" class="calc-input" placeholder="0,00"
                       oninput="calcExtraInput(this)" onblur="calcExtraBlur(this)" inputmode="decimal">
                <small id="calcExtraPreview" style="color:var(--accent);font-size:11px;margin-top:3px;display:none;"></small>
            </div>

            <!-- Sonuç -->
            <div class="calc-field calc-field-result">
                <label>Fark (Mevcut − Toplanan)</label>
                <div class="calc-result" id="calcResult">—</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const calcBaseVal = <?= $summary ? (float)$summary['available_after_advances'] : 0 ?>;
const calcPosVal  = <?= round($posNetBalance, 2) ?>;
document.addEventListener('DOMContentLoaded', function() {
    calcUpdate();
});

function toggleCalc() {
    const body    = document.getElementById('calcBody');
    const chevron = document.getElementById('calcChevron');
    const isOpen  = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : 'flex';
    chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
}

function parseMoney(str) {
    if (!str) return 0;
    str = str.trim().replace(/\s/g, '');
    if (str.includes(',') && str.includes('.')) {
        str = str.replace(/\./g, '').replace(',', '.');
    } else {
        str = str.replace(',', '.');
    }
    return parseFloat(str) || 0;
}

// Türkçe sayı formatını parse eder (binlik nokta desteğiyle)
function parseTRNum(str) {
    if (!str) return 0;
    str = str.trim();
    if (str.includes('.') && str.includes(',')) {
        str = str.replace(/\./g, '').replace(',', '.');
    } else if (str.includes(',')) {
        str = str.replace(',', '.');
    } else if (str.includes('.')) {
        const afterDot = str.split('.').slice(1).join('');
        if (afterDot.length === 3 || str.split('.').length > 2) {
            str = str.replace(/\./g, '');
        }
    }
    return parseFloat(str) || 0;
}

// Toplama ifadesini parse et: "1.000+500,50+200" → 1700.50
function parseSum(str) {
    if (!str) return 0;
    return str.split('+').reduce((acc, part) => acc + parseTRNum(part), 0);
}

function calcExtraInput(el) {
    const preview = document.getElementById('calcExtraPreview');
    if (el.value.includes('+')) {
        const sum = parseSum(el.value);
        preview.textContent = '= ' + sum.toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' ₺';
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
    calcUpdate();
}

function calcExtraBlur(el) {
    const preview = document.getElementById('calcExtraPreview');
    if (el.value.includes('+')) {
        const sum = parseSum(el.value);
        if (sum > 0) {
            el.value = sum.toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2});
        } else {
            el.value = '';
        }
        preview.style.display = 'none';
        calcUpdate();
    }
}

function calcUpdate() {
    const bank  = parseMoney(document.getElementById('calcBank').value);
    const cash  = parseMoney(document.getElementById('calcCash').value);
    const extra = parseSum(document.getElementById('calcExtra').value);

    const collected = calcPosVal + bank + cash + extra;
    const diff      = calcBaseVal - collected;

    const el = document.getElementById('calcResult');
    el.textContent = formatCalcMoney(diff);
    el.style.color = diff > 0 ? '#f97316'
                   : diff < 0 ? '#10b981'
                   : '#94a3b8';
}

function formatCalcMoney(v) {
    return '₺' + Math.abs(v).toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2})
         + (v > 0 ? ' eksik' : v < 0 ? ' fazla' : '');
}
</script>

<style>
.calc-bar {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    margin-bottom: 18px;
    overflow: hidden;
}
.calc-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-secondary);
    font-size: 13px;
    font-weight: 600;
    text-align: left;
    transition: background 0.15s;
}
.calc-toggle:hover { background: var(--bg-hover); }
.calc-toggle svg:first-child { color: var(--accent); }
.calc-toggle span { flex: 1; }

#calcBody {
    flex-direction: row;
    flex-wrap: wrap;
    gap: 12px;
    padding: 14px 16px;
    border-top: 1px solid var(--border);
    background: rgba(15,23,42,0.3);
    align-items: flex-end;
}

.calc-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
    flex: 1;
    min-width: 140px;
}
.calc-field label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--text-muted);
}
.calc-input {
    background: var(--bg-elevated);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 8px 12px;
    color: var(--text-main);
    font-size: 14px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    transition: border-color 0.15s;
    width: 100%;
    box-sizing: border-box;
}
.calc-input:focus {
    outline: none;
    border-color: var(--accent);
}
.calc-auto-val {
    background: rgba(30,41,59,0.5);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 14px;
    font-weight: 700;
    color: #0ea5e9;
}
.calc-field-result { min-width: 180px; }
.calc-result {
    background: rgba(30,41,59,0.5);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 15px;
    font-weight: 800;
    color: var(--text-muted);
    min-height: 38px;
    display: flex;
    align-items: center;
}
</style>

<?php if (!$summary): ?>
<div class="empty-state">
    <i data-lucide="calendar-plus" style="width:64px;height:64px;color:#64748b"></i>
    <h3>Bu ay için kayıt bulunamadı</h3>
    <p>Bu ayı oluşturmak için Ay Yönetimi sayfasına gidin.</p>
    <a href="?page=months" class="btn btn-primary">Ay Oluştur</a>
</div>
<?php else: ?>

<!-- ÜST BÖLÜM: FİNANSAL ÖZET + ORTAKLAR -->
<div class="db-top-grid">

    <!-- SOL: Finansal Özet -->
    <div class="dashboard-big-card db-card-left" style="display:flex;flex-direction:column;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <h2 class="section-title" style="margin:0;"><i data-lucide="bar-chart-2"></i> Finansal Özet</h2>
            <button onclick="toggleForecast()" class="btn btn-ghost btn-sm" id="toggleBtn" style="color:var(--accent);gap:5px;">
                <i data-lucide="eye" id="toggleIcon" style="width:16px;"></i>
                <span id="toggleText">Öngörü</span>
            </button>
        </div>

        <div id="metricsContainer" class="metrics-grid-view">
            <div class="metric-item metric-clickable" onclick="showPanel('panel-curo')" data-panel="panel-curo">
                <div class="metric-icon"><i data-lucide="trending-up"></i></div>
                <div class="metric-content">
                    <span class="metric-label">Aktif Cüro</span>
                    <span class="metric-value"><?= Calculator::money($summary['active_curo']) ?></span>
                </div>
                <i data-lucide="chevron-right" class="metric-arrow"></i>
            </div>
            <div class="metric-item metric-clickable metric-profit" onclick="showPanel('panel-profit')" data-panel="panel-profit">
                <div class="metric-icon"><i data-lucide="badge-dollar-sign"></i></div>
                <div class="metric-content">
                    <span class="metric-label">Aktif Kâr</span>
                    <span class="metric-value"><?= Calculator::money($summary['active_profit']) ?></span>
                </div>
                <i data-lucide="chevron-right" class="metric-arrow"></i>
            </div>
            <div class="metric-item metric-clickable metric-expense" onclick="showPanel('panel-expense')" data-panel="panel-expense">
                <div class="metric-icon"><i data-lucide="trending-down"></i></div>
                <div class="metric-content">
                    <span class="metric-label">Aktif Gider</span>
                    <span class="metric-value"><?= Calculator::money($summary['total_expenses']) ?></span>
                </div>
                <i data-lucide="chevron-right" class="metric-arrow"></i>
            </div>
            <div class="metric-item metric-clickable metric-ratio" onclick="showPanel('panel-stats')" data-panel="panel-stats">
                <div class="metric-icon"><i data-lucide="pie-chart"></i></div>
                <div class="metric-content">
                    <span class="metric-label">Cüro / Gider</span>
                    <span class="metric-value <?= $summary['expense_ratio']>30?'text-danger':'text-success' ?>"><?= Calculator::percent($summary['expense_ratio']) ?></span>
                </div>
                <i data-lucide="chevron-right" class="metric-arrow"></i>
            </div>
            <div class="metric-item metric-clickable" style="background:rgba(14,165,233,0.1);border-color:rgba(14,165,233,0.2);"
                 onclick="showPanel('panel-kasa')" data-panel="panel-kasa">
                <div class="metric-icon" style="background:rgba(14,165,233,0.1);color:#0ea5e9;"><i data-lucide="wallet"></i></div>
                <div class="metric-content">
                    <span class="metric-label">Avanslar Çıkınca Mevcut</span>
                    <span class="metric-value" style="color:#0ea5e9;"><?= Calculator::money($summary['available_after_advances']) ?></span>
                </div>
                <i data-lucide="chevron-right" class="metric-arrow"></i>
            </div>
        </div>

        <!-- Simülasyon -->
        <div id="forecastArea" style="max-height:0;overflow:hidden;transition:all 0.4s ease;opacity:0;">
            <div style="padding-top:18px;border-top:1px solid var(--border);margin-top:18px;display:flex;flex-direction:column;gap:16px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="metric-item" style="background:rgba(239,68,68,0.08);border-color:rgba(239,68,68,0.15);padding:12px;">
                        <div class="metric-icon" style="color:#ef4444;transform:scale(0.85);"><i data-lucide="clock"></i></div>
                        <div class="metric-content">
                            <span class="metric-label" style="font-size:10px;">Borç Yükü</span>
                            <span class="metric-value" style="color:#ef4444;font-size:16px;"><?= Calculator::money($unpaidDebts) ?></span>
                        </div>
                    </div>
                    <div class="form-group" style="margin:0;background:rgba(59,130,246,0.05);padding:12px;border-radius:12px;border:1px solid rgba(59,130,246,0.1);">
                        <label style="font-size:10px;color:var(--accent);margin-bottom:5px;display:block;font-weight:700;">EK GELİR GİRİŞİ</label>
                        <input type="number" id="extraIncome" oninput="recalcForecast()" placeholder="0"
                               style="width:100%;background:var(--bg-elevated);border:1px solid var(--border);color:var(--text-main);padding:8px 12px;border-radius:8px;font-weight:700;">
                    </div>
                </div>
                <div style="background:var(--bg-surface);padding:14px;border-radius:12px;border:1px solid var(--border);">
                    <div style="font-size:11px;color:var(--text-muted);margin-bottom:10px;font-weight:600;text-transform:uppercase;display:flex;justify-content:space-between;align-items:center;">
                        <span>Simülasyon Pay Dağılımı</span>
                        <div id="forecastProfitLabel" style="background:rgba(16,185,129,0.1);color:var(--success);padding:3px 10px;border-radius:20px;font-weight:700;"></div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
                        <?php foreach($summary['partners'] as $p): ?>
                        <div style="padding:10px;background:var(--bg-elevated);border-radius:10px;border:1px solid var(--border);display:flex;flex-direction:column;gap:3px;">
                            <span style="font-size:10px;opacity:0.6;font-weight:600;"><?= mb_strtoupper($p['name']) ?></span>
                            <span id="p-forecast-<?= $p['id'] ?>" style="font-weight:800;color:<?= $p['is_cash_reserve']?'var(--success)':'var(--text-main)' ?>;font-size:14px;">₺0</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SAĞ: Ortaklar -->
    <div class="dashboard-big-card db-card-right" style="display:flex;flex-direction:column;">
        <h2 class="section-title"><i data-lucide="users"></i> Ortaklar</h2>
        <div class="partners-list" style="display:flex;flex-direction:column;flex:1;">
            <?php foreach ($summary['partners'] as $p): ?>
            <?php if (!$p['is_cash_reserve']): ?>
            <div class="partner-list-item">
                <div class="partner-header">
                    <div class="partner-avatar"><?= mb_substr($p['name'],0,1) ?></div>
                    <div style="flex:1;display:flex;justify-content:space-between;align-items:center;">
                        <h3 style="margin:0;"><?= htmlspecialchars($p['name']) ?></h3>
                        <div style="background:rgba(30,41,59,0.5);padding:5px 10px;border-radius:var(--radius-md);border:1px solid var(--border);display:flex;flex-direction:column;align-items:flex-end;">
                            <span style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;">Aylık Ücret</span>
                            <span style="font-weight:700;color:var(--text-main);font-size:14px;"><?= Calculator::money($p['monthly_salary']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="partner-stats">
                    <div class="stat-row">
                        <span class="stat-label">Günlük Ort. Ücret</span>
                        <span class="stat-value"><?= Calculator::money($p['daily_avg_salary']) ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Harcama (Avans)</span>
                        <span class="stat-value text-warning"><?= Calculator::money($p['advance_total']) ?></span>
                    </div>
                    <div class="stat-row stat-highlight">
                        <span class="stat-label">Kalan Bakiye</span>
                        <span class="stat-value text-success" style="font-size:15px;font-weight:700;"><?= Calculator::money($p['remaining_balance']) ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>

            <?php
            $cashPartner = null;
            foreach ($summary['partners'] as $p) { if ($p['is_cash_reserve']) { $cashPartner = $p; break; } }
            ?>
            <?php if ($cashPartner): ?>
            <div class="partner-list-item" style="border-color:var(--accent);background:var(--bg-hover);">
                <div class="partner-header">
                    <div class="partner-avatar" style="background:var(--gradient-success);"><i data-lucide="vault" style="color:#fff;width:20px;height:20px;"></i></div>
                    <div style="flex:1;display:flex;justify-content:space-between;align-items:center;">
                        <h3 style="margin:0;">Kasa (Ortak Kasa)</h3>
                        <div style="background:rgba(16,185,129,0.1);padding:5px 10px;border-radius:var(--radius-md);border:1px solid rgba(16,185,129,0.2);display:flex;flex-direction:column;align-items:flex-end;">
                            <span style="font-size:10px;color:var(--success);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;">Aylık Pay</span>
                            <span style="font-weight:700;color:var(--success);font-size:14px;"><?= Calculator::money($cashPartner['monthly_salary']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="partner-stats">
                    <div class="stat-row">
                        <span class="stat-label">Kasa Devir (Sıcak Para)</span>
                        <span class="stat-value"><?= Calculator::money($summary['cash_carryover']) ?></span>
                    </div>
                    <div class="stat-row stat-highlight" style="background:rgba(16,185,129,0.15);">
                        <span class="stat-label">Kasa Birikim (Rezerv)</span>
                        <span class="stat-value text-success" style="font-size:15px;font-weight:700;"><?= Calculator::money($summary['reserve_carryover']) ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- DETAY PANELİ -->
<div id="detailPanel" class="db-detail-panel" style="display:none;">
    <div class="db-detail-inner">
        <div class="db-detail-header">
            <span id="detailPanelTitle" class="db-detail-title"></span>
            <button onclick="closePanel()" class="btn btn-ghost btn-sm" style="padding:4px 8px;">
                <i data-lucide="x" style="width:16px;height:16px;"></i>
            </button>
        </div>

        <!-- Cüro -->
        <div id="panel-curo" class="db-panel-content" style="display:none;">
            <div class="db-panel-grid">
                <div class="db-stat-card"><span class="db-stat-label">Aktif Cüro</span><span class="db-stat-value text-accent"><?= Calculator::money($summary['active_curo']) ?></span></div>
                <div class="db-stat-card"><span class="db-stat-label">Toplam POS</span><span class="db-stat-value"><?= Calculator::money($summary['total_pos']) ?></span></div>
                <div class="db-stat-card"><span class="db-stat-label">Dış Gelir</span><span class="db-stat-value"><?= Calculator::money($summary['total_external_revenue']) ?></span></div>
                <div class="db-stat-card"><span class="db-stat-label">İş Günü</span><span class="db-stat-value text-accent"><?= $summary['working_days'] ?></span></div>
            </div>
            <div style="margin-top:20px;">
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin-bottom:10px;">Günlük Gelir / Gider Trendi</div>
                <div class="chart-container"><canvas id="revenueChart"></canvas></div>
            </div>
        </div>

        <!-- Kâr -->
        <div id="panel-profit" class="db-panel-content" style="display:none;">
            <div class="db-panel-grid">
                <div class="db-stat-card" style="background:rgba(16,185,129,0.08);border-color:rgba(16,185,129,0.2);"><span class="db-stat-label">Aktif Kâr</span><span class="db-stat-value text-success"><?= Calculator::money($summary['active_profit']) ?></span></div>
                <div class="db-stat-card"><span class="db-stat-label">Günlük Ort. Kâr</span><span class="db-stat-value text-success"><?= Calculator::money($summary['daily_avg_profit']) ?></span></div>
                <div class="db-stat-card"><span class="db-stat-label">Aktif Cüro</span><span class="db-stat-value"><?= Calculator::money($summary['active_curo']) ?></span></div>
                <div class="db-stat-card"><span class="db-stat-label">Aktif Gider</span><span class="db-stat-value text-danger"><?= Calculator::money($summary['total_expenses']) ?></span></div>
            </div>
            <div style="margin-top:20px;">
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin-bottom:12px;">Ortak Pay Dağılımı</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;">
                    <?php foreach($summary['partners'] as $p): ?>
                    <div class="db-stat-card" style="<?= $p['is_cash_reserve']?'background:rgba(16,185,129,0.08);border-color:rgba(16,185,129,0.2);':'' ?>">
                        <span class="db-stat-label"><?= htmlspecialchars($p['name']) ?></span>
                        <span class="db-stat-value <?= $p['is_cash_reserve']?'text-success':'' ?>"><?= Calculator::money($summary['active_profit'] * $p['profit_share']) ?></span>
                        <span style="font-size:10px;color:var(--text-muted);">%<?= number_format($p['profit_share']*100,1,',','.') ?> pay</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Gider -->
        <div id="panel-expense" class="db-panel-content" style="display:none;">
            <div class="db-panel-grid">
                <div class="db-stat-card" style="background:rgba(239,68,68,0.08);border-color:rgba(239,68,68,0.2);"><span class="db-stat-label">Toplam Gider</span><span class="db-stat-value text-danger"><?= Calculator::money($summary['total_expenses']) ?></span></div>
                <div class="db-stat-card"><span class="db-stat-label">Cüro / Gider Oranı</span><span class="db-stat-value <?= $summary['expense_ratio']>30?'text-danger':'text-success' ?>"><?= Calculator::percent($summary['expense_ratio']) ?></span></div>
            </div>
            <div style="margin-top:20px;">
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin-bottom:12px;">Kategori Dağılımı</div>
                <?php $absTotalExpenses = abs($summary['total_expenses']); ?>
                <?php foreach ($summary['expense_by_category'] as $cat): ?>
                <?php if ($cat['total'] == 0) continue; ?>
                <?php $catPct = $absTotalExpenses > 0 ? (abs($cat['total']) / $absTotalExpenses) * 100 : 0; ?>
                <div style="margin-bottom:10px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                        <span style="font-size:13px;font-weight:600;"><?= htmlspecialchars($cat['name']) ?></span>
                        <span style="font-size:13px;font-weight:700;color:#ef4444;"><?= Calculator::money($cat['total']) ?></span>
                    </div>
                    <div style="height:5px;background:rgba(255,255,255,0.05);border-radius:3px;overflow:hidden;">
                        <div style="height:100%;width:<?= round($catPct) ?>%;background:linear-gradient(90deg,#ef4444,#f97316);border-radius:3px;"></div>
                    </div>
                    <?php if (!empty($cat['sub'])): ?>
                    <div style="margin-top:5px;padding-left:12px;display:flex;flex-direction:column;gap:2px;">
                        <?php foreach ($cat['sub'] as $sub): ?>
                        <?php if ($sub['total'] == 0) continue; ?>
                        <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);">
                            <span>└ <?= htmlspecialchars($sub['name']) ?></span>
                            <span style="color:#f87171;"><?= Calculator::money($sub['total']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- İstatistikler -->
        <div id="panel-stats" class="db-panel-content" style="display:none;">
            <div class="db-panel-grid">
                <div class="db-stat-card"><span class="db-stat-label">İş Günü</span><span class="db-stat-value text-accent"><?= $summary['working_days'] ?></span></div>
                <div class="db-stat-card"><span class="db-stat-label">Günlük Ort. Kâr</span><span class="db-stat-value text-success"><?= Calculator::money($summary['daily_avg_profit']) ?></span></div>
                <div class="db-stat-card"><span class="db-stat-label">Cüro / Gider</span><span class="db-stat-value <?= $summary['expense_ratio']>30?'text-danger':'text-success' ?>"><?= Calculator::percent($summary['expense_ratio']) ?></span></div>
                <div class="db-stat-card"><span class="db-stat-label">Toplam POS</span><span class="db-stat-value"><?= Calculator::money($summary['total_pos']) ?></span></div>
                <div class="db-stat-card"><span class="db-stat-label">Dış Gelir</span><span class="db-stat-value"><?= Calculator::money($summary['total_external_revenue']) ?></span></div>
                <div class="db-stat-card"><span class="db-stat-label">Aktif Cüro</span><span class="db-stat-value"><?= Calculator::money($summary['active_curo']) ?></span></div>
            </div>
            <div style="margin-top:20px;">
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin-bottom:12px;">Günlük Girişler</div>
                <div class="table-wrapper" style="max-height:320px;overflow-y:auto;">
                    <table class="data-table">
                        <thead><tr>
                            <th>Tarih</th><th class="text-right">Gelir</th><th class="text-right">Dış Gelir</th>
                            <?php foreach ($summary['categories'] as $cat): ?><th class="text-right"><?= htmlspecialchars($cat['name']) ?></th><?php endforeach; ?>
                            <th class="text-right">POS</th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($summary['entries'])): ?>
                            <tr><td colspan="<?= 4+count($summary['categories']) ?>" class="text-center text-muted">Henüz giriş yok</td></tr>
                        <?php else: ?>
                            <?php foreach ($summary['entries'] as $entry):
                                $entryExpenses = Database::fetchAll("SELECT category_id, amount FROM daily_expenses WHERE daily_entry_id = ?", [$entry['id']]);
                                $expMap = [];
                                foreach ($entryExpenses as $exp) $expMap[$exp['category_id']] = $exp['amount'];
                            ?>
                            <tr>
                                <td><?= date('d.m.Y', strtotime($entry['entry_date'])) ?></td>
                                <td class="text-right text-success"><?= Calculator::money((float)$entry['revenue'],false) ?></td>
                                <td class="text-right text-accent"><?= (float)$entry['external_revenue']>0 ? Calculator::money((float)$entry['external_revenue'],false) : '-' ?></td>
                                <?php foreach ($summary['categories'] as $cat): ?>
                                <td class="text-right text-danger"><?= isset($expMap[$cat['id']]) ? Calculator::money((float)$expMap[$cat['id']],false) : '-' ?></td>
                                <?php endforeach; ?>
                                <td class="text-right"><?= (float)$entry['pos_amount']>0 ? Calculator::money((float)$entry['pos_amount'],false) : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kasa -->
        <div id="panel-kasa" class="db-panel-content" style="display:none;">
            <div class="db-panel-grid">
                <div class="db-stat-card" style="background:rgba(16,185,129,0.08);border-color:rgba(16,185,129,0.2);"><span class="db-stat-label">Kasa Rezerv (Birikim)</span><span class="db-stat-value text-success"><?= Calculator::money($summary['reserve_carryover']) ?></span></div>
                <div class="db-stat-card"><span class="db-stat-label">Kasa Devir (Sıcak Para)</span><span class="db-stat-value"><?= Calculator::money($summary['cash_carryover']) ?></span></div>
                <div class="db-stat-card"><span class="db-stat-label">Kasa Dahil Toplam</span><span class="db-stat-value text-accent"><?= Calculator::money($summary['total_with_cash']) ?></span></div>
                <div class="db-stat-card" style="background:rgba(14,165,233,0.08);border-color:rgba(14,165,233,0.2);"><span class="db-stat-label">Avanslar Çıkınca Mevcut</span><span class="db-stat-value" style="color:#0ea5e9;"><?= Calculator::money($summary['available_after_advances']) ?></span></div>
                <?php if ($cashPartner): ?>
                <div class="db-stat-card"><span class="db-stat-label">Aylık Kasa Payı</span><span class="db-stat-value text-success"><?= Calculator::money($cashPartner['monthly_salary']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
window.chartData = {
    labels:   <?= json_encode(array_map(fn($e) => date('d.m', strtotime($e['entry_date'])), $summary['entries'])) ?>,
    revenues: <?= json_encode(array_map(fn($e) => (float)$e['revenue'], $summary['entries'])) ?>,
    externals:<?= json_encode(array_map(fn($e) => (float)$e['external_revenue'], $summary['entries'])) ?>,
    expenses: <?= json_encode(array_map(function($e) {
        $row = Database::fetch("SELECT SUM(amount) as total FROM daily_expenses WHERE daily_entry_id = ?", [$e['id']]);
        return abs((float)($row['total'] ?? 0));
    }, $summary['entries'])) ?>
};

const currentProfit = <?= (float)$summary['active_profit'] ?>;
const debtLoad      = <?= (float)$unpaidDebts ?>;
const partners      = <?= json_encode(array_map(fn($p) => ['id'=>$p['id'],'share'=>(float)$p['profit_share']], $summary['partners'])) ?>;

function formatMoney(v) { return '₺' + v.toLocaleString('tr-TR',{minimumFractionDigits:0,maximumFractionDigits:0}); }

function recalcForecast() {
    const extra = parseFloat(document.getElementById('extraIncome').value) || 0;
    const net   = (currentProfit + extra) - debtLoad;
    document.getElementById('forecastProfitLabel').innerText = 'Simülasyon Kârı: ' + formatMoney(net);
    partners.forEach(p => { document.getElementById('p-forecast-'+p.id).innerText = formatMoney(net * p.share); });
}

function toggleForecast() {
    const container = document.getElementById('metricsContainer');
    const area      = document.getElementById('forecastArea');
    const btnText   = document.getElementById('toggleText');
    const btnIcon   = document.getElementById('toggleIcon');
    const isOpening = area.style.maxHeight === '0px' || !area.style.maxHeight;
    if (isOpening) {
        container.classList.add('compact');
        area.style.maxHeight = '400px'; area.style.opacity = '1';
        if (btnText) btnText.innerText = 'Kapat';
        if (btnIcon) btnIcon.setAttribute('data-lucide','x');
        recalcForecast();
    } else {
        container.classList.remove('compact');
        area.style.maxHeight = '0px'; area.style.opacity = '0';
        if (btnText) btnText.innerText = 'Öngörü';
        if (btnIcon) btnIcon.setAttribute('data-lucide','eye');
    }
    if (window.lucide) window.lucide.createIcons();
}

const panelTitles = {
    'panel-curo':    'Cüro Detayı',
    'panel-profit':  'Kâr Detayı',
    'panel-expense': 'Gider Detayı',
    'panel-stats':   'İstatistikler & Günlük Girişler',
    'panel-kasa':    'Kasa Bilgileri',
};
let activePanel = null;
let chartInited = false;

function showPanel(id) {
    const panel = document.getElementById('detailPanel');
    if (activePanel === id && panel.style.display !== 'none') { closePanel(); return; }
    document.querySelectorAll('.db-panel-content').forEach(c => c.style.display = 'none');
    document.querySelectorAll('.metric-clickable').forEach(m => m.classList.remove('metric-active'));
    const target = document.getElementById(id);
    if (target) target.style.display = 'block';
    document.querySelectorAll('[data-panel="'+id+'"]').forEach(el => el.classList.add('metric-active'));
    document.getElementById('detailPanelTitle').innerText = panelTitles[id] || '';
    panel.style.display = 'block';
    activePanel = id;
    if (id === 'panel-curo' && !chartInited && window.chartData) {
        setTimeout(() => { initRevenueChart(); chartInited = true; }, 50);
    }
    if (window.lucide) window.lucide.createIcons();
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function closePanel() {
    document.getElementById('detailPanel').style.display = 'none';
    document.querySelectorAll('.metric-clickable').forEach(m => m.classList.remove('metric-active'));
    activePanel = null;
}
</script>

<?php endif; ?>

<style>
.db-top-grid {
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 22px;
    align-items: stretch;
    margin-bottom: 20px;
}
.db-top-grid > .dashboard-big-card {
    height: 100%;
    box-sizing: border-box;
}
.metric-clickable {
    cursor: pointer;
    user-select: none;
}
.metric-clickable:hover {
    border-color: var(--accent) !important;
    background: rgba(99,102,241,0.08) !important;
    transform: translateX(3px);
}
.metric-clickable.metric-active {
    border-color: var(--accent) !important;
    background: rgba(99,102,241,0.12) !important;
}
.metric-arrow {
    width: 15px; height: 15px;
    color: var(--text-muted);
    flex-shrink: 0;
    margin-left: auto;
    transition: transform 0.2s;
}
.metric-clickable:hover .metric-arrow,
.metric-clickable.metric-active .metric-arrow {
    color: var(--accent);
    transform: translateX(3px);
}
.db-detail-panel {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    animation: panelSlideIn 0.25s ease;
    margin-bottom: 20px;
}
@keyframes panelSlideIn {
    from { opacity:0; transform:translateY(-8px); }
    to   { opacity:1; transform:translateY(0); }
}
.db-detail-inner { padding: 20px 22px; }
.db-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--border);
}
.db-detail-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--text-main);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.db-panel-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
}
.db-stat-card {
    background: rgba(30,41,59,0.4);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.db-stat-label { font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:600; }
.db-stat-value { font-size:1.25rem; font-weight:700; color:var(--text-main); }

.metrics-grid-view {
    display: flex;
    flex-direction: column;
    gap: 10px;
    transition: all 0.3s ease;
    flex: 1;
}
.metric-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 13px 16px;
    background: rgba(30,41,59,0.4);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    width: 100%;
    transition: all 0.2s ease;
    flex: 1;
    min-height: 56px;
}
.metric-icon {
    width:40px; height:40px;
    display:flex; align-items:center; justify-content:center;
    background:rgba(59,130,246,0.1);
    color:var(--accent);
    border-radius:10px;
    flex-shrink:0;
}
.metric-content { display:flex; flex-direction:column; gap:2px; flex:1; }
.metric-label { font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:600; }
.metric-value { font-size:1.3rem; font-weight:700; color:var(--text-main); }

.metrics-grid-view.compact { gap:6px; flex:0; }
.metrics-grid-view.compact .metric-item { padding:7px 12px; flex:0 0 auto; min-height:46px; }
.metrics-grid-view.compact .metric-icon { width:30px; height:30px; border-radius:7px; }
.metrics-grid-view.compact .metric-value { font-size:1rem; }
.metrics-grid-view.compact .metric-label { font-size:9px; }

/* Yapılacaklar şeridi */
.db-task-strip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 9px 16px;
    margin-bottom: 18px;
    overflow: hidden;
}
.db-task-strip-left {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 0;
    overflow: hidden;
}
.db-strip-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 9px;
    border-radius: 20px;
    background: rgba(129,140,248,0.12);
    color: #818cf8;
    border: 1px solid rgba(129,140,248,0.2);
    white-space: nowrap;
    flex-shrink: 0;
}
.db-strip-pill-hot {
    background: rgba(249,115,22,0.1);
    color: #f97316;
    border-color: rgba(249,115,22,0.2);
}
.db-strip-task {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(30,41,59,0.5);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 3px 10px 3px 7px;
    font-size: 12px;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
    flex-shrink: 0;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.db-strip-task:hover {
    background: rgba(16,185,129,0.1);
    border-color: rgba(16,185,129,0.3);
    color: #10b981;
}
.db-strip-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}
.db-strip-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 600;
    color: var(--accent);
    white-space: nowrap;
    flex-shrink: 0;
    text-decoration: none;
    opacity: 0.75;
    transition: opacity 0.15s;
}
.db-strip-link:hover { opacity: 1; }

@media (max-width: 768px) {
    .db-top-grid { grid-template-columns: 1fr; }
    .db-task-strip { flex-wrap: wrap; }
}
</style>
