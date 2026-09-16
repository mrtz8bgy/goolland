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

// Get categories
$categories = getBlogCategories();

// Get current admin ID
$currentAdminId = $_SESSION['admin_id'] ?? 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_post'])) {
    // Validate required fields
    if (empty($_POST['title'])) {
        $error = 'لطفا عنوان مقاله را وارد کنید.';
    } else {
        // Prepare post data
        $postData = [
            'title' => trim($_POST['title']),
            'slug' => trim($_POST['slug'] ?? ''),
            'content' => $_POST['content'] ?? '',
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : 0,
            'author_id' => $currentAdminId,
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'published_at' => !empty($_POST['published_at']) ? $_POST['published_at'] : null
        ];
        
        try {
            // Create post
            $postId = createBlogPost($postData);
            
            if ($postId) {
                // Handle featured image upload
                if (!empty($_FILES['featured_image']['tmp_name'])) {
                    $uploadPath = 'assets/images/uploads/';
                    $imagePath = uploadImage($_FILES['featured_image'], $uploadPath);
                    
                    if ($imagePath) {
                        updateBlogPost($postId, ['featured_image' => $imagePath]);
                    }
                }
                
                // Log activity
                logAdminActivity('create_post', 'مقاله جدید ایجاد شد: ' . $postData['title']);
                
                $success = 'مقاله با موفقیت ایجاد شد.';
                
                // Redirect to edit page or clear form
                header("Location: edit_post.php?id=$postId&success=1");
                exit;
            } else {
                $error = 'خطا در ایجاد مقاله.';
            }
        } catch (Exception $e) {
            $error = 'خطا: ' . $e->getMessage();
        }
    }
}

$pageTitle = "افزودن مقاله جدید";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-plus-circle"></i> افزودن مقاله جدید</h1>
        <div class="admin-actions">
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

    <!-- Post Form -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>اطلاعات مقاله</h3>
        </div>
        
        <form method="POST" action="add_post.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">عنوان مقاله <span style="color: #f44336;">*</span></label>
                <input type="text" id="title" name="title" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                       placeholder="عنوان مقاله را وارد کنید" required>
            </div>

            <div class="form-row">
                <div class="form-col half">
                    <div class="form-group">
                        <label for="slug">نامک (Slug)</label>
                        <input type="text" id="slug" name="slug" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>" 
                               placeholder="نامک مقاله را وارد کنید">
                    </div>
                </div>
                <div class="form-col half">
                    <div class="form-group">
                        <label for="category_id">دسته‌بندی</label>
                        <select id="category_id" name="category_id" class="form-control select-control">
                            <option value="0">دسته‌بندی را انتخاب کنید</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="excerpt">چکیده</label>
                <textarea id="excerpt" name="excerpt" 
                          class="form-control textarea-control" 
                          data-maxlength="300" 
                          placeholder="چکیده مقاله را وارد کنید"><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="content">محتوا</label>
                <textarea id="content" name="content" 
                          class="form-control textarea-control" 
                          style="min-height: 300px;" 
                          placeholder="محتوا را وارد کنید"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="featured_image">تصویر اصلی</label>
                <div class="file-upload-area">
                    <i class="fas fa-image"></i>
                    <span class="upload-text">تصویر اصلی را آپلود کنید</span>
                    <span class="upload-hint">فرمت‌های مجاز: JPG, PNG, GIF, WebP</span>
                    <input type="file" id="featured_image" name="featured_image" accept="image/*">
                </div>
            </div>

            <div class="form-group">
                <label>تنظیمات سئو</label>
                <div class="form-row">
                    <div class="form-col full">
                        <div class="form-group">
                            <label for="meta_title">عنوان سئو (Meta Title)</label>
                            <input type="text" id="meta_title" name="meta_title" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>" 
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
                                      placeholder="توضیحات سئو برای موتورهای جستجو"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col full">
                        <div class="form-group">
                            <label for="meta_keywords">کلمات کلیدی سئو (Meta Keywords)</label>
                            <textarea id="meta_keywords" name="meta_keywords" 
                                      class="form-control textarea-control" 
                                      placeholder="کلمات کلیدی را با کاما جدا کنید"><?php echo htmlspecialchars($_POST['meta_keywords'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>تنظیمات انتشار</label>
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="published_at">تاریخ انتشار</label>
                            <input type="datetime-local" id="published_at" name="published_at" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['published_at'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_published" 
                                       value="1" <?php echo isset($_POST['is_published']) ? 'checked' : ''; ?>>
                                <span>منتشر شود</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_featured" 
                                       value="1" <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                                <span>ویژه</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" name="add_post" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    ذخیره مقاله
                </button>
                <a href="manage_posts.php" class="btn btn-secondary">
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

// Initialize TinyMCE or other editor would go here
// For now, we'll use a simple textarea
</script>

<?php require_once 'footer.php'; ?>
