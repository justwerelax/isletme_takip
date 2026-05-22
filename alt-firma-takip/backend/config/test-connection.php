<?php
/**
 * Database Connection Test Script
 * 
 * Run this script to verify your database configuration is correct.
 * Usage: php test-connection.php
 */

require_once __DIR__ . '/database.php';

echo "=== Database Connection Test ===\n\n";

try {
    echo "Attempting to connect to database...\n";
    
    // Get database instance
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "✓ Connection successful!\n\n";
    
    // Test query to verify database exists and is accessible
    echo "Testing database access...\n";
    $stmt = $conn->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    
    echo "✓ Connected to database: " . $result['db_name'] . "\n\n";
    
    // Check if tables exist
    echo "Checking for required tables...\n";
    $tables = ['users', 'alt_firma', 'yikama_isleri', 'para_hareketleri'];
    $existingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE :table");
        $stmt->execute(['table' => $table]);
        if ($stmt->fetch()) {
            echo "✓ Table '$table' exists\n";
            $existingTables[] = $table;
        } else {
            echo "✗ Table '$table' not found\n";
        }
    }
    
    echo "\n";
    
    if (count($existingTables) === count($tables)) {
        echo "✓ All required tables exist!\n";
        echo "\nDatabase is ready to use.\n";
    } else {
        echo "⚠ Some tables are missing. Please run the schema.sql file:\n";
        echo "  mysql -u root -p alt_firma_takip < backend/schema.sql\n";
    }
    
} catch (Exception $e) {
    echo "✗ Connection failed!\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Please check:\n";
    echo "1. MySQL server is running\n";
    echo "2. Database 'alt_firma_takip' exists\n";
    echo "3. Username and password are correct in database.php\n";
    echo "4. User has appropriate permissions\n\n";
    echo "To create the database:\n";
    echo "  CREATE DATABASE alt_firma_takip CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    exit(1);
}

echo "\n=== Test Complete ===\n";
