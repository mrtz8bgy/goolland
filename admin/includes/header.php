<?php
// Get admin info
$admin_info = [];
if (isset($_SESSION['admin_id'])) {
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin_info = $result->fetch_assoc();
}

// Get unread contacts count
$unread_contacts = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'unread'");
if ($result) {
    $unread_contacts = $result->fetch_assoc()['count'];
}

// Get pending orders count
$pending_orders = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
if ($result) {
    $pending_orders = $result->fetch_assoc()['count'];
}
?>

<!-- Header -->
<header class="admin-header">
    <div class="admin-header-inner">
        <div class="admin-logo">
            <span>🌿 Goolland</span>
        </div>
        
        <nav class="admin-nav">
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                داشبورد
            </a>
            <a href="orders.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'order') !== false ? 'active' : ''; ?>">
                سفارش‌ها
                <?php if ($pending_orders > 0): ?>
                    <sup style="color: var(--admin-danger); font-size: 10px;"><?php echo $pending_orders; ?></sup>
                <?php endif; ?>
            </a>
            <a href="products.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'product') !== false ? 'active' : ''; ?>">
                محصولات
            </a>
            <a href="categories.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'category') !== false ? 'active' : ''; ?>">
                دسته‌بندی‌ها
            </a>
        </nav>
        
        <div class="admin-header-actions">
            <div class="admin-search">
                <input type="text" placeholder="جستجو...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </div>
            
            <div class="admin-notifications">
                <i class="fas fa-bell icon"></i>
                <?php if ($unread_contacts > 0): ?>
                    <span class="badge"><?php echo $unread_contacts; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="admin-user">
                <div class="admin-user-avatar">
                    <?php echo mb_substr($_SESSION['admin_username'] ?? 'A', 0, 1); ?>
                </div>
                <div class="admin-user-info">
                    <span class="admin-user-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'مدیر'); ?></span>
                    <span class="admin-user-role"><?php echo htmlspecialchars($admin_info['role'] ?? 'admin'); ?></span>
                </div>
            </div>
            
            <a href="logout.php" class="admin-btn admin-btn-secondary admin-btn-sm">
                <i class="fas fa-sign-out-alt"></i>
                خروج
            </a>
        </div>
    </div>
</header>
