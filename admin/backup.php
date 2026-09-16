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
if ($adminRole !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create backup
    if (isset($_POST['create_backup'])) {
        $result = backupDatabase();
        
        if ($result) {
            logAdminActivity('create_backup', 'پشتیبانی از پایگاه داده ایجاد شد');
            $success = 'پشتیبانی با موفقیت ایجاد شد.';
        } else {
            $error = 'خطا در ایجاد پشتیبان.';
        }
    }
    
    // Restore backup
    if (isset($_POST['restore_backup'])) {
        $backupFile = $_POST['backup_file'] ?? '';
        
        if (!empty($backupFile)) {
            $result = restoreDatabase(ROOT_PATH . '/backup/' . $backupFile);
            
            if ($result) {
                logAdminActivity('restore_backup', 'پشتیبانی بازیابی شد: ' . $backupFile);
                $success = 'پشتیبان با موفقیت بازیابی شد.';
            } else {
                $error = 'خطا در بازیابی پشتیبان.';
            }
        } else {
            $error = 'لطفا فایل پشتیبان را انتخاب کنید.';
        }
    }
    
    // Optimize database
    if (isset($_POST['optimize_database'])) {
        $optimized = optimizeDatabase();
        
        if ($optimized > 0) {
            logAdminActivity('optimize_database', toPersianNumbers($optimized) . ' جدول بهینه‌سازی شد');
            $success = toPersianNumbers($optimized) . ' جدول با موفقیت بهینه‌سازی شد.';
        } else {
            $error = 'هیچ جدول برای بهینه‌سازی یافت نشد.';
        }
    }
    
    // Repair database
    if (isset($_POST['repair_database'])) {
        $repaired = repairDatabase();
        
        if ($repaired > 0) {
            logAdminActivity('repair_database', toPersianNumbers($repaired) . ' جدول تعمیر شد');
            $success = toPersianNumbers($repaired) . ' جدول با موفقیت تعمیر شد.';
        } else {
            $error = 'هیچ جدول برای تعمیر یافت نشد.';
        }
    }
    
    // Delete backup
    if (isset($_POST['delete_backup'])) {
        $backupFile = $_POST['backup_file'] ?? '';
        
        if (!empty($backupFile)) {
            $filePath = ROOT_PATH . '/backup/' . $backupFile;
            
            if (file_exists($filePath) && unlink($filePath)) {
                logAdminActivity('delete_backup', 'پشتیبان حذف شد: ' . $backupFile);
                $success = 'فایل پشتیبان با موفقیت حذف شد.';
            } else {
                $error = 'خطا در حذف فایل پشتیبان.';
            }
        } else {
            $error = 'لطفا فایل پشتیبان را انتخاب کنید.';
        }
    }
}

// Get backup files
$backupFiles = [];
$backupDir = ROOT_PATH . '/backup';

if (file_exists($backupDir)) {
    $files = scandir($backupDir);
    
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filePath = $backupDir . '/' . $file;
            $backupFiles[] = [
                'name' => $file,
                'size' => filesize($filePath),
                'created' => filemtime($filePath)
            ];
        }
    }
    
    // Sort by creation date (newest first)
    usort($backupFiles, function($a, $b) {
        return $b['created'] - $a['created'];
    });
}

// Get database statistics
$dbStats = getDatabaseStats();

// Get system information
$systemInfo = getSystemInfo();

