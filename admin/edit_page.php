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

// Get page ID
$pageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get page
try {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $page = false;
}

if (!$page) {
    header("Location: manage_pages.php");
    exit;
}

// Get current admin ID
$currentAdminId = $_SESSION['admin_id'] ?? 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_page'])) {
    // Validate required fields
    if (empty($_POST['title'])) {
        $error = 'لطفا عنوان صفحه را وارد کنید.';
    } else {
        // Prepare page data
        $pageData = [
            'title' => trim($_POST['title']),
            'slug' => trim($_POST['slug']),
            'content' => $_POST['content'] ?? '',
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'show_in_menu' => isset($_POST['show_in_menu']) ? 1 : 0,
            'show_in_footer' => isset($_POST['show_in_footer']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'template' => trim($_POST['template'] ?? '')
        ];
        
        try {
            // Update page
            $result = updatePage($pageId, $pageData);
            
            if ($result) {
                // Handle featured image upload
                if (!empty($_FILES['featured_image']['tmp_name'])) {
                    $uploadPath = 'assets/images/uploads/';
                    $imagePath = uploadImage($_FILES['featured_image'], $uploadPath);
                    
                    if ($imagePath) {
                        // Remove old image if exists
                        if ($page['featured_image'] && file_exists(ROOT_PATH . '/' . $page['featured_image'])) {
                            unlink(ROOT_PATH . '/' . $page['featured_image']);
                        }
                        
                        updatePage($pageId, ['featured_image' => $imagePath]);
                        
                        // Refresh page
                        $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
                        $stmt->execute([$pageId]);
                        $page = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                }
                
                // Log activity
                logAdminActivity('update_page', 'صفحه به‌روزرسانی شد: ' . $pageData['title']);
                
                $success = 'صفحه با موفقیت به‌روزرسانی شد.';
            } else {
                $error = 'خطا در به‌روزرسانی صفحه.';
            }
        } catch (Exception $e) {
            $error = 'خطا: ' . $e->getMessage();
        }
    }
}

// Handle image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    if ($page['featured_image'] && file_exists(ROOT_PATH . '/' . $page['featured_image'])) {
        unlink(ROOT_PATH . '/' . $page['featured_image']);
    }
    
    updatePage($pageId, ['featured_image' => '']);
    
    // Refresh page
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $success = 'تصویر اصلی با موفقیت حذف شد.';
}

