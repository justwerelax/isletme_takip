<?php

require_once __DIR__ . '/../config/database.php';

/**
 * User Model
 * 
 * Handles user authentication and user data retrieval.
 */
class User
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Authenticate user with username and password
     * 
     * @param string $username Username
     * @param string $password Plain text password
     * @return array|false User data (without password) if authenticated, false otherwise
     */
    public function authenticate($username, $password)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, password_hash, name, rol, alt_firma_id, created_at
                FROM users
                WHERE username = :username
                LIMIT 1
            ");
            
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false; // User not found
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return false; // Invalid password
            }
            
            // Remove password hash from returned data
            unset($user['password_hash']);
            
            return $user;
            
        } catch (PDOException $e) {
            error_log("User authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find user by ID
     * 
     * @param int $id User ID
     * @return array|false User data (without password) if found, false otherwise
     */
    public function findById($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, name, rol, alt_firma_id, created_at, updated_at
                FROM users
                WHERE id = :id
                LIMIT 1
            ");
            
            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ?: false;
            
        } catch (PDOException $e) {
            error_log("User findById error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find user by username
     * 
     * @param string $username Username
     * @return array|false User data (without password) if found, false otherwise
     */
    public function findByUsername($username)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, name, created_at, updated_at
                FROM users
                WHERE username = :username
                LIMIT 1
            ");
            
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ?: false;
            
        } catch (PDOException $e) {
            error_log("User findByUsername error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a new user
     * 
     * @param array $data User data (username, password, name)
     * @return int|false New user ID if successful, false otherwise
     */
    public function create($data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (username, password_hash, name)
                VALUES (:username, :password_hash, :name)
            ");
            
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt->execute([
                'username' => $data['username'],
                'password_hash' => $passwordHash,
                'name' => $data['name']
            ]);
            
            return $this->db->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("User create error: " . $e->getMessage());
            return false;
        }
    }
}
