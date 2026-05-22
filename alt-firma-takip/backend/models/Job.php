<?php

require_once __DIR__ . '/../config/database.php';

class Job
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getBySubcontractor($subcontractorId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, alt_firma_id, is_tipi, odeme_tipi, tarih, metrekare, birim_fiyat,
                       toplam_tutar, musteri_tutari, teslimat_tipi, komisyon_orani, komisyon_tutari, aciklama,
                       teslim_edildi, created_at, updated_at
                FROM yikama_isleri
                WHERE alt_firma_id = :alt_firma_id
                ORDER BY tarih DESC, id DESC
            ");
            $stmt->execute(['alt_firma_id' => $subcontractorId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Job getBySubcontractor error: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, alt_firma_id, is_tipi, odeme_tipi, tarih, metrekare, birim_fiyat,
                       toplam_tutar, musteri_tutari, teslimat_tipi, komisyon_orani, komisyon_tutari, aciklama,
                       teslim_edildi, created_at, updated_at
                FROM yikama_isleri WHERE id = :id LIMIT 1
            ");
            $stmt->execute(['id' => $id]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            return $job ?: false;
        } catch (PDOException $e) {
            error_log("Job getById error: " . $e->getMessage());
            return false;
        }
    }

    public function create($data)
    {
        try {
            $isTipi    = $data['is_tipi']    ?? 'bizim_isimiz';
            $odemeTipi = $data['odeme_tipi'] ?? null;

            if ($isTipi === 'kendi_isi') {
                // Kendi işi: m² × birim_fiyat = borç, komisyon yok
                $totalAmount      = $data['metrekare'] * $data['birim_fiyat'];
                $commissionRate   = 0;
                $commissionAmount = 0;
                $teslimatTipi     = null;
                $odemeTipi        = null;
            } elseif ($odemeTipi === 'm2_bazli') {
                // Bizim işimiz - m² bazlı: m² × birim_fiyat = borç, komisyon yok
                $totalAmount      = $data['metrekare'] * $data['birim_fiyat'];
                $commissionRate   = 0;
                $commissionAmount = 0;
                $teslimatTipi     = null;
            } else {
                // Bizim işimiz - komisyon bazlı: sipariş tutarı × komisyon oranı = borç
                $siparisTutari    = (float) ($data['siparis_tutari'] ?? ($data['metrekare'] * $data['birim_fiyat']));
                $commissionRate   = (float) ($data['komisyon_orani'] ?? 0.4);
                $commissionAmount = $siparisTutari * $commissionRate;
                $totalAmount      = $siparisTutari;
                $teslimatTipi     = $data['teslimat_tipi'] ?? 'alt_firma_teslim';
                $odemeTipi        = 'komisyon_bazli';
            }

            $stmt = $this->db->prepare("
                INSERT INTO yikama_isleri
                (alt_firma_id, is_tipi, odeme_tipi, tarih, metrekare, birim_fiyat, toplam_tutar, musteri_tutari,
                 teslimat_tipi, komisyon_orani, komisyon_tutari, aciklama, teslim_edildi)
                VALUES
                (:alt_firma_id, :is_tipi, :odeme_tipi, :tarih, :metrekare, :birim_fiyat, :toplam_tutar, :musteri_tutari,
                 :teslimat_tipi, :komisyon_orani, :komisyon_tutari, :aciklama, :teslim_edildi)
            ");

            $stmt->execute([
                'alt_firma_id'    => $data['alt_firma_id'],
                'is_tipi'         => $isTipi,
                'odeme_tipi'      => $odemeTipi,
                'tarih'           => $data['tarih'],
                'metrekare'       => $data['metrekare'],
                'birim_fiyat'     => $data['birim_fiyat'],
                'toplam_tutar'    => $totalAmount,
                'musteri_tutari'  => $data['musteri_tutari'] ?? null,
                'teslimat_tipi'   => $teslimatTipi,
                'komisyon_orani'  => $commissionRate,
                'komisyon_tutari' => $commissionAmount,
                'aciklama'        => $data['aciklama'] ?? null,
                'teslim_edildi'   => isset($data['teslim_edildi']) ? (int)$data['teslim_edildi'] : 0,
            ]);

            return [
                'id'              => $this->db->lastInsertId(),
                'toplam_tutar'    => $totalAmount,
                'komisyon_orani'  => $commissionRate,
                'komisyon_tutari' => $commissionAmount,
            ];
        } catch (PDOException $e) {
            error_log("Job create error: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data)
    {
        try {
            $isTipi    = $data['is_tipi']    ?? 'bizim_isimiz';
            $odemeTipi = $data['odeme_tipi'] ?? null;

            if ($isTipi === 'kendi_isi') {
                $totalAmount      = $data['metrekare'] * $data['birim_fiyat'];
                $commissionRate   = 0;
                $commissionAmount = 0;
                $teslimatTipi     = null;
                $odemeTipi        = null;
            } elseif ($odemeTipi === 'm2_bazli') {
                $totalAmount      = $data['metrekare'] * $data['birim_fiyat'];
                $commissionRate   = 0;
                $commissionAmount = 0;
                $teslimatTipi     = null;
            } else {
                $siparisTutari    = (float) ($data['siparis_tutari'] ?? ($data['metrekare'] * $data['birim_fiyat']));
                $commissionRate   = (float) ($data['komisyon_orani'] ?? 0.4);
                $commissionAmount = $siparisTutari * $commissionRate;
                $totalAmount      = $siparisTutari;
                $teslimatTipi     = $data['teslimat_tipi'] ?? 'alt_firma_teslim';
                $odemeTipi        = 'komisyon_bazli';
            }

            $stmt = $this->db->prepare("
                UPDATE yikama_isleri
                SET is_tipi = :is_tipi,
                    odeme_tipi = :odeme_tipi,
                    tarih = :tarih,
                    metrekare = :metrekare,
                    birim_fiyat = :birim_fiyat,
                    toplam_tutar = :toplam_tutar,
                    musteri_tutari = :musteri_tutari,
                    teslimat_tipi = :teslimat_tipi,
                    komisyon_orani = :komisyon_orani,
                    komisyon_tutari = :komisyon_tutari,
                    aciklama = :aciklama,
                    teslim_edildi = :teslim_edildi
                WHERE id = :id
            ");

            $ok = $stmt->execute([
                'id'              => $id,
                'is_tipi'         => $isTipi,
                'odeme_tipi'      => $odemeTipi,
                'tarih'           => $data['tarih'],
                'metrekare'       => $data['metrekare'],
                'birim_fiyat'     => $data['birim_fiyat'],
                'toplam_tutar'    => $totalAmount,
                'musteri_tutari'  => $data['musteri_tutari'] ?? null,
                'teslimat_tipi'   => $teslimatTipi,
                'komisyon_orani'  => $commissionRate,
                'komisyon_tutari' => $commissionAmount,
                'aciklama'        => $data['aciklama'] ?? null,
                'teslim_edildi'   => isset($data['teslim_edildi']) ? (int)$data['teslim_edildi'] : 0,
            ]);

            return $ok ? [
                'toplam_tutar'    => $totalAmount,
                'komisyon_orani'  => $commissionRate,
                'komisyon_tutari' => $commissionAmount,
            ] : false;
        } catch (PDOException $e) {
            error_log("Job update error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM yikama_isleri WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Job delete error: " . $e->getMessage());
            return false;
        }
    }

    public function toggleTeslim($id, $value)
    {
        try {
            $stmt = $this->db->prepare("UPDATE yikama_isleri SET teslim_edildi = :val WHERE id = :id");
            return $stmt->execute(['id' => $id, 'val' => $value]);
        } catch (PDOException $e) {
            error_log("Job toggleTeslim error: " . $e->getMessage());
            return false;
        }
    }

    public function getByDateRange($startDate, $endDate, $subcontractorId = null)
    {
        try {
            $sql = "
                SELECT id, alt_firma_id, is_tipi, tarih, metrekare, birim_fiyat, toplam_tutar,
                       teslimat_tipi, komisyon_orani, komisyon_tutari, aciklama
                FROM yikama_isleri
                WHERE tarih BETWEEN :start_date AND :end_date
            ";
            $params = ['start_date' => $startDate, 'end_date' => $endDate];
            if ($subcontractorId !== null) {
                $sql .= " AND alt_firma_id = :alt_firma_id";
                $params['alt_firma_id'] = $subcontractorId;
            }
            $sql .= " ORDER BY tarih DESC, id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Job getByDateRange error: " . $e->getMessage());
            return [];
        }
    }
}
