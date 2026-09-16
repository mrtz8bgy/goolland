<?php
/**
 * Goolland Order API
 * Handles order creation, tracking, and management
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
        case 'create':
            handleOrderCreation();
            break;
        case 'track':
            handleOrderTracking();
            break;
        case 'cancel':
            handleOrderCancellation();
            break;
        case 'get_status':
            getOrderStatus();
            break;
        case 'get_details':
            getOrderDetails();
            break;
        case 'reorder':
            handleReorder();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Action not specified']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Handle order creation
 */
function handleOrderCreation() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    
    // Validate cart is not empty
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_count = $stmt->fetch()['count'];
    
    if ($cart_count == 0) {
        echo json_encode(['success' => false, 'message' => 'سبد خرید شما خالی است']);
        return;
    }
    
    // Get cart items
    $stmt = $pdo->prepare("SELECT c.*, p.price, p.sale_price, p.stock, p.name as product_name, p.image as product_image, p.sku as product_sku FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();
    
    // Check stock for all items
    foreach ($cart_items as $item) {
        if ($item['stock'] < $item['quantity']) {
            echo json_encode(['success' => false, 'message' => 'متأسفانه تعداد مورد نظر از محصول "' . $item['product_name'] . '" در انبار موجود نیست']);
            return;
        }
    }
    
    // Calculate totals
    $subtotal = 0;
    $total_items = 0;
    
    foreach ($cart_items as $item) {
        $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
        $subtotal += $price * $item['quantity'];
        $total_items += $item['quantity'];
    }
    
    // Get shipping cost (for now, fixed amount)
    $shipping_cost = 0;
    
    // Get discount from coupon if applicable
    $coupon_code = trim($_POST['coupon_code'] ?? '');
    $discount = 0;
    
    if ($coupon_code) {
        $coupon = getCouponByCode($coupon_code);
        if ($coupon && $coupon['status'] == 'active' && $coupon['expire_date'] >= date('Y-m-d')) {
            if ($coupon['type'] == 'percentage') {
                $discount = ($subtotal * $coupon['value']) / 100;
            } else {
                $discount = $coupon['value'];
            }
            
            // Don't allow discount more than subtotal
            $discount = min($discount, $subtotal);
        }
    }
    
    $total = $subtotal + $shipping_cost - $discount;
    
    // Get user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Get shipping info from form
    $shipping_first_name = trim($_POST['shipping_first_name'] ?? $user['first_name']);
    $shipping_last_name = trim($_POST['shipping_last_name'] ?? $user['last_name']);
    $shipping_phone = trim($_POST['shipping_phone'] ?? $user['phone']);
    $shipping_email = trim($_POST['shipping_email'] ?? $user['email']);
    $shipping_address = trim($_POST['shipping_address'] ?? $user['address']);
    $shipping_city = trim($_POST['shipping_city'] ?? $user['city']);
    $shipping_state = trim($_POST['shipping_state'] ?? $user['state']);
    $shipping_postal_code = trim($_POST['shipping_postal_code'] ?? $user['postal_code']);
    $shipping_country = trim($_POST['shipping_country'] ?? 'ایران');
    
    // Use same for billing if checkbox is checked
    $same_as_billing = isset($_POST['same_as_billing']) ? true : false;
    
    if ($same_as_billing) {
        $billing_first_name = $shipping_first_name;
        $billing_last_name = $shipping_last_name;
        $billing_phone = $shipping_phone;
        $billing_email = $shipping_email;
        $billing_address = $shipping_address;
        $billing_city = $shipping_city;
        $billing_state = $shipping_state;
        $billing_postal_code = $shipping_postal_code;
        $billing_country = $shipping_country;
    } else {
        $billing_first_name = trim($_POST['billing_first_name'] ?? $user['first_name']);
        $billing_last_name = trim($_POST['billing_last_name'] ?? $user['last_name']);
        $billing_phone = trim($_POST['billing_phone'] ?? $user['phone']);
        $billing_email = trim($_POST['billing_email'] ?? $user['email']);
        $billing_address = trim($_POST['billing_address'] ?? $user['address']);
        $billing_city = trim($_POST['billing_city'] ?? $user['city']);
        $billing_state = trim($_POST['billing_state'] ?? $user['state']);
        $billing_postal_code = trim($_POST['billing_postal_code'] ?? $user['postal_code']);
        $billing_country = trim($_POST['billing_country'] ?? 'ایران');
    }
    
    $notes = trim($_POST['notes'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'cash_on_delivery');
    
    // Generate order number
    $order_number = 'GOOL-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
    
    // Generate tracking code
    $tracking_code = strtoupper(substr(md5(uniqid() . time()), 0, 12));
    
    // Create order
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO orders 
            (user_id, order_number, status, payment_method, payment_status, 
            subtotal, shipping_cost, discount, tax, total, currency,
            shipping_first_name, shipping_last_name, shipping_phone, shipping_email,
            shipping_address, shipping_city, shipping_state, shipping_postal_code, shipping_country,
            billing_first_name, billing_last_name, billing_phone, billing_email,
            billing_address, billing_city, billing_state, billing_postal_code, billing_country,
            notes, ip_address, user_agent, tracking_code, coupon_code, coupon_discount)
            VALUES (?, ?, 'pending', ?, 'pending', ?, ?, ?, 0, ?, 'IRR', 
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $user_id, $order_number, $payment_method,
            $subtotal, $shipping_cost, $discount, $total,
            $shipping_first_name, $shipping_last_name, $shipping_phone, $shipping_email,
            $shipping_address, $shipping_city, $shipping_state, $shipping_postal_code, $shipping_country,
            $billing_first_name, $billing_last_name, $billing_phone, $billing_email,
            $billing_address, $billing_city, $billing_state, $billing_postal_code, $billing_country,
            $notes, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '',
            $tracking_code, $coupon_code ? $coupon_code : null, $discount
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Add order items
        foreach ($cart_items as $item) {
            $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
            $item_total = $price * $item['quantity'];
            
            $stmt = $pdo->prepare("INSERT INTO order_items 
                (order_id, product_id, product_name, product_sku, quantity, price, subtotal, tax, total)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)");
            $stmt->execute([
                $order_id, $item['product_id'], $item['product_name'], $item['product_sku'],
                $item['quantity'], $price, $item_total, $item_total
            ]);
            
            // Update product stock
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        
        // Mark coupon as used if applicable
        if ($coupon_code && $coupon = getCouponByCode($coupon_code)) {
            $stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
            $stmt->execute([$coupon['id']]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'سفارش شما با موفقیت ثبت شد',
            'order_id' => $order_id,
            'order_number' => $order_number,
            'tracking_code' => $tracking_code,
            'redirect' => 'order-confirmation.php?id=' . $order_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'خطا در ثبت سفارش: ' . $e->getMessage()]);
    }
}

/**
 * Handle order tracking
 */
function handleOrderTracking() {
    global $pdo;
    
    $tracking_code = trim($_POST['tracking_code'] ?? $_GET['tracking_code'] ?? '');
    
    if (empty($tracking_code)) {
        echo json_encode(['success' => false, 'message' => 'لطفاً کد پیگیری را وارد کنید']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.tracking_code = ?");
    $stmt->execute([$tracking_code]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'سفارشی با این کد پیگیری یافت نشد']);
        return;
    }
    
    // Get order items
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$order['id']]);
    $order_items = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'order' => $order,
        'order_items' => $order_items
    ]);
}

