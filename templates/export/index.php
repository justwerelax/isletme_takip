<?php
$monthNames = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran',
               'Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
?>

<div style="max-width:860px;">

    <!-- Başlık -->
    <div style="margin-bottom:28px;">
        <h2 class="section-title" style="margin-bottom:6px;">
            <i data-lucide="download"></i> Veri Dışa Aktarma
        </h2>
        <p style="color:#94a3b8; font-size:14px; margin:0;">
            Verilerinizi CSV (Excel) veya JSON formatında indirin. CSV dosyaları Excel'de doğrudan açılır.
        </p>
    </div>

    <!-- ── Tam JSON Yedek ─────────────────────────────────────────────────── -->
    <div class="dashboard-big-card" style="margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <div style="font-size:16px; font-weight:600; margin-bottom:4px;">
                    📦 Tam Yedek (JSON)
                </div>
                <div style="font-size:13px; color:#94a3b8;">
                    Tüm veriler tek dosyada — aylar, girişler, giderler, avanslar, taksitler, krediler, ortaklar.
                    Olası bir veri kaybına karşı düzenli alın.
                </div>
            </div>
            <a href="?page=export&action=fullBackupJson"
               class="btn btn-primary"
               style="white-space:nowrap;">
                <i data-lucide="archive"></i> JSON İndir
            </a>
        </div>
    </div>

    <!-- ── Aylık Özet CSV ─────────────────────────────────────────────────── -->
    <div class="dashboard-big-card" style="margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <div style="font-size:16px; font-weight:600; margin-bottom:4px;">
                    📊 Aylık Özet (CSV)
                </div>
                <div style="font-size:13px; color:#94a3b8;">
                    Her ay için cüro, gider, kâr, çalışma günü özeti. Excel'de grafik çizmek için ideal.
                </div>
            </div>
            <a href="?page=export&action=summaryCsv"
               class="btn btn-primary"
               style="white-space:nowrap;">
                <i data-lucide="table-2"></i> CSV İndir
            </a>
        </div>
    </div>

    <!-- ── Günlük Girişler CSV ────────────────────────────────────────────── -->
    <div class="dashboard-big-card" style="margin-bottom:20px;">
        <div style="font-size:16px; font-weight:600; margin-bottom:4px;">
            📅 Günlük Girişler (CSV)
        </div>
        <div style="font-size:13px; color:#94a3b8; margin-bottom:16px;">
            Tarih bazlı gelir ve gider detayları. Belirli bir ay seçebilir veya tümünü indirebilirsiniz.
        </div>
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <select id="entriesMonth" class="select-input" style="width:auto;">
                <option value="">— Tüm Aylar —</option>
                <?php foreach ($months as $m): ?>
                <option value="<?= $m['year'] ?>-<?= $m['month'] ?>">
                    <?= $monthNames[$m['month']] ?> <?= $m['year'] ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button onclick="downloadEntries()" class="btn btn-primary">
                <i data-lucide="download"></i> CSV İndir
            </button>
        </div>
    </div>

    <!-- ── Avanslar CSV ───────────────────────────────────────────────────── -->
    <div class="dashboard-big-card" style="margin-bottom:20px;">
        <div style="font-size:16px; font-weight:600; margin-bottom:4px;">
            💰 Avanslar (CSV)
        </div>
        <div style="font-size:13px; color:#94a3b8; margin-bottom:16px;">
            Ortak avans kayıtları. Belirli bir ay seçebilir veya tümünü indirebilirsiniz.
        </div>
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <select id="advancesMonth" class="select-input" style="width:auto;">
                <option value="">— Tüm Aylar —</option>
                <?php foreach ($months as $m): ?>
                <option value="<?= $m['year'] ?>-<?= $m['month'] ?>">
                    <?= $monthNames[$m['month']] ?> <?= $m['year'] ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button onclick="downloadAdvances()" class="btn btn-primary">
                <i data-lucide="download"></i> CSV İndir
            </button>
        </div>
    </div>

    <!-- ── Taksitler & Krediler CSV ───────────────────────────────────────── -->
    <div class="dashboard-big-card" style="margin-bottom:20px;">
        <div style="font-size:16px; font-weight:600; margin-bottom:16px;">
            🏦 Taksitler & Krediler (CSV)
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="?page=export&action=installmentsCsv&category=installment" class="btn btn-ghost">
                <i data-lucide="list-checks"></i> Taksitli Borçlar
            </a>
            <a href="?page=export&action=installmentsCsv&category=loan" class="btn btn-ghost">
                <i data-lucide="landmark"></i> Krediler
            </a>
        </div>
    </div>

</div>

<script>
function downloadEntries() {
    const val = document.getElementById('entriesMonth').value;
    let url = '?page=export&action=entriesCsv';
    if (val) {
        const [year, month] = val.split('-');
        url += '&year=' + year + '&month=' + month;
    }
    window.location.href = url;
}

function downloadAdvances() {
    const val = document.getElementById('advancesMonth').value;
    let url = '?page=export&action=advancesCsv';
    if (val) {
        const [year, month] = val.split('-');
        url += '&year=' + year + '&month=' + month;
    }
    window.location.href = url;
}
</script>
