<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../models/Subcontractor.php';
require_once __DIR__ . '/../models/Job.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';

$user = AuthMiddleware::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Sadece GET desteklenir', 'METHOD_NOT_ALLOWED', 405);
}

// Format: json (default) veya csv
$format = strtolower($_GET['format'] ?? 'json');

// Opsiyonel: belirli bir alt firma
$subId = isset($_GET['sub_id']) && is_numeric($_GET['sub_id']) ? (int)$_GET['sub_id'] : null;

$subModel  = new Subcontractor();
$jobModel  = new Job();
$payModel  = new Payment();

// Alt firmaları çek
$subs = $subId ? [] : $subModel->getAll();
if ($subId) {
    $s = $subModel->getById($subId);
    if ($s) $subs = [$s];
}

// Her alt firma için işler ve ödemeleri ekle
$exportData = [];
foreach ($subs as $sub) {
    $jobs     = $jobModel->getBySubcontractor($sub['id']);
    $payments = $payModel->getBySubcontractor($sub['id']);
    $summary  = $subModel->getBalanceSummary($sub['id']);

    $exportData[] = [
        'firma'    => $sub,
        'ozet'     => $summary,
        'isler'    => $jobs,
        'odemeler' => $payments,
    ];
}

$exportedAt = date('Y-m-d H:i:s');

// ── JSON ──────────────────────────────────────────────────────────────────────
if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="alt-firma-yedek-' . date('Y-m-d') . '.json"');
    echo json_encode([
        'export_tarihi' => $exportedAt,
        'versiyon'      => '1.0',
        'toplam_firma'  => count($exportData),
        'firmalar'      => $exportData,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ── CSV ───────────────────────────────────────────────────────────────────────
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="alt-firma-yedek-' . date('Y-m-d') . '.csv"');

    // UTF-8 BOM — Excel'in Türkçe karakterleri doğru okuması için
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    // ── Bölüm 1: Alt Firmalar ─────────────────────────────────────────────────
    fputcsv($out, ['=== ALT FİRMALAR ==='], ';');
    fputcsv($out, ['ID', 'Ad', 'Telefon', 'Adres', 'Birim Fiyat', 'Komisyon Oranı', 'Durum', 'Kayıt Tarihi'], ';');
    foreach ($exportData as $row) {
        $f = $row['firma'];
        fputcsv($out, [
            $f['id'],
            $f['ad'],
            $f['telefon'] ?? '',
            $f['adres'] ?? '',
            $f['birim_fiyat'] ?? 0,
            $f['komisyon_orani'] ?? 0,
            $f['durum'],
            $f['created_at'],
        ], ';');
    }

    fputcsv($out, [], ';');

    // ── Bölüm 2: Yıkama İşleri ───────────────────────────────────────────────
    fputcsv($out, ['=== YIKAMA İŞLERİ ==='], ';');
    fputcsv($out, [
        'ID', 'Alt Firma ID', 'Alt Firma Adı', 'İş Tipi', 'Ödeme Tipi',
        'Tarih', 'Metrekare', 'Birim Fiyat', 'Toplam Tutar', 'Müşteri Tutarı',
        'Teslimat Tipi', 'Komisyon Oranı', 'Komisyon Tutarı',
        'Teslim Edildi', 'Açıklama', 'Kayıt Tarihi'
    ], ';');
    foreach ($exportData as $row) {
        $firmaAd = $row['firma']['ad'];
        foreach ($row['isler'] as $j) {
            fputcsv($out, [
                $j['id'],
                $j['alt_firma_id'],
                $firmaAd,
                $j['is_tipi'],
                $j['odeme_tipi'] ?? '',
                $j['tarih'],
                $j['metrekare'],
                $j['birim_fiyat'],
                $j['toplam_tutar'],
                $j['musteri_tutari'] ?? '',
                $j['teslimat_tipi'] ?? '',
                $j['komisyon_orani'],
                $j['komisyon_tutari'],
                $j['teslim_edildi'] ? 'Evet' : 'Hayır',
                $j['aciklama'] ?? '',
                $j['created_at'],
            ], ';');
        }
    }

    fputcsv($out, [], ';');

    // ── Bölüm 3: Para Hareketleri ─────────────────────────────────────────────
    fputcsv($out, ['=== PARA HAREKETLERİ ==='], ';');
    fputcsv($out, ['ID', 'Alt Firma ID', 'Alt Firma Adı', 'Tarih', 'Tutar', 'Hareket Tipi', 'Açıklama', 'Kayıt Tarihi'], ';');
    foreach ($exportData as $row) {
        $firmaAd = $row['firma']['ad'];
        foreach ($row['odemeler'] as $p) {
            fputcsv($out, [
                $p['id'],
                $p['alt_firma_id'],
                $firmaAd,
                $p['tarih'],
                $p['tutar'],
                $p['hareket_tipi'],
                $p['aciklama'] ?? '',
                $p['created_at'],
            ], ';');
        }
    }

    fputcsv($out, [], ';');

    // ── Bölüm 4: Bakiye Özeti ─────────────────────────────────────────────────
    fputcsv($out, ['=== BAKİYE ÖZETİ ==='], ';');
    fputcsv($out, ['Alt Firma', 'Kendi İşleri Toplamı', 'Bizim İşlerimiz Toplamı', 'Ödemeler', 'Mevcut Borç'], ';');
    foreach ($exportData as $row) {
        $oz = $row['ozet'];
        fputcsv($out, [
            $row['firma']['ad'],
            $oz['kendi_isleri_toplam'],
            $oz['bizim_islerimiz_toplam'],
            $oz['odemeler'],
            $oz['mevcut_borc'],
        ], ';');
    }

    fputcsv($out, [
        '',
        'Dışa Aktarma Tarihi: ' . $exportedAt,
    ], ';');

    fclose($out);
    exit;
}

Response::error('Geçersiz format. json veya csv kullanın.', 'INVALID_FORMAT', 400);
