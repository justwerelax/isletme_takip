<?php

/**
 * Validator Helper Class
 * 
 * Provides input validation methods for API endpoints.
 * Returns validation results with error messages.
 */
class Validator
{
    /**
     * Validation errors array
     * @var array
     */
    private $errors = [];
    
    /**
     * Validate that a field is required (not empty)
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The field name for error messages
     * @return self
     */
    public function required($value, $fieldName)
    {
        if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
            $this->errors[$fieldName] = "$fieldName alanı zorunludur";
        }
        
        return $this;
    }
    
    /**
     * Validate that a field is numeric
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The field name for error messages
     * @return self
     */
    public function numeric($value, $fieldName)
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$fieldName] = "$fieldName sayısal bir değer olmalıdır";
        }
        
        return $this;
    }
    
    /**
     * Validate that a field is a positive number (greater than 0)
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The field name for error messages
     * @return self
     */
    public function positive($value, $fieldName)
    {
        if ($value !== null && $value !== '' && is_numeric($value) && $value <= 0) {
            $this->errors[$fieldName] = "$fieldName sıfırdan büyük bir değer olmalıdır";
        }
        
        return $this;
    }
    
    /**
     * Validate that a field is a valid date
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The field name for error messages
     * @param string $format Expected date format (default: Y-m-d)
     * @return self
     */
    public function date($value, $fieldName, $format = 'Y-m-d')
    {
        if ($value !== null && $value !== '') {
            $date = \DateTime::createFromFormat($format, $value);
            if (!$date || $date->format($format) !== $value) {
                $this->errors[$fieldName] = "$fieldName geçerli bir tarih olmalıdır (format: $format)";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate that a field value is in an allowed list (enum)
     * 
     * @param mixed $value The value to validate
     * @param array $allowedValues Array of allowed values
     * @param string $fieldName The field name for error messages
     * @return self
     */
    public function enum($value, $allowedValues, $fieldName)
    {
        if ($value !== null && $value !== '' && !in_array($value, $allowedValues, true)) {
            $allowedList = implode(', ', $allowedValues);
            $this->errors[$fieldName] = "$fieldName geçerli bir değer olmalıdır. İzin verilen değerler: $allowedList";
        }
        
        return $this;
    }
    
    /**
     * Validate that a field is a valid email address
     * 
     * @param mixed $value The value to validate
     * @param string $fieldName The field name for error messages
     * @return self
     */
    public function email($value, $fieldName)
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$fieldName] = "$fieldName geçerli bir e-posta adresi olmalıdır";
        }
        
        return $this;
    }
    
    /**
     * Validate minimum length
     * 
     * @param mixed $value The value to validate
     * @param int $minLength Minimum length
     * @param string $fieldName The field name for error messages
     * @return self
     */
    public function minLength($value, $minLength, $fieldName)
    {
        if ($value !== null && $value !== '' && is_string($value) && strlen($value) < $minLength) {
            $this->errors[$fieldName] = "$fieldName en az $minLength karakter olmalıdır";
        }
        
        return $this;
    }
    
    /**
     * Validate maximum length
     * 
     * @param mixed $value The value to validate
     * @param int $maxLength Maximum length
     * @param string $fieldName The field name for error messages
     * @return self
     */
    public function maxLength($value, $maxLength, $fieldName)
    {
        if ($value !== null && $value !== '' && is_string($value) && strlen($value) > $maxLength) {
            $this->errors[$fieldName] = "$fieldName en fazla $maxLength karakter olmalıdır";
        }
        
        return $this;
    }
    
    /**
     * Check if validation passed (no errors)
     * 
     * @return bool
     */
    public function isValid()
    {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed (has errors)
     * 
     * @return bool
     */
    public function fails()
    {
        return !$this->isValid();
    }
    
    /**
     * Get all validation errors
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Get validation result as array
     * 
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function getResult()
    {
        return [
            'valid' => $this->isValid(),
            'errors' => $this->errors
        ];
    }
    
    /**
     * Static method to create a new validator instance
     * 
     * @return self
     */
    public static function make()
    {
        return new self();
    }
}
