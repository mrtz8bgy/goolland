<?php
session_start();
require_once 'includes/config.php';

// Get page slug
$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';

// Get page
$page = getPageBySlug($slug);

if (!$page) {
    header("Location: 404.php");
    exit;
}

$pageTitle = htmlspecialchars($page['title']);
$pageDescription = htmlspecialchars($page['meta_description'] ?? $page['excerpt'] ?? substr(strip_tags($page['content']), 0, 160));
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><?php echo htmlspecialchars($page['title']); ?></h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <span><?php echo htmlspecialchars($page['title']); ?></span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="page-content">
    <div class="container">
        <article class="static-page">
            <!-- Page Content -->
            <div class="page-body">
                <?php echo $page['content']; ?>
            </div>
        </article>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
