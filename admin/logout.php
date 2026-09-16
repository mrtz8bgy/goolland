<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (isAdminLoggedIn()) {
    // Log activity
    $adminId = $_SESSION['admin_id'] ?? 0;
    $adminUsername = $_SESSION['admin_username'] ?? 'ناشناس';
    logActivity('admin_logout', 'خروج از پنل ادمین توسط ' . $adminUsername);
}

// Clear session
$_SESSION = [];

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, 
        $params["path"], $params["domain"], 
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Clear cookies
setcookie('admin_token', '', time() - 42000, '/', '', true, true);
setcookie('admin_id', '', time() - 42000, '/', '', true, true);

// Redirect to login page
header("Location: login.php");
exit;
