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

// Get user ID
$userId = getCurrentUserId();

// Get user info
$user = getUserById($userId);

// Get user addresses
$addresses = getUserAddressesWithDetails($userId);

// Get user orders
$orders = getUserOrdersWithDetails($userId, 5);

// Get provinces
$provinces = getAllProvinces();

// Get user reviews
$reviews = [];
try {
    $stmt = $pdo->prepare("SELECT r.*, p.name as product_name FROM reviews r LEFT JOIN products p ON r.product_id = p.id WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reviews = [];
}

// Get wishlist count
$wishlistCount = getWishlistCount($userId);

// Handle update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $profileData = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? '')
    ];
    
    // Validate email
    if (!filter_var($profileData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'لطفا آدرس ایمیل معتبر وارد کنید.';
    } else {
        // Check if email already exists for another user
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$profileData['email'], $userId]);
            if ($stmt->fetchColumn()) {
                $error = 'ایمیل وارد شده قبلا توسط کاربر دیگری استفاده شده است.';
            } else {
                $result = updateUserProfileData($userId, $profileData);
                
                if ($result['success']) {
                    $success = $result['message'];
                    // Update session
                    $_SESSION['name'] = $profileData['name'];
                    $_SESSION['email'] = $profileData['email'];
                    $_SESSION['phone'] = $profileData['phone'];
                    
                    // Refresh user info
                    $user = getUserById($userId);
                } else {
                    $error = $result['message'];
                }
            }
        } catch (PDOException $e) {
            $error = 'خطا در به‌روزرسانی پروفایل: ' . $e->getMessage();
        }
    }
}

// Handle update password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword)) {
        $error = 'لطفا رمز عبور فعلی را وارد کنید.';
    } elseif (empty($newPassword)) {
        $error = 'لطفا رمز عبور جدید را وارد کنید.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'رمز عبور جدید باید حداقل 6 کاراکتر باشد.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'رمز عبور جدید و تکرار آن یکسان نیست.';
    } else {
        $result = updateUserPassword($userId, $currentPassword, $newPassword);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Handle add address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $addressData = [
        'user_id' => $userId,
        'title' => trim($_POST['address_title'] ?? ''),
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'phone' => trim($_POST['address_phone'] ?? ''),
        'province_id' => intval($_POST['province_id'] ?? 0),
        'city_id' => intval($_POST['city_id'] ?? 0),
        'address' => trim($_POST['address'] ?? ''),
        'postal_code' => trim($_POST['postal_code'] ?? ''),
        'is_default' => isset($_POST['is_default']) ? 1 : 0
    ];
    
    $result = createUserAddress($userId, $addressData);
    
    if ($result['success']) {
        $success = $result['message'];
        // Refresh addresses
        $addresses = getUserAddressesWithDetails($userId);
    } else {
        $error = $result['message'];
    }
}

// Handle update address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address'])) {
    $addressId = intval($_POST['address_id']);
    $addressData = [
        'title' => trim($_POST['address_title'] ?? ''),
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'phone' => trim($_POST['address_phone'] ?? ''),
        'province_id' => intval($_POST['province_id'] ?? 0),
        'city_id' => intval($_POST['city_id'] ?? 0),
        'address' => trim($_POST['address'] ?? ''),
        'postal_code' => trim($_POST['postal_code'] ?? '')
    ];
    
    $result = updateUserAddress($addressId, $userId, $addressData);
    
    if ($result['success']) {
        $success = $result['message'];
        // Refresh addresses
        $addresses = getUserAddressesWithDetails($userId);
    } else {
        $error = $result['message'];
    }
}

// Handle delete address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_address'])) {
    $addressId = intval($_POST['address_id']);
    
    $result = deleteUserAddress($addressId, $userId);
    
    if ($result['success']) {
        $success = $result['message'];
        // Refresh addresses
        $addresses = getUserAddressesWithDetails($userId);
    } else {
        $error = $result['message'];
    }
}

