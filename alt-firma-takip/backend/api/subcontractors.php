<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../models/Subcontractor.php';
require_once __DIR__ . '/../models/Job.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';

$user   = AuthMiddleware::requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts  = explode('/', trim($path, '/'));

$subcontractorId = null;
$action          = null;
$idx = array_search('subcontractors', $parts);
if ($idx !== false && isset($parts[$idx + 1]) && is_numeric($parts[$idx + 1])) {
    $subcontractorId = (int) $parts[$idx + 1];
    if (isset($parts[$idx + 2])) $action = $parts[$idx + 2];
}

$model = new Subcontractor();

// ── GET /api/subcontractors ──────────────────────────────────────────────────
if ($method === 'GET' && $subcontractorId === null) {
    $subs = $model->getAll();
    $totalDebt = $totalCredit = 0;
    foreach ($subs as &$s) {
        $s['balance'] = $model->calculateBalance($s['id']);
        if ($s['balance'] > 0) $totalDebt   += $s['balance'];
        else                   $totalCredit += abs($s['balance']);
    }
    Response::success([
        'subcontractors' => $subs,
        'summary'        => ['totalDebt' => $totalDebt, 'totalCredit' => $totalCredit]
    ]);
}

// ── GET /api/subcontractors/{id} ─────────────────────────────────────────────
if ($method === 'GET' && $subcontractorId !== null && $action === null) {
    $sub = $model->getById($subcontractorId);
    if (!$sub) Response::notFound('Alt firma bulunamadı');

    $jobModel     = new Job();
    $paymentModel = new Payment();

    $jobs     = $jobModel->getBySubcontractor($subcontractorId);
    $payments = $paymentModel->getBySubcontractor($subcontractorId);
    $summary  = $model->getBalanceSummary($subcontractorId);

    $sub['balance'] = $summary['mevcut_borc'];

    // İstatistikler
    $kendiM2       = 0;  // sadece kendi işlerinin m²'si
    $bizimAdres    = 0;  // bizim işlerimiz sayısı (bizden verilen adres)
    $firmaKari     = 0;  // komisyon bazlı bizim işlerden firma karı (komisyon_tutari)
    $toplamOdeme   = 0;  // yapılan ödemeler toplamı

    foreach ($jobs as $j) {
        if ($j['is_tipi'] === 'kendi_isi') {
            $kendiM2 += (float) $j['metrekare'];
        }
        if ($j['is_tipi'] === 'bizim_isimiz') {
            $bizimAdres++;
            // Firma karı = müşteri tutarı - firmaya ödenen (bizim payımız)
            if ($j['odeme_tipi'] === 'komisyon_bazli') {
                // komisyon bazlı: toplam_tutar = sipariş tutarı, komisyon_tutari = bizim payımız
                $firmaKari += (float) $j['komisyon_tutari'];
            } elseif ($j['odeme_tipi'] === 'm2_bazli') {
                // m² bazlı: musteri_tutari varsa kullan, yoksa toplam_tutar
                $musteriTutari = isset($j['musteri_tutari']) && $j['musteri_tutari'] > 0
                    ? (float) $j['musteri_tutari']
                    : (float) $j['toplam_tutar'];
                // firma karı = müşteri tutarı - bize ödediği (toplam_tutar)
                $firmaKari += $musteriTutari - (float) $j['toplam_tutar'];
            }
        }
    }

    foreach ($payments as $p) {
        if ($p['hareket_tipi'] === 'odeme') {
            $toplamOdeme += (float) $p['tutar'];
        }
    }

    Response::success([
        'subcontractor' => $sub,
        'jobs'          => $jobs,
        'payments'      => $payments,
        'summary'       => [
            'kendi_isleri_toplam'    => $summary['kendi_isleri_toplam'],
            'bizim_islerimiz_toplam' => $summary['bizim_islerimiz_toplam'],
            'mevcut_borc'            => $summary['mevcut_borc'],
            'kendiM2'                => $kendiM2,
            'bizimAdres'             => $bizimAdres,
            'firmaKari'              => $firmaKari,
            'toplamOdeme'            => $toplamOdeme,
            'jobCount'               => count($jobs),
        ]
    ]);
}

// ── POST /api/subcontractors ─────────────────────────────────────────────────
if ($method === 'POST' && $subcontractorId === null) {
    $input = json_decode(file_get_contents('php://input'), true);

    $v = Validator::make();
    $v->required($input['ad'] ?? null, 'ad');
    if ($v->fails()) Response::validationError($v->getErrors());

    $id = $model->create($input);
    if (!$id) Response::serverError('Alt firma oluşturulamadı');

    Response::success(['id' => $id], 'Alt firma başarıyla eklendi', 201);
}

// ── PUT /api/subcontractors/{id} ─────────────────────────────────────────────
if ($method === 'PUT' && $subcontractorId !== null && $action === null) {
    if (!$model->getById($subcontractorId)) Response::notFound('Alt firma bulunamadı');

    $input = json_decode(file_get_contents('php://input'), true);
    $v = Validator::make();
    $v->required($input['ad'] ?? null, 'ad');
    if ($v->fails()) Response::validationError($v->getErrors());

    if (!$model->update($subcontractorId, $input)) Response::serverError('Alt firma güncellenemedi');
    Response::success(null, 'Alt firma bilgileri güncellendi');
}

// ── PATCH /api/subcontractors/{id}/status ────────────────────────────────────
if ($method === 'PATCH' && $subcontractorId !== null && $action === 'status') {
    if (!$model->getById($subcontractorId)) Response::notFound('Alt firma bulunamadı');
    $newStatus = $model->toggleStatus($subcontractorId);
    if (!$newStatus) Response::serverError('Durum güncellenemedi');
    Response::success(['newStatus' => $newStatus], 'Durum güncellendi');
}

// ── DELETE /api/subcontractors/{id} ──────────────────────────────────────────
if ($method === 'DELETE' && $subcontractorId !== null && $action === null) {
    if (!$model->getById($subcontractorId)) Response::notFound('Alt firma bulunamadı');
    if (!$model->delete($subcontractorId)) Response::serverError('Alt firma silinemedi');
    Response::success(null, 'Alt firma silindi');
}

Response::error('Geçersiz endpoint', 'INVALID_ENDPOINT', 404);
