<?php
/**
 * Goolland - Luxury Flower & Plant Shop
 * Functions File
 */

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Sanitize output data
 */
function sanitizeOutput($data) {
    if (is_array($data)) {
        return array_map('sanitizeOutput', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Check if admin is logged in (for admin panel)
 */
function isAdminLoggedIn() {
    // Check session-based login
    if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0 && isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        return true;
    }
    
    // Check cookie-based login
    if (isset($_COOKIE['admin_token']) && isset($_COOKIE['admin_id'])) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT token, expiry FROM admin_sessions WHERE admin_id = ? AND token = ?");
            $stmt->execute([$_COOKIE['admin_id'], $_COOKIE['admin_token']]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session && strtotime($session['expiry']) > time()) {
                // Refresh session
                $_SESSION['admin_id'] = $_COOKIE['admin_id'];
                $_SESSION['admin_logged_in'] = true;
                return true;
            }
        } catch (PDOException $e) {
            return false;
        }
    }
    
    return false;
}

/**
 * Check if user is admin (for frontend)
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Get site setting
 */
function getSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Set site setting
 */
function setSetting($key, $value) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        return $stmt->execute([$key, $value, $value]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Format price with currency
 */
function formatPrice($price) {
    return number_format($price) . ' تومان';
}

/**
 * Format number with commas
 */
function formatNumber($number) {
    return number_format($number);
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'Y/m/d') {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Format date in Persian
 */
function formatPersianDate($date) {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    $year = date('Y', $timestamp);
    $month = date('m', $timestamp);
    $day = date('d', $timestamp);
    
    // Convert to Persian calendar (simple conversion - for accurate use a library)
    $persianYear = $year - 621;
    
    return toPersianNumbers($persianYear) . '/' . toPersianNumbers($month) . '/' . toPersianNumbers($day);
}

/**
 * Convert numbers to Persian
 */
function toPersianNumbers($number) {
    $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str_replace(range(0, 9), $persianDigits, (string)$number);
}

/**
 * Convert Persian numbers to English
 */
function toEnglishNumbers($string) {
    $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str_replace($persianDigits, range(0, 9), $string);
}

/**
 * Get order status text
 */
function getOrderStatusText($status) {
    $statuses = [
        'pending' => 'در انتظار',
        'processing' => 'در حال پردازش',
        'shipped' => 'ارسال شده',
        'delivered' => 'تحویل داده شده',
        'completed' => 'تکمیل شده',
        'cancelled' => 'کنسل شده'
    ];
    return $statuses[$status] ?? $status;
}

/**
 * Get payment method text
 */
function getPaymentMethodText($method) {
    $methods = [
        'cash' => 'پرداخت در محل',
        'online' => 'پرداخت آنلاین',
        'transfer' => 'واریز بانکی',
        'wallet' => 'کیف پول'
    ];
    return $methods[$method] ?? $method;
}

/**
 * Get payment status text
 */
function getPaymentStatusText($status) {
    $statuses = [
        'pending' => 'در انتظار پرداخت',
        'paid' => 'پرداخت شده',
        'failed' => 'پرداخت ناموفق',
        'refunded' => 'عوض شده'
    ];
    return $statuses[$status] ?? $status;
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Generate order number
 */
function generateOrderNumber() {
    return 'GO-' . date('Ymd') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Generate invoice number
 */
function generateInvoiceNumber() {
    return 'INV-' . date('YmdHis') . '-' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
}

/**
 * Upload image
 */
function uploadImage($file, $uploadPath = 'assets/images/uploads/') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }
    
    // Create upload directory if not exists
    $fullPath = ROOT_PATH . DS . $uploadPath;
    if (!file_exists($fullPath)) {
        mkdir($fullPath, 0755, true);
    }
    
    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $destination = $fullPath . $filename;
    
    // Check if file is an image
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $uploadPath . $filename;
    }
    
    return false;
}

/**
 * Delete image
 */
function deleteImage($imagePath) {
    if (empty($imagePath)) return true;
    
    $fullPath = ROOT_PATH . DS . $imagePath;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return true;
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get user by email
 */
function getUserByEmail($email) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get product by ID
 */
function getProductById($productId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get category by ID
 */
function getCategoryById($categoryId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get order by ID
 */
function getOrderById($orderId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get order items by order ID
 */
function getOrderItemsByOrderId($orderId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.price as product_price FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get cart count for user
 */
function getCartCount($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get wishlist count for user
 */
function getWishlistCount($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Check if product is in wishlist
 */
function isInWishlist($userId, $productId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        return $stmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get product images
 */
function getProductImages($productId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get product primary image
 */
function getProductPrimaryImage($productId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1");
        $stmt->execute([$productId]);
        $result = $stmt->fetchColumn();
        return $result ? $result : getSetting('default_product_image', 'assets/images/no-image.jpg');
    } catch (PDOException $e) {
        return getSetting('default_product_image', 'assets/images/no-image.jpg');
    }
}

/**
 * Get featured products
 */
function getFeaturedProducts($limit = 8) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_featured = 1 AND p.stock > 0 ORDER BY p.created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get new arrival products
 */
function getNewArrivalProducts($limit = 8) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock > 0 ORDER BY p.created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get best selling products
 */
function getBestSellingProducts($limit = 8) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, COUNT(oi.id) as sales_count FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN order_items oi ON p.id = oi.product_id WHERE p.stock > 0 GROUP BY p.id ORDER BY sales_count DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get products by category
 */
function getProductsByCategory($categoryId, $limit = null) {
    global $pdo;
    $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.stock > 0 ORDER BY p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$categoryId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    } else {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$categoryId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

/**
 * Get all categories
 */
function getAllCategories() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get category tree
 */
function getCategoryTree($parentId = 0, $level = 0) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order ASC, name ASC");
        $stmt->execute([$parentId]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        foreach ($categories as $category) {
            $category['children'] = getCategoryTree($category['id'], $level + 1);
            $result[] = $category;
        }
        
        return $result;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Search products
 */
function searchProducts($query, $categoryId = null, $minPrice = null, $maxPrice = null, $limit = null) {
    global $pdo;
    
    $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock > 0";
    $params = [];
    
    if (!empty($query)) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$query%";
        $params[] = "%$query%";
    }
    
    if ($categoryId) {
        $sql .= " AND p.category_id = ?";
        $params[] = $categoryId;
    }
    
    if ($minPrice !== null) {
        $sql .= " AND p.price >= ?";
        $params[] = $minPrice;
    }
    
    if ($maxPrice !== null) {
        $sql .= " AND p.price <= ?";
        $params[] = $maxPrice;
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get related products
 */
function getRelatedProducts($productId, $categoryId, $limit = 4) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? AND p.stock > 0 ORDER BY RAND() LIMIT ?");
        $stmt->execute([$categoryId, $productId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get product reviews
 */
function getProductReviews($productId, $limit = null) {
    global $pdo;
    $sql = "SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.is_approved = 1 ORDER BY r.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$productId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    } else {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$productId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

/**
 * Get average product rating
 */
function getAverageProductRating($productId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT AVG(rating) as average_rating FROM reviews WHERE product_id = ? AND is_approved = 1");
        $stmt->execute([$productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['average_rating'] ?? 0);
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get review count for product
 */
function getProductReviewCount($productId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as review_count FROM reviews WHERE product_id = ? AND is_approved = 1");
        $stmt->execute([$productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['review_count'] ?? 0);
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get shipping cost
 */
function getShippingCost() {
    return (float)getSetting('shipping_cost', 0);
}

/**
 * Get free shipping threshold
 */
function getFreeShippingThreshold() {
    return (float)getSetting('min_order_amount', 0);
}

/**
 * Calculate cart total
 */
function calculateCartTotal($cartItems) {
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $product = getProductById($item['product_id']);
        if ($product) {
            $subtotal += $product['price'] * $item['quantity'];
        }
    }
    
    $shippingCost = getShippingCost();
    $freeShippingThreshold = getFreeShippingThreshold();
    
    if ($subtotal >= $freeShippingThreshold) {
        $shippingCost = 0;
    }
    
    $total = $subtotal + $shippingCost;
    
    return [
        'subtotal' => $subtotal,
        'shipping' => $shippingCost,
        'total' => $total
    ];
}

/**
 * Send email
 */
function sendEmail($to, $subject, $message, $headers = []) {
    $defaultHeaders = [
        'From: ' . getSetting('site_name', 'گولند') . ' <' . getSetting('site_email', 'info@goolland.ir') . '>',
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    $allHeaders = array_merge($defaultHeaders, $headers);
    
    return mail($to, $subject, $message, implode("\r\n", $allHeaders));
}

/**
 * Send SMS (placeholder - implement with SMS gateway)
 */
function sendSMS($phone, $message) {
    // This is a placeholder - implement with actual SMS gateway API
    return true;
}

/**
 * Generate pagination
 */
function generatePagination($currentPage, $totalPages, $baseUrl = '') {
    $pagination = [];
    
    // Previous button
    if ($currentPage > 1) {
        $pagination[] = [
            'page' => $currentPage - 1,
            'text' => 'قبلی',
            'active' => false
        ];
    }
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $pagination[] = [
            'page' => 1,
            'text' => '1',
            'active' => false
        ];
        
        if ($startPage > 2) {
            $pagination[] = [
                'page' => null,
                'text' => '...',
                'active' => false,
                'disabled' => true
            ];
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $pagination[] = [
            'page' => $i,
            'text' => toPersianNumbers($i),
            'active' => $i === $currentPage
        ];
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $pagination[] = [
                'page' => null,
                'text' => '...',
                'active' => false,
                'disabled' => true
            ];
        }
        
        $pagination[] = [
            'page' => $totalPages,
            'text' => toPersianNumbers($totalPages),
            'active' => false
        ];
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $pagination[] = [
            'page' => $currentPage + 1,
            'text' => 'بعدی',
            'active' => false
        ];
    }
    
    return $pagination;
}

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    global $pdo;
    
    $stats = [
        'total_users' => 0,
        'total_products' => 0,
        'total_orders' => 0,
        'total_revenue' => 0,
        'pending_orders' => 0,
        'completed_orders' => 0
    ];
    
    try {
        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $stats['total_users'] = (int)$stmt->fetchColumn();
        
        // Total products
        $stmt = $pdo->query("SELECT COUNT(*) FROM products");
        $stats['total_products'] = (int)$stmt->fetchColumn();
        
        // Total orders
        $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
        $stats['total_orders'] = (int)$stmt->fetchColumn();
        
        // Total revenue
        $stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'");
        $stats['total_revenue'] = (float)($stmt->fetchColumn() ?? 0);
        
        // Pending orders
        $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
        $stats['pending_orders'] = (int)$stmt->fetchColumn();
        
        // Completed orders
        $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'");
        $stats['completed_orders'] = (int)$stmt->fetchColumn();
        
    } catch (PDOException $e) {
        // Return default stats on error
    }
    
    return $stats;
}

/**
 * Get recent orders
 */
function getRecentOrders($limit = 10) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get recent users
 */
function getRecentUsers($limit = 10) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get low stock products
 */
function getLowStockProducts($threshold = 5) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock <= ? AND p.stock > 0 ORDER BY p.stock ASC");
        $stmt->execute([$threshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get out of stock products
 */
function getOutOfStockProducts() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock = 0 ORDER BY p.name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get sales report
 */
function getSalesReport($startDate, $endDate) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT DATE(created_at) as date, COUNT(*) as order_count, SUM(total_amount) as total_sales FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY date");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get top selling products
 */
function getTopSellingProducts($limit = 10) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT p.*, COUNT(oi.id) as quantity_sold FROM products p LEFT JOIN order_items oi ON p.id = oi.product_id GROUP BY p.id ORDER BY quantity_sold DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get user orders
 */
function getUserOrders($userId, $limit = null) {
    global $pdo;
    $sql = "SELECT o.*, u.name as user_name, u.email as user_email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.user_id = ? ORDER BY o.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    } else {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

/**
 * Get order status count
 */
function getOrderStatusCount() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counts = [];
        foreach ($results as $result) {
            $counts[$result['status']] = (int)$result['count'];
        }
        
        return $counts;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get payment method count
 */
function getPaymentMethodCount() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT payment_method, COUNT(*) as count FROM orders GROUP BY payment_method");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counts = [];
        foreach ($results as $result) {
            $counts[$result['payment_method']] = (int)$result['count'];
        }
        
        return $counts;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get monthly sales
 */
function getMonthlySales($year) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT MONTH(created_at) as month, SUM(total_amount) as total FROM orders WHERE YEAR(created_at) = ? AND payment_status = 'paid' GROUP BY MONTH(created_at) ORDER BY month");
        $stmt->execute([$year]);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sales = array_fill(1, 12, 0);
        
        foreach ($results as $result) {
            $sales[(int)$result['month']] = (float)$result['total'];
        }
        
        return $sales;
    } catch (PDOException $e) {
        return array_fill(1, 12, 0);
    }
}

/**
 * Get category sales
 */
function getCategorySales($startDate = null, $endDate = null) {
    global $pdo;
    
    $sql = "SELECT c.id, c.name, COUNT(oi.id) as quantity, SUM(p.price * oi.quantity) as total_sales FROM categories c LEFT JOIN products p ON c.id = p.category_id LEFT JOIN order_items oi ON p.id = oi.product_id LEFT JOIN orders o ON oi.order_id = o.id";
    $params = [];
    
    if ($startDate && $endDate) {
        $sql .= " WHERE o.created_at BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
    } elseif ($startDate) {
        $sql .= " WHERE o.created_at >= ?";
        $params[] = $startDate;
    }
    
    $sql .= " GROUP BY c.id, c.name ORDER BY total_sales DESC";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get customer statistics
 */
function getCustomerStats() {
    global $pdo;
    
    $stats = [
        'total' => 0,
        'new_this_month' => 0,
        'active' => 0,
        'inactive' => 0
    ];
    
    try {
        // Total customers
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
        $stats['total'] = (int)$stmt->fetchColumn();
        
        // New this month
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND YEAR(created_at) = ? AND MONTH(created_at) = ?");
        $stmt->execute([date('Y'), date('m')]);
        $stats['new_this_month'] = (int)$stmt->fetchColumn();
        
        // Active customers (ordered in last 6 months)
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)");
        $stmt->execute();
        $stats['active'] = (int)$stmt->fetchColumn();
        
        // Inactive customers
        $stats['inactive'] = $stats['total'] - $stats['active'];
        
    } catch (PDOException $e) {
        // Return default stats on error
    }
    
    return $stats;
}

/**
 * Get product statistics
 */
function getProductStats() {
    global $pdo;
    
    $stats = [
        'total' => 0,
        'published' => 0,
        'draft' => 0,
        'featured' => 0,
        'in_stock' => 0,
        'out_of_stock' => 0
    ];
    
    try {
        // Total products
        $stmt = $pdo->query("SELECT COUNT(*) FROM products");
        $stats['total'] = (int)$stmt->fetchColumn();
        
        // Published products
        $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
        $stats['published'] = (int)$stmt->fetchColumn();
        
        // Draft products
        $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 0");
        $stats['draft'] = (int)$stmt->fetchColumn();
        
        // Featured products
        $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_featured = 1");
        $stats['featured'] = (int)$stmt->fetchColumn();
        
        // In stock
        $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0");
        $stats['in_stock'] = (int)$stmt->fetchColumn();
        
        // Out of stock
        $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0");
        $stats['out_of_stock'] = (int)$stmt->fetchColumn();
        
    } catch (PDOException $e) {
        // Return default stats on error
    }
    
    return $stats;
}

/**
 * Log activity
 */
function logActivity($userId, $action, $details = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO activities (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get activities
 */
function getActivities($userId = null, $limit = 20) {
    global $pdo;
    
    $sql = "SELECT a.*, u.name as user_name FROM activities a LEFT JOIN users u ON a.user_id = u.id";
    $params = [];
    
    if ($userId) {
        $sql .= " WHERE a.user_id = ?";
        $params[] = $userId;
    }
    
    $sql .= " ORDER BY a.created_at DESC LIMIT ?";
    $params[] = $limit;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get user profile
 */
function getUserProfile($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update user profile
 */
function updateUserProfile($userId, $data) {
    global $pdo;
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'password' && $key !== 'confirm_password') {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    // Handle password separately
    if (!empty($data['password'])) {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $fields[] = "password = ?";
        $values[] = $hashedPassword;
    }
    
    $values[] = $userId;
    
    if (empty($fields)) return false;
    
    try {
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create user
 */
function createUser($data) {
    global $pdo;
    
    $fields = ['name', 'email', 'phone', 'address', 'is_admin', 'is_active'];
    $placeholders = [];
    $values = [];
    
    foreach ($fields as $field) {
        $placeholders[] = "?";
        $values[] = $data[$field] ?? null;
    }
    
    // Add password
    if (!empty($data['password'])) {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $fields[] = 'password';
        $placeholders[] = "?";
        $values[] = $hashedPassword;
    }
    
    $fields[] = 'created_at';
    $placeholders[] = "NOW()";
    
    try {
        $sql = "INSERT INTO users (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete user
 */
function deleteUser($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create product
 */
function createProduct($data) {
    global $pdo;
    
    $fields = ['name', 'description', 'category_id', 'price', 'stock', 'sku', 'weight', 'dimensions', 'is_featured', 'is_active', 'meta_title', 'meta_description', 'meta_keywords'];
    $placeholders = [];
    $values = [];
    
    foreach ($fields as $field) {
        if (isset($data[$field])) {
            $placeholders[] = "?";
            $values[] = $data[$field];
        }
    }
    
    $fields[] = 'created_at';
    $placeholders[] = "NOW()";
    
    try {
        $sql = "INSERT INTO products (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update product
 */
function updateProduct($productId, $data) {
    global $pdo;
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id' && $key !== 'created_at' && isset($value)) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($fields)) return false;
    
    $values[] = $productId;
    
    try {
        $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete product
 */
function deleteProduct($productId) {
    global $pdo;
    try {
        // Delete product images first
        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $stmt->execute([$productId]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($images as $image) {
            deleteImage($image['image_path']);
        }
        
        // Delete product images from database
        $stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->execute([$productId]);
        
        // Delete product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$productId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create category
 */
function createCategory($data) {
    global $pdo;
    
    $fields = ['name', 'description', 'parent_id', 'image_path', 'sort_order', 'is_active', 'meta_title', 'meta_description', 'meta_keywords'];
    $placeholders = [];
    $values = [];
    
    foreach ($fields as $field) {
        if (isset($data[$field])) {
            $placeholders[] = "?";
            $values[] = $data[$field];
        }
    }
    
    $fields[] = 'created_at';
    $placeholders[] = "NOW()";
    
    try {
        $sql = "INSERT INTO categories (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update category
 */
function updateCategory($categoryId, $data) {
    global $pdo;
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id' && $key !== 'created_at' && isset($value)) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($fields)) return false;
    
    $values[] = $categoryId;
    
    try {
        $sql = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete category
 */
function deleteCategory($categoryId) {
    global $pdo;
    try {
        // Check if category has products
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $productCount = $stmt->fetchColumn();
        
        if ($productCount > 0) {
            return false; // Cannot delete category with products
        }
        
        // Delete category image
        $stmt = $pdo->prepare("SELECT image_path FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $imagePath = $stmt->fetchColumn();
        if ($imagePath) {
            deleteImage($imagePath);
        }
        
        // Delete category
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$categoryId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create order
 */
function createOrder($data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Insert order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, shipping_cost, discount_amount, final_amount, payment_method, payment_status, status, shipping_address, billing_address, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $data['user_id'],
            generateOrderNumber(),
            $data['total_amount'],
            $data['shipping_cost'],
            $data['discount_amount'] ?? 0,
            $data['final_amount'],
            $data['payment_method'],
            $data['payment_status'] ?? 'pending',
            $data['status'] ?? 'pending',
            $data['shipping_address'] ?? '',
            $data['billing_address'] ?? '',
            $data['notes'] ?? ''
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Insert order items
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['total_price']
                ]);
                
                // Update product stock
                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
        }
        
        // Clear cart
        if (isset($data['user_id']) && $data['user_id'] > 0) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$data['user_id']]);
        }
        
        $pdo->commit();
        return $orderId;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Update order
 */
function updateOrder($orderId, $data) {
    global $pdo;
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id' && $key !== 'created_at' && isset($value)) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($fields)) return false;
    
    $values[] = $orderId;
    
    try {
        $sql = "UPDATE orders SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete order
 */
function deleteOrder($orderId) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        // Get order items
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Restore product stock
        foreach ($items as $item) {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Delete order items
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        
        // Delete order
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Add to cart
 */
function addToCart($userId, $productId, $quantity = 1) {
    global $pdo;
    
    try {
        // Check if product already in cart
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update quantity
            $newQuantity = $existing['quantity'] + $quantity;
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            return $stmt->execute([$newQuantity, $existing['id']]);
        } else {
            // Add new item
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
            return $stmt->execute([$userId, $productId, $quantity]);
        }
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update cart item
 */
function updateCartItem($cartId, $quantity) {
    global $pdo;
    
    if ($quantity <= 0) {
        return deleteCartItem($cartId);
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        return $stmt->execute([$quantity, $cartId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete cart item
 */
function deleteCartItem($cartId, $userId = null) {
    global $pdo;
    try {
        if ($userId === null) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ?");
            return $stmt->execute([$cartId]);
        }

        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cartId, $userId]);
        return ['success' => true, 'message' => 'آیتم از سبد خرید حذف شد'];
    } catch (PDOException $e) {
        if ($userId === null) {
            return false;
        }
        return ['success' => false, 'message' => 'خطا در حذف آیتم: ' . $e->getMessage()];
    }
}

/**
 * Clear cart
 */
function clearCart($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get cart items
 */
function getCartItems($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT c.*, p.name as product_name, p.price as product_price, p.image_path as product_image, p.stock as product_stock FROM cart c LEFT JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Add to wishlist
 */
function addToWishlist($userId, $productId) {
    global $pdo;
    try {
        // Check if already in wishlist
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        if ($stmt->fetchColumn()) {
            return true; // Already in wishlist
        }
        
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");
        return $stmt->execute([$userId, $productId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Remove from wishlist
 */
function removeFromWishlist($userId, $productId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$userId, $productId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get wishlist items
 */
function getWishlistItems($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT w.*, p.name as product_name, p.price as product_price, p.image_path as product_image, p.stock as product_stock FROM wishlist w LEFT JOIN products p ON w.product_id = p.id WHERE w.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Add product image
 */
function addProductImage($productId, $imagePath, $isPrimary = false) {
    global $pdo;
    try {
        // If this is primary, unset other primary images
        if ($isPrimary) {
            $stmt = $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
            $stmt->execute([$productId]);
        }
        
        $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary, created_at) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$productId, $imagePath, $isPrimary ? 1 : 0]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete product image
 */
function deleteProductImage($imageId) {
    global $pdo;
    try {
        // Get image path
        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ?");
        $stmt->execute([$imageId]);
        $imagePath = $stmt->fetchColumn();
        
        // Delete image file
        if ($imagePath) {
            deleteImage($imagePath);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
        return $stmt->execute([$imageId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Set primary product image
 */
function setPrimaryProductImage($imageId) {
    global $pdo;
    try {
        // Get product ID
        $stmt = $pdo->prepare("SELECT product_id FROM product_images WHERE id = ?");
        $stmt->execute([$imageId]);
        $productId = $stmt->fetchColumn();
        
        if ($productId) {
            // Unset other primary images
            $stmt = $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
            $stmt->execute([$productId]);
            
            // Set this image as primary
            $stmt = $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?");
            return $stmt->execute([$imageId]);
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create review
 */
function createReview($data) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, product_id, rating, title, comment, is_approved, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $data['user_id'],
            $data['product_id'],
            $data['rating'],
            $data['title'] ?? '',
            $data['comment'] ?? '',
            $data['is_approved'] ?? 0
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get reviews
 */
function getReviews($productId = null, $userId = null, $isApproved = null, $limit = null) {
    global $pdo;
    
    $sql = "SELECT r.*, u.name as user_name, p.name as product_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id LEFT JOIN products p ON r.product_id = p.id";
    $params = [];
    
    $conditions = [];
    
    if ($productId) {
        $conditions[] = "r.product_id = ?";
        $params[] = $productId;
    }
    
    if ($userId) {
        $conditions[] = "r.user_id = ?";
        $params[] = $userId;
    }
    
    if ($isApproved !== null) {
        $conditions[] = "r.is_approved = ?";
        $params[] = $isApproved;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $sql .= " ORDER BY r.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Approve review
 */
function approveReview($reviewId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?");
        return $stmt->execute([$reviewId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete review
 */
function deleteReview($reviewId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        return $stmt->execute([$reviewId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get contact messages
 */
function getContactMessages($limit = null) {
    global $pdo;
    $sql = "SELECT * FROM contact_messages ORDER BY created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    } else {
        try {
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

/**
 * Create contact message
 */
function createContactMessage($data) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? '',
            $data['subject'],
            $data['message']
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Mark message as read
 */
function markMessageAsRead($messageId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$messageId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete message
 */
function deleteMessage($messageId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        return $stmt->execute([$messageId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get newsletter subscribers
 */
function getNewsletterSubscribers($limit = null) {
    global $pdo;
    $sql = "SELECT * FROM newsletter_subscribers ORDER BY created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    } else {
        try {
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

/**
 * Subscribe to newsletter
 */
function subscribeToNewsletter($email) {
    global $pdo;
    try {
        // Check if already subscribed
        $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            return false; // Already subscribed
        }
        
        $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, is_active, created_at) VALUES (?, 1, NOW())");
        return $stmt->execute([$email]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Unsubscribe from newsletter
 */
function unsubscribeFromNewsletter($email) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET is_active = 0 WHERE email = ?");
        return $stmt->execute([$email]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete subscriber
 */
function deleteSubscriber($subscriberId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
        return $stmt->execute([$subscriberId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Send newsletter
 */
function sendNewsletter($subject, $message, $subscriberIds = null) {
    global $pdo;
    
    try {
        if ($subscriberIds) {
            $stmt = $pdo->prepare("SELECT email FROM newsletter_subscribers WHERE id IN (" . implode(',', array_fill(0, count($subscriberIds), '?')) . ") AND is_active = 1");
            $stmt->execute($subscriberIds);
        } else {
            $stmt = $pdo->query("SELECT email FROM newsletter_subscribers WHERE is_active = 1");
        }
        
        $subscribers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $successCount = 0;
        
        foreach ($subscribers as $email) {
            if (sendEmail($email, $subject, $message)) {
                $successCount++;
            }
        }
        
        return $successCount;
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Create coupon
 */
function createCoupon($data) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_discount_amount, start_date, end_date, usage_limit, usage_count, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([
            $data['code'],
            $data['discount_type'] ?? 'percentage',
            $data['discount_value'],
            $data['min_order_amount'] ?? 0,
            $data['max_discount_amount'] ?? 0,
            $data['start_date'] ?? date('Y-m-d'),
            $data['end_date'] ?? date('Y-m-d', strtotime('+30 days')),
            $data['usage_limit'] ?? 0,
            $data['usage_count'] ?? 0
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get coupon by code
 */
function getCouponByCode($code) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND end_date >= CURDATE()");
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Validate coupon
 */
function validateCoupon($code, $orderAmount) {
    $coupon = getCouponByCode($code);
    
    if (!$coupon) {
        return ['valid' => false, 'message' => 'کد تخفیف معتبر نیست'];
    }
    
    if ($coupon['start_date'] > date('Y-m-d')) {
        return ['valid' => false, 'message' => 'کد تخفیف هنوز فعال نشده است'];
    }
    
    if ($coupon['end_date'] < date('Y-m-d')) {
        return ['valid' => false, 'message' => 'کد تخفیف منقضی شده است'];
    }
    
    if ($coupon['usage_limit'] > 0 && $coupon['usage_count'] >= $coupon['usage_limit']) {
        return ['valid' => false, 'message' => 'کد تخفیف به حداکثر استفاده رسیده است'];
    }
    
    if ($orderAmount < $coupon['min_order_amount']) {
        return ['valid' => false, 'message' => 'مبلغ سفارش کمتر از حداقل مورد نیاز است'];
    }
    
    // Calculate discount
    $discount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount = ($orderAmount * $coupon['discount_value']) / 100;
    } else {
        $discount = $coupon['discount_value'];
    }
    
    if ($coupon['max_discount_amount'] > 0 && $discount > $coupon['max_discount_amount']) {
        $discount = $coupon['max_discount_amount'];
    }
    
    return [
        'valid' => true,
        'message' => 'کد تخفیف معتبر است',
        'discount' => $discount,
        'coupon' => $coupon
    ];
}

/**
 * Use coupon
 */
function useCoupon($couponId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE coupons SET usage_count = usage_count + 1 WHERE id = ?");
        return $stmt->execute([$couponId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get all coupons
 */
function getAllCoupons() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Delete coupon
 */
function deleteCoupon($couponId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
        return $stmt->execute([$couponId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get shipping methods
 */
function getShippingMethods() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get payment methods
 */
function getPaymentMethods() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY sort_order ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get provinces
 */
function getProvinces() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM provinces ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get cities by province
 */
function getCitiesByProvince($provinceId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM cities WHERE province_id = ? ORDER BY name");
        $stmt->execute([$provinceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Create address
 */
function createAddress($data) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO addresses (user_id, title, first_name, last_name, phone, province_id, city_id, address, postal_code, is_default, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $data['user_id'],
            $data['title'] ?? '',
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $data['phone'] ?? '',
            $data['province_id'] ?? null,
            $data['city_id'] ?? null,
            $data['address'],
            $data['postal_code'] ?? '',
            $data['is_default'] ?? 0
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get user addresses
 */
function getUserAddresses($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT a.*, p.name as province_name, c.name as city_name FROM addresses a LEFT JOIN provinces p ON a.province_id = p.id LEFT JOIN cities c ON a.city_id = c.id WHERE a.user_id = ? ORDER BY a.is_default DESC, a.created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get default address
 */
function getDefaultAddress($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT a.*, p.name as province_name, c.name as city_name FROM addresses a LEFT JOIN provinces p ON a.province_id = p.id LEFT JOIN cities c ON a.city_id = c.id WHERE a.user_id = ? AND a.is_default = 1 LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Set default address
 */
function setDefaultAddress($addressId, $userId) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        // Unset other default addresses
        $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Set this address as default
        $stmt = $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Delete address
 */
function deleteAddress($addressId, $userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        return $stmt->execute([$addressId, $userId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update address
 */
function updateAddress($addressId, $data) {
    global $pdo;
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id' && $key !== 'user_id' && isset($value)) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($fields)) return false;
    
    $values[] = $addressId;
    $values[] = $data['user_id'] ?? null;
    
    try {
        $sql = "UPDATE addresses SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get site pages
 */
function getSitePages() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM pages WHERE is_active = 1 ORDER BY sort_order ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get page by slug
 */
function getPageBySlug($slug) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create page
 */
function createPage($data) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, meta_title, meta_description, meta_keywords, is_active, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['content'] ?? '',
            $data['meta_title'] ?? '',
            $data['meta_description'] ?? '',
            $data['meta_keywords'] ?? '',
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update page
 */
function updatePage($pageId, $data) {
    global $pdo;
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id' && $key !== 'created_at' && isset($value)) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($fields)) return false;
    
    $values[] = $pageId;
    
    try {
        $sql = "UPDATE pages SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete page
 */
function deletePage($pageId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
        return $stmt->execute([$pageId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create banner
 */
function createBanner($data) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO banners (title, subtitle, description, image_path, link, button_text, position, is_active, start_date, end_date, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $data['title'] ?? '',
            $data['subtitle'] ?? '',
            $data['description'] ?? '',
            $data['image_path'] ?? '',
            $data['link'] ?? '',
            $data['button_text'] ?? '',
            $data['position'] ?? 'home',
            $data['is_active'] ?? 1,
            $data['start_date'] ?? date('Y-m-d'),
            $data['end_date'] ?? date('Y-m-d', strtotime('+30 days')),
            $data['sort_order'] ?? 0
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get banners by position
 */
function getBannersByPosition($position) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM banners WHERE position = ? AND is_active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE() ORDER BY sort_order ASC");
        $stmt->execute([$position]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Delete banner
 */
function deleteBanner($bannerId) {
    global $pdo;
    try {
        // Get image path
        $stmt = $pdo->prepare("SELECT image_path FROM banners WHERE id = ?");
        $stmt->execute([$bannerId]);
        $imagePath = $stmt->fetchColumn();
        
        // Delete image file
        if ($imagePath) {
            deleteImage($imagePath);
        }
        
        // Delete banner
        $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
        return $stmt->execute([$bannerId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get all banners
 */
function getAllBanners() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM banners ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Create blog post
 */
function createBlogPost($data) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, content, excerpt, featured_image, category_id, author_id, meta_title, meta_description, meta_keywords, is_published, published_at, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW())");
        $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['content'] ?? '',
            $data['excerpt'] ?? '',
            $data['featured_image'] ?? '',
            $data['category_id'] ?? null,
            $data['author_id'] ?? null,
            $data['meta_title'] ?? '',
            $data['meta_description'] ?? '',
            $data['meta_keywords'] ?? '',
            $data['is_published'] ?? 0,
            $data['published_at'] ?? null,
            $data['sort_order'] ?? 0
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get blog posts
 */
function getBlogPosts($limit = null, $categoryId = null, $isPublished = true) {
    global $pdo;
    
    $sql = "SELECT bp.*, u.name as author_name, bc.name as category_name FROM blog_posts bp LEFT JOIN users u ON bp.author_id = u.id LEFT JOIN blog_categories bc ON bp.category_id = bc.id";
    $params = [];
    
    $conditions = [];
    
    if ($isPublished !== null) {
        $conditions[] = "bp.is_published = ?";
        $params[] = $isPublished ? 1 : 0;
    }
    
    if ($categoryId) {
        $conditions[] = "bp.category_id = ?";
        $params[] = $categoryId;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $sql .= " ORDER BY bp.published_at DESC, bp.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get blog post by slug
 */
function getBlogPostBySlug($slug) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT bp.*, u.name as author_name, bc.name as category_name FROM blog_posts bp LEFT JOIN users u ON bp.author_id = u.id LEFT JOIN blog_categories bc ON bp.category_id = bc.id WHERE bp.slug = ? AND bp.is_published = 1 LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get blog categories
 */
function getBlogCategories() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM blog_categories WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get recent blog posts
 */
function getRecentBlogPosts($limit = 5) {
    return getBlogPosts($limit);
}

/**
 * Delete blog post
 */
function deleteBlogPost($postId) {
    global $pdo;
    try {
        // Get featured image
        $stmt = $pdo->prepare("SELECT featured_image FROM blog_posts WHERE id = ?");
        $stmt->execute([$postId]);
        $imagePath = $stmt->fetchColumn();
        
        // Delete image file
        if ($imagePath) {
            deleteImage($imagePath);
        }
        
        // Delete post
        $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
        return $stmt->execute([$postId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get blog post comments
 */
function getBlogPostComments($postId, $isApproved = true) {
    global $pdo;
    
    $sql = "SELECT c.*, u.name as user_name FROM blog_comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.post_id = ?";
    $params = [$postId];
    
    if ($isApproved !== null) {
        $sql .= " AND c.is_approved = ?";
        $params[] = $isApproved ? 1 : 0;
    }
    
    $sql .= " ORDER BY c.created_at DESC";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Create blog comment
 */
function createBlogComment($data) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO blog_comments (post_id, user_id, parent_id, author_name, author_email, content, is_approved, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $data['post_id'],
            $data['user_id'] ?? null,
            $data['parent_id'] ?? null,
            $data['author_name'] ?? '',
            $data['author_email'] ?? '',
            $data['content'],
            $data['is_approved'] ?? 0
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Approve blog comment
 */
function approveBlogComment($commentId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE blog_comments SET is_approved = 1 WHERE id = ?");
        return $stmt->execute([$commentId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete blog comment
 */
function deleteBlogComment($commentId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM blog_comments WHERE id = ?");
        return $stmt->execute([$commentId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create blog category
 */
function createBlogCategory($data) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO blog_categories (name, slug, description, parent_id, image_path, is_active, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $data['name'],
            $data['slug'] ?? '',
            $data['description'] ?? '',
            $data['parent_id'] ?? null,
            $data['image_path'] ?? '',
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get FAQ categories
 */
function getFAQCategories() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM faq_categories WHERE is_active = 1 ORDER BY sort_order ASC, name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get FAQs by category
 */
function getFAQsByCategory($categoryId = null) {
    global $pdo;
    
    $sql = "SELECT f.*, fc.name as category_name FROM faqs f LEFT JOIN faq_categories fc ON f.category_id = fc.id WHERE f.is_active = 1";
    $params = [];
    
    if ($categoryId) {
        $sql .= " AND f.category_id = ?";
        $params[] = $categoryId;
    }
    
    $sql .= " ORDER BY f.sort_order ASC, f.question";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Create FAQ
 */
function createFAQ($data) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $data['category_id'] ?? null,
            $data['question'],
            $data['answer'],
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update FAQ
 */
function updateFAQ($faqId, $data) {
    global $pdo;
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id' && $key !== 'created_at' && isset($value)) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($fields)) return false;
    
    $values[] = $faqId;
    
    try {
        $sql = "UPDATE faqs SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete FAQ
 */
function deleteFAQ($faqId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM faqs WHERE id = ?");
        return $stmt->execute([$faqId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get social media links
 */
function getSocialMediaLinks() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM social_media WHERE is_active = 1 ORDER BY sort_order ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Backup database
 */
function backupDatabase() {
    global $pdo;
    
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $output = '';
    
    foreach ($tables as $table) {
        // Table structure
        $result = $pdo->query("SHOW CREATE TABLE $table");
        $row = $result->fetch(PDO::FETCH_NUM);
        $output .= "\n\n--\n-- Table structure for table `$table`\n--\n\n";
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $row[1] . ";\n\n";
        
        // Table data
        $result = $pdo->query("SELECT * FROM $table");
        $numFields = $result->columnCount();
        
        if ($numFields > 0) {
            $output .= "--\n-- Dumping data for table `$table`\n--\n\n";
            
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } elseif (is_numeric($value)) {
                        $values[] = $value;
                    } else {
                        $values[] = $pdo->quote($value);
                    }
                }
                $output .= "INSERT INTO `$table` VALUES(" . implode(',', $values) . ");\n";
            }
        }
    }
    
    $backupFile = ROOT_PATH . DS . 'backup' . DS . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Create backup directory if not exists
    if (!file_exists(dirname($backupFile))) {
        mkdir(dirname($backupFile), 0755, true);
    }
    
    return file_put_contents($backupFile, $output);
}

/**
 * Restore database
 */
function restoreDatabase($backupFile) {
    global $pdo;
    
    if (!file_exists($backupFile)) {
        return false;
    }
    
    $sql = file_get_contents($backupFile);
    $queries = explode(';', $sql);
    
    try {
        $pdo->beginTransaction();
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $pdo->exec($query);
            }
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Get database statistics
 */
function getDatabaseStats() {
    global $pdo;
    
    $stats = [
        'tables' => 0,
        'size' => 0,
        'rows' => 0
    ];
    
    try {
        // Get table count
        $result = $pdo->query("SHOW TABLES");
        $stats['tables'] = $result->rowCount();
        
        // Get database size
        $result = $pdo->query("SELECT SUM(data_length + index_length) as size FROM information_schema.tables WHERE table_schema = DATABASE()");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $stats['size'] = (int)($row['size'] ?? 0);
        
        // Get total rows
        $result = $pdo->query("SELECT SUM(table_rows) as rows FROM information_schema.tables WHERE table_schema = DATABASE()");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $stats['rows'] = (int)($row['rows'] ?? 0);
        
    } catch (PDOException $e) {
        // Return default stats on error
    }
    
    return $stats;
}

/**
 * Optimize database
 */
function optimizeDatabase() {
    global $pdo;
    
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $optimized = 0;
    
    foreach ($tables as $table) {
        try {
            $pdo->exec("OPTIMIZE TABLE `$table`");
            $optimized++;
        } catch (PDOException $e) {
            // Continue with next table
        }
    }
    
    return $optimized;
}

/**
 * Repair database
 */
function repairDatabase() {
    global $pdo;
    
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $repaired = 0;
    
    foreach ($tables as $table) {
        try {
            $pdo->exec("REPAIR TABLE `$table`");
            $repaired++;
        } catch (PDOException $e) {
            // Continue with next table
        }
    }
    
    return $repaired;
}

/**
 * Get system information
 */
function getSystemInfo() {
    return [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? '',
        'mysql_version' => 'MySQL', // Would need to query this
        'server_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? '',
        'server_name' => $_SERVER['SERVER_NAME'] ?? '',
        'server_addr' => $_SERVER['SERVER_ADDR'] ?? '',
        'server_port' => $_SERVER['SERVER_PORT'] ?? '',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size')
    ];
}

/**
 * Check system requirements
 */
function checkSystemRequirements() {
    $requirements = [
        'php_version' => [
            'name' => 'PHP Version',
            'required' => '7.4',
            'current' => phpversion(),
            'status' => version_compare(phpversion(), '7.4') >= 0
        ],
        'pdo' => [
            'name' => 'PDO Extension',
            'required' => true,
            'current' => extension_loaded('pdo'),
            'status' => extension_loaded('pdo')
        ],
        'pdo_mysql' => [
            'name' => 'PDO MySQL',
            'required' => true,
            'current' => extension_loaded('pdo_mysql'),
            'status' => extension_loaded('pdo_mysql')
        ],
        'file_uploads' => [
            'name' => 'File Uploads',
            'required' => true,
            'current' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
            'status' => ini_get('file_uploads')
        ],
        'gd' => [
            'name' => 'GD Library',
            'required' => false,
            'current' => extension_loaded('gd') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('gd')
        ],
        'curl' => [
            'name' => 'cURL',
            'required' => false,
            'current' => extension_loaded('curl') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('curl')
        ]
    ];
    
    $allPassed = true;
    foreach ($requirements as $key => $requirement) {
        if ($requirement['required'] && !$requirement['status']) {
            $allPassed = false;
        }
    }
    
    return [
        'requirements' => $requirements,
        'all_passed' => $allPassed
    ];
}

/**
 * Get file size in human readable format
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

/**
 * Get directory size
 */
function getDirectorySize($path) {
    $size = 0;
    
    if (!file_exists($path)) {
        return 0;
    }
    
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));
    
    foreach ($files as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }
    
    return $size;
}

/**
 * Clean directory
 */
function cleanDirectory($path, $olderThan = 86400) {
    if (!file_exists($path)) {
        return 0;
    }
    
    $count = 0;
    $now = time();
    
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));
    
    foreach ($files as $file) {
        if ($file->isFile() && $now - $file->getMTime() > $olderThan) {
            unlink($file->getRealPath());
            $count++;
        }
    }
    
    return $count;
}

/**
 * Generate sitemap
 */
function generateSitemap() {
    global $pdo;
    
    $urls = [];
    
    // Add static pages
    $pages = getSitePages();
    foreach ($pages as $page) {
        $urls[] = [
            'loc' => $page['slug'],
            'changefreq' => 'weekly',
            'priority' => '0.8'
        ];
    }
    
    // Add products
    try {
        $stmt = $pdo->query("SELECT slug FROM products WHERE is_active = 1");
        $products = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($products as $slug) {
            $urls[] = [
                'loc' => "product/$slug",
                'changefreq' => 'daily',
                'priority' => '0.9'
            ];
        }
    } catch (PDOException $e) {
        // Continue without products
    }
    
    // Add categories
    try {
        $stmt = $pdo->query("SELECT slug FROM categories WHERE is_active = 1");
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($categories as $slug) {
            $urls[] = [
                'loc' => "category/$slug",
                'changefreq' => 'weekly',
                'priority' => '0.7'
            ];
        }
    } catch (PDOException $e) {
        // Continue without categories
    }
    
    // Add blog posts
    try {
        $stmt = $pdo->query("SELECT slug FROM blog_posts WHERE is_published = 1");
        $posts = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($posts as $slug) {
            $urls[] = [
                'loc' => "blog/$slug",
                'changefreq' => 'weekly',
                'priority' => '0.6'
            ];
        }
    } catch (PDOException $e) {
        // Continue without blog posts
    }
    
    // Generate XML
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    
    foreach ($urls as $url) {
        $xml .= '<url>';
        $xml .= '<loc>' . htmlspecialchars($url['loc']) . '</loc>';
        $xml .= '<changefreq>' . ($url['changefreq'] ?? 'weekly') . '</changefreq>';
        $xml .= '<priority>' . ($url['priority'] ?? '0.5') . '</priority>';
        $xml .= '</url>';
    }
    
    $xml .= '</urlset>';
    
    $sitemapPath = ROOT_PATH . DS . 'sitemap.xml';
    
    return file_put_contents($sitemapPath, $xml);
}

/**
 * Generate robots.txt
 */
function generateRobotsTxt() {
    $content = "User-agent: *\n";
    $content .= "Disallow: /admin/\n";
    $content .= "Disallow: /includes/\n";
    $content .= "Disallow: /assets/\n";
    $content .= "Allow: /\n";
    $content .= "Sitemap: " . getSetting('site_url', 'http://localhost/goolland') . "/sitemap.xml\n";
    
    $robotsPath = ROOT_PATH . DS . 'robots.txt';
    
    return file_put_contents($robotsPath, $content);
}

/**
 * Clear cache
 */
function clearCache() {
    $cachePath = ROOT_PATH . DS . 'cache';
    
    if (!file_exists($cachePath)) {
        return 0;
    }
    
    $count = 0;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cachePath, RecursiveDirectoryIterator::SKIP_DOTS));
    
    foreach ($files as $file) {
        if ($file->isFile()) {
            unlink($file->getRealPath());
            $count++;
        }
    }
    
    return $count;
}

/**
 * Set cache
 */
function setCache($key, $value, $ttl = 3600) {
    $cachePath = ROOT_PATH . DS . 'cache';
    
    if (!file_exists($cachePath)) {
        mkdir($cachePath, 0755, true);
    }
    
    $cacheFile = $cachePath . DS . md5($key) . '.cache';
    $data = [
        'value' => $value,
        'expires' => time() + $ttl
    ];
    
    return file_put_contents($cacheFile, serialize($data));
}

/**
 * Get cache
 */
function getCache($key) {
    $cachePath = ROOT_PATH . DS . 'cache';
    $cacheFile = $cachePath . DS . md5($key) . '.cache';
    
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    $data = unserialize(file_get_contents($cacheFile));
    
    if ($data && $data['expires'] > time()) {
        return $data['value'];
    }
    
    // Cache expired, delete it
    unlink($cacheFile);
    return null;
}

/**
 * Delete cache
 */
function deleteCache($key) {
    $cachePath = ROOT_PATH . DS . 'cache';
    $cacheFile = $cachePath . DS . md5($key) . '.cache';
    
    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }
    
    return true;
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
 * Generate password reset token
 */
function generatePasswordResetToken($userId) {
    global $pdo;
    
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    try {
        $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
        return $stmt->execute([$userId, $token, $expires, $token, $expires]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Validate password reset token
 */
function validatePasswordResetToken($token) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete password reset token
 */
function deletePasswordResetToken($token) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
        return $stmt->execute([$token]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Reset password
 */
function resetPassword($userId, $newPassword) {
    global $pdo;
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $userId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Login user
 */
function loginUser($email, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['phone'] = $user['phone'] ?? '';
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Log activity
            logActivity($user['id'], 'login', 'User logged in');
            
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Logout user
 */
function logoutUser() {
    // Log activity
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    }
    
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    return true;
}

/**
 * Register user
 */
function registerUser($data) {
    global $pdo;
    
    // Check if email already exists
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'ایمیل وارد شده قبلا ثبت شده است'];
        }
        
        // Create user
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, address, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? '',
            $hashedPassword,
            $data['address'] ?? ''
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Auto login
        $_SESSION['user_id'] = $userId;
        $_SESSION['name'] = $data['name'];
        $_SESSION['email'] = $data['email'];
        $_SESSION['is_admin'] = 0;
        $_SESSION['phone'] = $data['phone'] ?? '';
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        
        // Log activity
        logActivity($userId, 'register', 'New user registered');
        
        return ['success' => true, 'message' => 'ثبت‌نام با موفقیت انجام شد', 'user_id' => $userId];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در ثبت‌نام: ' . $e->getMessage()];
    }
}

/**
 * Update user password
 */
function updateUserPassword($userId, $currentPassword, $newPassword) {
    global $pdo;
    
    try {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'رمز عبور فعلی اشتباه است'];
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        
        // Log activity
        logActivity($userId, 'change_password', 'User changed password');
        
        return ['success' => true, 'message' => 'رمز عبور با موفقیت تغییر یافت'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در تغییر رمز عبور: ' . $e->getMessage()];
    }
}

/**
 * Update user profile
 */
function updateUserProfileData($userId, $data) {
    global $pdo;
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'user_id' && $key !== 'password' && $key !== 'current_password' && $key !== 'confirm_password') {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($fields)) {
        return ['success' => false, 'message' => 'هیچ داده‌ای برای به‌روزرسانی وجود ندارد'];
    }
    
    $values[] = $userId;
    
    try {
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        // Log activity
        logActivity($userId, 'update_profile', 'User updated profile');
        
        return ['success' => true, 'message' => 'پروفایل با موفقیت به‌روزرسانی شد'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در به‌روزرسانی پروفایل: ' . $e->getMessage()];
    }
}

/**
 * Forgot password
 */
function forgotPassword($email) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'ایمیل وارد شده یافت نشد'];
        }
        
        // Generate reset token
        generatePasswordResetToken($user['id']);
        
        // Get the token
        $stmt = $pdo->prepare("SELECT token FROM password_reset_tokens WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user['id']]);
        $token = $stmt->fetchColumn();
        
        // Send reset email
        $resetLink = getSetting('site_url', 'http://localhost/goolland') . "/reset-password.php?token=$token";
        $subject = 'بازیابی رمز عبور - گولند';
        $message = "<p>سلام {$user['name']}</p>";
        $message .= "<p>برای بازیابی رمز عبور خود روی لینک زیر کلیک کنید:</p>";
        $message .= "<p><a href=\"$resetLink\">بازیابی رمز عبور</a></p>";
        $message .= "<p>اگر شما این درخواست را نداده‌اید، لطفا آن را نادیده بگیرید.</p>";
        
        if (sendEmail($user['email'], $subject, $message)) {
            return ['success' => true, 'message' => 'لینک بازیابی رمز عبور به ایمیل شما ارسال شد'];
        }
        
        return ['success' => false, 'message' => 'خطا در ارسال ایمیل'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در فرآیند بازیابی رمز عبور: ' . $e->getMessage()];
    }
}

/**
 * Add product to cart
 */
function addProductToCart($userId, $productId, $quantity = 1) {
    global $pdo;
    
    // Check if product exists and is in stock
    try {
        $stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return ['success' => false, 'message' => 'محصول یافت نشد'];
        }
        
        if ($product['stock'] < $quantity) {
            return ['success' => false, 'message' => 'موجودی کافی نیست'];
        }
        
        // Check if already in cart
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $newQuantity = $existing['quantity'] + $quantity;
            if ($newQuantity > $product['stock']) {
                $newQuantity = $product['stock'];
            }
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->execute([$newQuantity, $existing['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$userId, $productId, $quantity]);
        }
        
        return ['success' => true, 'message' => 'محصول به سبد خرید اضافه شد'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در افزودن به سبد خرید: ' . $e->getMessage()];
    }
}

/**
 * Update cart item quantity
 */
function updateCartItemQuantity($cartId, $quantity, $userId) {
    global $pdo;
    
    if ($quantity <= 0) {
        return deleteCartItem($cartId, $userId);
    }
    
    try {
        // Check product stock
        $stmt = $pdo->prepare("SELECT c.product_id, p.stock FROM cart c LEFT JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
        $stmt->execute([$cartId, $userId]);
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cartItem) {
            return ['success' => false, 'message' => 'آیتم یافت نشد'];
        }
        
        if ($quantity > $cartItem['stock']) {
            $quantity = $cartItem['stock'];
        }
        
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $cartId, $userId]);
        
        return ['success' => true, 'message' => 'مقدار با موفقیت به‌روزرسانی شد', 'quantity' => $quantity];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در به‌روزرسانی سبد خرید: ' . $e->getMessage()];
    }
}

/**
 * Get cart items with product details
 */
function getCartItemsWithDetails($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT c.*, p.name as product_name, p.price as product_price, p.image_path as product_image, p.sku as product_sku, p.stock as product_stock FROM cart c LEFT JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Calculate cart totals
 */
function calculateCartTotals($userId) {
    global $pdo;
    
    $cartItems = getCartItemsWithDetails($userId);
    
    $subtotal = 0;
    $totalQuantity = 0;
    
    foreach ($cartItems as $item) {
        $subtotal += $item['product_price'] * $item['quantity'];
        $totalQuantity += $item['quantity'];
    }
    
    $shippingCost = getShippingCost();
    $freeShippingThreshold = getFreeShippingThreshold();
    
    if ($subtotal >= $freeShippingThreshold) {
        $shippingCost = 0;
    }
    
    $total = $subtotal + $shippingCost;
    
    return [
        'subtotal' => $subtotal,
        'shipping_cost' => $shippingCost,
        'total' => $total,
        'total_quantity' => $totalQuantity,
        'item_count' => count($cartItems)
    ];
}

/**
 * Apply coupon to cart
 */
function applyCouponToCart($userId, $couponCode) {
    $result = validateCoupon($couponCode, calculateCartTotals($userId)['subtotal']);
    
    if (!$result['valid']) {
        return $result;
    }
    
    // Store coupon in session
    $_SESSION['coupon_code'] = $couponCode;
    $_SESSION['coupon_discount'] = $result['discount'];
    $_SESSION['coupon_id'] = $result['coupon']['id'];
    
    return ['success' => true, 'message' => $result['message'], 'discount' => $result['discount']];
}

/**
 * Remove coupon from cart
 */
function removeCouponFromCart() {
    unset($_SESSION['coupon_code']);
    unset($_SESSION['coupon_discount']);
    unset($_SESSION['coupon_id']);
    return ['success' => true, 'message' => 'کد تخفیف حذف شد'];
}

/**
 * Checkout
 */
function processCheckout($userId, $data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get cart items
        $cartItems = getCartItemsWithDetails($userId);
        
        if (empty($cartItems)) {
            return ['success' => false, 'message' => 'سبد خرید خالی است'];
        }
        
        // Calculate totals
        $totals = calculateCartTotals($userId);
        
        // Apply coupon discount
        $discount = 0;
        if (isset($_SESSION['coupon_discount'])) {
            $discount = $_SESSION['coupon_discount'];
        }
        
        $finalAmount = $totals['total'] - $discount;
        
        // Create order
        $orderData = [
            'user_id' => $userId,
            'total_amount' => $totals['total'],
            'shipping_cost' => $totals['shipping_cost'],
            'discount_amount' => $discount,
            'final_amount' => $finalAmount,
            'payment_method' => $data['payment_method'],
            'payment_status' => $data['payment_method'] === 'cash' ? 'pending' : 'unpaid',
            'status' => 'pending',
            'shipping_address' => $data['shipping_address'] ?? '',
            'billing_address' => $data['billing_address'] ?? '',
            'notes' => $data['notes'] ?? ''
        ];
        
        $orderId = createOrder($orderData);
        
        if (!$orderId) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'خطا در ایجاد سفارش'];
        }
        
        // Use coupon if applied
        if (isset($_SESSION['coupon_id'])) {
            useCoupon($_SESSION['coupon_id']);
            removeCouponFromCart();
        }
        
        // Log activity
        logActivity($userId, 'create_order', 'Order created with ID: ' . $orderId);
        
        $pdo->commit();
        
        return ['success' => true, 'message' => 'سفارش با موفقیت ثبت شد', 'order_id' => $orderId];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'خطا در ثبت سفارش: ' . $e->getMessage()];
    }
}

/**
 * Add review
 */
function addProductReview($userId, $productId, $rating, $title, $comment) {
    global $pdo;
    
    try {
        // Check if user has purchased this product
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items oi LEFT JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ? AND oi.product_id = ? AND o.payment_status = 'paid'");
        $stmt->execute([$userId, $productId]);
        $hasPurchased = $stmt->fetchColumn();
        
        if (!$hasPurchased) {
            return ['success' => false, 'message' => 'شما باید این محصول را خریداری کرده باشید تا بتوانید نظر دهید'];
        }
        
        // Check if user has already reviewed this product
        $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        if ($stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'شما قبلا برای این محصول نظر داده‌اید'];
        }
        
        // Create review
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, product_id, rating, title, comment, is_approved, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
        $stmt->execute([$userId, $productId, $rating, $title, $comment]);
        
        // Log activity
        logActivity($userId, 'add_review', 'Review added for product ID: ' . $productId);
        
        return ['success' => true, 'message' => 'نظر شما با موفقیت ثبت شد و پس از تایید نمایش داده خواهد شد'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در ثبت نظر: ' . $e->getMessage()];
    }
}

/**
 * Add to wishlist
 */
function addProductToWishlist($userId, $productId) {
    global $pdo;
    
    try {
        // Check if already in wishlist
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        if ($stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'این محصول قبلا به لیست علاقه‌مندی‌ها اضافه شده است'];
        }
        
        // Add to wishlist
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $productId]);
        
        // Log activity
        logActivity($userId, 'add_to_wishlist', 'Product added to wishlist: ' . $productId);
        
        return ['success' => true, 'message' => 'محصول به لیست علاقه‌مندی‌ها اضافه شد'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در افزودن به لیست علاقه‌مندی‌ها: ' . $e->getMessage()];
    }
}

/**
 * Remove from wishlist
 */
function removeProductFromWishlist($userId, $productId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        
        // Log activity
        logActivity($userId, 'remove_from_wishlist', 'Product removed from wishlist: ' . $productId);
        
        return ['success' => true, 'message' => 'محصول از لیست علاقه‌مندی‌ها حذف شد'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در حذف از لیست علاقه‌مندی‌ها: ' . $e->getMessage()];
    }
}

/**
 * Get wishlist items with product details
 */
function getWishlistItemsWithDetails($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT w.*, p.name as product_name, p.price as product_price, p.image_path as product_image, p.sku as product_sku, p.stock as product_stock FROM wishlist w LEFT JOIN products p ON w.product_id = p.id WHERE w.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get user orders with details
 */
function getUserOrdersWithDetails($userId, $limit = null) {
    global $pdo;
    
    $sql = "SELECT o.*, u.name as user_name, u.email as user_email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.user_id = ? ORDER BY o.created_at DESC";
    $params = [$userId];
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add order items to each order
        foreach ($orders as &$order) {
            $order['items'] = getOrderItemsByOrderId($order['id']);
        }
        
        return $orders;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get order details with items
 */
function getOrderDetails($orderId, $userId = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?" . ($userId ? " AND o.user_id = ?" : ""));
        $params = [$orderId];
        if ($userId) {
            $params[] = $userId;
        }
        $stmt->execute($params);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return false;
        }
        
        $order['items'] = getOrderItemsByOrderId($orderId);
        
        return $order;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Cancel order
 */
function cancelOrder($orderId, $userId) {
    global $pdo;
    
    try {
        // Get order
        $order = getOrderDetails($orderId, $userId);
        if (!$order) {
            return ['success' => false, 'message' => 'سفارش یافت نشد'];
        }
        
        // Check if order can be cancelled
        if ($order['status'] !== 'pending' && $order['status'] !== 'processing') {
            return ['success' => false, 'message' => 'امکان کنسل کردن این سفارش وجود ندارد'];
        }
        
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        
        // Restore product stock
        $items = getOrderItemsByOrderId($orderId);
        foreach ($items as $item) {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Log activity
        logActivity($userId, 'cancel_order', 'Order cancelled: ' . $orderId);
        
        return ['success' => true, 'message' => 'سفارش با موفقیت کنسل شد'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در کنسل کردن سفارش: ' . $e->getMessage()];
    }
}

/**
 * Track order
 */
function trackOrder($orderId, $email = null) {
    global $pdo;
    
    try {
        if ($email) {
            $stmt = $pdo->prepare("SELECT o.*, u.name as user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ? AND u.email = ?");
            $stmt->execute([$orderId, $email]);
        } else {
            $stmt = $pdo->prepare("SELECT o.*, u.name as user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
            $stmt->execute([$orderId]);
        }
        
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return ['success' => false, 'message' => 'سفارش یافت نشد'];
        }
        
        $order['items'] = getOrderItemsByOrderId($orderId);
        
        return ['success' => true, 'order' => $order];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در پیگیری سفارش: ' . $e->getMessage()];
    }
}

/**
 * Contact us
 */
function contactUs($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? '',
            $data['subject'],
            $data['message']
        ]);
        
        // Send confirmation email
        $subject = 'تایید دریافت پیام - گولند';
        $message = "<p>سلام {$data['name']}</p>";
        $message .= "<p>پیام شما با موفقیت دریافت شد. به زودی پاسخ شما را خواهیم داد.</p>";
        $message .= "<p>موضوع: {$data['subject']}</p>";
        $message .= "<p>پیام: {$data['message']}</p>";
        
        sendEmail($data['email'], $subject, $message);
        
        // Send notification to admin
        $adminEmail = getSetting('site_email', 'info@goolland.ir');
        $adminSubject = 'پیام جدید از فرم تماس با ما';
        $adminMessage = "<p>پیام جدیدی از {$data['name']} دریافت شد:</p>";
        $adminMessage .= "<p><strong>نام:</strong> {$data['name']}</p>";
        $adminMessage .= "<p><strong>ایمیل:</strong> {$data['email']}</p>";
        if (!empty($data['phone'])) {
            $adminMessage .= "<p><strong>تلفن:</strong> {$data['phone']}</p>";
        }
        $adminMessage .= "<p><strong>موضوع:</strong> {$data['subject']}</p>";
        $adminMessage .= "<p><strong>پیام:</strong> {$data['message']}</p>";
        
        sendEmail($adminEmail, $adminSubject, $adminMessage);
        
        return ['success' => true, 'message' => 'پیام شما با موفقیت ارسال شد'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در ارسال پیام: ' . $e->getMessage()];
    }
}

/**
 * Subscribe to newsletter
 */
function subscribeToNewsletterEmail($email) {
    global $pdo;
    
    try {
        // Check if already subscribed
        $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'ایمیل وارد شده قبلا ثبت شده است'];
        }
        
        $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, is_active, created_at) VALUES (?, 1, NOW())");
        $stmt->execute([$email]);
        
        // Send welcome email
        $subject = 'خوش آمدید به خبرنامه گولند';
        $message = "<p>با تشکر از ثبت‌نام در خبرنامه گولند</p>";
        $message .= "<p>شما از این پس آخرین اخبار و تخفیف‌های ما را دریافت خواهید کرد.</p>";
        
        sendEmail($email, $subject, $message);
        
        return ['success' => true, 'message' => 'ثبت‌نام در خبرنامه با موفقیت انجام شد'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در ثبت‌نام در خبرنامه: ' . $e->getMessage()];
    }
}

/**
 * Get all provinces
 */
function getAllProvinces() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM provinces ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get all cities
 */
function getAllCities($provinceId = null) {
    global $pdo;
    
    $sql = "SELECT c.*, p.name as province_name FROM cities c LEFT JOIN provinces p ON c.province_id = p.id";
    $params = [];
    
    if ($provinceId) {
        $sql .= " WHERE c.province_id = ?";
        $params[] = $provinceId;
    }
    
    $sql .= " ORDER BY c.name";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get city by ID
 */
function getCityById($cityId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT c.*, p.name as province_name FROM cities c LEFT JOIN provinces p ON c.province_id = p.id WHERE c.id = ?");
        $stmt->execute([$cityId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get province by ID
 */
function getProvinceById($provinceId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM provinces WHERE id = ?");
        $stmt->execute([$provinceId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create address for user
 */
function createUserAddress($userId, $data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO addresses (user_id, title, first_name, last_name, phone, province_id, city_id, address, postal_code, is_default, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userId,
            $data['title'] ?? '',
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $data['phone'] ?? '',
            $data['province_id'] ?? null,
            $data['city_id'] ?? null,
            $data['address'],
            $data['postal_code'] ?? '',
            $data['is_default'] ?? 0
        ]);
        
        $addressId = $pdo->lastInsertId();
        
        // If this is the first address, set as default
        if ($data['is_default'] ?? false) {
            setDefaultAddress($addressId, $userId);
        }
        
        return ['success' => true, 'message' => 'آدرس با موفقیت ذخیره شد', 'address_id' => $addressId];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در ذخیره آدرس: ' . $e->getMessage()];
    }
}

/**
 * Get user addresses with details
 */
function getUserAddressesWithDetails($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT a.*, p.name as province_name, c.name as city_name FROM addresses a LEFT JOIN provinces p ON a.province_id = p.id LEFT JOIN cities c ON a.city_id = c.id WHERE a.user_id = ? ORDER BY a.is_default DESC, a.created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get default user address
 */
function getDefaultUserAddress($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT a.*, p.name as province_name, c.name as city_name FROM addresses a LEFT JOIN provinces p ON a.province_id = p.id LEFT JOIN cities c ON a.city_id = c.id WHERE a.user_id = ? AND a.is_default = 1 LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update user address
 */
function updateUserAddress($addressId, $userId, $data) {
    global $pdo;
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'address_id' && $key !== 'user_id' && isset($value)) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($fields)) {
        return ['success' => false, 'message' => 'هیچ داده‌ای برای به‌روزرسانی وجود ندارد'];
    }
    
    $values[] = $addressId;
    $values[] = $userId;
    
    try {
        $sql = "UPDATE addresses SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        return ['success' => true, 'message' => 'آدرس با موفقیت به‌روزرسانی شد'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در به‌روزرسانی آدرس: ' . $e->getMessage()];
    }
}

/**
 * Delete user address
 */
function deleteUserAddress($addressId, $userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);
        
        return ['success' => true, 'message' => 'آدرس با موفقیت حذف شد'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطا در حذف آدرس: ' . $e->getMessage()];
    }
}

/**
 * Set default user address
 */
function setDefaultUserAddress($addressId, $userId) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        // Unset other default addresses
        $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Set this address as default
        $stmt = $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);
        
        $pdo->commit();
        return ['success' => true, 'message' => 'آدرس پیش‌فرض با موفقیت تنظیم شد'];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'خطا در تنظیم آدرس پیش‌فرض: ' . $e->getMessage()];
    }
}

/**
 * Get all shipping methods
 */
function getAllShippingMethods() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get shipping method by ID
 */
function getShippingMethodById($methodId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM shipping_methods WHERE id = ?");
        $stmt->execute([$methodId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get all payment methods
 */
function getAllPaymentMethods() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY sort_order ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get payment method by ID
 */
function getPaymentMethodById($methodId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE id = ?");
        $stmt->execute([$methodId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Admin Panel Functions
 */

/**
 * Get admin role name in Persian
 */
function getAdminRoleName($role) {
    $roles = [
        'admin' => 'مدیر کل',
        'manager' => 'مدیر',
        'editor' => 'ویرایشگر',
        'viewer' => 'مشاهده‌کننده'
    ];
    return $roles[$role] ?? $role;
}

/**
 * Get pending orders count
 */
function getPendingOrdersCount() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get admin by ID
 */
function getAdminById($adminId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get admin by username
 */
function getAdminByUsername($username) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get all admins
 */
function getAllAdmins() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}


/**
 * Create admin
 */
function createAdmin($data) {
    global $pdo;
    
    try {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$data["username"]]);
        if ($stmt->fetchColumn()) {
            return ["success" => false, "message" => "نام کاربری وارد شده قبلا ثبت شده است"];
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->execute([$data["email"]]);
        if ($stmt->fetchColumn()) {
            return ["success" => false, "message" => "ایمیل وارد شده قبلا ثبت شده است"];
        }
        
        // Create admin
        $hashedPassword = password_hash($data["password"], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, phone, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $data["username"],
            $data["email"],
            $hashedPassword,
            $data["full_name"] ?? "",
            $data["phone"] ?? "",
            $data["role"] ?? "admin",
            $data["is_active"] ?? 1
        ]);
        
        $adminId = $pdo->lastInsertId();
        
        // Log activity
        if (isset($_SESSION["admin_id"])) {
            logAdminActivity("create_admin", "Admin created: " . $data["username"]);
        }
        
        return ["success" => true, "message" => "مدیر جدید با موفقیت ایجاد شد", "admin_id" => $adminId];
        
    } catch (PDOException $e) {
        return ["success" => false, "message" => "خطا در ایجاد مدیر: " . $e->getMessage()];
    }
}

/**
 * Update admin
 */
function updateAdmin($adminId, $data) {
    global $pdo;
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== "admin_id" && $key !== "password" && $key !== "confirm_password" && isset($value)) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    // Handle password separately
    if (!empty($data["password"])) {
        $hashedPassword = password_hash($data["password"], PASSWORD_DEFAULT);
        $fields[] = "password = ?";
        $values[] = $hashedPassword;
    }
    
    if (empty($fields)) {
        return ["success" => false, "message" => "هیچ داده‌ای برای به‌روزرسانی وجود ندارد"];
    }
    
    $values[] = $adminId;
    
    try {
        $sql = "UPDATE admins SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        // Log activity
        if (isset($_SESSION["admin_id"])) {
            logAdminActivity("update_admin", "Admin updated: " . $adminId);
        }
        
        return ["success" => true, "message" => "مدیر با موفقیت به‌روزرسانی شد"];
        
    } catch (PDOException $e) {
        return ["success" => false, "message" => "خطا در به‌روزرسانی مدیر: " . $e->getMessage()];
    }
}

/**
 * Delete admin
 */
function deleteAdmin($adminId) {
    global $pdo;
    
    // Prevent deleting current admin
    if (isset($_SESSION["admin_id"]) && $_SESSION["admin_id"] == $adminId) {
        return ["success" => false, "message" => "شما نمی‌توانید حساب خود را حذف کنید"];
    }
    
    try {
        // Delete admin sessions
        $stmt = $pdo->prepare("DELETE FROM admin_sessions WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        
        // Delete admin
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        
        // Log activity
        if (isset($_SESSION["admin_id"])) {
            logAdminActivity("delete_admin", "Admin deleted: " . $adminId);
        }
        
        return ["success" => true, "message" => "مدیر با موفقیت حذف شد"];
        
    } catch (PDOException $e) {
        return ["success" => false, "message" => "خطا در حذف مدیر: " . $e->getMessage()];
    }
}

/**
 * Log admin activity
 */
function logAdminActivity($action, $details = "") {
    global $pdo;
    
    $adminId = $_SESSION["admin_id"] ?? 0;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_activities (admin_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $adminId,
            $action,
            $details,
            $_SERVER["REMOTE_ADDR"] ?? "",
            $_SERVER["HTTP_USER_AGENT"] ?? ""
        ]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get admin activities for dashboard
 */
function getAdminDashboardActivities($limit = 10) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT aa.*, a.username as admin_username FROM admin_activities aa LEFT JOIN admins a ON aa.admin_id = a.id ORDER BY aa.created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

