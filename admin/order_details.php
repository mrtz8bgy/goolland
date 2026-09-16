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
if (!in_array($adminRole, ['admin', 'manager'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

// Get order ID
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order details
$order = getOrderDetails($orderId);

if (!$order) {
    header("Location: manage_orders.php");
    exit;
}

// Get order items
$orderItems = getOrderItemsByOrderId($orderId);

// Get user details
$user = getUserById($order['user_id'] ?? 0);

// Get shipping address
$shippingAddress = '';
if ($order['shipping_address']) {
    $shippingAddress = $order['shipping_address'];
}

// Get billing address
$billingAddress = '';
if ($order['billing_address']) {
    $billingAddress = $order['billing_address'];
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'];
    
    $result = updateOrder($orderId, ['status' => $status]);
    
    if ($result) {
        // Refresh order
        $order = getOrderDetails($orderId);
        $orderItems = getOrderItemsByOrderId($orderId);
        
        logAdminActivity('update_order_status', 'وضعیت سفارش تغییر کرد: ' . $orderId . ' به ' . $status);
        $success = 'وضعیت سفارش با موفقیت به‌روزرسانی شد.';
    } else {
        $error = 'خطا در به‌روزرسانی وضعیت سفارش.';
    }
}

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_status'])) {
    $paymentStatus = $_POST['payment_status'];
    
    $result = updateOrder($orderId, ['payment_status' => $paymentStatus]);
    
    if ($result) {
        // Refresh order
        $order = getOrderDetails($orderId);
        
        logAdminActivity('update_payment_status', 'وضعیت پرداخت سفارش تغییر کرد: ' . $orderId . ' به ' . $paymentStatus);
        $success = 'وضعیت پرداخت سفارش با موفقیت به‌روزرسانی شد.';
    } else {
        $error = 'خطا در به‌روزرسانی وضعیت پرداخت.';
    }
}

// Handle notes update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notes'])) {
    $notes = trim($_POST['notes']);
    
    $result = updateOrder($orderId, ['notes' => $notes]);
    
    if ($result) {
        // Refresh order
        $order = getOrderDetails($orderId);
        
        logAdminActivity('update_order_notes', 'یادداشت سفارش به‌روزرسانی شد: ' . $orderId);
        $success = 'یادداشت سفارش با موفقیت به‌روزرسانی شد.';
    } else {
        $error = 'خطا در به‌روزرسانی یادداشت.';
    }
}

// Handle tracking number update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tracking'])) {
    $trackingNumber = trim($_POST['tracking_number']);
    
    $result = updateOrder($orderId, ['tracking_number' => $trackingNumber]);
    
    if ($result) {
        // Refresh order
        $order = getOrderDetails($orderId);
        
        logAdminActivity('update_tracking_number', 'شماره پیگیری سفارش به‌روزرسانی شد: ' . $orderId);
        $success = 'شماره پیگیری با موفقیت به‌روزرسانی شد.';
    } else {
        $error = 'خطا در به‌روزرسانی شماره پیگیری.';
    }
}

