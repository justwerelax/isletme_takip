<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../models/Job.php';
require_once __DIR__ . '/../models/Subcontractor.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';

// Require authentication for all report endpoints
$user = AuthMiddleware::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Parse the endpoint
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Check if this is the summary endpoint
$reportsIndex = array_search('reports', $pathParts);
$isSummary = $reportsIndex !== false && isset($pathParts[$reportsIndex + 1]) && $pathParts[$reportsIndex + 1] === 'summary';

/**
 * GET /api/reports/summary?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
 * Get summary report for date range
 */
if ($method === 'GET' && $isSummary) {
    // Get query parameters
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    // Validate date parameters
    $validator = Validator::make();
    
    if ($startDate) {
        $validator->date($startDate, 'start_date');
    }
    
    if ($endDate) {
        $validator->date($endDate, 'end_date');
    }
    
    if ($validator->fails()) {
        Response::validationError($validator->getErrors());
    }
    
    // If no dates provided, use current month
    if (!$startDate || !$endDate) {
        $startDate = date('Y-m-01'); // First day of current month
        $endDate = date('Y-m-t');    // Last day of current month
    }
    
    // Get jobs within date range
    $jobModel = new Job();
    $jobs = $jobModel->getByDateRange($startDate, $endDate);
    
    // Calculate summary statistics
    $totalJobs = count($jobs);
    $totalSquareMeters = 0;
    $totalRevenue = 0;
    $totalCommission = 0;
    
    // Group by subcontractor
    $bySubcontractor = [];
    
    foreach ($jobs as $job) {
        $totalSquareMeters += (float) $job['metrekare'];
        $totalRevenue += (float) $job['toplam_tutar'];
        $totalCommission += (float) $job['komisyon_tutari'];
        
        $subcontractorId = $job['alt_firma_id'];
        
        if (!isset($bySubcontractor[$subcontractorId])) {
            $bySubcontractor[$subcontractorId] = [
                'id' => $subcontractorId,
                'jobCount' => 0,
                'totalSquareMeters' => 0,
                'totalRevenue' => 0,
                'totalCommission' => 0
            ];
        }
        
        $bySubcontractor[$subcontractorId]['jobCount']++;
        $bySubcontractor[$subcontractorId]['totalSquareMeters'] += (float) $job['metrekare'];
        $bySubcontractor[$subcontractorId]['totalRevenue'] += (float) $job['toplam_tutar'];
        $bySubcontractor[$subcontractorId]['totalCommission'] += (float) $job['komisyon_tutari'];
    }
    
    // Get subcontractor names
    $subcontractorModel = new Subcontractor();
    $bySubcontractorArray = [];
    
    foreach ($bySubcontractor as $subcontractorId => $data) {
        $subcontractor = $subcontractorModel->getById($subcontractorId);
        if ($subcontractor) {
            $data['ad'] = $subcontractor['ad'];
            $bySubcontractorArray[] = $data;
        }
    }
    
    // Sort by total commission descending
    usort($bySubcontractorArray, function($a, $b) {
        return $b['totalCommission'] <=> $a['totalCommission'];
    });
    
    Response::success([
        'dateRange' => [
            'start_date' => $startDate,
            'end_date' => $endDate
        ],
        'summary' => [
            'totalJobs' => $totalJobs,
            'totalSquareMeters' => $totalSquareMeters,
            'totalRevenue' => $totalRevenue,
            'totalCommission' => $totalCommission
        ],
        'bySubcontractor' => $bySubcontractorArray
    ]);
}

// If no route matched
Response::error('Geçersiz endpoint', 'INVALID_ENDPOINT', 404);