// Handle set default address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_default_address'])) {
    $addressId = intval($_POST['address_id']);
    
    $result = setDefaultUserAddress($addressId, $userId);
    
    if ($result['success']) {
        $success = $result['message'];
        // Refresh addresses
        $addresses = getUserAddressesWithDetails($userId);
    } else {
        $error = $result['message'];
    }
}

// Get cities based on selected province
$selectedProvinceId = intval($_POST['province_id'] ?? 0);
$cities = getCitiesByProvince($selectedProvinceId);

// Get default address
$defaultAddress = getDefaultUserAddress($userId);

$pageTitle = 'حساب کاربری';
$pageDescription = 'حساب کاربری در فروشگاه آنلاین گل و گیاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-user"></i> حساب کاربری</h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <span>حساب کاربری</span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="profile-page">
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
        
        <div class="profile-container">
            <!-- Profile Sidebar -->
            <aside class="profile-sidebar">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <?php if ($user['image_path']): ?>
                            <img src="<?php echo $user['image_path']; ?>" alt="<?php echo htmlspecialchars($user['name']); ?>">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                
                <nav class="profile-nav">
                    <ul>
                        <li class="active">
                            <a href="#profile" onclick="showTab('profile')">
                                <i class="fas fa-user"></i>
                                پروفایل
                            </a>
                        </li>
                        <li>
                            <a href="#orders" onclick="showTab('orders')">
                                <i class="fas fa-shopping-bag"></i>
                                سفارشات
                                <span class="nav-count"><?php echo toPersianNumbers(count($orders)); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="#addresses" onclick="showTab('addresses')">
                                <i class="fas fa-map-marker-alt"></i>
                                آدرس‌ها
                                <span class="nav-count"><?php echo toPersianNumbers(count($addresses)); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="#reviews" onclick="showTab('reviews')">
                                <i class="fas fa-star"></i>
                                نظرات
                                <span class="nav-count"><?php echo toPersianNumbers(count($reviews)); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="wishlist.php">
                                <i class="fas fa-heart"></i>
                                علاقه‌مندی‌ها
                                <span class="nav-count"><?php echo toPersianNumbers($wishlistCount); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="#password" onclick="showTab('password')">
                                <i class="fas fa-lock"></i>
                                تغییر رمز عبور
                            </a>
                        </li>
                        <li>
                            <a href="logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                خروج
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>
            
            <!-- Profile Content -->
            <main class="profile-main">
                <!-- Profile Tab -->
                <div class="profile-tab active" id="profile-tab">
                    <div class="tab-header">
                        <h2><i class="fas fa-user"></i> پروفایل</h2>
                        <p>اطلاعات حساب کاربری خود را مدیریت کنید</p>
                    </div>
                    
                    <form method="POST" action="profile.php" class="profile-form">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="name">نام و نام خانوادگی</label>
                                    <input type="text" id="name" name="name" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" 
                                           placeholder="نام و نام خانوادگی خود را وارد کنید" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="email">آدرس ایمیل</label>
                                    <input type="email" id="email" name="email" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                                           placeholder="آدرس ایمیل خود را وارد کنید" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="phone">شماره تلفن</label>
                                    <input type="tel" id="phone" name="phone" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                           placeholder="شماره تلفن خود را وارد کنید">
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="address">آدرس</label>
                                    <input type="text" id="address" name="address" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" 
                                           placeholder="آدرس خود را وارد کنید">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                ذخیره تغییرات
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Orders Tab -->
                <div class="profile-tab" id="orders-tab">
                    <div class="tab-header">
                        <h2><i class="fas fa-shopping-bag"></i> سفارشات من</h2>
                        <a href="orders.php" class="view-all">مشاهده همه سفارشات</a>
                    </div>
                    
                    <?php if (empty($orders)): ?>
                        <div class="empty-section">
                            <i class="fas fa-shopping-bag"></i>
                            <h3>هیچ سفارشی یافت نشد</h3>
                            <p>شما هنوز هیچ سفارشی نداده‌اید.</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag"></i>
                                خرید کنید
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                                <?php
                                // Get order items count
                                $orderItemsCount = count($order['items']);
                                
                                // Get first product image
                                $firstProductImage = 'assets/images/no-image.jpg';
                                if (!empty($order['items'])) {
                                    $firstProduct = getProductById($order['items'][0]['product_id']);
                                    if ($firstProduct && $firstProduct['image_path']) {
                                        $firstProductImage = $firstProduct['image_path'];
                                    }
                                }
                                
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
                                $statusClass = $order['status'];
                                
                                // Calculate order total
                                $orderTotal = 0;
                                foreach ($order['items'] as $item) {
                                    $orderTotal += $item['total_price'];
                                }
                                $orderTotal += $order['shipping_cost'] - $order['discount_amount'];
                                
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
                                ?>
                                
                                <div class="order-card">
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
                                            <div class="order-product-preview">
                                                <img src="<?php echo $firstProductImage; ?>" alt="پیش‌نمایش سفارش">
                                                <?php if ($orderItemsCount > 1): ?>
                                                    <span class="more-products">+<?php echo toPersianNumbers($orderItemsCount - 1); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="order-product-count">
                                                <span><?php echo toPersianNumbers($orderItemsCount); ?> محصول</span>
                                            </div>
                                        </div>
                                        
                                        <div class="order-total">
                                            <span><?php echo formatPrice($orderTotal); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="order-footer">
                                        <div class="order-status">
                                            <span>وضعیت:</span>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </div>
                                        <div class="order-payment-status">
                                            <span>پرداخت:</span>
                                            <span class="payment-status <?php echo $order['payment_status'] === 'paid' ? 'paid' : 'pending'; ?>">
                                                <?php echo $paymentStatusText; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="order-actions">
                                            <a href="order-tracking.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-map-marker-alt"></i>
                                                پیگیری
                                            </a>
                                            <a href="order-confirmation.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-file-alt"></i>
                                                جزئیات
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Addresses Tab -->
                <div class="profile-tab" id="addresses-tab">
                    <div class="tab-header">
                        <h2><i class="fas fa-map-marker-alt"></i> آدرس‌ها</h2>
                        <button class="btn btn-primary btn-sm" onclick="showAddAddressModal()">
                            <i class="fas fa-plus"></i>
                            افزودن آدرس جدید
                        </button>
                    </div>
                    
                    <?php if (empty($addresses)): ?>
                        <div class="empty-section">
                            <i class="fas fa-map-marker-alt"></i>
                            <h3>هیچ آدرسی یافت نشد</h3>
                            <p>شما هنوز هیچ آدرسی ثبت نکرده‌اید.</p>
                            <button class="btn btn-primary" onclick="showAddAddressModal()">
                                <i class="fas fa-plus"></i>
                                افزودن آدرس
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="addresses-list">
                            <?php foreach ($addresses as $address): ?>
                                <div class="address-card" data-address-id="<?php echo $address['id']; ?>">
                                    <div class="address-header">
                                        <h4><?php echo htmlspecialchars($address['title'] ?? 'آدرس ' . toPersianNumbers($address['id'])); ?></h4>
                                        <?php if ($address['is_default']): ?>
                                            <span class="default-badge">پیش‌فرض</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="address-body">
                                        <div class="address-info">
                                            <p>
                                                <?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?>
                                                <br>
                                                <?php echo htmlspecialchars($address['phone']); ?>
                                                <br>
                                                <?php echo htmlspecialchars($address['province_name'] ?? ''); ?>
                                                <?php echo htmlspecialchars($address['city_name'] ?? ''); ?>
                                                <br>
                                                <?php echo htmlspecialchars($address['address']); ?>
                                                <?php if (!empty($address['postal_code'])): ?>
                                                    <br>
                                                    کد پستی: <?php echo htmlspecialchars($address['postal_code']); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="address-footer">
                                        <div class="address-actions">
                                            <form method="POST" action="profile.php" style="display: inline;">
                                                <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                                <button type="submit" name="set_default_address" 
                                                        class="btn btn-sm btn-secondary" 
                                                        title="تنظیم به عنوان آدرس پیش‌فرض">
                                                    <i class="fas fa-home"></i>
                                                </button>
                                            </form>
                                            <button class="btn btn-sm btn-secondary" 
                                                    onclick="showEditAddressModal(<?php echo $address['id']; ?>)" 
                                                    title="ویرایش آدرس">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" action="profile.php" style="display: inline;">
                                                <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                                <button type="submit" name="delete_address" 
                                                        class="btn btn-sm btn-danger" 
                                                        title="حذف آدرس"
                                                        onclick="return confirm('آیا از حذف این آدرس مطمئن هستید؟')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Reviews Tab -->
                <div class="profile-tab" id="reviews-tab">
                    <div class="tab-header">
                        <h2><i class="fas fa-star"></i> نظرات من</h2>
                        <p>نظرات شما درباره محصولاتی که خریداری کرده‌اید</p>
                    </div>
                    
                    <?php if (empty($reviews)): ?>
                        <div class="empty-section">
                            <i class="fas fa-star"></i>
                            <h3>هیچ نظری یافت نشد</h3>
                            <p>شما هنوز هیچ نظری ثبت نکرده‌اید.</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag"></i>
                                خرید کنید
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                                <?php
                                $product = getProductById($review['product_id']);
                                $productImages = getProductImages($review['product_id']);
                                $productImage = !empty($productImages) ? $productImages[0]['image_path'] : ($product['image_path'] ?: 'assets/images/no-image.jpg');
                                
                                // Get review status
                                $reviewStatus = $review['is_approved'] ? 'تایید شده' : 'در انتظار تایید';
                                $reviewStatusClass = $review['is_approved'] ? 'approved' : 'pending';
                                
                                // Get review date
                                $reviewDate = formatDate($review['created_at']);
                                ?>
                                
                                <div class="review-card">
                                    <div class="review-header">
                                        <div class="review-product">
                                            <div class="review-product-image">
                                                <img src="<?php echo $productImage; ?>" alt="<?php echo htmlspecialchars($product['name'] ?? 'محصول'); ?>">
                                            </div>
                                            <div class="review-product-info">
                                                <h4><?php echo htmlspecialchars($product['name'] ?? 'محصول'); ?></h4>
                                                <span class="review-date"><?php echo $reviewDate; ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i>
                                            <?php endfor; ?>
                                            <span class="rating-value"><?php echo toPersianNumbers($review['rating']); ?> از 5</span>
                                        </div>
                                    </div>
                                    
                                    <div class="review-body">
                                        <?php if ($review['title']): ?>
                                            <h4 class="review-title"><?php echo htmlspecialchars($review['title']); ?></h4>
                                        <?php endif; ?>
                                        <p class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    </div>
                                    
                                    <div class="review-footer">
                                        <span class="review-status <?php echo $reviewStatusClass; ?>">
                                            <?php echo $reviewStatus; ?>
                                        </span>
                                        <div class="review-actions">
                                            <a href="product.php?id=<?php echo $review['product_id']; ?>" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-eye"></i>
                                                مشاهده محصول
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Password Tab -->
                <div class="profile-tab" id="password-tab">
                    <div class="tab-header">
                        <h2><i class="fas fa-lock"></i> تغییر رمز عبور</h2>
                        <p>برای تغییر رمز عبور، اطلاعات زیر را وارد کنید</p>
                    </div>
                    
                    <form method="POST" action="profile.php" class="password-form">
                        <div class="form-group">
                            <label for="current_password">رمز عبور فعلی</label>
                            <div class="password-input">
                                <input type="password" id="current_password" name="current_password" 
                                       class="form-control" 
                                       placeholder="رمز عبور فعلی خود را وارد کنید" required>
                                <button type="button" class="toggle-password" onclick="togglePassword(this, 'current_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">رمز عبور جدید</label>
                            <div class="password-input">
                                <input type="password" id="new_password" name="new_password" 
                                       class="form-control" 
                                       placeholder="رمز عبور جدید را وارد کنید" required>
                                <button type="button" class="toggle-password" onclick="togglePassword(this, 'new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small style="color: #666;">رمز عبور باید حداقل 6 کاراکتر باشد</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">تکرار رمز عبور جدید</label>
                            <div class="password-input">
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="form-control" 
                                       placeholder="رمز عبور جدید را مجددا وارد کنید" required>
                                <button type="button" class="toggle-password" onclick="togglePassword(this, 'confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_password" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                تغییر رمز عبور
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal-overlay" id="add-address-modal">
    <div class="modal form-modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> افزودن آدرس جدید</h3>
            <button type="button" class="modal-close" onclick="closeAddAddressModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="profile.php" class="modal-form">
            <div class="modal-body">
                <div class="form-group">
                    <label for="address_title">عنوان آدرس</label>
                    <input type="text" id="address_title" name="address_title" 
                           class="form-control" 
                           placeholder="عنوان آدرس را وارد کنید">
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="first_name">نام <span style="color: #f44336;">*</span></label>
                            <input type="text" id="first_name" name="first_name" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" 
                                   placeholder="نام خود را وارد کنید" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="last_name">نام خانوادگی <span style="color: #f44336;">*</span></label>
                            <input type="text" id="last_name" name="last_name" 
                                   class="form-control" 
                                   placeholder="نام خانوادگی خود را وارد کنید" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address_phone">شماره تلفن <span style="color: #f44336;">*</span></label>
                    <input type="tel" id="address_phone" name="address_phone" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                           placeholder="شماره تلفن خود را وارد کنید" required>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="province_id">استان <span style="color: #f44336;">*</span></label>
                            <select id="province_id" name="province_id" class="form-control select-control" required onchange="loadCitiesForModal(this.value, 'add')">
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
                            <label for="city_id">شهر <span style="color: #f44336;">*</span></label>
                            <select id="city_id" name="city_id" class="form-control select-control" required>
                                <option value="">شهر را انتخاب کنید</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">آدرس کامل <span style="color: #f44336;">*</span></label>
                    <textarea id="address" name="address" class="form-control" rows="3" 
                              placeholder="آدرس کامل خود را وارد کنید" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="postal_code">کد پستی</label>
                    <input type="text" id="postal_code" name="postal_code" 
                           class="form-control" 
                           placeholder="کد پستی خود را وارد کنید">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="is_default" name="is_default" value="1">
                        <span>تنظیم به عنوان آدرس پیش‌فرض</span>
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="submit" name="add_address" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    ذخیره آدرس
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeAddAddressModal()">
                    <i class="fas fa-times"></i>
                    انصراف
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Address Modal -->
<div class="modal-overlay" id="edit-address-modal">
    <div class="modal form-modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> ویرایش آدرس</h3>
            <button type="button" class="modal-close" onclick="closeEditAddressModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="profile.php" class="modal-form" id="edit-address-form">
            <input type="hidden" name="address_id" id="edit_address_id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_address_title">عنوان آدرس</label>
                    <input type="text" id="edit_address_title" name="address_title" 
                           class="form-control" 
                           placeholder="عنوان آدرس را وارد کنید">
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="edit_first_name">نام <span style="color: #f44336;">*</span></label>
                            <input type="text" id="edit_first_name" name="first_name" 
                                   class="form-control" 
                                   placeholder="نام خود را وارد کنید" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="edit_last_name">نام خانوادگی <span style="color: #f44336;">*</span></label>
                            <input type="text" id="edit_last_name" name="last_name" 
                                   class="form-control" 
                                   placeholder="نام خانوادگی خود را وارد کنید" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_address_phone">شماره تلفن <span style="color: #f44336;">*</span></label>
                    <input type="tel" id="edit_address_phone" name="address_phone" 
                           class="form-control" 
                           placeholder="شماره تلفن خود را وارد کنید" required>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="edit_province_id">استان <span style="color: #f44336;">*</span></label>
                            <select id="edit_province_id" name="province_id" class="form-control select-control" required onchange="loadCitiesForModal(this.value, 'edit')">
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
                            <label for="edit_city_id">شهر <span style="color: #f44336;">*</span></label>
                            <select id="edit_city_id" name="city_id" class="form-control select-control" required>
                                <option value="">شهر را انتخاب کنید</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_address">آدرس کامل <span style="color: #f44336;">*</span></label>
                    <textarea id="edit_address" name="address" class="form-control" rows="3" 
                              placeholder="آدرس کامل خود را وارد کنید" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_postal_code">کد پستی</label>
                    <input type="text" id="edit_postal_code" name="postal_code" 
                           class="form-control" 
                           placeholder="کد پستی خود را وارد کنید">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="submit" name="update_address" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    ذخیره تغییرات
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeEditAddressModal()">
                    <i class="fas fa-times"></i>
                    انصراف
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Tab switching
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.profile-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Update nav active state
    document.querySelectorAll('.profile-nav li').forEach(li => {
        li.classList.remove('active');
    });
    
    event.target.closest('li').classList.add('active');
}

