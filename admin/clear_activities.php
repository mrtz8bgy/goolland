<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Check if admin has permission
$adminRole = $_SESSION['admin_role'] ?? 'admin';
if ($adminRole !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Clear activities
try {
    $pdo->exec("TRUNCATE TABLE admin_activities");
    
    // Log activity (this will be the last one)
    logAdminActivity('clear_activities', 'تاریخچه فعالیت‌ها پاک شد');
    
    header("Location: activities.php?success=1");
    exit;
} catch (PDOException $e) {
    header("Location: activities.php?error=1");
    exit;
}
