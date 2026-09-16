<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Get dashboard statistics
$stats = getDashboardStats();

// Get recent orders
$recentOrders = getRecentOrders(5);

// Get recent users
$recentUsers = getRecentUsers(5);

// Get low stock products
$lowStockProducts = getLowStockProducts(5);

// Get order status count
$orderStatusCount = getOrderStatusCount();

// Get customer stats
$customerStats = getCustomerStats();

// Get product stats
$productStats = getProductStats();

// Get monthly sales
$monthlySales = getMonthlySales(date('Y'));

// Get category sales
$categorySales = getCategorySales(date('Y-01-01'), date('Y-12-31'));

$pageTitle = "داشبورد";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1>داشبورد</h1>
        <div class="admin-actions">
            <a href="settings.php" class="btn btn-secondary">تنظیمات سایت</a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['total_users']); ?></span>
            <span class="stat-label">کل کاربران</span>
            <span class="stat-change positive">
                +<?php echo toPersianNumbers($customerStats['new_this_month']); ?> این ماه
            </span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-box-open"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['total_products']); ?></span>
            <span class="stat-label">کل محصولات</span>
            <span class="stat-change positive">
                +<?php echo toPersianNumbers($productStats['published']); ?> منتشر شده
            </span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['total_orders']); ?></span>
            <span class="stat-label">کل سفارش‌ها</span>
            <span class="stat-change positive">
                +<?php echo toPersianNumbers($stats['pending_orders']); ?> در انتظار
            </span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($stats['total_revenue']); ?></span>
            <span class="stat-label">درآمد کل (تومان)</span>
            <span class="stat-change positive">
                <?php 
                $todayRevenue = 0;
                $stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid' AND DATE(created_at) = CURDATE()");
                $todayRevenue = $stmt->fetchColumn();
                echo toPersianNumbers($todayRevenue ?? 0); ?> امروز
            </span>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="dashboard-charts">
        <!-- Monthly Sales Chart -->
        <div class="chart-card">
            <h3>فروش ماهانه</h3>
            <div class="chart-container">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>
        
        <!-- Order Status Chart -->
        <div class="chart-card">
            <h3>وضعیت سفارش‌ها</h3>
            <div class="chart-container">
                <canvas id="orderStatusChart"></canvas>
            </div>
        </div>
        
        <!-- Category Sales Chart -->
        <div class="chart-card">
            <h3>فروش بر اساس دسته‌بندی</h3>
            <div class="chart-container">
                <canvas id="categorySalesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="dashboard-content-row">
        <!-- Recent Orders -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>آخرین سفارش‌ها</h3>
                <a href="manage_orders.php" class="view-all">مشاهده همه</a>
            </div>
            <div class="table-responsive">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>شماره سفارش</th>
                            <th>مشتری</th>
                            <th>مبلغ</th>
                            <th>وضعیت</th>
                            <th>تاریخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="5" class="empty-message">هیچ سفارشی یافت نشد</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($order['user_name'] ?? 'مهمان'); ?></td>
                                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $order['status']; ?>">
                                            <?php echo getOrderStatusText($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($order['created_at'], 'Y/m/d'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>آخرین کاربران</h3>
                <a href="manage_users.php" class="view-all">مشاهده همه</a>
            </div>
            <div class="table-responsive">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>نام</th>
                            <th>ایمیل</th>
                            <th>تلفن</th>
                            <th>تاریخ ثبت‌نام</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentUsers)): ?>
                            <tr>
                                <td colspan="4" class="empty-message">هیچ کاربری یافت نشد</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo toPersianNumbers($user['phone'] ?? 'ندارد'); ?></td>
                                    <td><?php echo formatDate($user['created_at'], 'Y/m/d'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Low Stock Products -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>محصولات با موجودی کم</h3>
                <a href="manage_products.php" class="view-all">مشاهده همه</a>
            </div>
            <div class="table-responsive">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>نام محصول</th>
                            <th>دسته‌بندی</th>
                            <th>موجودی</th>
                            <th>قیمت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lowStockProducts)): ?>
                            <tr>
                                <td colspan="4" class="empty-message">هیچ محصولی با موجودی کم یافت نشد</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lowStockProducts as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'ندارد'); ?></td>
                                    <td><?php echo toPersianNumbers($product['stock']); ?></td>
                                    <td><?php echo formatPrice($product['price']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>آمار سریع</h3>
            </div>
            <div class="quick-stats">
                <div class="quick-stat">
                    <div class="quick-stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="quick-stat-content">
                        <span class="quick-stat-value"><?php echo toPersianNumbers($stats['completed_orders']); ?></span>
                        <span class="quick-stat-label">سفارش تکمیل شده</span>
                    </div>
                </div>
                <div class="quick-stat">
                    <div class="quick-stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="quick-stat-content">
                        <span class="quick-stat-value"><?php echo toPersianNumbers($stats['pending_orders']); ?></span>
                        <span class="quick-stat-label">سفارش در انتظار</span>
                    </div>
                </div>
                <div class="quick-stat">
                    <div class="quick-stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="quick-stat-content">
                        <span class="quick-stat-value"><?php echo toPersianNumbers($productStats['featured']); ?></span>
                        <span class="quick-stat-label">محصول ویژه</span>
                    </div>
                </div>
                <div class="quick-stat">
                    <div class="quick-stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="quick-stat-content">
                        <span class="quick-stat-value"><?php echo toPersianNumbers($productStats['out_of_stock']); ?></span>
                        <span class="quick-stat-label">محصول ناموجود</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activities -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>آخرین فعالیت‌ها</h3>
            <a href="activities.php" class="view-all">مشاهده همه</a>
        </div>
        <div class="activities-list">
            <?php
            $activities = getActivities(null, 10);
            if (empty($activities)):
            ?>
                <div class="empty-message">هیچ فعالیتی یافت نشد</div>
            <?php else: ?>
                <?php foreach ($activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-<?php 
                                switch ($activity['action']) {
                                    case 'login': echo 'sign-in-alt'; break;
                                    case 'logout': echo 'sign-out-alt'; break;
                                    case 'create_order': echo 'shopping-cart'; break;
                                    case 'update_order': echo 'edit'; break;
                                    case 'create_product': echo 'plus'; break;
                                    case 'update_product': echo 'edit'; break;
                                    case 'delete_product': echo 'trash'; break;
                                    case 'create_user': echo 'user-plus'; break;
                                    case 'update_user': echo 'user-edit'; break;
                                    case 'delete_user': echo 'user-times'; break;
                                    default: echo 'info-circle';
                                }
                            ?>"></i>
                        </div>
                        <div class="activity-content">
                            <span class="activity-action"><?php echo htmlspecialchars($activity['action']); ?></span>
                            <span class="activity-details"><?php echo htmlspecialchars($activity['details']); ?></span>
                            <span class="activity-time"><?php echo formatDate($activity['created_at']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Monthly Sales Chart
const monthlySalesCtx = document.getElementById('monthlySalesChart');
if (monthlySalesCtx) {
    const monthNames = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    const salesData = <?php echo json_encode(array_values($monthlySales)); ?>;
    
    new Chart(monthlySalesCtx, {
        type: 'line',
        data: {
            labels: monthNames,
            datasets: [{
                label: 'فروش ماهانه (تومان)',
                data: salesData,
                borderColor: '#2e7d32',
                backgroundColor: 'rgba(46, 125, 50, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Order Status Chart
const orderStatusCtx = document.getElementById('orderStatusChart');
if (orderStatusCtx) {
    const statusData = <?php echo json_encode($orderStatusCount); ?>;
    const statusLabels = <?php echo json_encode(array_keys($orderStatusCount)); ?>;
    const statusColors = {
        'pending': '#ff9800',
        'processing': '#2196f3',
        'shipped': '#4caf50',
        'delivered': '#8bc34a',
        'completed': '#2e7d32',
        'cancelled': '#f44336'
    };
    
    const backgroundColors = statusLabels.map(label => statusColors[label] || '#999');
    
    new Chart(orderStatusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels.map(label => {
                switch(label) {
                    case 'pending': return 'در انتظار';
                    case 'processing': return 'در حال پردازش';
                    case 'shipped': return 'ارسال شده';
                    case 'delivered': return 'تحویل داده شده';
                    case 'completed': return 'تکمیل شده';
                    case 'cancelled': return 'کنسل شده';
                    default: return label;
                }
            }),
            datasets: [{
                data: Object.values(statusData),
                backgroundColor: backgroundColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Category Sales Chart
const categorySalesCtx = document.getElementById('categorySalesChart');
if (categorySalesCtx) {
    const categoryData = <?php echo json_encode($categorySales); ?>;
    const categoryLabels = categoryData.map(item => item.name);
    const categoryValues = categoryData.map(item => item.total_sales);
    
    new Chart(categorySalesCtx, {
        type: 'bar',
        data: {
            labels: categoryLabels,
            datasets: [{
                label: 'فروش بر اساس دسته‌بندی (تومان)',
                data: categoryValues,
                backgroundColor: '#2e7d32',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
</script>

<?php require_once 'footer.php'; ?>
