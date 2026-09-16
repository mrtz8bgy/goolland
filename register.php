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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    
    // Validate inputs
    if (empty($name)) {
        $error = 'لطفا نام و نام خانوادگی را وارد کنید.';
    } elseif (empty($email)) {
        $error = 'لطفا آدرس ایمیل را وارد کنید.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'لطفا آدرس ایمیل معتبر وارد کنید.';
    } elseif (empty($password)) {
        $error = 'لطفا رمز عبور را وارد کنید.';
    } elseif (strlen($password) < 6) {
        $error = 'رمز عبور باید حداقل 6 کاراکتر باشد.';
    } elseif ($password !== $confirmPassword) {
        $error = 'رمز عبور و تکرار آن یکسان نیست.';
    } else {
        // Check if email already exists
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn()) {
                $error = 'ایمیل وارد شده قبلا ثبت شده است.';
            } else {
                // Create user
                $result = registerUser([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => $password,
                    'address' => $address
                ]);
                
                if ($result['success']) {
                    // Log activity
                    logActivity($result['user_id'], 'register', 'User registered');
                    
                    // Redirect to home page
                    header("Location: index.php");
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        } catch (PDOException $e) {
            $error = 'خطا در ثبت‌نام: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'ثبت‌نام در گولند';
$pageDescription = 'ثبت‌نام در فروشگاه آنلاین گل و گیاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>ثبت‌نام در گولند</h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <span>ثبت‌نام</span>
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
                    <h2><i class="fas fa-user-plus"></i> ثبت‌نام</h2>
                    <p>برای خرید از گولند، لطفا اطلاعات خود را وارد کنید</p>
                </div>
                
                <form method="POST" action="register.php" class="auth-form">
                    <div class="form-group">
                        <label for="name">نام و نام خانوادگی <span style="color: #f44336;">*</span></label>
                        <input type="text" id="name" name="name" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                               placeholder="نام و نام خانوادگی خود را وارد کنید" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">آدرس ایمیل <span style="color: #f44336;">*</span></label>
                        <input type="email" id="email" name="email" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               placeholder="آدرس ایمیل خود را وارد کنید" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">شماره تلفن</label>
                        <input type="tel" id="phone" name="phone" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                               placeholder="شماره تلفن خود را وارد کنید">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">آدرس</label>
                        <textarea id="address" name="address" 
                                  class="form-control" 
                                  placeholder="آدرس خود را وارد کنید"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
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
                        <small style="color: #666;">رمز عبور باید حداقل 6 کاراکتر باشد</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">تکرار رمز عبور <span style="color: #f44336;">*</span></label>
                        <div class="password-input">
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-control" 
                                   placeholder="رمز عبور را مجددا وارد کنید" required>
                            <button type="button" class="toggle-password" onclick="togglePassword(this, 'confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="terms" required>
                            <span>با <a href="page.php?slug=terms">شرایط و قوانین</a> موافقم</span>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="register" class="btn btn-primary btn-block">
                            <i class="fas fa-user-plus"></i>
                            ثبت‌نام
                        </button>
                    </div>
                    
                    <div class="auth-footer">
                        <p>قبلا ثبت‌نام کرده‌اید؟ <a href="login.php">وارد شوید</a></p>
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
