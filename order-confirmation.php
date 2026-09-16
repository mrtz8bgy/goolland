<?php
session_start();
require_once 'includes/config.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Get order ID
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Get order details
$order = getOrderDetails($orderId, getCurrentUserId());

if (!$order) {
    header("Location: 404.php");
    exit;
}

// Get order items
$orderItems = getOrderItemsByOrderId($orderId);

// Calculate totals
$subtotal = 0;
$totalQuantity = 0;

foreach ($orderItems as $item) {
    $subtotal += $item['total_price'];
    $totalQuantity += $item['quantity'];
}

$finalTotal = $order['final_amount'];

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

// Get shipping address
$shippingAddress = json_decode($order['shipping_address'], true);

// Get order status text
$orderStatuses = [
    'pending' => 'در انتظار',
    'processing' => 'در حال پردازش',
    'shipped' => 'ارسال شده',
    'delivered' => 'تحویل داده شده',
    'completed' => 'تکمیل شده',
    'cancelled' => 'کنسل شده'
];

$statusText = $orderStatuses[$order['status']] ?? $order['status'];

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

// Mark order as seen (if not already)
if ($order['is_seen'] == 0) {
    try {
        $stmt = $pdo->prepare("UPDATE orders SET is_seen = 1 WHERE id = ?");
        $stmt->execute([$orderId]);
    } catch (PDOException $e) {
        // Continue without updating
    }
}

