<?php
session_start();
require_once "includes/db.php";';

// Get search query
$query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : null;
$minPrice = isset($_GET['min_price']) ? intval(toEnglishNumbers($_GET['min_price'])) : null;
$maxPrice = isset($_GET['max_price']) ? intval(toEnglishNumbers($_GET['max_price'])) : null;
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'relevance';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 12;

// Get categories
$categories = getAllCategories();

// Search products
$products = [];
$totalProducts = 0;

if (!empty($query) || $categoryId || $minPrice !== null || $maxPrice !== null) {
    $products = searchProducts($query, $categoryId, $minPrice, $maxPrice);
    
    // Apply sorting
    switch ($sort) {
        case 'new':
            usort($products, function($a, $b) {
                return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
            });
            break;
        case 'price-low':
            usort($products, function($a, $b) {
                return $a['price'] - $b['price'];
            });
            break;
        case 'price-high':
            usort($products, function($a, $b) {
                return $b['price'] - $a['price'];
            });
            break;
        case 'rating':
            usort($products, function($a, $b) {
                $ratingA = getAverageProductRating($a['id']);
                $ratingB = getAverageProductRating($b['id']);
                return $ratingB <=> $ratingA;
            });
            break;
        default:
            // Relevance sort (by search match)
            if (!empty($query)) {
                usort($products, function($a, $b) use ($query) {
                    $scoreA = 0;
                    $scoreB = 0;
                    
                    // Check title match
                    if (strpos(strtolower($a['name']), strtolower($query)) !== false) $scoreA += 10;
                    if (strpos(strtolower($b['name']), strtolower($query)) !== false) $scoreB += 10;
                    
                    // Check description match
                    if (strpos(strtolower($a['description'] ?? ''), strtolower($query)) !== false) $scoreA += 5;
                    if (strpos(strtolower($b['description'] ?? ''), strtolower($query)) !== false) $scoreB += 5;
                    
                    // Check category match
                    if (isset($a['category_name']) && strpos(strtolower($a['category_name']), strtolower($query)) !== false) $scoreA += 3;
                    if (isset($b['category_name']) && strpos(strtolower($b['category_name']), strtolower($query)) !== false) $scoreB += 3;
                    
                    return $scoreB - $scoreA;
                });
            }
            break;
    }
    
    $totalProducts = count($products);
} else {
    // No search query, redirect to products page
    header("Location: products.php");
    exit;
}

// Pagination
$totalPages = ceil($totalProducts / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedProducts = array_slice($products, $offset, $perPage);

// Get price range for filter
$minPriceFilter = 0;
$maxPriceFilter = 0;
if (!empty($products)) {
    $prices = array_column($products, 'price');
    $minPriceFilter = min($prices);
    $maxPriceFilter = max($prices);
}

// Get all products price range
$allMinPrice = 0;
$allMaxPrice = 0;
try {
    $stmt = $pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE is_active = 1");
    $priceRange = $stmt->fetch(PDO::FETCH_ASSOC);
    $allMinPrice = (int)($priceRange['min_price'] ?? 0);
    $allMaxPrice = (int)($priceRange['max_price'] ?? 0);
} catch (PDOException $e) {
    $allMinPrice = 0;
    $allMaxPrice = 0;
}

$pageTitle = 'جستجوی محصولات - ' . htmlspecialchars($query);
$pageDescription = 'نتایج جستجوی ' . htmlspecialchars($query) . ' در فروشگاه گل و گیاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>نتایج جستجوی: <?php echo htmlspecialchars($query); ?></h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <span>جستجو</span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="search-page">
    <div class="container">
        <div class="search-container">
            <!-- Search Summary -->
            <div class="search-summary">
                <h2><i class="fas fa-search"></i> نتایج جستجو</h2>
                <p>
                    <?php echo toPersianNumbers($totalProducts); ?> محصول برای "<?php echo htmlspecialchars($query); ?>" یافت شد
                    <?php if ($categoryId): ?>
                        در دسته‌بندی <?php 
                        $category = getCategoryById($categoryId);
                        echo htmlspecialchars($category['name'] ?? '');
                        ?>
                    <?php endif; ?>
                </p>
            </div>
            
            <!-- Filter Bar -->
            <div class="search-filter-bar">
                <form method="GET" action="search.php" class="search-filters">
                    <input type="hidden" name="query" value="<?php echo htmlspecialchars($query); ?>">
                    
                    <div class="filter-group">
                        <label for="category">دسته‌بندی</label>
                        <select id="category" name="category" class="form-control select-control" onchange="this.form.submit()">
                            <option value="">همه دسته‌بندی‌ها</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort">مرتب‌سازی</label>
                        <select id="sort" name="sort" class="form-control select-control" onchange="this.form.submit()">
                            <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>مرتبط‌ترین</option>
                            <option value="new" <?php echo $sort === 'new' ? 'selected' : ''; ?>>جدیدترین</option>
                            <option value="price-low" <?php echo $sort === 'price-low' ? 'selected' : ''; ?>>ارزان‌ترین</option>
                            <option value="price-high" <?php echo $sort === 'price-high' ? 'selected' : ''; ?>>گران‌ترین</option>
                            <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>بیشترین امتیاز</option>
                        </select>
                    </div>
                    
                    <div class="filter-group price-filter-group">
                        <label>محدوده قیمت (تومان)</label>
                        <div class="price-filter">
                            <input type="number" name="min_price" 
                                   class="form-control" 
                                   placeholder="از" 
                                   value="<?php echo $minPrice !== null ? toPersianNumbers($minPrice) : ''; ?>">
                            <span>تا</span>
                            <input type="number" name="max_price" 
                                   class="form-control" 
                                   placeholder="تا" 
                                   value="<?php echo $maxPrice !== null ? toPersianNumbers($maxPrice) : ''; ?>">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-filter"></i>
                                فیلتر
                            </button>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <a href="search.php?query=<?php echo urlencode($query); ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-redo"></i>
                            بازنشانی
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Search Results -->
            <?php if (empty($paginatedProducts)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>محصولی یافت نشد</h3>
                    <p>متاسفانه هیچ محصولی با معیارهای جستجو شما یافت نشد.</p>
                    <div class="no-results-actions">
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-list"></i>
                            مشاهده همه محصولات
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-home"></i>
                            بازگشت به خانه
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="search-results">
                    <div class="products-grid">
                        <?php foreach ($paginatedProducts as $product): ?>
                            <?php include 'includes/product_card.php'; ?>
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
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Related Searches -->
<?php
// Get related searches (popular search terms)
$relatedSearches = [];
try {
    $stmt = $pdo->query("SELECT query, COUNT(*) as count FROM search_history WHERE query LIKE '%$query%' GROUP BY query ORDER BY count DESC LIMIT 5");
    $relatedSearches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $relatedSearches = [];
}

if (!empty($relatedSearches)):
?>
    <section class="related-searches">
        <div class="container">
            <h3><i class="fas fa-lightbulb"></i> جستجوهای مرتبط</h3>
            <div class="related-searches-list">
                <?php foreach ($relatedSearches as $search): ?>
                    <a href="search.php?query=<?php echo urlencode($search['query']); ?>">
                        <?php echo htmlspecialchars($search['query']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Popular Products -->
<?php
$popularProducts = getBestSellingProducts(4);
if (!empty($popularProducts)):
?>
    <section class="popular-products-section">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-fire"></i> پرفروش‌ترین محصولات</h2>
            <div class="products-grid">
                <?php foreach ($popularProducts as $product): ?>
                    <?php include 'includes/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<script>
// Search page specific scripts
</script>

<?php require_once 'includes/footer.php'; ?>
