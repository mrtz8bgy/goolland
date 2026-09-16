<?php
session_start();
require_once 'includes/db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate inputs
    if (empty($email)) {
        $error = 'لطفا آدرس ایمیل را وارد کنید.';
    } elseif (empty($password)) {
        $error = 'لطفا رمز عبور را وارد کنید.';
    } else {
        // Try to login
        $loginResult = loginUser($email, $password);
        
        if ($loginResult) {
            // Set remember me cookie
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 days
                
                // Save token in database
                try {
                    $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, token, expires_at, created_at) VALUES (?, ?, FROM_UNIXTIME(?), NOW()) ON DUPLICATE KEY UPDATE token = ?, expires_at = FROM_UNIXTIME(?)");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        $token,
                        $expires,
                        $token,
                        $expires
                    ]);
                } catch (PDOException $e) {
                    // Continue without saving token
                }
                
                setcookie('user_token', $token, $expires, '/', '', true, true);
                setcookie('user_id', $_SESSION['user_id'], $expires, '/', '', true, true);
            }
            
            // Log activity
            logActivity($_SESSION['user_id'], 'login', 'User logged in');
            
            // Redirect to previous page or home
            $redirectUrl = $_SESSION['redirect_url'] ?? 'index.php';
            unset($_SESSION['redirect_url']);
            
            header("Location: $redirectUrl");
            exit;
        } else {
            $error = 'ایمیل یا رمز عبور اشتباه است.';
        }
    }
}

// Check if there's a redirect URL
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_url'] = urldecode($_GET['redirect']);
}

$pageTitle = 'ورود به گولند';
$pageDescription = 'ورود به حساب کاربری در فروشگاه آنلاین گل و گیاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>ورود به حساب کاربری</h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <span>ورود</span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="auth-page">
    <div class="container">
        <div class="auth-container">
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="auth-card">
                <div class="auth-card-header">
                    <h2><i class="fas fa-sign-in-alt"></i> ورود به حساب کاربری</h2>
                    <p>به فروشگاه گل و گیاه گولند خوش آمدید</p>
                </div>
                
                <form method="POST" action="login.php" class="auth-form">
                    <div class="form-group">
                        <label for="email">آدرس ایمیل <span style="color: #f44336;">*</span></label>
                        <input type="email" id="email" name="email" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               placeholder="آدرس ایمیل خود را وارد کنید" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">رمز عبور <span style="color: #f44336;">*</span></label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" 
                                   class="form-control" 
                                   placeholder="رمز عبور خود را وارد کنید" required>
                            <button type="button" class="toggle-password" onclick="togglePassword(this, 'password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="forgot-password">
                            <a href="forgot-password.php">رمز عبور را فراموش کرده‌ام</a>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="remember">
                            <span>مرا به خاطر بسپار</span>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="login" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt"></i>
                            ورود
                        </button>
                    </div>
                    
                    <div class="auth-footer">
                        <p>حساب کاربری ندارید؟ <a href="register.php">ثبت‌نام کنید</a></p>
                    </div>
                </form>
            </div>
            
            <!-- Social Login -->
            <div class="social-login">
                <div class="social-login-divider">
                    <span>یا با حساب کاربری خود وارد شوید</span>
                </div>
                <div class="social-login-buttons">
                    <button class="social-btn google" onclick="alert('به زودی...')">
                        <i class="fab fa-google"></i>
                        گوگل
                    </button>
                    <button class="social-btn facebook" onclick="alert('به زودی...')">
                        <i class="fab fa-facebook"></i>
                        فیسبوک
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(button, inputId) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Form validation
const form = document.querySelector('.auth-form');
form.addEventListener('submit', function(e) {
    // Additional client-side validation can be added here
    return true;
});
</script>

<?php require_once 'includes/footer.php'; ?>
