<?php
session_start();
require_once 'includes/config.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Get cart items
$userId = getCurrentUserId();
$cartItems = getCartItemsWithDetails($userId);

// Calculate cart totals
$cartTotals = calculateCartTotals($userId);

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $cartId = intval($_POST['cart_id']);
    $quantity = intval($_POST['quantity']);
    
    $result = updateCartItemQuantity($cartId, $quantity, $userId);
    
    if ($result['success']) {
        $success = $result['message'];
        // Refresh cart items
        $cartItems = getCartItemsWithDetails($userId);
        $cartTotals = calculateCartTotals($userId);
    } else {
        $error = $result['message'];
    }
}

// Handle remove item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $cartId = intval($_POST['cart_id']);
    
    $result = deleteCartItem($cartId, $userId);
    
    if ($result['success']) {
        $success = $result['message'];
        // Refresh cart items
        $cartItems = getCartItemsWithDetails($userId);
        $cartTotals = calculateCartTotals($userId);
    } else {
        $error = $result['message'];
    }
}

// Handle clear cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cart'])) {
    $result = clearCart($userId);
    
    if ($result) {
        $success = 'سبد خرید با موفقیت خالی شد.';
        // Refresh cart items
        $cartItems = getCartItemsWithDetails($userId);
        $cartTotals = calculateCartTotals($userId);
    } else {
        $error = 'خطا در خالی کردن سبد خرید.';
    }
}

