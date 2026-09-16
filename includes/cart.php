<?php
/**
 * Cart API
 * Handles cart operations via AJAX
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
        addToCart();
        break;
    case 'update':
        updateCart();
        break;
    case 'remove':
        removeFromCart();
        break;
    case 'clear':
        clearCart();
        break;
    case 'get':
        getCart();
        break;
    case 'save_for_later':
        saveForLater();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'عملیات مشخص نشده است']);
}

/**
 * Add to cart
 */
function addToCart() {
    $userId = getCurrentUserId();
    $productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
    
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'شناسه محصول معتبر نیست']);
        return;
    }
    
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'تعداد باید بیشتر از 0 باشد']);
        return;
    }
    
    $result = addProductToCart($userId, $productId, $quantity);
    
    if ($result['success']) {
        // Get updated cart count
        $cartCount = getCartCount($userId);
        $result['cart_count'] = $cartCount;
    }
    
    echo json_encode($result);
}

/**
 * Update cart item
 */
function updateCart() {
    $userId = getCurrentUserId();
    $cartId = isset($_GET['cart_id']) ? intval($_GET['cart_id']) : 0;
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
    
    if ($cartId <= 0) {
        echo json_encode(['success' => false, 'message' => 'شناسه سبد خرید معتبر نیست']);
        return;
    }
    
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'تعداد باید بیشتر از 0 باشد']);
        return;
    }
    
    $result = updateCartItemQuantity($cartId, $quantity, $userId);
    
    if ($result['success']) {
        // Get updated cart totals
        $cartTotals = calculateCartTotals($userId);
        $result['cart_totals'] = $cartTotals;
    }
    
    echo json_encode($result);
}

/**
 * Remove from cart
 */
function removeFromCart() {
    $userId = getCurrentUserId();
    $cartId = isset($_GET['cart_id']) ? intval($_GET['cart_id']) : 0;
    
    if ($cartId <= 0) {
        echo json_encode(['success' => false, 'message' => 'شناسه سبد خرید معتبر نیست']);
        return;
    }
    
    $result = deleteCartItem($cartId, $userId);
    
    if ($result['success']) {
        // Get updated cart count and totals
        $cartCount = getCartCount($userId);
        $cartTotals = calculateCartTotals($userId);
        $result['cart_count'] = $cartCount;
        $result['cart_totals'] = $cartTotals;
    }
    
    echo json_encode($result);
}

/**
 * Clear cart
 */
function clearCart() {
    $userId = getCurrentUserId();
    
    $result = clearCart($userId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'سبد خرید با موفقیت خالی شد', 'cart_count' => 0]);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در خالی کردن سبد خرید']);
    }
}

/**
 * Get cart
 */
function getCart() {
    $userId = getCurrentUserId();
    
    $cartItems = getCartItemsWithDetails($userId);
    $cartTotals = calculateCartTotals($userId);
    
    echo json_encode([
        'success' => true,
        'items' => $cartItems,
        'totals' => $cartTotals
    ]);
}

/**
 * Save for later (move from cart to wishlist)
 */
function saveForLater() {
    $userId = getCurrentUserId();
    $cartId = isset($_GET['cart_id']) ? intval($_GET['cart_id']) : 0;
    $productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    
    if ($cartId <= 0 || $productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'شناسه‌ها معتبر نیستند']);
        return;
    }
    
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Add to wishlist
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE created_at = NOW()");
        $stmt->execute([$userId, $productId]);
        
        // Remove from cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cartId, $userId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'محصول به لیست علاقه‌مندی‌ها منتقل شد',
            'cart_count' => getCartCount($userId),
            'wishlist_count' => getWishlistCount($userId)
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'خطا در انتقال محصول: ' . $e->getMessage()]);
    }
}
