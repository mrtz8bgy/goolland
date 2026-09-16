<?php
require_once "includes/header.php";

// Get current category from URL
$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Base query
$query = "SELECT products.*, categories.name AS category_name, categories.slug AS category_slug FROM products LEFT JOIN categories ON products.category_id = categories.id";
$count_query = "SELECT COUNT(*) as total FROM products LEFT JOIN categories ON products.category_id = categories.id";
$where = [];
$params = [];
$types = '';

// Filter by category
if (!empty($category_slug)) {
    $where[] = "categories.slug = ?";
    $params[] = $category_slug;
    $types .= 's';
}

// Filter by search
if (!empty($search_query)) {
    $where[] = "(products.name LIKE ? OR products.description LIKE ? OR products.short_description LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}

// Only published products
$where[] = "products.status = 'publish'";
$where[] = "products.visibility = 'public'";

// Combine where clauses
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
    $count_query .= " WHERE " . implode(" AND ", $where);
}

// Order by
$query .= " ORDER BY products.is_featured DESC, products.created_at DESC";

// Limit for pagination
$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

// Get products
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

// Get total count
$stmt = $conn->prepare($count_query);
if (!empty($params) && strlen($types) > 2) {
    $count_params = array_slice($params, 0, -2);
    $count_types = substr($types, 0, -2);
    if (!empty($count_params)) {
        $stmt->bind_param($count_types, ...$count_params);
    }
}
$stmt->execute();
$count_result = $stmt->get_result();
$total = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);

// Get categories for sidebar
$categories = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC");

// Get featured products
$featured_products = $conn->query("SELECT products.*, categories.name AS category_name FROM products LEFT JOIN categories ON products.category_id = categories.id WHERE products.is_featured = 1 AND products.status = 'publish' ORDER BY products.created_at DESC LIMIT 3");

// Get current category info
$current_category = null;
if (!empty($category_slug)) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->bind_param("s", $category_slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_category = $result->fetch_assoc();
}

?>

<!-- Hero Section -->
<section class="page-hero">
    <div class="container">
        <div class="page-hero-content">
            <?php if ($current_category): ?>
                <h1><?php echo htmlspecialchars($current_category['name']); ?></h1>
                <p><?php echo htmlspecialchars($current_category['description'] ?? 'محصولات این دسته‌بندی'); ?></p>
            <?php elseif (!empty($search_query)): ?>
                <h1>نتایج جستجو برای "<?php echo htmlspecialchars($search_query); ?>"</h1>
                <p><?php echo $total; ?> محصول پیدا شد</p>
            <?php else: ?>
                <h1>همه محصولات</h1>
                <p>گزیده‌ای از بهترین گل‌ها و گیاهان</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Products Section -->
