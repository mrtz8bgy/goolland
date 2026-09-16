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
    // Delete product
    if (isset($_POST['delete_product'])) {
        $productId = $_POST['product_id'] ?? 0;
        
        if (deleteProduct($productId)) {
            logAdminActivity('delete_product', 'محصول حذف شد: ' . $productId);
            $success = 'محصول با موفقیت حذف شد.';
        } else {
            $error = 'خطا در حذف محصول.';
        }
    }
    
    // Delete selected products
    if (isset($_POST['delete_selected'])) {
        $productIds = $_POST['product_ids'] ?? [];
        $deletedCount = 0;
        
        foreach ($productIds as $productId) {
            if (deleteProduct($productId)) {
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            logAdminActivity('delete_products', toPersianNumbers($deletedCount) . ' محصول حذف شد');
            $success = toPersianNumbers($deletedCount) . ' محصول با موفقیت حذف شد.';
        } else {
            $error = 'هیچ محصولی برای حذف انتخاب نشده است.';
        }
    }
    
    // Toggle featured status
    if (isset($_POST['toggle_featured'])) {
        $productId = $_POST['product_id'] ?? 0;
        $isFeatured = $_POST['is_featured'] ?? 0;
        
        $result = updateProduct($productId, ['is_featured' => $isFeatured ? 0 : 1]);
        
        if ($result) {
            logAdminActivity('toggle_featured', 'وضعیت ویژه محصول تغییر کرد: ' . $productId);
            $success = 'وضعیت ویژه محصول با موفقیت تغییر یافت.';
        } else {
            $error = 'خطا در تغییر وضعیت محصول.';
        }
    }
    
    // Toggle active status
    if (isset($_POST['toggle_active'])) {
        $productId = $_POST['product_id'] ?? 0;
        $isActive = $_POST['is_active'] ?? 0;
        
        $result = updateProduct($productId, ['is_active' => $isActive ? 0 : 1]);
        
        if ($result) {
            logAdminActivity('toggle_active', 'وضعیت فعال محصول تغییر کرد: ' . $productId);
            $success = 'وضعیت فعال محصول با موفقیت تغییر یافت.';
        } else {
            $error = 'خطا در تغییر وضعیت محصول.';
        }
    }
}

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status = $_GET['status'] ?? 'all';
$stockStatus = $_GET['stock'] ?? 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}

if ($status !== 'all') {
    $isActive = $status === 'active' ? 1 : 0;
    $where[] = "p.is_active = ?";
    $params[] = $isActive;
}

if ($stockStatus !== 'all') {
    if ($stockStatus === 'in_stock') {
        $where[] = "p.stock > 0";
    } elseif ($stockStatus === 'out_of_stock') {
        $where[] = "p.stock = 0";
    } elseif ($stockStatus === 'low_stock') {
        $where[] = "p.stock > 0 AND p.stock <= 5";
    }
}

$whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

// Count products
try {
    $countQuery = "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id = c.id" . $whereClause;
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalProducts = (int)$stmt->fetchColumn();
    $totalPages = ceil($totalProducts / $perPage);
} catch (PDOException $e) {
    $totalProducts = 0;
    $totalPages = 1;
}

// Get products
try {
    $offset = ($page - 1) * $perPage;
    $query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id" . $whereClause . " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}

// Get categories
$categories = getAllCategories();