$pageTitle = "پشتیبانی و بازیابی";
require_once 'header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-database"></i> پشتیبان و بازیابی</h1>
        <div class="admin-actions">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> بازگشت
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Database Stats -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #2e7d32, #4caf50);">
                <i class="fas fa-table"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($dbStats['tables']); ?></span>
            <span class="stat-label">تعداد جدول‌ها</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #2196f3, #03a9f4);">
                <i class="fas fa-database"></i>
            </div>
            <span class="stat-value"><?php echo formatFileSize($dbStats['size']); ?></span>
            <span class="stat-label">حجم پایگاه داده</span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ff9800, #ffc107);">
                <i class="fas fa-list"></i>
            </div>
            <span class="stat-value"><?php echo toPersianNumbers($dbStats['rows']); ?></span>
            <span class="stat-label">تعداد رکوردها</span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs-container">
        <div class="tabs">
            <a href="#backup" class="tab-link active">
                <i class="fas fa-save"></i>
                پشتیبان‌گیری
            </a>
            <a href="#restore" class="tab-link">
                <i class="fas fa-undo"></i>
                بازیابی
            </a>
            <a href="#optimize" class="tab-link">
                <i class="fas fa-tune"></i>
                بهینه‌سازی
            </a>
            <a href="#system" class="tab-link">
                <i class="fas fa-info-circle"></i>
                اطلاعات سیستم
            </a>
        </div>

        <!-- Backup Tab -->
        <div id="backup" class="tab-content active">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>ایجاد پشتیبان</h3>
                </div>
                
                <div class="backup-section">
                    <p>با کلیک بر روی دکمه زیر، یک پشتیبان کامل از پایگاه داده ایجاد خواهد شد. این فرآیند ممکن است چند لحظه طول بکشد.</p>
                    
                    <form method="POST" action="backup.php">
                        <button type="submit" name="create_backup" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            ایجاد پشتیبان
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>لیست پشتیبان‌ها</h3>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>نام فایل</th>
                                <th>حجم</th>
                                <th>تاریخ ایجاد</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($backupFiles)): ?>
                                <tr>
                                    <td colspan="4" class="empty-message">هیچ فایل پشتیبانی یافت نشد</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($backupFiles as $backup): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($backup['name']); ?></td>
                                        <td><?php echo formatFileSize($backup['size']); ?></td>
                                        <td><?php echo formatDate(date('Y-m-d H:i:s', $backup['created'])); ?></td>
                                        <td>
                                            <form method="POST" action="backup.php" style="display: inline;">
                                                <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup['name']); ?>">
                                                <button type="submit" name="delete_backup" 
                                                        class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('آیا از حذف این فایل پشتیبان مطمئن هستید؟')">
                                                    <i class="fas fa-trash"></i> حذف
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Restore Tab -->
        <div id="restore" class="tab-content">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>بازیابی پشتیبان</h3>
                </div>
                
                <div class="backup-section">
                    <p>⚠️ توجه: بازیابی پشتیبان، تمام داده‌های فعلی را پاک خواهد کرد. لطفا قبل از بازیابی، از داده‌های فعلی پشتیبان بگیرید.</p>
                    
                    <form method="POST" action="backup.php">
                        <div class="form-group">
                            <label for="backup_file">فایل پشتیبان را انتخاب کنید:</label>
                            <select id="backup_file" name="backup_file" class="form-control select-control" required>
                                <option value="">انتخاب کنید</option>
                                <?php foreach ($backupFiles as $backup): ?>
                                    <option value="<?php echo htmlspecialchars($backup['name']); ?>">
                                        <?php echo htmlspecialchars($backup['name']); ?> (<?php echo formatFileSize($backup['size']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" name="restore_backup" class="btn btn-warning" onclick="return confirm('آیا از بازیابی این پشتیبان مطمئن هستید؟\n\nاین عمل قابل برگشت نیست!')">
                            <i class="fas fa-undo"></i>
                            بازیابی پشتیبان
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Optimize Tab -->
        <div id="optimize" class="tab-content">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>بهینه‌سازی پایگاه داده</h3>
                </div>
                
                <div class="backup-section">
                    <p>بهینه‌سازی جدول‌ها می‌تواند عملکرد پایگاه داده را بهبود بخشد. این فرآیند فضایی را که توسط رکوردهای حذف شده اشغال شده است آزاد می‌کند.</p>
                    
                    <form method="POST" action="backup.php">
                        <button type="submit" name="optimize_database" class="btn btn-primary">
                            <i class="fas fa-tune"></i>
                            بهینه‌سازی پایگاه داده
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>تعمیر پایگاه داده</h3>
                </div>
                
                <div class="backup-section">
                    <p>تعمیر جدول‌ها می‌تواند مشکلات احتمالی در پایگاه داده را برطرف کند.</p>
                    
                    <form method="POST" action="backup.php">
                        <button type="submit" name="repair_database" class="btn btn-warning">
                            <i class="fas fa-tools"></i>
                            تعمیر پایگاه داده
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- System Info Tab -->
        <div id="system" class="tab-content">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>اطلاعات سیستم</h3>
                </div>
                
                <div class="system-info">
                    <div class="info-section">
                        <h4>سرور</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">نرم‌افزار سرور:</span>
                                <span class="info-value"><?php echo htmlspecialchars($systemInfo['server_software'] ?? 'ندارد'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">نام سرور:</span>
                                <span class="info-value"><?php echo htmlspecialchars($systemInfo['server_name'] ?? 'ندارد'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">آدرس سرور:</span>
                                <span class="info-value"><?php echo htmlspecialchars($systemInfo['server_addr'] ?? 'ندارد'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">پورت سرور:</span>
                                <span class="info-value"><?php echo htmlspecialchars($systemInfo['server_port'] ?? 'ندارد'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">پروتکل:</span>
                                <span class="info-value"><?php echo htmlspecialchars($systemInfo['server_protocol'] ?? 'ندارد'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">مسیر ریشه:</span>
                                <span class="info-value"><?php echo htmlspecialchars($systemInfo['document_root'] ?? 'ندارد'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <h4>PHP</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">نسخه PHP:</span>
                                <span class="info-value"><?php echo htmlspecialchars($systemInfo['php_version'] ?? 'ندارد'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">حداکثر زمان اجرا:</span>
                                <span class="info-value"><?php echo htmlspecialchars($systemInfo['max_execution_time'] ?? 'ندارد'); ?> ثانیه</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">حافظه:</span>
                                <span class="info-value"><?php echo htmlspecialchars($systemInfo['memory_limit'] ?? 'ندارد'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">حداکثر حجم آپلود:</span>
                                <span class="info-value"><?php echo htmlspecialchars($systemInfo['upload_max_filesize'] ?? 'ندارد'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">حداکثر حجم POST:</span>
                                <span class="info-value"><?php echo htmlspecialchars($systemInfo['post_max_size'] ?? 'ندارد'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <h4>پایگاه داده</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">نوع پایگاه داده:</span>
                                <span class="info-value">MySQL</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">تعداد جدول‌ها:</span>
                                <span class="info-value"><?php echo toPersianNumbers($dbStats['tables']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">حجم پایگاه داده:</span>
                                <span class="info-value"><?php echo formatFileSize($dbStats['size']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">تعداد رکوردها:</span>
                                <span class="info-value"><?php echo toPersianNumbers($dbStats['rows']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tab functionality
function initTabs() {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href').substring(1);
            
            // Remove active class from all
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked
            this.classList.add('active');
            document.getElementById(targetId).classList.add('active');
        });
    });
}

// Initialize tabs
document.addEventListener('DOMContentLoaded', initTabs);
</script>

<style>
.backup-section {
    padding: 16px;
    background: var(--bg-primary);
    border-radius: var(--border-radius-sm);
    margin-bottom: 16px;
}

.backup-section p {
    margin-bottom: 16px;
    color: var(--text-secondary);
}

.info-section {
    margin-bottom: 24px;
}

.info-section h4 {
    font-size: 16px;
    color: var(--text-primary);
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border-color);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 16px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: var(--bg-primary);
    border-radius: var(--border-radius-sm);
    border: 1px solid var(--border-color);
}

.info-label {
    font-weight: 500;
    color: var(--text-secondary);
    font-size: 13px;
}

.info-value {
    color: var(--text-primary);
    font-size: 14px;
}
</style>

<?php require_once 'footer.php'; ?>
