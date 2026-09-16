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
    // Add page
    if (isset($_POST['add_page'])) {
        $pageData = [
            'title' => trim($_POST['title']),
            'slug' => trim($_POST['slug']),
            'content' => $_POST['content'] ?? '',
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0)
        ];
        
        $pageId = createPage($pageData);
        
        if ($pageId) {
            logAdminActivity('create_page', 'صفحه جدید ایجاد شد: ' . $pageData['title']);
            $success = 'صفحه با موفقیت ایجاد شد.';
        } else {
            $error = 'خطا در ایجاد صفحه.';
        }
    }
    
    // Update page
    if (isset($_POST['update_page'])) {
        $pageId = (int)$_POST['page_id'];
        $pageData = [
            'title' => trim($_POST['title']),
            'slug' => trim($_POST['slug']),
            'content' => $_POST['content'] ?? '',
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0)
        ];
        
        $result = updatePage($pageId, $pageData);
        
        if ($result) {
            logAdminActivity('update_page', 'صفحه به‌روزرسانی شد: ' . $pageData['title']);
            $success = 'صفحه با موفقیت به‌روزرسانی شد.';
        } else {
            $error = 'خطا در به‌روزرسانی صفحه.';
        }
    }
    
    // Delete page
    if (isset($_POST['delete_page'])) {
        $pageId = (int)$_POST['page_id'];
        
        if (deletePage($pageId)) {
            logAdminActivity('delete_page', 'صفحه حذف شد: ' . $pageId);
            $success = 'صفحه با موفقیت حذف شد.';
        } else {
            $error = 'خطا در حذف صفحه.';
        }
    }
    
    // Delete selected pages
    if (isset($_POST['delete_selected'])) {
        $pageIds = $_POST['page_ids'] ?? [];
        $deletedCount = 0;
        
        foreach ($pageIds as $pageId) {
            if (deletePage($pageId)) {
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            logAdminActivity('delete_pages', toPersianNumbers($deletedCount) . ' صفحه حذف شد');
            $success = toPersianNumbers($deletedCount) . ' صفحه با موفقیت حذف شد.';
        } else {
            $error = 'هیچ صفحه‌ای برای حذف انتخاب نشده است.';
        }
    }
    
    // Toggle active status
    if (isset($_POST['toggle_active'])) {
        $pageId = (int)$_POST['page_id'];
        $isActive = (int)$_POST['is_active'];
        
        $result = updatePage($pageId, ['is_active' => $isActive ? 0 : 1]);
        
        if ($result) {
            logAdminActivity('toggle_page_active', 'وضعیت صفحه تغییر کرد: ' . $pageId);
            $success = 'وضعیت صفحه با موفقیت تغییر یافت.';
        } else {
            $error = 'خطا در تغییر وضعیت صفحه.';
        }
    }
}

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(title LIKE ? OR slug LIKE ? OR content LIKE ?)";
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

// Count pages
try {
    $countQuery = "SELECT COUNT(*) FROM pages" . $whereClause;
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalPagesCount = (int)$stmt->fetchColumn();
    $totalPages = ceil($totalPagesCount / $perPage);
} catch (PDOException $e) {
    $totalPagesCount = 0;
    $totalPages = 1;
}

// Get pages
try {
    $offset = ($page - 1) * $perPage;
    $query = "SELECT * FROM pages" . $whereClause . " ORDER BY sort_order ASC, title ASC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pages = [];
}

