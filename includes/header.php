<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
require_once ROOT_PATH . '/includes/config.php';

// Get site settings
$siteName = getSetting('site_name', 'گولند - فروشگاه گل و گیاه');
$siteDescription = getSetting('site_description', 'فروشگاه آنلاین گل و گیاه با کیفیت بالا');
$siteLogo = getSetting('site_logo', 'assets/images/logo.png');
$siteFavicon = getSetting('site_favicon', 'assets/images/favicon.ico');
$sitePhone = getSetting('site_phone', '021-12345678');
$siteEmail = getSetting('site_email', 'info@goolland.ir');

// Get current user info
$currentUser = [];
if (isLoggedIn()) {
    $currentUser = getUserById(getCurrentUserId());
}

// Get cart count
$cartCount = isLoggedIn() ? getCartCount(getCurrentUserId()) : 0;

// Get wishlist count
$wishlistCount = isLoggedIn() ? getWishlistCount(getCurrentUserId()) : 0;

// Get current URL
$currentUrl = $_SERVER['REQUEST_URI'];

// Check if we're in admin panel
$isAdminPanel = strpos($currentUrl, '/admin/') !== false;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($pageTitle ?? $siteName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription ?? $siteDescription); ?>">
    <meta name="keywords" content="گل, گیاه, فروشگاه گل, خرید گل, گل آنلاین, هدیه گل, گلدان, گیاه آپارتمانی">
    <meta name="author" content="گولند">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:site_name" content="<?php echo htmlspecialchars($siteName); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle ?? $siteName); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription ?? $siteDescription); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $siteFavicon; ?>">
    <link rel="apple-touch-icon" href="<?php echo $siteFavicon; ?>">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/slick.min.css">
    <link rel="stylesheet" href="assets/css/slick-theme.min.css">
    <link rel="stylesheet" href="assets/css/nice-select.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (file_exists('assets/css/custom.css')): ?>
        <link rel="stylesheet" href="assets/css/custom.css">
    <?php endif; ?>
    
    <!-- JS Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="assets/js/nice-select.min.js"></script>
    <script src="assets/js/slick.min.js"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Inline CSS for critical rendering -->
    <style>
        :root {
            --primary-color: #2e7d32;
            --primary-dark: #1b5e20;
            --primary-light: #4caf50;
            --secondary-color: #ff9800;
            --secondary-dark: #e68a00;
            --secondary-light: #ffb74d;
            --accent-color: #9c27b0;
            --text-color: #333;
            --text-light: #666;
            --text-lighter: #999;
            --bg-color: #fff;
            --bg-light: #f8f9fa;
            --bg-dark: #f5f5f5;
            --border-color: #e0e0e0;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --info-color: #2196f3;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            font-family: 'Vazirmatn', sans-serif;
        }
        
        body {
            direction: rtl;
            text-align: right;
        }
    </style>
    
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <?php
    $googleAnalytics = getSetting('google_analytics', '');
    if (!empty($googleAnalytics)):
    ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($googleAnalytics); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo htmlspecialchars($googleAnalytics); ?>');
        </script>
    <?php endif; ?>
    
    <!-- No JavaScript Fallback -->
    <noscript>
        <style>
            .js-required { display: none !important; }
        </style>
    </noscript>