$pageTitle = 'تایید سفارش';
$pageDescription = 'تایید سفارش شماره ' . htmlspecialchars($order['order_number'] ?? 'GO-' . $order['id']);
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-check-circle"></i> تایید سفارش</h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <a href="orders.php">سفارشات من</a>
            <i class="fas fa-chevron-left"></i>
            <span>تایید سفارش</span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="order-confirmation-page">
    <div class="container">
        <!-- Success Message -->
        <div class="confirmation-message">
            <div class="confirmation-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>سفارش شما با موفقیت ثبت شد!</h2>
            <p>از خرید شما از فروشگاه گل و گیاه گولند سپاسگزاریم.</p>
            <p>شماره سفارش شما: <strong><?php echo htmlspecialchars($order['order_number'] ?? 'GO-' . $order['id']); ?></strong></p>
        </div>
        
        <div class="confirmation-container">
            <!-- Order Summary -->
            <div class="confirmation-main">
                <!-- Order Status -->
                <div class="order-status-card">
                    <h3><i class="fas fa-clipboard-list"></i> وضعیت سفارش</h3>
                    <div class="status-row">
                        <span>شماره سفارش:</span>
                        <strong><?php echo htmlspecialchars($order['order_number'] ?? 'GO-' . $order['id']); ?></strong>
                    </div>
                    <div class="status-row">
                        <span>تاریخ سفارش:</span>
                        <strong><?php echo formatDate($order['created_at']); ?></strong>
                    </div>
                    <div class="status-row">
                        <span>وضعیت سفارش:</span>
                        <span class="status-badge <?php echo $order['status']; ?>">
                            <?php echo $statusText; ?>
                        </span>
                    </div>
                    <div class="status-row">
                        <span>وضعیت پرداخت:</span>
                        <span class="payment-status <?php echo $order['payment_status'] === 'paid' ? 'paid' : 'pending'; ?>">
                            <?php echo $paymentStatusText; ?>
                        </span>
                    </div>
                    <div class="status-row">
                        <span>روش پرداخت:</span>
                        <strong><?php echo $paymentMethodText; ?></strong>
                    </div>
                </div>
                
                <!-- Shipping Information -->
                <div class="shipping-info-card">
                    <h3><i class="fas fa-truck"></i> اطلاعات ارسال</h3>
                    <div class="shipping-info">
                        <div class="shipping-address">
                            <h4>آدرس تحویل:</h4>
                            <p>
                                <?php if ($shippingAddress): ?>
                                    <?php echo htmlspecialchars($shippingAddress['first_name'] . ' ' . $shippingAddress['last_name']); ?>
                                    <br>
                                    <?php echo htmlspecialchars($shippingAddress['phone']); ?>
                                    <br>
                                    <?php echo htmlspecialchars($shippingAddress['province_name'] ?? ''); ?>
                                    <?php echo htmlspecialchars($shippingAddress['city_name'] ?? ''); ?>
                                    <br>
                                    <?php echo htmlspecialchars($shippingAddress['address']); ?>
                                    <?php if (!empty($shippingAddress['postal_code'])): ?>
                                        <br>
                                        کد پستی: <?php echo htmlspecialchars($shippingAddress['postal_code']); ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    آدرس در سیستم ثبت نشده است.
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="shipping-method">
                            <h4>روش ارسال:</h4>
                            <p>ارسال استاندارد</p>
                        </div>
                        
                        <div class="shipping-cost">
                            <h4>هزینه ارسال:</h4>
                            <p><?php echo formatPrice($order['shipping_cost']); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="order-items-card">
                    <h3><i class="fas fa-shopping-bag"></i> محصولات سفارش</h3>
                    
                    <div class="order-items-table">
                        <div class="order-items-header">
                            <div class="order-item-col">محصول</div>
                            <div class="order-item-col">قیمت واحد</div>
                            <div class="order-item-col">تعداد</div>
                            <div class="order-item-col">جمع</div>
                        </div>
                        
                        <div class="order-items-body">
                            <?php foreach ($orderItems as $item): ?>
                                <?php
                                $product = getProductById($item['product_id']);
                                if (!$product) continue;
                                
                                $productImages = getProductImages($item['product_id']);
                                $productImage = !empty($productImages) ? $productImages[0]['image_path'] : ($product['image_path'] ?: 'assets/images/no-image.jpg');
                                
                                // Get product variants if available
                                $variantInfo = '';
                                if (isset($item['variant_id']) && $item['variant_id'] > 0) {
                                    try {
                                        $stmt = $pdo->prepare("SELECT name FROM product_variants WHERE id = ?");
                                        $stmt->execute([$item['variant_id']]);
                                        $variant = $stmt->fetch(PDO::FETCH_ASSOC);
                                        if ($variant) {
                                            $variantInfo = ' - ' . htmlspecialchars($variant['name']);
                                        }
                                    } catch (PDOException $e) {
                                        // Continue without variant info
                                    }
                                }
                                ?>
                                
                                <div class="order-item-row">
                                    <div class="order-item-col product">
                                        <div class="order-item-image">
                                            <img src="<?php echo $productImage; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        </div>
                                        <div class="order-item-name">
                                            <?php echo htmlspecialchars($product['name'] . $variantInfo); ?>
                                            <?php if ($product['sku']): ?>
                                                <span class="order-item-sku">کد: <?php echo htmlspecialchars($product['sku']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="order-item-col price">
                                        <?php echo formatPrice($item['unit_price']); ?>
                                    </div>
                                    <div class="order-item-col quantity">
                                        <?php echo toPersianNumbers($item['quantity']); ?>
                                    </div>
                                    <div class="order-item-col total">
                                        <?php echo formatPrice($item['total_price']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Order Totals -->
                    <div class="order-totals">
                        <div class="order-total-row">
                            <span>جمع سبد خرید:</span>
                            <span><?php echo formatPrice($order['total_amount']); ?></span>
                        </div>
                        <div class="order-total-row">
                            <span>هزینه ارسال:</span>
                            <span><?php echo formatPrice($order['shipping_cost']); ?></span>
                        </div>
                        <?php if ($order['discount_amount'] > 0): ?>
                            <div class="order-total-row">
                                <span>تخفیف:</span>
                                <span class="discount">-<?php echo formatPrice($order['discount_amount']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="order-total-row final">
                            <span>مبلغ نهایی:</span>
                            <span class="final-amount"><?php echo formatPrice($finalTotal); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Order Notes -->
                <?php if ($order['notes']): ?>
                    <div class="order-notes-card">
                        <h3><i class="fas fa-sticky-note"></i> یادداشت‌های سفارش</h3>
                        <p><?php echo htmlspecialchars($order['notes']); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- What's Next -->
                <div class="whats-next-card">
                    <h3><i class="fas fa-question-circle"></i> مرحله بعدی چیست؟</h3>
                    <div class="whats-next-steps">
                        <div class="step">
                            <div class="step-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="step-content">
                                <h4>سفارش ثبت شد</h4>
                                <p>سفارش شما با موفقیت ثبت شد و شماره سفارش برای شما ارسال شد.</p>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="step-content">
                                <h4>پردازش سفارش</h4>
                                <p>تیم ما سفارش شما را بررسی و برای ارسال آماده می‌کند.</p>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-icon">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <div class="step-content">
                                <h4>ارسال سفارش</h4>
                                <p>سفارش شما ارسال خواهد شد و کد پیگیری برای شما ارسال می‌شود.</p>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-icon">
                                <i class="fas fa-box-open"></i>
                            </div>
                            <div class="step-content">
                                <h4>تحویل سفارش</h4>
                                <p>سفارش خود را دریافت کرده و از گل‌های زیبا لذت ببرید!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Actions -->
            <aside class="confirmation-sidebar">
                <div class="confirmation-actions">
                    <a href="orders.php" class="btn btn-primary btn-block">
                        <i class="fas fa-list"></i>
                        مشاهده سفارشات
                    </a>
                    
                    <a href="order-tracking.php?order_id=<?php echo $order['id']; ?>" class="btn btn-secondary btn-block">
                        <i class="fas fa-map-marker-alt"></i>
                        پیگیری سفارش
                    </a>
                    
                    <a href="products.php" class="btn btn-outline-primary btn-block">
                        <i class="fas fa-shopping-bag"></i>
                        ادامه خرید
                    </a>
                    
                    <button class="btn btn-secondary btn-block" onclick="printConfirmation()">
                        <i class="fas fa-print"></i>
                        چاپ فاکتور
                    </button>
                    
                    <button class="btn btn-secondary btn-block" onclick="shareOrder()">
                        <i class="fas fa-share-alt"></i>
                        اشتراک گذاری سفارش
                    </button>
                </div>
                
                <!-- Order Info Card -->
                <div class="order-info-card">
                    <h3><i class="fas fa-info-circle"></i> اطلاعات سفارش</h3>
                    <div class="order-info">
                        <div class="info-row">
                            <span>شماره سفارش:</span>
                            <strong><?php echo htmlspecialchars($order['order_number'] ?? 'GO-' . $order['id']); ?></strong>
                        </div>
                        <div class="info-row">
                            <span>تاریخ سفارش:</span>
                            <strong><?php echo formatDate($order['created_at']); ?></strong>
                        </div>
                        <div class="info-row">
                            <span>تعداد محصولات:</span>
                            <strong><?php echo toPersianNumbers($totalQuantity); ?> محصول</strong>
                        </div>
                        <div class="info-row">
                            <span>مبلغ نهایی:</span>
                            <strong><?php echo formatPrice($finalTotal); ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Need Help? -->
                <div class="need-help-card">
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
// Print confirmation function
function printConfirmation() {
    window.print();
}

// Share order function
function shareOrder() {
    const orderNumber = '<?php echo htmlspecialchars($order["order_number"] ?? "GO-" . $order["id"]); ?>';
    const orderUrl = window.location.href;
    const text = 'سفارش من در گولند: ' + orderNumber + ' - ' + orderUrl;
    
    if (navigator.share) {
        navigator.share({
            title: 'سفارش من در گولند',
            text: text,
            url: orderUrl
        }).catch(err => {
            console.error('Error sharing:', err);
        });
    } else {
        // Fallback for browsers that don't support Web Share API
        alert('برای اشتراک گذاری، لینک سفارش را کپی کنید: ' + orderUrl);
    }
}

// Add print styles
document.addEventListener('DOMContentLoaded', function() {
    const printStyles = document.createElement('style');
    printStyles.innerHTML = `
        @media print {
            body * {
                visibility: hidden;
            }
            .order-confirmation-page, .order-confirmation-page * {
                visibility: visible;
            }
            .order-confirmation-page {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    `;
    document.head.appendChild(printStyles);
});
</script>

<?php require_once 'includes/footer.php'; ?>