$pageTitle = "ویرایش صفحه - " . htmlspecialchars($page['title']);
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-edit"></i> ویرایش صفحه</h1>
        <div class="admin-actions">
            <a href="manage_pages.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> لیست صفحات
            </a>
            <a href="../page.php?slug=<?php echo htmlspecialchars($page['slug']); ?>" 
               class="btn btn-info" target="_blank">
                <i class="fas fa-eye"></i> مشاهده در سایت
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

    <!-- Page Form -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>ویرایش صفحه: <?php echo htmlspecialchars($page['title']); ?></h3>
        </div>
        
        <form method="POST" action="edit_page.php?id=<?php echo $pageId; ?>" enctype="multipart/form-data">
            <input type="hidden" name="page_id" value="<?php echo $pageId; ?>">
            
            <div class="form-group">
                <label for="title">عنوان صفحه <span style="color: #f44336;">*</span></label>
                <input type="text" id="title" name="title" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($page['title']); ?>" 
                       placeholder="عنوان صفحه را وارد کنید" required>
            </div>

            <div class="form-row">
                <div class="form-col half">
                    <div class="form-group">
                        <label for="slug">نامک (Slug)</label>
                        <input type="text" id="slug" name="slug" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($page['slug']); ?>" 
                               placeholder="نامک صفحه را وارد کنید">
                    </div>
                </div>
                <div class="form-col half">
                    <div class="form-group">
                        <label for="template">قالب</label>
                        <select id="template" name="template" class="form-control select-control">
                            <option value="" <?php echo empty($page['template']) ? 'selected' : ''; ?>>پیش‌فرض</option>
                            <option value="full-width" <?php echo $page['template'] === 'full-width' ? 'selected' : ''; ?>>عریض کامل</option>
                            <option value="no-sidebar" <?php echo $page['template'] === 'no-sidebar' ? 'selected' : ''; ?>>بدون نوار کناری</option>
                            <option value="contact" <?php echo $page['template'] === 'contact' ? 'selected' : ''; ?>>تماس با ما</option>
                            <option value="about" <?php echo $page['template'] === 'about' ? 'selected' : ''; ?>>درباره ما</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="excerpt">چکیده</label>
                <textarea id="excerpt" name="excerpt" 
                          class="form-control textarea-control" 
                          data-maxlength="300" 
                          placeholder="چکیده صفحه را وارد کنید"><?php echo htmlspecialchars($page['excerpt'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="content">محتوا</label>
                <textarea id="content" name="content" 
                          class="form-control textarea-control" 
                          style="min-height: 300px;" 
                          placeholder="محتوا را وارد کنید"><?php echo htmlspecialchars($page['content'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="featured_image">تصویر اصلی</label>
                <div class="file-upload-area">
                    <i class="fas fa-image"></i>
                    <span class="upload-text">تصویر اصلی را آپلود کنید</span>
                    <span class="upload-hint">فرمت‌های مجاز: JPG, PNG, GIF, WebP</span>
                    <input type="file" id="featured_image" name="featured_image" accept="image/*">
                </div>
                
                <?php if ($page['featured_image']): ?>
                    <div style="margin-top: 16px; display: flex; align-items: center; gap: 12px;">
                        <img src="../<?php echo htmlspecialchars($page['featured_image']); ?>" 
                             alt="تصویر اصلی" 
                             style="width: 100px; height: 100px; border-radius: 4px; object-fit: cover;">
                        <form method="POST" action="edit_page.php?id=<?php echo $pageId; ?>" style="display: inline;">
                            <button type="submit" name="delete_image" 
                                    class="btn btn-danger btn-sm" 
                                    onclick="return confirm('آیا از حذف این تصویر مطمئن هستید؟')">
                                <i class="fas fa-trash"></i> حذف تصویر
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>تنظیمات سئو</label>
                <div class="form-row">
                    <div class="form-col full">
                        <div class="form-group">
                            <label for="meta_title">عنوان سئو (Meta Title)</label>
                            <input type="text" id="meta_title" name="meta_title" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($page['meta_title'] ?? ''); ?>" 
                                   placeholder="عنوان سئو برای موتورهای جستجو">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col full">
                        <div class="form-group">
                            <label for="meta_description">توضیحات سئو (Meta Description)</label>
                            <textarea id="meta_description" name="meta_description" 
                                      class="form-control textarea-control" 
                                      data-maxlength="160" 
                                      placeholder="توضیحات سئو برای موتورهای جستجو"><?php echo htmlspecialchars($page['meta_description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col full">
                        <div class="form-group">
                            <label for="meta_keywords">کلمات کلیدی سئو (Meta Keywords)</label>
                            <textarea id="meta_keywords" name="meta_keywords" 
                                      class="form-control textarea-control" 
                                      placeholder="کلمات کلیدی را با کاما جدا کنید"><?php echo htmlspecialchars($page['meta_keywords'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>تنظیمات نمایش</label>
                <div class="form-row">
                    <div class="form-col third">
                        <div class="form-group">
                            <label for="sort_order">ترتیب</label>
                            <input type="number" id="sort_order" name="sort_order" 
                                   class="form-control" 
                                   value="<?php echo toPersianNumbers($page['sort_order'] ?? 0); ?>" min="0">
                        </div>
                    </div>
                    <div class="form-col third">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_published" 
                                       value="1" <?php echo $page['is_published'] ? 'checked' : ''; ?>>
                                <span>منتشر شود</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-col third">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="show_in_menu" 
                                       value="1" <?php echo $page['show_in_menu'] ? 'checked' : ''; ?>>
                                <span>نمایش در منوی اصلی</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col third">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="show_in_footer" 
                                       value="1" <?php echo $page['show_in_footer'] ? 'checked' : ''; ?>>
                                <span>نمایش در فوتر</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" name="update_page" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    ذخیره تغییرات
                </button>
                <a href="manage_pages.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    انصراف
                </a>
            </div>
        </form>
    </div>
</div>

<script>
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
document.addEventListener('DOMContentLoaded', initCharCounters);

// Auto-generate slug from title
document.getElementById('title').addEventListener('input', function() {
    const title = this.value;
    const slug = title.toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]+/g, '')
        .replace(/--+/g, '-')
        .replace(/^-+|-+$/g, '');
    
    document.getElementById('slug').value = slug;
});

// Page functions
function updatePage($pageId, $data) {
    global $pdo;
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'page_id' && $key !== 'id' && $key !== 'created_at' && isset($value)) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($fields)) return false;
    
    $values[] = $pageId;
    
    try {
        $sql = "UPDATE pages SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    } catch (PDOException $e) {
        return false;
    }
}
</script>

<?php require_once 'footer.php'; ?>
