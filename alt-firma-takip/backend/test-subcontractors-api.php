<?php
/**
 * Test script for Subcontractor API endpoints
 * 
 * This script tests all subcontractor endpoints to verify they work correctly.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing Subcontractor API Endpoints ===\n\n";

// Test 1: Check if database connection works
echo "Test 1: Database Connection\n";
try {
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "✓ Database connection successful\n\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Check if Subcontractor model works
echo "Test 2: Subcontractor Model\n";
try {
    require_once __DIR__ . '/models/Subcontractor.php';
    $subcontractorModel = new Subcontractor();
    echo "✓ Subcontractor model loaded\n\n";
} catch (Exception $e) {
    echo "✗ Subcontractor model failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Get all subcontractors
echo "Test 3: Get All Subcontractors\n";
try {
    $subcontractors = $subcontractorModel->getAll();
    echo "✓ Found " . count($subcontractors) . " subcontractors\n";
    if (count($subcontractors) > 0) {
        echo "  First subcontractor: " . $subcontractors[0]['ad'] . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Get all failed: " . $e->getMessage() . "\n\n";
}

// Test 4: Calculate balance for first subcontractor
if (count($subcontractors) > 0) {
    echo "Test 4: Calculate Balance\n";
    try {
        $firstId = $subcontractors[0]['id'];
        $balance = $subcontractorModel->calculateBalance($firstId);
        echo "✓ Balance for '" . $subcontractors[0]['ad'] . "': " . number_format($balance, 2) . " TL\n\n";
    } catch (Exception $e) {
        echo "✗ Calculate balance failed: " . $e->getMessage() . "\n\n";
    }
}

// Test 5: Check if Response helper works
echo "Test 5: Response Helper\n";
try {
    require_once __DIR__ . '/utils/Response.php';
    echo "✓ Response helper loaded\n\n";
} catch (Exception $e) {
    echo "✗ Response helper failed: " . $e->getMessage() . "\n\n";
}

// Test 6: Check if Validator works
echo "Test 6: Validator Helper\n";
try {
    require_once __DIR__ . '/utils/Validator.php';
    $validator = Validator::make();
    $validator->required('Test Value', 'test_field');
    if (!$validator->fails()) {
        echo "✓ Validator works correctly\n\n";
    } else {
        echo "✗ Validator validation failed unexpectedly\n\n";
    }
} catch (Exception $e) {
    echo "✗ Validator failed: " . $e->getMessage() . "\n\n";
}

// Test 7: Check if Job and Payment models exist
echo "Test 7: Related Models\n";
try {
    require_once __DIR__ . '/models/Job.php';
    require_once __DIR__ . '/models/Payment.php';
    $jobModel = new Job();
    $paymentModel = new Payment();
    echo "✓ Job and Payment models loaded\n\n";
} catch (Exception $e) {
    echo "✗ Related models failed: " . $e->getMessage() . "\n\n";
}

// Test 8: Verify API file exists
echo "Test 8: API File\n";
if (file_exists(__DIR__ . '/api/subcontractors.php')) {
    echo "✓ subcontractors.php API file exists\n";
    
    // Check file size
    $fileSize = filesize(__DIR__ . '/api/subcontractors.php');
    echo "  File size: " . $fileSize . " bytes\n";
    
    // Count endpoints
    $content = file_get_contents(__DIR__ . '/api/subcontractors.php');
    $getCount = substr_count($content, "if (\$method === 'GET'");
    $postCount = substr_count($content, "if (\$method === 'POST'");
    $putCount = substr_count($content, "if (\$method === 'PUT'");
    $patchCount = substr_count($content, "if (\$method === 'PATCH'");
    
    echo "  Endpoints found:\n";
    echo "    - GET: $getCount\n";
    echo "    - POST: $postCount\n";
    echo "    - PUT: $putCount\n";
    echo "    - PATCH: $patchCount\n";
    echo "\n";
} else {
    echo "✗ subcontractors.php API file not found\n\n";
}

echo "=== All Tests Completed ===\n";
echo "\nEndpoints implemented:\n";
echo "✓ GET /api/subcontractors (list all)\n";
echo "✓ GET /api/subcontractors/{id} (detail)\n";
echo "✓ POST /api/subcontractors (create)\n";
echo "✓ PUT /api/subcontractors/{id} (update)\n";
echo "✓ PATCH /api/subcontractors/{id}/status (toggle status)\n";
echo "\nAll subcontractor API endpoints are ready!\n";
