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
if (!in_array($adminRole, ['admin', 'editor'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add category
    if (isset($_POST['add_category'])) {
        $categoryData = [
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? '')
        ];
        
        // Handle image upload
        if (!empty($_FILES['image_path']['tmp_name'])) {
            $uploadPath = 'assets/images/uploads/';
            $imagePath = uploadImage($_FILES['image_path'], $uploadPath);
            if ($imagePath) {
                $categoryData['image_path'] = $imagePath;
            }
        }
        
        $categoryId = createCategory($categoryData);
        
        if ($categoryId) {
            logAdminActivity('create_category', 'دسته‌بندی جدید ایجاد شد: ' . $categoryData['name']);
            $success = 'دسته‌بندی با موفقیت ایجاد شد.';
        } else {
            $error = 'خطا در ایجاد دسته‌بندی.';
        }
    }
    
    // Update category
    if (isset($_POST['update_category'])) {
        $categoryId = (int)$_POST['category_id'];
        $categoryData = [
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? '')
        ];
        
        // Handle image upload
        if (!empty($_FILES['image_path']['tmp_name'])) {
            $uploadPath = 'assets/images/uploads/';
            $imagePath = uploadImage($_FILES['image_path'], $uploadPath);
            if ($imagePath) {
                $categoryData['image_path'] = $imagePath;
            }
        }
        
        $result = updateCategory($categoryId, $categoryData);
        
        if ($result) {
            logAdminActivity('update_category', 'دسته‌بندی به‌روزرسانی شد: ' . $categoryData['name']);
            $success = 'دسته‌بندی با موفقیت به‌روزرسانی شد.';
        } else {
            $error = 'خطا در به‌روزرسانی دسته‌بندی.';
        }
    }
    
    // Delete category
    if (isset($_POST['delete_category'])) {
        $categoryId = (int)$_POST['category_id'];
        
        if (deleteCategory($categoryId)) {
            logAdminActivity('delete_category', 'دسته‌بندی حذف شد: ' . $categoryId);
            $success = 'دسته‌بندی با موفقیت حذف شد.';
        } else {
            $error = 'خطا در حذف دسته‌بندی. ابتدا محصولات این دسته‌بندی را جابجا کنید.';
        }
    }
    
    // Delete selected categories
    if (isset($_POST['delete_selected'])) {
        $categoryIds = $_POST['category_ids'] ?? [];
        $deletedCount = 0;
        
        foreach ($categoryIds as $categoryId) {
            if (deleteCategory($categoryId)) {
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            logAdminActivity('delete_categories', toPersianNumbers($deletedCount) . ' دسته‌بندی حذف شد');
            $success = toPersianNumbers($deletedCount) . ' دسته‌بندی با موفقیت حذف شد.';
        } else {
            $error = 'هیچ دسته‌بندی برای حذف انتخاب نشده است.';
        }
    }
    
    // Toggle active status
    if (isset($_POST['toggle_active'])) {
        $categoryId = (int)$_POST['category_id'];
        $isActive = (int)$_POST['is_active'];
        
        $result = updateCategory($categoryId, ['is_active' => $isActive ? 0 : 1]);
        
        if ($result) {
            logAdminActivity('toggle_category_active', 'وضعیت دسته‌بندی تغییر کرد: ' . $categoryId);
            $success = 'وضعیت دسته‌بندی با موفقیت تغییر یافت.';
        } else {
            $error = 'خطا در تغییر وضعیت دسته‌بندی.';
        }
    }
}

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$parentId = isset($_GET['parent']) ? (int)$_GET['parent'] : 0;
$status = $_GET['status'] ?? 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($parentId > 0) {
    $where[] = "parent_id = ?";
    $params[] = $parentId;
} elseif ($parentId === -1) {
    $where[] = "parent_id = 0";
}

if ($status !== 'all') {
    $isActive = $status === 'active' ? 1 : 0;
    $where[] = "is_active = ?";
    $params[] = $isActive;
}

$whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

// Count categories
try {
    $countQuery = "SELECT COUNT(*) FROM categories" . $whereClause;
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalCategories = (int)$stmt->fetchColumn();
    $totalPages = ceil($totalCategories / $perPage);
} catch (PDOException $e) {
    $totalCategories = 0;
    $totalPages = 1;
}

// Get categories
try {
    $offset = ($page - 1) * $perPage;
    $query = "SELECT * FROM categories" . $whereClause . " ORDER BY parent_id ASC, sort_order ASC, name ASC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Get all categories for parent select
$allCategories = getAllCategories();

// Get statistics
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'with_products' => 0,
    'without_products' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $stats['total'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories WHERE is_active = 1");
    $stats['active'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories WHERE is_active = 0");
    $stats['inactive'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT category_id) FROM products WHERE category_id > 0");
    $stats['with_products'] = (int)$stmt->fetchColumn();
    
    $stats['without_products'] = $stats['total'] - $stats['with_products'];
} catch (PDOException $e) {
    // Use default stats
}

$pageTitle = "مدیریت دسته‌بندی‌ها";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-folder-open"></i> مدیریت دسته‌بندی‌ها</h1>
        <div class="admin-actions">
            <button class="btn btn-primary" onclick="openAddCategoryModal()">
                <i class="fas fa-plus"></i> افزودن دسته‌بندی جدید
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
                <i class="fas fa-folder"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['total']); ?></span>
            <span class="stat-label">کل دسته‌بندی‌ها</span>
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
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['inactive']); ?></span>
            <span class="stat-label">غیرفعال</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #2196f3, #03a9f4);">
                <i class="fas fa-box-open"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['with_products']); ?></span>
            <span class="stat-label">با محصول</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #9c27b0, #673ab7);">
                <i class="fas fa-folder-open"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['without_products']); ?></span>
            <span class="stat-label">بدون محصول</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="manage_categories.php">
            <div class="filter-group">
                <label for="search">جستجو</label>
                <input type="text" id="search" name="search" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="نام یا توضیحات دسته‌بندی">
            </div>
            
            <div class="filter-group">
                <label for="parent">والد</label>
                <select id="parent" name="parent" class="form-control">
                    <option value="-1" <?php echo $parentId === -1 ? 'selected' : ''; ?>>دسته‌بندی‌های اصلی</option>
                    <option value="0" <?php echo $parentId === 0 ? 'selected' : ''; ?>>همه</option>
                    <?php foreach ($allCategories as $category): ?>
                        <?php if ($category['parent_id'] == 0): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $parentId === $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
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
                <a href="manage_categories.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> بازنشانی
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <form method="POST" action="manage_categories.php" id="bulk-actions-form">
        <div class="bulk-actions">
            <span class="selected-count">0 دسته‌بندی انتخاب شده</span>
            <button type="submit" name="delete_selected" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف دسته‌بندی‌های انتخابی مطمئن هستید؟')">
                <i class="fas fa-trash"></i> حذف انتخابی
            </button>
        </div>
        
        <!-- Categories Table -->
        <div class="data-table-wrapper">
            <div class="data-table-header">
                <div class="table-title">
                    <i class="fas fa-list"></i>
                    لیست دسته‌بندی‌ها (<?php echo toPersianNumbers($totalCategories); ?>)
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="select-all" onchange="toggleSelectAll(this)">
                            </th>
                            <th style="width: 60px;">تصویر</th>
                            <th>نام دسته‌بندی</th>
                            <th>والد</th>
                            <th>تعداد محصولات</th>
                            <th>ترتیب</th>
                            <th>وضعیت</th>
                            <th>تاریخ</th>
                            <th style="width: 120px;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="9" class="empty-message">
                                    <i class="fas fa-folder-open"></i>
                                    <p>هیچ دسته‌بندی یافت نشد</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <tr data-category-id="<?php echo $category['id']; ?>">
                                    <td>
                                        <input type="checkbox" name="category_ids[]" 
                                               value="<?php echo $category['id']; ?>" 
                                               onchange="updateSelectedCount()">
                                    </td>
                                    <td>
                                        <?php if ($category['image_path']): ?>
                                            <img src="../<?php echo htmlspecialchars($category['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                                 style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; background: #f5f5f5; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image" style="color: #999; font-size: 16px;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                        <?php if ($category['parent_id'] > 0): ?>
                                            <span style="font-size: 11px; color: #666; display: block;">
                                                زیر دسته: <?php 
                                                $parent = getCategoryById($category['parent_id']);
                                                echo htmlspecialchars($parent['name'] ?? 'ندارد');
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($category['parent_id'] ? getCategoryById($category['parent_id'])['name'] : 'ندارد'); ?></td>
                                    <td>
                                        <?php
                                        try {
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                                            $stmt->execute([$category['id']]);
                                            $count = $stmt->fetchColumn();
                                            echo toPersianNumbers($count);
                                        } catch (PDOException $e) {
                                            echo '0';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo toPersianNumbers($category['sort_order']); ?></td>
                                    <td>
                                        <form method="POST" action="manage_categories.php" style="display: inline;">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $category['is_active']; ?>">
                                            <button type="submit" name="toggle_active" 
                                                    class="btn btn-sm <?php echo $category['is_active'] ? 'btn-success' : 'btn-secondary'; ?>"
                                                    title="فعال/غیرفعال">
                                                <i class="fas fa-<?php echo $category['is_active'] ? 'check' : 'times'; ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td><?php echo formatDate($category['created_at']); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <button type="button" class="action-btn edit" 
                                                    onclick="openEditCategoryModal(<?php echo $category['id']; ?>)" 
                                                    title="ویرایش">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" action="manage_categories.php" style="display: inline;">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" name="delete_category" 
                                                        class="action-btn delete" 
                                                        title="حذف"
                                                        onclick="return confirm('آیا از حذف این دسته‌بندی مطمئن هستید؟\n\nتوجه: ابتدا محصولات این دسته‌بندی را جابجا کنید.')">
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
                نمایش <?php echo toPersianNumbers($offset + 1); ?> تا <?php echo toPersianNumbers(min($offset + $perPage, $totalCategories)); ?> از <?php echo toPersianNumbers($totalCategories); ?> دسته‌بندی
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&parent=<?php echo $parentId; ?>&status=<?php echo $status; ?>" 
                       class="page-item">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === 1 || $i === $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&parent=<?php echo $parentId; ?>&status=<?php echo $status; ?>" 
                           class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo toPersianNumbers($i); ?>
                        </a>
                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                        <span class="page-item disabled">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&parent=<?php echo $parentId; ?>&status=<?php echo $status; ?>" 
                       class="page-item">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Category Modal -->
<div class="modal-overlay" id="add-category-modal">
    <div class="modal form-modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> افزودن دسته‌بندی جدید</h3>
            <button type="button" class="modal-close" onclick="closeAddCategoryModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="manage_categories.php" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_name">نام دسته‌بندی <span style="color: #f44336;">*</span></label>
                    <input type="text" id="add_name" name="name" 
                           class="form-control" 
                           placeholder="نام دسته‌بندی را وارد کنید" required>
                </div>
                
                <div class="form-group">
                    <label for="add_description">توضیحات</label>
                    <textarea id="add_description" name="description" 
                              class="form-control textarea-control" 
                              placeholder="توضیحات دسته‌بندی را وارد کنید"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_parent_id">دسته‌بندی والد</label>
                            <select id="add_parent_id" name="parent_id" class="form-control select-control">
                                <option value="0">دسته‌بندی اصلی</option>
                                <?php foreach ($allCategories as $category): ?>
                                    <?php if ($category['parent_id'] == 0): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_sort_order">ترتیب</label>
                            <input type="number" id="add_sort_order" name="sort_order" 
                                   class="form-control" 
                                   value="0" min="0">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="add_image_path">تصویر دسته‌بندی</label>
                    <div class="file-upload-area">
                        <i class="fas fa-image"></i>
                        <span class="upload-text">تصویر را آپلود کنید</span>
                        <span class="upload-hint">فرمت‌های مجاز: JPG, PNG, GIF, WebP</span>
                        <input type="file" id="add_image_path" name="image_path" accept="image/*">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>تنظیمات سئو</label>
                    <div class="form-row">
                        <div class="form-col full">
                            <div class="form-group">
                                <label for="add_meta_title">عنوان سئو</label>
                                <input type="text" id="add_meta_title" name="meta_title" 
                                       class="form-control" 
                                       placeholder="عنوان سئو">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col full">
                            <div class="form-group">
                                <label for="add_meta_description">توضیحات سئو</label>
                                <textarea id="add_meta_description" name="meta_description" 
                                          class="form-control textarea-control" 
                                          placeholder="توضیحات سئو"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col full">
                            <div class="form-group">
                                <label for="add_meta_keywords">کلمات کلیدی سئو</label>
                                <textarea id="add_meta_keywords" name="meta_keywords" 
                                          class="form-control textarea-control" 
                                          placeholder="کلمات کلیدی را با کاما جدا کنید"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" 
                               value="1" checked>
                        <span>فعال</span>
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="submit" name="add_category" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    ذخیره دسته‌بندی
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeAddCategoryModal()">
                    <i class="fas fa-times"></i>
                    انصراف
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal-overlay" id="edit-category-modal">
    <div class="modal form-modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> ویرایش دسته‌بندی</h3>
            <button type="button" class="modal-close" onclick="closeEditCategoryModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="manage_categories.php" enctype="multipart/form-data" id="edit-category-form">
            <input type="hidden" name="category_id" id="edit_category_id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_name">نام دسته‌بندی <span style="color: #f44336;">*</span></label>
                    <input type="text" id="edit_name" name="name" 
                           class="form-control" 
                           placeholder="نام دسته‌بندی را وارد کنید" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">توضیحات</label>
                    <textarea id="edit_description" name="description" 
                              class="form-control textarea-control" 
                              placeholder="توضیحات دسته‌بندی را وارد کنید"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_parent_id">دسته‌بندی والد</label>
                            <select id="edit_parent_id" name="parent_id" class="form-control select-control">
                                <option value="0">دسته‌بندی اصلی</option>
                                <?php foreach ($allCategories as $category): ?>
                                    <?php if ($category['id'] != ($editCategory['id'] ?? 0)): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_sort_order">ترتیب</label>
                            <input type="number" id="edit_sort_order" name="sort_order" 
                                   class="form-control" 
                                   min="0">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_image_path">تصویر دسته‌بندی</label>
                    <div class="file-upload-area">
                        <i class="fas fa-image"></i>
                        <span class="upload-text">تصویر را آپلود کنید</span>
                        <span class="upload-hint">فرمت‌های مجاز: JPG, PNG, GIF, WebP</span>
                        <input type="file" id="edit_image_path" name="image_path" accept="image/*">
                    </div>
                    <div id="edit-image-preview" style="margin-top: 12px;"></div>
                </div>
                
                <div class="form-group">
                    <label>تنظیمات سئو</label>
                    <div class="form-row">
                        <div class="form-col full">
                            <div class="form-group">
                                <label for="edit_meta_title">عنوان سئو</label>
                                <input type="text" id="edit_meta_title" name="meta_title" 
                                       class="form-control" 
                                       placeholder="عنوان سئو">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col full">
                            <div class="form-group">
                                <label for="edit_meta_description">توضیحات سئو</label>
                                <textarea id="edit_meta_description" name="meta_description" 
                                          class="form-control textarea-control" 
                                          placeholder="توضیحات سئو"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col full">
                            <div class="form-group">
                                <label for="edit_meta_keywords">کلمات کلیدی سئو</label>
                                <textarea id="edit_meta_keywords" name="meta_keywords" 
                                          class="form-control textarea-control" 
                                          placeholder="کلمات کلیدی را با کاما جدا کنید"></textarea>
                            </div>
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
                <button type="submit" name="update_category" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    ذخیره تغییرات
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeEditCategoryModal()">
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
        selectedCountEl.textContent = toPersianNumbers(count) + ' دسته‌بندی انتخاب شده';
    }
}

