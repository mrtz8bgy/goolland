<?php
// Admin 404 Page
$pageTitle = "صفحه یافت نشد - 404";
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - پنل مدیریت گولند</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    
    <!-- Persian Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="error-page">

<div class="admin-container">
    <div class="admin-content-wrapper">
        <div class="admin-content">
            <div class="error-container">
                <div class="error-code">404</div>
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1>صفحه یافت نشد</h1>
                <p>متأسفانه صفحه‌ای که به دنبال آن هستید یافت نشد.</p>
                <div class="error-actions">
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="fas fa-home"></i>
                        بازگشت به داشبورد
                    </a>
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i>
                        ورود مجدد
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    padding: 20px;
}

.error-container {
    text-align: center;
    max-width: 500px;
    margin: 0 auto;
    padding: 40px;
}

.error-code {
    font-size: 120px;
    font-weight: 700;
    color: #263238;
    line-height: 1;
    margin-bottom: 20px;
    opacity: 0.2;
}

.error-icon {
    font-size: 80px;
    color: #f44336;
    margin-bottom: 24px;
}

.error-container h1 {
    font-size: 28px;
    color: #263238;
    margin-bottom: 16px;
}

.error-container p {
    color: #666;
    font-size: 16px;
    margin-bottom: 32px;
}

.error-actions {
    display: flex;
    justify-content: center;
    gap: 16px;
    flex-wrap: wrap;
}

.error-actions .btn {
    min-width: 180px;
}

@media (max-width: 576px) {
    .error-code {
        font-size: 80px;
    }
    
    .error-icon {
        font-size: 60px;
    }
    
    .error-container h1 {
        font-size: 24px;
    }
    
    .error-actions {
        flex-direction: column;
    }
    
    .error-actions .btn {
        width: 100%;
    }
}
</style>

</body>
</html>