// Get statistics
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'in_stock' => 0,
    'out_of_stock' => 0,
    'featured' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $stats['total'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
    $stats['active'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 0");
    $stats['inactive'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0");
    $stats['in_stock'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0");
    $stats['out_of_stock'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_featured = 1");
    $stats['featured'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Use default stats
}

$pageTitle = "مدیریت محصولات";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-box-open"></i> مدیریت محصولات</h1>
        <div class="admin-actions">
            <a href="add_product.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> افزودن محصول جدید
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
                <i class="fas fa-boxes"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['total']); ?></span>
            <span class="stat-label">کل محصولات</span>
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
                <i class="fas fa-star"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['featured']); ?></span>
            <span class="stat-label">ویژه</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #8bc34a, #4caf50);">
                <i class="fas fa-check"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['in_stock']); ?></span>
            <span class="stat-label">موجود</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f44336, #e91e63);">
                <i class="fas fa-times-circle"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['out_of_stock']); ?></span>
            <span class="stat-label">ناموجود</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="manage_products.php">
            <div class="filter-group">
                <label for="search">جستجو</label>
                <input type="text" id="search" name="search" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="نام، توضیحات یا SKU محصول">
            </div>
            
            <div class="filter-group">
                <label for="category">دسته‌بندی</label>
                <select id="category" name="category" class="form-control">
                    <option value="0">همه دسته‌بندی‌ها</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo $categoryId === $category['id'] ? 'selected' : ''; ?>>
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
            
            <div class="filter-group">
                <label for="stock">موجودی</label>
                <select id="stock" name="stock" class="form-control">
                    <option value="all" <?php echo $stockStatus === 'all' ? 'selected' : ''; ?>>همه</option>
                    <option value="in_stock" <?php echo $stockStatus === 'in_stock' ? 'selected' : ''; ?>>موجود</option>
                    <option value="out_of_stock" <?php echo $stockStatus === 'out_of_stock' ? 'selected' : ''; ?>>ناموجود</option>
                    <option value="low_stock" <?php echo $stockStatus === 'low_stock' ? 'selected' : ''; ?>>موجودی کم</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> جستجو
                </button>
                <a href="manage_products.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> بازنشانی
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <form method="POST" action="manage_products.php" id="bulk-actions-form">
        <div class="bulk-actions">
            <span class="selected-count">0 محصول انتخاب شده</span>
            <button type="submit" name="delete_selected" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف محصولات انتخابی مطمئن هستید؟')">
                <i class="fas fa-trash"></i> حذف انتخابی
            </button>
        </div>
        
        <!-- Products Table -->
        <div class="data-table-wrapper">
            <div class="data-table-header">
                <div class="table-title">
                    <i class="fas fa-list"></i>
                    لیست محصولات (<?php echo toPersianNumbers($totalProducts); ?>)
                </div>
                <div class="table-actions">
                    <a href="manage_products.php" class="btn btn-info btn-sm">
                        <i class="fas fa-file-import"></i> وارد کردن
                    </a>
                    <a href="manage_products.php" class="btn btn-success btn-sm">
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
                            <th style="width: 60px;">تصویر</th>
                            <th>نام محصول</th>
                            <th>دسته‌بندی</th>
                            <th>SKU</th>
                            <th>قیمت</th>
                            <th>موجودی</th>
                            <th>ویژه</th>
                            <th>وضعیت</th>
                            <th>تاریخ</th>
                            <th style="width: 120px;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="11" class="empty-message">
                                    <i class="fas fa-box-open"></i>
                                    <p>هیچ محصولی یافت نشد</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr data-product-id="<?php echo $product['id']; ?>">
                                    <td>
                                        <input type="checkbox" name="product_ids[]" 
                                               value="<?php echo $product['id']; ?>" 
                                               onchange="updateSelectedCount()">
                                    </td>
                                    <td>
                                        <?php if ($product['image_path']): ?>
                                            <img src="../<?php echo htmlspecialchars($product['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; background: #f5f5f5; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image" style="color: #999; font-size: 16px;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'ندارد'); ?></td>
                                    <td><?php echo htmlspecialchars($product['sku'] ?? 'ندارد'); ?></td>
                                    <td><?php echo formatPrice($product['price']); ?></td>
                                    <td>
                                        <?php if ($product['stock'] > 5): ?>
                                            <span class="badge badge-success">موجود</span>
                                        <?php elseif ($product['stock'] > 0 && $product['stock'] <= 5): ?>
                                            <span class="badge badge-warning">موجودی کم</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">ناموجود</span>
                                        <?php endif; ?>
                                        <span style="font-size: 11px; color: #666;"><?php echo toPersianNumbers($product['stock']); ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" action="manage_products.php" style="display: inline;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="is_featured" value="<?php echo $product['is_featured']; ?>">
                                            <button type="submit" name="toggle_featured" 
                                                    class="btn btn-sm <?php echo $product['is_featured'] ? 'btn-success' : 'btn-secondary'; ?>"
                                                    title="ویژه/غیر ویژه">
                                                <i class="fas fa-<?php echo $product['is_featured'] ? 'star' : 'star-o'; ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" action="manage_products.php" style="display: inline;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $product['is_active']; ?>">
                                            <button type="submit" name="toggle_active" 
                                                    class="btn btn-sm <?php echo $product['is_active'] ? 'btn-success' : 'btn-secondary'; ?>"
                                                    title="فعال/غیرفعال">
                                                <i class="fas fa-<?php echo $product['is_active'] ? 'check' : 'times'; ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td><?php echo formatDate($product['created_at']); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                               class="action-btn edit" 
                                               title="ویرایش">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>"
                                               class="action-btn view" 
                                               title="گالری تصاویر">
                                                <i class="fas fa-images"></i>
                                            </a>
                                            <form method="POST" action="manage_products.php" style="display: inline;">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" name="delete_product" 
                                                        class="action-btn delete" 
                                                        title="حذف"
                                                        onclick="return confirm('آیا از حذف این محصول مطمئن هستید؟')">
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
                نمایش <?php echo toPersianNumbers($offset + 1); ?> تا <?php echo toPersianNumbers(min($offset + $perPage, $totalProducts)); ?> از <?php echo toPersianNumbers($totalProducts); ?> محصول
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryId; ?>&status=<?php echo $status; ?>&stock=<?php echo $stockStatus; ?>" 
                       class="page-item">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === 1 || $i === $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryId; ?>&status=<?php echo $status; ?>&stock=<?php echo $stockStatus; ?>" 
                           class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo toPersianNumbers($i); ?>
                        </a>
                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                        <span class="page-item disabled">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryId; ?>&status=<?php echo $status; ?>&stock=<?php echo $stockStatus; ?>" 
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
        selectedCountEl.textContent = toPersianNumbers(count) + ' محصول انتخاب شده';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>

<?php require_once 'footer.php'; ?>
