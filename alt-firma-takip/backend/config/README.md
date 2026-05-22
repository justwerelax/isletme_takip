# Database Configuration

This directory contains the database configuration for the Alt Firma Takip Sistemi.

## Files

- **database.php** - Main database configuration class with PDO connection and singleton pattern
- **database.example.php** - Usage examples demonstrating how to use the Database class

## Configuration

The database connection parameters are defined in `database.php`:

```php
private static $host = 'localhost';
private static $database = 'alt_firma_takip';
private static $username = 'root';
private static $password = '';
private static $charset = 'utf8mb4';
```

### Customizing Connection Parameters

To customize the database connection for your environment, edit the static properties in the `Database` class:

1. **$host** - Database server hostname (default: 'localhost')
2. **$database** - Database name (default: 'alt_firma_takip')
3. **$username** - Database username (default: 'root')
4. **$password** - Database password (default: empty string)
5. **$charset** - Character set (default: 'utf8mb4')

## Usage

### Basic Usage

```php
require_once __DIR__ . '/config/database.php';

// Get database instance
$db = Database::getInstance();

// Get PDO connection
$conn = $db->getConnection();

// Execute queries
$stmt = $conn->prepare("SELECT * FROM alt_firma");
$stmt->execute();
$results = $stmt->fetchAll();
```

### Using in Models

```php
class MyModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getData() {
        $stmt = $this->db->query("SELECT * FROM my_table");
        return $stmt->fetchAll();
    }
}
```

## Features

### Singleton Pattern
The Database class implements the singleton pattern to ensure only one database connection exists throughout the application lifecycle.

### Error Handling
Connection errors are caught and logged. A user-friendly error message is thrown as an exception.

### PDO Configuration
The connection is configured with:
- **ERRMODE_EXCEPTION** - Throws exceptions on errors
- **FETCH_ASSOC** - Returns associative arrays by default
- **EMULATE_PREPARES = false** - Uses real prepared statements

### Security
- Prevents cloning of the singleton instance
- Prevents unserialization of the singleton instance
- Uses prepared statements to prevent SQL injection

## Database Setup

Before using the database connection, ensure:

1. MySQL server is running
2. Database `alt_firma_takip` exists
3. Database schema is imported from `backend/schema.sql`
4. Database user has appropriate permissions

### Creating the Database

```sql
CREATE DATABASE alt_firma_takip CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Importing Schema

```bash
mysql -u root -p alt_firma_takip < backend/schema.sql
```

## Troubleshooting

### Connection Failed
If you see "Veritabanı bağlantısı kurulamadı" error:
1. Check MySQL server is running
2. Verify database name, username, and password
3. Ensure database exists
4. Check user permissions

### Character Encoding Issues
The connection uses utf8mb4 charset for full Unicode support including emojis. Ensure your database and tables use the same charset.
