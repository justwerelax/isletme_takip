<?php
$monthNames = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
$availableMonths = Database::fetchAll("SELECT * FROM months ORDER BY year DESC, month DESC");
?>

<!-- Üst bar -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
    <h2 class="section-title" style="margin:0;"><i data-lucide="bar-chart-2"></i> Aylık Performans Karşılaştırması</h2>
    <?php if (!empty($availableMonths)): ?>
    <form method="GET" style="display:flex; gap:8px; align-items:center;">
        <input type="hidden" name="page" value="reports">
        <input type="hidden" name="action" value="monthlyReport">
        <select name="month" id="reportMonthSel" class="select-input" style="width:auto;">
            <?php foreach ($availableMonths as $m): ?>
            <option value="<?= $m['month'] ?>" data-year="<?= $m['year'] ?>">
                <?= $monthNames[$m['month']] ?> <?= $m['year'] ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="year" id="reportYear" value="<?= $availableMonths[0]['year'] ?? date('Y') ?>">
        <button type="submit" class="btn btn-primary btn-sm">
            <i data-lucide="file-text"></i> Ay Sonu Raporu
        </button>
    </form>
    <?php endif; ?>
</div>

<!-- Grafik -->
<div class="dashboard-big-card" style="margin-bottom:24px;">
    <div style="height:350px;">
        <canvas id="comparisonChart"></canvas>
    </div>
</div>

<script>
document.getElementById('reportMonthSel')?.addEventListener('change', function() {
    document.getElementById('reportYear').value = this.options[this.selectedIndex].dataset.year;
});

document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('comparisonChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [
                { label: 'Cüro', data: <?= json_encode($chartCuro) ?>, backgroundColor: 'rgba(56,189,248,0.8)', borderRadius: 4 },
                { label: 'Kâr',  data: <?= json_encode($chartProfit) ?>, backgroundColor: 'rgba(34,197,94,0.8)', borderRadius: 4 },
                { label: 'Gider', data: <?= json_encode(array_map('abs', $chartExpense)) ?>, backgroundColor: 'rgba(239,68,68,0.8)', borderRadius: 4 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { color: '#94a3b8' } },
                tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ₺' + ctx.parsed.y.toLocaleString('tr-TR') } }
            },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8', callback: v => '₺' + (v/1000).toFixed(0) + 'K' } }
            }
        }
    });
});
</script>
