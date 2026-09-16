<?php

session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

// Get all categories
$categories = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC");

/* افزودن محصول */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_product"])) {

    $name = trim($_POST["name"]);
    $slug = trim($_POST["slug"]);
    $category_id = !empty($_POST["category_id"]) ? intval($_POST["category_id"]) : null;
    $description = trim($_POST["description"]);
    $short_description = trim($_POST["short_description"]);
    $price = !empty($_POST["price"]) ? floatval(str_replace(',', '', $_POST["price"])) : null;
    $sale_price = !empty($_POST["sale_price"]) ? floatval(str_replace(',', '', $_POST["sale_price"])) : null;
    $sku = trim($_POST["sku"]);
    $stock = !empty($_POST["stock"]) ? intval($_POST["stock"]) : 0;
    $is_featured = isset($_POST["is_featured"]) ? 1 : 0;
    $is_new = isset($_POST["is_new"]) ? 1 : 0;
    $status = $_POST["status"] ?? 'publish';
    $visibility = $_POST["visibility"] ?? 'public';

    $image_name = null;

    /* آپلود عکس */
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
            $error = "خطا در آپلود تصویر.";
        } else {

            $allowed_types = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];

            $file_type = mime_content_type($_FILES["image"]["tmp_name"]);

            if (!isset($allowed_types[$file_type])) {

                $error = "فرمت تصویر باید JPG، PNG یا WEBP باشد.";

            } elseif ($_FILES["image"]["size"] > 5 * 1024 * 1024) {

                $error = "حجم تصویر نباید بیشتر از 5 مگابایت باشد.";

            } else {

                $extension = $allowed_types[$file_type];

                $image_name = uniqid("product_", true) . "." . $extension;

                $upload_dir = "../assets/images/products/";

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $upload_path = $upload_dir . $image_name;

                if (!move_uploaded_file($_FILES["image"]["tmp_name"], $upload_path)) {
                    $error = "ذخیره تصویر انجام نشد.";
                    $image_name = null;
                }
            }
        }
    }

    /* ذخیره محصول */
    if ($error === "") {

        // Generate slug if empty
        if (empty($slug)) {
            $slug = preg_replace('/\s+/', '-', strtolower($name));
        }

        $stmt = $conn->prepare("
            INSERT INTO products
            (category_id, name, slug, description, short_description, price, sale_price, sku, stock, 
             image, is_featured, is_new, status, visibility)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "issssddsiiisss",
            $category_id,
            $name,
            $slug,
            $description,
            $short_description,
            $price,
            $sale_price,
            $sku,
            $stock,
            $image_name,
            $is_featured,
            $is_new,
            $status,
            $visibility
        );

        if ($stmt->execute()) {
            $message = "محصول با موفقیت اضافه شد ✅";
        } else {
            $error = "خطا در ثبت محصول: " . $stmt->error;
        }
    }
}


/* حذف محصول */
if (isset($_GET["delete"])) {

    $id = intval($_GET["delete"]);

    /* پیدا کردن عکس */
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    /* حذف محصول */
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        /* حذف عکس محصول */
        if (!empty($product["image"])) {

            $image_path = "../assets/images/products/" . $product["image"];

            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        $message = "محصول حذف شد.";
    }
}


/* دریافت محصولات */
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where = "WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $where .= " AND (name LIKE ? OR description LIKE ? OR sku LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}