// Handle apply coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $couponCode = trim($_POST['coupon_code'] ?? '');
    
    if (empty($couponCode)) {
        $error = 'لطفا کد تخفیف را وارد کنید.';
    } else {
        $result = applyCouponToCart($userId, $couponCode);
        
        if ($result['valid']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Handle remove coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_coupon'])) {
    $result = removeCouponFromCart();
    $success = $result['message'];
}

// Get shipping cost
$shippingCost = getShippingCost();
$freeShippingThreshold = getFreeShippingThreshold();

$pageTitle = 'سبد خرید';
$pageDescription = 'سبد خرید شما در فروشگاه آنلاین گل و گیاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-shopping-cart"></i> سبد خرید</h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <span>سبد خرید</span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="cart-page">
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
        
        <div class="cart-container">
            <!-- Cart Items -->
            <main class="cart-main">
                <?php if (empty($cartItems)): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>سبد خرید شما خالی است</h3>
                        <p>هیچ محصولی به سبد خرید خود اضافه نکرده‌اید.</p>
                        <div class="empty-cart-actions">
                            <a href="products.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag"></i>
                                ادامه خرید
                            </a>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-home"></i>
                                بازگشت به خانه
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="cart-items">
                        <div class="cart-header">
                            <div class="cart-header-item">محصول</div>
                            <div class="cart-header-item">قیمت</div>
                            <div class="cart-header-item">تعداد</div>
                            <div class="cart-header-item">جمع</div>
                            <div class="cart-header-item">عملیات</div>
                        </div>
                        
                        <div class="cart-body">
                            <?php foreach ($cartItems as $item): ?>
                                <?php
                                $product = getProductById($item['product_id']);
                                if (!$product) continue;
                                
                                $productImages = getProductImages($item['product_id']);
                                $primaryImage = !empty($productImages) ? $productImages[0]['image_path'] : ($product['image_path'] ?: 'assets/images/no-image.jpg');
                                
                                $maxQuantity = $product['stock'] ?? 99;
                                $isLowStock = $item['quantity'] >= $maxQuantity;
                                
                                // Check if product price has changed
                                $currentPrice = $product['price'];
                                $originalPrice = $item['product_price'];
                                $priceChanged = $currentPrice != $originalPrice;
                                
                                // Calculate item total
                                $itemTotal = $currentPrice * $item['quantity'];
                                
                                // Check if product is still available
                                $isAvailable = $product['is_active'] && $product['stock'] > 0;
                                
                                // Check if product stock has changed
                                $stockChanged = $item['quantity'] > $product['stock'];
                                if ($stockChanged) {
                                    $item['quantity'] = $product['stock'];
                                    // Update cart item
                                    updateCartItem($item['id'], $product['stock'], $userId);
                                }
                                ?>
                                
                                <div class="cart-item" data-cart-id="<?php echo $item['id']; ?>">
                                    <div class="cart-item-product">
                                        <a href="product.php?id=<?php echo $product['id']; ?>">
                                            <div class="cart-item-image">
                                                <img src="<?php echo $primaryImage; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                            </div>
                                            <div class="cart-item-info">
                                                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                                <?php if ($product['sku']): ?>
                                                    <p class="cart-item-sku">کد: <?php echo htmlspecialchars($product['sku']); ?></p>
                                                <?php endif; ?>
                                                <?php if (!$isAvailable): ?>
                                                    <span class="stock-badge out-of-stock">ناموجود</span>
                                                <?php endif; ?>
                                                <?php if ($priceChanged): ?>
                                                    <span class="price-changed-badge">قیمت به‌روزرسانی شد</span>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </div>
                                    
                                    <div class="cart-item-price">
                                        <?php if ($priceChanged): ?>
                                            <span class="original-price"><?php echo formatPrice($originalPrice); ?></span>
                                        <?php endif; ?>
                                        <span class="current-price"><?php echo formatPrice($currentPrice); ?></span>
                                    </div>
                                    
                                    <div class="cart-item-quantity">
                                        <form method="POST" action="cart.php" class="quantity-form">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <div class="quantity-input">
                                                <button type="button" class="qty-btn minus" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo max(1, $item['quantity'] - 1); ?>)">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" name="quantity" 
                                                       value="<?php echo toPersianNumbers($item['quantity']); ?>" 
                                                       min="1" 
                                                       max="<?php echo toPersianNumbers($maxQuantity); ?>"
                                                       onchange="this.form.submit()">
                                                <button type="button" class="qty-btn plus" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo min($maxQuantity, $item['quantity'] + 1); ?>)">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <button type="submit" name="update_quantity" class="update-qty-btn" style="display: none;">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </form>
                                        <?php if ($isLowStock): ?>
                                            <p class="low-stock-message">حداکثر موجودی</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="cart-item-total">
                                        <?php echo formatPrice($itemTotal); ?>
                                    </div>
                                    
                                    <div class="cart-item-actions">
                                        <form method="POST" action="cart.php">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="remove_item" class="remove-btn" onclick="return confirm('آیا از حذف این محصول مطمئن هستید؟')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <button class="save-for-later-btn" onclick="saveForLater(<?php echo $item['id']; ?>, <?php echo $product['id']; ?>)">
                                            <i class="fas fa-heart"></i>
                                            ذخیره برای بعد
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Cart Footer -->
                    <div class="cart-footer">
                        <div class="cart-footer-left">
                            <form method="POST" action="cart.php">
                                <button type="submit" name="clear_cart" class="btn btn-secondary" onclick="return confirm('آیا از خالی کردن سبد خرید مطمئن هستید؟')">
                                    <i class="fas fa-trash"></i>
                                    خالی کردن سبد خرید
                                </button>
                            </form>
                        </div>
                        <div class="cart-footer-right">
                            <a href="products.php" class="btn btn-outline-primary">
                                <i class="fas fa-shopping-bag"></i>
                                ادامه خرید
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
            
            <!-- Cart Summary -->
            <aside class="cart-sidebar">
                <div class="cart-summary">
                    <h3><i class="fas fa-file-invoice"></i> خلاصه سبد خرید</h3>
                    
                    <div class="summary-row">
                        <span>جمع سبد خرید</span>
                        <span><?php echo formatPrice($cartTotals['subtotal']); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>هزینه ارسال</span>
                        <span>
                            <?php if ($cartTotals['subtotal'] >= $freeShippingThreshold): ?>
                                رایگان
                            <?php else: ?>
                                <?php echo formatPrice($shippingCost); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <!-- Coupon -->
                    <div class="coupon-section">
                        <form method="POST" action="cart.php" class="coupon-form">
                            <div class="coupon-input">
                                <input type="text" name="coupon_code" 
                                       placeholder="کد تخفیف را وارد کنید" 
                                       value="<?php echo htmlspecialchars($_POST['coupon_code'] ?? ''); ?>">
                                <button type="submit" name="apply_coupon" class="btn btn-primary btn-sm">
                                    <i class="fas fa-check"></i>
                                    اعمال
                                </button>
                            </div>
                        </form>
                        
                        <?php if (isset($_SESSION['coupon_code'])): ?>
                            <div class="applied-coupon">
                                <span>
                                    <i class="fas fa-tag"></i>
                                    کد تخفیف: <?php echo htmlspecialchars($_SESSION['coupon_code']); ?>
                                </span>
                                <form method="POST" action="cart.php" style="display: inline;">
                                    <button type="submit" name="remove_coupon" class="remove-coupon-btn">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php
                    $discountAmount = isset($_SESSION['coupon_discount']) ? $_SESSION['coupon_discount'] : 0;
                    $finalTotal = $cartTotals['total'] - $discountAmount;
                    
                    if ($discountAmount > 0):
                    ?>
                        <div class="summary-row discount-row">
                            <span>تخفیف</span>
                            <span class="discount-amount">-<?php echo formatPrice($discountAmount); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row total-row">
                        <span>مبلغ قابل پرداخت</span>
                        <span class="total-amount"><?php echo formatPrice($finalTotal); ?></span>
                    </div>
                    
                    <div class="checkout-actions">
                        <?php if (!empty($cartItems)): ?>
                            <a href="checkout.php" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-checkout"></i>
                                تسویه حساب
                            </a>
                        <?php endif; ?>
                        
                        <p class="checkout-note">
                            <i class="fas fa-info-circle"></i>
                            هزینه ارسال بر اساس آدرس تحویل محاسبه می‌شود
                        </p>
                    </div>
                </div>
                
                <!-- Guarantees -->
                <div class="cart-guarantees">
                    <h4><i class="fas fa-shield-alt"></i> تضمین‌ها</h4>
                    <ul>
                        <li>
                            <i class="fas fa-undo"></i>
                            <span>گارانتی بازگشت وجه</span>
                        </li>
                        <li>
                            <i class="fas fa-shipping-fast"></i>
                            <span>ارسال سریع</span>
                        </li>
                        <li>
                            <i class="fas fa-leaf"></i>
                            <span>گل‌های تازه</span>
                        </li>
                    </ul>
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
// Update quantity function
function updateQuantity(cartId, quantity) {
    const form = document.querySelector(`#cart-item-${cartId} .quantity-form`);
    const qtyInput = form.querySelector('input[name="quantity"]');
    qtyInput.value = quantity;
    form.submit();
}

// Save for later function
function saveForLater(cartId, productId) {
    if (confirm('آیا می‌خواهید این محصول را برای بعد ذخیره کنید؟')) {
        fetch('includes/cart.php?action=save_for_later&cart_id=' + cartId + '&product_id=' + productId, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }
}

// Real-time cart updates
function initCartUpdates() {
    // Add event listeners for quantity changes
    document.querySelectorAll('.quantity-input input').forEach(input => {
        input.addEventListener('change', function() {
            const form = this.closest('form');
            const cartId = this.closest('.cart-item').dataset.cartId;
            const quantity = parseInt(this.value);
            const maxQuantity = parseInt(this.max);
            
            if (quantity > maxQuantity) {
                this.value = maxQuantity;
                alert(`حداکثر موجودی ${toPersianNumbers(maxQuantity)} عدد است.`);
            }
            
            // Submit form if quantity changed
            if (this.defaultValue !== this.value) {
                form.submit();
            }
        });
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', initCartUpdates);
</script>

<?php require_once 'includes/footer.php'; ?>