// Open add category modal
function openAddCategoryModal() {
    document.getElementById('add-category-modal').classList.add('active');
}

// Close add category modal
function closeAddCategoryModal() {
    document.getElementById('add-category-modal').classList.remove('active');
}

// Open edit category modal
function openEditCategoryModal(categoryId) {
    // Get category data via AJAX or use existing data
    const category = <?php echo json_encode($categories); ?>.find(c => c.id == categoryId);
    
    if (category) {
        document.getElementById('edit_category_id').value = category.id;
        document.getElementById('edit_name').value = category.name;
        document.getElementById('edit_description').value = category.description || '';
        document.getElementById('edit_parent_id').value = category.parent_id || 0;
        document.getElementById('edit_sort_order').value = category.sort_order || 0;
        document.getElementById('edit_meta_title').value = category.meta_title || '';
        document.getElementById('edit_meta_description').value = category.meta_description || '';
        document.getElementById('edit_meta_keywords').value = category.meta_keywords || '';
        document.getElementById('edit_is_active').checked = category.is_active == 1;
        
        // Show image preview
        const previewContainer = document.getElementById('edit-image-preview');
        if (category.image_path) {
            previewContainer.innerHTML = `
                <div class="file-preview-item">
                    <img src="../${category.image_path}" alt="تصویر دسته‌بندی">
                </div>
            `;
        } else {
            previewContainer.innerHTML = '';
        }
        
        document.getElementById('edit-category-modal').classList.add('active');
    }
}

// Close edit category modal
function closeEditCategoryModal() {
    document.getElementById('edit-category-modal').classList.remove('active');
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddCategoryModal();
        closeEditCategoryModal();
    }
});

// Close modals when clicking outside
document.getElementById('add-category-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddCategoryModal();
    }
});

document.getElementById('edit-category-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditCategoryModal();
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>

<?php require_once 'footer.php'; ?>
