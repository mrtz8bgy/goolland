<?php
/**
 * Wishlist API
 * Handles wishlist operations via AJAX
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'شما وارد حساب کاربری نشده‌اید']);
    exit;
}

$userId = getCurrentUserId();
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    case 'add':
        addToWishlist();
        break;
    case 'remove':
        removeFromWishlist();
        break;
    case 'toggle':
        toggleWishlist();
        break;
    case 'get':
        getWishlist();
        break;
    case 'clear':
        clearWishlist();
        break;
    case 'is_in_wishlist':
        isInWishlist();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'عملیات مشخص نشده است']);
}

/**
 * Add to wishlist
 */
function addToWishlist() {
    $userId = getCurrentUserId();
    $productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'شناسه محصول معتبر نیست']);
        return;
    }
    
    $result = addProductToWishlist($userId, $productId);
    
    if ($result['success']) {
        // Get updated wishlist count
        $wishlistCount = getWishlistCount($userId);
        $result['wishlist_count'] = $wishlistCount;
    }
    
    echo json_encode($result);
}

/**
 * Remove from wishlist
 */
function removeFromWishlist() {
    $userId = getCurrentUserId();
    $productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'شناسه محصول معتبر نیست']);
        return;
    }
    
    $result = removeProductFromWishlist($userId, $productId);
    
    if ($result['success']) {
        // Get updated wishlist count
        $wishlistCount = getWishlistCount($userId);
        $result['wishlist_count'] = $wishlistCount;
    }
    
    echo json_encode($result);
}

/**
 * Toggle wishlist (add if not in wishlist, remove if in wishlist)
 */
function toggleWishlist() {
    $userId = getCurrentUserId();
    $productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'شناسه محصول معتبر نیست']);
        return;
    }
    
    // Check if already in wishlist
    if (isInWishlist($userId, $productId)) {
        $result = removeProductFromWishlist($userId, $productId);
        $action = 'removed';
    } else {
        $result = addProductToWishlist($userId, $productId);
        $action = 'added';
    }
    
    if ($result['success']) {
        $wishlistCount = getWishlistCount($userId);
        $result['wishlist_count'] = $wishlistCount;
        $result['action'] = $action;
    }
    
    echo json_encode($result);
}

/**
 * Get wishlist
 */
function getWishlist() {
    $userId = getCurrentUserId();
    
    $wishlistItems = getWishlistItemsWithDetails($userId);
    
    echo json_encode([
        'success' => true,
        'items' => $wishlistItems,
        'count' => count($wishlistItems)
    ]);
}

/**
 * Clear wishlist
 */
function clearWishlist() {
    $userId = getCurrentUserId();
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?");
        $result = $stmt->execute([$userId]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'لیست علاقه‌مندی‌ها با موفقیت خالی شد', 'wishlist_count' => 0]);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در خالی کردن لیست علاقه‌مندی‌ها']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'خطا: ' . $e->getMessage()]);
    }
}

/**
 * Check if product is in wishlist
 */
function isInWishlist() {
    $userId = getCurrentUserId();
    $productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'in_wishlist' => false, 'message' => 'شناسه محصول معتبر نیست']);
        return;
    }
    
    $isInWishlist = isInWishlist($userId, $productId);
    
    echo json_encode(['success' => true, 'in_wishlist' => $isInWishlist]);
}