// Get statistics
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM pages");
    $stats['total'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM pages WHERE is_active = 1");
    $stats['active'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM pages WHERE is_active = 0");
    $stats['inactive'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Use default stats
}

$pageTitle = "مدیریت صفحات استاتیک";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-file-alt"></i> مدیریت صفحات استاتیک</h1>
        <div class="admin-actions">
            <button class="btn btn-primary" onclick="openAddPageModal()">
                <i class="fas fa-plus"></i> افزودن صفحه جدید
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
                <i class="fas fa-file"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['total']); ?></span>
            <span class="stat-label">کل صفحات</span>
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
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="manage_pages.php">
            <div class="filter-group">
                <label for="search">جستجو</label>
                <input type="text" id="search" name="search" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="عنوان یا نامک صفحه">
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
                <a href="manage_pages.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> بازنشانی
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <form method="POST" action="manage_pages.php" id="bulk-actions-form">
        <div class="bulk-actions">
            <span class="selected-count">0 صفحه انتخاب شده</span>
            <button type="submit" name="delete_selected" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف صفحات انتخابی مطمئن هستید؟')">
                <i class="fas fa-trash"></i> حذف انتخابی
            </button>
        </div>
        
        <!-- Pages Table -->
        <div class="data-table-wrapper">
            <div class="data-table-header">
                <div class="table-title">
                    <i class="fas fa-list"></i>
                    لیست صفحات (<?php echo toPersianNumbers($totalPagesCount); ?>)
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="select-all" onchange="toggleSelectAll(this)">
                            </th>
                            <th>عنوان صفحه</th>
                            <th>نامک</th>
                            <th>ترتیب</th>
                            <th>وضعیت</th>
                            <th>تاریخ</th>
                            <th style="width: 120px;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="7" class="empty-message">
                                    <i class="fas fa-file-alt"></i>
                                    <p>هیچ صفحه‌ای یافت نشد</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pages as $page): ?>
                                <tr data-page-id="<?php echo $page['id']; ?>">
                                    <td>
                                        <input type="checkbox" name="page_ids[]" 
                                               value="<?php echo $page['id']; ?>" 
                                               onchange="updateSelectedCount()">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($page['title']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($page['slug']); ?></td>
                                    <td><?php echo toPersianNumbers($page['sort_order']); ?></td>
                                    <td>
                                        <form method="POST" action="manage_pages.php" style="display: inline;">
                                            <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $page['is_active']; ?>">
                                            <button type="submit" name="toggle_active" 
                                                    class="btn btn-sm <?php echo $page['is_active'] ? 'btn-success' : 'btn-secondary'; ?>"
                                                    title="فعال/غیرفعال">
                                                <i class="fas fa-<?php echo $page['is_active'] ? 'check' : 'times'; ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td><?php echo formatDate($page['created_at']); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="../page.php?slug=<?php echo htmlspecialchars($page['slug']); ?>" 
                                               class="action-btn view" 
                                               title="مشاهده در سایت" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="action-btn edit" 
                                                    onclick="openEditPageModal(<?php echo $page['id']; ?>)" 
                                                    title="ویرایش">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" action="manage_pages.php" style="display: inline;">
                                                <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                                                <button type="submit" name="delete_page" 
                                                        class="action-btn delete" 
                                                        title="حذف"
                                                        onclick="return confirm('آیا از حذف این صفحه مطمئن هستید؟')">
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
                نمایش <?php echo toPersianNumbers($offset + 1); ?> تا <?php echo toPersianNumbers(min($offset + $perPage, $totalPagesCount)); ?> از <?php echo toPersianNumbers($totalPagesCount); ?> صفحه
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

<!-- Add Page Modal -->
<div class="modal-overlay" id="add-page-modal">
    <div class="modal form-modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> افزودن صفحه جدید</h3>
            <button type="button" class="modal-close" onclick="closeAddPageModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="manage_pages.php">
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_title">عنوان صفحه <span style="color: #f44336;">*</span></label>
                    <input type="text" id="add_title" name="title" 
                           class="form-control" 
                           placeholder="عنوان صفحه را وارد کنید" required>
                </div>
                
                <div class="form-group">
                    <label for="add_slug">نامک (Slug) <span style="color: #f44336;">*</span></label>
                    <input type="text" id="add_slug" name="slug" 
                           class="form-control" 
                           placeholder="نامک صفحه را وارد کنید" required>
                </div>
                
                <div class="form-group">
                    <label for="add_content">محتوا</label>
                    <textarea id="add_content" name="content" 
                              class="form-control textarea-control" 
                              style="min-height: 200px;" 
                              placeholder="محتوا را وارد کنید"></textarea>
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
                                          data-maxlength="160" 
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
                <button type="submit" name="add_page" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    ذخیره صفحه
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeAddPageModal()">
                    <i class="fas fa-times"></i>
                    انصراف
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Page Modal -->
<div class="modal-overlay" id="edit-page-modal">
    <div class="modal form-modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> ویرایش صفحه</h3>
            <button type="button" class="modal-close" onclick="closeEditPageModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="manage_pages.php" id="edit-page-form">
            <input type="hidden" name="page_id" id="edit_page_id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_title">عنوان صفحه <span style="color: #f44336;">*</span></label>
                    <input type="text" id="edit_title" name="title" 
                           class="form-control" 
                           placeholder="عنوان صفحه را وارد کنید" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_slug">نامک (Slug) <span style="color: #f44336;">*</span></label>
                    <input type="text" id="edit_slug" name="slug" 
                           class="form-control" 
                           placeholder="نامک صفحه را وارد کنید" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_content">محتوا</label>
                    <textarea id="edit_content" name="content" 
                              class="form-control textarea-control" 
                              style="min-height: 200px;" 
                              placeholder="محتوا را وارد کنید"></textarea>
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
                                          data-maxlength="160" 
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
                <button type="submit" name="update_page" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    ذخیره تغییرات
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeEditPageModal()">
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
        selectedCountEl.textContent = toPersianNumbers(count) + ' صفحه انتخاب شده';
    }
}

