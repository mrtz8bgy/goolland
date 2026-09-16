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
    // Add coupon
    if (isset($_POST['add_coupon'])) {
        $couponData = [
            'code' => strtoupper(trim($_POST['code'])),
            'discount_type' => $_POST['discount_type'] ?? 'percentage',
            'discount_value' => (float)toEnglishNumbers($_POST['discount_value']),
            'min_order_amount' => (float)toEnglishNumbers($_POST['min_order_amount'] ?? 0),
            'max_discount_amount' => (float)toEnglishNumbers($_POST['max_discount_amount'] ?? 0),
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'end_date' => $_POST['end_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'usage_limit' => (int)($_POST['usage_limit'] ?? 0),
            'usage_count' => 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        $result = createCoupon($couponData);
        
        if ($result['success']) {
            logAdminActivity('create_coupon', 'کوپن جدید ایجاد شد: ' . $couponData['code']);
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
    
    // Update coupon
    if (isset($_POST['update_coupon'])) {
        $couponId = (int)$_POST['coupon_id'];
        $couponData = [
            'code' => strtoupper(trim($_POST['code'])),
            'discount_type' => $_POST['discount_type'] ?? 'percentage',
            'discount_value' => (float)toEnglishNumbers($_POST['discount_value']),
            'min_order_amount' => (float)toEnglishNumbers($_POST['min_order_amount'] ?? 0),
            'max_discount_amount' => (float)toEnglishNumbers($_POST['max_discount_amount'] ?? 0),
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'end_date' => $_POST['end_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'usage_limit' => (int)($_POST['usage_limit'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        $result = updateCoupon($couponId, $couponData);
        
        if ($result['success']) {
            logAdminActivity('update_coupon', 'کوپن به‌روزرسانی شد: ' . $couponData['code']);
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
    
    // Delete coupon
    if (isset($_POST['delete_coupon'])) {
        $couponId = (int)$_POST['coupon_id'];
        
        if (deleteCoupon($couponId)) {
            logAdminActivity('delete_coupon', 'کوپن حذف شد: ' . $couponId);
            $success = 'کوپن با موفقیت حذف شد.';
        } else {
            $error = 'خطا در حذف کوپن.';
        }
    }
    
    // Delete selected coupons
    if (isset($_POST['delete_selected'])) {
        $couponIds = $_POST['coupon_ids'] ?? [];
        $deletedCount = 0;
        
        foreach ($couponIds as $couponId) {
            if (deleteCoupon($couponId)) {
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            logAdminActivity('delete_coupons', toPersianNumbers($deletedCount) . ' کوپن حذف شد');
            $success = toPersianNumbers($deletedCount) . ' کوپن با موفقیت حذف شد.';
        } else {
            $error = 'هیچ کوپنی برای حذف انتخاب نشده است.';
        }
    }
    
    // Toggle active status
    if (isset($_POST['toggle_active'])) {
        $couponId = (int)$_POST['coupon_id'];
        $isActive = (int)$_POST['is_active'];
        
        $result = updateCoupon($couponId, ['is_active' => $isActive ? 0 : 1]);
        
        if ($result['success']) {
            logAdminActivity('toggle_coupon_active', 'وضعیت کوپن تغییر کرد: ' . $couponId);
            $success = 'وضعیت کوپن با موفقیت تغییر یافت.';
        } else {
            $error = $result['message'] ?? 'خطا در تغییر وضعیت کوپن.';
        }
    }
}

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';
$type = $_GET['type'] ?? 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(code LIKE ? OR discount_type LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status !== 'all') {
    $isActive = $status === 'active' ? 1 : 0;
    $where[] = "is_active = ?";
    $params[] = $isActive;
}

if ($type !== 'all') {
    $where[] = "discount_type = ?";
    $params[] = $type;
}

$whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

// Count coupons
try {
    $countQuery = "SELECT COUNT(*) FROM coupons" . $whereClause;
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalCoupons = (int)$stmt->fetchColumn();
    $totalPages = ceil($totalCoupons / $perPage);
} catch (PDOException $e) {
    $totalCoupons = 0;
    $totalPages = 1;
}

// Get coupons
try {
    $offset = ($page - 1) * $perPage;
    $query = "SELECT * FROM coupons" . $whereClause . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $coupons = [];
}

// Get statistics
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'percentage' => 0,
    'fixed' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM coupons");
    $stats['total'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM coupons WHERE is_active = 1");
    $stats['active'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM coupons WHERE is_active = 0");
    $stats['inactive'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM coupons WHERE discount_type = 'percentage'");
    $stats['percentage'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM coupons WHERE discount_type = 'fixed'");
    $stats['fixed'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Use default stats
}

// Generate random coupon code
function generateCouponCode($length = 8) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

$pageTitle = "مدیریت کوپن‌ها";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-ticket-alt"></i> مدیریت کوپن‌ها</h1>
        <div class="admin-actions">
            <button class="btn btn-primary" onclick="openAddCouponModal()">
                <i class="fas fa-plus"></i> افزودن کوپن جدید
            </button>
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
                <i class="fas fa-ticket-alt"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['total']); ?></span>
            <span class="stat-label">کل کوپن‌ها</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4caf50, #8bc34a);">
                <i class="fas fa-check-circle"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['active']); ?></span>
            <span class="stat-label">فعال</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ff9800, #ffc107);">
                <i class="fas fa-times-circle"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['inactive']); ?></span>
            <span class="stat-label">غیرفعال</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #2196f3, #03a9f4);">
                <i class="fas fa-percent"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['percentage']); ?></span>
            <span class="stat-label">درصدی</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #9c27b0, #673ab7);">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['fixed']); ?></span>
            <span class="stat-label">ثابت</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="manage_coupons.php">
            <div class="filter-group">
                <label for="search">جستجو</label>
                <input type="text" id="search" name="search" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="کد کوپن">
            </div>
            
            <div class="filter-group">
                <label for="status">وضعیت</label>
                <select id="status" name="status" class="form-control">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>همه</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>فعال</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>غیرفعال</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="type">نوع تخفیف</label>
                <select id="type" name="type" class="form-control">
                    <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>همه</option>
                    <option value="percentage" <?php echo $type === 'percentage' ? 'selected' : ''; ?>>درصدی</option>
                    <option value="fixed" <?php echo $type === 'fixed' ? 'selected' : ''; ?>>ثابت</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> جستجو
                </button>
                <a href="manage_coupons.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> بازنشانی
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <form method="POST" action="manage_coupons.php" id="bulk-actions-form">
        <div class="bulk-actions">
            <span class="selected-count">0 کوپن انتخاب شده</span>
            <button type="submit" name="delete_selected" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف کوپن‌های انتخابی مطمئن هستید؟')">
                <i class="fas fa-trash"></i> حذف انتخابی
            </button>
        </div>
        
        <!-- Coupons Table -->
        <div class="data-table-wrapper">
            <div class="data-table-header">
                <div class="table-title">
                    <i class="fas fa-list"></i>
                    لیست کوپن‌ها (<?php echo toPersianNumbers($totalCoupons); ?>)
                </div>
                <div class="table-actions">
                    <a href="manage_coupons.php" class="btn btn-success btn-sm">
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
                            <th>کد کوپن</th>
                            <th>نوع تخفیف</th>
                            <th>مقدار تخفیف</th>
                            <th>حداقل سفارش</th>
                            <th>حداکثر تخفیف</th>
                            <th>محدودیت استفاده</th>
                            <th>استفاده شده</th>
                            <th>تاریخ انقضا</th>
                            <th>وضعیت</th>
                            <th style="width: 120px;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($coupons)): ?>
                            <tr>
                                <td colspan="11" class="empty-message">
                                    <i class="fas fa-ticket-alt"></i>
                                    <p>هیچ کوپنی یافت نشد</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($coupons as $coupon): ?>
                                <tr data-coupon-id="<?php echo $coupon['id']; ?>">
                                    <td>
                                        <input type="checkbox" name="coupon_ids[]" 
                                               value="<?php echo $coupon['id']; ?>" 
                                               onchange="updateSelectedCount()">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($coupon['code']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $coupon['discount_type'] === 'percentage' ? 'info' : 'success'; ?>">
                                            <?php echo $coupon['discount_type'] === 'percentage' ? 'درصدی' : 'ثابت'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo toPersianNumbers($coupon['discount_value']); ?>
                                        <?php echo $coupon['discount_type'] === 'percentage' ? '%' : 'تومان'; ?>
                                    </td>
                                    <td><?php echo formatPrice($coupon['min_order_amount']); ?></td>
                                    <td><?php echo formatPrice($coupon['max_discount_amount']); ?></td>
                                    <td>
                                        <?php echo $coupon['usage_limit'] > 0 ? toPersianNumbers($coupon['usage_limit']) : 'نامحدود'; ?>
                                    </td>
                                    <td><?php echo toPersianNumbers($coupon['usage_count']); ?></td>
                                    <td><?php echo formatDate($coupon['end_date']); ?></td>
                                    <td>
                                        <form method="POST" action="manage_coupons.php" style="display: inline;">
                                            <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $coupon['is_active']; ?>">
                                            <button type="submit" name="toggle_active" 
                                                    class="btn btn-sm <?php echo $coupon['is_active'] ? 'btn-success' : 'btn-secondary'; ?>"
                                                    title="فعال/غیرفعال">
                                                <i class="fas fa-<?php echo $coupon['is_active'] ? 'check' : 'times'; ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button type="button" class="action-btn edit" 
                                                    onclick="openEditCouponModal(<?php echo $coupon['id']; ?>)" 
                                                    title="ویرایش">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" action="manage_coupons.php" style="display: inline;">
                                                <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                                <button type="submit" name="delete_coupon" 
                                                        class="action-btn delete" 
                                                        title="حذف"
                                                        onclick="return confirm('آیا از حذف این کوپن مطمئن هستید؟')">
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
                نمایش <?php echo toPersianNumbers($offset + 1); ?> تا <?php echo toPersianNumbers(min($offset + $perPage, $totalCoupons)); ?> از <?php echo toPersianNumbers($totalCoupons); ?> کوپن
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&type=<?php echo $type; ?>" 
                       class="page-item">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === 1 || $i === $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&type=<?php echo $type; ?>" 
                           class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo toPersianNumbers($i); ?>
                        </a>
                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                        <span class="page-item disabled">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&type=<?php echo $type; ?>" 
                       class="page-item">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Coupon Modal -->
<div class="modal-overlay" id="add-coupon-modal">
    <div class="modal form-modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> افزودن کوپن جدید</h3>
            <button type="button" class="modal-close" onclick="closeAddCouponModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="manage_coupons.php">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_code">کد کوپن <span style="color: #f44336;">*</span></label>
                            <div class="input-wrapper" style="display: flex; gap: 8px;">
                                <input type="text" id="add_code" name="code" 
                                       class="form-control" 
                                       value="<?php echo generateCouponCode(); ?>" 
                                       placeholder="کد کوپن را وارد کنید" required>
                                <button type="button" class="btn btn-secondary" onclick="generateRandomCode('add_code')" title="تولید کد تصادفی">
                                    <i class="fas fa-random"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_discount_type">نوع تخفیف</label>
                            <select id="add_discount_type" name="discount_type" class="form-control select-control">
                                <option value="percentage">درصدی</option>
                                <option value="fixed">ثابت (تومان)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_discount_value">مقدار تخفیف <span style="color: #f44336;">*</span></label>
                            <input type="text" id="add_discount_value" name="discount_value" 
                                   class="form-control" 
                                   value="" 
                                   placeholder="مقدار تخفیف" required>
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_min_order_amount">حداقل مبلغ سفارش</label>
                            <input type="text" id="add_min_order_amount" name="min_order_amount" 
                                   class="form-control" 
                                   value="0" 
                                   placeholder="حداقل مبلغ سفارش">
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_max_discount_amount">حداکثر مقدار تخفیف</label>
                            <input type="text" id="add_max_discount_amount" name="max_discount_amount" 
                                   class="form-control" 
                                   value="0" 
                                   placeholder="حداکثر مقدار تخفیف">
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_usage_limit">محدودیت استفاده</label>
                            <input type="text" id="add_usage_limit" name="usage_limit" 
                                   class="form-control" 
                                   value="0" 
                                   placeholder="محدودیت استفاده (0 = نامحدود)">
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_start_date">تاریخ شروع</label>
                            <input type="date" id="add_start_date" name="start_date" 
                                   class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_end_date">تاریخ انقضا</label>
                            <input type="date" id="add_end_date" name="end_date" 
                                   class="form-control" 
                                   value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="add_is_active" name="is_active" 
                               value="1" checked>
                        <span>فعال</span>
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="submit" name="add_coupon" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    ذخیره کوپن
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeAddCouponModal()">
                    <i class="fas fa-times"></i>
                    انصراف
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Coupon Modal -->
<div class="modal-overlay" id="edit-coupon-modal">
    <div class="modal form-modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> ویرایش کوپن</h3>
            <button type="button" class="modal-close" onclick="closeEditCouponModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="manage_coupons.php" id="edit-coupon-form">
            <input type="hidden" name="coupon_id" id="edit_coupon_id">
            
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_code">کد کوپن <span style="color: #f44336;">*</span></label>
                            <input type="text" id="edit_code" name="code" 
                                   class="form-control" 
                                   placeholder="کد کوپن را وارد کنید" required>
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_discount_type">نوع تخفیف</label>
                            <select id="edit_discount_type" name="discount_type" class="form-control select-control">
                                <option value="percentage">درصدی</option>
                                <option value="fixed">ثابت (تومان)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_discount_value">مقدار تخفیف <span style="color: #f44336;">*</span></label>
                            <input type="text" id="edit_discount_value" name="discount_value" 
                                   class="form-control" 
                                   placeholder="مقدار تخفیف" required>
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_min_order_amount">حداقل مبلغ سفارش</label>
                            <input type="text" id="edit_min_order_amount" name="min_order_amount" 
                                   class="form-control" 
                                   placeholder="حداقل مبلغ سفارش">
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_max_discount_amount">حداکثر مقدار تخفیف</label>
                            <input type="text" id="edit_max_discount_amount" name="max_discount_amount" 
                                   class="form-control" 
                                   placeholder="حداکثر مقدار تخفیف">
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_usage_limit">محدودیت استفاده</label>
                            <input type="text" id="edit_usage_limit" name="usage_limit" 
                                   class="form-control" 
                                   placeholder="محدودیت استفاده (0 = نامحدود)">
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_start_date">تاریخ شروع</label>
                            <input type="date" id="edit_start_date" name="start_date" 
                                   class="form-control">
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_end_date">تاریخ انقضا</label>
                            <input type="date" id="edit_end_date" name="end_date" 
                                   class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="edit_is_active" name="is_active" 
                               value="1">
                        <span>فعال</span>
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="submit" name="update_coupon" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    ذخیره تغییرات
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeEditCouponModal()">
                    <i class="fas fa-times"></i>
                    انصراف
                </button>
            </div>
        </form>
    </div>
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
        selectedCountEl.textContent = toPersianNumbers(count) + ' کوپن انتخاب شده';
    }
}

// Generate random code
function generateRandomCode(inputId) {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = '';
    for (let i = 0; i < 8; i++) {
        code += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    document.getElementById(inputId).value = code;
}

// Open add coupon modal
function openAddCouponModal() {
    document.getElementById('add-coupon-modal').classList.add('active');
}

// Close add coupon modal
function closeAddCouponModal() {
    document.getElementById('add-coupon-modal').classList.remove('active');
}

// Open edit coupon modal
function openEditCouponModal(couponId) {
    // Get coupon data
    const coupon = <?php echo json_encode($coupons); ?>.find(c => c.id == couponId);
    
    if (coupon) {
        document.getElementById('edit_coupon_id').value = coupon.id;
        document.getElementById('edit_code').value = coupon.code;
        document.getElementById('edit_discount_type').value = coupon.discount_type;
        document.getElementById('edit_discount_value').value = toPersianNumbers(coupon.discount_value);
        document.getElementById('edit_min_order_amount').value = toPersianNumbers(coupon.min_order_amount);
        document.getElementById('edit_max_discount_amount').value = toPersianNumbers(coupon.max_discount_amount);
        document.getElementById('edit_usage_limit').value = toPersianNumbers(coupon.usage_limit);
        document.getElementById('edit_start_date').value = coupon.start_date;
        document.getElementById('edit_end_date').value = coupon.end_date;
        document.getElementById('edit_is_active').checked = coupon.is_active == 1;
        
        document.getElementById('edit-coupon-modal').classList.add('active');
    }
}

// Close edit coupon modal
function closeEditCouponModal() {
    document.getElementById('edit-coupon-modal').classList.remove('active');
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddCouponModal();
        closeEditCouponModal();
    }
});

// Close modals when clicking outside
document.getElementById('add-coupon-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddCouponModal();
    }
});

document.getElementById('edit-coupon-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditCouponModal();
    }
});

// Format number inputs
document.querySelectorAll('#add_discount_value, #add_min_order_amount, #add_max_discount_amount, #add_usage_limit, #edit_discount_value, #edit_min_order_amount, #edit_max_discount_amount, #edit_usage_limit').forEach(input => {
    input.addEventListener('input', function() {
        // Remove all non-digit characters
        let value = this.value.replace(/\D/g, '');
        
        // Format with commas
        if (value.length > 0) {
            value = value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        this.value = value;
    });
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>

<?php require_once 'footer.php'; ?>
