<?php

session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>داشبورد مدیریت | Goolland</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: #f5f7f6;
            color: #333;
        }

        .header {
            background: #198754;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .logout {
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.15);
            padding: 10px 18px;
            border-radius: 8px;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
        }

        .welcome h2 {
            margin-top: 0;
        }

        .menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 30px 20px;
            border-radius: 15px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            transition: 0.2s;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 7px 20px rgba(0,0,0,0.1);
        }

        .icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .card h3 {
            margin: 10px 0;
        }

        .card p {
            color: #777;
            font-size: 14px;
        }

    </style>

</head>

<body>

<header class="header">

    <h1>🌿 پنل مدیریت Goolland</h1>

    <a class="logout" href="logout.php">
        خروج از پنل
    </a>

</header>


<div class="container">

    <div class="welcome">

        <h2>
            سلام <?php echo htmlspecialchars($_SESSION["admin_username"]); ?> 👋
        </h2>

        <p>
            به پنل مدیریت سایت Goolland خوش آمدید.
        </p>

    </div>


    <div class="menu">

        <a class="card" href="settings.php">

            <div class="icon">⚙️</div>

            <h3>تنظیمات سایت</h3>

            <p>
                نام سایت، شماره تلفن، شبکه‌های اجتماعی و اطلاعات تماس
            </p>

        </a>


        <a class="card" href="categories.php">

            <div class="icon">📂</div>

            <h3>دسته‌بندی‌ها</h3>

            <p>
                مدیریت دسته‌بندی‌های سایت
            </p>

        </a>


        <a class="card" href="products.php">

            <div class="icon">🌱</div>

            <h3>محصولات</h3>

            <p>
                افزودن و مدیریت گل‌ها و محصولات
            </p>

        </a>


        <a class="card" href="articles.php">

            <div class="icon">📝</div>

            <h3>مقالات</h3>

            <p>
                مدیریت مقالات و مطالب سایت
            </p>

        </a>

    </div>

</div>

</body>

</html>