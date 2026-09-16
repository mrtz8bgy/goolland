<?php
session_start();
require_once 'includes/config.php';

$error = '';
$success = '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// Check if token is valid
$tokenData = validatePasswordResetToken($token);

if (!$tokenData) {
    $error = 'لینک بازیابی رمز عبور معتبر نیست یا منقضی شده است.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword)) {
        $error = 'لطفا رمز عبور جدید را وارد کنید.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'رمز عبور باید حداقل 6 کاراکتر باشد.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'رمز عبور و تکرار آن یکسان نیست.';
    } else {
        // Check token again
        $tokenData = validatePasswordResetToken($token);
        
        if ($tokenData) {
            $result = resetPassword($tokenData['user_id'], $newPassword);
            
            if ($result) {
                // Delete token
                deletePasswordResetToken($token);
                
                $success = 'رمز عبور شما با موفقیت تغییر یافت. اکنون می‌توانید وارد شوید.';
                
                // Clear token from URL
                $token = '';
            } else {
                $error = 'خطا در تغییر رمز عبور.';
            }
        } else {
            $error = 'لینک بازیابی رمز عبور معتبر نیست یا منقضی شده است.';
        }
    }
}

$pageTitle = 'بازیابی رمز عبور';
$pageDescription = 'بازیابی رمز عبور در فروشگاه آنلاین گل و گیاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-lock"></i> بازیابی رمز عبور</h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <span>بازیابی رمز عبور</span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="auth-page">
    <div class="container">
        <div class="auth-container">
            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
                
                <div class="auth-card">
                    <div class="auth-card-header">
                        <h2><i class="fas fa-check-circle"></i> موفقیت‌آمیز</h2>
                        <p>رمز عبور شما با موفقیت تغییر یافت.</p>
                    </div>
                    
                    <div class="auth-footer">
                        <a href="login.php" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt"></i>
                            ورود به حساب کاربری
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($tokenData): ?>
                    <div class="auth-card">
                        <div class="auth-card-header">
                            <h2><i class="fas fa-lock"></i> بازیابی رمز عبور</h2>
                            <p>رمز عبور جدید خود را وارد کنید</p>
                        </div>
                        
                        <form method="POST" action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" class="auth-form">
                            <div class="form-group">
                                <label for="new_password">رمز عبور جدید <span style="color: #f44336;">*</span></label>
                                <div class="password-input">
                                    <input type="password" id="new_password" name="new_password" 
                                           class="form-control" 
                                           placeholder="رمز عبور جدید را وارد کنید" required>
                                    <button type="button" class="toggle-password" onclick="togglePassword(this, 'new_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small style="color: #666;">رمز عبور باید حداقل 6 کاراکتر باشد</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">تکرار رمز عبور جدید <span style="color: #f44336;">*</span></label>
                                <div class="password-input">
                                    <input type="password" id="confirm_password" name="confirm_password" 
                                           class="form-control" 
                                           placeholder="رمز عبور جدید را مجددا وارد کنید" required>
                                    <button type="button" class="toggle-password" onclick="togglePassword(this, 'confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="reset_password" class="btn btn-primary btn-block">
                                    <i class="fas fa-save"></i>
                                    تغییر رمز عبور
                                </button>
                            </div>
                            
                            <div class="auth-footer">
                                <p><a href="login.php">بازگشت به صفحه ورود</a></p>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="auth-card">
                        <div class="auth-card-header">
                            <h2><i class="fas fa-exclamation-circle"></i> لینک معتبر نیست</h2>
                            <p>لینک بازیابی رمز عبور معتبر نیست یا منقضی شده است.</p>
                        </div>
                        
                        <div class="auth-footer">
                            <a href="forgot-password.php" class="btn btn-primary btn-block">
                                <i class="fas fa-key"></i>
                                درخواست لینک جدید
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
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
</script>

<?php require_once 'includes/footer.php'; ?>
