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
$categories = getAllCategories();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // Validate required fields
    if (empty($_POST['name']) || empty($_POST['price']) || empty($_POST['category_id'])) {
        $error = 'لطفا فیلدهای اجباری را پر کنید.';
    } else {
        // Prepare product data
        $productData = [
            'name' => trim($_POST['name']),
            'description' => $_POST['description'] ?? '',
            'category_id' => (int)$_POST['category_id'],
            'price' => (float)toEnglishNumbers($_POST['price']),
            'stock' => (int)toEnglishNumbers($_POST['stock']),
            'sku' => trim($_POST['sku'] ?? ''),
            'weight' => (float)toEnglishNumbers($_POST['weight'] ?? 0),
            'dimensions' => trim($_POST['dimensions'] ?? ''),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? '')
        ];
        
        try {
            // Create product
            $productId = createProduct($productData);
            
            if ($productId) {
                // Handle primary image upload
                if (!empty($_FILES['primary_image']['tmp_name'])) {
                    $uploadPath = 'assets/images/uploads/';
                    $imagePath = uploadImage($_FILES['primary_image'], $uploadPath);
                    
                    if ($imagePath) {
                        addProductImage($productId, $imagePath, true);
                    }
                }
                
                // Handle gallery images upload
                if (!empty($_FILES['gallery_images']['tmp_name'][0])) {
                    $uploadPath = 'assets/images/uploads/';
                    foreach ($_FILES['gallery_images']['tmp_name'] as $index => $tmpName) {
                        if (!empty($tmpName)) {
                            $imagePath = uploadImage([
                                'tmp_name' => $tmpName,
                                'name' => $_FILES['gallery_images']['name'][$index],
                                'type' => $_FILES['gallery_images']['type'][$index],
                                'size' => $_FILES['gallery_images']['size'][$index],
                                'error' => $_FILES['gallery_images']['error'][$index]
                            ], $uploadPath);
                            
                            if ($imagePath) {
                                addProductImage($productId, $imagePath, false);
                            }
                        }
                    }
                }
                
                // Log activity
                logAdminActivity('create_product', 'محصول جدید ایجاد شد: ' . $productData['name']);
                
                $success = 'محصول با موفقیت ایجاد شد.';
                
                // Redirect to edit page or clear form
                header("Location: edit_product.php?id=$productId&success=1");
                exit;
            } else {
                $error = 'خطا در ایجاد محصول.';
            }
        } catch (Exception $e) {
            $error = 'خطا: ' . $e->getMessage();
        }
    }
}

$pageTitle = "افزودن محصول جدید";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-plus-circle"></i> افزودن محصول جدید</h1>
        <div class="admin-actions">
            <a href="manage_products.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> لیست محصولات
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

    <!-- Product Form -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>اطلاعات محصول</h3>
        </div>
        
        <form method="POST" action="add_product.php" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-col half">
                    <div class="form-group">
                        <label for="name">نام محصول <span style="color: #f44336;">*</span></label>
                        <input type="text" id="name" name="name" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                               placeholder="نام محصول را وارد کنید" required>
                    </div>
                </div>
                <div class="form-col half">
                    <div class="form-group">
                        <label for="sku">کد محصول (SKU)</label>
                        <input type="text" id="sku" name="sku" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>" 
                               placeholder="کد یکتای محصول">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col half">
                    <div class="form-group">
                        <label for="category_id">دسته‌بندی <span style="color: #f44336;">*</span></label>
                        <select id="category_id" name="category_id" class="form-control select-control" required>
                            <option value="">دسته‌بندی را انتخاب کنید</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-col half">
                    <div class="form-group">
                        <label for="price">قیمت (تومان) <span style="color: #f44336;">*</span></label>
                        <input type="text" id="price" name="price" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" 
                               placeholder="قیمت محصول" required>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col half">
                    <div class="form-group">
                        <label for="stock">موجودی</label>
                        <input type="text" id="stock" name="stock" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['stock'] ?? '0'); ?>" 
                               placeholder="موجودی محصول" min="0">
                    </div>
                </div>
                <div class="form-col half">
                    <div class="form-group">
                        <label for="weight">وزن (کیلوگرم)</label>
                        <input type="text" id="weight" name="weight" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>" 
                               placeholder="وزن محصول">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="dimensions">ابعاد (طول × عرض × ارتفاع)</label>
                <input type="text" id="dimensions" name="dimensions" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($_POST['dimensions'] ?? ''); ?>" 
                       placeholder="مثال: 30 × 20 × 15 سانتی‌متر">
            </div>

            <div class="form-group">
                <label for="description">توضیحات محصول</label>
                <textarea id="description" name="description" 
                          class="form-control textarea-control" 
                          placeholder="توضیحات کامل محصول را وارد کنید"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>تصاویر محصول</label>
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="primary_image">تصویر اصلی</label>
                            <div class="file-upload-area">
                                <i class="fas fa-image"></i>
                                <span class="upload-text">تصویر اصلی را آپلود کنید</span>
                                <span class="upload-hint">فرمت‌های مجاز: JPG, PNG, GIF, WebP</span>
                                <input type="file" id="primary_image" name="primary_image" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="form-col half">
                        <div class="form-group">
                            <label for="gallery_images">گالری تصاویر</label>
                            <div class="file-upload-area">
                                <i class="fas fa-images"></i>
                                <span class="upload-text">تصاویر گالری را آپلود کنید</span>
                                <span class="upload-hint">حداکثر 10 تصویر - فرمت‌های مجاز: JPG, PNG, GIF, WebP</span>
                                <input type="file" id="gallery_images" name="gallery_images[]" accept="image/*" multiple max="10">
                            </div>
                        </div>
                    </div>
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
                <label>وضعیت</label>
                <div class="form-row">
                    <div class="form-col half">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" 
                                       value="1" <?php echo isset($_POST['is_active']) ? 'checked' : 'checked'; ?>>
                                <span>فعال</span>
                            </label>
                        </div>
                    </div>
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
                <button type="submit" name="add_product" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    ذخیره محصول
                </button>
                <a href="manage_products.php" class="btn btn-secondary">
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

// Price and number formatting for display
function formatNumberInput(input) {
    // Remove all non-digit characters
    let value = input.value.replace(/\D/g, '');
    
    // Format with commas
    if (value.length > 0) {
        value = value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    input.value = value;
}

// Apply formatting to price and number inputs
document.querySelectorAll('#price, #stock, #weight').forEach(input => {
    input.addEventListener('input', function() {
        formatNumberInput(this);
    });
    
    // Format on page load if there's a value
    if (this.value) {
        formatNumberInput(this);
    }
});
</script>

<?php require_once 'footer.php'; ?>
