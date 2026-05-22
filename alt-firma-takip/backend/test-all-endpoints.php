<?php

/**
 * Comprehensive API Endpoint Test Script
 * Tests all endpoints: Auth, Subcontractors, Jobs, Payments, Reports
 */

// Configuration
$baseUrl = 'http://localhost/isletme-takip-sistemi/alt-firma-takip/backend/api';
$token = null;

// Color output for terminal
function colorOutput($message, $color = 'green') {
    $colors = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m"
    ];
    
    echo $colors[$color] . $message . $colors['reset'] . PHP_EOL;
}

function makeRequest($endpoint, $method = 'GET', $data = null, $useToken = true) {
    global $baseUrl, $token;
    
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Content-Type: application/json'];
    
    if ($useToken && $token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

echo "\n";
colorOutput("=== Alt Firma Takip Sistemi - API Endpoint Tests ===", 'blue');
echo "\n";

// Test 1: Login
colorOutput("Test 1: POST /api/auth/login", 'yellow');
$response = makeRequest('/auth/login', 'POST', [
    'username' => 'admin',
    'password' => 'admin123'
], false);

if ($response['code'] === 200 && isset($response['body']['token'])) {
    $token = $response['body']['token'];
    colorOutput("✓ Login successful, token received", 'green');
} else {
    colorOutput("✗ Login failed: " . json_encode($response['body']), 'red');
    exit(1);
}

// Test 2: Verify Token
colorOutput("\nTest 2: POST /api/auth/verify", 'yellow');
$response = makeRequest('/auth/verify', 'POST');

if ($response['code'] === 200 && $response['body']['success']) {
    colorOutput("✓ Token verification successful", 'green');
} else {
    colorOutput("✗ Token verification failed", 'red');
}

// Test 3: Get All Subcontractors
colorOutput("\nTest 3: GET /api/subcontractors", 'yellow');
$response = makeRequest('/subcontractors', 'GET');

if ($response['code'] === 200 && $response['body']['success']) {
    $subcontractors = $response['body']['data']['subcontractors'];
    colorOutput("✓ Retrieved " . count($subcontractors) . " subcontractors", 'green');
    
    // Store first subcontractor ID for later tests
    $testSubcontractorId = $subcontractors[0]['id'] ?? null;
} else {
    colorOutput("✗ Failed to get subcontractors", 'red');
    $testSubcontractorId = null;
}

// Test 4: Create Job
if ($testSubcontractorId) {
    colorOutput("\nTest 4: POST /api/jobs", 'yellow');
    $response = makeRequest('/jobs', 'POST', [
        'alt_firma_id' => $testSubcontractorId,
        'tarih' => date('Y-m-d'),
        'metrekare' => 50.00,
        'birim_fiyat' => 25.00,
        'teslimat_tipi' => 'alt_firma_teslim',
        'aciklama' => 'Test job from API test script'
    ]);
    
    if ($response['code'] === 201 && $response['body']['success']) {
        $testJobId = $response['body']['data']['id'];
        $calculatedTotal = $response['body']['data']['toplam_tutar'];
        $calculatedCommission = $response['body']['data']['komisyon_tutari'];
        
        colorOutput("✓ Job created successfully", 'green');
        colorOutput("  - Job ID: $testJobId", 'green');
        colorOutput("  - Total: $calculatedTotal TL", 'green');
        colorOutput("  - Commission: $calculatedCommission TL", 'green');
    } else {
        colorOutput("✗ Failed to create job: " . json_encode($response['body']), 'red');
        $testJobId = null;
    }
} else {
    colorOutput("\nTest 4: POST /api/jobs - SKIPPED (no subcontractor)", 'yellow');
    $testJobId = null;
}

// Test 5: Update Job
if ($testJobId) {
    colorOutput("\nTest 5: PUT /api/jobs/{id}", 'yellow');
    $response = makeRequest("/jobs/$testJobId", 'PUT', [
        'tarih' => date('Y-m-d'),
        'metrekare' => 75.00,
        'birim_fiyat' => 30.00,
        'teslimat_tipi' => 'ana_firma_teslim',
        'aciklama' => 'Updated test job'
    ]);
    
    if ($response['code'] === 200 && $response['body']['success']) {
        $updatedTotal = $response['body']['data']['toplam_tutar'];
        $updatedCommission = $response['body']['data']['komisyon_tutari'];
        
        colorOutput("✓ Job updated successfully", 'green');
        colorOutput("  - New Total: $updatedTotal TL", 'green');
        colorOutput("  - New Commission: $updatedCommission TL", 'green');
    } else {
        colorOutput("✗ Failed to update job", 'red');
    }
} else {
    colorOutput("\nTest 5: PUT /api/jobs/{id} - SKIPPED", 'yellow');
}

// Test 6: Create Payment
if ($testSubcontractorId) {
    colorOutput("\nTest 6: POST /api/payments", 'yellow');
    $response = makeRequest('/payments', 'POST', [
        'alt_firma_id' => $testSubcontractorId,
        'tarih' => date('Y-m-d'),
        'tutar' => 500.00,
        'hareket_tipi' => 'odeme',
        'aciklama' => 'Test payment from API test script'
    ]);
    
    if ($response['code'] === 201 && $response['body']['success']) {
        $testPaymentId = $response['body']['data']['id'];
        colorOutput("✓ Payment created successfully (ID: $testPaymentId)", 'green');
    } else {
        colorOutput("✗ Failed to create payment: " . json_encode($response['body']), 'red');
        $testPaymentId = null;
    }
} else {
    colorOutput("\nTest 6: POST /api/payments - SKIPPED", 'yellow');
    $testPaymentId = null;
}

// Test 7: Update Payment
if ($testPaymentId) {
    colorOutput("\nTest 7: PUT /api/payments/{id}", 'yellow');
    $response = makeRequest("/payments/$testPaymentId", 'PUT', [
        'tarih' => date('Y-m-d'),
        'tutar' => 750.00,
        'hareket_tipi' => 'tahsilat',
        'aciklama' => 'Updated test payment'
    ]);
    
    if ($response['code'] === 200 && $response['body']['success']) {
        colorOutput("✓ Payment updated successfully", 'green');
    } else {
        colorOutput("✗ Failed to update payment", 'red');
    }
} else {
    colorOutput("\nTest 7: PUT /api/payments/{id} - SKIPPED", 'yellow');
}

// Test 8: Get Subcontractor Detail
if ($testSubcontractorId) {
    colorOutput("\nTest 8: GET /api/subcontractors/{id}", 'yellow');
    $response = makeRequest("/subcontractors/$testSubcontractorId", 'GET');
    
    if ($response['code'] === 200 && $response['body']['success']) {
        $data = $response['body']['data'];
        $jobCount = count($data['jobs']);
        $paymentCount = count($data['payments']);
        $balance = $data['subcontractor']['balance'];
        
        colorOutput("✓ Subcontractor detail retrieved", 'green');
        colorOutput("  - Jobs: $jobCount", 'green');
        colorOutput("  - Payments: $paymentCount", 'green');
        colorOutput("  - Balance: $balance TL", 'green');
    } else {
        colorOutput("✗ Failed to get subcontractor detail", 'red');
    }
} else {
    colorOutput("\nTest 8: GET /api/subcontractors/{id} - SKIPPED", 'yellow');
}

// Test 9: Get Report Summary
colorOutput("\nTest 9: GET /api/reports/summary", 'yellow');
$startDate = date('Y-m-01'); // First day of current month
$endDate = date('Y-m-t');    // Last day of current month
$response = makeRequest("/reports/summary?start_date=$startDate&end_date=$endDate", 'GET');

if ($response['code'] === 200 && $response['body']['success']) {
    $summary = $response['body']['data']['summary'];
    colorOutput("✓ Report summary retrieved", 'green');
    colorOutput("  - Total Jobs: " . $summary['totalJobs'], 'green');
    colorOutput("  - Total Square Meters: " . $summary['totalSquareMeters'], 'green');
    colorOutput("  - Total Revenue: " . $summary['totalRevenue'] . " TL", 'green');
    colorOutput("  - Total Commission: " . $summary['totalCommission'] . " TL", 'green');
} else {
    colorOutput("✗ Failed to get report summary", 'red');
}

// Test 10: Delete Payment
if ($testPaymentId) {
    colorOutput("\nTest 10: DELETE /api/payments/{id}", 'yellow');
    $response = makeRequest("/payments/$testPaymentId", 'DELETE');
    
    if ($response['code'] === 200 && $response['body']['success']) {
        colorOutput("✓ Payment deleted successfully", 'green');
    } else {
        colorOutput("✗ Failed to delete payment", 'red');
    }
} else {
    colorOutput("\nTest 10: DELETE /api/payments/{id} - SKIPPED", 'yellow');
}

// Test 11: Delete Job
if ($testJobId) {
    colorOutput("\nTest 11: DELETE /api/jobs/{id}", 'yellow');
    $response = makeRequest("/jobs/$testJobId", 'DELETE');
    
    if ($response['code'] === 200 && $response['body']['success']) {
        colorOutput("✓ Job deleted successfully", 'green');
    } else {
        colorOutput("✗ Failed to delete job", 'red');
    }
} else {
    colorOutput("\nTest 11: DELETE /api/jobs/{id} - SKIPPED", 'yellow');
}

// Test 12: Validation Error Test
colorOutput("\nTest 12: POST /api/jobs (validation error test)", 'yellow');
$response = makeRequest('/jobs', 'POST', [
    'alt_firma_id' => $testSubcontractorId,
    'tarih' => 'invalid-date',
    'metrekare' => -50,
    'birim_fiyat' => 'not-a-number',
    'teslimat_tipi' => 'invalid_type'
]);

if ($response['code'] === 400 && !$response['body']['success']) {
    colorOutput("✓ Validation errors correctly returned", 'green');
    colorOutput("  - Errors: " . json_encode($response['body']['errors']), 'green');
} else {
    colorOutput("✗ Validation test failed", 'red');
}

echo "\n";
colorOutput("=== All Tests Completed ===", 'blue');
echo "\n";
