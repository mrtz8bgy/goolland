<?php
session_start();
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'لطفا آدرس ایمیل را وارد کنید.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'لطفا آدرس ایمیل معتبر وارد کنید.';
    } else {
        $result = forgotPassword($email);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'فراموشی رمز عبور';
$pageDescription = 'بازیابی رمز عبور در فروشگاه آنلاین گل و گیاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-key"></i> فراموشی رمز عبور</h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <span>فراموشی رمز عبور</span>
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
            
            <?php if (!$success): ?>
                <div class="auth-card">
                    <div class="auth-card-header">
                        <h2><i class="fas fa-key"></i> فراموشی رمز عبور</h2>
                        <p>آدرس ایمیل خود را وارد کنید تا لینک بازیابی رمز عبور برای شما ارسال شود</p>
                    </div>
                    
                    <form method="POST" action="forgot-password.php" class="auth-form">
                        <div class="form-group">
                            <label for="email">آدرس ایمیل <span style="color: #f44336;">*</span></label>
                            <input type="email" id="email" name="email" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   placeholder="آدرس ایمیل خود را وارد کنید" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="forgot_password" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane"></i>
                                ارسال لینک بازیابی
                            </button>
                        </div>
                        
                        <div class="auth-footer">
                            <p>به یاد آوردید؟ <a href="login.php">وارد شوید</a></p>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="auth-card">
                    <div class="auth-card-header">
                        <h2><i class="fas fa-check-circle"></i> لینک بازیابی ارسال شد</h2>
                        <p>لینک بازیابی رمز عبور به آدرس ایمیل شما ارسال شد. لطفا ایمیل خود را بررسی کنید.</p>
                    </div>
                    
                    <div class="auth-footer">
                        <p>لینک را دریافت نکرده‌اید؟ <a href="forgot-password.php">دوباره امتحان کنید</a></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
