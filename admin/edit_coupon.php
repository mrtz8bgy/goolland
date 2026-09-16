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

// Get coupon ID
$couponId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get coupon
try {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$couponId]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $coupon = false;
}

if (!$coupon) {
    header("Location: manage_coupons.php");
    exit;
}

// Get products for restrictions
$products = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM products WHERE is_active = 1 ORDER BY name");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}

// Get categories for restrictions
$categories = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_coupon'])) {
    // Validate required fields
    if (empty($_POST['code'])) {
        $error = 'لطفا کد کوپن را وارد کنید.';
    } elseif (empty($_POST['discount_type'])) {
        $error = 'لطفا نوع تخفیف را انتخاب کنید.';
    } elseif (empty($_POST['discount_value']) || !is_numeric($_POST['discount_value'])) {
        $error = 'لطفا مقدار تخفیف را وارد کنید.';
    } else {
        // Prepare coupon data
        $couponData = [
            'code' => strtoupper(trim($_POST['code'])),
            'description' => trim($_POST['description'] ?? ''),
            'discount_type' => $_POST['discount_type'],
            'discount_value' => (float)$_POST['discount_value'],
            'min_order_amount' => !empty($_POST['min_order_amount']) ? (float)$_POST['min_order_amount'] : 0,
            'max_uses' => !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : 0,
            'uses_per_customer' => !empty($_POST['uses_per_customer']) ? (int)$_POST['uses_per_customer'] : 0,
            'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'free_shipping' => isset($_POST['free_shipping']) ? 1 : 0,
            'exclude_sale_items' => isset($_POST['exclude_sale_items']) ? 1 : 0
        ];
        
        // Handle product restrictions
        $productIds = $_POST['product_ids'] ?? [];
        if (!empty($productIds)) {
            $couponData['product_ids'] = implode(',', array_map('intval', $productIds));
        } else {
            $couponData['product_ids'] = '';
        }
        
        // Handle category restrictions
        $categoryIds = $_POST['category_ids'] ?? [];
        if (!empty($categoryIds)) {
            $couponData['category_ids'] = implode(',', array_map('intval', $categoryIds));
        } else {
            $couponData['category_ids'] = '';
        }
        
        try {
            // Update coupon
            $result = updateCoupon($couponId, $couponData);
            
            if ($result) {
                // Log activity
                logAdminActivity('update_coupon', 'کوپن به‌روزرسانی شد: ' . $couponData['code']);
                
                // Refresh coupon
                $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
                $stmt->execute([$couponId]);
                $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $success = 'کوپن با موفقیت به‌روزرسانی شد.';
            } else {
                $error = 'خطا در به‌روزرسانی کوپن.';
            }
        } catch (Exception $e) {
            $error = 'خطا: ' . $e->getMessage();
        }
    }
}

