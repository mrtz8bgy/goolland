<?php
session_start();
require_once 'includes/config.php';

// Log activity
if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'logout', 'User logged out');
}

// Log admin activity
if (isset($_SESSION['admin_id'])) {
    logAdminActivity('logout', 'Admin logged out');
}

// Clear session
$_SESSION = [];

// Destroy session
session_destroy();

// Clear cookies
setcookie('user_token', '', time() - 3600, '/');
setcookie('user_id', '', time() - 3600, '/');
setcookie('admin_token', '', time() - 3600, '/');
setcookie('admin_id', '', time() - 3600, '/');

// Redirect to home
header("Location: index.php");
exit;
