<?php
session_start();
require_once 'includes/config.php';

$error = '';
$order = null;
$orderId = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_order'])) {
    $orderIdInput = trim($_POST['order_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($orderIdInput)) {
        $error = 'لطفا شماره سفارش را وارد کنید.';
    } else {
        // Try to find order by order number
        $orderId = null;
        
        // Check if it's a numeric ID
        if (is_numeric($orderIdInput)) {
            $orderId = intval($orderIdInput);
        } else {
            // Try to find by order number
            try {
                $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_number = ?");
                $stmt->execute([$orderIdInput]);
                $orderId = $stmt->fetchColumn();
            } catch (PDOException $e) {
                $orderId = null;
            }
        }
        
        if ($orderId) {
            // Check if user is logged in and owns the order
            if (isLoggedIn()) {
                $userId = getCurrentUserId();
                $order = getOrderDetails($orderId, $userId);
                
                if (!$order) {
                    $error = 'سفارش یافت نشد یا متعلق به شما نیست.';
                }
            } elseif (!empty($email)) {
                // Try to find order by email
                $result = trackOrder($orderId, $email);
                
                if ($result['success']) {
                    $order = $result['order'];
                } else {
                    $error = $result['message'];
                }
            } else {
                $error = 'لطفا آدرس ایمیل خود را وارد کنید.';
            }
        } else {
            $error = 'سفارش یافت نشد.';
        }
    }
} elseif (isset($_GET['order_id'])) {
    // Direct link with order ID
    $orderId = intval($_GET['order_id']);
    
    if (isLoggedIn()) {
        $userId = getCurrentUserId();
        $order = getOrderDetails($orderId, $userId);
        
        if (!$order) {
            $error = 'سفارش یافت نشد یا متعلق به شما نیست.';
        }
    } else {
        // Redirect to login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit;
    }
}

// Get order items if order exists
$orderItems = [];
$orderStatuses = [
    'pending' => 'در انتظار',
    'processing' => 'در حال پردازش',
    'shipped' => 'ارسال شده',
    'delivered' => 'تحویل داده شده',
    'completed' => 'تکمیل شده',
    'cancelled' => 'کنسل شده'
];

