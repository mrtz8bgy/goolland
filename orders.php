<?php
session_start();
require_once 'includes/db.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Get user ID
$userId = getCurrentUserId();

// Get user orders
$orders = getUserOrdersWithDetails($userId);

// Handle cancel order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $orderId = intval($_POST['order_id']);
    
    $result = cancelOrder($orderId, $userId);
    
    if ($result['success']) {
        $success = $result['message'];
        // Refresh orders
        $orders = getUserOrdersWithDetails($userId);
    } else {
        $error = $result['message'];
    }
}

// Get order statuses
$orderStatuses = [
    'pending' => 'در انتظار',
    'processing' => 'در حال پردازش',
    'shipped' => 'ارسال شده',
    'delivered' => 'تحویل داده شده',
    'completed' => 'تکمیل شده',
    'cancelled' => 'کنسل شده'
];

$pageTitle = 'سفارشات من';
$pageDescription = 'لیست سفارشات شما در فروشگاه آنلاین گل و گیاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-list"></i> سفارشات من</h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <span>سفارشات من</span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="orders-page">
    <div class="container">
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <i class="fas fa-shopping-bag"></i>
                <h3>هیچ سفارشی یافت نشد</h3>
                <p>شما هنوز هیچ سفارشی نداده‌اید.</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i>
                    خرید کنید
                </a>
            </div>
        <?php else: ?>
            <div class="orders-container">
                <!-- Orders List -->
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <?php
                        // Calculate order totals
                        $orderTotal = 0;
                        $orderQuantity = 0;
                        
                        foreach ($order['items'] as $item) {
                            $orderTotal += $item['total_price'];
                            $orderQuantity += $item['quantity'];
                        }
                        
                        $orderTotal += $order['shipping_cost'] - $order['discount_amount'];
                        
                        // Get status text and class
                        $statusText = $orderStatuses[$order['status']] ?? $order['status'];
                        $statusClass = '';
                        
                        switch ($order['status']) {
                            case 'pending':
                                $statusClass = 'pending';
                                break;
                            case 'processing':
                                $statusClass = 'processing';
                                break;
                            case 'shipped':
                                $statusClass = 'shipped';
                                break;
                            case 'delivered':
                            case 'completed':
                                $statusClass = 'completed';
                                break;
                            case 'cancelled':
                                $statusClass = 'cancelled';
                                break;
                            default:
                                $statusClass = 'pending';
                        }
                        
                        // Get payment status text
                        $paymentStatusText = '';
                        switch ($order['payment_status']) {
                            case 'paid':
                                $paymentStatusText = 'پرداخت شده';
                                break;
                            case 'pending':
                                $paymentStatusText = 'در انتظار پرداخت';
                                break;
                            case 'failed':
                                $paymentStatusText = 'پرداخت ناموفق';
                                break;
                            case 'refunded':
                                $paymentStatusText = 'عوض شده';
                                break;
                            default:
                                $paymentStatusText = $order['payment_status'];
                        }
                        
                        // Check if order can be cancelled
                        $canCancel = in_array($order['status'], ['pending', 'processing']);
                        
                        // Get first product image for order
                        $firstProductImage = 'assets/images/no-image.jpg';
                        if (!empty($order['items'])) {
                            $firstProduct = getProductById($order['items'][0]['product_id']);
                            if ($firstProduct && $firstProduct['image_path']) {
                                $firstProductImage = $firstProduct['image_path'];
                            }
                        }
                        ?>
                        
                        <div class="order-card" data-order-id="<?php echo $order['id']; ?>">
                            <div class="order-header">
                                <div class="order-id">
                                    <span>شماره سفارش:</span>
                                    <strong><?php echo htmlspecialchars($order['order_number'] ?? 'GO-' . $order['id']); ?></strong>
                                </div>
                                <div class="order-date">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo formatDate($order['created_at']); ?></span>
                                </div>
                            </div>
                            
                            <div class="order-body">
                                <div class="order-products">
                                    <?php if (!empty($order['items'])): ?>
                                        <div class="order-product-list">
                                            <?php foreach (array_slice($order['items'], 0, 3) as $item): ?>
                                                <?php
                                                $product = getProductById($item['product_id']);
                                                if (!$product) continue;
                                                
                                                $productImages = getProductImages($item['product_id']);
                                                $productImage = !empty($productImages) ? $productImages[0]['image_path'] : ($product['image_path'] ?: 'assets/images/no-image.jpg');
                                                ?>
                                                <div class="order-product">
                                                    <div class="order-product-image">
                                                        <img src="<?php echo $productImage; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                    </div>
                                                    <div class="order-product-info">
                                                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                                        <span class="order-product-quantity">x <?php echo toPersianNumbers($item['quantity']); ?></span>
                                                        <span class="order-product-price"><?php echo formatPrice($item['total_price']); ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            
                                            <?php if (count($order['items']) > 3): ?>
                                                <div class="order-product-more">
                                                    + <?php echo toPersianNumbers(count($order['items']) - 3); ?> محصول دیگر
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="order-details">
                                    <div class="order-detail">
                                        <span>مبلغ سفارش:</span>
                                        <strong><?php echo formatPrice($order['total_amount']); ?></strong>
                                    </div>
                                    <div class="order-detail">
                                        <span>تعداد محصولات:</span>
                                        <strong><?php echo toPersianNumbers($orderQuantity); ?> محصول</strong>
                                    </div>
                                    <div class="order-detail">
                                        <span>هزینه ارسال:</span>
                                        <strong><?php echo formatPrice($order['shipping_cost']); ?></strong>
                                    </div>
                                    <?php if ($order['discount_amount'] > 0): ?>
                                        <div class="order-detail">
                                            <span>تخفیف:</span>
                                            <strong class="discount">-<?php echo formatPrice($order['discount_amount']); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <div class="order-detail total">
                                        <span>مبلغ نهایی:</span>
                                        <strong><?php echo formatPrice($order['final_amount']); ?></strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="order-footer">
                                <div class="order-status">
                                    <span>وضعیت سفارش:</span>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </div>
                                <div class="order-payment-status">
                                    <span>وضعیت پرداخت:</span>
                                    <span class="payment-status <?php echo $order['payment_status'] === 'paid' ? 'paid' : 'pending'; ?>">
                                        <?php echo $paymentStatusText; ?>
                                    </span>
                                </div>
                                
                                <div class="order-actions">
                                    <a href="order-tracking.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-map-marker-alt"></i>
                                        پیگیری سفارش
                                    </a>
                                    
                                    <?php if ($canCancel): ?>
                                        <form method="POST" action="orders.php" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" name="cancel_order" 
                                                    class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('آیا از کنسل کردن این سفارش مطمئن هستید؟')">
                                                <i class="fas fa-times"></i>
                                                کنسل کردن
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <a href="order-confirmation.php?order_id=<?php echo $order['id']; ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-print"></i>
                                        چاپ فاکتور
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php
                $totalPages = ceil(count($orders) / 10);
                $currentPage = 1;
                
                if ($totalPages > 1):
                ?>
                    <nav class="pagination">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?php echo $currentPage - 1; ?>" class="page-item">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-item disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i === $currentPage): ?>
                                <span class="page-item active"><?php echo toPersianNumbers($i); ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>" class="page-item">
                                    <?php echo toPersianNumbers($i); ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?php echo $currentPage + 1; ?>" class="page-item">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-item disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Related Products -->
<?php
$relatedProducts = getFeaturedProducts(4);
if (!empty($relatedProducts)):
?>
    <section class="related-products-section">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-gift"></i> محصولات ویژه</h2>
            <div class="products-grid">
                <?php foreach ($relatedProducts as $product): ?>
                    <?php include 'includes/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<script>
// Orders page specific scripts
</script>

<?php require_once 'includes/footer.php'; ?>
