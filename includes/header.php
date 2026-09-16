<?php
require_once __DIR__ . "/db.php";

// Get settings
$settings = [];
$result = $conn->query("SELECT * FROM settings LIMIT 1");
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
}

// Default values
$site_name = htmlspecialchars($settings["site_name"] ?? "Goolland");
$site_description = htmlspecialchars($settings["description"] ?? "فروشگاه آنلاین گل و گیاه");
$site_logo = !empty($settings["logo"]) ? "/assets/images/" . htmlspecialchars($settings["logo"]) : null;
$site_favicon = !empty($settings["favicon"]) ? "/assets/images/" . htmlspecialchars($settings["favicon"]) : "/assets/images/favicon.ico";

// Current page URL
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// Generate CSRF token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
generateCSRFToken();

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' data: https:; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; connect-src 'self'");
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?php echo $site_name; ?> | فروشگاه گل و گیاه</title>
    <meta name="description" content="<?php echo $site_description; ?>">
    <meta name="keywords" content="گل, گیاه, فروشگاه گل, گل فروشی, گیاه آپارتمانی, گلدان, هدیه گل">
    <meta name="author" content="Goolland">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:site_name" content="<?php echo $site_name; ?>">
    <meta property="og:title" content="<?php echo $site_name; ?> | فروشگاه گل و گیاه">
    <meta property="og:description" content="<?php echo $site_description; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $current_url; ?>">
    <?php if ($site_logo): ?>
    <meta property="og:image" content="<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $site_logo; ?>">
    <?php endif; ?>
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $site_name; ?> | فروشگاه گل و گیاه">
    <meta name="twitter:description" content="<?php echo $site_description; ?>">
    <?php if ($site_logo): ?>
    <meta name="twitter:image" content="<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $site_logo; ?>">
    <?php endif; ?>
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo $current_url; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $site_favicon; ?>">
    <link rel="apple-touch-icon" href="<?php echo $site_favicon; ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="/assets/css/style.css" as="style">
    
    <!-- No JavaScript fallback -->
    <noscript>
        <style>
            .no-js-message {
                display: block;
                background: #ff6b6b;
                color: white;
                padding: 15px;
                text-align: center;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 9999;
            }
        </style>
    </noscript>
</head>

<body>
    <!-- No JS Message -->
    <div class="no-js-message" style="display: none;">
        ⚠️ لطفاً JavaScript را در مرورگر خود فعال کنید تا سایت به درستی نمایش داده شود.
    </div>
    
    <!-- Loading Indicator -->
    <div id="loading-indicator" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center;">
        <div style="color: white; font-size: 24px;">
            🌿 در حال بارگذاری...
        </div>
    </div>

    <!-- Header -->
    <header class="site-header">
        <div class="container header-inner">
            <!-- Logo -->
            <div class="logo">
                <a href="/">
                    <?php if ($site_logo): ?>
                        <img src="<?php echo $site_logo; ?>" alt="<?php echo $site_name; ?>" style="height: 40px; width: auto;">
                    <?php else: ?>
                        🌿
                    <?php endif; ?>
                    <span><?php echo $site_name; ?></span>
                </a>
            </div>

            <!-- Main Menu -->
            <nav class="main-menu">
                <a href="/" class="<?php echo $_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/index.php' ? 'active' : ''; ?>">خانه</a>
                <a href="/about.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'about') !== false ? 'active' : ''; ?>">درباره ما</a>
                <a href="/products.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'products') !== false || strpos($_SERVER['REQUEST_URI'], 'product') !== false ? 'active' : ''; ?>">محصولات</a>
                <a href="/articles.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'articles') !== false || strpos($_SERVER['REQUEST_URI'], 'article') !== false ? 'active' : ''; ?>">مقالات</a>
                <a href="/contact.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'contact') !== false ? 'active' : ''; ?>">تماس با ما</a>
            </nav>

            <!-- Header Actions -->
            <div class="header-actions">
                <!-- Search -->
                <div class="header-search">
                    <form action="/search.php" method="GET">
                        <input type="text" name="q" placeholder="جستجوی محصولات..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                        <button type="submit">🔍</button>
                    </form>
                </div>
                
                <!-- Phone -->
                <?php if (!empty($settings['phone'])): ?>
                    <div class="header-phone">
                        📞
                        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $settings['phone']); ?>">
                            <?php echo htmlspecialchars($settings['phone']); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Cart -->
                <a href="/cart.php" class="header-cart">
                    🛒
                    <?php 
                    // Cart count (placeholder - will be implemented with session)
                    $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                    if ($cart_count > 0):
                    ?>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                
                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                    ☰
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobile-menu">
            <nav>
                <a href="/">خانه</a>
                <a href="/about.php">درباره ما</a>
                <a href="/products.php">محصولات</a>
                <a href="/articles.php">مقالات</a>
                <a href="/contact.php">تماس با ما</a>
                <a href="/cart.php">سبد خرید</a>
            </nav>
        </div>
    </header>

    <!-- Breadcrumb -->
    <?php 
    $breadcrumb = [];
    $current_page = basename($_SERVER['PHP_SELF']);
    
    switch ($current_page) {
        case 'index.php':
            $breadcrumb = [['name' => 'خانه', 'url' => '/']];
            break;
        case 'about.php':
            $breadcrumb = [
                ['name' => 'خانه', 'url' => '/'],
                ['name' => 'درباره ما', 'url' => '/about.php']
            ];
            break;
        case 'products.php':
            $breadcrumb = [
                ['name' => 'خانه', 'url' => '/'],
                ['name' => 'محصولات', 'url' => '/products.php']
            ];
            break;
        case 'product.php':
            $breadcrumb = [
                ['name' => 'خانه', 'url' => '/'],
                ['name' => 'محصولات', 'url' => '/products.php'],
                ['name' => 'جزئیات محصول', 'url' => '#']
            ];
            break;
        case 'articles.php':
            $breadcrumb = [
                ['name' => 'خانه', 'url' => '/'],
                ['name' => 'مقالات', 'url' => '/articles.php']
            ];
            break;
        case 'article.php':
            $breadcrumb = [
                ['name' => 'خانه', 'url' => '/'],
                ['name' => 'مقالات', 'url' => '/articles.php'],
                ['name' => 'مقاله', 'url' => '#']
            ];
            break;
        case 'contact.php':
            $breadcrumb = [
                ['name' => 'خانه', 'url' => '/'],
                ['name' => 'تماس با ما', 'url' => '/contact.php']
            ];
            break;
    }
    
    if (!empty($breadcrumb) && count($breadcrumb) > 1):
    ?>
    <div class="breadcrumb">
        <div class="container">
            <?php foreach ($breadcrumb as $index => $item): ?>
                <?php if ($index > 0): ?>
                    <span class="breadcrumb-separator">/</span>
                <?php endif; ?>
                <?php if ($item['url'] !== '#'): ?>
                    <a href="<?php echo $item['url']; ?>"><?php echo $item['name']; ?></a>
                <?php else: ?>
                    <span><?php echo $item['name']; ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <main>
