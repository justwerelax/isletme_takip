<?php

require_once __DIR__ . '/../config/jwt.php';

/**
 * JWT Helper Class
 * 
 * Provides JSON Web Token generation, verification, and decoding.
 * Uses HS256 algorithm for token signing.
 */
class JWT
{
    /**
     * Generate a JWT token
     * 
     * @param int $userId User ID
     * @param string $username Username
     * @param array $additionalClaims Optional additional claims to include in payload
     * @return string JWT token
     */
    public static function generate($userId, $username, $additionalClaims = [])
    {
        // Header
        $header = [
            'alg' => JWT_ALGORITHM,
            'typ' => 'JWT'
        ];
        
        // Payload
        $payload = [
            'iss' => JWT_ISSUER,
            'user_id' => $userId,
            'username' => $username,
            'iat' => time(),
            'exp' => time() + JWT_EXPIRATION
        ];
        
        // Merge additional claims
        $payload = array_merge($payload, $additionalClaims);
        
        // Encode header and payload
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        // Create signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        // Return complete token
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }
    
    /**
     * Verify a JWT token
     * 
     * @param string $token JWT token to verify
     * @return array|false Returns decoded payload if valid, false if invalid
     */
    public static function verify($token)
    {
        if (empty($token)) {
            return false;
        }
        
        // Split token into parts
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Verify signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);
        $signatureCheck = self::base64UrlEncode($signature);
        
        if ($signatureEncoded !== $signatureCheck) {
            return false; // Invalid signature
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        
        if (!$payload) {
            return false; // Invalid payload
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false; // Token expired
        }
        
        return $payload;
    }
    
    /**
     * Decode a JWT token without verification (use with caution)
     * 
     * @param string $token JWT token to decode
     * @return array|false Returns decoded payload if valid format, false if invalid
     */
    public static function decode($token)
    {
        if (empty($token)) {
            return false;
        }
        
        // Split token into parts
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        
        return $payload ?: false;
    }
    
    /**
     * Extract token from Authorization header
     * 
     * @param string|null $authHeader Authorization header value
     * @return string|null Token or null if not found
     */
    public static function extractFromHeader($authHeader = null)
    {
        // If no header provided, try to get from $_SERVER
        if ($authHeader === null) {
            $authHeader = self::getAuthorizationHeader();
        }
        
        if (empty($authHeader)) {
            return null;
        }
        
        // Check for Bearer token
        if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Get Authorization header from request
     * 
     * @return string|null
     */
    private static function getAuthorizationHeader()
    {
        // Try different methods to get Authorization header
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }
        
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                return $headers['Authorization'];
            }
            if (isset($headers['authorization'])) {
                return $headers['authorization'];
            }
        }
        
        return null;
    }
    
    /**
     * Base64 URL encode
     * 
     * @param string $data Data to encode
     * @return string Encoded string
     */
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     * 
     * @param string $data Data to decode
     * @return string Decoded string
     */
    private static function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Check if a token is expired
     * 
     * @param string $token JWT token
     * @return bool True if expired, false otherwise
     */
    public static function isExpired($token)
    {
        $payload = self::decode($token);
        
        if (!$payload || !isset($payload['exp'])) {
            return true;
        }
        
        return $payload['exp'] < time();
    }
    
    /**
     * Get remaining time until token expiration
     * 
     * @param string $token JWT token
     * @return int|false Seconds until expiration, or false if invalid/expired
     */
    public static function getTimeToExpiration($token)
    {
        $payload = self::decode($token);
        
        if (!$payload || !isset($payload['exp'])) {
            return false;
        }
        
        $remaining = $payload['exp'] - time();
        
        return $remaining > 0 ? $remaining : false;
    }
}