if ($order) {
    $orderItems = getOrderItemsByOrderId($order['id']);
    
    // Get shipping address
    $shippingAddress = json_decode($order['shipping_address'], true);
    
    // Get payment method text
    $paymentMethodText = '';
    switch ($order['payment_method']) {
        case 'cash':
            $paymentMethodText = 'پرداخت در محل';
            break;
        case 'online':
            $paymentMethodText = 'پرداخت آنلاین';
            break;
        case 'transfer':
            $paymentMethodText = 'واریز بانکی';
            break;
        case 'wallet':
            $paymentMethodText = 'کیف پول';
            break;
        default:
            $paymentMethodText = $order['payment_method'];
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
    
    // Calculate totals
    $subtotal = 0;
    $totalQuantity = 0;
    
    foreach ($orderItems as $item) {
        $subtotal += $item['total_price'];
        $totalQuantity += $item['quantity'];
    }
    
    $finalTotal = $order['final_amount'];
}

$pageTitle = 'پیگیری سفارش';
$pageDescription = 'پیگیری سفارش در فروشگاه آنلاین گل و گیاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-map-marker-alt"></i> پیگیری سفارش</h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <span>پیگیری سفارش</span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="order-tracking-page">
    <div class="container">
        <!-- Error Message -->
        <?php if ($error && !$order): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$order): ?>
            <!-- Tracking Form -->
            <div class="tracking-form-section">
                <div class="tracking-form-card">
                    <div class="tracking-form-header">
                        <h2><i class="fas fa-search"></i> پیگیری سفارش</h2>
                        <p>برای پیگیری سفارش خود، شماره سفارش و آدرس ایمیل را وارد کنید</p>
                    </div>
                    
                    <form method="POST" action="order-tracking.php" class="tracking-form">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="order_id">شماره سفارش <span style="color: #f44336;">*</span></label>
                                    <input type="text" id="order_id" name="order_id" 
                                           class="form-control" 
                                           placeholder="شماره سفارش یا کد پیگیری" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="email">آدرس ایمیل <span style="color: #f44336;">*</span></label>
                                    <input type="email" id="email" name="email" 
                                           class="form-control" 
                                           placeholder="آدرس ایمیل ثبت شده" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="track_order" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                جستجو
                            </button>
                        </div>
                    </form>
                    
                    <div class="tracking-info">
                        <p><i class="fas fa-info-circle"></i> شماره سفارش را می‌توانید در ایمیل تایید سفارش یا در صفحه تایید سفارش پیدا کنید.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Order Tracking Info -->
            <div class="tracking-info-section">
                <!-- Success Message -->
                <div class="tracking-success">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2>سفارش یافت شد!</h2>
                    <p>شماره سفارش: <strong><?php echo htmlspecialchars($order['order_number'] ?? 'GO-' . $order['id']); ?></strong></p>
                </div>
                
                <div class="tracking-container">
                    <!-- Order Summary -->
                    <div class="tracking-main">
                        <!-- Order Status -->
                        <div class="tracking-status">
                            <h3><i class="fas fa-clipboard-list"></i> وضعیت سفارش</h3>
                            
                            <!-- Status Timeline -->
                            <div class="status-timeline">
                                <div class="timeline-item <?php echo $order['status'] === 'cancelled' ? 'cancelled' : ($order['status'] === 'pending' ? 'active' : 'completed'); ?>">
                                    <div class="timeline-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h4>در انتظار</h4>
                                        <p>سفارش در انتظار پردازش است</p>
                                        <span class="timeline-date">
                                            <?php echo formatDate($order['created_at']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="timeline-arrow">
                                    <i class="fas fa-chevron-left"></i>
                                </div>
                                
                                <div class="timeline-item <?php echo in_array($order['status'], ['cancelled', 'pending']) ? '' : ($order['status'] === 'processing' ? 'active' : 'completed'); ?>">
                                    <div class="timeline-icon">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h4>در حال پردازش</h4>
                                        <p>سفارش در حال آماده‌سازی است</p>
                                    </div>
                                </div>
                                
                                <div class="timeline-arrow">
                                    <i class="fas fa-chevron-left"></i>
                                </div>
                                
                                <div class="timeline-item <?php echo in_array($order['status'], ['cancelled', 'pending', 'processing']) ? '' : ($order['status'] === 'shipped' ? 'active' : 'completed'); ?>">
                                    <div class="timeline-icon">
                                        <i class="fas fa-truck"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h4>ارسال شده</h4>
                                        <p>سفارش ارسال شده است</p>
                                    </div>
                                </div>
                                
                                <div class="timeline-arrow">
                                    <i class="fas fa-chevron-left"></i>
                                </div>
                                
                                <div class="timeline-item <?php echo in_array($order['status'], ['cancelled', 'pending', 'processing', 'shipped']) ? '' : ($order['status'] === 'delivered' ? 'active' : 'completed'); ?>">
                                    <div class="timeline-icon">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h4>تحویل داده شده</h4>
                                        <p>سفارش تحویل داده شده است</p>
                                    </div>
                                </div>
                                
                                <div class="timeline-arrow">
                                    <i class="fas fa-chevron-left"></i>
                                </div>
                                
                                <div class="timeline-item <?php echo $order['status'] === 'completed' ? 'completed' : ''; ?>">
                                    <div class="timeline-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h4>تکمیل شده</h4>
                                        <p>سفارش تکمیل شده است</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Current Status -->
                            <div class="current-status">
                                <h4>وضعیت فعلی سفارش:</h4>
                                <span class="status-badge <?php echo $order['status']; ?>">
                                    <?php echo $orderStatuses[$order['status']] ?? $order['status']; ?>
                                </span>
                            </div>
                            
                            <!-- Status Message -->
                            <div class="status-message">
                                <?php
                                switch ($order['status']) {
                                    case 'pending':
                                        echo '<p><i class="fas fa-info-circle"></i> سفارش شما در انتظار پردازش است. به زودی اقدامات لازم انجام خواهد شد.</p>';
                                        break;
                                    case 'processing':
                                        echo '<p><i class="fas fa-info-circle"></i> سفارش شما در حال پردازش است. محصولات شما در حال آماده‌سازی هستند.</p>';
                                        break;
                                    case 'shipped':
                                        echo '<p><i class="fas fa-info-circle"></i> سفارش شما ارسال شده است. کد پیگیری: <strong>' . htmlspecialchars($order['tracking_number'] ?? 'در حال دریافت') . '</strong></p>';
                                        break;
                                    case 'delivered':
                                        echo '<p><i class="fas fa-info-circle"></i> سفارش شما تحویل داده شده است. امیدواریم از خرید خود راضی باشید!</p>';
                                        break;
                                    case 'completed':
                                        echo '<p><i class="fas fa-info-circle"></i> سفارش شما تکمیل شده است. از خرید شما سپاسگزاریم!</p>';
                                        break;
                                    case 'cancelled':
                                        echo '<p><i class="fas fa-exclamation-circle"></i> سفارش شما کنسل شده است. در صورت نیاز با پشتیبانی تماس بگیرید.</p>';
                                        break;
                                    default:
                                        echo '<p><i class="fas fa-info-circle"></i> وضعیت سفارش شما: ' . htmlspecialchars($order['status']) . '</p>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Order Details -->
                        <div class="tracking-details">
                            <h3><i class="fas fa-file-alt"></i> جزئیات سفارش</h3>
                            
                            <div class="details-grid">
                                <div class="detail-card">
                                    <h4><i class="fas fa-shopping-bag"></i> اطلاعات سفارش</h4>
                                    <div class="detail-row">
                                        <span>شماره سفارش:</span>
                                        <strong><?php echo htmlspecialchars($order['order_number'] ?? 'GO-' . $order['id']); ?></strong>
                                    </div>
                                    <div class="detail-row">
                                        <span>تاریخ سفارش:</span>
                                        <strong><?php echo formatDate($order['created_at']); ?></strong>
                                    </div>
                                    <div class="detail-row">
                                        <span>تعداد محصولات:</span>
                                        <strong><?php echo toPersianNumbers($totalQuantity); ?> محصول</strong>
                                    </div>
                                    <div class="detail-row">
                                        <span>مبلغ نهایی:</span>
                                        <strong><?php echo formatPrice($finalTotal); ?></strong>
                                    </div>
                                    <div class="detail-row">
                                        <span>روش پرداخت:</span>
                                        <strong><?php echo $paymentMethodText; ?></strong>
                                    </div>
                                    <div class="detail-row">
                                        <span>وضعیت پرداخت:</span>
                                        <strong class="payment-status <?php echo $order['payment_status'] === 'paid' ? 'paid' : 'pending'; ?>">
                                            <?php echo $paymentStatusText; ?>
                                        </strong>
                                    </div>
                                </div>
                                
                                <div class="detail-card">
                                    <h4><i class="fas fa-truck"></i> اطلاعات ارسال</h4>
                                    <div class="detail-row">
                                        <span>روش ارسال:</span>
                                        <strong>ارسال استاندارد</strong>
                                    </div>
                                    <div class="detail-row">
                                        <span>هزینه ارسال:</span>
                                        <strong><?php echo formatPrice($order['shipping_cost']); ?></strong>
                                    </div>
                                    <?php if ($order['tracking_number']): ?>
                                        <div class="detail-row">
                                            <span>کد پیگیری:</span>
                                            <strong><?php echo htmlspecialchars($order['tracking_number']); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <div class="detail-row">
                                        <span>آدرس تحویل:</span>
                                        <div class="address-text">
                                            <?php if ($shippingAddress): ?>
                                                <?php echo htmlspecialchars($shippingAddress['first_name'] . ' ' . $shippingAddress['last_name']); ?>
                                                <br>
                                                <?php echo htmlspecialchars($shippingAddress['phone']); ?>
                                                <br>
                                                <?php echo htmlspecialchars($shippingAddress['province_name'] ?? ''); ?>
                                                <?php echo htmlspecialchars($shippingAddress['city_name'] ?? ''); ?>
                                                <br>
                                                <?php echo htmlspecialchars($shippingAddress['address']); ?>
                                            <?php else: ?>
                                                آدرس ثبت نشده
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="detail-card">
                                    <h4><i class="fas fa-box"></i> محصولات سفارش</h4>
                                    <div class="tracking-products">
                                        <?php foreach ($orderItems as $item): ?>
                                            <?php
                                            $product = getProductById($item['product_id']);
                                            if (!$product) continue;
                                            
                                            $productImages = getProductImages($item['product_id']);
                                            $productImage = !empty($productImages) ? $productImages[0]['image_path'] : ($product['image_path'] ?: 'assets/images/no-image.jpg');
                                            ?>
                                            
                                            <div class="tracking-product">
                                                <div class="tracking-product-image">
                                                    <img src="<?php echo $productImage; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                </div>
                                                <div class="tracking-product-info">
                                                    <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                                                    <span class="tracking-product-quantity">x <?php echo toPersianNumbers($item['quantity']); ?></span>
                                                    <span class="tracking-product-price"><?php echo formatPrice($item['total_price']); ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="tracking-product-totals">
                                        <div class="total-row">
                                            <span>جمع سبد خرید:</span>
                                            <span><?php echo formatPrice($order['total_amount']); ?></span>
                                        </div>
                                        <div class="total-row">
                                            <span>هزینه ارسال:</span>
                                            <span><?php echo formatPrice($order['shipping_cost']); ?></span>
                                        </div>
                                        <?php if ($order['discount_amount'] > 0): ?>
                                            <div class="total-row">
                                                <span>تخفیف:</span>
                                                <span class="discount">-<?php echo formatPrice($order['discount_amount']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="total-row final">
                                            <span>مبلغ نهایی:</span>
                                            <span><?php echo formatPrice($finalTotal); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tracking Sidebar -->
                    <aside class="tracking-sidebar">
                        <div class="tracking-actions">
                            <a href="order-confirmation.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary btn-block">
                                <i class="fas fa-print"></i>
                                چاپ فاکتور
                            </a>
                            
                            <a href="orders.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-list"></i>
                                لیست سفارشات
                            </a>
                            
                            <a href="contact.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-envelope"></i>
                                تماس با پشتیبانی
                            </a>
                            
                            <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                                <form method="POST" action="orders.php" class="cancel-form">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" name="cancel_order" class="btn btn-danger btn-block" onclick="return confirm('آیا از کنسل کردن این سفارش مطمئن هستید؟')">
                                        <i class="fas fa-times"></i>
                                        کنسل کردن سفارش
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Need Help? -->
                        <div class="tracking-help">
                            <h3><i class="fas fa-question-circle"></i> به کمک نیاز دارید؟</h3>
                            <p>اگر سوالی درباره سفارش خود دارید، با تیم پشتیبانی ما تماس بگیرید.</p>
                            <div class="help-contacts">
                                <div class="help-contact">
                                    <i class="fas fa-phone"></i>
                                    <a href="tel:<?php echo htmlspecialchars(getSetting('site_phone', '021-12345678')); ?>">
                                        <?php echo htmlspecialchars(getSetting('site_phone', '021-12345678')); ?>
                                    </a>
                                </div>
                                <div class="help-contact">
                                    <i class="fas fa-envelope"></i>
                                    <a href="mailto:<?php echo htmlspecialchars(getSetting('site_email', 'info@goolland.ir')); ?>">
                                        <?php echo htmlspecialchars(getSetting('site_email', 'info@goolland.ir')); ?>
                                    </a>
                                </div>
                                <div class="help-contact">
                                    <i class="fab fa-whatsapp"></i>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', getSetting('site_phone', '021-12345678')); ?>" target="_blank">
                                        واتس‌اپ
                                    </a>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
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
// Tracking page specific scripts
</script>

<?php require_once 'includes/footer.php'; ?>
