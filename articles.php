<?php
require_once "includes/header.php";

// Get current category from URL
$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

// Base query
$query = "SELECT articles.*, categories.name AS category_name, categories.slug AS category_slug FROM articles LEFT JOIN categories ON articles.category_id = categories.id";
$count_query = "SELECT COUNT(*) as total FROM articles LEFT JOIN categories ON articles.category_id = categories.id";
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
    $where[] = "(articles.title LIKE ? OR articles.content LIKE ? OR articles.excerpt LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}

// Only published articles
$where[] = "articles.status = 'publish'";

// Combine where clauses
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
    $count_query .= " WHERE " . implode(" AND ", $where);
}

// Order by
$query .= " ORDER BY articles.published_at DESC, articles.created_at DESC";

// Limit for pagination
$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

// Get articles
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$articles = $stmt->get_result();

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

// Get popular articles
$popular_articles = $conn->query("SELECT articles.*, categories.name AS category_name FROM articles LEFT JOIN categories ON articles.category_id = categories.id WHERE articles.status = 'publish' ORDER BY articles.view_count DESC LIMIT 5");

// Get recent articles
$recent_articles = $conn->query("SELECT articles.*, categories.name AS category_name FROM articles LEFT JOIN categories ON articles.category_id = categories.id WHERE articles.status = 'publish' ORDER BY articles.published_at DESC LIMIT 5");

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
                <h1>مقالات <?php echo htmlspecialchars($current_category['name']); ?></h1>
                <p><?php echo htmlspecialchars($current_category['description'] ?? 'مقالات این دسته‌بندی'); ?></p>
            <?php elseif (!empty($search_query)): ?>
                <h1>نتایج جستجو برای "<?php echo htmlspecialchars($search_query); ?>"</h1>
                <p><?php echo $total; ?> مقاله پیدا شد</p>
            <?php else: ?>
                <h1>مقالات آموزشی</h1>
                <p>مطالب مفید درباره گل و گیاه</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Articles Section -->
