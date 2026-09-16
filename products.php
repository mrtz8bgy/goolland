<?php
session_start();
require_once 'includes/db.php';

// Get parameters
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : null;
$searchQuery = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'default';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 12;

// Get category info
$category = null;
if ($categoryId) {
    $category = getCategoryById($categoryId);
}

// Build query
$products = [];
$totalProducts = 0;

// Get products based on parameters
if ($categoryId) {
    $products = getProductsByCategory($categoryId);
    $totalProducts = count($products);
} elseif ($searchQuery) {
    $products = searchProducts($searchQuery);
    $totalProducts = count($products);
} else {
    switch ($sort) {
        case 'new':
            $products = getNewArrivalProducts();
            break;
        case 'featured':
            $products = getFeaturedProducts();
            break;
        case 'bestselling':
            $products = getBestSellingProducts();
            break;
        case 'price-low':
            $products = searchProducts('', null, null, null);
            usort($products, function($a, $b) {
                return $a['price'] - $b['price'];
            });
            break;
        case 'price-high':
            $products = searchProducts('', null, null, null);
            usort($products, function($a, $b) {
                return $b['price'] - $a['price'];
            });
            break;
        default:
            $products = searchProducts('', null, null, null);
            break;
    }
    $totalProducts = count($products);
}

// Pagination
$totalPages = ceil($totalProducts / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedProducts = array_slice($products, $offset, $perPage);

// Get categories for sidebar
$categories = getAllCategories();

// Get price range for filter
$minPrice = 0;
$maxPrice = 0;
if (!empty($products)) {
    $prices = array_column($products, 'price');
    $minPrice = min($prices);
    $maxPrice = max($prices);
}

// Get all categories for sidebar
$allCategories = getAllCategories();

// Get featured products for sidebar
$featuredProducts = getFeaturedProducts(3);

// Get recent products for sidebar
$recentProducts = getNewArrivalProducts(3);

// Get best selling products for sidebar
$bestSellingProducts = getBestSellingProducts(3);

$pageTitle = 'محصولات' . ($category ? ' - ' . htmlspecialchars($category['name']) : '') . ($searchQuery ? ' - جستجوی: ' . htmlspecialchars($searchQuery) : '');
$pageDescription = 'لیست محصولات گل و گیاه در فروشگاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><?php echo $category ? htmlspecialchars($category['name']) : ($searchQuery ? 'نتایج جستجوی: ' . htmlspecialchars($searchQuery) : 'همه محصولات'); ?></h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <?php if ($category): ?>
                <span><?php echo htmlspecialchars($category['name']); ?></span>
            <?php else: ?>
                <span>محصولات</span>
            <?php endif; ?>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="products-page">
    <div class="container">
        <!-- Sidebar -->
        <aside class="page-sidebar">
            <!-- Search Widget -->
            <div class="sidebar-widget search-widget">
                <h3 class="widget-title">جستجو</h3>
                <form action="search.php" method="get" class="search-form">
                    <input type="text" name="query" placeholder="جستجوی محصولات..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            
            <!-- Categories Widget -->
            <div class="sidebar-widget categories-widget">
                <h3 class="widget-title">دسته‌بندی‌ها</h3>
                <ul>
                    <li><a href="products.php" class="<?php echo !$categoryId && !$searchQuery ? 'active' : ''; ?>">همه محصولات <i class="fas fa-chevron-left"></i></a></li>
                    <?php foreach ($allCategories as $cat): ?>
                        <li>
                            <a href="products.php?category=<?php echo $cat['id']; ?>" class="<?php echo $categoryId == $cat['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Filter Widget -->
            <div class="sidebar-widget filter-widget">
                <h3 class="widget-title">فیلتر محصولات</h3>
                
                <!-- Price Filter -->
                <div class="filter-item">
                    <div class="filter-title">محدوده قیمت (تومان)</div>
                    <div class="price-filter">
                        <div class="price-range">
                            <input type="number" id="min_price" min="0" max="<?php echo $maxPrice; ?>" placeholder="از" value="<?php echo isset($_GET['min_price']) ? toPersianNumbers($_GET['min_price']) : toPersianNumbers($minPrice); ?>">
                            <span>تا</span>
                            <input type="number" id="max_price" min="<?php echo $minPrice; ?>" max="10000000" placeholder="تا" value="<?php echo isset($_GET['max_price']) ? toPersianNumbers($_GET['max_price']) : toPersianNumbers($maxPrice); ?>">
                        </div>
                        <div class="price-slider" data-min="<?php echo $minPrice; ?>" data-max="<?php echo $maxPrice; ?>"></div>
                    </div>
                </div>
                
                <!-- Stock Filter -->
                <div class="filter-item">
                    <div class="filter-title">موجودی</div>
                    <div class="filter-options">
                        <label>
                            <input type="checkbox" name="in_stock" <?php echo isset($_GET['in_stock']) ? 'checked' : ''; ?>>
                            <span>موجود</span>
                        </label>
                        <label>
                            <input type="checkbox" name="out_of_stock" <?php echo isset($_GET['out_of_stock']) ? 'checked' : ''; ?>>
                            <span>ناموجود</span>
                        </label>
                    </div>
                </div>
                
                <!-- Featured Filter -->
                <div class="filter-item">
                    <div class="filter-title">ویژه</div>
                    <div class="filter-options">
                        <label>
                            <input type="checkbox" name="featured" <?php echo isset($_GET['featured']) ? 'checked' : ''; ?>>
                            <span>محصولات ویژه</span>
                        </label>
                    </div>
                </div>
                
                <button class="btn btn-primary btn-sm" onclick="applyFilters()">
                    <i class="fas fa-filter"></i>
                    اعمال فیلترها
                </button>
            </div>
            
            <!-- Featured Products Widget -->
            <div class="sidebar-widget">
                <h3 class="widget-title">محصولات ویژه</h3>
                <div class="featured-products-widget">
                    <?php foreach ($featuredProducts as $product): ?>
                        <div class="featured-product">
                            <a href="product.php?id=<?php echo $product['id']; ?>">
                                <div class="featured-product-image">
                                    <img src="<?php echo $product['image_path'] ?: 'assets/images/no-image.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                                <div class="featured-product-info">
                                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <span class="featured-product-price"><?php echo formatPrice($product['price']); ?></span>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recent Products Widget -->
            <div class="sidebar-widget">
                <h3 class="widget-title">محصولات جدید</h3>
                <div class="recent-products-widget">
                    <?php foreach ($recentProducts as $product): ?>
                        <div class="recent-product">
                            <a href="product.php?id=<?php echo $product['id']; ?>">
                                <div class="recent-product-image">
                                    <img src="<?php echo $product['image_path'] ?: 'assets/images/no-image.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                                <div class="recent-product-info">
                                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <span class="recent-product-price"><?php echo formatPrice($product['price']); ?></span>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Tags Widget -->
            <div class="sidebar-widget tags-widget">
                <h3 class="widget-title">برچسب‌ها</h3>
                <div class="tags-list">
                    <a href="#">گل رز</a>
                    <a href="#">گل لاله</a>
                    <a href="#">گیاه آپارتمانی</a>
                    <a href="#">گلدان</a>
                    <a href="#">هديه</a>
                    <a href="#">عروسی</a>
                    <a href="#">تولد</a>
                    <a href="#">تبریک</a>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="page-main">
            <!-- Products Header -->
            <div class="products-header">
                <div class="products-header-left">
                    <span class="products-count">
                        <?php echo toPersianNumbers($totalProducts); ?> محصول یافت شد
                    </span>
                </div>
                <div class="products-header-right">
                    <div class="view-mode">
                        <button class="grid-view active" title="نمایش شبکه‌ای">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="list-view" title="نمایش لیستی">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    <div class="sort-by">
                        <label for="sort">مرتب‌سازی:</label>
                        <select id="sort" name="sort" onchange="changeSort()">
                            <option value="default" <?php echo $sort === 'default' ? 'selected' : ''; ?>>پیش‌فرض</option>
                            <option value="new" <?php echo $sort === 'new' ? 'selected' : ''; ?>>جدیدترین</option>
                            <option value="featured" <?php echo $sort === 'featured' ? 'selected' : ''; ?>>ویژه</option>
                            <option value="bestselling" <?php echo $sort === 'bestselling' ? 'selected' : ''; ?>>پرفروش‌ترین</option>
                            <option value="price-low" <?php echo $sort === 'price-low' ? 'selected' : ''; ?>>ارزان‌ترین</option>
                            <option value="price-high" <?php echo $sort === 'price-high' ? 'selected' : ''; ?>>گران‌ترین</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="products-grid products-content">
                <?php if (empty($paginatedProducts)): ?>
                    <div class="no-products">
                        <i class="fas fa-search"></i>
                        <h3>محصولی یافت نشد</h3>
                        <p>متاسفانه هیچ محصولی با معیارهای جستجو شما یافت نشد.</p>
                        <a href="products.php" class="btn btn-primary">بازگشت به همه محصولات</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($paginatedProducts as $product): ?>
                        <?php include 'includes/product_card.php'; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Products List (Alternative View) -->
            <div class="products-list products-content" style="display: none;">
                <?php foreach ($paginatedProducts as $product): ?>
                    <div class="product-card-list">
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="product-image">
                            <img src="<?php echo $product['image_path'] ?: 'assets/images/no-image.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </a>
                        <div class="product-info">
                            <h3><a href="product.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a></h3>
                            <div class="product-rating">
                                <?php 
                                $rating = getAverageProductRating($product['id']);
                                for ($i = 1; $i <= 5; $i++):
                                    echo '<i class="fas fa-star ' . ($i <= $rating ? 'filled' : '') . '"></i>';
                                endfor;
                                ?>
                                <span>(<?php echo toPersianNumbers(getProductReviewCount($product['id'])); ?>)</span>
                            </div>
                            <p><?php echo htmlspecialchars(substr($product['description'], 0, 150)) . '...'; ?></p>
                            <div class="product-footer">
                                <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                                <button class="add-to-cart btn btn-primary btn-sm" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-shopping-cart"></i>
                                    افزودن به سبد
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-item">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="page-item disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="page-item active"><?php echo toPersianNumbers($i); ?></span>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="page-item">
                                <?php echo toPersianNumbers($i); ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-item">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php else: ?>
                        <span class="page-item disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Related Products Modal -->
<div class="modal" id="related-products-modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <button class="modal-close"><i class="fas fa-times"></i></button>
        <h2>محصولات مرتبط</h2>
        <div class="related-products-grid" id="related-products-container"></div>
    </div>
</div>

<script>
// View mode toggle
const gridViewBtn = document.querySelector('.grid-view');
const listViewBtn = document.querySelector('.list-view');
const productsGrid = document.querySelector('.products-grid');
const productsList = document.querySelector('.products-list');

if (gridViewBtn && listViewBtn && productsGrid && productsList) {
    gridViewBtn.addEventListener('click', () => {
        gridViewBtn.classList.add('active');
        listViewBtn.classList.remove('active');
        productsGrid.style.display = 'grid';
        productsList.style.display = 'none';
    });
    
    listViewBtn.addEventListener('click', () => {
        gridViewBtn.classList.remove('active');
        listViewBtn.classList.add('active');
        productsGrid.style.display = 'none';
        productsList.style.display = 'block';
    });
}

// Sort change
function changeSort() {
    const sortSelect = document.getElementById('sort');
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('sort', sortSelect.value);
    currentUrl.searchParams.delete('page');
    window.location.href = currentUrl.toString();
}

// Apply filters
function applyFilters() {
    const minPrice = document.getElementById('min_price').value;
    const maxPrice = document.getElementById('max_price').value;
    const inStock = document.querySelector('input[name="in_stock"]:checked');
    const outOfStock = document.querySelector('input[name="out_of_stock"]:checked');
    const featured = document.querySelector('input[name="featured"]:checked');
    
    const params = new URLSearchParams();
    
    if (minPrice) params.set('min_price', minPrice);
    if (maxPrice) params.set('max_price', maxPrice);
    if (inStock) params.set('in_stock', '1');
    if (outOfStock) params.set('out_of_stock', '1');
    if (featured) params.set('featured', '1');
    
    <?php if ($categoryId): ?>
        params.set('category', '<?php echo $categoryId; ?>');
    <?php endif; ?>
    
    <?php if ($searchQuery): ?>
        params.set('query', '<?php echo urlencode($searchQuery); ?>');
    <?php endif; ?>
    
    window.location.href = 'products.php?' + params.toString();
}

// Price slider
initPriceSlider();
</script>

<?php require_once 'includes/footer.php'; ?>
