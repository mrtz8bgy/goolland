<?php
session_start();
require_once 'includes/db.php';

// Get site info
$siteName = getSetting('site_name', 'گولند');
$siteDescription = getSetting('site_description', 'فروشگاه آنلاین گل و گیاه با کیفیت بالا');

// Get page content
$aboutPage = getPageBySlug('about');

// Get testimonials
$stmt = $pdo->query("SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.is_approved = 1 AND r.rating >= 4 ORDER BY RAND() LIMIT 6");
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get team members (static for now)
$teamMembers = [
    [
        'name' => 'مهدی محمدی',
        'position' => 'بنیان‌گذار و مدیر عامل',
        'image' => 'assets/images/team/team1.jpg',
        'description' => 'بنیان‌گذار گولند با بیش از 15 سال تجربه در صنعت گل و گیاه'
    ],
    [
        'name' => 'fatemeh احمدی',
        'position' => 'مدیر بازاریابی',
        'image' => 'assets/images/team/team2.jpg',
        'description' => 'متخصص در بازاریابی دیجیتال و استراتژی‌های فروش'
    ],
    [
        'name' => 'علی رضا زاده',
        'position' => 'سرپرست گلخانه',
        'image' => 'assets/images/team/team3.jpg',
        'description' => 'متخصص در پرورش گل‌ها و گیاهان آپارتمانی'
    ],
    [
        'name' => 'زهرا کریمی',
        'position' => 'مشاور مشتریان',
        'image' => 'assets/images/team/team4.jpg',
        'description' => 'مشاور ارشد در انتخاب گل‌ها و هدایای مناسب'
    ]
];

$pageTitle = 'درباره ما';
$pageDescription = 'درباره فروشگاه آنلاین گل و گیاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>درباره ما</h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <span>درباره ما</span>
        </nav>
    </div>
</div>

<!-- About Section -->
<section class="about-section">
    <div class="container">
        <div class="about-container">
            <!-- About Content -->
            <div class="about-content">
                <div class="about-header">
                    <h2>داستان ما</h2>
                    <p class="about-subtitle">گولند: جایی که زیبایی با طبیعت ملاقات می‌کند</p>
                </div>
                
                <div class="about-text">
                    <?php if ($aboutPage && $aboutPage['content']): ?>
                        <?php echo $aboutPage['content']; ?>
                    <?php else: ?>
                        <p>
                            فروشگاه آنلاین گل و گیاه گولند در سال 1395 با هدف ارائه گل‌ها و گیاهان با کیفیت به مشتریان تاسیس شد. 
                            ما با داشتن گلخانه‌های مدرن و تیم متخصص، محصولات تازه و با کیفیت را در اختیار شما قرار می‌دهیم.
                        </p>
                        <p>
                            گولند تنها یک فروشگاه نیست، بلکه تجربه‌ای متفاوت از خرید گل و گیاه است. ما معتقدیم که گل‌ها می‌توانند 
                            شادی و زیبایی را به زندگی شما بیاورند و لحظه‌های خاص را به یادماندنی‌ترین لحظات تبدیل کنند.
                        </p>
                        <p>
                            با بیش از 1000 نوع گل و گیاه مختلف، گولند بزرگ‌ترین مجموعه گل و گیاه در ایران است. ما از گل‌های 
                            سنتی ایرانی تا گیاهان نادر و خاص را ارائه می‌دهیم تا برای هر سلیقه و هر مناسبت، محصول مناسبی داشته باشیم.
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Statistics -->
                <div class="about-stats">
                    <div class="stat-item">
                        <div class="stat-number">
                            <span class="counter" data-count="1500">0</span>
                            <span>+</span>
                        </div>
                        <div class="stat-label">محصول مختلف</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">
                            <span class="counter" data-count="5000">0</span>
                            <span>+</span>
                        </div>
                        <div class="stat-label">مشتری راضی</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">
                            <span class="counter" data-count="10">0</span>
                            <span>+</span>
                        </div>
                        <div class="stat-label">سال تجربه</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">
                            <span class="counter" data-count="98">0</span>
                            <span>%</span>
                        </div>
                        <div class="stat-label">رضایت مشتریان</div>
                    </div>
                </div>
            </div>
            
            <!-- About Image -->
            <div class="about-image">
                <img src="assets/images/about-us.jpg" alt="درباره گولند">
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="why-choose-us">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">چرا گولند را انتخاب کنیم؟</h2>
            <p>ما در گولند تعهد داریم که بهترین تجربه خرید را برای شما فراهم کنیم</p>
        </div>
        
        <div class="why-choose-grid">
            <div class="why-choose-item">
                <div class="why-choose-icon">
                    <i class="fas fa-seedling"></i>
                </div>
                <h3>گل‌های تازه و با کیفیت</h3>
                <p>تمامی گل‌های ما از گلخانه‌های معتبر تهیه می‌شوند و تا زمان تحویل تازگی خود را حفظ می‌کنند</p>
            </div>
            
            <div class="why-choose-item">
                <div class="why-choose-icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <h3>ارسال سریع و مطمئن</h3>
                <p>با سیستم حمل و نقل پیشرفته، سفارش شما در سریع‌ترین زمان ممکن به دست شما می‌رسد</p>
            </div>
            
            <div class="why-choose-item">
                <div class="why-choose-icon">
                    <i class="fas fa-leaf"></i>
                </div>
                <h3>بسته‌بندی ویژه</h3>
                <p>هر گل با دقت و ظرافت خاصی بسته‌بندی می‌شود تا در طول حمل و نقل آسیب نبینند</p>
            </div>
            
            <div class="why-choose-item">
                <div class="why-choose-icon">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <h3>خدمات مشتری عالی</h3>
                <p>تیم پشتیبانی ما در تمام ساعات شبانه‌روز آماده پاسخگویی به سوالات و نیازهای شما است</p>
            </div>
            
            <div class="why-choose-item">
                <div class="why-choose-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <h3>گارانتی رضایت</h3>
                <p>اگر از خرید خود راضی نبودید، پول شما را برگردانده و محصول را پس می‌گیریم</p>
            </div>
            
            <div class="why-choose-item">
                <div class="why-choose-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <h3>هدایای ویژه</h3>
                <p>با خرید از گولند، علاوه بر گل‌های زیبا، هدایای ویژه و کارت‌های تبریک هم دریافت می‌کنید</p>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="team-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">تیم حرفه‌ای ما</h2>
            <p>با تیم متخصص و با تجربه گولند آشنا شوید</p>
        </div>
        
        <div class="team-grid">
            <?php foreach ($teamMembers as $member): ?>
                <div class="team-card">
                    <div class="team-image">
                        <?php if (file_exists($member['image'])): ?>
                            <img src="<?php echo $member['image']; ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                        <?php else: ?>
                            <div class="team-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="team-info">
                        <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                        <p class="team-position"><?php echo htmlspecialchars($member['position']); ?></p>
                        <p class="team-description"><?php echo htmlspecialchars($member['description']); ?></p>
                    </div>
                    <div class="team-social">
                        <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<?php if (!empty($testimonials)): ?>
    <section class="testimonials-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">نظرات مشتریان</h2>
                <p>شما می‌توانید نظرات مشتریان راضی ما را بخوانید</p>
            </div>
            
            <div class="testimonials-grid">
                <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-card">
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
        </div>
    </section>