$pageTitle = "جزئیات سفارش #" . str_pad($orderId, 6, '0', STR_PAD_LEFT);
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-file-invoice"></i> جزئیات سفارش #<?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?></h1>
        <div class="admin-actions">
            <a href="manage_orders.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> لیست سفارش‌ها
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

    <!-- Order Status -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #2196f3, #03a9f4);">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-content">
                <span class="stat-value">#<?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?></span>
                <span class="stat-label">شماره سفارش</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, <?php echo $order['status'] === 'completed' ? '#4caf50' : ($order['status'] === 'cancelled' ? '#f44336' : '#ff9800'); ?>, <?php echo $order['status'] === 'completed' ? '#2e7d32' : ($order['status'] === 'cancelled' ? '#d32f2f' : '#e68a00'); ?>);">
                <i class="fas fa-<?php 
                    switch ($order['status']) {
                        case 'pending': echo 'clock'; break;
                        case 'processing': echo 'sync-alt'; break;
                        case 'shipped': echo 'truck'; break;
                        case 'delivered': echo 'box-open'; break;
                        case 'completed': echo 'check-circle'; break;
                        case 'cancelled': echo 'times-circle'; break;
                        default: echo 'question-circle';
                    }
                ?>"></i>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo getOrderStatusText($order['status']); ?></span>
                <span class="stat-label">وضعیت سفارش</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, <?php echo $order['payment_status'] === 'paid' ? '#4caf50' : '#ff9800'; ?>, <?php echo $order['payment_status'] === 'paid' ? '#2e7d32' : '#e68a00'; ?>);">
                <i class="fas fa-<?php echo $order['payment_status'] === 'paid' ? 'check-circle' : ($order['payment_status'] === 'failed' ? 'times-circle' : 'clock'); ?>"></i>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo getPaymentStatusText($order['payment_status']); ?></span>
                <span class="stat-label">وضعیت پرداخت</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #2e7d32, #4caf50);">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo formatPrice($order['total_amount']); ?></span>
                <span class="stat-label">مبلغ کل</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #9c27b0, #673ab7);">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo formatDate($order['created_at']); ?></span>
                <span class="stat-label">تاریخ سفارش</span>
            </div>
        </div>
    </div>

    <!-- Order Content -->
    <div class="dashboard-content-row">
        <!-- Customer Information -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-user"></i> اطلاعات مشتری</h3>
            </div>
            
            <div class="customer-info">
                <div class="info-row">
                    <div class="info-label">نام:</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['user_name'] ?? 'مهمان'); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">ایمیل:</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['user_email'] ?? 'ندارد'); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">تلفن:</div>
                    <div class="info-value"><?php echo toPersianNumbers($order['user_phone'] ?? 'ندارد'); ?></div>
                </div>
                
                <?php if ($user): ?>
                    <div class="info-row">
                        <div class="info-label">شناسه کاربر:</div>
                        <div class="info-value">#<?php echo toPersianNumbers($user['id']); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-box-open"></i> آیتم‌های سفارش</h3>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>محصول</th>
                            <th>قیمت واحد</th>
                            <th>تعداد</th>
                            <th>مبلغ کل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orderItems)): ?>
                            <tr>
                                <td colspan="4" class="empty-message">هیچ آیتمی یافت نشد</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orderItems as $item): ?>
                                <?php $product = getProductById($item['product_id']); ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <?php if ($product && $product['image_path']): ?>
                                                <img src="../<?php echo htmlspecialchars($product['image_path']); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['name'] ?? $item['product_name']); ?>" 
                                                     style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                            <?php else: ?>
                                                <div style="width: 40px; height: 40px; background: #f5f5f5; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-image" style="color: #999; font-size: 16px;"></i>
                                                </div>
                                            <?php endif; ?>
                                            <span><?php echo htmlspecialchars($product['name'] ?? $item['product_name']); ?></span>
                                        </div>
                                        <?php if ($product && $product['sku']): ?>
                                            <div style="font-size: 11px; color: #666; margin-top: 4px;">
                                                کد: <?php echo htmlspecialchars($product['sku']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatPrice($item['unit_price']); ?></td>
                                    <td><?php echo toPersianNumbers($item['quantity']); ?></td>
                                    <td><?php echo formatPrice($item['total_price']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" style="text-align: right;">مبلغ:</th>
                            <th><?php echo formatPrice($order['total_amount']); ?></th>
                        </tr>
                        <tr>
                            <th colspan="3" style="text-align: right;">هزینه ارسال:</th>
                            <th><?php echo formatPrice($order['shipping_cost'] ?? 0); ?></th>
                        </tr>
                        <?php if ($order['discount_amount'] > 0): ?>
                            <tr>
                                <th colspan="3" style="text-align: right;">تخفیف:</th>
                                <th>-<?php echo formatPrice($order['discount_amount']); ?></th>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th colspan="3" style="text-align: right;">مبلغ نهایی:</th>
                            <th><?php echo formatPrice($order['final_amount'] ?? $order['total_amount']); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-file-invoice-dollar"></i> خلاصه سفارش</h3>
            </div>
            
            <div class="order-summary">
                <div class="summary-row">
                    <div class="summary-label">شماره سفارش:</div>
                    <div class="summary-value">#<?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?></div>
                </div>
                
                <div class="summary-row">
                    <div class="summary-label">تاریخ سفارش:</div>
                    <div class="summary-value"><?php echo formatDate($order['created_at']); ?></div>
                </div>
                
                <div class="summary-row">
                    <div class="summary-label">روش پرداخت:</div>
                    <div class="summary-value"><?php echo getPaymentMethodText($order['payment_method'] ?? ''); ?></div>
                </div>
                
                <div class="summary-row">
                    <div class="summary-label">وضعیت پرداخت:</div>
                    <div class="summary-value">
                        <span class="status-badge <?php echo $order['payment_status'] ?? ''; ?>">
                            <?php echo getPaymentStatusText($order['payment_status'] ?? ''); ?>
                        </span>
                    </div>
                </div>
                
                <div class="summary-row">
                    <div class="summary-label">وضعیت سفارش:</div>
                    <div class="summary-value">
                        <span class="status-badge <?php echo $order['status'] ?? ''; ?>">
                            <?php echo getOrderStatusText($order['status'] ?? ''); ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($order['tracking_number']): ?>
                    <div class="summary-row">
                        <div class="summary-label">شماره پیگیری:</div>
                        <div class="summary-value"><?php echo htmlspecialchars($order['tracking_number']); ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="summary-row">
                    <div class="summary-label">مبلغ کل:</div>
                    <div class="summary-value"><?php echo formatPrice($order['total_amount']); ?></div>
                </div>
                
                <div class="summary-row">
                    <div class="summary-label">هزینه ارسال:</div>
                    <div class="summary-value"><?php echo formatPrice($order['shipping_cost'] ?? 0); ?></div>
                </div>
                
                <?php if ($order['discount_amount'] > 0): ?>
                    <div class="summary-row">
                        <div class="summary-label">تخفیف:</div>
                        <div class="summary-value">-<?php echo formatPrice($order['discount_amount']); ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="summary-row total">
                    <div class="summary-label">مبلغ نهایی:</div>
                    <div class="summary-value"><?php echo formatPrice($order['final_amount'] ?? $order['total_amount']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Shipping Information -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-truck"></i> اطلاعات ارسال</h3>
            </div>
            
            <div class="shipping-info">
                <?php if ($shippingAddress): ?>
                    <div class="info-row">
                        <div class="info-label">آدرس:</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($shippingAddress)); ?></div>
                    </div>
                <?php else: ?>
                    <div class="empty-message">آدرسی ثبت نشده است</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Billing Information -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-credit-card"></i> اطلاعات صورتحساب</h3>
            </div>
            
            <div class="billing-info">
                <?php if ($billingAddress): ?>
                    <div class="info-row">
                        <div class="info-label">آدرس:</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($billingAddress)); ?></div>
                    </div>
                <?php else: ?>
                    <div class="empty-message">آدرسی ثبت نشده است</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Order Notes -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-sticky-note"></i> یادداشت‌ها</h3>
            </div>
            
            <div class="notes-section">
                <?php if ($order['notes']): ?>
                    <div class="note-item">
                        <div class="note-content"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
                        <div class="note-meta">
                            <span>تاریخ: <?php echo formatDate($order['created_at']); ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-message">یادداشتی ثبت نشده است</div>
                <?php endif; ?>
                
                <form method="POST" action="order_details.php?id=<?php echo $orderId; ?>" style="margin-top: 16px;">
                    <div class="form-group">
                        <textarea name="notes" class="form-control textarea-control" 
                                  placeholder="یادداشت جدید را وارد کنید"><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="update_notes" class="btn btn-primary btn-sm">
                            <i class="fas fa-save"></i>
                            ذخیره یادداشت
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tracking Number -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-shipping-fast"></i> شماره پیگیری</h3>
            </div>
            
            <form method="POST" action="order_details.php?id=<?php echo $orderId; ?>">
                <div class="form-group">
                    <input type="text" name="tracking_number" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>" 
                           placeholder="شماره پیگیری را وارد کنید">
                </div>
                <div class="form-group">
                    <button type="submit" name="update_tracking" class="btn btn-primary btn-sm">
                        <i class="fas fa-save"></i>
                        ذخیره شماره پیگیری
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Update Status -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-exchange-alt"></i> به‌روزرسانی وضعیت</h3>
            </div>
            
            <div class="status-update">
                <form method="POST" action="order_details.php?id=<?php echo $orderId; ?>" style="margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="status">وضعیت سفارش</label>
                        <select id="status" name="status" class="form-control select-control">
                            <option value="pending" <?php echo ($order['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>
                                در انتظار
                            </option>
                            <option value="processing" <?php echo ($order['status'] ?? '') === 'processing' ? 'selected' : ''; ?>>
                                در حال پردازش
                            </option>
                            <option value="shipped" <?php echo ($order['status'] ?? '') === 'shipped' ? 'selected' : ''; ?>>
                                ارسال شده
                            </option>
                            <option value="delivered" <?php echo ($order['status'] ?? '') === 'delivered' ? 'selected' : ''; ?>>
                                تحویل داده شده
                            </option>
                            <option value="completed" <?php echo ($order['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>
                                تکمیل شده
                            </option>
                            <option value="cancelled" <?php echo ($order['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>
                                کنسل شده
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                            <i class="fas fa-sync-alt"></i>
                            به‌روزرسانی وضعیت سفارش
                        </button>
                    </div>
                </form>
                
                <form method="POST" action="order_details.php?id=<?php echo $orderId; ?>">
                    <div class="form-group">
                        <label for="payment_status">وضعیت پرداخت</label>
                        <select id="payment_status" name="payment_status" class="form-control select-control">
                            <option value="pending" <?php echo ($order['payment_status'] ?? '') === 'pending' ? 'selected' : ''; ?>>
                                در انتظار پرداخت
                            </option>
                            <option value="paid" <?php echo ($order['payment_status'] ?? '') === 'paid' ? 'selected' : ''; ?>>
                                پرداخت شده
                            </option>
                            <option value="failed" <?php echo ($order['payment_status'] ?? '') === 'failed' ? 'selected' : ''; ?>>
                                پرداخت ناموفق
                            </option>
                            <option value="refunded" <?php echo ($order['payment_status'] ?? '') === 'refunded' ? 'selected' : ''; ?>>
                                عوض شده
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="update_payment_status" class="btn btn-primary btn-sm">
                            <i class="fas fa-credit-card"></i>
                            به‌روزرسانی وضعیت پرداخت
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
