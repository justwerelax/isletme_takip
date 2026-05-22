<?php

require_once __DIR__ . '/JWT.php';
require_once __DIR__ . '/Response.php';

/**
 * Authentication Middleware
 * 
 * Provides authentication checking for protected API endpoints.
 */
class AuthMiddleware
{
    /**
     * Require authentication for the current request
     * 
     * Checks for valid JWT token in Authorization header.
     * Returns user data if authenticated, sends 401 error and exits if not.
     * 
     * @return array User data from JWT token
     */
    public static function requireAuth()
    {
        // Extract token from Authorization header
        $token = JWT::extractFromHeader();
        
        if (!$token) {
            Response::unauthorized('Token bulunamadı. Lütfen giriş yapın.');
        }
        
        // Verify token
        $payload = JWT::verify($token);
        
        if (!$payload) {
            Response::unauthorized('Geçersiz veya süresi dolmuş token. Lütfen tekrar giriş yapın.');
        }
        
        // Return user data
        return [
            'user_id' => $payload['user_id'],
            'username' => $payload['username']
        ];
    }
    
    /**
     * Optional authentication - returns user data if authenticated, null if not
     * 
     * Does not exit on authentication failure, just returns null.
     * 
     * @return array|null User data if authenticated, null otherwise
     */
    public static function optionalAuth()
    {
        // Extract token from Authorization header
        $token = JWT::extractFromHeader();
        
        if (!$token) {
            return null;
        }
        
        // Verify token
        $payload = JWT::verify($token);
        
        if (!$payload) {
            return null;
        }
        
        // Return user data
        return [
            'user_id' => $payload['user_id'],
            'username' => $payload['username']
        ];
    }
    
    /**
     * Check if current request is authenticated
     * 
     * @return bool True if authenticated, false otherwise
     */
    public static function isAuthenticated()
    {
        $token = JWT::extractFromHeader();
        
        if (!$token) {
            return false;
        }
        
        $payload = JWT::verify($token);
        
        return $payload !== false;
    }
}