<section class="section">
    <div class="container">
        <div class="articles-layout">
            <!-- Sidebar -->
            <aside class="articles-sidebar">
                <!-- Categories Filter -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">دسته‌بندی‌ها</h3>
                    <ul class="widget-list">
                        <li class="<?php echo empty($category_slug) ? 'active' : ''; ?>">
                            <a href="/articles.php">همه مقالات</a>
                        </li>
                        <?php if ($categories && $categories->num_rows > 0): ?>
                            <?php 
                            $categories->data_seek(0); // Reset result pointer
                            while ($category = $categories->fetch_assoc()): 
                            ?>
                                <li class="<?php echo $category['slug'] === $category_slug ? 'active' : ''; ?>">
                                    <a href="/articles.php?category=<?php echo htmlspecialchars($category['slug']); ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                        <span class="count">
                                            <?php
                                            $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM articles WHERE category_id = ? AND status = 'publish'");
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
                
                <!-- Popular Articles -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">مقالات پربازدید</h3>
                    <ul class="widget-articles">
                        <?php if ($popular_articles && $popular_articles->num_rows > 0): ?>
                            <?php while ($article = $popular_articles->fetch_assoc()): ?>
                                <li>
                                    <a href="/article.php?id=<?php echo $article['id']; ?>">
                                        <?php if (!empty($article['image'])): ?>
                                            <img src="/assets/images/articles/<?php echo htmlspecialchars($article['image']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                                        <?php else: ?>
                                            <div class="widget-article-image">📝</div>
                                        <?php endif; ?>
                                        <div class="widget-article-info">
                                            <h4><?php echo htmlspecialchars(mb_substr($article['title'], 0, 40)); ?></h4>
                                            <span class="view-count">👁️ <?php echo number_format($article['view_count']); ?> بازدید</span>
                                        </div>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Recent Articles -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">آخرین مقالات</h3>
                    <ul class="widget-articles">
                        <?php if ($recent_articles && $recent_articles->num_rows > 0): ?>
                            <?php while ($article = $recent_articles->fetch_assoc()): ?>
                                <li>
                                    <a href="/article.php?id=<?php echo $article['id']; ?>">
                                        <?php if (!empty($article['image'])): ?>
                                            <img src="/assets/images/articles/<?php echo htmlspecialchars($article['image']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                                        <?php else: ?>
                                            <div class="widget-article-image">📄</div>
                                        <?php endif; ?>
                                        <div class="widget-article-info">
                                            <h4><?php echo htmlspecialchars(mb_substr($article['title'], 0, 40)); ?></h4>
                                            <span class="date">📅 <?php echo date('Y/m/d', strtotime($article['published_at'] ?? $article['created_at'])); ?></span>
                                        </div>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Tags Cloud -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">برچسب‌ها</h3>
                    <div class="tags-cloud">
                        <a href="/articles.php?q=نگهداری">نگهداری</a>
                        <a href="/articles.php?q=گیاهان">گیاهان</a>
                        <a href="/articles.php?q=آپارتمانی">آپارتمانی</a>
                        <a href="/articles.php?q=گل">گل</a>
                        <a href="/articles.php?q=آموزشی">آموزشی</a>
                        <a href="/articles.php?q=دکوراسیون">دکوراسیون</a>
                        <a href="/articles.php?q=باغبانی">باغبانی</a>
                        <a href="/articles.php?q=سلامت">سلامت</a>
                    </div>
                </div>
            </aside>
            
            <!-- Main Content -->
            <main class="articles-main">
                <!-- Articles Grid -->
                <?php if ($articles && $articles->num_rows > 0): ?>
                    <div class="articles-grid">
                        <?php while ($article = $articles->fetch_assoc()): ?>
                            <article class="article-card" data-aos="fade-up">
                                <a href="/article.php?id=<?php echo $article['id']; ?>">
                                    <div class="article-image-wrapper">
                                        <?php if (!empty($article['image'])): ?>
                                            <img src="/assets/images/articles/<?php echo htmlspecialchars($article['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($article['title']); ?>" 
                                                 class="article-image" 
                                                 loading="lazy">
                                        <?php else: ?>
                                            <div class="article-image-placeholder">📝</div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($article['category_name'])): ?>
                                            <span class="article-category">
                                                <?php echo htmlspecialchars($article['category_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="article-content">
                                        <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                                        
                                        <div class="article-meta">
                                            <span class="article-date">
                                                📅 <?php echo date('Y/m/d', strtotime($article['published_at'] ?? $article['created_at'])); ?>
                                            </span>
                                            <?php if (!empty($article['author'])): ?>
                                                <span class="article-author">
                                                    ✍️ <?php echo htmlspecialchars($article['author']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="article-views">
                                                👁️ <?php echo number_format($article['view_count']); ?> بازدید
                                            </span>
                                        </div>
                                        
                                        <div class="article-excerpt">
                                            <?php echo htmlspecialchars(mb_substr(strip_tags($article['excerpt'] ?? $article['content']), 0, 150)); ?>...
                                        </div>
                                        
                                        <span class="read-more">
                                            ادامه مطلب
                                            <span class="arrow">→</span>
                                        </span>
                                    </div>
                                </a>
                            </article>
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
                    <div class="no-articles">
                        <div class="no-articles-icon">📝</div>
                        <h3>مقاله‌ای یافت نشد</h3>
                        <p>متاسفانه هیچ مقاله‌ای با فیلترهای اعمال شده یافت نشد.</p>
                        <a href="/articles.php" class="btn btn-primary">
                            بازگردانی فیلترها
                        </a>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>می‌خواهید بیشتر بدانید؟</h2>
            <p>مقالات ما را بخوانید یا با تیم ما تماس بگیرید</p>
            <div class="cta-buttons">
                <a href="/products.php" class="btn btn-primary">
                    مشاهده محصولات
                </a>
                <a href="/contact.php" class="btn btn-secondary">
                    تماس با ما
                </a>
            </div>
        </div>
    </div>
</section>

<?php
require_once "includes/footer.php";
?>
