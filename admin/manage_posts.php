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
    // Delete post
    if (isset($_POST['delete_post'])) {
        $postId = (int)$_POST['post_id'];
        
        if (deleteBlogPost($postId)) {
            logAdminActivity('delete_post', 'مقاله حذف شد: ' . $postId);
            $success = 'مقاله با موفقیت حذف شد.';
        } else {
            $error = 'خطا در حذف مقاله.';
        }
    }
    
    // Delete selected posts
    if (isset($_POST['delete_selected'])) {
        $postIds = $_POST['post_ids'] ?? [];
        $deletedCount = 0;
        
        foreach ($postIds as $postId) {
            if (deleteBlogPost($postId)) {
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            logAdminActivity('delete_posts', toPersianNumbers($deletedCount) . ' مقاله حذف شد');
            $success = toPersianNumbers($deletedCount) . ' مقاله با موفقیت حذف شد.';
        } else {
            $error = 'هیچ مقاله‌ای برای حذف انتخاب نشده است.';
        }
    }
    
    // Toggle published status
    if (isset($_POST['toggle_published'])) {
        $postId = (int)$_POST['post_id'];
        $isPublished = (int)$_POST['is_published'];
        
        $result = updateBlogPost($postId, ['is_published' => $isPublished ? 0 : 1]);
        
        if ($result) {
            logAdminActivity('toggle_post_published', 'وضعیت انتشار مقاله تغییر کرد: ' . $postId);
            $success = 'وضعیت انتشار مقاله با موفقیت تغییر یافت.';
        } else {
            $error = 'خطا در تغییر وضعیت انتشار.';
        }
    }
    
    // Toggle featured status
    if (isset($_POST['toggle_featured'])) {
        $postId = (int)$_POST['post_id'];
        $isFeatured = (int)$_POST['is_featured'];
        
        $result = updateBlogPost($postId, ['is_featured' => $isFeatured ? 0 : 1]);
        
        if ($result) {
            logAdminActivity('toggle_post_featured', 'وضعیت ویژه مقاله تغییر کرد: ' . $postId);
            $success = 'وضعیت ویژه مقاله با موفقیت تغییر یافت.';
        } else {
            $error = 'خطا در تغییر وضعیت ویژه.';
        }
    }
}

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status = $_GET['status'] ?? 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(bp.title LIKE ? OR bp.excerpt LIKE ? OR bp.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryId > 0) {
    $where[] = "bp.category_id = ?";
    $params[] = $categoryId;
}

if ($status !== 'all') {
    $isPublished = $status === 'published' ? 1 : 0;
    $where[] = "bp.is_published = ?";
    $params[] = $isPublished;
}

$whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

// Count posts
try {
    $countQuery = "SELECT COUNT(*) FROM blog_posts bp LEFT JOIN blog_categories bc ON bp.category_id = bc.id LEFT JOIN users u ON bp.author_id = u.id" . $whereClause;
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalPosts = (int)$stmt->fetchColumn();
    $totalPages = ceil($totalPosts / $perPage);
} catch (PDOException $e) {
    $totalPosts = 0;
    $totalPages = 1;
}

// Get posts
try {
    $offset = ($page - 1) * $perPage;
    $query = "SELECT bp.*, bc.name as category_name, u.name as author_name FROM blog_posts bp LEFT JOIN blog_categories bc ON bp.category_id = bc.id LEFT JOIN users u ON bp.author_id = u.id" . $whereClause . " ORDER BY bp.published_at DESC, bp.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $posts = [];
}

// Get categories
$categories = getBlogCategories();

