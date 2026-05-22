# Subcontractor API Documentation

## Overview

This document describes all subcontractor API endpoints implemented in `backend/api/subcontractors.php`.

All endpoints require JWT authentication via the `Authorization: Bearer <token>` header.

## Base URL

```
/api/subcontractors
```

## Endpoints

### 1. GET /api/subcontractors

**Description:** Get all subcontractors with calculated balances and summary statistics.

**Authentication:** Required

**Request:**
```http
GET /api/subcontractors HTTP/1.1
Authorization: Bearer <jwt_token>
```

**Response (Success - 200):**
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
        "notlar": "Güvenilir firma",
        "durum": "aktif",
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00",
        "balance": 1250.50
      }
    ],
    "summary": {
      "totalDebt": 2500.00,
      "totalCredit": 500.00
    }
  }
}
```

**Features:**
- Returns all subcontractors ordered by name (ad)
- Calculates balance for each subcontractor
- Provides summary with total debt and credit
- Balance > 0: Main company owes subcontractor
- Balance < 0: Subcontractor owes main company

---

### 2. GET /api/subcontractors/{id}

**Description:** Get single subcontractor with all jobs, payments, and detailed statistics.

**Authentication:** Required

**Request:**
```http
GET /api/subcontractors/1 HTTP/1.1
Authorization: Bearer <jwt_token>
```

**Response (Success - 200):**
```json
{
  "success": true,
  "data": {
    "subcontractor": {
      "id": 1,
      "ad": "Yıldız Halı",
      "telefon": "0555 111 2233",
      "adres": "İstanbul",
      "notlar": "Güvenilir firma",
      "durum": "aktif",
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00",
      "balance": 1250.50
    },
    "jobs": [
      {
        "id": 1,
        "alt_firma_id": 1,
        "tarih": "2024-01-15",
        "metrekare": "50.00",
        "birim_fiyat": "25.00",
        "toplam_tutar": "1250.00",
        "teslimat_tipi": "alt_firma_teslim",
        "komisyon_orani": "0.4000",
        "komisyon_tutari": "500.00",
        "aciklama": "Test iş",
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00"
      }
    ],
    "payments": [
      {
        "id": 1,
        "alt_firma_id": 1,
        "tarih": "2024-01-20",
        "tutar": "500.00",
        "hareket_tipi": "odeme",
        "aciklama": "Ödeme",
        "created_at": "2024-01-20 14:00:00",
        "updated_at": "2024-01-20 14:00:00"
      }
    ],
    "summary": {
      "totalSquareMeters": 150.00,
      "totalRevenue": 3750.00,
      "totalCommission": 1500.00,
      "jobCount": 10,
      "paymentCount": 5
    }
  }
}
```

**Response (Not Found - 404):**
```json
{
  "success": false,
  "error": "Alt firma bulunamadı",
  "code": "NOT_FOUND"
}
```

**Features:**
- Returns complete subcontractor details
- Includes all jobs with commission calculations
- Includes all payment transactions
- Provides comprehensive summary statistics
- Calculates current balance

---

### 3. POST /api/subcontractors

**Description:** Create a new subcontractor.

**Authentication:** Required

**Request:**
```http
POST /api/subcontractors HTTP/1.1
Authorization: Bearer <jwt_token>
Content-Type: application/json

{
  "ad": "Yeni Alt Firma",
  "telefon": "0555 999 8877",
  "adres": "Ankara",
  "notlar": "Test notlar"
}
```

**Request Body Parameters:**
- `ad` (string, required): Subcontractor name
- `telefon` (string, optional): Phone number
- `adres` (string, optional): Address
- `notlar` (string, optional): Notes

**Response (Success - 201):**
```json
{
  "success": true,
  "message": "Alt firma başarıyla eklendi",
  "data": {
    "id": 5
  }
}
```

**Response (Validation Error - 422):**
```json
{
  "success": false,
  "error": "Doğrulama hatası",
  "code": "VALIDATION_ERROR",
  "errors": [
    "ad alanı zorunludur"
  ]
}
```

**Validation Rules:**
- `ad` field is required
- New subcontractor is created with status "aktif" by default

---

### 4. PUT /api/subcontractors/{id}

**Description:** Update an existing subcontractor.

**Authentication:** Required

**Request:**
```http
PUT /api/subcontractors/1 HTTP/1.1
Authorization: Bearer <jwt_token>
Content-Type: application/json

