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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update order status
    if (isset($_POST['update_status'])) {
        $orderId = (int)$_POST['order_id'];
        $status = $_POST['status'];
        
        $result = updateOrder($orderId, ['status' => $status]);
        
        if ($result) {
            logAdminActivity('update_order_status', 'وضعیت سفارش تغییر کرد: ' . $orderId . ' به ' . $status);
            $success = 'وضعیت سفارش با موفقیت به‌روزرسانی شد.';
        } else {
            $error = 'خطا در به‌روزرسانی وضعیت سفارش.';
        }
    }
    
    // Update payment status
    if (isset($_POST['update_payment_status'])) {
        $orderId = (int)$_POST['order_id'];
        $paymentStatus = $_POST['payment_status'];
        
        $result = updateOrder($orderId, ['payment_status' => $paymentStatus]);
        
        if ($result) {
            logAdminActivity('update_payment_status', 'وضعیت پرداخت سفارش تغییر کرد: ' . $orderId . ' به ' . $paymentStatus);
            $success = 'وضعیت پرداخت سفارش با موفقیت به‌روزرسانی شد.';
        } else {
            $error = 'خطا در به‌روزرسانی وضعیت پرداخت.';
        }
    }
    
    // Delete order
    if (isset($_POST['delete_order'])) {
        $orderId = (int)$_POST['order_id'];
        
        if (deleteOrder($orderId)) {
            logAdminActivity('delete_order', 'سفارش حذف شد: ' . $orderId);
            $success = 'سفارش با موفقیت حذف شد.';
        } else {
            $error = 'خطا در حذف سفارش.';
        }
    }
    
    // Delete selected orders
    if (isset($_POST['delete_selected'])) {
        $orderIds = $_POST['order_ids'] ?? [];
        $deletedCount = 0;
        
        foreach ($orderIds as $orderId) {
            if (deleteOrder($orderId)) {
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            logAdminActivity('delete_orders', toPersianNumbers($deletedCount) . ' سفارش حذف شد');
            $success = toPersianNumbers($deletedCount) . ' سفارش با موفقیت حذف شد.';
        } else {
            $error = 'هیچ سفارشی برای حذف انتخاب نشده است.';
        }
    }
}

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';
$paymentStatus = $_GET['payment_status'] ?? 'all';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $status;
}

if ($paymentStatus !== 'all') {
    $where[] = "o.payment_status = ?";
    $params[] = $paymentStatus;
}

if (!empty($startDate)) {
    $where[] = "o.created_at >= ?";
    $params[] = $startDate;
}

if (!empty($endDate)) {
    $where[] = "o.created_at <= ?";
    $params[] = $endDate . ' 23:59:59';
}

$whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

// Count orders
try {
    $countQuery = "SELECT COUNT(*) FROM orders o LEFT JOIN users u ON o.user_id = u.id" . $whereClause;
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalOrders = (int)$stmt->fetchColumn();
    $totalPages = ceil($totalOrders / $perPage);
} catch (PDOException $e) {
    $totalOrders = 0;
    $totalPages = 1;
}

// Get orders
try {
    $offset = ($page - 1) * $perPage;
    $query = "SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone FROM orders o LEFT JOIN users u ON o.user_id = u.id" . $whereClause . " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get order items for each order
    foreach ($orders as &$order) {
        $order['items'] = getOrderItemsByOrderId($order['id']);
    }
} catch (PDOException $e) {
    $orders = [];
}

