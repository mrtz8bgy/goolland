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
    // Add admin
    if (isset($_POST['add_admin'])) {
        $result = createAdmin($_POST);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
    
    // Update admin
    if (isset($_POST['update_admin'])) {
        $adminId = (int)$_POST['admin_id'];
        $result = updateAdmin($adminId, $_POST);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
    
    // Delete admin
    if (isset($_POST['delete_admin'])) {
        $adminId = (int)$_POST['admin_id'];
        $result = deleteAdmin($adminId);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
    
    // Delete selected admins
    if (isset($_POST['delete_selected'])) {
        $adminIds = $_POST['admin_ids'] ?? [];
        $deletedCount = 0;
        
        foreach ($adminIds as $adminId) {
            $result = deleteAdmin($adminId);
            if ($result['success']) {
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            $success = toPersianNumbers($deletedCount) . ' مدیر با موفقیت حذف شد.';
        } else {
            $error = 'هیچ مدیری برای حذف انتخاب نشده است.';
        }
    }
    
    // Toggle active status
    if (isset($_POST['toggle_active'])) {
        $adminId = (int)$_POST['admin_id'];
        $isActive = (int)$_POST['is_active'];
        
        $result = updateAdmin($adminId, ['is_active' => $isActive ? 0 : 1]);
        
        if ($result['success']) {
            $success = 'وضعیت مدیر با موفقیت تغییر یافت.';
        } else {
            $error = $result['message'] ?? 'خطا در تغییر وضعیت مدیر.';
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

if (!empty($search)) {
    $where[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role !== 'all') {
    $where[] = "role = ?";
    $params[] = $role;
}

if ($status !== 'all') {
    $isActive = $status === 'active' ? 1 : 0;
    $where[] = "is_active = ?";
    $params[] = $isActive;
}

$whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

// Count admins
try {
    $countQuery = "SELECT COUNT(*) FROM admins" . $whereClause;
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalAdmins = (int)$stmt->fetchColumn();
    $totalPages = ceil($totalAdmins / $perPage);
} catch (PDOException $e) {
    $totalAdmins = 0;
    $totalPages = 1;
}

// Get admins
try {
    $offset = ($page - 1) * $perPage;
    $query = "SELECT * FROM admins" . $whereClause . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $admins = [];
}

// Get current admin ID
$currentAdminId = $_SESSION['admin_id'] ?? 0;

// Get statistics
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'admin' => 0,
    'manager' => 0,
    'editor' => 0,
    'viewer' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    $stats['total'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE is_active = 1");
    $stats['active'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE is_active = 0");
    $stats['inactive'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'admin'");
    $stats['admin'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'manager'");
    $stats['manager'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'editor'");
    $stats['editor'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'viewer'");
    $stats['viewer'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Use default stats
}

$pageTitle = "مدیریت مدیران";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-user-shield"></i> مدیریت مدیران</h1>
        <div class="admin-actions">
            <button class="btn btn-primary" onclick="openAddAdminModal()">
                <i class="fas fa-plus"></i> افزودن مدیر جدید
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
                <i class="fas fa-users"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['total']); ?></span>
            <span class="stat-label">کل مدیران</span>
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
            <div class="stat-icon" style="background: linear-gradient(135deg, #f44336, #e91e63);">
                <i class="fas fa-crown"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['admin']); ?></span>
            <span class="stat-label">مدیر کل</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #2196f3, #03a9f4);">
                <i class="fas fa-user-tie"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['manager']); ?></span>
            <span class="stat-label">مدیر</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #9c27b0, #673ab7);">
                <i class="fas fa-edit"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['editor']); ?></span>
            <span class="stat-label">ویرایشگر</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #607d8b, #455a64);">
                <i class="fas fa-eye"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['viewer']); ?></span>
            <span class="stat-label">مشاهده‌کننده</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="manage_admins.php">
            <div class="filter-group">
                <label for="search">جستجو</label>
                <input type="text" id="search" name="search" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="نام کاربری، ایمیل یا تلفن">
            </div>
            
            <div class="filter-group">
                <label for="role">نقش</label>
                <select id="role" name="role" class="form-control">
                    <option value="all" <?php echo $role === 'all' ? 'selected' : ''; ?>>همه نقش‌ها</option>
                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>مدیر کل</option>
                    <option value="manager" <?php echo $role === 'manager' ? 'selected' : ''; ?>>مدیر</option>
                    <option value="editor" <?php echo $role === 'editor' ? 'selected' : ''; ?>>ویرایشگر</option>
                    <option value="viewer" <?php echo $role === 'viewer' ? 'selected' : ''; ?>>مشاهده‌کننده</option>
                </select>
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
                <a href="manage_admins.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> بازنشانی
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <form method="POST" action="manage_admins.php" id="bulk-actions-form">
        <div class="bulk-actions">
            <span class="selected-count">0 مدیر انتخاب شده</span>
            <button type="submit" name="delete_selected" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف مدیران انتخابی مطمئن هستید؟')">
                <i class="fas fa-trash"></i> حذف انتخابی
            </button>
        </div>
        
        <!-- Admins Table -->
        <div class="data-table-wrapper">
            <div class="data-table-header">
                <div class="table-title">
                    <i class="fas fa-list"></i>
                    لیست مدیران (<?php echo toPersianNumbers($totalAdmins); ?>)
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
                            <th>نام کاربری</th>
                            <th>نام کامل</th>
                            <th>ایمیل</th>
                            <th>تلفن</th>
                            <th>نقش</th>
                            <th>وضعیت</th>
                            <th>تاریخ ثبت‌نام</th>
                            <th style="width: 120px;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admins)): ?>
                            <tr>
                                <td colspan="11" class="empty-message">
                                    <i class="fas fa-user-shield"></i>
                                    <p>هیچ مدیری یافت نشد</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($admins as $admin): ?>
                                <tr data-admin-id="<?php echo $admin['id']; ?>" <?php echo $admin['id'] == $currentAdminId ? 'style="background: rgba(255, 255, 0, 0.1);"' : ''; ?>>
                                    <td>
                                        <input type="checkbox" name="admin_ids[]" 
                                               value="<?php echo $admin['id']; ?>" 
                                               onchange="updateSelectedCount()" 
                                               <?php echo $admin['id'] == $currentAdminId ? 'disabled' : ''; ?>>
                                    </td>
                                    <td>
                                        <?php if ($admin['avatar']): ?>
                                            <img src="../<?php echo htmlspecialchars($admin['avatar']); ?>" 
                                                 alt="<?php echo htmlspecialchars($admin['username']); ?>" 
                                                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; background: #2e7d32; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                                <?php echo mb_substr(htmlspecialchars($admin['username']), 0, 1, 'UTF-8'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($admin['full_name'] ?? 'ندارد'); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td><?php echo toPersianNumbers($admin['phone'] ?? 'ندارد'); ?></td>
                                    <td>
                                        <span class="badge badge-primary">
                                            <?php echo getAdminRoleName($admin['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="manage_admins.php" style="display: inline;">
                                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $admin['is_active']; ?>">
                                            <button type="submit" name="toggle_active" 
                                                    class="btn btn-sm <?php echo $admin['is_active'] ? 'btn-success' : 'btn-secondary'; ?>"
                                                    title="فعال/غیرفعال" 
                                                    <?php echo $admin['id'] == $currentAdminId ? 'disabled' : ''; ?>>
                                                <i class="fas fa-<?php echo $admin['is_active'] ? 'check' : 'times'; ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td><?php echo formatDate($admin['created_at']); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <button type="button" class="action-btn edit" 
                                                    onclick="openEditAdminModal(<?php echo $admin['id']; ?>)" 
                                                    title="ویرایش" 
                                                    <?php echo $admin['id'] == $currentAdminId ? 'disabled' : ''; ?>>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" action="manage_admins.php" style="display: inline;">
                                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                <button type="submit" name="delete_admin" 
                                                        class="action-btn delete" 
                                                        title="حذف"
                                                        onclick="return confirm('آیا از حذف این مدیر مطمئن هستید؟')" 
                                                        <?php echo $admin['id'] == $currentAdminId ? 'disabled' : ''; ?>>
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
                نمایش <?php echo toPersianNumbers($offset + 1); ?> تا <?php echo toPersianNumbers(min($offset + $perPage, $totalAdmins)); ?> از <?php echo toPersianNumbers($totalAdmins); ?> مدیر
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role; ?>&status=<?php echo $status; ?>" 
                       class="page-item">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === 1 || $i === $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role; ?>&status=<?php echo $status; ?>" 
                           class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo toPersianNumbers($i); ?>
                        </a>
                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                        <span class="page-item disabled">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role; ?>&status=<?php echo $status; ?>" 
                       class="page-item">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Admin Modal -->
<div class="modal-overlay" id="add-admin-modal">
    <div class="modal form-modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> افزودن مدیر جدید</h3>
            <button type="button" class="modal-close" onclick="closeAddAdminModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="manage_admins.php">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_username">نام کاربری <span style="color: #f44336;">*</span></label>
                            <input type="text" id="add_username" name="username" 
                                   class="form-control" 
                                   placeholder="نام کاربری را وارد کنید" required>
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_email">ایمیل <span style="color: #f44336;">*</span></label>
                            <input type="email" id="add_email" name="email" 
                                   class="form-control" 
                                   placeholder="ایمیل را وارد کنید" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_password">رمز عبور <span style="color: #f44336;">*</span></label>
                            <input type="password" id="add_password" name="password" 
                                   class="form-control" 
                                   placeholder="رمز عبور را وارد کنید" required>
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_confirm_password">تایید رمز عبور <span style="color: #f44336;">*</span></label>
                            <input type="password" id="add_confirm_password" name="confirm_password" 
                                   class="form-control" 
                                   placeholder="رمز عبور را دوباره وارد کنید" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_full_name">نام کامل</label>
                            <input type="text" id="add_full_name" name="full_name" 
                                   class="form-control" 
                                   placeholder="نام کامل را وارد کنید">
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_phone">تلفن</label>
                            <input type="text" id="add_phone" name="phone" 
                                   class="form-control" 
                                   placeholder="تلفن را وارد کنید">
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_role">نقش</label>
                            <select id="add_role" name="role" class="form-control select-control">
                                <option value="admin">مدیر کل</option>
                                <option value="manager">مدیر</option>
                                <option value="editor">ویرایشگر</option>
                                <option value="viewer">مشاهده‌کننده</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="add_is_active" name="is_active" 
                                       value="1" checked>
                                <span>فعال</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="submit" name="add_admin" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    ذخیره مدیر
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeAddAdminModal()">
                    <i class="fas fa-times"></i>
                    انصراف
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal-overlay" id="edit-admin-modal">
    <div class="modal form-modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> ویرایش مدیر</h3>
            <button type="button" class="modal-close" onclick="closeEditAdminModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="manage_admins.php" id="edit-admin-form">
            <input type="hidden" name="admin_id" id="edit_admin_id">
            
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_username">نام کاربری</label>
                            <input type="text" id="edit_username" name="username" 
                                   class="form-control" 
                                   placeholder="نام کاربری را وارد کنید" required>
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_email">ایمیل</label>
                            <input type="email" id="edit_email" name="email" 
                                   class="form-control" 
                                   placeholder="ایمیل را وارد کنید" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_password">رمز عبور (خالی بگذارید برای عدم تغییر)</label>
                            <input type="password" id="edit_password" name="password" 
                                   class="form-control" 
                                   placeholder="رمز عبور جدید را وارد کنید">
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_confirm_password">تایید رمز عبور جدید</label>
                            <input type="password" id="edit_confirm_password" name="confirm_password" 
                                   class="form-control" 
                                   placeholder="رمز عبور جدید را دوباره وارد کنید">
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_full_name">نام کامل</label>
                            <input type="text" id="edit_full_name" name="full_name" 
                                   class="form-control" 
                                   placeholder="نام کامل را وارد کنید">
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_phone">تلفن</label>
                            <input type="text" id="edit_phone" name="phone" 
                                   class="form-control" 
                                   placeholder="تلفن را وارد کنید">
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_role">نقش</label>
                            <select id="edit_role" name="role" class="form-control select-control">
                                <option value="admin">مدیر کل</option>
                                <option value="manager">مدیر</option>
                                <option value="editor">ویرایشگر</option>
                                <option value="viewer">مشاهده‌کننده</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="edit_is_active" name="is_active" 
                                       value="1">
                                <span>فعال</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="submit" name="update_admin" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    ذخیره تغییرات
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeEditAdminModal()">
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
    const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]:not([disabled])');
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
        selectedCountEl.textContent = toPersianNumbers(count) + ' مدیر انتخاب شده';
    }
}

// Open add admin modal
function openAddAdminModal() {
    document.getElementById('add-admin-modal').classList.add('active');
}

// Close add admin modal
function closeAddAdminModal() {
    document.getElementById('add-admin-modal').classList.remove('active');
}

// Open edit admin modal
function openEditAdminModal(adminId) {
    // Get admin data
    const admin = <?php echo json_encode($admins); ?>.find(a => a.id == adminId);
    
    if (admin) {
        document.getElementById('edit_admin_id').value = admin.id;
        document.getElementById('edit_username').value = admin.username;
        document.getElementById('edit_email').value = admin.email;
        document.getElementById('edit_full_name').value = admin.full_name || '';
        document.getElementById('edit_phone').value = admin.phone || '';
        document.getElementById('edit_role').value = admin.role;
        document.getElementById('edit_is_active').checked = admin.is_active == 1;
        
        document.getElementById('edit-admin-modal').classList.add('active');
    }
}

// Close edit admin modal
function closeEditAdminModal() {
    document.getElementById('edit-admin-modal').classList.remove('active');
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddAdminModal();
        closeEditAdminModal();
    }
});

// Close modals when clicking outside
document.getElementById('add-admin-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddAdminModal();
    }
});

document.getElementById('edit-admin-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditAdminModal();
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});

// Form validation for add admin
function validateAddAdminForm() {
    const password = document.getElementById('add_password').value;
    const confirmPassword = document.getElementById('add_confirm_password').value;
    
    if (password !== confirmPassword) {
        alert('رمز عبور و تایید آن یکسان نیستند.');
        return false;
    }
    
    return true;
}

// Form validation for edit admin
function validateEditAdminForm() {
    const password = document.getElementById('edit_password').value;
    const confirmPassword = document.getElementById('edit_confirm_password').value;
    
    if (password !== confirmPassword) {
        alert('رمز عبور و تایید آن یکسان نیستند.');
        return false;
    }
    
    return true;
}

// Attach validation to forms
document.getElementById('add-admin-modal').querySelector('form').addEventListener('submit', function(e) {
    if (!validateAddAdminForm()) {
        e.preventDefault();
    }
});

document.getElementById('edit-admin-modal').querySelector('form').addEventListener('submit', function(e) {
    if (!validateEditAdminForm()) {
        e.preventDefault();
    }
});
</script>

<?php require_once 'footer.php'; ?>
