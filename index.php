<?php
require_once 'includes/db.php';

// Get site settings
$site_name = getSetting('site_name', 'گولند - فروشگاه گل و گیاه');
$site_description = getSetting('site_description', 'فروشگاه آنلاین گل و گیاه با کیفیت بالا');

// Get featured products
$featuredProducts = getFeaturedProducts(8);

// Get new arrival products
$newProducts = getNewArrivalProducts(8);

// Get best selling products
$bestSellingProducts = getBestSellingProducts(8);

// Get all categories
$categories = getAllCategories();

// Get banners
$banners = getBannersByPosition('home');

// Get testimonials (from reviews)
$stmt = $pdo->query("SELECT r.*, COALESCE(NULLIF(r.author_name, ''), NULLIF(CONCAT_WS(' ', u.first_name, u.last_name), ''), 'مشتری') as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.status = 'approved' AND r.rating >= 4 ORDER BY RAND() LIMIT 4");
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get blog posts
$blogPosts = getBlogPosts(3);

// Get brands (static for now)
$brands = [
    ['name' => 'گولند', 'logo' => 'assets/images/brands/brand1.png'],
    ['name' => 'گل‌سرا', 'logo' => 'assets/images/brands/brand2.png'],
    ['name' => 'گیاهان سبز', 'logo' => 'assets/images/brands/brand3.png'],
    ['name' => 'باغ بان', 'logo' => 'assets/images/brands/brand4.png'],
    ['name' => 'گل‌ستان', 'logo' => 'assets/images/brands/brand5.png'],
];

$pageTitle = $site_name;
$pageDescription = $site_description;
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <span class="hero-subtitle">به فروشگاه گل و گیاه گولند خوش آمدید</span>
            <h1>گل‌های <span>تازه و زیبا</span> برای هر مناسبت</h1>
            <p>گولند با ارائه گل‌ها و گیاهان با کیفیت و خدمات حرفه‌ای، تجربه‌ای متفاوت از خرید آنلاین را برای شما فراهم می‌کند.</p>
            <div class="hero-buttons">
                <a href="products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag"></i>
                    خرید گل
                </a>
                <a href="#categories" class="btn btn-outline-white btn-lg">
                    <i class="fas fa-list"></i>
                    دسته‌بندی‌ها
                </a>
            </div>
        </div>
        <div class="hero-image">
            <img src="assets/images/hero-flower-bouquet.jpg" alt="گل‌های تازه و زیبا">
            <div class="hero-badge">
                تخفیف ویژه
                <small>تا 30% تخفیف</small>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section" id="categories">
    <div class="container">
        <h2 class="section-title">دسته‌بندی‌های محبوب</h2>
        <div class="categories-grid">
            <?php foreach (array_slice($categories, 0, 8) as $category): ?>
                <div class="category-card fade-in">
                    <a href="products.php?category=<?php echo $category['id']; ?>">
                        <div class="category-image">
                            <?php if ($category['image_path']): ?>
                                <img src="<?php echo $category['image_path']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-leaf"></i>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                        <span class="category-count">
                            <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                            $stmt->execute([$category['id']]);
                            echo toPersianNumbers($stmt->fetchColumn()) . ' محصول';
                            ?>
                        </span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Banners Section -->
