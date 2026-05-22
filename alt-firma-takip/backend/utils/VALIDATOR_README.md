# Validator Helper Class

The `Validator` class provides a fluent interface for validating input data in API endpoints.

## Features

- **Fluent Interface**: Chain multiple validation rules
- **Comprehensive Validation**: Required, numeric, positive, date, enum, email, length checks
- **Turkish Error Messages**: User-friendly error messages in Turkish
- **Easy Integration**: Simple to use in API endpoints

## Basic Usage

```php
require_once __DIR__ . '/Validator.php';

// Create a new validator instance
$validator = Validator::make();

// Add validation rules
$validator
    ->required($value, 'fieldName')
    ->numeric($value, 'fieldName')
    ->positive($value, 'fieldName');

// Check if validation passed
if ($validator->isValid()) {
    // Validation passed
    echo "All validations passed!";
} else {
    // Validation failed
    $errors = $validator->getErrors();
    // Handle errors
}

// Or get result as array
$result = $validator->getResult();
// Returns: ['valid' => bool, 'errors' => array]
```

## Available Validation Methods

### required($value, $fieldName)
Validates that a field is not empty.

```php
$validator->required($username, 'username');
// Error: "username alanı zorunludur"
```

### numeric($value, $fieldName)
Validates that a field is a numeric value.

```php
$validator->numeric($price, 'price');
// Error: "price sayısal bir değer olmalıdır"
```

### positive($value, $fieldName)
Validates that a field is a positive number (greater than 0).

```php
$validator->positive($amount, 'amount');
// Error: "amount sıfırdan büyük bir değer olmalıdır"
```

### date($value, $fieldName, $format = 'Y-m-d')
Validates that a field is a valid date in the specified format.

```php
$validator->date($date, 'tarih');
// Error: "tarih geçerli bir tarih olmalıdır (format: Y-m-d)"

// Custom format
$validator->date($datetime, 'created_at', 'Y-m-d H:i:s');
```

### enum($value, $allowedValues, $fieldName)
Validates that a field value is in an allowed list.

```php
$validator->enum($type, ['alt_firma_teslim', 'ana_firma_teslim'], 'teslimat_tipi');
// Error: "teslimat_tipi geçerli bir değer olmalıdır. İzin verilen değerler: alt_firma_teslim, ana_firma_teslim"
```

### email($value, $fieldName)
Validates that a field is a valid email address.

```php
$validator->email($email, 'email');
// Error: "email geçerli bir e-posta adresi olmalıdır"
```

### minLength($value, $minLength, $fieldName)
Validates minimum string length.

```php
$validator->minLength($password, 8, 'password');
// Error: "password en az 8 karakter olmalıdır"
```

### maxLength($value, $maxLength, $fieldName)
Validates maximum string length.

```php
$validator->maxLength($description, 500, 'description');
// Error: "description en fazla 500 karakter olmalıdır"
```

## Checking Validation Results

### isValid()
Returns `true` if validation passed (no errors).

```php
if ($validator->isValid()) {
    // Proceed with data processing
}
```

### fails()
Returns `true` if validation failed (has errors).

```php
if ($validator->fails()) {
    // Handle validation errors
}
```

### getErrors()
Returns an array of validation errors.

```php
$errors = $validator->getErrors();
// Returns: ['fieldName' => 'error message', ...]
```

### getResult()
Returns validation result as an array with `valid` boolean and `errors` array.

```php
$result = $validator->getResult();
// Returns: ['valid' => bool, 'errors' => array]
```

## Real-World Examples

### Example 1: Job Creation Validation

```php
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Create validator
$validator = Validator::make();

// Validate job data
$validator
    ->required($data['alt_firma_id'] ?? null, 'alt_firma_id')
    ->numeric($data['alt_firma_id'] ?? null, 'alt_firma_id')
    ->required($data['tarih'] ?? null, 'tarih')
    ->date($data['tarih'] ?? null, 'tarih')
    ->required($data['metrekare'] ?? null, 'metrekare')
    ->numeric($data['metrekare'] ?? null, 'metrekare')
    ->positive($data['metrekare'] ?? null, 'metrekare')
    ->required($data['birim_fiyat'] ?? null, 'birim_fiyat')
    ->numeric($data['birim_fiyat'] ?? null, 'birim_fiyat')
    ->positive($data['birim_fiyat'] ?? null, 'birim_fiyat')
    ->required($data['teslimat_tipi'] ?? null, 'teslimat_tipi')
    ->enum($data['teslimat_tipi'] ?? null, ['alt_firma_teslim', 'ana_firma_teslim'], 'teslimat_tipi');

// Check validation
if ($validator->fails()) {
    Response::error('Geçersiz veri', 400, $validator->getErrors());
    exit;
}

// Proceed with job creation
// ...
```

### Example 2: Payment Creation Validation

```php
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Create validator
$validator = Validator::make();

// Validate payment data
$validator
    ->required($data['alt_firma_id'] ?? null, 'alt_firma_id')
    ->numeric($data['alt_firma_id'] ?? null, 'alt_firma_id')
    ->required($data['tarih'] ?? null, 'tarih')
    ->date($data['tarih'] ?? null, 'tarih')
    ->required($data['tutar'] ?? null, 'tutar')
    ->numeric($data['tutar'] ?? null, 'tutar')
    ->positive($data['tutar'] ?? null, 'tutar')
    ->required($data['hareket_tipi'] ?? null, 'hareket_tipi')
    ->enum($data['hareket_tipi'] ?? null, ['odeme', 'tahsilat'], 'hareket_tipi');

// Check validation
if ($validator->fails()) {
    Response::error('Geçersiz veri', 400, $validator->getErrors());
    exit;
}

// Proceed with payment creation
// ...
```

### Example 3: Subcontractor Creation Validation

```php
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Create validator
$validator = Validator::make();

// Validate subcontractor data
$validator
    ->required($data['ad'] ?? null, 'ad')
    ->maxLength($data['ad'] ?? null, 100, 'ad');

// Optional fields validation (only if provided)
if (isset($data['telefon']) && $data['telefon'] !== '') {
    $validator->maxLength($data['telefon'], 20, 'telefon');
}

// Check validation
if ($validator->fails()) {
    Response::error('Geçersiz veri', 400, $validator->getErrors());
    exit;
}

// Proceed with subcontractor creation
// ...
```

## Integration with Response Helper

The Validator class works seamlessly with the Response helper:

```php
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';

$validator = Validator::make();
$validator->required($value, 'fieldName');

if ($validator->fails()) {
    // Send error response with validation errors
    Response::error('Validation failed', 400, $validator->getErrors());
    exit;
}

// Send success response
Response::success($data, 'Operation successful');
```

## Testing

Run the test script to verify the Validator class:

```bash
php backend/utils/test-validator.php
```

All tests should pass with "✓ PASSED" status.

## Notes

- All error messages are in Turkish for user-facing APIs
- Validation methods can be chained for cleaner code
- Null and empty string values are handled gracefully
- The validator uses strict type checking for enum validation
- Date validation supports custom formats
