<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';

// Require authentication for all payment endpoints
$user = AuthMiddleware::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Parse the endpoint
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Get payment ID if present
$paymentId = null;

// Find 'payments' in path and get ID if present
$paymentsIndex = array_search('payments', $pathParts);
if ($paymentsIndex !== false && isset($pathParts[$paymentsIndex + 1])) {
    $nextPart = $pathParts[$paymentsIndex + 1];
    if (is_numeric($nextPart)) {
        $paymentId = (int) $nextPart;
    }
}

$paymentModel = new Payment();

/**
 * POST /api/payments
 * Create new payment
 */
if ($method === 'POST' && $paymentId === null) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    $validator = Validator::make();
    $validator->required($input['alt_firma_id'] ?? null, 'alt_firma_id');
    $validator->required($input['tarih'] ?? null, 'tarih');
    $validator->required($input['tutar'] ?? null, 'tutar');
    $validator->required($input['hareket_tipi'] ?? null, 'hareket_tipi');
    
    // Validate numeric fields
    if (isset($input['tutar'])) {
        $validator->numeric($input['tutar'], 'tutar');
        $validator->positive($input['tutar'], 'tutar');
    }
    
    // Validate date
    if (isset($input['tarih'])) {
        $validator->date($input['tarih'], 'tarih');
    }
    
    // Validate hareket_tipi enum
    if (isset($input['hareket_tipi'])) {
        $validator->enum($input['hareket_tipi'], ['odeme', 'bakiye_ekle'], 'hareket_tipi');
    }
    
    if ($validator->fails()) {
        Response::validationError($validator->getErrors());
    }
    
    // Create payment
    $newId = $paymentModel->create($input);
    
    if (!$newId) {
        Response::serverError('Para hareketi kaydedilemedi');
    }
    
    Response::success([
        'id' => $newId
    ], 'Para hareketi kaydedildi', 201);
}

/**
 * PUT /api/payments/{id}
 * Update payment
 */
if ($method === 'PUT' && $paymentId !== null) {
    // Check if payment exists
    $payment = $paymentModel->getById($paymentId);
    if (!$payment) {
        Response::notFound('Para hareketi bulunamadı');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    $validator = Validator::make();
    $validator->required($input['tarih'] ?? null, 'tarih');
    $validator->required($input['tutar'] ?? null, 'tutar');
    $validator->required($input['hareket_tipi'] ?? null, 'hareket_tipi');
    
    // Validate numeric fields
    if (isset($input['tutar'])) {
        $validator->numeric($input['tutar'], 'tutar');
        $validator->positive($input['tutar'], 'tutar');
    }
    
    // Validate date
    if (isset($input['tarih'])) {
        $validator->date($input['tarih'], 'tarih');
    }
    
    // Validate hareket_tipi enum
    if (isset($input['hareket_tipi'])) {
        $validator->enum($input['hareket_tipi'], ['odeme', 'bakiye_ekle'], 'hareket_tipi');
    }
    
    if ($validator->fails()) {
        Response::validationError($validator->getErrors());
    }
    
    // Update payment
    $success = $paymentModel->update($paymentId, $input);
    
    if (!$success) {
        Response::serverError('Para hareketi güncellenemedi');
    }
    
    Response::success(null, 'Para hareketi güncellendi');
}

/**
 * DELETE /api/payments/{id}
 * Delete payment
 */
if ($method === 'DELETE' && $paymentId !== null) {
    // Check if payment exists
    $payment = $paymentModel->getById($paymentId);
    if (!$payment) {
        Response::notFound('Para hareketi bulunamadı');
    }
    
    // Delete payment
    $success = $paymentModel->delete($paymentId);
    
    if (!$success) {
        Response::serverError('Para hareketi silinemedi');
    }
    
    Response::success(null, 'Para hareketi silindi');
}

// If no route matched
Response::error('Geçersiz endpoint', 'INVALID_ENDPOINT', 404);
