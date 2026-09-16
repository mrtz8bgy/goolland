<?php
// Get settings for footer
$settings = [];
$result = $conn->query("SELECT * FROM settings LIMIT 1");
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
}

// Get categories for footer
$categories = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC LIMIT 6");

// Get recent articles for footer
$articles = $conn->query("SELECT * FROM articles WHERE status = 'publish' ORDER BY published_at DESC LIMIT 3");

// Site stats
$products_count = getTableCount('products', "status = 'publish'", $conn);
$articles_count = getTableCount('articles', "status = 'publish'", $conn);
$categories_count = getTableCount('categories', "status = 'active'", $conn);
?>

    </main>

    <!-- Newsletter Section -->
    <section class="newsletter-section">
        <div class="container">
            <div class="newsletter-content">
                <div class="newsletter-text">
                    <h3>📧 از آخرین خبرها باخبر شوید</h3>
                    <p>برای دریافت تخفیف‌ها و خبرهای ویژه، ایمیل خود را ثبت کنید</p>
                </div>
                <form class="newsletter-form" action="/subscribe.php" method="POST">
                    <input type="email" name="email" placeholder="آدرس ایمیل خود را وارد کنید..." required>
                    <button type="submit" class="btn btn-primary">
                        عضویت
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container footer-inner">
            <!-- About Section -->
            <div class="footer-section footer-about">
                <h3 class="footer-title">
                    <?php if (!empty($settings['logo'])): ?>
                        <img src="/assets/images/<?php echo htmlspecialchars($settings['logo']); ?>" alt="<?php echo htmlspecialchars($settings['site_name'] ?? 'Goolland'); ?>" style="height: 40px; margin-bottom: 10px;">
                    <?php else: ?>
                        🌿
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($settings['site_name'] ?? 'Goolland'); ?></span>
                </h3>
                <p class="footer-description">
                    <?php echo htmlspecialchars($settings['about_text'] ?? 'فروشگاه آنلاین گل و گیاه با بهترین کیفیت و قیمت مناسب'); ?>
                </p>
                <div class="footer-social">
                    <?php if (!empty($settings['instagram'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['instagram']); ?>" target="_blank" rel="noopener noreferrer" title="اینستاگرام">
                            <span class="social-icon">📸</span>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['telegram'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['telegram']); ?>" target="_blank" rel="noopener noreferrer" title="تلگرام">
                            <span class="social-icon">💬</span>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['whatsapp'])): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['whatsapp']); ?>" target="_blank" rel="noopener noreferrer" title="واتساپ">
                            <span class="social-icon">🟢</span>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['facebook']); ?>" target="_blank" rel="noopener noreferrer" title="فیسبوک">
                            <span class="social-icon">📘</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-section">
                <h3 class="footer-title">لینک‌های سریع</h3>
                <ul class="footer-links">
                    <li><a href="/">خانه</a></li>
                    <li><a href="/about.php">درباره ما</a></li>
                    <li><a href="/products.php">محصولات</a></li>
                    <li><a href="/articles.php">مقالات</a></li>
                    <li><a href="/contact.php">تماس با ما</a></li>
                    <li><a href="/cart.php">سبد خرید</a></li>
                </ul>
            </div>

            <!-- Categories -->
            <div class="footer-section">
                <h3 class="footer-title">دسته‌بندی‌ها</h3>
                <ul class="footer-links">
                    <?php if ($categories && $categories->num_rows > 0): ?>
                        <?php while ($category = $categories->fetch_assoc()): ?>
                            <li>
                                <a href="/products.php?category=<?php echo htmlspecialchars($category['slug']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li><a href="/products.php">همه محصولات</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Latest Articles -->
            <div class="footer-section">
                <h3 class="footer-title">آخرین مقالات</h3>
                <ul class="footer-articles">
                    <?php if ($articles && $articles->num_rows > 0): ?>
                        <?php while ($article = $articles->fetch_assoc()): ?>
                            <li>
                                <a href="/article.php?id=<?php echo $article['id']; ?>">
                                    <?php echo htmlspecialchars(mb_substr($article['title'], 0, 50)); ?>
                                </a>
                                <span class="article-date">
                                    <?php echo date('Y/m/d', strtotime($article['published_at'] ?? $article['created_at'])); ?>
                                </span>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li><a href="/articles.php">مشاهده همه مقالات</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="footer-section">
                <h3 class="footer-title">اطلاعات تماس</h3>
                <ul class="footer-contact">
                    <?php if (!empty($settings['address'])): ?>
                        <li>
                            <span class="contact-icon">📍</span>
                            <span><?php echo htmlspecialchars($settings['address']); ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($settings['phone'])): ?>
                        <li>
                            <span class="contact-icon">📞</span>
                            <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $settings['phone']); ?>">
                                <?php echo htmlspecialchars($settings['phone']); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($settings['email'])): ?>
                        <li>
                            <span class="contact-icon">✉️</span>
                            <a href="mailto:<?php echo htmlspecialchars($settings['email']); ?>">
                                <?php echo htmlspecialchars($settings['email']); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($settings['working_hours'])): ?>
                        <li>
                            <span class="contact-icon">⏰</span>
                            <span><?php echo htmlspecialchars($settings['working_hours']); ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="container">
                <div class="footer-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($products_count); ?></span>
                        <span class="stat-label">محصول</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($articles_count); ?></span>
                        <span class="stat-label">مقاله</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($categories_count); ?></span>
                        <span class="stat-label">دسته‌بندی</span>
                    </div>
                </div>
                <div class="footer-copyright">
                    <p>
                        © <?php echo date('Y'); ?> 
                        <strong><?php echo htmlspecialchars($settings['site_name'] ?? 'Goolland'); ?></strong>
                        . تمامی حقوق محفوظ است.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="back-to-top" class="back-to-top" title="بازگشت به بالا">
        ↑
    </button>

    <!-- WhatsApp Float Button -->
    <?php if (!empty($settings['whatsapp'])): ?>
        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['whatsapp']); ?>" 
           class="whatsapp-float" 
           target="_blank" 
           rel="noopener noreferrer"
           title="واتساپ">
            🟢
        </a>
    <?php endif; ?>

    <!-- JavaScript -->
    <script src="/assets/js/script.js"></script>
    
    <!-- Google Analytics (if configured) -->
    <?php if (!empty($settings['google_analytics'])): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($settings['google_analytics']); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo htmlspecialchars($settings["google_analytics"]); ?>');
        </script>
    <?php endif; ?>

    <!-- Schema.org Markup -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?php echo addslashes($settings['site_name'] ?? 'Goolland'); ?>",
        "url": "<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>",
        "description": "<?php echo addslashes($settings['description'] ?? 'فروشگاه آنلاین گل و گیاه'); ?>",
        "logo": "<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . ($settings['logo'] ? '/assets/images/' . $settings['logo'] : '/assets/images/logo.png'); ?>",
        "contactPoint": [
            {
                "@type": "ContactPoint",
                "telephone": "<?php echo preg_replace('/[^0-9+]/', '', $settings['phone'] ?? ''); ?>",
                "contactType": "customer service"
            }
        ],
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "تهران",
            "addressCountry": "IR",
            "streetAddress": "<?php echo addslashes($settings['address'] ?? ''); ?>"
        },
        "sameAs": [
            <?php if (!empty($settings['instagram'])): ?>"<?php echo addslashes($settings['instagram']); ?>",<?php endif; ?>
            <?php if (!empty($settings['telegram'])): ?>"<?php echo addslashes($settings['telegram']); ?>",<?php endif; ?>
            <?php if (!empty($settings['whatsapp'])): ?>"https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['whatsapp']); ?>"<?php endif; ?>
        ]
    }
    </script>

</body>
</html>
