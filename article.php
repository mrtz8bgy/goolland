<?php

require_once "includes/db.php";

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

$stmt = $conn->prepare("
    SELECT
        articles.*,
        categories.name AS category_name
    FROM articles
    LEFT JOIN categories
        ON articles.category_id = categories.id
    WHERE articles.id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$article = $result->fetch_assoc();

if (!$article) {
    http_response_code(404);
    die("مقاله مورد نظر پیدا نشد.");
}

require_once "includes/header.php";

?>

<section class="article-detail-section">

    <div class="container">

        <article class="article-detail">

            <?php if (!empty($article["image"])): ?>

                <img
                    src="/assets/images/articles/<?php echo htmlspecialchars($article["image"]); ?>"
                    alt="<?php echo htmlspecialchars($article["title"]); ?>"
                    class="article-detail-image"
                >

            <?php endif; ?>


            <?php if (!empty($article["category_name"])): ?>

                <div class="article-category">
                    📂
                    <?php echo htmlspecialchars($article["category_name"]); ?>
                </div>

            <?php endif; ?>


            <h1>
                <?php echo htmlspecialchars($article["title"]); ?>
            </h1>


            <div class="article-date">
                📅
                <?php echo date("Y/m/d", strtotime($article["created_at"])); ?>
            </div>


            <div class="article-content">

                <?php
                echo nl2br(htmlspecialchars($article["content"]));
                ?>

            </div>


            <a href="/articles.php" class="back-articles">
                ← بازگشت به مقالات
            </a>

        </article>

    </div>

</section>

<?php

require_once "includes/footer.php";

?>