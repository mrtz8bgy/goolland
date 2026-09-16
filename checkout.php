<?php
session_start();
require_once "includes/db.php";';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

// Redirect to cart if cart is empty
$userId = getCurrentUserId();
$cartItems = getCartItemsWithDetails($userId);

if (empty($cartItems)) {
    header("Location: cart.php");
    exit;
}

$error = '';
$success = '';

// Get user info
$user = getUserById($userId);

// Get user addresses
$addresses = getUserAddressesWithDetails($userId);
$defaultAddress = getDefaultUserAddress($userId);

// Get provinces
$provinces = getAllProvinces();

// Get shipping methods
$shippingMethods = getAllShippingMethods();

// Get payment methods
$paymentMethods = getAllPaymentMethods();

// Calculate cart totals
$cartTotals = calculateCartTotals($userId);

// Get shipping cost
$shippingCost = getShippingCost();
$freeShippingThreshold = getFreeShippingThreshold();

// Apply free shipping if threshold met
if ($cartTotals['subtotal'] >= $freeShippingThreshold) {
    $shippingCost = 0;
}

// Get coupon discount
$discountAmount = isset($_SESSION['coupon_discount']) ? $_SESSION['coupon_discount'] : 0;
$finalTotal = $cartTotals['subtotal'] + $shippingCost - $discountAmount;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Validate required fields
    $requiredFields = ['first_name', 'last_name', 'phone', 'province_id', 'city_id', 'address', 'payment_method'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $missingFields[] = getFieldLabel($field);
        }
    }
    
    if (!empty($missingFields)) {
        $error = 'لطفا فیلدهای زیر را وارد کنید: ' . implode('، ', $missingFields);
    } else {
        // Prepare order data
        $orderData = [
            'user_id' => $userId,
            'total_amount' => $cartTotals['subtotal'],
            'shipping_cost' => $shippingCost,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalTotal,
            'payment_method' => $_POST['payment_method'],
            'payment_status' => $_POST['payment_method'] === 'cash' ? 'pending' : 'unpaid',
            'status' => 'pending',
            'shipping_address' => json_encode([
                'first_name' => trim($_POST['first_name']),
                'last_name' => trim($_POST['last_name']),
                'phone' => trim($_POST['phone']),
                'province_id' => intval($_POST['province_id']),
                'city_id' => intval($_POST['city_id']),
                'province_name' => getProvinceById(intval($_POST['province_id']))['name'] ?? '',
                'city_name' => getCityById(intval($_POST['city_id']))['name'] ?? '',
                'address' => trim($_POST['address']),
                'postal_code' => trim($_POST['postal_code'] ?? ''),
                'notes' => trim($_POST['notes'] ?? '')
            ], JSON_UNESCAPED_UNICODE),
            'billing_address' => json_encode([
                'first_name' => trim($_POST['billing_first_name'] ?? $_POST['first_name']),
                'last_name' => trim($_POST['billing_last_name'] ?? $_POST['last_name']),
                'phone' => trim($_POST['billing_phone'] ?? $_POST['phone']),
                'province_id' => intval($_POST['billing_province_id'] ?? $_POST['province_id']),
                'city_id' => intval($_POST['billing_city_id'] ?? $_POST['city_id']),
                'province_name' => getProvinceById(intval($_POST['billing_province_id'] ?? $_POST['province_id']))['name'] ?? '',
                'city_name' => getCityById(intval($_POST['billing_city_id'] ?? $_POST['city_id']))['name'] ?? '',
                'address' => trim($_POST['billing_address'] ?? $_POST['address']),
                'postal_code' => trim($_POST['billing_postal_code'] ?? $_POST['postal_code'] ?? '')
            ], JSON_UNESCAPED_UNICODE),
            'notes' => trim($_POST['notes'] ?? '')
        ];
        
        // Prepare order items
        $orderItems = [];
        foreach ($cartItems as $item) {
            $product = getProductById($item['product_id']);
            if ($product) {
                $orderItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product['price'],
                    'total_price' => $product['price'] * $item['quantity']
                ];
            }
        }
        
        $orderData['items'] = $orderItems;
        
        // Process order
        $result = processCheckout($userId, $orderData);
        
        if ($result['success']) {
            // Clear coupon from session
            unset($_SESSION['coupon_code']);
            unset($_SESSION['coupon_discount']);
            unset($_SESSION['coupon_id']);
            
            // Log activity
            logActivity($userId, 'checkout', 'Order placed with ID: ' . $result['order_id']);
            
            // Redirect to order confirmation
            header("Location: order-confirmation.php?order_id={$result['order_id']}");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Get cities based on selected province
$selectedProvinceId = intval($_POST['province_id'] ?? ($defaultAddress['province_id'] ?? ($provinces[0]['id'] ?? 0)));
$cities = getCitiesByProvince($selectedProvinceId);

// Helper function to get field labels
function getFieldLabel($field) {
    $labels = [
        'first_name' => 'نام',
        'last_name' => 'نام خانوادگی',
        'phone' => 'شماره تلفن',
        'province_id' => 'استان',
        'city_id' => 'شهر',
        'address' => 'آدرس',
        'payment_method' => 'روش پرداخت'
    ];
    return $labels[$field] ?? $field;
}

$pageTitle = 'تسویه حساب';
$pageDescription = 'تسویه حساب در فروشگاه آنلاین گل و گیاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-credit-card"></i> تسویه حساب</h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <a href="cart.php">سبد خرید</a>
            <i class="fas fa-chevron-left"></i>
            <span>تسویه حساب</span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="checkout-page">
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
        
        <div class="checkout-container">
            <!-- Checkout Steps -->
            <div class="checkout-steps">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div class="step-label">اطلاعات ارسال</div>
                </div>
                <div class="step-arrow"><i class="fas fa-chevron-left"></i></div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-label">روش پرداخت</div>
                </div>
                <div class="step-arrow"><i class="fas fa-chevron-left"></i></div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-label">تایید سفارش</div>
                </div>
            </div>
            
            <form method="POST" action="checkout.php" class="checkout-form">
                <div class="checkout-form-container">
                    <!-- Shipping Information -->
                    <div class="checkout-section">
                        <h3><i class="fas fa-truck"></i> اطلاعات ارسال</h3>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="first_name">نام <span style="color: #f44336;">*</span></label>
                                    <input type="text" id="first_name" name="first_name" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ($defaultAddress['first_name'] ?? ($user['name'] ?? ''))); ?>" 
                                           placeholder="نام خود را وارد کنید" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="last_name">نام خانوادگی <span style="color: #f44336;">*</span></label>
                                    <input type="text" id="last_name" name="last_name" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ($defaultAddress['last_name'] ?? '')); ?>" 
                                           placeholder="نام خانوادگی خود را وارد کنید" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="phone">شماره تلفن <span style="color: #f44336;">*</span></label>
                                    <input type="tel" id="phone" name="phone" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ($defaultAddress['phone'] ?? ($user['phone'] ?? ''))); ?>" 
                                           placeholder="شماره تلفن خود را وارد کنید" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="postal_code">کد پستی</label>
                                    <input type="text" id="postal_code" name="postal_code" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ($defaultAddress['postal_code'] ?? '')); ?>" 
                                           placeholder="کد پستی خود را وارد کنید">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="province_id">استان <span style="color: #f44336;">*</span></label>
                                    <select id="province_id" name="province_id" class="form-control select-control" required onchange="loadCities(this.value)">
                                        <option value="">استان را انتخاب کنید</option>
                                        <?php foreach ($provinces as $province): ?>
                                            <option value="<?php echo $province['id']; ?>" 
                                                    <?php echo ($selectedProvinceId == $province['id']) ? 'selected' : ''; ?>
                                                    <?php echo ($defaultAddress['province_id'] == $province['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($province['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="city_id">شهر <span style="color: #f44336;">*</span></label>
                                    <select id="city_id" name="city_id" class="form-control select-control" required>
                                        <option value="">شهر را انتخاب کنید</option>
                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?php echo $city['id']; ?>" 
                                                    <?php echo ($defaultAddress['city_id'] == $city['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($city['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">آدرس کامل <span style="color: #f44336;">*</span></label>
                            <textarea id="address" name="address" class="form-control" rows="3" 
                                      placeholder="آدرس کامل خود را وارد کنید" required><?php echo htmlspecialchars($_POST['address'] ?? ($defaultAddress['address'] ?? '')); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">یادداشت‌های سفارش (اختیاری)</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3" 
                                      placeholder="یادداشت‌ها یا درخواست‌های خاص خود را وارد کنید"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Use default address -->
                        <?php if ($defaultAddress): ?>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="use_default_address" 
                                           onclick="fillDefaultAddress(this)">
                                    <span>استفاده از آدرس پیش‌فرض</span>
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Billing Information -->
                    <div class="checkout-section">
                        <h3><i class="fas fa-file-invoice"></i> اطلاعات صورت‌حساب</h3>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="same_as_shipping" 
                                       name="same_as_shipping" 
                                       checked 
                                       onclick="toggleBillingAddress(this)">
                                <span>آدرس صورت‌حساب همان آدرس ارسال است</span>
                            </label>
                        </div>
                        
                        <div id="billing_address_section" style="display: none;">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="billing_first_name">نام</label>
                                        <input type="text" id="billing_first_name" name="billing_first_name" 
                                               class="form-control" 
                                               placeholder="نام خود را وارد کنید">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="billing_last_name">نام خانوادگی</label>
                                        <input type="text" id="billing_last_name" name="billing_last_name" 
                                               class="form-control" 
                                               placeholder="نام خانوادگی خود را وارد کنید">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="billing_phone">شماره تلفن</label>
                                        <input type="tel" id="billing_phone" name="billing_phone" 
                                               class="form-control" 
                                               placeholder="شماره تلفن خود را وارد کنید">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="billing_postal_code">کد پستی</label>
                                        <input type="text" id="billing_postal_code" name="billing_postal_code" 
                                               class="form-control" 
                                               placeholder="کد پستی خود را وارد کنید">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="billing_province_id">استان</label>
                                        <select id="billing_province_id" name="billing_province_id" class="form-control select-control" onchange="loadBillingCities(this.value)">
                                            <option value="">استان را انتخاب کنید</option>
                                            <?php foreach ($provinces as $province): ?>
                                                <option value="<?php echo $province['id']; ?>">
                                                    <?php echo htmlspecialchars($province['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="billing_city_id">شهر</label>
                                        <select id="billing_city_id" name="billing_city_id" class="form-control select-control">
                                            <option value="">شهر را انتخاب کنید</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="billing_address">آدرس کامل</label>
                                <textarea id="billing_address" name="billing_address" class="form-control" rows="3" 
                                          placeholder="آدرس کامل خود را وارد کنید"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping Method -->
                    <div class="checkout-section">
                        <h3><i class="fas fa-shipping-fast"></i> روش ارسال</h3>
                        
                        <?php if (!empty($shippingMethods)): ?>
                            <div class="shipping-methods">
                                <?php foreach ($shippingMethods as $method): ?>
                                    <div class="shipping-method">
                                        <label>
                                            <input type="radio" name="shipping_method" 
                                                   value="<?php echo $method['id']; ?>" 
                                                   <?php echo $method['is_default'] ? 'checked' : ''; ?>
                                                   onchange="updateShippingCost(<?php echo $method['price']; ?>)">
                                            <div class="shipping-method-info">
                                                <h4><?php echo htmlspecialchars($method['name']); ?></h4>
                                                <p><?php echo htmlspecialchars($method['description']); ?></p>
                                                <span class="shipping-price">
                                                    <?php echo $method['price'] > 0 ? formatPrice($method['price']) : 'رایگان'; ?>
                                                </span>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="shipping-method">
                                <p>روش ارسال پیش‌فرض: <?php echo formatPrice($shippingCost); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="checkout-section">
                        <h3><i class="fas fa-credit-card"></i> روش پرداخت</h3>
                        
                        <?php if (!empty($paymentMethods)): ?>
                            <div class="payment-methods">
                                <?php foreach ($paymentMethods as $method): ?>
                                    <div class="payment-method">
                                        <label>
                                            <input type="radio" name="payment_method" 
                                                   value="<?php echo $method['code']; ?>" 
                                                   <?php echo $method['is_default'] ? 'checked' : ''; ?>
                                                   required>
                                            <div class="payment-method-info">
                                                <div class="payment-icon">
                                                    <i class="fas fa-<?php echo $method['icon'] ?? 'credit-card'; ?>"></i>
                                                </div>
                                                <h4><?php echo htmlspecialchars($method['name']); ?></h4>
                                                <p><?php echo htmlspecialchars($method['description']); ?></p>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="payment-methods">
                                <div class="payment-method">
                                    <label>
                                        <input type="radio" name="payment_method" value="cash" checked required>
                                        <div class="payment-method-info">
                                            <div class="payment-icon">
                                                <i class="fas fa-money-bill"></i>
                                            </div>
                                            <h4>پرداخت در محل</h4>
                                            <p>پرداخت وجه سفارش در زمان تحویل</p>
                                        </div>
                                    </label>
                                </div>
                                <div class="payment-method">
                                    <label>
                                        <input type="radio" name="payment_method" value="online">
                                        <div class="payment-method-info">
                                            <div class="payment-icon">
                                                <i class="fas fa-credit-card"></i>
                                            </div>
                                            <h4>پرداخت آنلاین</h4>
                                            <p>پرداخت با کارت‌های بانکی عضو شتاب</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <aside class="checkout-sidebar">
                    <div class="order-summary">
                        <h3><i class="fas fa-file-invoice"></i> خلاصه سفارش</h3>
                        
                        <!-- Cart Items -->
                        <div class="order-items">
                            <h4>محصولات سبد خرید</h4>
                            <ul>
                                <?php foreach ($cartItems as $item): ?>
                                    <?php
                                    $product = getProductById($item['product_id']);
                                    if (!$product) continue;
                                    
                                    $itemTotal = $product['price'] * $item['quantity'];
                                    ?>
                                    <li>
                                        <div class="order-item-image">
                                            <img src="<?php echo $product['image_path'] ?: 'assets/images/no-image.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        </div>
                                        <div class="order-item-info">
                                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                                            <span class="order-item-quantity">x <?php echo toPersianNumbers($item['quantity']); ?></span>
                                            <span class="order-item-price"><?php echo formatPrice($itemTotal); ?></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <!-- Summary Totals -->
                        <div class="summary-totals">
                            <div class="summary-row">
                                <span>جمع سبد خرید</span>
                                <span><?php echo formatPrice($cartTotals['subtotal']); ?></span>
                            </div>
                            
                            <div class="summary-row">
                                <span>هزینه ارسال</span>
                                <span id="shipping_cost_display">
                                    <?php echo $shippingCost > 0 ? formatPrice($shippingCost) : 'رایگان'; ?>
                                </span>
                            </div>
                            
                            <?php if ($discountAmount > 0): ?>
                                <div class="summary-row">
                                    <span>تخفیف</span>
                                    <span class="discount-amount">-<?php echo formatPrice($discountAmount); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="summary-row total-row">
                                <span>مبلغ قابل پرداخت</span>
                                <span id="final_total_display" class="total-amount"><?php echo formatPrice($finalTotal); ?></span>
                            </div>
                        </div>
                        
                        <!-- Place Order Button -->
                        <div class="place-order">
                            <button type="submit" name="place_order" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-check"></i>
                                ثبت سفارش
                            </button>
                            
                            <p class="checkout-note">
                                با کلیک بر روی "ثبت سفارش" موافقت خود را با 
                                <a href="page.php?slug=terms" target="_blank">قوانین و مقررات</a> 
                                اعلام می‌کنید.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Guarantees -->
                    <div class="checkout-guarantees">
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
            </form>
        </div>
    </div>
</div>

<script>
// Load cities based on province
function loadCities(provinceId) {
    fetch('includes/locations.php?action=get_cities&province_id=' + provinceId)
    .then(response => response.json())
    .then(data => {
        const citySelect = document.getElementById('city_id');
        citySelect.innerHTML = '<option value="">شهر را انتخاب کنید</option>';
        
        data.cities.forEach(city => {
            const option = document.createElement('option');
            option.value = city.id;
            option.textContent = city.name;
            citySelect.appendChild(option);
        });
    });
}

// Load billing cities based on province
function loadBillingCities(provinceId) {
    fetch('includes/locations.php?action=get_cities&province_id=' + provinceId)
    .then(response => response.json())
    .then(data => {
        const citySelect = document.getElementById('billing_city_id');
        citySelect.innerHTML = '<option value="">شهر را انتخاب کنید</option>';
        
        data.cities.forEach(city => {
            const option = document.createElement('option');
            option.value = city.id;
            option.textContent = city.name;
            citySelect.appendChild(option);
        });
    });
}

// Update shipping cost
function updateShippingCost(price) {
    const shippingCostEl = document.getElementById('shipping_cost_display');
    const finalTotalEl = document.getElementById('final_total_display');
    
    const currentTotal = <?php echo $cartTotals['subtotal'] + $cartTotals['shipping']; ?>;
    const discount = <?php echo $discountAmount; ?>;
    const newTotal = currentTotal + price - <?php echo $cartTotals['shipping']; ?> - discount;
    
    shippingCostEl.textContent = price > 0 ? formatPrice(price) : 'رایگان';
    finalTotalEl.textContent = formatPrice(newTotal);
}

// Toggle billing address
function toggleBillingAddress(checkbox) {
    const billingSection = document.getElementById('billing_address_section');
    
    if (checkbox.checked) {
        billingSection.style.display = 'none';
    } else {
        billingSection.style.display = 'block';
    }
}

// Fill default address
function fillDefaultAddress(checkbox) {
    if (checkbox.checked) {
        const defaultAddress = <?php echo json_encode($defaultAddress); ?>;
        
        if (defaultAddress) {
            document.getElementById('first_name').value = defaultAddress.first_name || '';
            document.getElementById('last_name').value = defaultAddress.last_name || '';
            document.getElementById('phone').value = defaultAddress.phone || '';
            document.getElementById('postal_code').value = defaultAddress.postal_code || '';
            
            if (defaultAddress.province_id) {
                document.getElementById('province_id').value = defaultAddress.province_id;
                loadCities(defaultAddress.province_id);
                
                setTimeout(() => {
                    if (defaultAddress.city_id) {
                        document.getElementById('city_id').value = defaultAddress.city_id;
                    }
                }, 500);
            }
            
            document.getElementById('address').value = defaultAddress.address || '';
        }
    }
}

// Format price function
function formatPrice(price) {
    return new Intl.NumberFormat('fa-IR').format(price) + ' تومان';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize select controls
    if (typeof NiceSelect !== 'undefined') {
        $('select.select-control').niceSelect();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
