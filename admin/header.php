<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- Title -->
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : ''; ?>پنل ادمین - گولند</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Vazir:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    
    <!-- Preload key resources -->
    <link rel="preload" href="assets/css/admin.css" as="style">
    <link rel="preload" href="assets/js/admin.js" as="script">
    
    <!-- No JavaScript fallback -->
    <noscript>
        <style>
            .no-js-message {
                display: block;
                padding: 20px;
                background: #fff8e1;
                border: 1px solid #ffc107;
                text-align: center;
                color: #8d6e63;
            }
        </style>
    </noscript>
</head>
<body>
    <!-- No JavaScript Message -->
    <div class="no-js-message" style="display: none;">
        <i class="fas fa-exclamation-triangle"></i>
        برای تجربه بهتر، لطفا JavaScript را در مرورگر خود فعال کنید
    </div>

    <!-- Admin Container -->
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <!-- Logo -->
            <div class="logo">
                <?php $logo = getSetting('site_logo', '../assets/images/logo-white.png'); ?>
                <?php if (file_exists($logo)): ?>
                    <img src="<?php echo $logo; ?>" alt="لوگو">
                <?php else: ?>
                    <h2>گولند</h2>
                <?php endif; ?>
            </div>
            
            <!-- Navigation Menu -->
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span>داشبورد</span>
                    </a>
                </li>
                
                <li class="nav-item has-submenu">
                    <a href="#" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_products.php', 'manage_categories.php', 'manage_brands.php', 'manage_reviews.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-box-open"></i>
                        <span>محصولات</span>
                    </a>
                    <ul class="nav-submenu">
                        <li><a href="manage_products.php">مدیریت محصولات</a></li>
                        <li><a href="manage_categories.php">مدیریت دسته‌بندی‌ها</a></li>
                        <li><a href="manage_brands.php">مدیریت برندها</a></li>
                        <li><a href="manage_reviews.php">مدیریت نظرات</a></li>
                    </ul>
                </li>
                
                <li class="nav-item has-submenu">
                    <a href="#" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_orders.php', 'order_details.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i>
                        <span>سفارش‌ها</span>
                    </a>
                    <ul class="nav-submenu">
                        <li><a href="manage_orders.php">لیست سفارش‌ها</a></li>
                        <li><a href="order_details.php">جزئیات سفارش</a></li>
                    </ul>
                </li>
                
                <li class="nav-item has-submenu">
                    <a href="#" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_users.php', 'manage_admins.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>کاربران</span>
                    </a>
                    <ul class="nav-submenu">
                        <li><a href="manage_users.php">مدیریت کاربران</a></li>
                        <li><a href="manage_admins.php">مدیریت ادمین‌ها</a></li>
                    </ul>
                </li>
                
                <li class="nav-item has-submenu">
                    <a href="#" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_blog.php', 'manage_blog_categories.php', 'manage_blog_comments.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-blog"></i>
                        <span>بلاگ</span>
                    </a>
                    <ul class="nav-submenu">
                        <li><a href="manage_blog.php">مدیریت مقالات</a></li>
                        <li><a href="manage_blog_categories.php">مدیریت دسته‌بندی‌ها</a></li>
                        <li><a href="manage_blog_comments.php">مدیریت نظرات</a></li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="manage_pages.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_pages.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i>
                        <span>صفحات استاتیک</span>
                    </a>
                </li>
                
                <li class="nav-item has-submenu">
                    <a href="#" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_coupons.php', 'manage_shipping.php', 'manage_payment.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>تنظیمات فروشگاه</span>
                    </a>
                    <ul class="nav-submenu">
                        <li><a href="manage_coupons.php">مدیریت کوپن‌ها</a></li>
                        <li><a href="manage_shipping.php">روش‌های ارسال</a></li>
                        <li><a href="manage_payment.php">روش‌های پرداخت</a></li>
                    </ul>
                </li>
                
                <li class="nav-item has-submenu">
                    <a href="#" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_contact.php', 'manage_newsletter.php', 'manage_faq.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-envelope"></i>
                        <span>ارتباط با مشتریان</span>
                    </a>
                    <ul class="nav-submenu">
                        <li><a href="manage_contact.php">پیام‌های تماس</a></li>
                        <li><a href="manage_newsletter.php">خبرنامه</a></li>
                        <li><a href="manage_faq.php">سوالات متداول</a></li>
                    </ul>
                </li>
                
                <li class="nav-item has-submenu">
                    <a href="#" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_banners.php', 'manage_social.php', 'manage_settings.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-paint-brush"></i>
                        <span>ظاهری و تنظیمات</span>
                    </a>
                    <ul class="nav-submenu">
                        <li><a href="manage_banners.php">مدیریت بنرها</a></li>
                        <li><a href="manage_social.php">شبکه‌های اجتماعی</a></li>
                        <li><a href="settings.php">تنظیمات سایت</a></li>
                    </ul>
                </li>
                
                <li class="nav-item has-submenu">
                    <a href="#" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['system_info.php', 'database_backup.php', 'cache_management.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-server"></i>
                        <span>سیستم</span>
                    </a>
                    <ul class="nav-submenu">
                        <li><a href="system_info.php">اطلاعات سیستم</a></li>
                        <li><a href="database_backup.php">پشتیبان گیری</a></li>
                        <li><a href="cache_management.php">مدیریت کش</a></li>
                        <li><a href="logs.php">لاگ‌ها</a></li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="../index.php" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        <span>مشاهده سایت</span>
                    </a>
                </li>
            </ul>
            
            <!-- User Info -->
            <div class="user-info">
                <div class="user-avatar">
                    <?php if (!empty($_SESSION['avatar'])): ?>
                        <img src="<?php echo $_SESSION['avatar']; ?>" alt="آواتار">
                    <?php else: ?>
                        <span><?php echo substr($_SESSION['name'], 0, 1); ?></span>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <span class="user-role">ادمین</span>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
