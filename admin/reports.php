<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Check if admin has permission
$adminRole = $_SESSION['admin_role'] ?? 'admin';
if (!in_array($adminRole, ['admin', 'manager'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

// Get current year
$currentYear = date('Y');
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;

// Get date range
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get sales report
$salesReport = getSalesReport($startDate, $endDate);

// Get monthly sales
$monthlySales = getMonthlySales($year);

// Get category sales
$categorySales = getCategorySales($startDate, $endDate);

// Get top selling products
$topSellingProducts = getTopSellingProducts(10);

// Get order status count
$orderStatusCount = getOrderStatusCount();

// Get payment method count
$paymentMethodCount = getPaymentMethodCount();

// Get user statistics
$userStats = getCustomerStats();

// Get product statistics
$productStats = getProductStats();

$pageTitle = "گزارش‌ها";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-chart-bar"></i> گزارش‌ها</h1>
        <div class="admin-actions">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> بازگشت
            </a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="filter-bar">
        <form method="GET" action="reports.php">
            <div class="filter-group">
                <label for="year">سال</label>
                <select id="year" name="year" class="form-control" onchange="this.form.submit()">
                    <?php for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year === $y ? 'selected' : ''; ?>>
                            <?php echo toPersianNumbers($y); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="start_date">از تاریخ</label>
                <input type="date" id="start_date" name="start_date" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($startDate); ?>">
            </div>
            
            <div class="filter-group">
                <label for="end_date">تا تاریخ</label>
                <input type="date" id="end_date" name="end_date" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($endDate); ?>">
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> فیلتر
                </button>
                <a href="reports.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> بازنشانی
                </a>
            </div>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #2e7d32, #4caf50);">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <span class="stat-value"><?php 
                $totalSales = array_sum(array_column($salesReport, 'total_sales'));
                echo formatPrice($totalSales);
            ?></span>
            <span class="stat-label">فروش کل</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #2196f3, #03a9f4);">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <span class="stat-value"><?php 
                $totalOrders = array_sum(array_column($salesReport, 'order_count'));
                echo toPersianNumbers($totalOrders);
            ?></span>
            <span class="stat-label">تعداد سفارش‌ها</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ff9800, #ffc107);">
                <i class="fas fa-box-open"></i>
            </div>
            <span class="stat-value"><?php 
                $totalQuantity = array_sum(array_column($salesReport, 'order_count'));
                echo toPersianNumbers($totalQuantity);
            ?></span>
            <span class="stat-label">میانگین سفارش روزانه</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #9c27b0, #673ab7);">
                <i class="fas fa-chart-line"></i>
            </div>
            <span class="stat-value"><?php 
                $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
                echo formatPrice($avgOrderValue);
            ?></span>
            <span class="stat-label">میانگین ارزش سفارش</span>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="dashboard-charts">
        <!-- Monthly Sales Chart -->
        <div class="chart-card">
            <h3><i class="fas fa-calendar-alt"></i> فروش ماهانه</h3>
            <div class="chart-container">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>
        
        <!-- Category Sales Chart -->
        <div class="chart-card">
            <h3><i class="fas fa-folder-open"></i> فروش بر اساس دسته‌بندی</h3>
            <div class="chart-container">
                <canvas id="categorySalesChart"></canvas>
            </div>
        </div>
        
        <!-- Order Status Chart -->
        <div class="chart-card">
            <h3><i class="fas fa-chart-pie"></i> وضعیت سفارش‌ها</h3>
            <div class="chart-container">
                <canvas id="orderStatusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Reports Content Row -->
    <div class="dashboard-content-row">
        <!-- Daily Sales Report -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-calendar-day"></i> گزارش فروش روزانه</h3>
                <a href="reports.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-export"></i> صادر کردن
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>تاریخ</th>
                            <th>تعداد سفارش‌ها</th>
                            <th>مبلغ فروش</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($salesReport)): ?>
                            <tr>
                                <td colspan="3" class="empty-message">هیچ داده‌ای یافت نشد</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($salesReport as $report): ?>
                                <tr>
                                    <td><?php echo formatDate($report['date']); ?></td>
                                    <td><?php echo toPersianNumbers($report['order_count']); ?></td>
                                    <td><?php echo formatPrice($report['total_sales']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>جمع</th>
                            <th><?php echo toPersianNumbers(array_sum(array_column($salesReport, 'order_count'))); ?></th>
                            <th><?php echo formatPrice(array_sum(array_column($salesReport, 'total_sales'))); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <!-- Category Sales Report -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-tags"></i> فروش بر اساس دسته‌بندی</h3>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>دسته‌بندی</th>
                            <th>تعداد</th>
                            <th>مبلغ فروش</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categorySales)): ?>
                            <tr>
                                <td colspan="3" class="empty-message">هیچ داده‌ای یافت نشد</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categorySales as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo toPersianNumbers($category['quantity']); ?></td>
                                    <td><?php echo formatPrice($category['total_sales']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Top Selling Products -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-fire"></i> پرفروش‌ترین محصولات</h3>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>محصول</th>
                            <th>تعداد فروش</th>
                            <th>مبلغ فروش</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topSellingProducts)): ?>
                            <tr>
                                <td colspan="3" class="empty-message">هیچ محصولی یافت نشد</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($topSellingProducts as $index => $product): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div style="font-size: 11px; color: #666;">#<?php echo toPersianNumbers($index + 1); ?></div>
                                    </td>
                                    <td><?php echo toPersianNumbers($product['quantity_sold'] ?? 0); ?></td>
                                    <td><?php echo formatPrice($product['price'] * ($product['quantity_sold'] ?? 0)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Order Status Report -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-clipboard-list"></i> گزارش وضعیت سفارش‌ها</h3>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>وضعیت</th>
                            <th>تعداد</th>
                            <th>درصد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalOrdersForStatus = array_sum($orderStatusCount);
                        $statuses = [
                            'pending' => 'در انتظار',
                            'processing' => 'در حال پردازش',
                            'shipped' => 'ارسال شده',
                            'delivered' => 'تحویل داده شده',
                            'completed' => 'تکمیل شده',
                            'cancelled' => 'کنسل شده'
                        ];
                        
                        foreach ($statuses as $key => $label):
                            $count = $orderStatusCount[$key] ?? 0;
                            $percentage = $totalOrdersForStatus > 0 ? ($count / $totalOrdersForStatus) * 100 : 0;
                        ?>
                            <tr>
                                <td><?php echo $label; ?></td>
                                <td><?php echo toPersianNumbers($count); ?></td>
                                <td><?php echo toPersianNumbers(number_format($percentage, 2)); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>جمع</th>
                            <th><?php echo toPersianNumbers($totalOrdersForStatus); ?></th>
                            <th>100%</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <!-- Payment Method Report -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-credit-card"></i> گزارش روش‌های پرداخت</h3>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>روش پرداخت</th>
                            <th>تعداد</th>
                            <th>درصد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalOrdersForPayment = array_sum($paymentMethodCount);
                        $paymentMethods = [
                            'cash' => 'پرداخت در محل',
                            'online' => 'پرداخت آنلاین',
                            'transfer' => 'واریز بانکی',
                            'wallet' => 'کیف پول'
                        ];
                        
                        foreach ($paymentMethods as $key => $label):
                            $count = $paymentMethodCount[$key] ?? 0;
                            $percentage = $totalOrdersForPayment > 0 ? ($count / $totalOrdersForPayment) * 100 : 0;
                        ?>
                            <tr>
                                <td><?php echo $label; ?></td>
                                <td><?php echo toPersianNumbers($count); ?></td>
                                <td><?php echo toPersianNumbers(number_format($percentage, 2)); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>جمع</th>
                            <th><?php echo toPersianNumbers($totalOrdersForPayment); ?></th>
                            <th>100%</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <!-- Customer Statistics -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-users"></i> آمار مشتریان</h3>
            </div>
            
            <div class="stats-grid">
                <div class="mini-stat-card">
                    <div class="stat-icon" style="background: #2e7d32;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo toPersianNumbers($userStats['total']); ?></span>
                        <span class="stat-label">کل مشتریان</span>
                    </div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="stat-icon" style="background: #4caf50;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo toPersianNumbers($userStats['new_this_month']); ?></span>
                        <span class="stat-label">مشتریان جدید این ماه</span>
                    </div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="stat-icon" style="background: #2196f3;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo toPersianNumbers($userStats['active']); ?></span>
                        <span class="stat-label">فعال</span>
                    </div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="stat-icon" style="background: #ff9800;">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo toPersianNumbers($userStats['inactive']); ?></span>
                        <span class="stat-label">غیرفعال</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Statistics -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-box-open"></i> آمار محصولات</h3>
            </div>
            
            <div class="stats-grid">
                <div class="mini-stat-card">
                    <div class="stat-icon" style="background: #2e7d32;">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo toPersianNumbers($productStats['total']); ?></span>
                        <span class="stat-label">کل محصولات</span>
                    </div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="stat-icon" style="background: #4caf50;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo toPersianNumbers($productStats['published']); ?></span>
                        <span class="stat-label">منتشر شده</span>
                    </div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="stat-icon" style="background: #ff9800;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo toPersianNumbers($productStats['featured']); ?></span>
                        <span class="stat-label">ویژه</span>
                    </div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="stat-icon" style="background: #8bc34a;">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo toPersianNumbers($productStats['in_stock']); ?></span>
                        <span class="stat-label">موجود</span>
                    </div>
                </div>
                
                <div class="mini-stat-card">
                    <div class="stat-icon" style="background: #f44336;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo toPersianNumbers($productStats['out_of_stock']); ?></span>
                        <span class="stat-label">ناموجود</span>
                    </div>
                </div>
            </div>
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
    const persianLabels = statusLabels.map(label => {
        switch(label) {
            case 'pending': return 'در انتظار';
            case 'processing': return 'در حال پردازش';
            case 'shipped': return 'ارسال شده';
            case 'delivered': return 'تحویل داده شده';
            case 'completed': return 'تکمیل شده';
            case 'cancelled': return 'کنسل شده';
            default: return label;
        }
    });
    
    new Chart(orderStatusCtx, {
        type: 'doughnut',
        data: {
            labels: persianLabels,
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
</script>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
}

.mini-stat-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-sm);
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.mini-stat-card .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--border-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.mini-stat-card .stat-content {
    display: flex;
    flex-direction: column;
}

.mini-stat-card .stat-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-primary);
}

.mini-stat-card .stat-label {
    font-size: 12px;
    color: var(--text-secondary);
}
</style>

<?php require_once 'footer.php'; ?>
