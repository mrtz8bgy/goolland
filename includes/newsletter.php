<?php
/**
 * Newsletter API
 * Handles newsletter subscription
 */

require_once 'config.php';

header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'subscribe':
        subscribe();
        break;
    case 'unsubscribe':
        unsubscribe();
        break;
    default:
        // Handle direct form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            subscribe();
        } else {
            echo json_encode(['success' => false, 'message' => 'عملیات مشخص نشده است']);
        }
}

/**
 * Subscribe to newsletter
 */
function subscribe() {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'لطفا آدرس ایمیل را وارد کنید']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'لطفا آدرس ایمیل معتبر وارد کنید']);
        return;
    }
    
    $result = subscribeToNewsletterEmail($email);
    
    echo json_encode($result);
}

/**
 * Unsubscribe from newsletter
 */
function unsubscribe() {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'لطفا آدرس ایمیل را وارد کنید']);
        return;
    }
    
    $result = unsubscribeFromNewsletter($email);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'با موفقیت از خبرنامه خارج شدید']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در خارج شدن از خبرنامه']);
    }
}