<?php if (!empty($banners)): ?>
    <section class="banner-section">
        <div class="container">
            <div class="banner-grid">
                <?php foreach ($banners as $banner): ?>
                    <div class="banner-card fade-in">
                        <?php if ($banner['image_path']): ?>
                            <img src="<?php echo $banner['image_path']; ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>" class="banner-image">
                        <?php endif; ?>
                        <div class="banner-overlay"></div>
                        <div class="banner-content">
                            <?php if ($banner['title']): ?>
                                <h3><?php echo htmlspecialchars($banner['title']); ?></h3>
                            <?php endif; ?>
                            <?php if ($banner['subtitle']): ?>
                                <p><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                            <?php endif; ?>
                            <?php if ($banner['link']): ?>
                                <a href="<?php echo $banner['link']; ?>" class="btn btn-primary">
                                    <?php echo htmlspecialchars($banner['button_text'] ?? 'مشاهده'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Featured Products Section -->
<section class="products-section">
    <div class="container">
        <div class="products-header">
            <h2 class="section-title">محصولات ویژه</h2>
            <a href="products.php?featured=1" class="view-all">مشاهده همه</a>
        </div>
        <div class="products-grid">
            <?php foreach ($featuredProducts as $product): ?>
                <?php include 'includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <h2 class="section-title">چرا از گولند خرید کنیم؟</h2>
        <div class="features-grid">
            <div class="feature-card fade-in">
                <div class="feature-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3>ارسال سریع</h3>
                <p>ارسال گل‌ها و گیاهان شما در سریع‌ترین زمان ممکن با بسته‌بندی ویژه</p>
            </div>
            <div class="feature-card fade-in delay-1">
                <div class="feature-icon">
                    <i class="fas fa-leaf"></i>
                </div>
                <h3>گل‌های تازه</h3>
                <p>تمامی گل‌های ما از گلخانه‌های معتبر تهیه می‌شوند و تازگی خود را حفظ می‌کنند</p>
            </div>
            <div class="feature-card fade-in delay-2">
                <div class="feature-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <h3>گارانتی راضی بودن</h3>
                <p>اگر از خرید خود راضی نبودید، پول شما را برگردانده و محصول را پس می‌گیریم</p>
            </div>
            <div class="feature-card fade-in delay-3">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>پشتیبانی 24/7</h3>
                <p>تیم پشتیبانی ما در تمام ساعات شبانه‌روز آماده پاسخگویی به شما است</p>
            </div>
        </div>
    </div>
</section>

<!-- New Arrival Products Section -->
<section class="products-section" style="background: white;">
    <div class="container">
        <div class="products-header">
            <h2 class="section-title">تازه‌ترین محصولات</h2>
            <a href="products.php?sort=new" class="view-all">مشاهده همه</a>
        </div>
        <div class="products-grid">
            <?php foreach ($newProducts as $product): ?>
                <?php include 'includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<?php if (!empty($testimonials)): ?>
    <section class="testimonials-section">
        <div class="container">
            <h2 class="section-title">نظرات مشتریان</h2>
            <div class="testimonials-slider">
                <div class="testimonials-track">
                    <?php foreach ($testimonials as $testimonial): ?>
                        <div class="testimonial-card fade-in">
                            <div class="testimonial-header">
                                <div class="testimonial-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="testimonial-author">
                                    <h4><?php echo htmlspecialchars($testimonial['user_name'] ?? 'مشتری'); ?></h4>
                                    <span class="author-role">مشتری</span>
                                </div>
                            </div>
                            <div class="testimonial-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $testimonial['rating'] ? 'filled' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="testimonial-text">
                                <?php echo htmlspecialchars($testimonial['comment'] ?? $testimonial['title']); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="slider-dots">
                    <?php for ($i = 0; $i < count($testimonials); $i++): ?>
                        <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>" data-slide="<?php echo $i; ?>"></span>
                    <?php endfor; ?>
                </div>
                <div class="slider-nav">
                    <button class="prev"><i class="fas fa-chevron-right"></i></button>
                    <button class="next"><i class="fas fa-chevron-left"></i></button>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Blog Section -->
<?php if (!empty($blogPosts)): ?>
    <section class="blog-section">
        <div class="container">
            <div class="blog-header">
                <h2 class="section-title">مقالات و راهنماها</h2>
                <a href="blog.php" class="view-all">مشاهده همه</a>
            </div>
            <div class="blog-grid">
                <?php foreach ($blogPosts as $post): ?>
                    <article class="blog-card fade-in">
                        <a href="blog-post.php?slug=<?php echo $post['slug']; ?>">
                            <div class="blog-image">
                                <?php if ($post['featured_image']): ?>
                                    <img src="<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                <?php else: ?>
                                    <img src="assets/images/default-blog.jpg" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="blog-content">
                                <div class="blog-meta">
                                    <span><i class="fas fa-calendar"></i> <?php echo formatDate($post['published_at']); ?></span>
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author_name'] ?? 'ادمین'); ?></span>
                                </div>
                                <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($post['excerpt'], 0, 100)) . '...'; ?></p>
                                <span class="read-more">ادامه مطلب <i class="fas fa-arrow-left"></i></span>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Brands Section -->
<section class="brands-section">
    <div class="container">
        <div class="brands-grid">
            <?php foreach ($brands as $brand): ?>
                <div class="brand-logo">
                    <?php if (file_exists($brand['logo'])): ?>
                        <img src="<?php echo $brand['logo']; ?>" alt="<?php echo htmlspecialchars($brand['name']); ?>">
                    <?php else: ?>
                        <span><?php echo htmlspecialchars($brand['name']); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter-section">
    <div class="container">
        <div class="newsletter-content">
            <div>
                <h2>برای دریافت اخبار و تخفیف‌ها عضو شوید</h2>
                <p>با عضویت در خبرنامه گولند، از آخرین تخفیف‌ها، محصولات جدید و رویدادهای ویژه مطلع شوید.</p>
            </div>
            <form action="includes/newsletter.php" method="post" class="newsletter-form">
                <input type="email" name="email" placeholder="آدرس ایمیل خود را وارد کنید" required>
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-paper-plane"></i>
                    عضویت
                </button>
            </form>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
