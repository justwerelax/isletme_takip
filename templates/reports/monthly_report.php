<?php
$mn = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
$m   = $summary['month'];
$mon = $mn[$m['month']] . ' ' . $m['year'];

$realPartners = array_values(array_filter($summary['partners'], fn($p) => !$p['is_cash_reserve']));
$cashPartner  = current(array_filter($summary['partners'], fn($p) => $p['is_cash_reserve'])) ?: null;
$expCats      = array_values(array_filter($summary['expense_by_category'], fn($c) => $c['total'] != 0));
$absTotalExp  = abs($summary['total_expenses']);
$hasDebt      = array_sum(array_column($upcomingDebts,'total')) > 0;
$clrs         = ['#c0392b','#e67e22','#f39c12','#8e44ad','#2980b9','#27ae60','#d81b60'];
?>

<!-- Ekran butonu -->
<div class="screen-only" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="?page=reports" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> Geri</a>
        <span style="font-weight:600;"><?= $mon ?> — Ay Sonu Raporu</span>
    </div>
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i data-lucide="printer"></i> Yazdır / PDF</button>
</div>

<!-- ══ RAPOR ══ -->
<div id="RPT">

<div class="rpt-header">
    <div>
        <h1><?= $mon ?> — Ay Sonu Raporu</h1>
        <p><?= date('d.m.Y') ?><?= $m['is_locked'] ? ' &nbsp;·&nbsp; Kilitli Ay' : '' ?></p>
    </div>
</div>

<!-- ── 1. FİNANSAL ÖZET ── -->
<table class="summary-table">
    <thead><tr>
        <th>Aktif Cüro</th>
        <th>Toplam Gider</th>
        <th>Aktif Kâr</th>
        <th>Cüro / Gider</th>
        <th>İş Günü</th>
        <th>Günlük Ort. Kâr</th>
    </tr></thead>
    <tbody><tr>
        <td><?= Calculator::money($summary['active_curo']) ?></td>
        <td><?= Calculator::money($summary['total_expenses']) ?></td>
        <td class="bold"><?= Calculator::money($summary['active_profit']) ?></td>
        <td><?= Calculator::percent($summary['expense_ratio']) ?></td>
        <td><?= $summary['working_days'] ?></td>
        <td><?= Calculator::money($summary['daily_avg_profit']) ?></td>
    </tr></tbody>
</table>