<section class="section">
    <div class="container">
        <div class="products-layout">
            <!-- Sidebar -->
            <aside class="products-sidebar">
                <!-- Categories Filter -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">دسته‌بندی‌ها</h3>
                    <ul class="widget-list">
                        <li class="<?php echo empty($category_slug) ? 'active' : ''; ?>">
                            <a href="/products.php">همه محصولات</a>
                        </li>
                        <?php if ($categories && $categories->num_rows > 0): ?>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                                <li class="<?php echo $category['slug'] === $category_slug ? 'active' : ''; ?>">
                                    <a href="/products.php?category=<?php echo htmlspecialchars($category['slug']); ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                        <span class="count">
                                            <?php
                                            $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ? AND status = 'publish'");
                                            $count_stmt->bind_param("i", $category['id']);
                                            $count_stmt->execute();
                                            $count_result = $count_stmt->get_result();
                                            $count = $count_result->fetch_assoc()['count'];
                                            echo $count;
                                            ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Featured Products -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">محصولات ویژه</h3>
                    <div class="widget-products">
                        <?php if ($featured_products && $featured_products->num_rows > 0): ?>
                            <?php while ($product = $featured_products->fetch_assoc()): ?>
                                <div class="widget-product">
                                    <a href="/product.php?id=<?php echo $product['id']; ?>">
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="/assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <div class="widget-product-image">🌿</div>
                                        <?php endif; ?>
                                        <div class="widget-product-info">
                                            <h4><?php echo htmlspecialchars(mb_substr($product['name'], 0, 30)); ?></h4>
                                            <?php if ($product['price'] !== null): ?>
                                                <span class="price"><?php echo number_format($product['price']); ?> تومان</span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Price Filter -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">فیلتر قیمت</h3>
                    <form method="GET" action="/products.php" class="price-filter">
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_slug); ?>">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                        <div class="price-range">
                            <label for="min_price">از:</label>
                            <input type="number" id="min_price" name="min_price" placeholder="حداقل" min="0">
                        </div>
                        <div class="price-range">
                            <label for="max_price">تا:</label>
                            <input type="number" id="max_price" name="max_price" placeholder="حداکثر" min="0">
                        </div>
                        <button type="submit" class="btn btn-secondary" style="width: 100%; margin-top: 10px;">
                            اعمال فیلتر
                        </button>
                    </form>
                </div>
            </aside>
            
            <!-- Main Content -->
            <main class="products-main">
                <!-- Sort Options -->
                <div class="products-sort">
                    <div class="sort-left">
                        <span class="products-count">
                            <?php echo $total; ?> محصول یافت شد
                        </span>
                    </div>
                    <div class="sort-right">
                        <form method="GET" action="/products.php" class="sort-form">
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_slug); ?>">
                            <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                            <select name="sort" onchange="this.form.submit()">
                                <option value="">مرتب‌سازی پیش‌فرض</option>
                                <option value="newest" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'newest') ? 'selected' : ''; ?>>جدیدترین</option>
                                <option value="price_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_asc') ? 'selected' : ''; ?>>ارزان‌ترین</option>
                                <option value="price_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_desc') ? 'selected' : ''; ?>>گران‌ترین</option>
                                <option value="rating" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'rating') ? 'selected' : ''; ?>>بالاترین امتیاز</option>
                            </select>
                        </form>
                        <div class="view-toggle">
                            <button class="view-btn active" data-view="grid">⬛</button>
                            <button class="view-btn" data-view="list">⋮</button>
                        </div>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <?php if ($products && $products->num_rows > 0): ?>
                    <div class="products-grid">
                        <?php while ($product = $products->fetch_assoc()): ?>
                            <div class="product-card" data-aos="fade-up">
                                <a href="/product.php?id=<?php echo $product['id']; ?>">
                                    <div class="product-image-wrapper">
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="/assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="product-image" 
                                                 loading="lazy">
                                        <?php else: ?>
                                            <div class="product-image-placeholder">🌿</div>
                                        <?php endif; ?>
                                        
                                        <?php if ($product['is_new'] == 1): ?>
                                            <span class="product-badge new">جدید</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($product['is_featured'] == 1): ?>
                                            <span class="product-badge featured">ویژه</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($product['sale_price'] !== null && $product['sale_price'] < $product['price']): ?>
                                            <span class="product-badge sale">
                                                <?php 
                                                $discount_percent = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                                                echo $discount_percent . '%';
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-info">
                                        <?php if (!empty($product['category_name'])): ?>
                                            <span class="product-category">
                                                <?php echo htmlspecialchars($product['category_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        
                                        <div class="product-description">
                                            <?php echo htmlspecialchars(mb_substr($product['short_description'] ?? $product['description'], 0, 100)); ?>
                                        </div>
                                        
                                        <div class="product-pricing">
                                            <?php if ($product['sale_price'] !== null && $product['sale_price'] < $product['price']): ?>
                                                <span class="product-price old"><?php echo number_format($product['price']); ?> تومان</span>
                                                <span class="product-price sale"><?php echo number_format($product['sale_price']); ?> تومان</span>
                                            <?php elseif ($product['price'] !== null): ?>
                                                <span class="product-price"><?php echo number_format($product['price']); ?> تومان</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="product-actions">
                                            <button class="btn btn-primary add-to-cart" 
                                                    data-product-id="<?php echo $product['id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                    data-product-price="<?php echo $product['sale_price'] ?? $product['price']; ?>">
                                                افزودن به سبد
                                            </button>
                                            <a href="/product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">
                                                جزئیات
                                            </a>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="pagination">
                            <ul>
                                <?php if ($page > 1): ?>
                                    <li>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                           class="page-numbers prev" 
                                           aria-label="قبلی">
                                            «
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1):
                                    echo '<li><a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '" class="page-numbers">1</a></li>';
                                    if ($start_page > 2):
                                        echo '<li><span class="page-numbers dots">...</span></li>';
                                endif;
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                    $is_active = $i == $page;
                                ?>
                                    <li>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                           class="page-numbers <?php echo $is_active ? 'current' : ''; ?>" 
                                           <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($end_page < $total_pages): 
                                    if ($end_page < $total_pages - 1):
                                        echo '<li><span class="page-numbers dots">...</span></li>';
                                    echo '<li><a href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '" class="page-numbers">' . $total_pages . '</a></li>';
                                endif; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                           class="page-numbers next" 
                                           aria-label="بعدی">
                                            »
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-products">
                        <div class="no-products-icon">🌱</div>
                        <h3>محصولی یافت نشد</h3>
                        <p>متاسفانه هیچ محصولی با فیلترهای اعمال شده یافت نشد.</p>
                        <a href="/products.php" class="btn btn-primary">
                            بازگردانی فیلترها
                        </a>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<!-- Related Categories -->
<?php if ($current_category): ?>
    <section class="section related-categories">
        <div class="container">
            <div class="section-title">
                <h2>دسته‌بندی‌های مرتبط</h2>
            </div>
            <div class="categories-grid">
                <?php 
                $related_categories = $conn->query("SELECT * FROM categories WHERE status = 'active' AND id != {$current_category['id']} ORDER BY RAND() LIMIT 4");
                if ($related_categories && $related_categories->num_rows > 0):
                    while ($category = $related_categories->fetch_assoc()):
                ?>
                    <a href="/products.php?category=<?php echo htmlspecialchars($category['slug']); ?>" class="category-card">
                        <div class="category-image">
                            <?php if (!empty($category['image'])): ?>
                                <img src="/assets/images/categories/<?php echo htmlspecialchars($category['image']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                            <?php else: ?>
                                <div class="category-placeholder">🌿</div>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    </a>
                <?php 
                    endwhile;
                endif;
                ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>سوالی دارید؟</h2>
            <p>تیم پشتیبانی ما همیشه آماده پاسخگویی به سوال‌های شماست</p>
            <div class="cta-buttons">
                <a href="/contact.php" class="btn btn-primary">
                    تماس با ما
                </a>
                <a href="/about.php" class="btn btn-secondary">
                    درباره ما
                </a>
            </div>
        </div>
    </div>
</section>

<?php
require_once "includes/footer.php";
?>
