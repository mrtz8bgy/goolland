<?php
session_start();
require_once 'includes/db.php';

// Get parameters
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : null;
$searchQuery = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 9;

// Get blog categories
$blogCategories = getBlogCategories();

// Get selected category
$selectedCategory = null;
if ($categoryId) {
    foreach ($blogCategories as $category) {
        if ($category['id'] == $categoryId) {
            $selectedCategory = $category;
            break;
        }
    }
}

// Get blog posts
$posts = [];
$totalPosts = 0;

if ($categoryId) {
    $posts = getBlogPosts(null, $categoryId, true);
} elseif ($searchQuery) {
    // Search posts
    try {
        $stmt = $pdo->prepare("SELECT bp.*, u.name as author_name, bc.name as category_name FROM blog_posts bp LEFT JOIN users u ON bp.author_id = u.id LEFT JOIN blog_categories bc ON bp.category_id = bc.id WHERE bp.is_published = 1 AND (bp.title LIKE ? OR bp.content LIKE ? OR bp.excerpt LIKE ?) ORDER BY bp.published_at DESC");
        $searchParam = "%$searchQuery%";
        $stmt->execute([$searchParam, $searchParam, $searchParam]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $posts = [];
    }
} else {
    $posts = getBlogPosts(null, null, true);
}

$totalPosts = count($posts);
$totalPages = ceil($totalPosts / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedPosts = array_slice($posts, $offset, $perPage);

// Get featured posts
$featuredPosts = [];
try {
    $stmt = $pdo->query("SELECT bp.*, u.name as author_name, bc.name as category_name FROM blog_posts bp LEFT JOIN users u ON bp.author_id = u.id LEFT JOIN blog_categories bc ON bp.category_id = bc.id WHERE bp.is_published = 1 AND bp.is_featured = 1 ORDER BY bp.published_at DESC LIMIT 3");
    $featuredPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $featuredPosts = [];
}

// Get recent posts
$recentPosts = [];
try {
    $stmt = $pdo->query("SELECT bp.*, u.name as author_name, bc.name as category_name FROM blog_posts bp LEFT JOIN users u ON bp.author_id = u.id LEFT JOIN blog_categories bc ON bp.category_id = bc.id WHERE bp.is_published = 1 ORDER BY bp.published_at DESC LIMIT 5");
    $recentPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentPosts = [];
}

// Get popular posts (most viewed)
$popularPosts = [];
try {
    $stmt = $pdo->query("SELECT bp.*, u.name as author_name, bc.name as category_name FROM blog_posts bp LEFT JOIN users u ON bp.author_id = u.id LEFT JOIN blog_categories bc ON bp.category_id = bc.id WHERE bp.is_published = 1 ORDER BY bp.view_count DESC LIMIT 5");
    $popularPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $popularPosts = [];
}

// Get tags from posts
$tags = [];
try {
    $stmt = $pdo->query("SELECT meta_keywords FROM blog_posts WHERE is_published = 1 AND meta_keywords IS NOT NULL");
    $allTags = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($allTags as $tagString) {
        $tagArray = explode(',', $tagString);
        foreach ($tagArray as $tag) {
            $tag = trim($tag);
            if (!empty($tag) && !in_array($tag, $tags)) {
                $tags[] = $tag;
            }
        }
    }
} catch (PDOException $e) {
    $tags = [];
}

$pageTitle = 'بلاگ' . ($selectedCategory ? ' - ' . htmlspecialchars($selectedCategory['name']) : '') . ($searchQuery ? ' - جستجوی: ' . htmlspecialchars($searchQuery) : '');
$pageDescription = 'مقالات و راهنماهای گل و گیاه در فروشگاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>بلاگ گولند</h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <?php if ($selectedCategory): ?>
                <a href="blog.php">بلاگ</a>
                <i class="fas fa-chevron-left"></i>
                <span><?php echo htmlspecialchars($selectedCategory['name']); ?></span>
            <?php else: ?>
                <span>بلاگ</span>
            <?php endif; ?>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="blog-page">
    <div class="container">
        <div class="blog-container">
            <!-- Main Content -->
            <main class="blog-main">
                <!-- Featured Posts -->
                <?php if (!empty($featuredPosts)): ?>
                    <section class="featured-posts">
                        <h2 class="section-title"><i class="fas fa-star"></i> مقالات ویژه</h2>
                        <div class="featured-posts-grid">
                            <?php foreach ($featuredPosts as $post): ?>
                                <article class="featured-post-card">
                                    <a href="blog-post.php?slug=<?php echo $post['slug']; ?>">
                                        <div class="featured-post-image">
                                            <?php if ($post['featured_image']): ?>
                                                <img src="<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                            <?php else: ?>
                                                <img src="assets/images/default-blog.jpg" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                            <?php endif; ?>
                                        </div>
                                        <div class="featured-post-content">
                                            <div class="featured-post-category">
                                                <?php if ($post['category_name']): ?>
                                                    <span><?php echo htmlspecialchars($post['category_name']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                                            <p><?php echo htmlspecialchars(substr($post['excerpt'], 0, 150)) . '...'; ?></p>
                                            <div class="featured-post-meta">
                                                <span><i class="fas fa-calendar"></i> <?php echo formatDate($post['published_at']); ?></span>
                                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author_name'] ?? 'ادمین'); ?></span>
                                            </div>
                                        </div>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- Posts List -->
                <section class="posts-list">
                    <div class="posts-list-header">
                        <h2 class="section-title">
                            <i class="fas fa-list"></i>
                            <?php echo $selectedCategory ? htmlspecialchars($selectedCategory['name']) : ($searchQuery ? 'نتایج جستجوی: ' . htmlspecialchars($searchQuery) : 'همه مقالات'); ?>
                        </h2>
                        <span class="posts-count"><?php echo toPersianNumbers($totalPosts); ?> مقاله</span>
                    </div>
                    
                    <?php if (empty($paginatedPosts)): ?>
                        <div class="no-posts">
                            <i class="fas fa-newspaper"></i>
                            <h3>مقاله‌ای یافت نشد</h3>
                            <p>متاسفانه هیچ مقاله‌ای با معیارهای جستجو شما یافت نشد.</p>
                        </div>
                    <?php else: ?>
                        <div class="posts-grid">
                            <?php foreach ($paginatedPosts as $post): ?>
                                <article class="post-card fade-in">
                                    <a href="blog-post.php?slug=<?php echo $post['slug']; ?>">
                                        <div class="post-image">
                                            <?php if ($post['featured_image']): ?>
                                                <img src="<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                            <?php else: ?>
                                                <img src="assets/images/default-blog.jpg" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                            <?php endif; ?>
                                        </div>
                                        <div class="post-content">
                                            <div class="post-category">
                                                <?php if ($post['category_name']): ?>
                                                    <a href="blog.php?category=<?php echo $post['category_id'] ?? 0; ?>">
                                                        <?php echo htmlspecialchars($post['category_name']); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                                            <p class="post-excerpt"><?php echo htmlspecialchars(substr($post['excerpt'], 0, 120)) . '...'; ?></p>
                                            <div class="post-meta">
                                                <span><i class="fas fa-calendar"></i> <?php echo formatDate($post['published_at']); ?></span>
                                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author_name'] ?? 'ادمین'); ?></span>
                                                <span><i class="fas fa-eye"></i> <?php echo toPersianNumbers($post['view_count'] ?? 0); ?> بازدید</span>
                                            </div>
                                            <span class="read-more">ادامه مطلب <i class="fas fa-arrow-left"></i></span>
                                        </div>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
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
                </section>
            </main>
            
            <!-- Sidebar -->
            <aside class="blog-sidebar">
                <!-- Search Widget -->
                <div class="sidebar-widget search-widget">
                    <h3 class="widget-title"><i class="fas fa-search"></i> جستجو در بلاگ</h3>
                    <form action="blog.php" method="get" class="search-form">
                        <input type="text" name="query" placeholder="جستجوی مقالات..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <!-- Categories Widget -->
                <div class="sidebar-widget categories-widget">
                    <h3 class="widget-title"><i class="fas fa-folder-open"></i> دسته‌بندی‌ها</h3>
                    <ul>
                        <li><a href="blog.php" class="<?php echo !$categoryId && !$searchQuery ? 'active' : ''; ?>">همه دسته‌بندی‌ها</a></li>
                        <?php foreach ($blogCategories as $category): ?>
                            <?php
                            // Count posts in this category
                            $postCount = 0;
                            try {
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts WHERE category_id = ? AND is_published = 1");
                                $stmt->execute([$category['id']]);
                                $postCount = $stmt->fetchColumn();
                            } catch (PDOException $e) {
                                $postCount = 0;
                            }
                            ?>
                            <li>
                                <a href="blog.php?category=<?php echo $category['id']; ?>" 
                                   class="<?php echo $categoryId == $category['id'] ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                    <span class="post-count"><?php echo toPersianNumbers($postCount); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Recent Posts Widget -->
                <div class="sidebar-widget recent-posts-widget">
                    <h3 class="widget-title"><i class="fas fa-clock"></i> جدیدترین مقالات</h3>
                    <ul>
                        <?php foreach ($recentPosts as $post): ?>
                            <li>
                                <a href="blog-post.php?slug=<?php echo $post['slug']; ?>">
                                    <div class="recent-post-image">
                                        <?php if ($post['featured_image']): ?>
                                            <img src="<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                        <?php else: ?>
                                            <img src="assets/images/default-blog.jpg" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="recent-post-info">
                                        <h4><?php echo htmlspecialchars($post['title']); ?></h4>
                                        <span class="recent-post-date"><?php echo formatDate($post['published_at']); ?></span>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Popular Posts Widget -->
                <div class="sidebar-widget popular-posts-widget">
                    <h3 class="widget-title"><i class="fas fa-fire"></i> پر بازدیدترین مقالات</h3>
                    <ul>
                        <?php foreach ($popularPosts as $post): ?>
                            <li>
                                <a href="blog-post.php?slug=<?php echo $post['slug']; ?>">
                                    <div class="popular-post-image">
                                        <?php if ($post['featured_image']): ?>
                                            <img src="<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                        <?php else: ?>
                                            <img src="assets/images/default-blog.jpg" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="popular-post-info">
                                        <h4><?php echo htmlspecialchars($post['title']); ?></h4>
                                        <span class="popular-post-views"><i class="fas fa-eye"></i> <?php echo toPersianNumbers($post['view_count'] ?? 0); ?></span>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Tags Widget -->
                <?php if (!empty($tags)): ?>
                    <div class="sidebar-widget tags-widget">
                        <h3 class="widget-title"><i class="fas fa-tags"></i> برچسب‌ها</h3>
                        <div class="tags-list">
                            <?php foreach ($tags as $tag): ?>
                                <a href="blog.php?query=<?php echo urlencode($tag); ?>">
                                    <?php echo htmlspecialchars($tag); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Newsletter Widget -->
                <div class="sidebar-widget newsletter-widget">
                    <h3 class="widget-title"><i class="fas fa-paper-plane"></i> خبرنامه</h3>
                    <p>برای دریافت آخرین مقالات و اخبار در خبرنامه عضو شوید</p>
                    <form action="includes/newsletter.php" method="post" class="newsletter-form">
                        <input type="email" name="email" placeholder="آدرس ایمیل" required>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-paper-plane"></i>
                            عضویت
                        </button>
                    </form>
                </div>
            </aside>
        </div>
    </div>
</div>

<!-- Related Products Section -->
<?php
$relatedProducts = getFeaturedProducts(4);
if (!empty($relatedProducts)):
?>
    <section class="related-products-section">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-gift"></i> محصولات ویژه</h2>
            <div class="products-grid">
                <?php foreach ($relatedProducts as $product): ?>
                    <?php include 'includes/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<script>
// Blog page specific scripts
</script>

<?php require_once 'includes/footer.php'; ?>
