<?php
/**
 * Goolland Profile API
 * Handles user profile updates
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'لطفاً ابتدا وارد حساب کاربری شوید', 'redirect' => 'login.php']);
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

try {
    switch ($action) {
        case 'update':
            handleProfileUpdate();
            break;
        case 'update_password':
            handlePasswordUpdate();
            break;
        case 'update_address':
            handleAddressUpdate();
            break;
        case 'get_profile':
            getUserProfile();
            break;
        case 'get_orders':
            getUserOrders();
            break;
        case 'get_order':
            getUserOrder();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Action not specified']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Handle profile update
 */
function handleProfileUpdate() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validate inputs
    if (empty($first_name) || empty($last_name)) {
        echo json_encode(['success' => false, 'message' => 'لطفاً نام و نام خانوادگی را وارد کنید']);
        return;
    }
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'لطفاً آدرس ایمیل را وارد کنید']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'لطفاً آدرس ایمیل معتبر وارد کنید']);
        return;
    }
    
    // Check if email already exists for another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'این آدرس ایمیل قبلاً توسط کاربر دیگری استفاده شده است']);
        return;
    }
    
    // Update profile
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
    $result = $stmt->execute([$first_name, $last_name, $email, $phone, $user_id]);
    
    if ($result) {
        // Update session
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        
        echo json_encode(['success' => true, 'message' => 'اطلاعات شما با موفقیت به‌روزرسانی شد']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در به‌روزرسانی اطلاعات']);
    }
}

/**
 * Handle password update
 */
function handlePasswordUpdate() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($current_password)) {
        echo json_encode(['success' => false, 'message' => 'لطفاً رمز عبور فعلی را وارد کنید']);
        return;
    }
    
    if (empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'لطفاً رمز عبور جدید را وارد کنید']);
        return;
    }
    
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'رمز عبور جدید و تکرار آن یکسان نیست']);
        return;
    }
    
    if (strlen($new_password) < 6) {
        echo json_encode(['success' => false, 'message' => 'رمز عبور باید حداقل 6 کاراکتر باشد']);
        return;
    }
    
    // Get current user
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'کاربری یافت نشد']);
        return;
    }
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'رمز عبور فعلی اشتباه است']);
        return;
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $result = $stmt->execute([$hashed_password, $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'رمز عبور شما با موفقیت تغییر یافت']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در تغییر رمز عبور']);
    }
}

/**
 * Handle address update
 */
function handleAddressUpdate() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? 'ایران');
    
    // Update address
    $stmt = $pdo->prepare("UPDATE users SET address = ?, city = ?, state = ?, postal_code = ?, country = ? WHERE id = ?");
    $result = $stmt->execute([$address, $city, $state, $postal_code, $country, $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'آدرس شما با موفقیت به‌روزرسانی شد']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در به‌روزرسانی آدرس']);
    }
}

/**
 * Get user profile
 */
function getUserProfile() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Remove sensitive data
        unset($user['password']);
        unset($user['reset_token']);
        unset($user['remember_token']);
        
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'کاربری یافت نشد']);
    }
}

/**
 * Get user orders
 */
function getUserOrders() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $count = $stmt->fetch()['count'];
    
    // Get orders
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$user_id, $limit, $offset]);
    $orders = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'pagination' => [
            'total' => $count,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($count / $limit)
        ]
    ]);
}

/**
 * Get single user order
 */
function getUserOrder() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
    
    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID not specified']);
        return;
    }
    
    // Get order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'سفارشی یافت نشد']);
        return;
    }
    
    // Get order items
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'order' => $order,
        'order_items' => $order_items
    ]);
}

/**
 * Helper function to check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}