$pageTitle = "ویرایش کوپن - " . htmlspecialchars($coupon['code']);
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-edit"></i> ویرایش کوپن</h1>
        <div class="admin-actions">
            <a href="manage_coupons.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> لیست کوپن‌ها
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> بازگشت
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

    <!-- Coupon Form -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>ویرایش کوپن: <?php echo htmlspecialchars($coupon['code']); ?></h3>
        </div>
        
        <form method="POST" action="edit_coupon.php?id=<?php echo $couponId; ?>">
            <div class="form-group">
                <label for="code">کد کوپن <span style="color: #f44336;">*</span></label>
                <input type="text" id="code" name="code" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($coupon['code']); ?>" 
                       placeholder="کد کوپن را وارد کنید" required>
            </div>

            <div class="form-group">
                <label for="description">توضیحات</label>
                <textarea id="description" name="description" 
                          class="form-control textarea-control" 
                          placeholder="توضیحات کوپن را وارد کنید"><?php echo htmlspecialchars($coupon['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>تنظیمات تخفیف</label>
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="discount_type">نوع تخفیف <span style="color: #f44336;">*</span></label>
                            <select id="discount_type" name="discount_type" class="form-control select-control" required>
                                <option value="" disabled>نوع تخفیف را انتخاب کنید</option>
                                <option value="percentage" <?php echo $coupon['discount_type'] === 'percentage' ? 'selected' : ''; ?>>درصد (%)</option>
                                <option value="fixed_amount" <?php echo $coupon['discount_type'] === 'fixed_amount' ? 'selected' : ''; ?>>مبلغ ثابت (تومان)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="discount_value">مقدار تخفیف <span style="color: #f44336;">*</span></label>
                            <input type="number" id="discount_value" name="discount_value" 
                                   class="form-control" 
                                   value="<?php echo toPersianNumbers($coupon['discount_value']); ?>" 
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="min_order_amount">حداقل مبلغ سفارش</label>
                <div class="input-group">
                    <input type="number" id="min_order_amount" name="min_order_amount" 
                           class="form-control" 
                           value="<?php echo toPersianNumbers($coupon['min_order_amount'] ?? 0); ?>" 
                           step="1000" min="0">
                    <span class="input-suffix">تومان</span>
                </div>
            </div>

            <div class="form-group">
                <label>محدودیت‌های استفاده</label>
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="max_uses">حداکثر تعداد استفاده</label>
                            <input type="number" id="max_uses" name="max_uses" 
                                   class="form-control" 
                                   value="<?php echo toPersianNumbers($coupon['max_uses'] ?? 0); ?>" 
                                   min="0" placeholder="0 = نامحدود">
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="uses_per_customer">حداکثر استفاده هر مشتری</label>
                            <input type="number" id="uses_per_customer" name="uses_per_customer" 
                                   class="form-control" 
                                   value="<?php echo toPersianNumbers($coupon['uses_per_customer'] ?? 0); ?>" 
                                   min="0" placeholder="0 = نامحدود">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>تاریخ انقضا</label>
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="start_date">تاریخ شروع</label>
                            <input type="datetime-local" id="start_date" name="start_date" 
                                   class="form-control" 
                                   value="<?php echo $coupon['start_date'] ? date('Y-m-d\TH:i', strtotime($coupon['start_date'])) : ''; ?>">
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="end_date">تاریخ پایان</label>
                            <input type="datetime-local" id="end_date" name="end_date" 
                                   class="form-control" 
                                   value="<?php echo $coupon['end_date'] ? date('Y-m-d\TH:i', strtotime($coupon['end_date'])) : ''; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>محدودیت‌های محصول</label>
                <div class="form-row">
                    <div class="form-col full">
                        <div class="form-group">
                            <label>محصولات مجاز</label>
                            <div class="checkbox-group" style="max-height: 200px; overflow-y: auto; border: 1px solid #e0e0e0; padding: 12px; border-radius: 4px;">
                                <?php
                                $selectedProductIds = !empty($coupon['product_ids']) ? explode(',', $coupon['product_ids']) : [];
                                foreach ($products as $product):
                                    $isChecked = in_array($product['id'], $selectedProductIds);
                                ?>
                                    <label style="display: block; padding: 8px 0;">
                                        <input type="checkbox" name="product_ids[]" 
                                               value="<?php echo $product['id']; ?>" 
                                               <?php echo $isChecked ? 'checked' : ''; ?>>
                                        <span><?php echo htmlspecialchars($product['name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <small style="color: #666; margin-top: 8px; display: block;">
                                در صورت عدم انتخاب، کوپن برای همه محصولات قابل استفاده خواهد بود
                            </small>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col full">
                        <div class="form-group">
                            <label>دسته‌بندی‌های مجاز</label>
                            <div class="checkbox-group" style="max-height: 200px; overflow-y: auto; border: 1px solid #e0e0e0; padding: 12px; border-radius: 4px;">
                                <?php
                                $selectedCategoryIds = !empty($coupon['category_ids']) ? explode(',', $coupon['category_ids']) : [];
                                foreach ($categories as $category):
                                    $isChecked = in_array($category['id'], $selectedCategoryIds);
                                ?>
                                    <label style="display: block; padding: 8px 0;">
                                        <input type="checkbox" name="category_ids[]" 
                                               value="<?php echo $category['id']; ?>" 
                                               <?php echo $isChecked ? 'checked' : ''; ?>>
                                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <small style="color: #666; margin-top: 8px; display: block;">
                                در صورت عدم انتخاب، کوپن برای همه دسته‌بندی‌ها قابل استفاده خواهد بود
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>تنظیمات اضافی</label>
                <div class="form-row">
                    <div class="form-col full">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="free_shipping" 
                                       value="1" <?php echo $coupon['free_shipping'] ? 'checked' : ''; ?>>
                                <span>ارسال رایگان</span>
                            </label>
                            <p style="font-size: 13px; color: #666; margin-top: 4px;">
                                در صورت فعال بودن، این کوپن ارسال رایگان را برای سفارش فعال می‌کند
                            </p>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col full">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="exclude_sale_items" 
                                       value="1" <?php echo $coupon['exclude_sale_items'] ? 'checked' : ''; ?>>
                                <span>غیرفعال کردن برای محصولات تخفیف‌دار</span>
                            </label>
                            <p style="font-size: 13px; color: #666; margin-top: 4px;">
                                در صورت فعال بودن، این کوپن برای محصولاتی که در حال حاضر تخفیف دارند، اعمال نخواهد شد
                            </p>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col full">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" 
                                       value="1" <?php echo $coupon['is_active'] ? 'checked' : ''; ?>>
                                <span>فعال</span>
                            </label>
                            <p style="font-size: 13px; color: #666; margin-top: 4px;">
                                در صورت غیرفعال بودن، مشتریان نمی‌توانند از این کوپن استفاده کنند
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" name="update_coupon" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    ذخیره تغییرات
                </button>
                <a href="manage_coupons.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    انصراف
                </a>
            </div>
        </form>
    </div>

    <!-- Coupon Stats -->
    <div class="dashboard-card" style="margin-top: 20px;">
        <div class="card-header">
            <h3><i class="fas fa-chart-bar"></i> آمار کوپن</h3>
        </div>
        
        <div class="coupon-stats">
            <div class="stat-row">
                <span class="stat-label">تعداد استفاده:</span>
                <span class="stat-value">
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM coupon_uses WHERE coupon_id = ?");
                        $stmt->execute([$couponId]);
                        $uses = $stmt->fetchColumn();
                        echo toPersianNumbers($uses) . ' بار';
                    } catch (PDOException $e) {
                        echo '0 بار';
                    }
                    ?>
                </span>
            </div>
            
            <div class="stat-row">
                <span class="stat-label">مبلغ کل تخفیف:</span>
                <span class="stat-value">
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT SUM(discount_amount) FROM coupon_uses WHERE coupon_id = ?");
                        $stmt->execute([$couponId]);
                        $totalDiscount = $stmt->fetchColumn();
                        echo toPersianNumbers(number_format($totalDiscount ?? 0)) . ' تومان';
                    } catch (PDOException $e) {
                        echo '0 تومان';
                    }
                    ?>
                </span>
            </div>
            
            <?php
            // Check if coupon is expired
            $isExpired = false;
            if ($coupon['end_date']) {
                $endDate = new DateTime($coupon['end_date']);
                $now = new DateTime();
                $isExpired = $endDate < $now;
            }
            
            // Check if coupon is valid
            $isValid = $coupon['is_active'] && !$isExpired;
            if ($coupon['start_date']) {
                $startDate = new DateTime($coupon['start_date']);
                $now = new DateTime();
                $isValid = $isValid && ($startDate <= $now);
            }
            
            // Check usage limits
            $usesLimitReached = false;
            if ($coupon['max_uses'] > 0) {
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM coupon_uses WHERE coupon_id = ?");
                    $stmt->execute([$couponId]);
                    $usesCount = $stmt->fetchColumn();
                    $usesLimitReached = $usesCount >= $coupon['max_uses'];
                } catch (PDOException $e) {
                    // Continue
                }
            }
            
            if ($coupon['end_date']):
            ?>
                <div class="stat-row">
                    <span class="stat-label">تاریخ انقضا:</span>
                    <span class="stat-value <?php echo $isExpired ? 'expired' : ''; ?>">
                        <?php echo formatDate($coupon['end_date']); ?>
                        <?php if ($isExpired): ?>
                            <span class="status-badge danger">منقضی شده</span>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <div class="stat-row">
                <span class="stat-label">وضعیت:</span>
                <span class="stat-value">
                    <?php if (!$coupon['is_active']): ?>
                        <span class="status-badge secondary">غیرفعال</span>
                    <?php elseif ($isExpired): ?>
                        <span class="status-badge danger">منقضی شده</span>
                    <?php elseif ($usesLimitReached): ?>
                        <span class="status-badge warning">حداکثر استفاده رسیده</span>
                    <?php else: ?>
                        <span class="status-badge success">فعال</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<script>
// Generate random coupon code
document.addEventListener('DOMContentLoaded', function() {
    // Add generate button for coupon code
    const codeInput = document.getElementById('code');
    const generateBtn = document.createElement('button');
    generateBtn.type = 'button';
    generateBtn.className = 'btn btn-sm btn-secondary';
    generateBtn.style.marginRight = '8px';
    generateBtn.innerHTML = '<i class="fas fa-random"></i>';
    generateBtn.title = 'تولید کد تصادفی';
    generateBtn.onclick = function() {
        const randomCode = generateRandomCode(8);
        codeInput.value = randomCode;
    };
    
    if (codeInput) {
        codeInput.parentNode.insertBefore(generateBtn, codeInput);
    }
});

// Generate random coupon code
function generateRandomCode(length) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

// Coupon functions
function updateCoupon($couponId, $data) {
    global $pdo;
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'coupon_id' && $key !== 'id' && $key !== 'created_at' && isset($value)) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($fields)) return false;
    
    $values[] = $couponId;
    
    try {
        $sql = "UPDATE coupons SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    } catch (PDOException $e) {
        return false;
    }
}
</script>

<?php require_once 'footer.php'; ?>
