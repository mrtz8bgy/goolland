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
            'slug' => trim($_POST['slug']),
            'description' => trim($_POST['description'] ?? ''),
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Handle image upload
        if (!empty($_FILES['image_path']['tmp_name'])) {
            $uploadPath = 'assets/images/uploads/';
            $imagePath = uploadImage($_FILES['image_path'], $uploadPath);
            if ($imagePath) {
                $categoryData['image_path'] = $imagePath;
            }
        }
        
        try {
            $categoryId = createBlogCategory($categoryData);
            
            if ($categoryId) {
                logAdminActivity('create_post_category', 'دسته‌بندی بلاگ جدید ایجاد شد: ' . $categoryData['name']);
                $success = 'دسته‌بندی با موفقیت ایجاد شد.';
            } else {
                $error = 'خطا در ایجاد دسته‌بندی.';
            }
        } catch (Exception $e) {
            $error = 'خطا: ' . $e->getMessage();
        }
    }
    
    // Update category
    if (isset($_POST['update_category'])) {
        $categoryId = (int)$_POST['category_id'];
        $categoryData = [
            'name' => trim($_POST['name']),
            'slug' => trim($_POST['slug']),
            'description' => trim($_POST['description'] ?? ''),
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Handle image upload
        if (!empty($_FILES['image_path']['tmp_name'])) {
            $uploadPath = 'assets/images/uploads/';
            $imagePath = uploadImage($_FILES['image_path'], $uploadPath);
            if ($imagePath) {
                $categoryData['image_path'] = $imagePath;
            }
        }
        
        try {
            $result = updateBlogCategory($categoryId, $categoryData);
            
            if ($result) {
                logAdminActivity('update_post_category', 'دسته‌بندی بلاگ به‌روزرسانی شد: ' . $categoryData['name']);
                $success = 'دسته‌بندی با موفقیت به‌روزرسانی شد.';
            } else {
                $error = 'خطا در به‌روزرسانی دسته‌بندی.';
            }
        } catch (Exception $e) {
            $error = 'خطا: ' . $e->getMessage();
        }
    }
    
    // Delete category
    if (isset($_POST['delete_category'])) {
        $categoryId = (int)$_POST['category_id'];
        
        try {
            $result = deleteBlogCategory($categoryId);
            
            if ($result) {
                logAdminActivity('delete_post_category', 'دسته‌بندی بلاگ حذف شد: ' . $categoryId);
                $success = 'دسته‌بندی با موفقیت حذف شد.';
            } else {
                $error = 'خطا در حذف دسته‌بندی. ابتدا مقالات این دسته‌بندی را جابجا کنید.';
            }
        } catch (Exception $e) {
            $error = 'خطا: ' . $e->getMessage();
        }
    }
    
    // Delete selected categories
    if (isset($_POST['delete_selected'])) {
        $categoryIds = $_POST['category_ids'] ?? [];
        $deletedCount = 0;
        
        foreach ($categoryIds as $categoryId) {
            try {
                if (deleteBlogCategory($categoryId)) {
                    $deletedCount++;
                }
            } catch (Exception $e) {
                // Continue with next category
            }
        }
        
        if ($deletedCount > 0) {
            logAdminActivity('delete_post_categories', toPersianNumbers($deletedCount) . ' دسته‌بندی بلاگ حذف شد');
            $success = toPersianNumbers($deletedCount) . ' دسته‌بندی با موفقیت حذف شد.';
        } else {
            $error = 'هیچ دسته‌بندی برای حذف انتخاب نشده است.';
        }
    }
    
    // Toggle active status
    if (isset($_POST['toggle_active'])) {
        $categoryId = (int)$_POST['category_id'];
        $isActive = (int)$_POST['is_active'];
        
        $result = updateBlogCategory($categoryId, ['is_active' => $isActive ? 0 : 1]);
        
        if ($result) {
            logAdminActivity('toggle_post_category_active', 'وضعیت دسته‌بندی بلاگ تغییر کرد: ' . $categoryId);
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
    $countQuery = "SELECT COUNT(*) FROM blog_categories" . $whereClause;
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
    $query = "SELECT * FROM blog_categories" . $whereClause . " ORDER BY parent_id ASC, sort_order ASC, name ASC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Get all categories for parent select
$allCategories = [];
try {
    $stmt = $pdo->query("SELECT * FROM blog_categories WHERE parent_id = 0 ORDER BY name");
    $allCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allCategories = [];
}

// Get statistics
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'with_posts' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_categories");
    $stats['total'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_categories WHERE is_active = 1");
    $stats['active'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_categories WHERE is_active = 0");
    $stats['inactive'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT category_id) FROM blog_posts WHERE category_id > 0");
    $stats['with_posts'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Use default stats
}

$pageTitle = "مدیریت دسته‌بندی‌های بلاگ";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-folder-open"></i> مدیریت دسته‌بندی‌های بلاگ</h1>
        <div class="admin-actions">
            <button class="btn btn-primary" onclick="openAddCategoryModal()">
                <i class="fas fa-plus"></i> افزودن دسته‌بندی جدید
            </button>
            <a href="manage_posts.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> لیست مقالات
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
                <i class="fas fa-file-alt"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['with_posts']); ?></span>
            <span class="stat-label">با مقاله</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="manage_post_categories.php">
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
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo $parentId === $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
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
                <a href="manage_post_categories.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> بازنشانی
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <form method="POST" action="manage_post_categories.php" id="bulk-actions-form">
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
                            <th>تعداد مقالات</th>
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
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts WHERE category_id = ?");
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
                                        <form method="POST" action="manage_post_categories.php" style="display: inline;">
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
                                            <form method="POST" action="manage_post_categories.php" style="display: inline;">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" name="delete_category" 
                                                        class="action-btn delete" 
                                                        title="حذف"
                                                        onclick="return confirm('آیا از حذف این دسته‌بندی مطمئن هستید؟\n\nتوجه: ابتدا مقالات این دسته‌بندی را جابجا کنید.')">
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
        
        <form method="POST" action="manage_post_categories.php" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_name">نام دسته‌بندی <span style="color: #f44336;">*</span></label>
                    <input type="text" id="add_name" name="name" 
                           class="form-control" 
                           placeholder="نام دسته‌بندی را وارد کنید" required>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_slug">نامک (Slug)</label>
                            <input type="text" id="add_slug" name="slug" 
                                   class="form-control" 
                                   placeholder="نامک دسته‌بندی را وارد کنید">
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_parent_id">دسته‌بندی والد</label>
                            <select id="add_parent_id" name="parent_id" class="form-control select-control">
                                <option value="0">دسته‌بندی اصلی</option>
                                <?php foreach ($allCategories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="add_description">توضیحات</label>
                    <textarea id="add_description" name="description" 
                              class="form-control textarea-control" 
                              placeholder="توضیحات دسته‌بندی را وارد کنید"></textarea>
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
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="add_sort_order">ترتیب</label>
                            <input type="number" id="add_sort_order" name="sort_order" 
                                   class="form-control" 
                                   value="0" min="0">
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
        
        <form method="POST" action="manage_post_categories.php" enctype="multipart/form-data" id="edit-category-form">
            <input type="hidden" name="category_id" id="edit_category_id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_name">نام دسته‌بندی <span style="color: #f44336;">*</span></label>
                    <input type="text" id="edit_name" name="name" 
                           class="form-control" 
                           placeholder="نام دسته‌بندی را وارد کنید" required>
                </div>
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_slug">نامک (Slug)</label>
                            <input type="text" id="edit_slug" name="slug" 
                                   class="form-control" 
                                   placeholder="نامک دسته‌بندی را وارد کنید">
                        </div>
                    </div>
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
                </div>
                
                <div class="form-group">
                    <label for="edit_description">توضیحات</label>
                    <textarea id="edit_description" name="description" 
                              class="form-control textarea-control" 
                              placeholder="توضیحات دسته‌بندی را وارد کنید"></textarea>
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
                
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="edit_sort_order">ترتیب</label>
                            <input type="number" id="edit_sort_order" name="sort_order" 
                                   class="form-control" 
                                   min="0">
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
    // Get category data
    const category = <?php echo json_encode($categories); ?>.find(c => c.id == categoryId);
    
    if (category) {
        document.getElementById('edit_category_id').value = category.id;
        document.getElementById('edit_name').value = category.name;
        document.getElementById('edit_slug').value = category.slug || '';
        document.getElementById('edit_description').value = category.description || '';
        document.getElementById('edit_parent_id').value = category.parent_id || 0;
        document.getElementById('edit_sort_order').value = category.sort_order || 0;
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

// Auto-generate slug from name
document.getElementById('add_name').addEventListener('input', function() {
    const name = this.value;
    const slug = name.toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]+/g, '')
        .replace(/--+/g, '-')
        .replace(/^-+|-+$/g, '');
    
    document.getElementById('add_slug').value = slug;
});

document.getElementById('edit_name').addEventListener('input', function() {
    const name = this.value;
    const slug = name.toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]+/g, '')
        .replace(/--+/g, '-')
        .replace(/^-+|-+$/g, '');
    
    document.getElementById('edit_slug').value = slug;
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});

// Blog category functions
function createBlogCategory($data) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO blog_categories (name, slug, description, parent_id, image_path, is_active, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['description'] ?? '',
            $data['parent_id'] ?? 0,
            $data['image_path'] ?? '',
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

function updateBlogCategory($categoryId, $data) {
    global $pdo;
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'category_id' && $key !== 'created_at' && isset($value)) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($fields)) return false;
    
    $values[] = $categoryId;
    
    try {
        $sql = "UPDATE blog_categories SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    } catch (PDOException $e) {
        return false;
    }
}

function deleteBlogCategory($categoryId) {
    global $pdo;
    try {
        // Check if category has posts
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $postCount = $stmt->fetchColumn();
        
        if ($postCount > 0) {
            return false; // Cannot delete category with posts
        }
        
        // Delete category image
        $stmt = $pdo->prepare("SELECT image_path FROM blog_categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $imagePath = $stmt->fetchColumn();
        
        if ($imagePath && file_exists(ROOT_PATH . '/' . $imagePath)) {
            unlink(ROOT_PATH . '/' . $imagePath);
        }
        
        // Delete category
        $stmt = $pdo->prepare("DELETE FROM blog_categories WHERE id = ?");
        return $stmt->execute([$categoryId]);
    } catch (PDOException $e) {
        return false;
    }
}

function updateBlogPost($postId, $data) {
    global $pdo;
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'post_id' && $key !== 'id' && $key !== 'created_at' && isset($value)) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($fields)) return false;
    
    $values[] = $postId;
    
    try {
        $sql = "UPDATE blog_posts SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    } catch (PDOException $e) {
        return false;
    }
}
</script>

<?php require_once 'footer.php'; ?>
