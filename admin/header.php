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
    <link rel="stylesheet" href="../assets/css/admin-manage.css">
    
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
                    <a href="#" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_products.php', 'manage_categories.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-box-open"></i>
                        <span>محصولات</span>
                    </a>
                    <ul class="nav-submenu">
                        <li><a href="manage_products.php">مدیریت محصولات</a></li>
                        <li><a href="manage_categories.php">مدیریت دسته‌بندی‌ها</a></li>
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
                    <a href="#" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_posts.php', 'manage_post_categories.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-blog"></i>
                        <span>بلاگ</span>
                    </a>
                    <ul class="nav-submenu">
                        <li><a href="manage_posts.php">مدیریت مقالات</a></li>
                        <li><a href="manage_post_categories.php">مدیریت دسته‌بندی‌ها</a></li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="manage_pages.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_pages.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i>
                        <span>صفحات استاتیک</span>
                    </a>
                </li>
                
                <li class="nav-item has-submenu">
                    <a href="#" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_coupons.php', 'settings.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>تنظیمات فروشگاه</span>
                    </a>
                    <ul class="nav-submenu">
                        <li><a href="manage_coupons.php">مدیریت کوپن‌ها</a></li>
                        <li><a href="settings.php">تنظیمات سایت</a></li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="activities.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'activities.php' ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i>
                        <span>تاریخچه فعالیت‌ها</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="backup.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'backup.php' ? 'active' : ''; ?>">
                        <i class="fas fa-database"></i>
                        <span>پشتیبان‌گیری</span>
                    </a>
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