{
  "ad": "Güncellenmiş Ad",
  "telefon": "0555 111 2233",
  "adres": "İstanbul",
  "notlar": "Yeni notlar"
}
```

**Request Body Parameters:**
- `ad` (string, required): Subcontractor name
- `telefon` (string, optional): Phone number
- `adres` (string, optional): Address
- `notlar` (string, optional): Notes

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Alt firma bilgileri güncellendi"
}
```

**Response (Not Found - 404):**
```json
{
  "success": false,
  "error": "Alt firma bulunamadı",
  "code": "NOT_FOUND"
}
```

**Response (Validation Error - 422):**
```json
{
  "success": false,
  "error": "Doğrulama hatası",
  "code": "VALIDATION_ERROR",
  "errors": [
    "ad alanı zorunludur"
  ]
}
```

**Validation Rules:**
- Subcontractor must exist
- `ad` field is required

---

### 5. PATCH /api/subcontractors/{id}/status

**Description:** Toggle subcontractor status between "aktif" and "pasif".

**Authentication:** Required

**Request:**
```http
PATCH /api/subcontractors/1/status HTTP/1.1
Authorization: Bearer <jwt_token>
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Durum güncellendi",
  "data": {
    "newStatus": "pasif"
  }
}
```

**Response (Not Found - 404):**
```json
{
  "success": false,
  "error": "Alt firma bulunamadı",
  "code": "NOT_FOUND"
}
```

**Features:**
- Automatically toggles between "aktif" and "pasif"
- Returns the new status in response
- No request body required

---

## Error Responses

### Authentication Error (401)
```json
{
  "success": false,
  "error": "Yetkisiz erişim",
  "code": "UNAUTHORIZED"
}
```

### Not Found (404)
```json
{
  "success": false,
  "error": "Alt firma bulunamadı",
  "code": "NOT_FOUND"
}
```

### Validation Error (422)
```json
{
  "success": false,
  "error": "Doğrulama hatası",
  "code": "VALIDATION_ERROR",
  "errors": [
    "ad alanı zorunludur"
  ]
}
```

### Server Error (500)
```json
{
  "success": false,
  "error": "Sunucu hatası",
  "code": "SERVER_ERROR"
}
```

---

## Balance Calculation

The balance for each subcontractor is calculated using the formula:

```
Balance = Total Commission - Total Payments + Total Collections
```

Where:
- **Total Commission**: Sum of all `komisyon_tutari` from `yikama_isleri` table
- **Total Payments**: Sum of all `tutar` where `hareket_tipi = 'odeme'` (money paid to subcontractor)
- **Total Collections**: Sum of all `tutar` where `hareket_tipi = 'tahsilat'` (money collected from subcontractor)

**Balance Interpretation:**
- **Positive balance**: Main company owes money to the subcontractor
- **Negative balance**: Subcontractor owes money to the main company
- **Zero balance**: Accounts are balanced

---

## CORS Configuration

All endpoints support CORS with the following headers:
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

OPTIONS requests are handled for CORS preflight.

---

## Testing

Use the provided test script to verify all endpoints:

```bash
php backend/test-subcontractors-api.php
```

Or test with curl:

```bash
# Login first to get token
curl -X POST http://localhost/alt-firma-takip/backend/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'

# Get all subcontractors
curl -X GET http://localhost/alt-firma-takip/backend/api/subcontractors \
  -H "Authorization: Bearer <token>"

# Create subcontractor
curl -X POST http://localhost/alt-firma-takip/backend/api/subcontractors \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"ad":"Test Firma","telefon":"0555 123 4567"}'
```

---

## Implementation Status

✅ **Task 6.1**: Create subcontractor API file - COMPLETED
✅ **Task 6.2**: Implement GET /api/subcontractors - COMPLETED
✅ **Task 6.3**: Implement GET /api/subcontractors/{id} - COMPLETED
✅ **Task 6.4**: Implement POST /api/subcontractors - COMPLETED
✅ **Task 6.5**: Implement PUT /api/subcontractors/{id} - COMPLETED
✅ **Task 6.6**: Implement PATCH /api/subcontractors/{id}/status - COMPLETED

All subcontractor API endpoints are fully implemented and tested!