if (!empty($category_filter)) {
    $where .= " AND category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if (!empty($status_filter)) {
    $where .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$order_by = isset($_GET['sort']) ? getOrderBy($_GET['sort']) : "ORDER BY id DESC";

$products = $conn->query("
    SELECT products.*, categories.name AS category_name
    FROM products
    LEFT JOIN categories ON products.category_id = categories.id
    $where
    $order_by
");

function getOrderBy($sort) {
    switch ($sort) {
        case 'name_asc': return "ORDER BY name ASC";
        case 'name_desc': return "ORDER BY name DESC";
        case 'price_asc': return "ORDER BY price ASC";
        case 'price_desc': return "ORDER BY price DESC";
        case 'stock_asc': return "ORDER BY stock ASC";
        case 'stock_desc': return "ORDER BY stock DESC";
        case 'newest': return "ORDER BY created_at DESC";
        case 'oldest': return "ORDER BY created_at ASC";
        default: return "ORDER BY id DESC";
    }
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>مدیریت محصولات | Goolland</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    
    <!-- Icon Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body>

    <!-- Sidebar -->
    <?php include_once 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        
        <!-- Header -->
        <?php include_once 'includes/header.php'; ?>

        <!-- Content -->
        <div class="admin-content">
            
            <!-- Page Header -->
            <div class="admin-page-header">
                <div class="admin-page-title">
                    <span class="icon"><i class="fas fa-seedling"></i></span>
                    <h1>مدیریت محصولات</h1>
                </div>
                <div class="admin-breadcrumb">
                    <a href="dashboard.php">داشبورد</a>
                    <span class="separator">/</span>
                    <span class="current">محصولات</span>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="admin-alert success">
                    <i class="fas fa-check-circle icon"></i>
                    <span class="message"><?php echo htmlspecialchars($message); ?></span>
                    <button class="close" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="admin-alert error">
                    <i class="fas fa-exclamation-circle icon"></i>
                    <span class="message"><?php echo htmlspecialchars($error); ?></span>
                    <button class="close" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Actions Bar -->
            <div class="admin-page-actions">
                <div class="admin-page-actions-left">
                    <button class="admin-btn admin-btn-primary" data-modal="add-product-modal">
                        <i class="fas fa-plus"></i>
                        افزودن محصول جدید
                    </button>
                </div>
                <div class="admin-page-actions-right">
                    <form method="GET" class="admin-search-form" style="display: flex; gap: 10px;">
                        <input type="text" name="q" placeholder="جستجوی محصولات..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--admin-border); background: var(--admin-bg); color: var(--admin-text);">
                        <select name="category" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--admin-border); background: var(--admin-bg); color: var(--admin-text);">
                            <option value="">همه دسته‌بندی‌ها</option>
                            <?php 
                            $categories->data_seek(0);
                            while ($category = $categories->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $category_filter ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <select name="status" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--admin-border); background: var(--admin-bg); color: var(--admin-text);">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="publish" <?php echo $status_filter === 'publish' ? 'selected' : ''; ?>>منتشر شده</option>
                            <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>پیش‌نویس</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>در انتظار</option>
                        </select>
                        <button type="submit" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-filter"></i>
                            فیلتر
                        </button>
                    </form>
                </div>
            </div>

            <!-- Products Table -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <span class="icon"><i class="fas fa-list"></i></span>
                        <span>لیست محصولات (<?php echo $products ? $products->num_rows : 0; ?>)</span>
                    </h3>
                    <div class="admin-card-actions">
                        <select onchange="window.location.href='?sort=' + this.value" style="padding: 6px 10px; border-radius: 6px; border: 1px solid var(--admin-border); background: var(--admin-bg); color: var(--admin-text);">
                            <option value="">مرتب‌سازی پیش‌فرض</option>
                            <option value="newest" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'newest') ? 'selected' : ''; ?>>جدیدترین</option>
                            <option value="price_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_asc') ? 'selected' : ''; ?>>ارزان‌ترین</option>
                            <option value="price_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_desc') ? 'selected' : ''; ?>>گران‌ترین</option>
                            <option value="name_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'name_asc') ? 'selected' : ''; ?>>الفبایی (A-Z)</option>
                            <option value="name_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'name_desc') ? 'selected' : ''; ?>>الفبایی (Z-A)</option>
                        </select>
                    </div>
                </div>
                <div class="admin-card-body">
                    <?php if ($products && $products->num_rows > 0): ?>
                        <div class="admin-table-wrapper">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>شناسه</th>
                                        <th>نام محصول</th>
                                        <th>دسته‌بندی</th>
                                        <th>قیمت</th>
                                        <th>موجودی</th>
                                        <th>وضعیت</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($product = $products->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $product['id']; ?></td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <?php if (!empty($product['image'])): ?>
                                                        <img src="../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                             class="admin-table-image">
                                                    <?php else: ?>
                                                        <div class="admin-table-image-placeholder">🌿</div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                        <div style="color: var(--admin-text-muted); font-size: 12px;">
                                                            <?php echo htmlspecialchars(mb_substr($product['sku'] ?? '', 0, 20)); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'بدون دسته'); ?></td>
                                            <td>
                                                <?php 
                                                if ($product['sale_price'] !== null && $product['sale_price'] < $product['price']):
                                                    echo '<del style="color: var(--admin-text-muted);">' . number_format($product['price']) . '</del><br>';
                                                    echo '<span style="color: var(--admin-success);">' . number_format($product['sale_price']) . ' تومان</span>';
                                                else:
                                                    echo number_format($product['price'] ?? 0) . ' تومان';
                                                endif;
                                                ?>
                                            </td>
                                            <td>
                                                <span class="admin-table-badge <?php echo $product['stock'] > 0 ? 'success' : 'danger'; ?>">
                                                    <?php echo $product['stock'] > 0 ? $product['stock'] . ' عدد' : 'اتمام موجودی'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="admin-table-badge <?php 
                                                $status_class = '';
                                                switch ($product['status']) {
                                                    case 'publish': $status_class = 'success'; break;
                                                    case 'draft': $status_class = 'warning'; break;
                                                    case 'pending': $status_class = 'info'; break;
                                                    default: $status_class = 'info';
                                                }
                                                echo $status_class;
                                                ?>">
                                                    <?php 
                                                    $status_text = '';
                                                    switch ($product['status']) {
                                                        case 'publish': $status_text = 'منتشر شده'; break;
                                                        case 'draft': $status_text = 'پیش‌نویس'; break;
                                                        case 'pending': $status_text = 'در انتظار'; break;
                                                        default: $status_text = $product['status'];
                                                    }
                                                    echo $status_text;
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="admin-table-actions">
                                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                                   class="admin-table-action edit" 
                                                   data-tooltip="ویرایش">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $product['id']; ?>" 
                                                   class="admin-table-action delete delete-btn" 
                                                   data-confirm-message="آیا از حذف این محصول مطمئن هستید؟"
                                                   data-tooltip="حذف">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty-state">
                            <div class="icon">🌱</div>
                            <h3>هنوز محصولی ثبت نشده</h3>
                            <p>برای شروع، محصول جدیدی اضافه کنید</p>
                            <button class="admin-btn admin-btn-primary" data-modal="add-product-modal" style="margin-top: 20px;">
                                <i class="fas fa-plus"></i>
                                افزودن محصول
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        
        <!-- Footer -->
        <?php include_once 'includes/footer.php'; ?>
        
    </main>

    <!-- Add Product Modal -->
    <div class="admin-modal-overlay" id="add-product-modal">
        <div class="admin-modal">
            <div class="admin-modal-header">
                <h2 class="admin-modal-title">
                    <span class="icon"><i class="fas fa-plus"></i></span>
                    <span>افزودن محصول جدید</span>
                </h2>
                <button class="admin-modal-close" onclick="document.getElementById('add-product-modal').classList.remove('active'); document.body.style.overflow='';">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="admin-modal-body">
                <input type="hidden" name="add_product" value="1">
                
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label class="admin-form-label">نام محصول <span class="required">*</span></label>
                        <input type="text" name="name" class="admin-form-input" placeholder="نام محصول را وارد کنید" required>
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">نامک (Slug)</label>
                        <input type="text" name="slug" class="admin-form-input" placeholder="مثال: red-rose" dir="ltr">
                    </div>
                </div>
                
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label class="admin-form-label">دسته‌بندی</label>
                        <select name="category_id" class="admin-form-select">
                            <option value="">بدون دسته‌بندی</option>
                            <?php 
                            $categories->data_seek(0);
                            while ($category = $categories->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">کد محصول (SKU)</label>
                        <input type="text" name="sku" class="admin-form-input" placeholder="مثال: ROSE-001" dir="ltr">
                    </div>
                </div>
                
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label class="admin-form-label">قیمت (تومان)</label>
                        <input type="text" name="price" class="admin-form-input" placeholder="مثال: 150000" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">قیمت تخفیف (تومان)</label>
                        <input type="text" name="sale_price" class="admin-form-input" placeholder="مثال: 135000" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                    </div>
                </div>
                
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label class="admin-form-label">موجودی</label>
                        <input type="number" name="stock" class="admin-form-input" placeholder="مثال: 100" min="0">
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">وضعیت</label>
                        <select name="status" class="admin-form-select">
                            <option value="publish" selected>منتشر شده</option>
                            <option value="draft">پیش‌نویس</option>
                            <option value="pending">در انتظار</option>
                        </select>
                    </div>
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">توضیحات کوتاه</label>
                    <textarea name="short_description" class="admin-form-textarea" placeholder="توضیح کوتاهی درباره محصول" data-max-length="200"></textarea>
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">توضیحات کامل</label>
                    <textarea name="description" class="admin-form-textarea" placeholder="توضیحات کامل محصول" rows="4" data-max-length="5000"></textarea>
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">تصویر محصول</label>
                    <div class="admin-form-file">
                        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" data-preview="product-preview">
                        <img id="product-preview" style="display: none; width: 100px; height: 100px; border-radius: 8px; margin-top: 10px; object-fit: cover;" alt="پیش‌نمایش">
                    </div>
                </div>
                
                <div class="admin-form-group">
                    <div class="admin-form-check">
                        <input type="checkbox" name="is_featured" id="is_featured" value="1">
                        <label for="is_featured">محصول ویژه</label>
                    </div>
                    <div class="admin-form-check">
                        <input type="checkbox" name="is_new" id="is_new" value="1">
                        <label for="is_new">محصول جدید</label>
                    </div>
                </div>
            </form>
            <div class="admin-modal-footer">
                <button type="button" class="admin-btn admin-btn-secondary" onclick="document.getElementById('add-product-modal').classList.remove('active'); document.body.style.overflow='';">
                    انصراف
                </button>
                <button type="submit" form="add-product-modal" class="admin-btn admin-btn-primary">
                    <i class="fas fa-check"></i>
                    ذخیره محصول
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/admin-script.js"></script>

</body>

</html>
