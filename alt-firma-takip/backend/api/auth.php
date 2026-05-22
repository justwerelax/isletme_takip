<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Parse the endpoint
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Get the action (login or verify)
$action = end($pathParts);

/**
 * POST /api/auth/login
 * Login with username and password
 */
if ($method === 'POST' && $action === 'login') {
    // Get request body
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    $validator = Validator::make();
    $validator->required($input['username'] ?? null, 'username')
              ->required($input['password'] ?? null, 'password');
    
    if ($validator->fails()) {
        Response::validationError($validator->getErrors());
    }
    
    // Authenticate user
    $userModel = new User();
    $user = $userModel->authenticate($input['username'], $input['password']);
    
    if (!$user) {
        Response::error('Geçersiz kullanıcı adı veya şifre', 'AUTH_FAILED', 401);
    }
    
    // Generate JWT token
    $token = JWT::generate($user['id'], $user['username']);
    
    // Return success response
    Response::success([
        'token' => $token,
        'user' => $user
    ], 'Giriş başarılı');
}

/**
 * POST /api/auth/verify
 * Verify JWT token
 */
if ($method === 'POST' && $action === 'verify') {
    // Require authentication (will exit with 401 if invalid)
    $userData = AuthMiddleware::requireAuth();
    
    // Get full user data
    $userModel = new User();
    $user = $userModel->findById($userData['user_id']);
    
    if (!$user) {
        Response::error('Kullanıcı bulunamadı', 'USER_NOT_FOUND', 404);
    }
    
    // Return success response
    Response::success([
        'user' => $user
    ], 'Token geçerli');
}

/**
 * GET /api/auth/me
 * Get current user data
 */
if ($method === 'GET' && $action === 'me') {
    // Require authentication
    $userData = AuthMiddleware::requireAuth();
    
    // Get full user data
    $userModel = new User();
    $user = $userModel->findById($userData['user_id']);
    
    if (!$user) {
        Response::error('Kullanıcı bulunamadı', 'USER_NOT_FOUND', 404);
    }
    
    // Return success response
    Response::success($user);
}

// If no route matched
Response::error('Geçersiz endpoint', 'INVALID_ENDPOINT', 404);
