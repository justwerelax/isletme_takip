<?php
/**
 * Validator Test Script
 * 
 * Run this script to verify the Validator class works correctly.
 * Usage: php test-validator.php
 */

require_once __DIR__ . '/Validator.php';

echo "=== Validator Class Test ===\n\n";

// Test 1: Required validation
echo "Test 1: Required validation\n";
$validator = Validator::make();
$validator->required('', 'username');
$result = $validator->getResult();
echo "Empty string: " . ($result['valid'] ? "✗ FAILED" : "✓ PASSED") . "\n";
echo "Error: " . ($result['errors']['username'] ?? 'none') . "\n\n";

// Test 2: Numeric validation
echo "Test 2: Numeric validation\n";
$validator = Validator::make();
$validator->numeric('abc', 'price');
$result = $validator->getResult();
echo "Non-numeric: " . ($result['valid'] ? "✗ FAILED" : "✓ PASSED") . "\n";
echo "Error: " . ($result['errors']['price'] ?? 'none') . "\n\n";

$validator = Validator::make();
$validator->numeric('123.45', 'price');
$result = $validator->getResult();
echo "Valid numeric: " . ($result['valid'] ? "✓ PASSED" : "✗ FAILED") . "\n\n";

// Test 3: Positive validation
echo "Test 3: Positive validation\n";
$validator = Validator::make();
$validator->positive(-5, 'amount');
$result = $validator->getResult();
echo "Negative number: " . ($result['valid'] ? "✗ FAILED" : "✓ PASSED") . "\n";
echo "Error: " . ($result['errors']['amount'] ?? 'none') . "\n\n";

$validator = Validator::make();
$validator->positive(0, 'amount');
$result = $validator->getResult();
echo "Zero: " . ($result['valid'] ? "✗ FAILED" : "✓ PASSED") . "\n";
echo "Error: " . ($result['errors']['amount'] ?? 'none') . "\n\n";

$validator = Validator::make();
$validator->positive(10, 'amount');
$result = $validator->getResult();
echo "Positive number: " . ($result['valid'] ? "✓ PASSED" : "✗ FAILED") . "\n\n";

// Test 4: Date validation
echo "Test 4: Date validation\n";
$validator = Validator::make();
$validator->date('2024-13-45', 'date');
$result = $validator->getResult();
echo "Invalid date: " . ($result['valid'] ? "✗ FAILED" : "✓ PASSED") . "\n";
echo "Error: " . ($result['errors']['date'] ?? 'none') . "\n\n";

$validator = Validator::make();
$validator->date('2024-01-15', 'date');
$result = $validator->getResult();
echo "Valid date: " . ($result['valid'] ? "✓ PASSED" : "✗ FAILED") . "\n\n";

// Test 5: Enum validation
echo "Test 5: Enum validation\n";
$validator = Validator::make();
$validator->enum('invalid', ['alt_firma_teslim', 'ana_firma_teslim'], 'teslimat_tipi');
$result = $validator->getResult();
echo "Invalid enum: " . ($result['valid'] ? "✗ FAILED" : "✓ PASSED") . "\n";
echo "Error: " . ($result['errors']['teslimat_tipi'] ?? 'none') . "\n\n";

$validator = Validator::make();
$validator->enum('alt_firma_teslim', ['alt_firma_teslim', 'ana_firma_teslim'], 'teslimat_tipi');
$result = $validator->getResult();
echo "Valid enum: " . ($result['valid'] ? "✓ PASSED" : "✗ FAILED") . "\n\n";

// Test 6: Method chaining
echo "Test 6: Method chaining\n";
$validator = Validator::make();
$validator
    ->required('50', 'metrekare')
    ->numeric('50', 'metrekare')
    ->positive('50', 'metrekare');
$result = $validator->getResult();
echo "Valid chained validation: " . ($result['valid'] ? "✓ PASSED" : "✗ FAILED") . "\n\n";

$validator = Validator::make();
$validator
    ->required('', 'metrekare')
    ->numeric('abc', 'birim_fiyat')
    ->positive(-5, 'tutar');
$result = $validator->getResult();
echo "Multiple errors: " . ($result['valid'] ? "✗ FAILED" : "✓ PASSED") . "\n";
echo "Error count: " . count($result['errors']) . "\n";
foreach ($result['errors'] as $field => $error) {
    echo "  - $field: $error\n";
}
echo "\n";

// Test 7: Real-world scenario - Job validation
echo "Test 7: Real-world scenario - Job validation\n";
$jobData = [
    'alt_firma_id' => '1',
    'tarih' => '2024-01-15',
    'metrekare' => '50.5',
    'birim_fiyat' => '25.00',
    'teslimat_tipi' => 'alt_firma_teslim'
];

$validator = Validator::make();
$validator
    ->required($jobData['alt_firma_id'], 'alt_firma_id')
    ->numeric($jobData['alt_firma_id'], 'alt_firma_id')
    ->required($jobData['tarih'], 'tarih')
    ->date($jobData['tarih'], 'tarih')
    ->required($jobData['metrekare'], 'metrekare')
    ->numeric($jobData['metrekare'], 'metrekare')
    ->positive($jobData['metrekare'], 'metrekare')
    ->required($jobData['birim_fiyat'], 'birim_fiyat')
    ->numeric($jobData['birim_fiyat'], 'birim_fiyat')
    ->positive($jobData['birim_fiyat'], 'birim_fiyat')
    ->required($jobData['teslimat_tipi'], 'teslimat_tipi')
    ->enum($jobData['teslimat_tipi'], ['alt_firma_teslim', 'ana_firma_teslim'], 'teslimat_tipi');

$result = $validator->getResult();
echo "Valid job data: " . ($result['valid'] ? "✓ PASSED" : "✗ FAILED") . "\n";
if (!$result['valid']) {
    foreach ($result['errors'] as $field => $error) {
        echo "  - $field: $error\n";
    }
}
echo "\n";

echo "=== All Tests Complete ===\n";
