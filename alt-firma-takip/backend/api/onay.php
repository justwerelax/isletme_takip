<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';
require_once __DIR__ . '/../models/Job.php';
require_once __DIR__ . '/../models/Payment.php';

$user   = AuthMiddleware::requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts  = explode('/', trim($path, '/'));
$db     = Database::getInstance()->getConnection();

$onayIdx = array_search('onay', $parts);
$onayId  = ($onayIdx !== false && isset($parts[$onayIdx + 1]) && is_numeric($parts[$onayIdx + 1]))
           ? (int)$parts[$onayIdx + 1] : null;
$action  = ($onayId && isset($parts[$onayIdx + 2])) ? $parts[$onayIdx + 2] : null;

// ── POST /api/onay — Firma yeni onay talebi gönderir ─────────────────────────
if ($method === 'POST' && $onayId === null) {
    $input = json_decode(file_get_contents('php://input'), true);
    $tip   = $input['tip'] ?? null; // 'teslim' veya 'odeme'

    if (!in_array($tip, ['teslim', 'odeme'])) Response::error('Geçersiz tip', 'INVALID', 400);

    $stmt = $db->prepare("INSERT INTO onay_bekleyen (alt_firma_id, tip, is_id, tutar, tarih, aciklama)
                          VALUES (:alt_firma_id, :tip, :is_id, :tutar, :tarih, :aciklama)");
    $stmt->execute([
        'alt_firma_id' => $input['alt_firma_id'],
        'tip'          => $tip,
        'is_id'        => $input['is_id']   ?? null,
        'tutar'        => $input['tutar']   ?? null,
        'tarih'        => $input['tarih']   ?? null,
        'aciklama'     => $input['aciklama'] ?? null,
    ]);

    Response::success(['id' => $db->lastInsertId()], 'Onay talebiniz gönderildi', 201);
}

// ── GET /api/onay — Admin bekleyen talepleri listeler ────────────────────────
if ($method === 'GET' && $onayId === null) {
    $durum = $_GET['durum'] ?? 'bekliyor';
    $stmt  = $db->prepare("
        SELECT o.*, f.ad as firma_ad
        FROM onay_bekleyen o
        JOIN alt_firma f ON f.id = o.alt_firma_id
        WHERE o.durum = :durum
        ORDER BY o.created_at DESC
    ");
    $stmt->execute(['durum' => $durum]);
    Response::success(['talepler' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// ── PUT /api/onay/{id}/onayla — Admin onaylar ────────────────────────────────
if ($method === 'PUT' && $onayId !== null && $action === 'onayla') {
    $stmt = $db->prepare("SELECT * FROM onay_bekleyen WHERE id = :id AND durum = 'bekliyor'");
    $stmt->execute(['id' => $onayId]);
    $talep = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$talep) Response::notFound('Talep bulunamadı veya zaten işlendi');

    if ($talep['tip'] === 'teslim') {
        // İşi teslim edildi olarak işaretle
        $jobModel = new Job();
        $jobModel->toggleTeslim($talep['is_id'], 1);
    } elseif ($talep['tip'] === 'odeme') {
        // Ödeme kaydı oluştur
        $stmt2 = $db->prepare("INSERT INTO para_hareketleri (alt_firma_id, tarih, tutar, hareket_tipi, aciklama)
                                VALUES (:alt_firma_id, :tarih, :tutar, 'odeme', :aciklama)");
        $stmt2->execute([
            'alt_firma_id' => $talep['alt_firma_id'],
            'tarih'        => $talep['tarih'],
            'tutar'        => $talep['tutar'],
            'aciklama'     => $talep['aciklama'] ?? 'Firma ödemesi',
        ]);
    }

    $db->prepare("UPDATE onay_bekleyen SET durum='onaylandi' WHERE id=:id")->execute(['id' => $onayId]);
    Response::success(null, 'Onaylandı');
}

// ── PUT /api/onay/{id}/reddet — Admin reddeder ───────────────────────────────
if ($method === 'PUT' && $onayId !== null && $action === 'reddet') {
    $db->prepare("UPDATE onay_bekleyen SET durum='reddedildi' WHERE id=:id")->execute(['id' => $onayId]);
    Response::success(null, 'Reddedildi');
}

Response::error('Geçersiz endpoint', 'INVALID_ENDPOINT', 404);
