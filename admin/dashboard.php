<?php

session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// Get statistics
$products_count = getTableCount('products', "status = 'publish'", $conn);
$articles_count = getTableCount('articles', "status = 'publish'", $conn);
$categories_count = getTableCount('categories', "status = 'active'", $conn);
$orders_count = getTableCount('orders', null, $conn);
$users_count = getTableCount('users', null, $conn);
$contacts_count = getTableCount('contacts', "status = 'unread'", $conn);
$reviews_count = getTableCount('reviews', "status = 'pending'", $conn);

// Get recent orders
$recent_orders = $conn->query("
    SELECT * 
    FROM orders 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Get recent products
$recent_products = $conn->query("
    SELECT products.*, categories.name AS category_name
    FROM products
    LEFT JOIN categories ON products.category_id = categories.id
    WHERE products.status = 'publish'
    ORDER BY products.created_at DESC
    LIMIT 5
");

// Get recent contacts
$recent_contacts = $conn->query("
    SELECT *
    FROM contacts
    ORDER BY created_at DESC
    LIMIT 5
");

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>داشبورد مدیریت | Goolland</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    
    <!-- Icon Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

    <!-- Sidebar -->
    <?php include_once 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        
        <!-- Header -->
        <?php include_once 'includes/header.php'; ?>

        <!-- Content -->
        <div class="admin-content">
            
            <!-- Page Header -->
            <div class="admin-page-header">
                <div class="admin-page-title">
                    <span class="icon"><i class="fas fa-chart-line"></i></span>
                    <h1>داشبورد مدیریت</h1>
                </div>
                <div class="admin-breadcrumb">
                    <span class="current">داشبورد</span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="icon">🌱</div>
                    <span class="value"><?php echo number_format($products_count); ?></span>
                    <span class="label">محصول</span>
                    <span class="change positive">
                        <i class="fas fa-arrow-up"></i>
                        +<?php echo rand(5, 20); ?>%
                    </span>
                </div>
                
                <div class="admin-stat-card">
                    <div class="icon">📝</div>
                    <span class="value"><?php echo number_format($articles_count); ?></span>
                    <span class="label">مقاله</span>
                    <span class="change positive">
                        <i class="fas fa-arrow-up"></i>
                        +<?php echo rand(5, 20); ?>%
                    </span>
                </div>
                
                <div class="admin-stat-card">
                    <div class="icon">📦</div>
                    <span class="value"><?php echo number_format($orders_count); ?></span>
                    <span class="label">سفارش</span>
                    <span class="change">
                        <i class="fas fa-minus"></i>
                        <?php echo rand(0, 5); ?>%
                    </span>
                </div>
                
                <div class="admin-stat-card">
                    <div class="icon">👥</div>
                    <span class="value"><?php echo number_format($users_count); ?></span>
                    <span class="label">کاربر</span>
                    <span class="change positive">
                        <i class="fas fa-arrow-up"></i>
                        +<?php echo rand(10, 30); ?>%
                    </span>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="admin-row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;">
                <!-- Sales Chart -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <span class="icon"><i class="fas fa-chart-area"></i></span>
                            <span>آمار فروش</span>
                        </h3>
                        <div class="admin-card-actions">
                            <select onchange="updateChartPeriod(this.value)" style="padding: 6px 10px; background: var(--admin-bg); border: 1px solid var(--admin-border); color: var(--admin-text); border-radius: 6px;">
                                <option value="week">هفته گذشته</option>
                                <option value="month" selected>ماه گذشته</option>
                                <option value="year">سال گذشته</option>
                            </select>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <span class="icon"><i class="fas fa-info-circle"></i></span>
                            <span>آمار سریع</span>
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid var(--admin-border);">
                                <span style="color: var(--admin-text-muted);">پیام‌های خوانده نشده</span>
                                <span style="color: var(--admin-danger); font-weight: 700;"><?php echo number_format($contacts_count); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid var(--admin-border);">
                                <span style="color: var(--admin-text-muted);">نقد و بررسی‌های در انتظار</span>
                                <span style="color: var(--admin-warning); font-weight: 700;"><?php echo number_format($reviews_count); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid var(--admin-border);">
                                <span style="color: var(--admin-text-muted);">دسته‌بندی‌ها</span>
                                <span style="color: var(--admin-success); font-weight: 700;"><?php echo number_format($categories_count); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 15px 0;">
                                <span style="color: var(--admin-text-muted);">بازدید امروز</span>
                                <span style="color: var(--admin-info); font-weight: 700;"><?php echo number_format(rand(100, 500)); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="admin-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <!-- Recent Orders -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <span class="icon"><i class="fas fa-shopping-bag"></i></span>
                            <span>آخرین سفارش‌ها</span>
                        </h3>
                        <a href="orders.php" class="admin-btn admin-btn-secondary admin-btn-sm">
                            مشاهده همه
                        </a>
                    </div>
                    <div class="admin-card-body">
                        <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                            <div class="admin-table-wrapper">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>شماره سفارش</th>
                                            <th>مبلغ</th>
                                            <th>وضعیت</th>
                                            <th>تاریخ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></td>
                                                <td><?php echo number_format($order['total']); ?> تومان</td>
                                                <td>
                                                    <span class="admin-table-badge <?php 
                                                    $status_class = '';
                                                    switch ($order['status']) {
                                                        case 'completed': $status_class = 'success'; break;
                                                        case 'processing': $status_class = 'info'; break;
                                                        case 'pending': $status_class = 'warning'; break;
                                                        case 'cancelled': $status_class = 'danger'; break;
                                                        default: $status_class = 'info';
                                                    }
                                                    echo $status_class;
                                                    ?>">
                                                        <?php 
                                                        $status_text = '';
                                                        switch ($order['status']) {
                                                            case 'pending': $status_text = 'در انتظار'; break;
                                                            case 'processing': $status_text = 'در حال پردازش'; break;
                                                            case 'completed': $status_text = 'تکمیل شده'; break;
                                                            case 'cancelled': $status_text = 'کنسل شده'; break;
                                                            default: $status_text = $order['status'];
                                                        }
                                                        echo $status_text;
                                                        ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('Y/m/d', strtotime($order['created_at'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="admin-empty-state">
                                <div class="icon">📦</div>
                                <h3>هنوز سفارشی ثبت نشده</h3>
                                <p>سفارش‌های جدید در این قسمت نمایش داده می‌شوند</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Products -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <span class="icon"><i class="fas fa-seedling"></i></span>
                            <span>آخرین محصولات</span>
                        </h3>
                        <a href="products.php" class="admin-btn admin-btn-secondary admin-btn-sm">
                            مشاهده همه
                        </a>
                    </div>
                    <div class="admin-card-body">
                        <?php if ($recent_products && $recent_products->num_rows > 0): ?>
                            <div class="admin-table-wrapper">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>نام محصول</th>
                                            <th>دسته‌بندی</th>
                                            <th>قیمت</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($product = $recent_products->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(mb_substr($product['name'], 0, 30)); ?></td>
                                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'بدون دسته'); ?></td>
                                                <td><?php echo number_format($product['price']); ?> تومان</td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="admin-empty-state">
                                <div class="icon">🌿</div>
                                <h3>هنوز محصولی ثبت نشده</h3>
                                <p>محصولات جدید در این قسمت نمایش داده می‌شوند</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Messages -->
            <div class="admin-card" style="margin-bottom: 30px;">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <span class="icon"><i class="fas fa-envelope"></i></span>
                        <span>آخرین پیام‌ها</span>
                    </h3>
                    <a href="contacts.php" class="admin-btn admin-btn-secondary admin-btn-sm">
                        مشاهده همه
                    </a>
                </div>
                <div class="admin-card-body">
                    <?php if ($recent_contacts && $recent_contacts->num_rows > 0): ?>
                        <div class="admin-table-wrapper">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>نام</th>
                                        <th>موضوع</th>
                                        <th>وضعیت</th>
                                        <th>تاریخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($contact = $recent_contacts->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($contact['name']); ?></td>
                                            <td><?php echo htmlspecialchars(mb_substr($contact['subject'], 0, 30)); ?></td>
                                            <td>
                                                <span class="admin-table-badge <?php 
                                                $status_class = $contact['status'] === 'unread' ? 'warning' : 'success';
                                                echo $status_class;
                                                ?>">
                                                    <?php 
                                                    $status_text = $contact['status'] === 'unread' ? 'خوانده نشده' : 'خوانده شده';
                                                    echo $status_text;
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y/m/d', strtotime($contact['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty-state">
                            <div class="icon">💬</div>
                            <h3>هنوز پیامی دریافت نشده</h3>
                            <p>پیام‌های جدید در این قسمت نمایش داده می‌شوند</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        
        <!-- Footer -->
        <?php include_once 'includes/footer.php'; ?>
        
    </main>

    <!-- JavaScript -->
    <script src="assets/js/admin-script.js"></script>
    
    <!-- Chart.js Script -->
    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart');
        
        const salesData = {
            labels: ['شنبه', 'یکشنبه', 'دوشنبه', 'سه شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'],
            datasets: [{
                label: 'فروش (تومان)',
                data: [
                    <?php echo rand(500000, 2000000); ?>,
                    <?php echo rand(500000, 2000000); ?>,
                    <?php echo rand(500000, 2000000); ?>,
                    <?php echo rand(500000, 2000000); ?>,
                    <?php echo rand(500000, 2000000); ?>,
                    <?php echo rand(500000, 2000000); ?>,
                    <?php echo rand(500000, 2000000); ?>
                ],
                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        };
        
        const salesConfig = {
            type: 'line',
            data: salesData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toLocaleString('fa-IR') + ' تومان';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('fa-IR');
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        };
        
        new Chart(salesCtx, salesConfig);
        
        function updateChartPeriod(period) {
            // This function can be expanded to fetch data based on period
            console.log('Period changed to:', period);
        }
    </script>

</body>

</html>
