<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../models/Job.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';

$user = AuthMiddleware::requireAuth();

$method   = $_SERVER['REQUEST_METHOD'];
$path     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts    = explode('/', trim($path, '/'));
$jobId    = null;
$jobsIdx  = array_search('jobs', $parts);
if ($jobsIdx !== false && isset($parts[$jobsIdx + 1]) && is_numeric($parts[$jobsIdx + 1])) {
    $jobId = (int) $parts[$jobsIdx + 1];
}

$jobModel = new Job();

// ── POST /api/jobs ────────────────────────────────────────────────────────────
if ($method === 'POST' && $jobId === null) {
    $input  = json_decode(file_get_contents('php://input'), true);
    $isTipi = $input['is_tipi'] ?? 'bizim_isimiz';

    $v = Validator::make();
    $v->required($input['alt_firma_id'] ?? null, 'alt_firma_id');
    $v->required($input['tarih']        ?? null, 'tarih');

    // birim_fiyat komisyon bazlı dışında zorunlu
    $odemeTipi = $input['odeme_tipi'] ?? null;
    if ($odemeTipi !== 'komisyon_bazli') {
        $v->required($input['birim_fiyat'] ?? null, 'birim_fiyat');
    }
    if (!isset($input['birim_fiyat'])) $input['birim_fiyat'] = 0;
    if (!isset($input['metrekare']))   $input['metrekare']   = 0;

    // teslimat_tipi sadece bizim işimizde zorunlu
    if ($isTipi === 'bizim_isimiz') {
        if (!isset($input['komisyon_orani'])) {
            $input['komisyon_orani'] = 0.4;
        }
    }

    // numeric/positive kontrolü sadece değer girilmişse ve komisyon bazlı değilse
    if ($odemeTipi !== 'komisyon_bazli') {
        if ($input['metrekare'] > 0)   { $v->numeric($input['metrekare'],  'metrekare');  $v->positive($input['metrekare'],  'metrekare'); }
        if ($input['birim_fiyat'] > 0) { $v->numeric($input['birim_fiyat'],'birim_fiyat');$v->positive($input['birim_fiyat'],'birim_fiyat'); }
    }
    if (isset($input['tarih'])) { $v->date($input['tarih'], 'tarih'); }

    if ($v->fails()) Response::validationError($v->getErrors());

    $result = $jobModel->create($input);
    if (!$result) Response::serverError('İş kaydı oluşturulamadı');

    Response::success([
        'id'              => $result['id'],
        'toplam_tutar'    => $result['toplam_tutar'],
        'komisyon_orani'  => $result['komisyon_orani'],
        'komisyon_tutari' => $result['komisyon_tutari'],
    ], 'İş kaydı başarıyla eklendi', 201);
}

// ── PUT /api/jobs/{id} ────────────────────────────────────────────────────────
if ($method === 'PUT' && $jobId !== null) {
    $job = $jobModel->getById($jobId);
    if (!$job) Response::notFound('İş kaydı bulunamadı');

    $input  = json_decode(file_get_contents('php://input'), true);
    $isTipi = $input['is_tipi'] ?? $job['is_tipi'] ?? 'bizim_isimiz';

    $v = Validator::make();
    $v->required($input['tarih'] ?? null, 'tarih');

    $odemeTipi = $input['odeme_tipi'] ?? null;
    if ($odemeTipi !== 'komisyon_bazli') {
        $v->required($input['birim_fiyat'] ?? null, 'birim_fiyat');
    }
    if (!isset($input['birim_fiyat'])) $input['birim_fiyat'] = 0;
    if (!isset($input['metrekare']))   $input['metrekare']   = 0;

    if ($isTipi === 'bizim_isimiz' && !isset($input['komisyon_orani'])) {
        $input['komisyon_orani'] = 0.4;
    }

    if ($odemeTipi !== 'komisyon_bazli') {
        if ($input['metrekare'] > 0)   { $v->numeric($input['metrekare'],  'metrekare');  $v->positive($input['metrekare'],  'metrekare'); }
        if ($input['birim_fiyat'] > 0) { $v->numeric($input['birim_fiyat'],'birim_fiyat');$v->positive($input['birim_fiyat'],'birim_fiyat'); }
    }
    if (isset($input['tarih'])) { $v->date($input['tarih'], 'tarih'); }

    if ($v->fails()) Response::validationError($v->getErrors());

    $result = $jobModel->update($jobId, $input);
    if (!$result) Response::serverError('İş kaydı güncellenemedi');

    Response::success([
        'toplam_tutar'    => $result['toplam_tutar'],
        'komisyon_orani'  => $result['komisyon_orani'],
        'komisyon_tutari' => $result['komisyon_tutari'],
    ], 'İş kaydı güncellendi');
}

// ── DELETE /api/jobs/{id} ─────────────────────────────────────────────────────
if ($method === 'DELETE' && $jobId !== null) {
    if (!$jobModel->getById($jobId)) Response::notFound('İş kaydı bulunamadı');
    if (!$jobModel->delete($jobId)) Response::serverError('İş kaydı silinemedi');
    Response::success(null, 'İş kaydı silindi');
}

// ── PATCH /api/jobs/{id}/teslim ───────────────────────────────────────────────
if ($method === 'PATCH' && $jobId !== null) {
    $job = $jobModel->getById($jobId);
    if (!$job) Response::notFound('İş kaydı bulunamadı');
    $newVal = ((int)$job['teslim_edildi'] === 1) ? 0 : 1;
    $ok = $jobModel->toggleTeslim($jobId, $newVal);
    if (!$ok) Response::serverError('Güncellenemedi');
    Response::success(['teslim_edildi' => $newVal], 'Güncellendi');
}

Response::error('Geçersiz endpoint', 'INVALID_ENDPOINT', 404);
