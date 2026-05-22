<?php

/**
 * JWT Configuration
 * 
 * Configuration settings for JSON Web Token authentication.
 * 
 * IMPORTANT: Change JWT_SECRET to a strong, random string in production!
 * You can generate a secure secret using: openssl rand -base64 32
 */

// JWT Secret Key - CHANGE THIS IN PRODUCTION!
define('JWT_SECRET', 'your-secret-key-change-this-in-production-use-strong-random-string');

// JWT Token Expiration Time (in seconds)
// Default: 24 hours (86400 seconds)
define('JWT_EXPIRATION', 24 * 60 * 60);

// JWT Algorithm
define('JWT_ALGORITHM', 'HS256');

// JWT Issuer (optional)
define('JWT_ISSUER', 'alt-firma-takip-sistemi');