<?php endif; ?>

<!-- Partners Section -->
<section class="partners-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">همکاران ما</h2>
            <p>ما با برندها و شرکت‌های معتبر همکاری می‌کنیم</p>
        </div>
        
        <div class="partners-grid">
            <div class="partner-logo">
                <img src="assets/images/brands/brand1.png" alt="همکار 1">
            </div>
            <div class="partner-logo">
                <img src="assets/images/brands/brand2.png" alt="همکار 2">
            </div>
            <div class="partner-logo">
                <img src="assets/images/brands/brand3.png" alt="همکار 3">
            </div>
            <div class="partner-logo">
                <img src="assets/images/brands/brand4.png" alt="همکار 4">
            </div>
            <div class="partner-logo">
                <img src="assets/images/brands/brand5.png" alt="همکار 5">
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-container">
            <div class="cta-content">
                <h2>برای خرید گل‌های زیبا و با کیفیت اقدام کنید</h2>
                <p>گولند با ارائه گل‌ها و گیاهان با کیفیت و خدمات حرفه‌ای، تجربه‌ای متفاوت از خرید آنلاین را برای شما فراهم می‌کند.</p>
            </div>
            <div class="cta-buttons">
                <a href="products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag"></i>
                    خرید گل
                </a>
                <a href="contact.php" class="btn btn-outline-white btn-lg">
                    <i class="fas fa-envelope"></i>
                    تماس با ما
                </a>
            </div>
        </div>
    </div>
</section>

<script>
// Counter animation
function animateCounters() {
    const counters = document.querySelectorAll('.counter');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.dataset.count);
                const duration = 2000;
                const step = target / (duration / 16);
                let current = 0;
                
                const updateCounter = () => {
                    current += step;
                    if (current < target) {
                        counter.textContent = Math.floor(current).toLocaleString('fa-IR');
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target.toLocaleString('fa-IR');
                    }
                };
                
                updateCounter();
                observer.unobserve(counter);
            }
        });
    }, { threshold: 0.5 });
    
    counters.forEach(counter => observer.observe(counter));
}

// Initialize counters
document.addEventListener('DOMContentLoaded', animateCounters);
</script>

<?php require_once 'includes/footer.php'; ?>
