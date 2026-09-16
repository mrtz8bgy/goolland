<?php
require_once __DIR__ . "/db.php";

$settings = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($settings["site_name"] ?? "Goolland"); ?>
    </title>

    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>

<header class="site-header">

    <div class="container header-inner">

        <div class="logo">
            <a href="/index.php">
                🌿
                <?php echo htmlspecialchars($settings["site_name"] ?? "Goolland"); ?>
            </a>
        </div>

        <nav class="main-menu">

            <a href="/index.php">خانه</a>

            <a href="/about.php">درباره ما</a>

            <a href="/articles.php">مقالات</a>

            <a href="/contact.php">تماس با ما</a>

        </nav>

        <div class="header-phone">

            <?php if (!empty($settings["phone"])): ?>

                📞
                <a href="tel:<?php echo htmlspecialchars($settings["phone"]); ?>">
                    <?php echo htmlspecialchars($settings["phone"]); ?>
                </a>

            <?php endif; ?>

        </div>

    </div>

</header>

<main>