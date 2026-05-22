<?php

require_once __DIR__ . '/../config/database.php';

class Subcontractor
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        try {
            $stmt = $this->db->query("
                SELECT id, ad, telefon, adres, notlar, birim_fiyat, komisyon_orani, durum, created_at, updated_at
                FROM alt_firma
                ORDER BY ad ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Subcontractor getAll error: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, ad, telefon, adres, notlar, birim_fiyat, komisyon_orani, durum, created_at, updated_at
                FROM alt_firma
                WHERE id = :id
                LIMIT 1
            ");
            $stmt->execute(['id' => $id]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC);
            return $sub ?: false;
        } catch (PDOException $e) {
            error_log("Subcontractor getById error: " . $e->getMessage());
            return false;
        }
    }

    public function create($data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO alt_firma (ad, telefon, adres, notlar, birim_fiyat, komisyon_orani, durum)
                VALUES (:ad, :telefon, :adres, :notlar, :birim_fiyat, :komisyon_orani, 'aktif')
            ");
            $stmt->execute([
                'ad'             => $data['ad'],
                'telefon'        => $data['telefon'] ?? null,
                'adres'          => $data['adres'] ?? null,
                'notlar'         => $data['notlar'] ?? null,
                'birim_fiyat'    => $data['birim_fiyat'] ?? 0,
                'komisyon_orani' => $data['komisyon_orani'] ?? 0.4000,
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Subcontractor create error: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE alt_firma
                SET ad = :ad,
                    telefon = :telefon,
                    adres = :adres,
                    notlar = :notlar,
                    birim_fiyat = :birim_fiyat,
                    komisyon_orani = :komisyon_orani
                WHERE id = :id
            ");
            return $stmt->execute([
                'id'             => $id,
                'ad'             => $data['ad'],
                'telefon'        => $data['telefon'] ?? null,
                'adres'          => $data['adres'] ?? null,
                'notlar'         => $data['notlar'] ?? null,
                'birim_fiyat'    => $data['birim_fiyat'] ?? 0,
                'komisyon_orani' => $data['komisyon_orani'] ?? 0.4000,
            ]);
        } catch (PDOException $e) {
            error_log("Subcontractor update error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM alt_firma WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Subcontractor delete error: " . $e->getMessage());
            return false;
        }
    }

    public function toggleStatus($id)
    {
        try {
            $sub = $this->getById($id);
            if (!$sub) return false;
            $newStatus = $sub['durum'] === 'aktif' ? 'pasif' : 'aktif';
            $stmt = $this->db->prepare("UPDATE alt_firma SET durum = :durum WHERE id = :id");
            $ok = $stmt->execute(['id' => $id, 'durum' => $newStatus]);
            return $ok ? $newStatus : false;
        } catch (PDOException $e) {
            error_log("Subcontractor toggleStatus error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Bakiye hesaplama:
     * - kendi_isi: toplam_tutar (borç olarak eklenir)
     * - bizim_isimiz: komisyon_tutari (borç olarak eklenir)
     * - odeme: ödenen tutar (borçtan düşer)
     * - tahsilat: tahsil edilen tutar (borca eklenir)
     */
    public function calculateBalance($id)
    {
        try {
            // Kendi işleri: her zaman hesaba dahil (teslim_edildi filtresi yok)
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(toplam_tutar), 0) as total
                FROM yikama_isleri
                WHERE alt_firma_id = :id AND is_tipi = 'kendi_isi'
            ");
            $stmt->execute(['id' => $id]);
            $kendiIsleri = (float) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Bizim işimiz m² bazlı: sadece teslim edilenler
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(toplam_tutar), 0) as total
                FROM yikama_isleri
                WHERE alt_firma_id = :id AND is_tipi = 'bizim_isimiz' AND odeme_tipi = 'm2_bazli' AND teslim_edildi = 1
            ");
            $stmt->execute(['id' => $id]);
            $bizimM2 = (float) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Bizim işimiz komisyon bazlı - alt firma teslim: sadece teslim edilenler
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(toplam_tutar - komisyon_tutari), 0) as total
                FROM yikama_isleri
                WHERE alt_firma_id = :id
                  AND is_tipi = 'bizim_isimiz'
                  AND odeme_tipi = 'komisyon_bazli'
                  AND teslimat_tipi = 'alt_firma_teslim'
                  AND teslim_edildi = 1
            ");
            $stmt->execute(['id' => $id]);
            $bizimAltTeslim = (float) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Bizim işimiz komisyon bazlı - ana firma teslim: sadece teslim edilenler
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(komisyon_tutari), 0) as total
                FROM yikama_isleri
                WHERE alt_firma_id = :id
                  AND is_tipi = 'bizim_isimiz'
                  AND odeme_tipi = 'komisyon_bazli'
                  AND teslimat_tipi = 'ana_firma_teslim'
                  AND teslim_edildi = 1
            ");
            $stmt->execute(['id' => $id]);
            $bizimAnaTeslim = (float) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Ödemeler: firma bize ödedi → borç azalır
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(tutar), 0) as total
                FROM para_hareketleri
                WHERE alt_firma_id = :id AND hareket_tipi = 'odeme'
            ");
            $stmt->execute(['id' => $id]);
            $odemeler = (float) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Bakiye ekle
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(tutar), 0) as total
                FROM para_hareketleri
                WHERE alt_firma_id = :id AND hareket_tipi = 'bakiye_ekle'
            ");
            $stmt->execute(['id' => $id]);
            $bakiyeEkle = (float) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Negatif = firma bize borçlu, Pozitif = biz firmaya borçluyuz
            // Firma borçları: kendi + m2 + altTeslim(net)
            // Bizim borcumuz: anaTeslim komisyon
            // Ödeme yapılınca firma borcu azalır
            $firmaBorc = $kendiIsleri + $bizimM2 + $bizimAltTeslim;
            $bizimBorc = $bizimAnaTeslim;
            $net = $bizimBorc - $firmaBorc + $odemeler + $bakiyeEkle;

            return $net; // negatif = firma borçlu, pozitif = biz borçluyuz

        } catch (PDOException $e) {
            error_log("Subcontractor calculateBalance error: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Detaylı bakiye özeti (dashboard göstergesi için)
     */
    public function getBalanceSummary($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN is_tipi='kendi_isi' THEN toplam_tutar ELSE 0 END), 0) as kendi_toplam,
                    COALESCE(SUM(CASE WHEN is_tipi='bizim_isimiz' AND odeme_tipi='m2_bazli' AND teslim_edildi=1 THEN toplam_tutar ELSE 0 END), 0) as bizim_m2_toplam,
                    COALESCE(SUM(CASE WHEN is_tipi='bizim_isimiz' AND odeme_tipi='komisyon_bazli' AND teslimat_tipi='alt_firma_teslim' AND teslim_edildi=1 THEN (toplam_tutar - komisyon_tutari) ELSE 0 END), 0) as bizim_alt_net,
                    COALESCE(SUM(CASE WHEN is_tipi='bizim_isimiz' AND odeme_tipi='komisyon_bazli' AND teslimat_tipi='ana_firma_teslim' AND teslim_edildi=1 THEN komisyon_tutari ELSE 0 END), 0) as bizim_ana_komisyon
                FROM yikama_isleri
                WHERE alt_firma_id = :id
            ");
            $stmt->execute(['id' => $id]);
            $jobs = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $this->db->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN hareket_tipi='odeme' THEN tutar ELSE 0 END), 0) as odemeler,
                    COALESCE(SUM(CASE WHEN hareket_tipi='bakiye_ekle' THEN tutar ELSE 0 END), 0) as bakiye_ekle
                FROM para_hareketleri
                WHERE alt_firma_id = :id
            ");
            $stmt->execute(['id' => $id]);
            $payments = $stmt->fetch(PDO::FETCH_ASSOC);

            $kendiToplam    = (float) $jobs['kendi_toplam'];
            $bizimM2        = (float) $jobs['bizim_m2_toplam'];
            $bizimAltNet    = (float) $jobs['bizim_alt_net'];
            $bizimAnaKom    = (float) $jobs['bizim_ana_komisyon'];
            $odemeler       = (float) $payments['odemeler'];
            $bakiyeEkle     = (float) $payments['bakiye_ekle'];

            // Firma bize borçlu toplamı
            $firmaBorc = $kendiToplam + $bizimM2 + $bizimAltNet;
            // Biz firmaya borçlu toplamı
            $bizimBorc = $bizimAnaKom;
            // Net: negatif = firma borçlu, pozitif = biz borçluyuz
            $net = $bizimBorc - $firmaBorc + $odemeler + $bakiyeEkle;

            return [
                'kendi_isleri_toplam'    => $kendiToplam,
                'bizim_islerimiz_toplam' => $bizimM2 + $bizimAltNet,
                'bizim_ana_komisyon'     => $bizimAnaKom,
                'odemeler'               => $odemeler,
                'bakiye_ekle'            => $bakiyeEkle,
                'mevcut_borc'            => $net,
            ];
        } catch (PDOException $e) {
            error_log("Subcontractor getBalanceSummary error: " . $e->getMessage());
            return [
                'kendi_isleri_toplam'    => 0,
                'bizim_islerimiz_toplam' => 0,
                'bizim_ana_komisyon'     => 0,
                'odemeler'               => 0,
                'bakiye_ekle'            => 0,
                'mevcut_borc'            => 0,
            ];
        }
    }

    public function getActive()
    {
        try {
            $stmt = $this->db->query("
                SELECT id, ad, telefon, adres, notlar, birim_fiyat, komisyon_orani, durum
                FROM alt_firma WHERE durum = 'aktif' ORDER BY ad ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Subcontractor getActive error: " . $e->getMessage());
            return [];
        }
    }
}
