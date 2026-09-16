<?php

session_start();

require_once "../includes/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "خطا در اعتبارسنجی فرم. لطفاً دوباره امتحان کنید.";
    } else {
        $username = trim($_POST["username"]);
        $password = $_POST["password"];

        $stmt = $conn->prepare("SELECT id, username, password, status FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $admin = $result->fetch_assoc();

            if ($admin['status'] !== 'active') {
                $error = "حساب کاربری شما غیرفعال است.";
            } elseif (password_verify($password, $admin["password"])) {

                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_username"] = $admin["username"];

                // Update last login
                $conn->query("UPDATE admins SET last_login = NOW() WHERE id = " . $admin["id"]);

                header("Location: dashboard.php");
                exit;

            } else {
                $error = "نام کاربری یا رمز عبور اشتباه است.";
            }

        } else {
            $error = "نام کاربری یا رمز عبور اشتباه است.";
        }
    }
}

// Generate CSRF token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
generateCSRFToken();
$csrf_token = $_SESSION['csrf_token'] ?? '';

// Get settings
$settings = [];
$result = $conn->query("SELECT * FROM settings LIMIT 1");
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ورود مدیر | Goolland</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    
    <!-- Icon Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
        }
        
        .login-card {
            background: var(--admin-surface);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius-lg);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .login-header {
            padding: 40px 30px 30px;
            text-align: center;
            background: linear-gradient(135deg, var(--admin-bg-dark) 0%, var(--admin-bg) 100%);
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            margin: auto;
            
            display: flex;
            align-items: center;
            justify-content: center;
            
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-primary-light));
            border-radius: 20px;
            
            font-size: 40px;
            margin-bottom: 20px;
        }
        
        .login-title {
            color: var(--admin-text);
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .login-subtitle {
            color: var(--admin-text-muted);
            font-size: 14px;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .login-form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .login-label {
            color: var(--admin-text);
            font-size: 14px;
            font-weight: 600;
        }
        
        .login-input-wrapper {
            position: relative;
        }
        
        .login-input-wrapper .icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--admin-text-muted);
        }
        
        .login-input {
            width: 100%;
            padding: 14px 16px 14px 45px;
            
            background: var(--admin-bg);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            
            color: var(--admin-text);
            font-size: 15px;
            
            transition: var(--admin-transition);
        }
        
        .login-input:focus {
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            outline: none;
        }
        
        .login-input::placeholder {
            color: var(--admin-text-dark);
        }
        
        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--admin-primary);
        }
        
        .remember-me label {
            color: var(--admin-text-muted);
            font-size: 13px;
        }
        
        .forgot-password {
            color: var(--admin-primary);
            font-size: 13px;
            transition: var(--admin-transition);
        }
        
        .forgot-password:hover {
            color: var(--admin-primary-light);
        }
        
        .login-btn {
            width: 100%;
            padding: 16px 24px;
            
            background: var(--admin-primary);
            border: none;
            border-radius: var(--admin-radius);
            
            color: white;
            font-size: 16px;
            font-weight: 700;
            
            cursor: pointer;
            
            transition: var(--admin-transition);
        }
        
        .login-btn:hover {
            background: var(--admin-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .login-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: var(--admin-radius-sm);
            
            padding: 12px 16px;
            
            display: flex;
            align-items: center;
            gap: 10px;
            
            color: var(--admin-danger);
            font-size: 14px;
            
            margin-bottom: 15px;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .login-error .icon {
            color: var(--admin-danger);
        }
        
        .login-footer {
            padding: 25px 30px;
            text-align: center;
            background: var(--admin-bg);
            border-top: 1px solid var(--admin-border);
        }
        
        .login-footer p {
            color: var(--admin-text-muted);
            font-size: 13px;
            margin-bottom: 0;
        }
        
        .login-footer a {
            color: var(--admin-primary);
        }
        
        .login-footer a:hover {
            color: var(--admin-primary-light);
        }
        
        .login-decorations {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: -1;
        }
        
        .login-decorations::before,
        .login-decorations::after {
            content: '';
            position: absolute;
        }
        
        .login-decorations::before {
            width: 400px;
            height: 400px;
            top: -100px;
            right: -100px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.1);
            filter: blur(100px);
        }
        
        .login-decorations::after {
            width: 300px;
            height: 300px;
            bottom: -50px;
            left: -50px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.1);
            filter: blur(100px);
        }
        
        @media (max-width: 576px) {
            body {
                padding: 15px;
            }
            
            .login-card {
                border-radius: var(--admin-radius);
            }
            
            .login-header {
                padding: 30px 20px 20px;
            }
            
            .login-logo {
                width: 60px;
                height: 60px;
                font-size: 30px;
            }
            
            .login-title {
                font-size: 20px;
            }
            
            .login-body {
                padding: 20px;
            }
            
            .login-options {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>

</head>

<body>
    
    <div class="login-decorations"></div>
    
    <div class="login-container">
        <div class="login-card">
            
            <div class="login-header">
                <div class="login-logo">🌿</div>
                <h1 class="login-title">Goolland</h1>
                <p class="login-subtitle">پنل مدیریت سایت</p>
            </div>
            
            <div class="login-body">
                <form method="POST" class="login-form" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <?php if (!empty($error)): ?>
                        <div class="login-error">
                            <i class="fas fa-exclamation-circle icon"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="login-form-group">
                        <label class="login-label" for="username">نام کاربری</label>
                        <div class="login-input-wrapper">
                            <i class="fas fa-user icon"></i>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="login-input" 
                                placeholder="نام کاربری خود را وارد کنید"
                                required
                                autocomplete="username"
                            >
                        </div>
                    </div>
                    
                    <div class="login-form-group">
                        <label class="login-label" for="password">رمز عبور</label>
                        <div class="login-input-wrapper">
                            <i class="fas fa-lock icon"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="login-input" 
                                placeholder="رمز عبور خود را وارد کنید"
                                required
                                autocomplete="current-password"
                            >
                        </div>
                    </div>
                    
                    <div class="login-options">
                        <label class="remember-me">
                            <input type="checkbox" id="remember">
                            <span>مرا به خاطر بسپار</span>
                        </label>
                        <a href="forgot-password.php" class="forgot-password">
                            رمز عبور را فراموش کرده‌اید؟
                        </a>
                    </div>
                    
                    <button type="submit" class="login-btn" id="loginBtn">
                        <i class="fas fa-sign-in-alt"></i>
                        ورود به پنل
                    </button>
                </form>
            </div>
            
            <div class="login-footer">
                <p>
                    © <?php echo date('Y'); ?> 
                    <a href="/" target="_blank">Goolland</a>
                    - تمام حقوق محفوظ است
                </p>
            </div>
            
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Form validation
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                loginBtn.disabled = true;
                setTimeout(() => {
                    loginBtn.disabled = false;
                }, 1000);
            }
        });
        
        // Remove error on input
        const inputs = loginForm.querySelectorAll('.login-input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                const errorElement = loginForm.querySelector('.login-error');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            });
        });
    </script>

</body>

</html>
