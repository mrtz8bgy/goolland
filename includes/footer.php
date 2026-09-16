<?php
// Get site settings
$siteName = getSetting('site_name', 'گولند - فروشگاه گل و گیاه');
$siteDescription = getSetting('site_description', 'فروشگاه آنلاین گل و گیاه با کیفیت بالا');
$sitePhone = getSetting('site_phone', '021-12345678');
$siteEmail = getSetting('site_email', 'info@goolland.ir');
$siteAddress = getSetting('site_address', 'تهران، خیابان ولیعصر، پلاک 123');

// Get social media links
$socialMedia = getSocialMediaLinks();

// Get pages for footer
$pages = getSitePages();

// Check if we're in admin panel
$currentUrl = $_SERVER['REQUEST_URI'];
$isAdminPanel = strpos($currentUrl, '/admin/') !== false;

// Close main content wrapper
if (!$isAdminPanel):
    echo '</div> <!-- .main-content-wrapper -->';
?>

<?php if (!$isAdminPanel): ?>
    <!-- Newsletter Section -->
    <section class="newsletter-section footer-newsletter">
        <div class="container">
            <div class="newsletter-content">
                <div>
                    <h3><i class="fas fa-paper-plane"></i> برای دریافت اخبار و تخفیف‌ها عضو شوید</h3>
                    <p>با عضویت در خبرنامه گولند، از آخرین تخفیف‌ها، محصولات جدید و رویدادهای ویژه مطلع شوید.</p>
                </div>
                <form action="includes/newsletter.php" method="post" class="newsletter-form">
                    <input type="email" name="email" placeholder="آدرس ایمیل خود را وارد کنید" required>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        عضویت
                    </button>
                </form>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-container">
            <!-- Footer Info -->
            <div class="footer-info">
                <div class="footer-logo">
                    <?php
                    $siteLogo = getSetting('site_logo', 'assets/images/logo.png');
                    if (file_exists($siteLogo)):
                    ?>
                        <img src="<?php echo $siteLogo; ?>" alt="<?php echo htmlspecialchars($siteName); ?>">
                    <?php else: ?>
                        <div class="logo-placeholder">
                            <i class="fas fa-leaf"></i>
                            <span><?php echo htmlspecialchars($siteName); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="footer-description">
                    <?php echo htmlspecialchars($siteDescription); ?>
                </p>
                
                <!-- Social Media -->
                <div class="footer-social">
                    <?php foreach ($socialMedia as $social): ?>
                        <a href="<?php echo htmlspecialchars($social['url']); ?>" 
                           target="_blank" 
                           title="<?php echo htmlspecialchars($social['name']); ?>"
                           class="social-link">
                            <i class="fab fa-<?php echo htmlspecialchars($social['icon']); ?>"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <!-- Payment Methods -->
                <div class="payment-methods">
                    <h4>روش‌های پرداخت</h4>
                    <div class="payment-icons">
                        <img src="assets/images/payment/zarinpal.png" alt="زارین پال">
                        <img src="assets/images/payment/mellat.png" alt="بانک ملت">
                        <img src="assets/images/payment/saman.png" alt="بانک سامان">
                        <img src="assets/images/payment/tejarat.png" alt="بانک تجارت">
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="footer-links">
                <h4><i class="fas fa-link"></i> لینک‌های سریع</h4>
                <ul>
                    <li><a href="index.php">خانه</a></li>
                    <li><a href="products.php">محصولات</a></li>
                    <li><a href="products.php?featured=1">محصولات ویژه</a></li>
                    <li><a href="products.php?sort=new">محصولات جدید</a></li>
                    <li><a href="products.php?sort=bestselling">پرفروش‌ترین‌ها</a></li>
                </ul>
            </div>
            
            <!-- Customer Service -->
            <div class="footer-links">
                <h4><i class="fas fa-headset"></i> خدمات مشتریان</h4>
                <ul>
                    <li><a href="contact.php">تماس با ما</a></li>
                    <li><a href="about.php">درباره ما</a></li>
                    <li><a href="page.php?slug=terms">قوانین و مقررات</a></li>
                    <li><a href="page.php?slug=privacy">حریم خصوصی</a></li>
                    <li><a href="page.php?slug=return">سیاست بازگرداندن</a></li>
                </ul>
            </div>
            
            <!-- Account -->
            <div class="footer-links">
                <h4><i class="fas fa-user"></i> حساب کاربری</h4>
                <ul>
                    <?php if (!isLoggedIn()): ?>
                        <li><a href="login.php">ورود</a></li>
                        <li><a href="register.php">ثبت‌نام</a></li>
                    <?php else: ?>
                        <li><a href="profile.php">حساب کاربری</a></li>
                        <li><a href="orders.php">سفارشات من</a></li>
                        <li><a href="wishlist.php">علاقه‌مندی‌ها</a></li>
                        <li><a href="logout.php">خروج</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Contact Info -->
            <div class="footer-contact">
                <h4><i class="fas fa-map-marker-alt"></i> اطلاعات تماس</h4>
                <ul>
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($siteAddress); ?></span>
                    </li>
                    <li>
                        <i class="fas fa-phone"></i>
                        <a href="tel:<?php echo htmlspecialchars($sitePhone); ?>">
                            <?php echo htmlspecialchars($sitePhone); ?>
                        </a>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?php echo htmlspecialchars($siteEmail); ?>">
                            <?php echo htmlspecialchars($siteEmail); ?>
                        </a>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <span>شنبه تا چهارشنبه: 9:00 - 18:00</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button class="back-to-top" onclick="scrollToTop()" title="بازگشت به بالا">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- WhatsApp Button -->
<a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $sitePhone); ?>" 
   class="whatsapp-button" 
   target="_blank" 
   title="ارتباط از طریق واتس‌اپ">
    <i class="fab fa-whatsapp"></i>
</a>

<!-- JS Files -->
<script src="assets/js/main.js"></script>
<script src="assets/js/custom.js"></script>

<!-- Custom JS for the page -->
<?php if (isset($pageScripts)): ?>
    <?php echo $pageScripts; ?>
<?php endif; ?>

<!-- Close HTML -->
</body>
</html>