// Get statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'paid' => 0,
    'unpaid' => 0,
    'total_revenue' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $stats['total'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $stats['pending'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'");
    $stats['processing'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'shipped'");
    $stats['shipped'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'");
    $stats['delivered'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'");
    $stats['completed'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'");
    $stats['cancelled'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid'");
    $stats['paid'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status != 'paid'");
    $stats['unpaid'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'");
    $stats['total_revenue'] = (float)($stmt->fetchColumn() ?? 0);
} catch (PDOException $e) {
    // Use default stats
}

$pageTitle = "مدیریت سفارش‌ها";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-shopping-cart"></i> مدیریت سفارش‌ها</h1>
        <div class="admin-actions">
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

    <!-- Stats Cards -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #2e7d32, #4caf50);">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['total']); ?></span>
            <span class="stat-label">کل سفارش‌ها</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ff9800, #ffc107);">
                <i class="fas fa-clock"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['pending']); ?></span>
            <span class="stat-label">در انتظار</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #2196f3, #03a9f4);">
                <i class="fas fa-sync-alt"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['processing']); ?></span>
            <span class="stat-label">در حال پردازش</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4caf50, #8bc34a);">
                <i class="fas fa-truck"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['shipped']); ?></span>
            <span class="stat-label">ارسال شده</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #8bc34a, #afb42b);">
                <i class="fas fa-check-circle"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['delivered'] + $stats['completed']); ?></span>
            <span class="stat-label">تکمیل شده</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f44336, #e91e63);">
                <i class="fas fa-times-circle"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['cancelled']); ?></span>
            <span class="stat-label">کنسل شده</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #9c27b0, #673ab7);">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <span class="stat-value"><?php echo formatPrice($stats['total_revenue']); ?></span>
            <span class="stat-label">درآمد کل</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4caf50, #2e7d32);">
                <i class="fas fa-credit-card"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['paid']); ?></span>
            <span class="stat-label">پرداخت شده</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ff9800, #ff8a65);">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['unpaid']); ?></span>
            <span class="stat-label">در انتظار پرداخت</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="manage_orders.php">
            <div class="filter-group">
                <label for="search">جستجو</label>
                <input type="text" id="search" name="search" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="شماره سفارش، نام یا ایمیل مشتری">
            </div>
            
            <div class="filter-group">
                <label for="status">وضعیت سفارش</label>
                <select id="status" name="status" class="form-control">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>همه وضعیت‌ها</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>در انتظار</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>در حال پردازش</option>
                    <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>ارسال شده</option>
                    <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>تحویل داده شده</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>تکمیل شده</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>کنسل شده</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="payment_status">وضعیت پرداخت</label>
                <select id="payment_status" name="payment_status" class="form-control">
                    <option value="all" <?php echo $paymentStatus === 'all' ? 'selected' : ''; ?>>همه</option>
                    <option value="paid" <?php echo $paymentStatus === 'paid' ? 'selected' : ''; ?>>پرداخت شده</option>
                    <option value="pending" <?php echo $paymentStatus === 'pending' ? 'selected' : ''; ?>>در انتظار پرداخت</option>
                    <option value="failed" <?php echo $paymentStatus === 'failed' ? 'selected' : ''; ?>>پرداخت ناموفق</option>
                    <option value="refunded" <?php echo $paymentStatus === 'refunded' ? 'selected' : ''; ?>>عوض شده</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="start_date">از تاریخ</label>
                <input type="date" id="start_date" name="start_date" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($startDate); ?>">
            </div>
            
            <div class="filter-group">
                <label for="end_date">تا تاریخ</label>
                <input type="date" id="end_date" name="end_date" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($endDate); ?>">
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> جستجو
                </button>
                <a href="manage_orders.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> بازنشانی
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <form method="POST" action="manage_orders.php" id="bulk-actions-form">
        <div class="bulk-actions">
            <span class="selected-count">0 سفارش انتخاب شده</span>
            <button type="submit" name="delete_selected" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف سفارشات انتخابی مطمئن هستید؟')">
                <i class="fas fa-trash"></i> حذف انتخابی
            </button>
        </div>
        
        <!-- Orders Table -->
        <div class="data-table-wrapper">
            <div class="data-table-header">
                <div class="table-title">
                    <i class="fas fa-list"></i>
                    لیست سفارش‌ها (<?php echo toPersianNumbers($totalOrders); ?>)
                </div>
                <div class="table-actions">
                    <a href="export_orders.php" class="btn btn-success btn-sm">
                        <i class="fas fa-file-export"></i> صادر کردن
                    </a>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="select-all" onchange="toggleSelectAll(this)">
                            </th>
                            <th>شماره سفارش</th>
                            <th>مشتری</th>
                            <th>مبلغ</th>
                            <th>روش پرداخت</th>
                            <th>وضعیت پرداخت</th>
                            <th>وضعیت سفارش</th>
                            <th>تاریخ</th>
                            <th style="width: 120px;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="10" class="empty-message">
                                    <i class="fas fa-shopping-cart"></i>
                                    <p>هیچ سفارشی یافت نشد</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr data-order-id="<?php echo $order['id']; ?>">
                                    <td>
                                        <input type="checkbox" name="order_ids[]" 
                                               value="<?php echo $order['id']; ?>" 
                                               onchange="updateSelectedCount()">
                                    </td>
                                    <td>
                                        <strong>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                        <div style="font-size: 11px; color: #666;"><?php echo htmlspecialchars($order['order_number'] ?? ''); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($order['user_name'] ?? 'مهمان'); ?></div>
                                        <div style="font-size: 11px; color: #666;"><?php echo htmlspecialchars($order['user_email'] ?? ''); ?></div>
                                        <div style="font-size: 11px; color: #666;"><?php echo toPersianNumbers($order['user_phone'] ?? ''); ?></div>
                                    </td>
                                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                                    <td><?php echo getPaymentMethodText($order['payment_method'] ?? ''); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $order['payment_status'] ?? ''; ?>">
                                            <?php echo getPaymentStatusText($order['payment_status'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $order['status'] ?? ''; ?>">
                                            <?php echo getOrderStatusText($order['status'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($order['created_at']); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                                               class="action-btn view" 
                                               title="مشاهده جزئیات">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="order_edit.php?id=<?php echo $order['id']; ?>" 
                                               class="action-btn edit" 
                                               title="ویرایش">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="manage_orders.php" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" name="delete_order" 
                                                        class="action-btn delete" 
                                                        title="حذف"
                                                        onclick="return confirm('آیا از حذف این سفارش مطمئن هستید؟')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination-wrapper">
            <div class="pagination-info">
                نمایش <?php echo toPersianNumbers($offset + 1); ?> تا <?php echo toPersianNumbers(min($offset + $perPage, $totalOrders)); ?> از <?php echo toPersianNumbers($totalOrders); ?> سفارش
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&payment_status=<?php echo $paymentStatus; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
                       class="page-item">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === 1 || $i === $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&payment_status=<?php echo $paymentStatus; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
                           class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo toPersianNumbers($i); ?>
                        </a>
                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                        <span class="page-item disabled">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&payment_status=<?php echo $paymentStatus; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
                       class="page-item">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Toggle select all
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateSelectedCount();
}

// Update selected count
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    const count = checkboxes.length;
    const selectedCountEl = document.querySelector('.selected-count');
    
    if (selectedCountEl) {
        selectedCountEl.textContent = toPersianNumbers(count) + ' سفارش انتخاب شده';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>

<?php require_once 'footer.php'; ?>