// Get statistics
$stats = [
    'total' => 0,
    'published' => 0,
    'draft' => 0,
    'featured' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts");
    $stats['total'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE is_published = 1");
    $stats['published'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE is_published = 0");
    $stats['draft'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE is_featured = 1");
    $stats['featured'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Use default stats
}

$pageTitle = "مدیریت مقالات بلاگ";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-blog"></i> مدیریت مقالات بلاگ</h1>
        <div class="admin-actions">
            <a href="add_post.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> افزودن مقاله جدید
            </a>
            <a href="manage_post_categories.php" class="btn btn-secondary">
                <i class="fas fa-folder"></i> مدیریت دسته‌بندی‌ها
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
                <i class="fas fa-file-alt"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['total']); ?></span>
            <span class="stat-label">کل مقالات</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4caf50, #8bc34a);">
                <i class="fas fa-check-circle"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['published']); ?></span>
            <span class="stat-label">منتشر شده</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ff9800, #ffc107);">
                <i class="fas fa-file"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['draft']); ?></span>
            <span class="stat-label">پیش‌نویس</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #9c27b0, #673ab7);">
                <i class="fas fa-star"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['featured']); ?></span>
            <span class="stat-label">ویژه</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="manage_posts.php">
            <div class="filter-group">
                <label for="search">جستجو</label>
                <input type="text" id="search" name="search" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="عنوان یا محتوای مقاله">
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
                    <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>منتشر شده</option>
                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>پیش‌نویس</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> جستجو
                </button>
                <a href="manage_posts.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> بازنشانی
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <form method="POST" action="manage_posts.php" id="bulk-actions-form">
        <div class="bulk-actions">
            <span class="selected-count">0 مقاله انتخاب شده</span>
            <button type="submit" name="delete_selected" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف مقالات انتخابی مطمئن هستید؟')">
                <i class="fas fa-trash"></i> حذف انتخابی
            </button>
        </div>
        
        <!-- Posts Table -->
        <div class="data-table-wrapper">
            <div class="data-table-header">
                <div class="table-title">
                    <i class="fas fa-list"></i>
                    لیست مقالات (<?php echo toPersianNumbers($totalPosts); ?>)
                </div>
                <div class="table-actions">
                    <a href="manage_posts.php" class="btn btn-success btn-sm">
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
                            <th>عنوان مقاله</th>
                            <th>دسته‌بندی</th>
                            <th>نویسنده</th>
                            <th>ویژه</th>
                            <th>وضعیت</th>
                            <th>تاریخ انتشار</th>
                            <th style="width: 120px;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="10" class="empty-message">
                                    <i class="fas fa-file-alt"></i>
                                    <p>هیچ مقاله‌ای یافت نشد</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <tr data-post-id="<?php echo $post['id']; ?>">
                                    <td>
                                        <input type="checkbox" name="post_ids[]" 
                                               value="<?php echo $post['id']; ?>" 
                                               onchange="updateSelectedCount()">
                                    </td>
                                    <td>
                                        <?php if ($post['featured_image']): ?>
                                            <img src="../<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                                 style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; background: #f5f5f5; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image" style="color: #999; font-size: 16px;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($post['category_name'] ?? 'ندارد'); ?></td>
                                    <td><?php echo htmlspecialchars($post['author_name'] ?? 'ندارد'); ?></td>
                                    <td>
                                        <form method="POST" action="manage_posts.php" style="display: inline;">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <input type="hidden" name="is_featured" value="<?php echo $post['is_featured']; ?>">
                                            <button type="submit" name="toggle_featured" 
                                                    class="btn btn-sm <?php echo $post['is_featured'] ? 'btn-success' : 'btn-secondary'; ?>"
                                                    title="ویژه/غیر ویژه">
                                                <i class="fas fa-<?php echo $post['is_featured'] ? 'star' : 'star-o'; ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" action="manage_posts.php" style="display: inline;">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <input type="hidden" name="is_published" value="<?php echo $post['is_published']; ?>">
                                            <button type="submit" name="toggle_published" 
                                                    class="btn btn-sm <?php echo $post['is_published'] ? 'btn-success' : 'btn-secondary'; ?>"
                                                    title="منتشر شده/پیش‌نویس">
                                                <i class="fas fa-<?php echo $post['is_published'] ? 'check' : 'times'; ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td><?php echo formatDate($post['published_at'] ?? $post['created_at']); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="edit_post.php?id=<?php echo $post['id']; ?>" 
                                               class="action-btn edit" 
                                               title="ویرایش">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="../blog-post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" 
                                               class="action-btn view" 
                                               title="مشاهده در سایت" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form method="POST" action="manage_posts.php" style="display: inline;">
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <button type="submit" name="delete_post" 
                                                        class="action-btn delete" 
                                                        title="حذف"
                                                        onclick="return confirm('آیا از حذف این مقاله مطمئن هستید؟')">
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
                نمایش <?php echo toPersianNumbers($offset + 1); ?> تا <?php echo toPersianNumbers(min($offset + $perPage, $totalPosts)); ?> از <?php echo toPersianNumbers($totalPosts); ?> مقاله
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryId; ?>&status=<?php echo $status; ?>" 
                       class="page-item">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === 1 || $i === $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryId; ?>&status=<?php echo $status; ?>" 
                           class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo toPersianNumbers($i); ?>
                        </a>
                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                        <span class="page-item disabled">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryId; ?>&status=<?php echo $status; ?>" 
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
        selectedCountEl.textContent = toPersianNumbers(count) + ' مقاله انتخاب شده';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>

<?php require_once 'footer.php'; ?>
