<?php

require_once "includes/db.php";

$settings = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc();

$categories = $conn->query("
    SELECT *
    FROM categories
    ORDER BY id DESC
");

$products = $conn->query("
    SELECT products.*, categories.name AS category_name
    FROM products
    LEFT JOIN categories
        ON products.category_id = categories.id
    WHERE products.status = 'publish'
    ORDER BY products.id DESC
    LIMIT 8
");

$articles = $conn->query("
    SELECT articles.*, categories.name AS category_name
    FROM articles
    LEFT JOIN categories
        ON articles.category_id = categories.id
    WHERE articles.status = 'publish'
    ORDER BY articles.id DESC
    LIMIT 6
");

require_once "includes/header.php";

?>

<!-- Hero Section -->
<section class="hero">
    <div class="container hero-content">
        <div class="hero-text">
            <h1>
                <?php echo htmlspecialchars($settings["site_name"] ?? "Goolland"); ?>
            </h1>
            <p>
                <?php
                echo htmlspecialchars(
                    $settings["description"]
                    ?? "به دنیای گل‌ها و گیاهان زیبا خوش آمدید."
                );
                ?>
            </p>
            <div class="hero-buttons">
                <a href="#products" class="btn btn-primary">
                    مشاهده محصولات
                </a>
                <a href="/contact.php" class="btn btn-secondary">
                    تماس با ما
                </a>
            </div>
        </div>
        <div class="hero-image">
            🌿🌱🪴
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>دسته‌بندی‌ها</h2>
            <p>محصولات مورد نظر خود را انتخاب کنید</p>
        </div>
        <div class="cards">
            <?php if ($categories && $categories->num_rows > 0): ?>
                <?php while ($category = $categories->fetch_assoc()): ?>
                    <a href="/products.php?category=<?php echo htmlspecialchars($category['slug']); ?>" class="card">
                        <h3>
                            🌱
                            <?php echo htmlspecialchars($category["name"]); ?>
                        </h3>
                        <p>مشاهده محصولات این دسته</p>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="card">
                    <h3>هنوز دسته‌ای ثبت نشده</h3>
                    <p>از پنل مدیریت یک دسته‌بندی اضافه کنید.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Products Section -->
<section class="section" id="products">
    <div class="container">
        <div class="section-title">
            <h2>محصولات ما</h2>
            <p>جدیدترین گل‌ها و گیاهان</p>
        </div>
        <div class="products">
            <?php if ($products && $products->num_rows > 0): ?>
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="product-card">
                        <a href="/product.php?id=<?php echo $product['id']; ?>">
                            <div class="product-image-wrapper">
                                <?php if (!empty($product["image"])): ?>
                                    <img
                                        src="/assets/images/products/<?php echo htmlspecialchars($product["image"]); ?>"
                                        alt="<?php echo htmlspecialchars($product["name"]); ?>"
                                        class="product-image"
                                    >
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
                                <?php if (!empty($product["category_name"])): ?>
                                    <span class="product-category">
                                        <?php echo htmlspecialchars($product["category_name"]); ?>
                                    </span>
                                <?php endif; ?>
                                <h3><?php echo htmlspecialchars($product["name"]); ?></h3>
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
            <?php else: ?>
                <div class="card">
                    <h3>هنوز محصولی ثبت نشده</h3>
                    <p>از پنل مدیریت یک محصول اضافه کنید.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Articles Section -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>آخرین مقالات</h2>
            <p>مطالب آموزشی درباره گل و گیاه</p>
        </div>
        <div class="articles-grid">
            <?php if ($articles && $articles->num_rows > 0): ?>
                <?php while ($article = $articles->fetch_assoc()): ?>
                    <article class="article-card">
                        <a href="/article.php?id=<?php echo $article['id']; ?>">
                            <div class="article-image-wrapper">
                                <?php if (!empty($article['image'])): ?>
                                    <img
                                        src="/assets/images/articles/<?php echo htmlspecialchars($article['image']); ?>"
                                        alt="<?php echo htmlspecialchars($article['title']); ?>"
                                        class="article-card-image"
                                    >
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
            <?php else: ?>
                <div class="card">
                    <h3>هنوز مقاله‌ای ثبت نشده</h3>
                    <p>از پنل مدیریت یک مقاله اضافه کنید.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>ارتباط با ما</h2>
            <p>برای دریافت اطلاعات بیشتر با ما در تماس باشید.</p>
        </div>
        <div class="contact-info-grid">
            <?php if (!empty($settings["phone"])): ?>
                <div class="contact-card">
                    <div class="contact-icon">📞</div>
                    <div class="contact-content">
                        <h3>تلفن</h3>
                        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $settings['phone']); ?>">
                            <?php echo htmlspecialchars($settings['phone']); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($settings["email"])): ?>
                <div class="contact-card">
                    <div class="contact-icon">✉️</div>
                    <div class="contact-content">
                        <h3>ایمیل</h3>
                        <a href="mailto:<?php echo htmlspecialchars($settings['email']); ?>">
                            <?php echo htmlspecialchars($settings['email']); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($settings["address"])): ?>
                <div class="contact-card">
                    <div class="contact-icon">📍</div>
                    <div class="contact-content">
                        <h3>آدرس</h3>
                        <p><?php echo nl2br(htmlspecialchars($settings['address'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="contact-card">
                <div class="contact-icon">🌿</div>
                <div class="contact-content">
                    <h3>Goolland</h3>
                    <p>همراه شما برای انتخاب و نگهداری بهتر گل و گیاه.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once "includes/footer.php";
?>