// Open add page modal
function openAddPageModal() {
    document.getElementById('add-page-modal').classList.add('active');
}

// Close add page modal
function closeAddPageModal() {
    document.getElementById('add-page-modal').classList.remove('active');
}

// Open edit page modal
function openEditPageModal(pageId) {
    // Get page data
    const page = <?php echo json_encode($pages); ?>.find(p => p.id == pageId);
    
    if (page) {
        document.getElementById('edit_page_id').value = page.id;
        document.getElementById('edit_title').value = page.title;
        document.getElementById('edit_slug').value = page.slug;
        document.getElementById('edit_content').value = page.content;
        document.getElementById('edit_meta_title').value = page.meta_title || '';
        document.getElementById('edit_meta_description').value = page.meta_description || '';
        document.getElementById('edit_meta_keywords').value = page.meta_keywords || '';
        document.getElementById('edit_sort_order').value = page.sort_order || 0;
        document.getElementById('edit_is_active').checked = page.is_active == 1;
        
        document.getElementById('edit-page-modal').classList.add('active');
    }
}

// Close edit page modal
function closeEditPageModal() {
    document.getElementById('edit-page-modal').classList.remove('active');
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddPageModal();
        closeEditPageModal();
    }
});

// Close modals when clicking outside
document.getElementById('add-page-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddPageModal();
    }
});

document.getElementById('edit-page-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditPageModal();
    }
});

// Auto-generate slug from title
document.getElementById('add_title').addEventListener('input', function() {
    const title = this.value;
    const slug = title.toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]+/g, '')
        .replace(/--+/g, '-')
        .replace(/^-+|-+$/g, '');
    
    document.getElementById('add_slug').value = slug;
});

document.getElementById('edit_title').addEventListener('input', function() {
    const title = this.value;
    const slug = title.toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]+/g, '')
        .replace(/--+/g, '-')
        .replace(/^-+|-+$/g, '');
    
    document.getElementById('edit_slug').value = slug;
});

// Character counters for textareas
function initCharCounters() {
    const textareas = document.querySelectorAll('textarea[data-maxlength]');
    textareas.forEach(textarea => {
        const maxLength = parseInt(textarea.dataset.maxlength);
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.style.cssText = 'font-size: 12px; color: #999; text-align: right; margin-top: 4px;';
        textarea.parentNode.insertBefore(counter, textarea.nextSibling);
        
        textarea.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            counter.textContent = toPersianNumbers(remaining) + ' کاراکتر باقی مانده';
            
            if (remaining < 0) {
                counter.style.color = '#f44336';
            } else if (remaining < 20) {
                counter.style.color = '#ff9800';
            } else {
                counter.style.color = '#999';
            }
        });
        
        // Trigger once
        textarea.dispatchEvent(new Event('input'));
    });
}

// Initialize character counters
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
    initCharCounters();
});
</script>

<?php require_once 'footer.php'; ?>
