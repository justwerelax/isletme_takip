<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="İşletme Takip Sistemi - Gelir, gider ve kâr takibi">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — İşletme Takip</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <div class="logo-icon">İT</div>
                <span class="logo-text">İşletme Takip</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="?page=dashboard" class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard"></i>
                <span>Dashboard</span>
            </a>
            <a href="?page=entries" class="nav-item <?= ($currentPage ?? '') === 'entries' ? 'active' : '' ?>">
                <i data-lucide="file-text"></i>
                <span>Günlük Girişler</span>
            </a>
            <a href="?page=pos" class="nav-item <?= ($currentPage ?? '') === 'pos' ? 'active' : '' ?>">
                <i data-lucide="credit-card"></i>
                <span>POS İşlemleri</span>
            </a>
            <a href="?page=advances" class="nav-item <?= ($currentPage ?? '') === 'advances' ? 'active' : '' ?>">
                <i data-lucide="wallet"></i>
                <span>Avanslar</span>
            </a>
            <a href="?page=installments" class="nav-item <?= ($currentPage ?? '') === 'installments' ? 'active' : '' ?>">
                <i data-lucide="list-checks"></i>
                <span>Taksitli Borçlar</span>
            </a>
            <a href="?page=loans" class="nav-item <?= ($currentPage ?? '') === 'loans' ? 'active' : '' ?>">
                <i data-lucide="landmark"></i>
                <span>Krediler</span>
            </a>
            <a href="?page=months" class="nav-item <?= ($currentPage ?? '') === 'months' ? 'active' : '' ?>">
                <i data-lucide="calendar"></i>
                <span>Ay Yönetimi</span>
            </a>
            <a href="?page=reports" class="nav-item <?= ($currentPage ?? '') === 'reports' ? 'active' : '' ?>">
                <i data-lucide="bar-chart-3"></i>
                <span>Raporlar</span>
            </a>
            <a href="?page=export" class="nav-item <?= ($currentPage ?? '') === 'export' ? 'active' : '' ?>">
                <i data-lucide="download"></i>
                <span>Dışa Aktar</span>
            </a>
            <a href="?page=tasks" class="nav-item <?= ($currentPage ?? '') === 'tasks' ? 'active' : '' ?>">
                <i data-lucide="clipboard-list"></i>
                <span>Yapılacaklar</span>
            </a>
            <a href="?page=staff" class="nav-item <?= ($currentPage ?? '') === 'staff' ? 'active' : '' ?>">
                <i data-lucide="users"></i>
                <span>Personel</span>
            </a>
            <?php if (Auth::isAdmin()): ?>
            <div class="nav-divider"></div>
            <a href="?page=settings" class="nav-item <?= ($currentPage ?? '') === 'settings' ? 'active' : '' ?>">
                <i data-lucide="settings"></i>
                <span>Ayarlar</span>
            </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= mb_substr($user['full_name'] ?? 'U', 0, 1) ?></div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($user['full_name'] ?? '') ?></span>
                    <span class="user-role"><?= ($user['role'] ?? '') === 'admin' ? 'Yönetici' : 'Görüntüleyici' ?></span>
                </div>
            </div>
            <a href="?page=logout" class="nav-item logout-btn">
                <i data-lucide="log-out"></i>
                <span>Çıkış</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="top-bar">
            <button class="menu-toggle" id="menuToggle">
                <i data-lucide="menu"></i>
            </button>
            <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
            <div class="top-bar-right">
                <span class="current-date">
                    <i data-lucide="clock" style="width:16px;height:16px"></i>
                    <?php
                        $months_tr = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
                        echo date('d') . ' ' . ($months_tr[(int)date('n')] ?? '') . ' ' . date('Y');
                    ?>
                </span>

                <!-- Versiyon Rozeti -->
                <div class="version-badge-wrap" id="versionWrap">
                    <button class="version-badge" id="versionBtn" onclick="toggleChangelog()" title="Sürüm Geçmişi">
                        <i data-lucide="tag" style="width:12px;height:12px;"></i>
                        v2.5.2
                    </button>

                    <div class="changelog-dropdown" id="changelogDropdown">
                        <div class="changelog-header">
                            <span><i data-lucide="history" style="width:14px;height:14px;vertical-align:middle;margin-right:5px;"></i>Sürüm Geçmişi</span>
                            <button onclick="toggleChangelog()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:16px;line-height:1;">×</button>
                        </div>
                        <div class="changelog-body">

                            <?php
                            $changelog = [
                                [
                                    'version' => 'v2.5.2',
                                    'date'    => '23 Mayıs 2026',
                                    'type'    => 'feature',
                                    'label'   => 'Yeni Özellik',
                                    'items'   => [
                                        'Personel: "Maaş Dışı" ödeme tipi eklendi (deneme günü, özel ödeme)',
                                        'Maaş dışı ödemeler hakediş hesabına dahil edilmez',
                                        'Günlük girişlerde her maaşlı personele "Maaş dışı" checkbox eklendi',
                                        'Personel detay tablosunda Avans / Maaş Dışı rozeti gösterimi',
                                        'staff_expenses tablosuna is_salary sütunu eklendi',
                                    ],
                                ],
                                [
                                    'version' => 'v2.5.1',
                                    'date'    => '23 Mayıs 2026',
                                    'type'    => 'fix',
                                    'label'   => 'Düzeltme',
                                    'items'   => [
                                        'Personel hakediş: ayın ortasında işe girenler için start_date\'ten itibaren sayılıyor',
                                        'Maaşlı personel footer: "X gün çalışma" → "X avans ödemesi" olarak düzeltildi',
                                        'Hakediş detayında işe giriş tarihi gösteriliyor (o ay içinde girdiyse)',
                                    ],
                                ],
                                [
                                    'version' => 'v2.5.0',
                                    'date'    => '23 Mayıs 2026',
                                    'type'    => 'feature',
                                    'label'   => 'Yeni Özellik',
                                    'items'   => [
                                        'Personel: ay seçici + aylık ödeme kırılımı (expandable satırlar)',
                                        'Personel: hakediş hesabı — geçen takvim günü × (maaş ÷ 30)',
                                        'Personel: kalan bakiye = hakediş − ödenen avanslar',
                                        'Personel: özet kartlar (toplam ödeme, bordo, hakediş kalan)',
                                        'Personel: maaşlıda "avans ödemesi", gündelikçide "gün çalışma" etiketi',
                                        'POS sistemi sadeleştirildi: tek banka, otomatik komisyon kaldırıldı',
                                        'Türkçe sayı parse düzeltmesi: "2.356,04" → 2356.04 doğru kaydediliyor',
                                        'Gider toplaması blur\'da binlik nokta olmadan yazılıyor (PHP parse sorunu giderildi)',
                                    ],
                                ],
                                [
                                    'version' => 'v2.4.1',
                                    'date'    => '22 Mayıs 2026',
                                    'type'    => 'fix',
                                    'label'   => 'Düzeltme',
                                    'items'   => [
                                        'Kredi gider açıklaması artık "otomatik_odeme" yerine kredi adını gösteriyor',
                                        'Aylık raporda Kredi Ödemesi alt kategorisi altında hangi kredininin ödendiği görünüyor',
                                        'Ghost fabrika gider kaydı temizlendi',
                                    ],
                                ],
                                [
                                    'version' => 'v2.4.0',
                                    'date'    => '22 Mayıs 2026',
                                    'type'    => 'feature',
                                    'label'   => 'Yeni Özellik',
                                    'items'   => [
                                        'Krediler: ödeme tarihini seçerek ilgili günün giderine ekle',
                                        'Taksitler: ay sonu toplu gider yazma (yalnızca taksit kategorisi)',
                                        'Fabrika altında "Kredi Ödemesi" alt kategorisi oluşturuldu',
                                        'Halkbank POS komisyon oranı POS sayfasından ayarlanabiliyor',
                                        'Kredi geri alma: expense_entry_id ile tam ve doğru silme',
                                    ],
                                ],
                                [
                                    'version' => 'v2.3.0',
                                    'date'    => '21 Mayıs 2026',
                                    'type'    => 'feature',
                                    'label'   => 'Yeni Özellik',
                                    'items'   => [
                                        'POS sayfası: Normal POS ve Halkbank POS ayrı tablolara bölündü',
                                        'Ay Genel Özeti: iki banka brüt/komisyon/net ayrı gösteriliyor',
                                        'Kalan bakiyeler banka bazında ayrı hesaplanıyor',
                                        'Halkbank komisyon oranı sidebar\'dan ayarlanabiliyor',
                                    ],
                                ],
                                [
                                    'version' => 'v2.2.0',
                                    'date'    => '20 Mayıs 2026',
                                    'type'    => 'feature',
                                    'label'   => 'Yeni Özellik',
                                    'items'   => [
                                        'POS: Otomatik Halkbank komisyon gider yazımı kaldırıldı',
                                        'Seçili günlerin komisyonunu belirli bir tarihin giderine ekle (checkbox + tarih)',
                                        'Komisyon geri alma butonu eklendi',
                                        'Halkbank POS komisyonu hesaplamaya dahil edildi',
                                        'POS tablosuna "Tip" kolonu eklendi (POS / HB rozeti)',
                                    ],
                                ],
                                [
                                    'version' => 'v2.1.0',
                                    'date'    => '19 Mayıs 2026',
                                    'type'    => 'feature',
                                    'label'   => 'Yeni Özellik',
                                    'items'   => [
                                        'Günlük Girişler: Personel gideri açılır detayda kişi kırılımıyla gösteriliyor',
                                        'Gider alanında "1500+1200+300" yazınca otomatik topluyor',
                                        'Her gider alanının altına açıklama (not) eklenebiliyor',
                                        'Açıklamalar günlük giriş detay satırında italik gösteriliyor',
                                    ],
                                ],
                                [
                                    'version' => 'v2.0.0',
                                    'date'    => 'Başlangıç',
                                    'type'    => 'base',
                                    'label'   => 'Temel Sürüm',
                                    'items'   => [
                                        'Dashboard, Günlük Girişler, POS İşlemleri',
                                        'Taksitli Borçlar ve Krediler modülü',
                                        'Ay Yönetimi, Raporlar, Dışa Aktarma',
                                        'Personel, Avans, Ayarlar modülleri',
                                    ],
                                ],
                            ];
                            $typeColors = [
                                'fix'     => ['bg' => 'rgba(245,158,11,0.15)',  'color' => '#f59e0b'],
                                'feature' => ['bg' => 'rgba(14,165,233,0.15)',  'color' => '#38bdf8'],
                                'base'    => ['bg' => 'rgba(100,116,139,0.15)', 'color' => '#94a3b8'],
                            ];
                            foreach ($changelog as $idx => $entry):
                                $tc = $typeColors[$entry['type']] ?? $typeColors['feature'];
                            ?>
                            <div class="cl-version-block <?= $idx === 0 ? 'cl-current' : '' ?>">
                                <div class="cl-version-head">
                                    <span class="cl-version-num"><?= $entry['version'] ?></span>
                                    <span class="cl-type-badge" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>;"><?= $entry['label'] ?></span>
                                    <span class="cl-date"><?= $entry['date'] ?></span>
                                </div>
                                <ul class="cl-items">
                                    <?php foreach ($entry['items'] as $item): ?>
                                    <li><?= htmlspecialchars($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endforeach; ?>

                        </div>
                    </div>
                </div>
                <!-- /Versiyon Rozeti -->

            </div>
        </header>

        <div class="content-area">
            <?php if ($flashSuccess = Auth::flash('success')): ?>
                <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
            <?php endif; ?>
            <?php if ($flashError = Auth::flash('error')): ?>
                <div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div>
            <?php endif; ?>

            <?php 
                $view = $viewPath ?? ($currentPage ?? 'dashboard');
                if (str_ends_with($view, '.php')) {
                    require BASE_PATH . '/templates/' . $view;
                } else {
                    require BASE_PATH . '/templates/' . $view . '/index.php';
                }
            ?>
        </div>
    </main>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script src="assets/js/app.js?v=<?= time() ?>"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
