<?php
/**
 * Contact API
 * Handles contact form submission
 */

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'متد درخواست معتبر نیست']);
    exit;
}

// Get form data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validate inputs
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'لطفا نام و نام خانوادگی را وارد کنید']);
    exit;
}

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'لطفا آدرس ایمیل را وارد کنید']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'لطفا آدرس ایمیل معتبر وارد کنید']);
    exit;
}

if (empty($subject)) {
    echo json_encode(['success' => false, 'message' => 'لطفا موضوع را وارد کنید']);
    exit;
}

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'لطفا پیام را وارد کنید']);
    exit;
}

// Submit contact form
$result = contactUs([
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'subject' => $subject,
    'message' => $message
]);

echo json_encode($result);