<!-- ── 2. ORTA BÖLÜM: Gider + Kasa ── -->
<div class="two-col">

    <!-- Sol: Gider Dağılımı -->
    <div class="col">
        <h2>Gider Dağılımı</h2>
        <?php if (!empty($expCats)): ?>
        <div style="display:flex;gap:16px;align-items:flex-start;">
            <canvas id="donut" width="90" height="90" style="flex-shrink:0;margin-top:4px;"></canvas>
            <table class="inner-table" style="flex:1;">
                <thead><tr><th>Kategori</th><th>Tutar</th><th>Pay</th></tr></thead>
                <tbody>
                <?php foreach ($expCats as $i => $cat):
                    if ($cat['total'] == 0) continue;
                    $pct = $absTotalExp > 0 ? (abs($cat['total'])/$absTotalExp)*100 : 0;
                ?>
                <tr>
                    <td><span class="dot-inline" style="background:<?= $clrs[$i%count($clrs)] ?>;"></span><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                    <td><?= Calculator::money($cat['total']) ?></td>
                    <td>%<?= number_format($pct,1,',','.') ?></td>
                </tr>
                <?php if (!empty($cat['sub'])): ?>
                <?php foreach ($cat['sub'] as $sub): ?>
                <?php if ($sub['total'] == 0) continue; ?>
                <tr style="color:#888;font-size:10px;">
                    <td style="padding-left:16px;">└ <?= htmlspecialchars($sub['name']) ?></td>
                    <td><?= Calculator::money($sub['total']) ?></td>
                    <td></td>
                </tr>
                <?php
                // "Kredi Ödemesi" alt kategorisi → ödenen kredileri satır satır göster
                if ($sub['slug'] === 'kredi-odeme' && !empty($summary['paid_loans'])):
                    foreach ($summary['paid_loans'] as $loan): ?>
                <tr style="color:#aaa;font-size:9px;">
                    <td style="padding-left:28px;">· <?= htmlspecialchars($loan['title']) ?> <span style="opacity:.6;">(<?= date('d.m.Y', strtotime($loan['entry_date'])) ?>)</span></td>
                    <td><?= Calculator::money(-(float)$loan['amount']) ?></td>
                    <td></td>
                </tr>
                <?php endforeach; endif; ?>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td>Toplam</td>
                    <td><?= Calculator::money($summary['total_expenses']) ?></td>
                    <td>%100</td>
                </tr>
                </tbody>
            </table>
        </div>

        <?php if ($hasDebt): ?>
        <h2 style="margin-top:10px;">Önümüzdeki 3 Ay Borç Takvimi</h2>
        <table class="inner-table">
            <thead><tr><th>Ay</th><th>Krediler</th><th>Taksitler</th><th>Toplam</th></tr></thead>
            <tbody>
            <?php foreach ($upcomingDebts as $ud): ?>
            <tr>
                <td><?= $mn[$ud['month']] ?> <?= $ud['year'] ?></td>
                <td><?= $ud['loans']>0 ? Calculator::money($ud['loans']) : '—' ?></td>
                <td><?= $ud['installments']>0 ? Calculator::money($ud['installments']) : '—' ?></td>
                <td class="bold"><?= $ud['total']>0 ? Calculator::money($ud['total']) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>3 Ay Toplam</td>
                <td><?= Calculator::money($upcomingLoans) ?></td>
                <td><?= Calculator::money($upcomingInstallments) ?></td>
                <td class="bold"><?= Calculator::money($upcomingDebtsTotal) ?></td>
            </tr>
            </tbody>
        </table>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Sağ: Kasa -->
    <div class="col">
        <h2>Kasa Bilgileri</h2>
        <table class="inner-table">
            <tbody>
            <tr><td>Kasa Devir</td><td><?= Calculator::money($summary['cash_carryover']) ?></td></tr>
            <tr><td>Kasa Dahil Toplam</td><td><?= Calculator::money($summary['total_with_cash']) ?></td></tr>
            <tr><td>Avanslar Çıkınca Mevcut</td><td><?= Calculator::money($summary['available_after_advances']) ?></td></tr>
            </tbody>
        </table>

        <h2 style="margin-top:10px;">Gelecek Aya Aktarılan Rezerv</h2>
        <table class="inner-table">
            <tbody>
            <tr><td>Mevcut Rezerv</td><td><?= Calculator::money($summary['reserve_carryover']) ?></td></tr>
            <?php if ($cashPartner): ?>
            <tr><td>+ Bu Ay Kasa Payı</td><td><?= Calculator::money($cashPartner['monthly_salary']) ?></td></tr>
            <?php endif; ?>
            <tr class="total-row"><td>Toplam Rezerv</td><td><?= Calculator::money($nextReserve) ?></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ── 3. ORTAKLAR ── -->
<div class="partners-grid">
    <?php foreach ($realPartners as $p): ?>
    <div class="partner-block">
        <h2><?= htmlspecialchars($p['name']) ?> <span class="sub">Günlük Ort: <?= Calculator::money($p['daily_avg_salary']) ?></span></h2>
        <table class="inner-table">
            <tbody>
            <tr><td>Aylık Ücret</td><td class="bold"><?= Calculator::money($p['monthly_salary']) ?></td></tr>
            <tr><td>Kullanılan Avans</td><td><?= Calculator::money($p['advance_total']) ?></td></tr>
            </tbody>
        </table>

        <?php if (!empty($p['advance_list'])): ?>
        <p class="section-label">Avans Hareketleri</p>
        <table class="inner-table">
            <thead><tr><th>Tarih</th><th>Açıklama</th><th>Tutar</th></tr></thead>
            <tbody>
            <?php foreach ($p['advance_list'] as $adv): ?>
            <tr>
                <td><?= date('d.m.Y', strtotime($adv['advance_date'])) ?></td>
                <td><?= htmlspecialchars($adv['description'] ?: '—') ?></td>
                <td><?= Calculator::money((float)$adv['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <table class="inner-table" style="margin-top:6px;">
            <tbody>
            <tr class="total-row"><td>Ödenecek Bakiye</td><td class="bold"><?= Calculator::money($p['remaining_balance']) ?></td></tr>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
</div>

</div><!-- /#RPT -->

<!-- Donut chart (sadece ekranda anlamlı, print'te canvas görünür) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const c = document.getElementById('donut');
    if (!c) return;
    const data   = <?= json_encode(array_map(fn($x) => round(abs($x['total']),2), $expCats)) ?>;
    const labels = <?= json_encode(array_map(fn($x) => $x['name'], $expCats)) ?>;
    const colors = <?= json_encode($clrs) ?>;
    if (!data.length) return;
    new Chart(c.getContext('2d'), {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors.slice(0,data.length), borderWidth: 1, borderColor: '#fff' }] },
        options: { responsive:false, cutout:'55%', plugins:{ legend:{display:false}, tooltip:{callbacks:{label:x=>' ₺'+x.parsed.toLocaleString('tr-TR',{minimumFractionDigits:0})}} } }
    });
});
</script>