// Show default tab on page load
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash;
    if (hash) {
        const tabName = hash.substring(1);
        showTab(tabName);
    }
});

// Toggle password visibility
function togglePassword(button, inputId) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Address Modal Functions
function showAddAddressModal() {
    document.getElementById('add-address-modal').classList.add('active');
}

function closeAddAddressModal() {
    document.getElementById('add-address-modal').classList.remove('active');
}

function showEditAddressModal(addressId) {
    const address = <?php echo json_encode($addresses); ?>.find(a => a.id == addressId);
    
    if (address) {
        document.getElementById('edit_address_id').value = address.id;
        document.getElementById('edit_address_title').value = address.title || '';
        document.getElementById('edit_first_name').value = address.first_name || '';
        document.getElementById('edit_last_name').value = address.last_name || '';
        document.getElementById('edit_address_phone').value = address.phone || '';
        document.getElementById('edit_province_id').value = address.province_id || '';
        document.getElementById('edit_address').value = address.address || '';
        document.getElementById('edit_postal_code').value = address.postal_code || '';
        
        // Load cities for the selected province
        loadCitiesForModal(address.province_id, 'edit');
        
        setTimeout(() => {
            document.getElementById('edit_city_id').value = address.city_id || '';
        }, 500);
        
        document.getElementById('edit-address-modal').classList.add('active');
    }
}

function closeEditAddressModal() {
    document.getElementById('edit-address-modal').classList.remove('active');
}

// Load cities for modal
function loadCitiesForModal(provinceId, modalType) {
    fetch('includes/locations.php?action=get_cities&province_id=' + provinceId)
    .then(response => response.json())
    .then(data => {
        const citySelectId = modalType === 'add' ? 'city_id' : 'edit_city_id';
        const citySelect = document.getElementById(citySelectId);
        citySelect.innerHTML = '<option value="">شهر را انتخاب کنید</option>';
        
        data.cities.forEach(city => {
            const option = document.createElement('option');
            option.value = city.id;
            option.textContent = city.name;
            citySelect.appendChild(option);
        });
    });
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddAddressModal();
        closeEditAddressModal();
    }
});

// Close modals when clicking outside
document.getElementById('add-address-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddAddressModal();
    }
});

document.getElementById('edit-address-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditAddressModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
