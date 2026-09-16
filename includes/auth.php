<?php
/**
 * Goolland Authentication API
 * Handles user login, registration, password reset, etc.
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

try {
    switch ($action) {
        case 'login':
            handleLogin();
            break;
        case 'register':
            handleRegister();
            break;
        case 'logout':
            handleLogout();
            break;
        case 'forgot_password':
            handleForgotPassword();
            break;
        case 'reset_password':
            handleResetPassword();
            break;
        case 'check_session':
            checkSession();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Action not specified']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Handle user login
 */
function handleLogin() {
    global $pdo;
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'لطفا ایمیل و رمز عبور را وارد کنید']);
        return;
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'کاربری با این ایمیل یافت نشد یا حساب شما غیرفعال است']);
        return;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'ایمیل یا رمز عبور اشتباه است']);
        return;
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['last_login'] = time();
    
    // Update last login in database
    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
    
    // Set remember me cookie if requested
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expire = time() + (30 * 24 * 60 * 60); // 30 days
        
        setcookie('remember_token', $token, $expire, '/', '', true, true);
        
        // Store token in database
        $pdo->prepare("UPDATE users SET remember_token = ?, token_expire = FROM_UNIXTIME(?) WHERE id = ?")
            ->execute([$token, $expire, $user['id']]);
    }
    
    // Check if there's a redirect URL
    $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
    unset($_SESSION['redirect_after_login']);
    
    echo json_encode(['success' => true, 'message' => 'ورود موفق', 'redirect' => $redirect]);
}

/**
 * Handle user registration
 */
function handleRegister() {
    global $pdo;
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($first_name) || empty($last_name)) {
        echo json_encode(['success' => false, 'message' => 'لطفا نام و نام خانوادگی را وارد کنید']);
        return;
    }
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'لطفا آدرس ایمیل را وارد کنید']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'لطفا آدرس ایمیل معتبر وارد کنید']);
        return;
    }
    
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'لطفا رمز عبور را وارد کنید']);
        return;
    }
    
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'رمز عبور و تکرار آن یکسان نیست']);
        return;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'رمز عبور باید حداقل 6 کاراکتر باشد']);
        return;
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'این آدرس ایمیل قبلاً ثبت‌نام شده است']);
        return;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password, role, status, email_verified, created_at) VALUES (?, ?, ?, ?, ?, 'customer', 'active', 0, NOW())");
    $result = $stmt->execute([$first_name, $last_name, $email, $phone, $hashed_password]);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'خطا در ثبت‌نام، لطفاً دوباره امتحان کنید']);
        return;
    }
    
    $user_id = $pdo->lastInsertId();
    
    // Auto-login after registration
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
    $_SESSION['user_role'] = 'customer';
    $_SESSION['logged_in'] = true;
    
    echo json_encode(['success' => true, 'message' => 'ثبت‌نام موفق', 'redirect' => 'index.php']);
}

/**
 * Handle user logout
 */
function handleLogout() {
    // Unset all session variables
    $_SESSION = [];
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, 
            $params["path"], $params["domain"], 
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Delete remember me cookie
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    
    echo json_encode(['success' => true, 'message' => 'خروج موفق', 'redirect' => 'index.php']);
}

/**
 * Handle forgot password
 */
function handleForgotPassword() {
    global $pdo;
    
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'لطفا آدرس ایمیل را وارد کنید']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'لطفا آدرس ایمیل معتبر وارد کنید']);
        return;
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Don't reveal that user doesn't exist for security
        echo json_encode(['success' => true, 'message' => 'اگر این ایمیل ثبت شده باشد، لینک بازیابی برای شما ارسال شد']);
        return;
    }
    
    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expire = time() + (24 * 60 * 60); // 24 hours
    
    // Store token in database
    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, token_expire = FROM_UNIXTIME(?) WHERE id = ?");
    $stmt->execute([$token, $expire, $user['id']]);
    
    // Create reset link
    $reset_link = 'https://' . $_SERVER['HTTP_HOST'] . '/reset-password.php?token=' . $token;
    
    // Here you would send the email. For now, we'll just return the token info
    // In a real application, you would use PHPMailer or similar to send the email
    
    echo json_encode([
        'success' => true,
        'message' => 'لینک بازیابی رمز عبور به آدرس ایمیل شما ارسال شد. لطفا ایمیل خود را بررسی کنید.',
        'reset_link' => $reset_link // For testing purposes only
    ]);
}

/**
 * Handle reset password
 */
function handleResetPassword() {
    global $pdo;
    
    $token = trim($_POST['token'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    
    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => 'توکن معتبر نیست']);
        return;
    }
    
    if (empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'لطفا رمز عبور جدید را وارد کنید']);
        return;
    }
    
    if (strlen($new_password) < 6) {
        echo json_encode(['success' => false, 'message' => 'رمز عبور باید حداقل 6 کاراکتر باشد']);
        return;
    }
    
    // Find user with this token
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND token_expire > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'لینک بازیابی معتبر نیست یا منقضی شده است']);
        return;
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password and clear token
    $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expire = NULL WHERE id = ?");
    $stmt->execute([$hashed_password, $user['id']]);
    
    echo json_encode(['success' => true, 'message' => 'رمز عبور شما با موفقیت تغییر یافت']);
}

/**
 * Check if user is logged in
 */
function checkSession() {
    if (isLoggedIn()) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_email' => $_SESSION['user_email'] ?? null,
            'user_name' => $_SESSION['user_name'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null
        ]);
    } else {
        echo json_encode(['success' => true, 'logged_in' => false]);
    }
}

/**
 * Helper function to check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}
