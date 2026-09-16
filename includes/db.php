<?php
/**
 * Goolland Database Configuration
 * Version: 3.0.0
 * Description: Database connection and helper functions
 */

// Prevent direct access
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Database Configuration
$host = 'localhost';
$dbname = 'goolland';
$username = 'root';
$password = '';

// Establish PDO database connection only if not already set
if (!isset($pdo) || !$pdo instanceof PDO) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        
        // Set PDO attributes
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        // Set time zone
        $pdo->exec("SET time_zone = '+03:30'"); // Iran Time Zone
        
    } catch (PDOException $e) {
        // Log error to file
        error_log("Database Connection Error: " . $e->getMessage());
        
        // Display user-friendly error
        die("خطا در اتصال به سرور. لطفاً بعداً دوباره امتحان کنید.");
    }
}

// Also create mysqli connection for compatibility
if (!isset($conn) || !$conn instanceof mysqli) {
    try {
        $conn = new mysqli($host, $username, $password, $dbname);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        // Set charset to utf8mb4 for full Unicode support
        if (!$conn->set_charset("utf8mb4")) {
            error_log("Error setting charset: " . $conn->error);
        }
        
        // Set time zone
        $conn->query("SET time_zone = '+03:30'");
        
    } catch (Exception $e) {
        error_log("Database Connection Error (mysqli): " . $e->getMessage());
    }
}

/**
 * Sanitize input data for mysqli
 */
function sanitizeMySQLi($data, $connection) {
    if (is_array($data)) {
        return array_map(function($item) use ($connection) {
            return sanitizeMySQLi($item, $connection);
        }, $data);
    }
    
    if ($data === null) {
        return null;
    }
    
    $data = trim($data);
    $data = strip_tags($data);
    return $connection->real_escape_string($data);
}

/**
 * Sanitize input for general use (not for SQL)
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    if ($data === null) {
        return null;
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get table row count
 */
function getTableCount($table, $condition = '', $params = []) {
    global $pdo;
    
    $sql = "SELECT COUNT(*) as count FROM `$table`";
    
    if ($condition) {
        $sql .= " WHERE $condition";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Check if table exists
 */
function tableExists($table) {
    global $pdo;
    
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        return $result->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get last insert ID
 */
function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

/**
 * Begin transaction
 */
function beginTransaction() {
    global $pdo;
    $pdo->beginTransaction();
}

/**
 * Commit transaction
 */
function commitTransaction() {
    global $pdo;
    $pdo->commit();
}

/**
 * Rollback transaction
 */
function rollbackTransaction() {
    global $pdo;
    $pdo->rollBack();
}

/**
 * Close connections on shutdown
 */
register_shutdown_function(function() {
    global $pdo, $conn;
    
    $pdo = null;
    
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
});

// Include helper functions
require_once __DIR__ . '/functions.php';
