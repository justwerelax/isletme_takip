<?php

/**
 * API Router
 * 
 * Main entry point for all API requests.
 * Routes requests to appropriate API endpoint files.
 */

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in production
ini_set('log_errors', 1);

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request URI and method
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Parse the path
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Find 'api' in path and get the endpoint
$apiIndex = array_search('api', $pathParts);
$endpoint = null;

if ($apiIndex !== false && isset($pathParts[$apiIndex + 1])) {
    $endpoint = $pathParts[$apiIndex + 1];
}

// Route to appropriate API file
switch ($endpoint) {
    case 'auth':
        require_once __DIR__ . '/auth.php';
        break;
        
    case 'subcontractors':
        require_once __DIR__ . '/subcontractors.php';
        break;
        
    case 'jobs':
        require_once __DIR__ . '/jobs.php';
        break;
        
    case 'payments':
        require_once __DIR__ . '/payments.php';
        break;
        
    case 'reports':
        require_once __DIR__ . '/reports.php';
        break;
        
    case 'export':
        require_once __DIR__ . '/export.php';
        break;

    case 'onay':
        require_once __DIR__ . '/onay.php';
        break;
        
    default:
        // Unknown endpoint
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Endpoint bulunamadı',
            'code' => 'ENDPOINT_NOT_FOUND',
            'available_endpoints' => [
                '/api/auth/login',
                '/api/auth/verify',
                '/api/subcontractors',
                '/api/jobs',
                '/api/payments',
                '/api/reports/summary',
                '/api/export?format=json',
                '/api/export?format=csv',
                '/api/export?format=json&sub_id={id}'
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
}
