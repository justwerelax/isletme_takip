# Alt Firma Takip Sistemi - API Endpoints Reference

## Base URL
```
http://localhost/isletme-takip-sistemi/alt-firma-takip/backend/api
```

## Authentication
All endpoints (except `/auth/login`) require JWT authentication.

**Header Format**:
```
Authorization: Bearer <your_jwt_token>
```

---

## Authentication Endpoints

### POST /api/auth/login
Login and receive JWT token.

**Request**:
```json
{
  "username": "admin",
  "password": "admin123"
}
```

**Response** (200):
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "username": "admin",
    "name": "Admin User"
  }
}
```

### POST /api/auth/verify
Verify JWT token validity.

**Headers**: `Authorization: Bearer <token>`

**Response** (200):
```json
{
  "success": true,
  "user": {
    "id": 1,
    "username": "admin"
  }
}
```

---

## Subcontractor Endpoints

### GET /api/subcontractors
Get all subcontractors with calculated balances.

**Response** (200):
```json
{
  "success": true,
  "data": {
    "subcontractors": [
      {
        "id": 1,
        "ad": "Yıldız Halı",
        "telefon": "0555 111 2233",
        "adres": "İstanbul",
        "notlar": "",
        "durum": "aktif",
        "balance": 1250.50,
        "created_at": "2024-01-15 10:30:00"
      }
    ],
    "summary": {
      "totalDebt": 2500.00,
      "totalCredit": 500.00
    }
  }
}
```

### GET /api/subcontractors/{id}
Get single subcontractor with jobs and payments.

**Response** (200):
```json
{
  "success": true,
  "data": {
    "subcontractor": {
      "id": 1,
      "ad": "Yıldız Halı",
      "telefon": "0555 111 2233",
      "balance": 1250.50
    },
    "jobs": [...],
    "payments": [...],
    "summary": {
      "totalSquareMeters": 150.00,
      "totalRevenue": 3750.00,
      "totalCommission": 1500.00,
      "jobCount": 5,
      "paymentCount": 3
    }
  }
}
```

### POST /api/subcontractors
Create new subcontractor.

**Request**:
```json
{
  "ad": "Yeni Alt Firma",
  "telefon": "0555 999 8877",
  "adres": "Ankara",
  "notlar": "Test notlar"
}
```

**Response** (201):
```json
{
  "success": true,
  "message": "Alt firma başarıyla eklendi",
  "data": {
    "id": 5
  }
}
```

### PUT /api/subcontractors/{id}
Update subcontractor.

**Request**:
```json
{
  "ad": "Güncellenmiş Ad",
  "telefon": "0555 111 2233",
  "adres": "İstanbul",
  "notlar": "Yeni notlar"
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "Alt firma bilgileri güncellendi"
}
```

### PATCH /api/subcontractors/{id}/status
Toggle subcontractor status (aktif/pasif).

**Response** (200):
```json
{
  "success": true,
  "message": "Durum güncellendi",
  "data": {
    "newStatus": "pasif"
  }
}
```

---

## Job Endpoints

### POST /api/jobs
Create new job with automatic commission calculation.

**Request**:
```json
{
  "alt_firma_id": 1,
  "tarih": "2024-01-15",
  "metrekare": 50.00,
  "birim_fiyat": 25.00,
  "teslimat_tipi": "alt_firma_teslim",
  "aciklama": "Test iş"
}
```

**Validation Rules**:
- `alt_firma_id`: Required, must be valid subcontractor ID
- `tarih`: Required, must be valid date (Y-m-d format)
- `metrekare`: Required, must be positive number
- `birim_fiyat`: Required, must be positive number
- `teslimat_tipi`: Required, must be "alt_firma_teslim" or "ana_firma_teslim"
- `aciklama`: Optional

**Response** (201):
```json
{
  "success": true,
  "message": "İş kaydı başarıyla eklendi",
  "data": {
    "id": 10,
    "toplam_tutar": 1250.00,
    "komisyon_orani": 0.4000,
    "komisyon_tutari": 500.00
  }
}
```

**Commission Calculation**:
- `alt_firma_teslim`: 40% commission
- `ana_firma_teslim`: 30% commission

### PUT /api/jobs/{id}
Update job with recalculation.

**Request**:
```json
{
  "tarih": "2024-01-16",
  "metrekare": 75.00,
  "birim_fiyat": 30.00,
  "teslimat_tipi": "ana_firma_teslim",
  "aciklama": "Updated job"
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "İş kaydı güncellendi",
  "data": {
    "toplam_tutar": 2250.00,
    "komisyon_orani": 0.3000,
    "komisyon_tutari": 675.00
  }
}
```

### DELETE /api/jobs/{id}
Delete job.

**Response** (200):
```json
{
  "success": true,
  "message": "İş kaydı silindi"
}
```

---

## Payment Endpoints

### POST /api/payments
Create new payment.

**Request**:
```json
{
  "alt_firma_id": 1,
  "tarih": "2024-01-20",
  "tutar": 500.00,
  "hareket_tipi": "odeme",
  "aciklama": "Ödeme"
}
```

**Validation Rules**:
- `alt_firma_id`: Required, must be valid subcontractor ID
- `tarih`: Required, must be valid date (Y-m-d format)
- `tutar`: Required, must be positive number
- `hareket_tipi`: Required, must be "odeme" or "tahsilat"
- `aciklama`: Optional

**Response** (201):
```json
{
  "success": true,
  "message": "Para hareketi kaydedildi",
  "data": {
    "id": 5
  }
}
```

**Payment Types**:
- `odeme`: Payment from main company to subcontractor (increases balance)
- `tahsilat`: Collection from subcontractor to main company (decreases balance)

### PUT /api/payments/{id}
Update payment.

**Request**:
```json
{
  "tarih": "2024-01-21",
  "tutar": 750.00,
  "hareket_tipi": "tahsilat",
  "aciklama": "Updated payment"
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "Para hareketi güncellendi"
}
```

### DELETE /api/payments/{id}
Delete payment.

**Response** (200):
```json
{
  "success": true,
  "message": "Para hareketi silindi"
}
```

---

## Report Endpoints

### GET /api/reports/summary
Get summary report for date range.

**Query Parameters**:
- `start_date` (optional): Start date in Y-m-d format (defaults to first day of current month)
- `end_date` (optional): End date in Y-m-d format (defaults to last day of current month)

**Example**:
```
GET /api/reports/summary?start_date=2024-01-01&end_date=2024-01-31
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "dateRange": {
      "start_date": "2024-01-01",
      "end_date": "2024-01-31"
    },
    "summary": {
      "totalJobs": 25,
      "totalSquareMeters": 1250.00,
      "totalRevenue": 31250.00,
      "totalCommission": 12500.00
    },
    "bySubcontractor": [
      {
        "id": 1,
        "ad": "Yıldız Halı",
        "jobCount": 10,
        "totalSquareMeters": 500.00,
        "totalRevenue": 12500.00,
        "totalCommission": 5000.00
      },
      {
        "id": 2,
        "ad": "Güneş Halı",
        "jobCount": 8,
        "totalSquareMeters": 400.00,
        "totalRevenue": 10000.00,
        "totalCommission": 4000.00
      }
    ]
  }
}
```

---

## Error Responses

### Validation Error (400)
```json
{
  "success": false,
  "errors": [
    "metrekare alanı gereklidir",
    "birim_fiyat pozitif bir sayı olmalıdır"
  ]
}
```

### Not Found (404)
```json
{
  "success": false,
  "error": "İş kaydı bulunamadı"
}
```

### Unauthorized (401)
```json
{
  "success": false,
  "error": "Token geçersiz veya süresi dolmuş",
  "code": "INVALID_TOKEN"
}
```

### Server Error (500)
```json
{
  "success": false,
  "error": "İş kaydı oluşturulamadı"
}
```

---

## Testing with cURL

### Login
```bash
curl -X POST http://localhost/isletme-takip-sistemi/alt-firma-takip/backend/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### Create Job
```bash
curl -X POST http://localhost/isletme-takip-sistemi/alt-firma-takip/backend/api/jobs \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "alt_firma_id": 1,
    "tarih": "2024-01-15",
    "metrekare": 50,
    "birim_fiyat": 25,
    "teslimat_tipi": "alt_firma_teslim",
    "aciklama": "Test job"
  }'
```

