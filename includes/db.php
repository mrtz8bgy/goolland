<?php

/**
 * Goolland Database Configuration
 * Version: 2.0.0
 * Description: Enhanced database connection with security and error handling
 */

// Database Configuration
$host = "localhost";
$dbname = "goolland";
$username = "root";
$password = "";

// Establish database connection
try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4 for full Unicode support (including emojis)
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error setting charset: " . $conn->error);
    }
    
    // Set time zone
    $conn->query("SET time_zone = '+03:30'"); // Iran Time Zone
    
} catch (Exception $e) {
    // Log error to file
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Display user-friendly error in development
    if (defined('DEBUG') && DEBUG === true) {
        die("Database Error: " . $e->getMessage());
    } else {
        die("خطا در اتصال به سرور. لطفاً بعداً دوباره امتحان کنید.");
    }
}

// Security: Prevent SQL injection by disabling multi queries
$conn->multi_query = false;

/**
 * Sanitize input data
 * @param mixed $data 
 * @return mixed
 */
function sanitize($data, $conn) {
    if (is_array($data)) {
        return array_map(function($item) use ($conn) {
            return sanitize($item, $conn);
        }, $data);
    }
    
    if ($data === null) {
        return null;
    }
    
    // Trim whitespace
    $data = trim($data);
    
    // Remove HTML tags
    $data = strip_tags($data);
    
    // Escape special characters
    return $conn->real_escape_string($data);
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token 
 * @return bool
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get table row count
 * @param string $table 
 * @param string $condition 
 * @return int
 */
function getTableCount($table, $condition = '', $conn) {
    $query = "SELECT COUNT(*) as count FROM `$table`";
    if ($condition) {
        $query .= " WHERE $condition";
    }
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return (int)($row['count'] ?? 0);
}

/**
 * Check if table exists
 * @param string $table 
 * @return bool
 */
function tableExists($table, $conn) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result->num_rows > 0;
}

/**
 * Get last insert ID
 * @return int
 */
function getLastInsertId($conn) {
    return $conn->insert_id;
}

/**
 * Begin transaction
 */
function beginTransaction($conn) {
    $conn->begin_transaction();
}

/**
 * Commit transaction
 */
function commitTransaction($conn) {
    $conn->commit();
}

/**
 * Rollback transaction
 */
function rollbackTransaction($conn) {
    $conn->rollback();
}

// Close connection on script end
register_shutdown_function(function() use ($conn) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
});

?>
