# Response Helper Class Documentation

## Overview

The `Response` class provides standardized JSON response formatting for all API endpoints in the Alt Firma Takip Sistemi. It ensures consistent response structure, proper HTTP status codes, and UTF-8 encoding.

## Location

`backend/utils/Response.php`

## Features

- ✅ Standardized JSON response format
- ✅ Proper HTTP status codes
- ✅ UTF-8 character encoding support
- ✅ Pretty-printed JSON output
- ✅ Automatic exit after response
- ✅ Multiple response types (success, error, validation, unauthorized, not found, server error)

## Methods

### 1. `Response::success($data, $message, $statusCode)`

Sends a successful JSON response.

**Parameters:**
- `$data` (mixed, optional): The data to return
- `$message` (string, optional): Success message
- `$statusCode` (int, default: 200): HTTP status code

**Example:**
```php
Response::success(['id' => 1, 'name' => 'Test'], 'İşlem başarılı');
```

**Output:**
```json
{
    "success": true,
    "message": "İşlem başarılı",
    "data": {
        "id": 1,
        "name": "Test"
    }
}
```

### 2. `Response::error($message, $code, $statusCode)`

Sends an error JSON response.

**Parameters:**
- `$message` (string, required): Error message
- `$code` (string, optional): Error code
- `$statusCode` (int, default: 400): HTTP status code

**Example:**
```php
Response::error('Kayıt bulunamadı', 'NOT_FOUND', 404);
```

**Output:**
```json
{
    "success": false,
    "error": "Kayıt bulunamadı",
    "code": "NOT_FOUND"
}
```

### 3. `Response::validationError($errors, $statusCode)`

Sends a validation error response with multiple error messages.

**Parameters:**
- `$errors` (array, required): Array of validation error messages
- `$statusCode` (int, default: 422): HTTP status code

**Example:**
```php
Response::validationError([
    'ad' => 'Ad alanı zorunludur',
    'telefon' => 'Geçerli bir telefon numarası giriniz'
]);
```

**Output:**
```json
{
    "success": false,
    "error": "Doğrulama hatası",
    "code": "VALIDATION_ERROR",
    "errors": {
        "ad": "Ad alanı zorunludur",
        "telefon": "Geçerli bir telefon numarası giriniz"
    }
}
```

### 4. `Response::unauthorized($message)`

Sends a 401 Unauthorized response.

**Parameters:**
- `$message` (string, default: "Yetkisiz erişim"): Error message

**Example:**
```php
Response::unauthorized('Token geçersiz');
```

**Output:**
```json
{
    "success": false,
    "error": "Token geçersiz",
    "code": "UNAUTHORIZED"
}
```

### 5. `Response::notFound($message)`

Sends a 404 Not Found response.

**Parameters:**
- `$message` (string, default: "Kayıt bulunamadı"): Error message

**Example:**
```php
Response::notFound('Alt firma bulunamadı');
```

**Output:**
```json
{
    "success": false,
    "error": "Alt firma bulunamadı",
    "code": "NOT_FOUND"
}
```

### 6. `Response::serverError($message)`

Sends a 500 Internal Server Error response.

**Parameters:**
- `$message` (string, default: "Sunucu hatası"): Error message

**Example:**
```php
Response::serverError('Veritabanı bağlantı hatası');
```

**Output:**
```json
{
    "success": false,
    "error": "Veritabanı bağlantı hatası",
    "code": "SERVER_ERROR"
}
```

## Usage in API Endpoints

The Response class is used throughout all API endpoints:

```php
// Include the Response class
require_once __DIR__ . '/../utils/Response.php';

// Success response
if ($result) {
    Response::success($result, 'İşlem başarılı');
}

// Error response
if (!$subcontractor) {
    Response::notFound('Alt firma bulunamadı');
}

// Validation error
if ($validator->fails()) {
    Response::validationError($validator->getErrors());
}
```

## HTTP Status Codes

The Response class uses appropriate HTTP status codes:

- **200 OK**: Successful GET, PUT, PATCH, DELETE requests
- **201 Created**: Successful POST requests (resource created)
- **400 Bad Request**: General client errors
- **401 Unauthorized**: Authentication required or failed
- **404 Not Found**: Resource not found
- **422 Unprocessable Entity**: Validation errors
- **500 Internal Server Error**: Server-side errors

## Response Format

All responses follow a consistent format:

**Success Response:**
```json
{
    "success": true,
    "message": "Optional message",
    "data": { /* response data */ }
}
```

**Error Response:**
```json
{
    "success": false,
    "error": "Error message",
    "code": "ERROR_CODE"
}
```

## Testing

The Response class has been tested and verified to work correctly:

✅ Success responses with data and message
✅ Error responses with custom codes
✅ Validation error responses with multiple errors
✅ Proper HTTP status codes
✅ UTF-8 encoding for Turkish characters
✅ JSON pretty-printing

## Requirements Met

This implementation satisfies all requirements from Task 3.1:

- ✅ Create `backend/utils/Response.php`
- ✅ Implement `success($data, $message)` method returning JSON
- ✅ Implement `error($message, $code)` method returning JSON error
- ✅ Set proper HTTP status codes and Content-Type headers
- ✅ Additional helper methods for common response types

## Integration

The Response class is integrated with:

- ✅ Authentication middleware (`AuthMiddleware.php`)
- ✅ Subcontractor API (`api/subcontractors.php`)
- ✅ Job API (`api/jobs.php`)
- ✅ Payment API (`api/payments.php`)
- ✅ Report API (`api/reports.php`)

All API endpoints use the Response class for consistent JSON responses.
