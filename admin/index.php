<?php
/**
 * Admin Panel Index
 * Redirects to dashboard
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (isAdminLoggedIn()) {
    header("Location: dashboard.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
