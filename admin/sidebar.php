<?php
// Get current page for active menu item
$currentPage = basename($_SERVER['PHP_SELF']);

// Get admin role
$adminRole = $_SESSION['admin_role'] ?? 'admin';

// Check permissions
$canManageProducts = in_array($adminRole, ['admin', 'editor']);
$canManageOrders = in_array($adminRole, ['admin', 'manager']);
$canManageUsers = $adminRole === 'admin';
$canManageSettings = $adminRole === 'admin';
$canManageCategories = in_array($adminRole, ['admin', 'editor']);
$canManageBlog = in_array($adminRole, ['admin', 'editor']);
$canManagePages = in_array($adminRole, ['admin', 'editor']);
$canManageCoupons = in_array($adminRole, ['admin', 'manager']);
$canViewReports = in_array($adminRole, ['admin', 'manager']);
$canManageAdmins = $adminRole === 'admin';
?>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-leaf"></i>
            <span class="logo-text">گولند</span>
        </div>
        <button class="sidebar-toggle" title="بستن منو">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-th-large"></i>
                    <span>داشبورد</span>
                </a>
            </li>

            <?php if ($canManageOrders): ?>
            <li class="nav-item <?php echo $currentPage === 'manage_orders.php' ? 'active' : ''; ?>">
                <a href="manage_orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>مدیریت سفارش‌ها</span>
                    <?php 
                    $pendingCount = getPendingOrdersCount();
                    if ($pendingCount > 0):
                    ?>
                    <span class="nav-badge"><?php echo toPersianNumbers($pendingCount); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($canManageProducts): ?>
            <li class="nav-item <?php echo $currentPage === 'manage_products.php' ? 'active' : ''; ?>">
                <a href="manage_products.php" class="nav-link">
                    <i class="fas fa-box-open"></i>
                    <span>مدیریت محصولات</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($canManageCategories): ?>
            <li class="nav-item <?php echo $currentPage === 'manage_categories.php' ? 'active' : ''; ?>">
                <a href="manage_categories.php" class="nav-link">
                    <i class="fas fa-folder-open"></i>
                    <span>مدیریت دسته‌بندی‌ها</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($canManageUsers): ?>
            <li class="nav-item <?php echo $currentPage === 'manage_users.php' ? 'active' : ''; ?>">
                <a href="manage_users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>مدیریت کاربران</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($canManageBlog): ?>
            <li class="nav-item <?php echo $currentPage === 'manage_posts.php' || $currentPage === 'manage_post_categories.php' ? 'active' : ''; ?>">
                <a href="manage_posts.php" class="nav-link">
                    <i class="fas fa-blog"></i>
                    <span>مدیریت بلاگ</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($canManagePages): ?>
            <li class="nav-item <?php echo $currentPage === 'manage_pages.php' ? 'active' : ''; ?>">
                <a href="manage_pages.php" class="nav-link">
                    <i class="fas fa-file-alt"></i>
                    <span>مدیریت صفحات</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($canManageCoupons): ?>
            <li class="nav-item <?php echo $currentPage === 'manage_coupons.php' ? 'active' : ''; ?>">
                <a href="manage_coupons.php" class="nav-link">
                    <i class="fas fa-ticket-alt"></i>
                    <span>مدیریت کوپن‌ها</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($canViewReports): ?>
            <li class="nav-item <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>گزارش‌ها</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($canManageSettings): ?>
            <li class="nav-item <?php echo $currentPage === 'settings.php' || $currentPage === 'shipping.php' || $currentPage === 'payment.php' ? 'active' : ''; ?>">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>تنظیمات سایت</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($canManageAdmins): ?>
            <li class="nav-item <?php echo $currentPage === 'manage_admins.php' ? 'active' : ''; ?>">
                <a href="manage_admins.php" class="nav-link">
                    <i class="fas fa-user-shield"></i>
                    <span>مدیریت مدیران</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-item">
                <a href="activities.php" class="nav-link">
                    <i class="fas fa-history"></i>
                    <span>تاریخچه فعالیت‌ها</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="backup.php" class="nav-link">
                    <i class="fas fa-database"></i>
                    <span>پشتیبانی و بازیابی</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'مدیر'); ?></span>
                    <span class="user-role"><?php echo getAdminRoleName($_SESSION['admin_role'] ?? 'admin'); ?></span>
                </div>
            </div>
            <a href="logout.php" class="nav-link logout-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>خروج</span>
            </a>
        </div>
    </nav>
</aside>
