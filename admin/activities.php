<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Get filter parameters
$action = $_GET['action'] ?? '';
$adminId = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : 0;
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 30;

// Build query
$where = [];
$params = [];

if (!empty($action)) {
    $where[] = "action LIKE ?";
    $params[] = "%$action%";
}

if ($adminId > 0) {
    $where[] = "admin_id = ?";
    $params[] = $adminId;
}

if (!empty($startDate)) {
    $where[] = "created_at >= ?";
    $params[] = $startDate;
}

if (!empty($endDate)) {
    $where[] = "created_at <= ?";
    $params[] = $endDate . ' 23:59:59';
}

$whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

// Count activities
try {
    $countQuery = "SELECT COUNT(*) FROM admin_activities" . $whereClause;
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalActivities = (int)$stmt->fetchColumn();
    $totalPages = ceil($totalActivities / $perPage);
} catch (PDOException $e) {
    $totalActivities = 0;
    $totalPages = 1;
}

// Get activities
try {
    $offset = ($page - 1) * $perPage;
    $query = "SELECT aa.*, a.username as admin_username FROM admin_activities aa LEFT JOIN admins a ON aa.admin_id = a.id" . $whereClause . " ORDER BY aa.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $activities = [];
}

// Get all admins for filter
$admins = getAllAdmins();

// Get action types
$actionTypes = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT action FROM admin_activities ORDER BY action");
    $actionTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $actionTypes = [];
}

$pageTitle = "تاریخچه فعالیت‌ها";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-history"></i> تاریخچه فعالیت‌ها</h1>
        <div class="admin-actions">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> بازگشت
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="activities.php">
            <div class="filter-group">
                <label for="action">نوع فعالیت</label>
                <select id="action" name="action" class="form-control">
                    <option value="">همه فعالیت‌ها</option>
                    <?php foreach ($actionTypes as $actionType): ?>
                        <option value="<?php echo htmlspecialchars($actionType); ?>" 
                                <?php echo $action === $actionType ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($actionType); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="admin_id">مدیر</label>
                <select id="admin_id" name="admin_id" class="form-control">
                    <option value="0">همه مدیران</option>
                    <?php foreach ($admins as $admin): ?>
                        <option value="<?php echo $admin['id']; ?>" 
                                <?php echo $adminId === $admin['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($admin['username']); ?>
                        </option>
                    <?php endforeach; ?>
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
                    <i class="fas fa-search"></i> جستجو
                </button>
                <a href="activities.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> بازنشانی
                </a>
            </div>
        </form>
    </div>

    <!-- Activities Table -->
    <div class="data-table-wrapper">
        <div class="data-table-header">
            <div class="table-title">
                <i class="fas fa-list"></i>
                لیست فعالیت‌ها (<?php echo toPersianNumbers($totalActivities); ?>)
            </div>
            <div class="table-actions">
                <button class="btn btn-danger btn-sm" onclick="confirmClearActivities()">
                    <i class="fas fa-trash"></i> پاک کردن تاریخچه
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>مدیر</th>
                        <th>فعالیت</th>
                        <th>جزئیات</th>
                        <th>IP</th>
                        <th>تاریخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activities)): ?>
                        <tr>
                            <td colspan="5" class="empty-message">
                                <i class="fas fa-history"></i>
                                <p>هیچ فعالیتی یافت نشد</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['admin_username'] ?? 'ناشناس'); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($activity['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($activity['details'] ?? 'ندارد'); ?></td>
                                <td><?php echo htmlspecialchars($activity['ip_address'] ?? 'ندارد'); ?></td>
                                <td><?php echo formatDate($activity['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination-wrapper">
            <div class="pagination-info">
                نمایش <?php echo toPersianNumbers($offset + 1); ?> تا <?php echo toPersianNumbers(min($offset + $perPage, $totalActivities)); ?> از <?php echo toPersianNumbers($totalActivities); ?> فعالیت
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&action=<?php echo urlencode($action); ?>&admin_id=<?php echo $adminId; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
                       class="page-item">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === 1 || $i === $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?php echo $i; ?>&action=<?php echo urlencode($action); ?>&admin_id=<?php echo $adminId; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
                           class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo toPersianNumbers($i); ?>
                        </a>
                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                        <span class="page-item disabled">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&action=<?php echo urlencode($action); ?>&admin_id=<?php echo $adminId; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
                       class="page-item">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Confirm clear activities
function confirmClearActivities() {
    if (confirm('آیا از پاک کردن تمام تاریخچه فعالیت‌ها مطمئن هستید؟')) {
        window.location.href = 'clear_activities.php';
    }
}
</script>

<?php require_once 'footer.php'; ?>
