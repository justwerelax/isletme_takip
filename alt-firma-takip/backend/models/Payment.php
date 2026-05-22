<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Payment Model
 * 
 * Handles CRUD operations for payment transactions (para_hareketleri).
 */
class Payment
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all payments for a subcontractor
     * 
     * @param int $subcontractorId Subcontractor ID
     * @return array Array of payments
     */
    public function getBySubcontractor($subcontractorId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, alt_firma_id, tarih, tutar, hareket_tipi, aciklama,
                       created_at, updated_at
                FROM para_hareketleri
                WHERE alt_firma_id = :alt_firma_id
                ORDER BY tarih DESC, id DESC
            ");
            
            $stmt->execute(['alt_firma_id' => $subcontractorId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Payment getBySubcontractor error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get payment by ID
     * 
     * @param int $id Payment ID
     * @return array|false Payment data if found, false otherwise
     */
    public function getById($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, alt_firma_id, tarih, tutar, hareket_tipi, aciklama,
                       created_at, updated_at
                FROM para_hareketleri
                WHERE id = :id
                LIMIT 1
            ");
            
            $stmt->execute(['id' => $id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $payment ?: false;
            
        } catch (PDOException $e) {
            error_log("Payment getById error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a new payment
     * 
     * @param array $data Payment data (alt_firma_id, tarih, tutar, hareket_tipi, aciklama)
     * @return int|false New payment ID if successful, false otherwise
     */
    public function create($data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO para_hareketleri 
                (alt_firma_id, tarih, tutar, hareket_tipi, aciklama)
                VALUES 
                (:alt_firma_id, :tarih, :tutar, :hareket_tipi, :aciklama)
            ");
            
            $stmt->execute([
                'alt_firma_id' => $data['alt_firma_id'],
                'tarih' => $data['tarih'],
                'tutar' => $data['tutar'],
                'hareket_tipi' => $data['hareket_tipi'],
                'aciklama' => $data['aciklama'] ?? null
            ]);
            
            return $this->db->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Payment create error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update payment
     * 
     * @param int $id Payment ID
     * @param array $data Payment data to update
     * @return bool True if successful, false otherwise
     */
    public function update($id, $data)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE para_hareketleri
                SET tarih = :tarih,
                    tutar = :tutar,
                    hareket_tipi = :hareket_tipi,
                    aciklama = :aciklama
                WHERE id = :id
            ");
            
            return $stmt->execute([
                'id' => $id,
                'tarih' => $data['tarih'],
                'tutar' => $data['tutar'],
                'hareket_tipi' => $data['hareket_tipi'],
                'aciklama' => $data['aciklama'] ?? null
            ]);
            
        } catch (PDOException $e) {
            error_log("Payment update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete payment
     * 
     * @param int $id Payment ID
     * @return bool True if successful, false otherwise
     */
    public function delete($id)
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM para_hareketleri
                WHERE id = :id
            ");
            
            return $stmt->execute(['id' => $id]);
            
        } catch (PDOException $e) {
            error_log("Payment delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get payments within a date range
     * 
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @param int|null $subcontractorId Optional subcontractor ID filter
     * @return array Array of payments
     */
    public function getByDateRange($startDate, $endDate, $subcontractorId = null)
    {
        try {
            $sql = "
                SELECT id, alt_firma_id, tarih, tutar, hareket_tipi, aciklama,
                       created_at, updated_at
                FROM para_hareketleri
                WHERE tarih BETWEEN :start_date AND :end_date
            ";
            
            $params = [
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
            
            if ($subcontractorId !== null) {
                $sql .= " AND alt_firma_id = :alt_firma_id";
                $params['alt_firma_id'] = $subcontractorId;
            }
            
            $sql .= " ORDER BY tarih DESC, id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Payment getByDateRange error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total payments for a subcontractor
     * 
     * @param int $subcontractorId Subcontractor ID
     * @return float Total payment amount
     */
    public function getTotalPayments($subcontractorId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(tutar), 0) as total
                FROM para_hareketleri
                WHERE alt_firma_id = :alt_firma_id AND hareket_tipi = 'odeme'
            ");
            
            $stmt->execute(['alt_firma_id' => $subcontractorId]);
            
            return (float) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
        } catch (PDOException $e) {
            error_log("Payment getTotalPayments error: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Get total collections for a subcontractor
     * 
     * @param int $subcontractorId Subcontractor ID
     * @return float Total collection amount
     */
    public function getTotalCollections($subcontractorId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(tutar), 0) as total
                FROM para_hareketleri
                WHERE alt_firma_id = :alt_firma_id AND hareket_tipi = 'bakiye_ekle'
            ");
            
            $stmt->execute(['alt_firma_id' => $subcontractorId]);
            
            return (float) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
        } catch (PDOException $e) {
            error_log("Payment getTotalCollections error: " . $e->getMessage());
            return 0.0;
        }
    }
}
