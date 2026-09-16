<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Check if admin has permission
$adminRole = $_SESSION['admin_role'] ?? 'admin';
if ($adminRole !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

// Handle settings form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        // Save general settings
        $settings = [
            'site_name',
            'site_url',
            'site_email',
            'site_phone',
            'site_address',
            'site_description',
            'site_keywords',
            'site_copyright',
            'min_order_amount',
            'shipping_cost',
            'free_shipping_threshold',
            'currency',
            'currency_symbol',
            'decimals',
            'date_format',
            'time_format',
            'language',
            'theme_color',
            'maintenance_mode',
            'google_analytics',
            'facebook_pixel'
        ];
        
        foreach ($settings as $key) {
            if (isset($_POST[$key])) {
                $value = trim($_POST[$key]);
                setSetting($key, $value);
            }
        }
        
        // Save logo
        if (!empty($_FILES['site_logo']['tmp_name'])) {
            $uploadPath = 'assets/images/';
            $logoPath = uploadImage($_FILES['site_logo'], $uploadPath);
            if ($logoPath) {
                // Remove old logo
                $oldLogo = getSetting('site_logo');
                if ($oldLogo && file_exists(ROOT_PATH . '/' . $oldLogo)) {
                    unlink(ROOT_PATH . '/' . $oldLogo);
                }
                setSetting('site_logo', $logoPath);
            }
        }
        
        // Save favicon
        if (!empty($_FILES['site_favicon']['tmp_name'])) {
            $uploadPath = 'assets/images/';
            $faviconPath = uploadImage($_FILES['site_favicon'], $uploadPath);
            if ($faviconPath) {
                // Remove old favicon
                $oldFavicon = getSetting('site_favicon');
                if ($oldFavicon && file_exists(ROOT_PATH . '/' . $oldFavicon)) {
                    unlink(ROOT_PATH . '/' . $oldFavicon);
                }
                setSetting('site_favicon', $faviconPath);
            }
        }
        
        // Log activity
        logAdminActivity('update_settings', 'تنظیمات سایت به‌روزرسانی شد');
        
        $success = 'تنظیمات با موفقیت ذخیره شد.';
    }
    
    // Handle shipping settings
    if (isset($_POST['save_shipping'])) {
        // Delete existing shipping methods
        $pdo->query("DELETE FROM shipping_methods");
        
        // Save new shipping methods
        if (!empty($_POST['shipping_name'])) {
            foreach ($_POST['shipping_name'] as $index => $name) {
                if (!empty($name)) {
                    $price = $_POST['shipping_price'][$index] ?? 0;
                    $minAmount = $_POST['shipping_min_amount'][$index] ?? 0;
                    $maxAmount = $_POST['shipping_max_amount'][$index] ?? 0;
                    $estimatedDays = $_POST['shipping_estimated_days'][$index] ?? '';
                    $isActive = isset($_POST['shipping_active'][$index]) ? 1 : 0;
                    $sortOrder = $index + 1;
                    
                    $stmt = $pdo->prepare("INSERT INTO shipping_methods (name, price, min_order_amount, max_order_amount, estimated_delivery, is_active, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $price, $minAmount, $maxAmount, $estimatedDays, $isActive, $sortOrder]);
                }
            }
        }
        
        // Save default shipping cost
        if (isset($_POST['default_shipping_cost'])) {
            setSetting('shipping_cost', $_POST['default_shipping_cost']);
        }
        
        // Save free shipping threshold
        if (isset($_POST['free_shipping_threshold'])) {
            setSetting('free_shipping_threshold', $_POST['free_shipping_threshold']);
        }
        
        logAdminActivity('update_shipping_settings', 'تنظیمات ارسال به‌روزرسانی شد');
        $success = 'تنظیمات ارسال با موفقیت ذخیره شد.';
    }
    
    // Handle payment settings
    if (isset($_POST['save_payment'])) {
        // Save payment methods
        $paymentMethods = ['cash', 'online', 'transfer', 'wallet'];
        foreach ($paymentMethods as $method) {
            $isActive = isset($_POST['payment_method_active'][$method]) ? 1 : 0;
            $sortOrder = $_POST['payment_method_sort'][$method] ?? 0;
            
            $stmt = $pdo->prepare("INSERT INTO payment_methods (code, name, is_active, sort_order, created_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE is_active = ?, sort_order = ?");
            $stmt->execute([
                $method,
                getPaymentMethodText($method),
                $isActive,
                $sortOrder,
                $isActive,
                $sortOrder
            ]);
        }
        
        // Save payment gateway settings
        $gatewaySettings = [
            'zarinpal_merchant_id',
            'zarinpal_is_active',
            'payir_api_key',
            'payir_is_active'
        ];
        
        foreach ($gatewaySettings as $key) {
            if (isset($_POST[$key])) {
                setSetting($key, $_POST[$key]);
            }
        }
        
        logAdminActivity('update_payment_settings', 'تنظیمات پرداخت به‌روزرسانی شد');
        $success = 'تنظیمات پرداخت با موفقیت ذخیره شد.';
    }
    
    // Handle social media settings
    if (isset($_POST['save_social'])) {
        // Delete existing social media links
        $pdo->query("DELETE FROM social_media");
        
        // Save new social media links
        if (!empty($_POST['social_name'])) {
            foreach ($_POST['social_name'] as $index => $name) {
                if (!empty($name) && !empty($_POST['social_url'][$index])) {
                    $url = $_POST['social_url'][$index];
                    $icon = $_POST['social_icon'][$index] ?? '';
                    $isActive = isset($_POST['social_active'][$index]) ? 1 : 0;
                    $sortOrder = $index + 1;
                    
                    $stmt = $pdo->prepare("INSERT INTO social_media (name, url, icon_class, is_active, sort_order, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $url, $icon, $isActive, $sortOrder]);
                }
            }
        }
        
        logAdminActivity('update_social_settings', 'تنظیمات شبکه‌های اجتماعی به‌روزرسانی شد');
        $success = 'تنظیمات شبکه‌های اجتماعی با موفقیت ذخیره شد.';
    }
}

// Get current settings
$siteSettings = [
    'site_name',
    'site_url',
    'site_email',
    'site_phone',
    'site_address',
    'site_description',
    'site_keywords',
    'site_copyright',
    'min_order_amount',
    'shipping_cost',
    'free_shipping_threshold',
    'currency',
    'currency_symbol',
    'decimals',
    'date_format',
    'time_format',
    'language',
    'theme_color',
    'maintenance_mode',
    'google_analytics',
    'facebook_pixel',
    'site_logo',
    'site_favicon'
];

$settings = [];
foreach ($siteSettings as $key) {
    $settings[$key] = getSetting($key);
}

// Get shipping methods
$shippingMethods = [];
try {
    $stmt = $pdo->query("SELECT * FROM shipping_methods ORDER BY sort_order ASC");
    $shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $shippingMethods = [];
}

// Get payment methods
$paymentMethods = [];
try {
    $stmt = $pdo->query("SELECT * FROM payment_methods ORDER BY sort_order ASC");
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $paymentMethods = [];
}

// Get social media links
$socialMedia = [];
try {
    $stmt = $pdo->query("SELECT * FROM social_media ORDER BY sort_order ASC");
    $socialMedia = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $socialMedia = [];
}

// Social media icons
$socialIcons = [
    'instagram' => 'fab fa-instagram',
    'telegram' => 'fab fa-telegram',
    'whatsapp' => 'fab fa-whatsapp',
    'facebook' => 'fab fa-facebook',
    'twitter' => 'fab fa-twitter',
    'linkedin' => 'fab fa-linkedin',
    'youtube' => 'fab fa-youtube',
    'aparat' => 'fas fa-play-circle'
];

$pageTitle = "تنظیمات سایت";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-cog"></i> تنظیمات سایت</h1>
        <div class="admin-actions">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> بازگشت به داشبورد
            </a>
        </div>
    </div>

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

    <!-- Tabs -->
    <div class="tabs-container">
        <div class="tabs">
            <a href="#general" class="tab-link active">
                <i class="fas fa-info-circle"></i>
                عمومی
            </a>
            <a href="#shipping" class="tab-link">
                <i class="fas fa-truck"></i>
                ارسال
            </a>
            <a href="#payment" class="tab-link">
                <i class="fas fa-credit-card"></i>
                پرداخت
            </a>
            <a href="#social" class="tab-link">
                <i class="fas fa-share-alt"></i>
                شبکه‌های اجتماعی
            </a>
            <a href="#seo" class="tab-link">
                <i class="fas fa-search"></i>
                سئو
            </a>
        </div>

        <!-- General Settings Tab -->
        <div id="general" class="tab-content active">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>تنظیمات عمومی</h3>
                </div>
                
                <form method="POST" action="settings.php" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-col half">
                            <div class="form-group">
                                <label for="site_name">نام سایت</label>
                                <input type="text" id="site_name" name="site_name" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" 
                                       placeholder="نام سایت را وارد کنید" required>
                            </div>
                        </div>
                        <div class="form-col half">
                            <div class="form-group">
                                <label for="site_url">آدرس سایت</label>
                                <input type="url" id="site_url" name="site_url" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['site_url'] ?? ''); ?>" 
                                       placeholder="https://example.com" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col half">
                            <div class="form-group">
                                <label for="site_email">ایمیل سایت</label>
                                <input type="email" id="site_email" name="site_email" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>" 
                                       placeholder="info@example.com" required>
                            </div>
                        </div>
                        <div class="form-col half">
                            <div class="form-group">
                                <label for="site_phone">تلفن سایت</label>
                                <input type="text" id="site_phone" name="site_phone" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['site_phone'] ?? ''); ?>" 
                                       placeholder="021-12345678">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="site_address">آدرس سایت</label>
                        <textarea id="site_address" name="site_address" 
                                  class="form-control textarea-control" 
                                  placeholder="آدرس کامل سایت را وارد کنید"><?php echo htmlspecialchars($settings['site_address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-col half">
                            <div class="form-group">
                                <label for="site_logo">لوگو سایت</label>
                                <div class="file-upload-area">
                                    <i class="fas fa-image"></i>
                                    <span class="upload-text">لوگو را آپلود کنید</span>
                                    <span class="upload-hint">فرمت‌های مجاز: JPG, PNG, GIF, WebP</span>
                                    <input type="file" id="site_logo" name="site_logo" accept="image/*">
                                </div>
                                <?php if ($settings['site_logo']): ?>
                                    <div class="file-preview" style="margin-top: 12px;">
                                        <div class="file-preview-item">
                                            <img src="../<?php echo htmlspecialchars($settings['site_logo']); ?>" alt="لوگو سایت">
                                            <button type="button" class="remove-btn" onclick="removeLogo()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-col half">
                            <div class="form-group">
                                <label for="site_favicon">فاوآیکون سایت</label>
                                <div class="file-upload-area">
                                    <i class="fas fa-image"></i>
                                    <span class="upload-text">فاوآیکون را آپلود کنید</span>
                                    <span class="upload-hint">فرمت‌های مجاز: ICO, PNG</span>
                                    <input type="file" id="site_favicon" name="site_favicon" accept="image/x-icon,image/png">
                                </div>
                                <?php if ($settings['site_favicon']): ?>
                                    <div class="file-preview" style="margin-top: 12px;">
                                        <div class="file-preview-item">
                                            <img src="../<?php echo htmlspecialchars($settings['site_favicon']); ?>" alt="فاوآیکون سایت">
                                            <button type="button" class="remove-btn" onclick="removeFavicon()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col half">
                            <div class="form-group">
                                <label for="currency">واحد پول</label>
                                <select id="currency" name="currency" class="form-control select-control">
                                    <option value="تومان" <?php echo ($settings['currency'] ?? '') === 'تومان' ? 'selected' : ''; ?>>تومان</option>
                                    <option value="ریال" <?php echo ($settings['currency'] ?? '') === 'ریال' ? 'selected' : ''; ?>>ریال</option>
                                    <option value="دולר" <?php echo ($settings['currency'] ?? '') === 'دולר' ? 'selected' : ''; ?>>دالر</option>
                                    <option value="یورو" <?php echo ($settings['currency'] ?? '') === 'یورو' ? 'selected' : ''; ?>>یورو</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-col half">
                            <div class="form-group">
                                <label for="currency_symbol">نماد پول</label>
                                <input type="text" id="currency_symbol" name="currency_symbol" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? 'تومان'); ?>" 
                                       placeholder="نماد پول">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col half">
                            <div class="form-group">
                                <label for="date_format">فرمت تاریخ</label>
                                <select id="date_format" name="date_format" class="form-control select-control">
                                    <option value="Y/m/d" <?php echo ($settings['date_format'] ?? '') === 'Y/m/d' ? 'selected' : ''; ?>>YYYY/MM/DD</option>
                                    <option value="Y-m-d" <?php echo ($settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                    <option value="d/m/Y" <?php echo ($settings['date_format'] ?? '') === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-col half">
                            <div class="form-group">
                                <label for="time_format">فرمت زمان</label>
                                <select id="time_format" name="time_format" class="form-control select-control">
                                    <option value="H:i" <?php echo ($settings['time_format'] ?? '') === 'H:i' ? 'selected' : ''; ?>>HH:MM (24 ساعت)</option>
                                    <option value="h:i A" <?php echo ($settings['time_format'] ?? '') === 'h:i A' ? 'selected' : ''; ?>>HH:MM AM/PM</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="site_copyright">متن کپی‌رایت</label>
                        <input type="text" id="site_copyright" name="site_copyright" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($settings['site_copyright'] ?? ''); ?>" 
                               placeholder="© 2024 نام سایت. تمام حقوق محفوظ است.">
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="maintenance_mode" 
                                   value="1" <?php echo ($settings['maintenance_mode'] ?? '') === '1' ? 'checked' : ''; ?>>
                            <span>فعال کردن حالت تعمیر و نگهداری</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="google_analytics">کد گوگل آنالیتیکس</label>
                        <textarea id="google_analytics" name="google_analytics" 
                                  class="form-control textarea-control" 
                                  placeholder="کد گوگل آنالیتیکس را وارد کنید"><?php echo htmlspecialchars($settings['google_analytics'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="facebook_pixel">کد فیسبوک پیکسل</label>
                        <textarea id="facebook_pixel" name="facebook_pixel" 
                                  class="form-control textarea-control" 
                                  placeholder="کد فیسبوک پیکسل را وارد کنید"><?php echo htmlspecialchars($settings['facebook_pixel'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="save_settings" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            ذخیره تنظیمات
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Shipping Settings Tab -->
        <div id="shipping" class="tab-content">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>تنظیمات ارسال</h3>
                </div>
                
                <form method="POST" action="settings.php">
                    <div class="form-group">
                        <label for="default_shipping_cost">هزینه ارسال پیش‌فرض (تومان)</label>
                        <input type="number" id="default_shipping_cost" name="default_shipping_cost" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($settings['shipping_cost'] ?? '0'); ?>" 
                               min="0">
                    </div>

                    <div class="form-group">
                        <label for="free_shipping_threshold">حداقل مبلغ سفارش برای ارسال رایگان (تومان)</label>
                        <input type="number" id="free_shipping_threshold" name="free_shipping_threshold" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($settings['free_shipping_threshold'] ?? '0'); ?>" 
                               min="0">
                    </div>

                    <div class="form-group">
                        <label>روش‌های ارسال</label>
                        <div id="shipping-methods-container">
                            <?php foreach ($shippingMethods as $index => $method): ?>
                                <div class="shipping-method-item" style="background: var(--bg-primary); padding: 16px; border-radius: var(--border-radius-sm); margin-bottom: 12px; border: 1px solid var(--border-color);">
                                    <div class="form-row">
                                        <div class="form-col half">
                                            <div class="form-group">
                                                <label>نام روش ارسال</label>
                                                <input type="text" name="shipping_name[]" 
                                                       class="form-control" 
                                                       value="<?php echo htmlspecialchars($method['name']); ?>" 
                                                       placeholder="نام روش ارسال">
                                            </div>
                                        </div>
                                        <div class="form-col half">
                                            <div class="form-group">
                                                <label>قیمت (تومان)</label>
                                                <input type="number" name="shipping_price[]" 
                                                       class="form-control" 
                                                       value="<?php echo htmlspecialchars($method['price']); ?>" 
                                                       min="0">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-col half">
                                            <div class="form-group">
                                                <label>حداقل مبلغ سفارش (تومان)</label>
                                                <input type="number" name="shipping_min_amount[]" 
                                                       class="form-control" 
                                                       value="<?php echo htmlspecialchars($method['min_order_amount']); ?>" 
                                                       min="0">
                                            </div>
                                        </div>
                                        <div class="form-col half">
                                            <div class="form-group">
                                                <label>حداکثر مبلغ سفارش (تومان)</label>
                                                <input type="number" name="shipping_max_amount[]" 
                                                       class="form-control" 
                                                       value="<?php echo htmlspecialchars($method['max_order_amount']); ?>" 
                                                       min="0">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-col half">
                                            <div class="form-group">
                                                <label>زمان تخمینی تحویل (روز)</label>
                                                <input type="text" name="shipping_estimated_days[]" 
                                                       class="form-control" 
                                                       value="<?php echo htmlspecialchars($method['estimated_delivery']); ?>" 
                                                       placeholder="مثال: 2-3 روز">
                                            </div>
                                        </div>
                                        <div class="form-col half">
                                            <div class="form-group">
                                                <label>
                                                    <input type="checkbox" name="shipping_active[]" 
                                                           value="1" <?php echo $method['is_active'] ? 'checked' : ''; ?>>
                                                    <span>فعال</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeShippingMethod(this)">
                                        <i class="fas fa-trash"></i> حذف
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addShippingMethod()">
                            <i class="fas fa-plus"></i> افزودن روش ارسال جدید
                        </button>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="save_shipping" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            ذخیره تنظیمات ارسال
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payment Settings Tab -->
        <div id="payment" class="tab-content">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>تنظیمات پرداخت</h3>
                </div>
                
                <form method="POST" action="settings.php">
                    <div class="form-group">
                        <label>روش‌های پرداخت</label>
                        <div class="payment-methods-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                            <?php
                            $paymentMethodOptions = [
                                'cash' => 'پرداخت در محل',
                                'online' => 'پرداخت آنلاین',
                                'transfer' => 'واریز بانکی',
                                'wallet' => 'کیف پول'
                            ];
                            
                            foreach ($paymentMethodOptions as $code => $name):
                                $method = array_filter($paymentMethods, function($m) use ($code) { return $m['code'] === $code; });
                                $method = reset($method) ?: ['is_active' => 1, 'sort_order' => 0];
                            ?>
                                <div style="background: var(--bg-primary); padding: 16px; border-radius: var(--border-radius-sm); border: 1px solid var(--border-color);">
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="payment_method_active[<?php echo $code; ?>]" 
                                                   value="1" <?php echo $method['is_active'] ? 'checked' : ''; ?>>
                                            <span><?php echo $name; ?></span>
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label>ترتیب</label>
                                        <input type="number" name="payment_method_sort[<?php echo $code; ?>]" 
                                               class="form-control" 
                                               value="<?php echo $method['sort_order']; ?>" 
                                               min="0">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>درگاه‌های پرداخت آنلاین</label>
                        <div class="gateway-settings" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <div style="background: var(--bg-primary); padding: 20px; border-radius: var(--border-radius-sm); border: 1px solid var(--border-color);">
                                <h4 style="margin-bottom: 16px; color: var(--text-primary);">
                                    <i class="fas fa-credit-card" style="color: #0066cc; margin-left: 8px;"></i>
                                    زرین‌پال
                                </h4>
                                <div class="form-group">
                                    <label for="zarinpal_merchant_id">کد مرچنت</label>
                                    <input type="text" id="zarinpal_merchant_id" name="zarinpal_merchant_id" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars(getSetting('zarinpal_merchant_id', '')); ?>" 
                                           placeholder="کد مرچنت زرین‌پال">
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="zarinpal_is_active" 
                                               value="1" <?php echo getSetting('zarinpal_is_active', '') === '1' ? 'checked' : ''; ?>>
                                        <span>فعال کردن زرین‌پال</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div style="background: var(--bg-primary); padding: 20px; border-radius: var(--border-radius-sm); border: 1px solid var(--border-color);">
                                <h4 style="margin-bottom: 16px; color: var(--text-primary);">
                                    <i class="fas fa-credit-card" style="color: #009900; margin-left: 8px;"></i>
                                    پی‌لاین
                                </h4>
                                <div class="form-group">
                                    <label for="payir_api_key">کلید API</label>
                                    <input type="text" id="payir_api_key" name="payir_api_key" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars(getSetting('payir_api_key', '')); ?>" 
                                           placeholder="کلید API پی‌لاین">
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="payir_is_active" 
                                               value="1" <?php echo getSetting('payir_is_active', '') === '1' ? 'checked' : ''; ?>>
                                        <span>فعال کردن پی‌لاین</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="save_payment" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            ذخیره تنظیمات پرداخت
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Social Media Settings Tab -->
        <div id="social" class="tab-content">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>شبکه‌های اجتماعی</h3>
                </div>
                
                <form method="POST" action="settings.php">
                    <div class="form-group">
                        <label>لینک‌های شبکه‌های اجتماعی</label>
                        <div id="social-media-container">
                            <?php foreach ($socialMedia as $index => $social): ?>
                                <div class="social-media-item" style="background: var(--bg-primary); padding: 16px; border-radius: var(--border-radius-sm); margin-bottom: 12px; border: 1px solid var(--border-color);">
                                    <div class="form-row">
                                        <div class="form-col half">
                                            <div class="form-group">
                                                <label>نام</label>
                                                <input type="text" name="social_name[]" 
                                                       class="form-control" 
                                                       value="<?php echo htmlspecialchars($social['name']); ?>" 
                                                       placeholder="نام شبکه اجتماعی">
                                            </div>
                                        </div>
                                        <div class="form-col half">
                                            <div class="form-group">
                                                <label>آیکون</label>
                                                <select name="social_icon[]" class="form-control select-control">
                                                    <?php foreach ($socialIcons as $key => $icon): ?>
                                                        <option value="<?php echo $icon; ?>" <?php echo ($social['icon_class'] ?? '') === $icon ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($key); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>آدرس لینک</label>
                                        <input type="url" name="social_url[]" 
                                               class="form-control" 
                                               value="<?php echo htmlspecialchars($social['url']); ?>" 
                                               placeholder="https://example.com">
                                    </div>
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="social_active[]" 
                                                   value="1" <?php echo $social['is_active'] ? 'checked' : ''; ?>>
                                            <span>فعال</span>
                                        </label>
                                    </div>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeSocialMedia(this)">
                                        <i class="fas fa-trash"></i> حذف
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addSocialMedia()">
                            <i class="fas fa-plus"></i> افزودن شبکه اجتماعی جدید
                        </button>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="save_social" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            ذخیره تنظیمات شبکه‌های اجتماعی
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- SEO Settings Tab -->
        <div id="seo" class="tab-content">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>تنظیمات سئو</h3>
                </div>
                
                <form method="POST" action="settings.php">
                    <div class="form-group">
                        <label for="site_description">توضیحات سایت (Meta Description)</label>
                        <textarea id="site_description" name="site_description" 
                                  class="form-control textarea-control" 
                                  data-maxlength="160" 
                                  placeholder="توضیحات سایت برای موتورهای جستجو (حداکثر 160 کاراکتر)"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="site_keywords">کلمات کلیدی (Meta Keywords)</label>
                        <textarea id="site_keywords" name="site_keywords" 
                                  class="form-control textarea-control" 
                                  placeholder="کلمات کلیدی سایت را با کاما جدا کنید"><?php echo htmlspecialchars($settings['site_keywords'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="seo_title">عنوان سئو (Meta Title)</label>
                        <input type="text" id="seo_title" name="site_name" 
                               class="form-control" 
                               data-maxlength="60" 
                               value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" 
                               placeholder="عنوان سایت برای سئو (حداکثر 60 کاراکتر)">
                    </div>

                    <div class="form-group">
                        <button type="submit" name="save_settings" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            ذخیره تنظیمات سئو
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Tab functionality
function initTabs() {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href').substring(1);
            
            // Remove active class from all
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked
            this.classList.add('active');
            document.getElementById(targetId).classList.add('active');
        });
    });
}

// Add shipping method
function addShippingMethod() {
    const container = document.getElementById('shipping-methods-container');
    const index = container.querySelectorAll('.shipping-method-item').length;
    
    const html = `
        <div class="shipping-method-item" style="background: var(--bg-primary); padding: 16px; border-radius: var(--border-radius-sm); margin-bottom: 12px; border: 1px solid var(--border-color);">
            <div class="form-row">
                <div class="form-col half">
                    <div class="form-group">
                        <label>نام روش ارسال</label>
                        <input type="text" name="shipping_name[]" 
                               class="form-control" 
                               placeholder="نام روش ارسال">
                    </div>
                </div>
                <div class="form-col half">
                    <div class="form-group">
                        <label>قیمت (تومان)</label>
                        <input type="number" name="shipping_price[]" 
                               class="form-control" 
                               min="0">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-col half">
                    <div class="form-group">
                        <label>حداقل مبلغ سفارش (تومان)</label>
                        <input type="number" name="shipping_min_amount[]" 
                               class="form-control" 
                               min="0">
                    </div>
                </div>
                <div class="form-col half">
                    <div class="form-group">
                        <label>حداکثر مبلغ سفارش (تومان)</label>
                        <input type="number" name="shipping_max_amount[]" 
                               class="form-control" 
                               min="0">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-col half">
                    <div class="form-group">
                        <label>زمان تخمینی تحویل (روز)</label>
                        <input type="text" name="shipping_estimated_days[]" 
                               class="form-control" 
                               placeholder="مثال: 2-3 روز">
                    </div>
                </div>
                <div class="form-col half">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="shipping_active[]" 
                                   value="1" checked>
                            <span>فعال</span>
                        </label>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeShippingMethod(this)">
                <i class="fas fa-trash"></i> حذف
            </button>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
}

// Remove shipping method
function removeShippingMethod(button) {
    const item = button.closest('.shipping-method-item');
    if (item) {
        item.remove();
    }
}

// Add social media
function addSocialMedia() {
    const container = document.getElementById('social-media-container');
    const index = container.querySelectorAll('.social-media-item').length;
    
    const html = `
        <div class="social-media-item" style="background: var(--bg-primary); padding: 16px; border-radius: var(--border-radius-sm); margin-bottom: 12px; border: 1px solid var(--border-color);">
            <div class="form-row">
                <div class="form-col half">
                    <div class="form-group">
                        <label>نام</label>
                        <input type="text" name="social_name[]" 
                               class="form-control" 
                               placeholder="نام شبکه اجتماعی">
                    </div>
                </div>
                <div class="form-col half">
                    <div class="form-group">
                        <label>آیکون</label>
                        <select name="social_icon[]" class="form-control select-control">
                            <?php foreach ($socialIcons as $key => $icon): ?>
                                <option value="<?php echo $icon; ?>">
                                    <?php echo htmlspecialchars($key); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>آدرس لینک</label>
                <input type="url" name="social_url[]" 
                       class="form-control" 
                       placeholder="https://example.com">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="social_active[]" 
                           value="1" checked>
                    <span>فعال</span>
                </label>
            </div>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeSocialMedia(this)">
                <i class="fas fa-trash"></i> حذف
            </button>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
}

// Remove social media
function removeSocialMedia(button) {
    const item = button.closest('.social-media-item');
    if (item) {
        item.remove();
    }
}

// Remove logo
function removeLogo() {
    if (confirm('آیا از حذف لوگو مطمئن هستید؟')) {
        // Create hidden input to mark logo for deletion
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'remove_logo';
        input.value = '1';
        document.querySelector('form').appendChild(input);
        
        // Remove preview
        const preview = document.querySelector('.file-preview');
        if (preview) {
            preview.remove();
        }
        
        // Clear file input
        document.getElementById('site_logo').value = '';
    }
}

// Remove favicon
function removeFavicon() {
    if (confirm('آیا از حذف فاوآیکون مطمئن هستید؟')) {
        // Create hidden input to mark favicon for deletion
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'remove_favicon';
        input.value = '1';
        document.querySelector('form').appendChild(input);
        
        // Remove preview
        const preview = document.querySelector('.file-preview');
        if (preview) {
            preview.remove();
        }
        
        // Clear file input
        document.getElementById('site_favicon').value = '';
    }
}

// Initialize tabs on page load
domReady(initTabs);

// Character counters for textareas
function initCharCounters() {
    const textareas = document.querySelectorAll('textarea[data-maxlength]');
    textareas.forEach(textarea => {
        const maxLength = parseInt(textarea.dataset.maxlength);
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.style.cssText = 'font-size: 12px; color: #999; text-align: right; margin-top: 4px;';
        textarea.parentNode.insertBefore(counter, textarea.nextSibling);
        
        textarea.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            counter.textContent = toPersianNumbers(remaining) + ' کاراکتر باقی مانده';
            
            if (remaining < 0) {
                counter.style.color = '#f44336';
            } else if (remaining < 20) {
                counter.style.color = '#ff9800';
            } else {
                counter.style.color = '#999';
            }
        });
        
        // Trigger once
        textarea.dispatchEvent(new Event('input'));
    });
}

// Initialize character counters
domReady(initCharCounters);
</script>

<?php require_once 'footer.php'; ?>
