<?php
/**
 * Database Configuration Usage Example
 * 
 * This file demonstrates how to use the Database class
 * in your models and API endpoints.
 */

// Include the database configuration
require_once __DIR__ . '/database.php';

// Example 1: Get database connection
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Now you can use $conn for queries
    $stmt = $conn->prepare("SELECT * FROM alt_firma WHERE durum = :durum");
    $stmt->execute(['durum' => 'aktif']);
    $results = $stmt->fetchAll();
    
    echo "Connection successful!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Example 2: Using in a model class
class SubcontractorModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM alt_firma");
        return $stmt->fetchAll();
    }
}

// Example 3: Prepared statement with parameters
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("INSERT INTO alt_firma (ad, telefon, adres) VALUES (:ad, :telefon, :adres)");
    $stmt->execute([
        'ad' => 'Test Firma',
        'telefon' => '0555 111 2233',
        'adres' => 'İstanbul'
    ]);
    
    echo "Insert successful! ID: " . $conn->lastInsertId() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