<style>
/* ════ EKRAN PREVİEW ════ */
#RPT {
    background: #fff;
    color: #111;
    font-family: 'Inter', Arial, sans-serif;
    font-size: 12px;
    max-width: 800px;
    margin: 0 auto;
    padding: 24px 28px;
    border: 1px solid #ddd;
    border-radius: 8px;
    line-height: 1.4;
}

.rpt-header { margin-bottom: 12px; border-bottom: 2px solid #111; padding-bottom: 8px; }
.rpt-header h1 { font-size: 16px; font-weight: 800; margin: 0 0 2px; }
.rpt-header p  { font-size: 10px; color: #666; margin: 0; }

h2 { font-size: 11px; font-weight: 700; text-transform: uppercase;
     letter-spacing: 0.4px; color: #333; margin: 0 0 5px;
     border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }

/* Özet tablo */
.summary-table { width:100%; border-collapse:collapse; margin-bottom:12px; }
.summary-table th { background:#f3f4f6; font-size:9px; font-weight:700; text-transform:uppercase;
                    letter-spacing:0.3px; color:#555; padding:5px 8px; border:1px solid #e5e7eb; text-align:center; }
.summary-table td { padding:6px 8px; border:1px solid #e5e7eb; text-align:center; font-weight:600; font-size:12px; }

/* İki sütun */
.two-col { display:grid; grid-template-columns:1.2fr 0.8fr; gap:16px; margin-bottom:12px; }
.col { }

/* İç tablolar */
.inner-table { width:100%; border-collapse:collapse; font-size:11px; }
.inner-table th { background:#f9fafb; font-size:9px; font-weight:700; text-transform:uppercase;
                  letter-spacing:0.3px; color:#666; padding:4px 7px; border:1px solid #e5e7eb; }
.inner-table td { padding:4px 7px; border:1px solid #e5e7eb; color:#222; }
.inner-table td:last-child { text-align:right; font-weight:500; }
.inner-table th:last-child { text-align:right; }
.total-row td { background:#f3f4f6; font-weight:700; }

.dot-inline { display:inline-block; width:7px; height:7px; border-radius:50%; margin-right:5px; vertical-align:middle; }
.bold { font-weight:700 !important; }
.sub  { font-size:9px; font-weight:400; color:#666; margin-left:6px; }
.section-label { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.3px; color:#888; margin:6px 0 3px; }

/* Ortaklar */
.partners-grid { display:grid; grid-template-columns:repeat(<?= count($realPartners) ?>,1fr); gap:14px; }
.partner-block { border:1px solid #e5e7eb; border-radius:6px; padding:10px 12px; }

/* ════ YAZICI ════ */
@media print {
    @page {
        size: A4 portrait;
        margin: 12mm 14mm;
    }

    /* Sadece raporu göster */
    .screen-only { display: none !important; }
    .sidebar, .top-bar, .menu-toggle, .sidebar-overlay,
    .alert, .month-selector { display: none !important; }

    body { background: #fff !important; margin: 0 !important; padding: 0 !important; }
    .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    .content-area { padding: 0 !important; margin: 0 !important; }

    #RPT {
        border: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
        font-size: 10px !important;
    }

    .rpt-header h1 { font-size: 14px !important; }
    h2 { font-size: 9px !important; }
    .summary-table th, .summary-table td { font-size: 9px !important; padding: 4px 6px !important; }
    .inner-table th, .inner-table td { font-size: 9px !important; padding: 3px 5px !important; }
    .two-col { gap: 12px !important; }
    .partners-grid { gap: 10px !important; }
    .partner-block { padding: 7px 9px !important; }
}
</style>