/**
 * Handle order cancellation
 */
function handleOrderCancellation() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $reason = trim($_POST['reason'] ?? '');
    
    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID not specified']);
        return;
    }
    
    // Check if order belongs to user
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'سفارشی یافت نشد یا متعلق به شما نیست']);
        return;
    }
    
    // Check if order can be cancelled
    if (!in_array($order['status'], ['pending', 'processing'])) {
        echo json_encode(['success' => false, 'message' => 'این سفارش قابل لغو نیست']);
        return;
    }
    
    // Cancel order
    $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', cancelled_at = NOW(), notes = CONCAT(notes, '\n\nدلیل لغو: ', ?) WHERE id = ?");
    $stmt->execute([$reason, $order_id]);
    
    // Restore product stock
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
    
    foreach ($order_items as $item) {
        $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }
    
    echo json_encode(['success' => true, 'message' => 'سفارش شما با موفقیت لغو شد']);
}

/**
 * Get order status
 */
function getOrderStatus() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
    
    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID not specified']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'سفارشی یافت نشد']);
        return;
    }
    
    echo json_encode(['success' => true, 'status' => $order['status']]);
}

/**
 * Get order details
 */
function getOrderDetails() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
    
    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID not specified']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'سفارشی یافت نشد']);
        return;
    }
    
    // Get order items
    $stmt = $pdo->prepare("SELECT oi.*, p.image as product_image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'order' => $order,
        'order_items' => $order_items
    ]);
}

/**
 * Handle reorder
 */
function handleReorder() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    
    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID not specified']);
        return;
    }
    
    // Get order items
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
    
    if (empty($order_items)) {
        echo json_encode(['success' => false, 'message' => 'سفارشی یافت نشد یا خالی است']);
        return;
    }
    
    // Clear current cart
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Add items to cart
    foreach ($order_items as $item) {
        // Check if product exists and has stock
        $stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id = ?");
        $stmt->execute([$item['product_id']]);
        $product = $stmt->fetch();
        
        if ($product && $product['stock'] >= $item['quantity']) {
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $item['product_id'], $item['quantity']]);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'محصولات سفارش به سبد خرید شما اضافه شد', 'redirect' => 'cart.php']);
}

/**
 * Get coupon by code
 */
function getCouponByCode($code) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ?");
    $stmt->execute([$code]);
    return $stmt->fetch();
}

/**
 * Helper function to check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}