### Get Report
```bash
curl -X GET "http://localhost/isletme-takip-sistemi/alt-firma-takip/backend/api/reports/summary?start_date=2024-01-01&end_date=2024-01-31" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Notes

1. **Authentication**: All endpoints except `/auth/login` require a valid JWT token in the Authorization header.

2. **Date Format**: All dates must be in `Y-m-d` format (e.g., "2024-01-15").

3. **Numeric Values**: All numeric values (metrekare, birim_fiyat, tutar) must be positive numbers.

4. **Enum Values**:
   - `teslimat_tipi`: "alt_firma_teslim" or "ana_firma_teslim"
   - `hareket_tipi`: "odeme" or "tahsilat"
   - `durum`: "aktif" or "pasif"

5. **Automatic Calculations**:
   - `toplam_tutar` = metrekare × birim_fiyat
   - `komisyon_tutari` = toplam_tutar × komisyon_orani
   - `komisyon_orani` = 0.40 (40%) for alt_firma_teslim, 0.30 (30%) for ana_firma_teslim

6. **Balance Calculation**:
   - Balance = Total Commission - Total Payments + Total Collections
   - Positive balance = Main company owes subcontractor
   - Negative balance = Subcontractor owes main company

7. **CORS**: All endpoints support CORS for cross-origin requests.

8. **Content-Type**: All requests and responses use `application/json; charset=utf-8`.
