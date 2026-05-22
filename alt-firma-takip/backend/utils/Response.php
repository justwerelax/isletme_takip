<?php

/**
 * Response Helper Class
 * 
 * Provides standardized JSON response formatting for API endpoints.
 * Handles success and error responses with proper HTTP status codes.
 */
class Response
{
    /**
     * Send a success response
     * 
     * @param mixed $data The data to return
     * @param string|null $message Optional success message
     * @param int $statusCode HTTP status code (default: 200)
     * @return void
     */
    public static function success($data = null, $message = null, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => true
        ];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param string|null $code Optional error code
     * @param int $statusCode HTTP status code (default: 400)
     * @return void
     */
    public static function error($message, $code = null, $statusCode = 400)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => false,
            'error' => $message
        ];
        
        if ($code !== null) {
            $response['code'] = $code;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send a validation error response
     * 
     * @param array $errors Array of validation error messages
     * @param int $statusCode HTTP status code (default: 422)
     * @return void
     */
    public static function validationError($errors, $statusCode = 422)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => false,
            'error' => 'Doğrulama hatası',
            'code' => 'VALIDATION_ERROR',
            'errors' => $errors
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send an unauthorized error response
     * 
     * @param string $message Error message (default: "Yetkisiz erişim")
     * @return void
     */
    public static function unauthorized($message = 'Yetkisiz erişim')
    {
        self::error($message, 'UNAUTHORIZED', 401);
    }
    
    /**
     * Send a not found error response
     * 
     * @param string $message Error message (default: "Kayıt bulunamadı")
     * @return void
     */
    public static function notFound($message = 'Kayıt bulunamadı')
    {
        self::error($message, 'NOT_FOUND', 404);
    }
    
    /**
     * Send an internal server error response
     * 
     * @param string $message Error message (default: "Sunucu hatası")
     * @return void
     */
    public static function serverError($message = 'Sunucu hatası')
    {
        self::error($message, 'SERVER_ERROR', 500);
    }
}
