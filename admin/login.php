<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// If already logged in, redirect to dashboard
if (isAdminLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'لطفا نام کاربری و رمز عبور را وارد کنید.';
    } else {
        // Check admin credentials
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM admins WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                // Login successful
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['last_activity'] = time();

                // Log activity
                logActivity('admin_login', 'ورود به پنل ادمین توسط ' . $admin['username']);

                // Handle remember me
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                    
                    // Save token to database
                    $stmt = $pdo->prepare("INSERT INTO admin_sessions (admin_id, token, expiry) VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE token = ?, expiry = ?");
                    $stmt->execute([$admin['id'], $token, date('Y-m-d H:i:s', $expiry), $token, date('Y-m-d H:i:s', $expiry)]);
                    
                    // Set cookie
                    setcookie('admin_token', $token, $expiry, '/', '', true, true);
                    setcookie('admin_id', $admin['id'], $expiry, '/', '', true, true);
                }

                header("Location: dashboard.php");
                exit;
            } else {
                $error = 'نام کاربری یا رمز عبور اشتباه است.';
                // Log failed attempt
                logActivity('admin_login_failed', 'تلاش ناموفق برای ورود با نام کاربری: ' . $username);
            }
        } catch (PDOException $e) {
            $error = 'خطا در اتصال به پایگاه داده.';
            logError('Admin login error: ' . $e->getMessage());
        }
    }
}

// Page title
$pageTitle = "ورود به پنل ادمین";
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo getSetting('site_name', 'گولند'); ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/admin-login.css">
    
    <!-- Persian Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-leaf"></i>
                <h1>گولند</h1>
                <p>پنل مدیریت</p>
            </div>
        </div>

        <form method="POST" action="login.php" class="login-form">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username">نام کاربری</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           placeholder="نام کاربری را وارد کنید" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">رمز عبور</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" 
                           placeholder="رمز عبور را وارد کنید" required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-options">
                <label class="checkbox-wrapper">
                    <input type="checkbox" name="remember" id="remember">
                    <span class="checkmark"></span>
                    <span>مرا به خاطر بسپار</span>
                </label>
            </div>

            <button type="submit" name="login" class="btn btn-primary btn-block">
                ورود به پنل
            </button>

            <div class="login-footer">
                <a href="../index.php">بازگشت به سایت</a>
            </div>
        </form>
    </div>

    <div class="login-decoration">
        <div class="decoration-item"></div>
        <div class="decoration-item"></div>
        <div class="decoration-item"></div>
        <div class="decoration-item"></div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.querySelector('.toggle-password i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleBtn.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Add focus effect to inputs
document.querySelectorAll('.input-wrapper input').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.classList.add('focused');
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.classList.remove('focused');
    });
});
</script>

</body>
</html>
