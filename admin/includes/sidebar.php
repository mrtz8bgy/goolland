<?php
// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

// Get unread contacts count
$unread_contacts = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'unread'");
if ($result) {
    $unread_contacts = $result->fetch_assoc()['count'];
}

// Get pending reviews count
$pending_reviews = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'pending'");
if ($result) {
    $pending_reviews = $result->fetch_assoc()['count'];
}

// Get pending orders count
$pending_orders = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
if ($result) {
    $pending_orders = $result->fetch_assoc()['count'];
}
?>

<!-- Sidebar -->
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="admin-sidebar-header">
        <div class="admin-sidebar-logo">
            <span>🌿 Goolland</span>
        </div>
        <button class="admin-sidebar-close" id="admin-sidebar-close">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <nav class="admin-sidebar-menu">
        <div class="admin-menu-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php" class="admin-menu-link">
                <span class="icon"><i class="fas fa-chart-line"></i></span>
                <span class="label">داشبورد</span>
            </a>
        </div>
        
        <div class="admin-menu-item <?php echo strpos($current_page, 'order') !== false ? 'active' : ''; ?>">
            <a href="orders.php" class="admin-menu-link">
                <span class="icon"><i class="fas fa-shopping-bag"></i></span>
                <span class="label">سفارش‌ها</span>
                <?php if ($pending_orders > 0): ?>
                    <span class="badge"><?php echo $pending_orders; ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <div class="admin-menu-item <?php echo strpos($current_page, 'product') !== false ? 'active' : ''; ?>">
            <a href="products.php" class="admin-menu-link">
                <span class="icon"><i class="fas fa-seedling"></i></span>
                <span class="label">محصولات</span>
            </a>
        </div>
        
        <div class="admin-menu-item <?php echo strpos($current_page, 'category') !== false ? 'active' : ''; ?>">
            <a href="categories.php" class="admin-menu-link">
                <span class="icon"><i class="fas fa-folder"></i></span>
                <span class="label">دسته‌بندی‌ها</span>
            </a>
        </div>
        
        <div class="admin-menu-item <?php echo strpos($current_page, 'article') !== false ? 'active' : ''; ?>">
            <a href="articles.php" class="admin-menu-link">
                <span class="icon"><i class="fas fa-newspaper"></i></span>
                <span class="label">مقالات</span>
            </a>
        </div>
        
        <div class="admin-menu-item <?php echo strpos($current_page, 'user') !== false ? 'active' : ''; ?>">
            <a href="users.php" class="admin-menu-link">
                <span class="icon"><i class="fas fa-users"></i></span>
                <span class="label">کاربران</span>
            </a>
        </div>
        
        <div class="admin-menu-item <?php echo strpos($current_page, 'contact') !== false ? 'active' : ''; ?>">
            <a href="contacts.php" class="admin-menu-link">
                <span class="icon"><i class="fas fa-envelope"></i></span>
                <span class="label">پیام‌ها</span>
                <?php if ($unread_contacts > 0): ?>
                    <span class="badge"><?php echo $unread_contacts; ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <div class="admin-menu-item <?php echo strpos($current_page, 'review') !== false ? 'active' : ''; ?>">
            <a href="reviews.php" class="admin-menu-link">
                <span class="icon"><i class="fas fa-star"></i></span>
                <span class="label">نقد و بررسی‌ها</span>
                <?php if ($pending_reviews > 0): ?>
                    <span class="badge"><?php echo $pending_reviews; ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <div class="admin-menu-item <?php echo strpos($current_page, 'setting') !== false ? 'active' : ''; ?>">
            <a href="settings.php" class="admin-menu-link">
                <span class="icon"><i class="fas fa-cog"></i></span>
                <span class="label">تنظیمات سایت</span>
            </a>
        </div>
        
        <div class="admin-menu-item <?php echo strpos($current_page, 'admin') !== false ? 'active' : ''; ?>">
            <a href="admins.php" class="admin-menu-link">
                <span class="icon"><i class="fas fa-user-shield"></i></span>
                <span class="label">مدیران سایت</span>
            </a>
        </div>
    </nav>
</aside>

<!-- Sidebar Toggle Button -->
<button class="admin-sidebar-toggle" id="admin-sidebar-toggle">
    <i class="fas fa-bars"></i>
</button>