</head>
<body class="<?php echo $isAdminPanel ? 'admin-panel' : 'frontend'; ?>">
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-left">
                <span>به فروشگاه گل و گیاه گولند خوش آمدید</span>
            </div>
            <div class="top-bar-right">
                <ul class="top-links">
                    <?php if (!isLoggedIn()): ?>
                        <li><a href="register.php"><i class="fas fa-user-plus"></i> ثبت‌نام</a></li>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> ورود</a></li>
                    <?php else: ?>
                        <li><a href="profile.php"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['name'] ?? 'حساب کاربری'); ?></a></li>
                        <li><a href="orders.php"><i class="fas fa-list"></i> سفارشات من</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> خروج</a></li>
                    <?php endif; ?>
                    <li><a href="contact.php"><i class="fas fa-envelope"></i> تماس با ما</a></li>
                </ul>
                
                <!-- Language Switcher -->
                <div class="language-switcher">
                    <a href="#" class="active">فارسی</a>
                    <span>|</span>
                    <a href="#">English</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-container">
                <!-- Logo -->
                <div class="site-logo">
                    <a href="index.php">
                        <?php if (file_exists($siteLogo)): ?>
                            <img src="<?php echo $siteLogo; ?>" alt="<?php echo htmlspecialchars($siteName); ?>">
                        <?php else: ?>
                            <div class="logo-placeholder">
                                <i class="fas fa-leaf"></i>
                                <span><?php echo htmlspecialchars($siteName); ?></span>
                            </div>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- Search Bar -->
                <div class="search-container">
                    <form action="search.php" method="get" class="search-form">
                        <input type="text" name="query" placeholder="جستجوی محصولات..." value="<?php echo htmlspecialchars($_GET['query'] ?? ''); ?>">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <!-- Header Actions -->
                <div class="header-actions">
                    <!-- Wishlist -->
                    <a href="wishlist.php" class="header-action wishlist-action">
                        <i class="fas fa-heart"></i>
                        <?php if ($wishlistCount > 0): ?>
                            <span class="action-count"><?php echo toPersianNumbers($wishlistCount); ?></span>
                        <?php endif; ?>
                        <span class="action-label">علاقه‌مندی‌ها</span>
                    </a>
                    
                    <!-- Cart -->
                    <a href="cart.php" class="header-action cart-action">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cartCount > 0): ?>
                            <span class="action-count"><?php echo toPersianNumbers($cartCount); ?></span>
                        <?php endif; ?>
                        <span class="action-label">سبد خرید</span>
                    </a>
                    
                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Navigation -->
    <nav class="main-navigation">
        <div class="container">
            <div class="nav-container">
                <!-- Categories Menu -->
                <div class="categories-menu">
                    <button class="categories-toggle">
                        <i class="fas fa-bars"></i>
                        <span>دسته‌بندی‌ها</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="categories-dropdown">
                        <ul>
                            <?php
                            $mainCategories = getAllCategories();
                            foreach ($mainCategories as $category):
                                // Check if category has subcategories
                                $subCategories = [];
                                try {
                                    $stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? AND is_active = 1 ORDER BY name");
                                    $stmt->execute([$category['id']]);
                                    $subCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch (PDOException $e) {
                                    $subCategories = [];
                                }
                            ?>
                                <li class="<?php echo !empty($subCategories) ? 'has-subcategories' : ''; ?>">
                                    <a href="products.php?category=<?php echo $category['id']; ?>">
                                        <?php if ($category['image_path']): ?>
                                            <img src="<?php echo $category['image_path']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-leaf"></i>
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                                    </a>
                                    <?php if (!empty($subCategories)): ?>
                                        <i class="fas fa-chevron-left"></i>
                                        <ul class="subcategories">
                                            <?php foreach ($subCategories as $subCategory): ?>
                                                <li>
                                                    <a href="products.php?category=<?php echo $subCategory['id']; ?>">
                                                        <span><?php echo htmlspecialchars($subCategory['name']); ?></span>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <!-- All Products Link -->
                        <div class="categories-footer">
                            <a href="products.php">
                                <i class="fas fa-list"></i>
                                همه محصولات
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Main Menu -->
                <ul class="main-menu">
                    <li class="<?php echo strpos($currentUrl, 'index.php') !== false || $currentUrl === '/' ? 'active' : ''; ?>">
                        <a href="index.php"><i class="fas fa-home"></i> خانه</a>
                    </li>
                    <li class="<?php echo strpos($currentUrl, 'products.php') !== false ? 'active' : ''; ?>">
                        <a href="products.php"><i class="fas fa-shopping-bag"></i> محصولات</a>
                    </li>
                    <li class="<?php echo strpos($currentUrl, 'blog.php') !== false ? 'active' : ''; ?>">
                        <a href="blog.php"><i class="fas fa-newspaper"></i> بلاگ</a>
                    </li>
                    <li class="<?php echo strpos($currentUrl, 'about.php') !== false ? 'active' : ''; ?>">
                        <a href="about.php"><i class="fas fa-info-circle"></i> درباره ما</a>
                    </li>
                    <li class="<?php echo strpos($currentUrl, 'contact.php') !== false ? 'active' : ''; ?>">
                        <a href="contact.php"><i class="fas fa-envelope"></i> تماس با ما</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay" onclick="toggleMobileMenu()"></div>
    <div class="mobile-menu">
        <div class="mobile-menu-header">
            <button class="mobile-menu-close" onclick="toggleMobileMenu()">
                <i class="fas fa-times"></i>
            </button>
            <span>منو</span>
        </div>
        <div class="mobile-menu-content">
            <ul class="mobile-main-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> خانه</a></li>
                <li><a href="products.php"><i class="fas fa-shopping-bag"></i> محصولات</a></li>
                <li><a href="blog.php"><i class="fas fa-newspaper"></i> بلاگ</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> درباره ما</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope"></i> تماس با ما</a></li>
                
                <?php if (!isLoggedIn()): ?>
                    <li><a href="register.php"><i class="fas fa-user-plus"></i> ثبت‌نام</a></li>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> ورود</a></li>
                <?php else: ?>
                    <li><a href="profile.php"><i class="fas fa-user"></i> حساب کاربری</a></li>
                    <li><a href="orders.php"><i class="fas fa-list"></i> سفارشات من</a></li>
                    <li><a href="wishlist.php"><i class="fas fa-heart"></i> علاقه‌مندی‌ها</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> خروج</a></li>
                <?php endif; ?>
            </ul>
            
            <div class="mobile-categories">
                <h4><i class="fas fa-bars"></i> دسته‌بندی‌ها</h4>
                <ul>
                    <?php
                    $mobileCategories = getAllCategories();
                    foreach ($mobileCategories as $category):
                    ?>
                        <li>
                            <a href="products.php?category=<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper">
