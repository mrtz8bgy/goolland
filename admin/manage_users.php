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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete user
    if (isset($_POST['delete_user'])) {
        $userId = (int)$_POST['user_id'];
        
        if (deleteUser($userId)) {
            logAdminActivity('delete_user', 'کاربر حذف شد: ' . $userId);
            $success = 'کاربر با موفقیت حذف شد.';
        } else {
            $error = 'خطا در حذف کاربر.';
        }
    }
    
    // Delete selected users
    if (isset($_POST['delete_selected'])) {
        $userIds = $_POST['user_ids'] ?? [];
        $deletedCount = 0;
        
        foreach ($userIds as $userId) {
            if (deleteUser($userId)) {
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            logAdminActivity('delete_users', toPersianNumbers($deletedCount) . ' کاربر حذف شد');
            $success = toPersianNumbers($deletedCount) . ' کاربر با موفقیت حذف شد.';
        } else {
            $error = 'هیچ کاربری برای حذف انتخاب نشده است.';
        }
    }
    
    // Toggle active status
    if (isset($_POST['toggle_active'])) {
        $userId = (int)$_POST['user_id'];
        $isActive = (int)$_POST['is_active'];
        
        $result = updateUserProfile($userId, ['is_active' => $isActive ? 0 : 1]);
        
        if ($result['success']) {
            logAdminActivity('toggle_user_active', 'وضعیت کاربر تغییر کرد: ' . $userId);
            $success = 'وضعیت کاربر با موفقیت تغییر یافت.';
        } else {
            $error = $result['message'] ?? 'خطا در تغییر وضعیت کاربر.';
        }
    }
}

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;

// Build query
$where = [];
$params = [];

$where[] = "is_admin = 0"; // Only regular users, not admins

if (!empty($search)) {
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status !== 'all') {
    $isActive = $status === 'active' ? 1 : 0;
    $where[] = "is_active = ?";
    $params[] = $isActive;
}

$whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

// Count users
try {
    $countQuery = "SELECT COUNT(*) FROM users" . $whereClause;
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalUsers = (int)$stmt->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);
} catch (PDOException $e) {
    $totalUsers = 0;
    $totalPages = 1;
}

// Get users
try {
    $offset = ($page - 1) * $perPage;
    $query = "SELECT * FROM users" . $whereClause . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}

// Get statistics
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'new_this_month' => 0,
    'with_orders' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
    $stats['total'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND is_active = 1");
    $stats['active'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND is_active = 0");
    $stats['inactive'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND YEAR(created_at) = ? AND MONTH(created_at) = ?");
    $stmt->execute([date('Y'), date('m')]);
    $stats['new_this_month'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM orders WHERE user_id > 0");
    $stats['with_orders'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Use default stats
}

$pageTitle = "مدیریت کاربران";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-users"></i> مدیریت کاربران</h1>
        <div class="admin-actions">
            <a href="add_user.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> افزودن کاربر جدید
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

    <!-- Stats Cards -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #2e7d32, #4caf50);">
                <i class="fas fa-users"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['total']); ?></span>
            <span class="stat-label">کل کاربران</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4caf50, #8bc34a);">
                <i class="fas fa-user-check"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['active']); ?></span>
            <span class="stat-label">فعال</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ff9800, #ffc107);">
                <i class="fas fa-user-times"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['inactive']); ?></span>
            <span class="stat-label">غیرفعال</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #2196f3, #03a9f4);">
                <i class="fas fa-user-plus"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['new_this_month']); ?></span>
            <span class="stat-label">کاربران جدید این ماه</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #9c27b0, #673ab7);">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['with_orders']); ?></span>
            <span class="stat-label">با سفارش</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="manage_users.php">
            <div class="filter-group">
                <label for="search">جستجو</label>
                <input type="text" id="search" name="search" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="نام، ایمیل یا تلفن کاربر">
            </div>
            
            <div class="filter-group">
                <label for="status">وضعیت</label>
                <select id="status" name="status" class="form-control">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>همه</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>فعال</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>غیرفعال</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> جستجو
                </button>
                <a href="manage_users.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> بازنشانی
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <form method="POST" action="manage_users.php" id="bulk-actions-form">
        <div class="bulk-actions">
            <span class="selected-count">0 کاربر انتخاب شده</span>
            <button type="submit" name="delete_selected" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف کاربران انتخابی مطمئن هستید؟')">
                <i class="fas fa-trash"></i> حذف انتخابی
            </button>
        </div>
        
        <!-- Users Table -->
        <div class="data-table-wrapper">
            <div class="data-table-header">
                <div class="table-title">
                    <i class="fas fa-list"></i>
                    لیست کاربران (<?php echo toPersianNumbers($totalUsers); ?>)
                </div>
                <div class="table-actions">
                    <a href="export_users.php" class="btn btn-success btn-sm">
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
                            <th style="width: 60px;">آواتار</th>
                            <th>نام</th>
                            <th>ایمیل</th>
                            <th>تلفن</th>
                            <th>سفارش‌ها</th>
                            <th>تاریخ ثبت‌نام</th>
                            <th>وضعیت</th>
                            <th style="width: 120px;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="10" class="empty-message">
                                    <i class="fas fa-users"></i>
                                    <p>هیچ کاربری یافت نشد</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr data-user-id="<?php echo $user['id']; ?>">
                                    <td>
                                        <input type="checkbox" name="user_ids[]" 
                                               value="<?php echo $user['id']; ?>" 
                                               onchange="updateSelectedCount()">
                                    </td>
                                    <td>
                                        <?php if ($user['avatar']): ?>
                                            <img src="../<?php echo htmlspecialchars($user['avatar']); ?>" 
                                                 alt="<?php echo htmlspecialchars($user['name']); ?>" 
                                                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; background: #2e7d32; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                                <?php echo mb_substr(htmlspecialchars($user['name']), 0, 1, 'UTF-8'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo toPersianNumbers($user['phone'] ?? 'ندارد'); ?></td>
                                    <td>
                                        <?php
                                        try {
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
                                            $stmt->execute([$user['id']]);
                                            $count = $stmt->fetchColumn();
                                            echo toPersianNumbers($count);
                                        } catch (PDOException $e) {
                                            echo '0';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                    <td>
                                        <form method="POST" action="manage_users.php" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $user['is_active']; ?>">
                                            <button type="submit" name="toggle_active" 
                                                    class="btn btn-sm <?php echo $user['is_active'] ? 'btn-success' : 'btn-secondary'; ?>"
                                                    title="فعال/غیرفعال">
                                                <i class="fas fa-<?php echo $user['is_active'] ? 'check' : 'times'; ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="user_details.php?id=<?php echo $user['id']; ?>" 
                                               class="action-btn view" 
                                               title="مشاهده جزئیات">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" 
                                               class="action-btn edit" 
                                               title="ویرایش">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="manage_users.php" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user" 
                                                        class="action-btn delete" 
                                                        title="حذف"
                                                        onclick="return confirm('آیا از حذف این کاربر مطمئن هستید؟')">
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
                نمایش <?php echo toPersianNumbers($offset + 1); ?> تا <?php echo toPersianNumbers(min($offset + $perPage, $totalUsers)); ?> از <?php echo toPersianNumbers($totalUsers); ?> کاربر
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>" 
                       class="page-item">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === 1 || $i === $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>" 
                           class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo toPersianNumbers($i); ?>
                        </a>
                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                        <span class="page-item disabled">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>" 
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
        selectedCountEl.textContent = toPersianNumbers(count) + ' کاربر انتخاب شده';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>

<?php require_once 'footer.php'; ?>
