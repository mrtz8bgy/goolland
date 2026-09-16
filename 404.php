<?php
session_start();
require_once 'includes/config.php';

$pageTitle = 'صفحه یافت نشد - 404';
$pageDescription = 'صفحه مورد نظر یافت نشد';
require_once 'includes/header.php';
?>

<!-- 404 Section -->
<section class="error-section">
    <div class="container">
        <div class="error-content">
            <div class="error-code">404</div>
            <h1>صفحه یافت نشد</h1>
            <p>متاسفانه صفحه‌ای که به دنبال آن هستید یافت نشد یا حذف شده است.</p>
            
            <div class="error-actions">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    بازگشت به خانه
                </a>
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-shopping-bag"></i>
                    خرید گل
                </a>
                <a href="contact.php" class="btn btn-secondary">
                    <i class="fas fa-envelope"></i>
                    تماس با ما
                </a>
            </div>
        </div>
        
        <div class="error-image">
            <img src="assets/images/404-flower.jpg" alt="گل 404">
        </div>
    </div>
</section>

<!-- Suggestions -->
<section class="suggestions-section">
    <div class="container">
        <h2>شاید به دنبال یکی از این صفحات باشید:</h2>
        <div class="suggestions-grid">
            <a href="index.php" class="suggestion-card">
                <i class="fas fa-home"></i>
                <span>خانه</span>
            </a>
            <a href="products.php" class="suggestion-card">
                <i class="fas fa-shopping-bag"></i>
                <span>محصولات</span>
            </a>
            <a href="blog.php" class="suggestion-card">
                <i class="fas fa-newspaper"></i>
                <span>بلاگ</span>
            </a>
            <a href="about.php" class="suggestion-card">
                <i class="fas fa-info-circle"></i>
                <span>درباره ما</span>
            </a>
            <a href="contact.php" class="suggestion-card">
                <i class="fas fa-envelope"></i>
                <span>تماس با ما</span>
            </a>
        </div>
    </div>
</section>

<!-- Search Section -->
<section class="error-search">
    <div class="container">
        <h2>یا جستجو کنید:</h2>
        <form action="search.php" method="get" class="search-form">
            <input type="text" name="query" placeholder="جستجوی محصولات..." required>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i>
                جستجو
            </button>
        </form>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
