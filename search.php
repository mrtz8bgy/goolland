<?php

require_once "includes/db.php";

$q = isset($_GET["q"]) ? trim($_GET["q"]) : "";

$products = [];
$articles = [];

if ($q !== "") {

    $search = "%" . $q . "%";

    // جستجو در محصولات
    $stmt = $conn->prepare("
        SELECT products.*, categories.name AS category_name
        FROM products
        LEFT JOIN categories
            ON products.category_id = categories.id
        WHERE products.name LIKE ?
           OR products.description LIKE ?
        ORDER BY products.id DESC
    ");

    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();

    $product_result = $stmt->get_result();

    while ($row = $product_result->fetch_assoc()) {
        $products[] = $row;
    }


    // جستجو در مقالات
    $stmt = $conn->prepare("
        SELECT articles.*, categories.name AS category_name
        FROM articles
        LEFT JOIN categories
            ON articles.category_id = categories.id
        WHERE articles.title LIKE ?
           OR articles.content LIKE ?
        ORDER BY articles.id DESC
    ");

    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();

    $article_result = $stmt->get_result();

    while ($row = $article_result->fetch_assoc()) {
        $articles[] = $row;
    }
}

require_once "includes/header.php";

?>

<section class="search-page">

    <div class="container">

        <div class="section-title">
            <h1>🔎 جستجو در Goolland</h1>

            <?php if ($q !== ""): ?>

                <p>
                    نتایج جستجو برای:
                    <strong>
                        <?php echo htmlspecialchars($q); ?>
                    </strong>
                </p>

            <?php else: ?>

                <p>
                    نام محصول یا مقاله مورد نظر خود را جستجو کنید.
                </p>

            <?php endif; ?>
        </div>


        <?php if ($q !== ""): ?>

            <!-- محصولات -->

            <?php if (count($products) > 0): ?>

                <div class="search-section">

                    <h2>🌿 محصولات</h2>

                    <div class="products">

                        <?php foreach ($products as $product): ?>

                            <a
                                href="/product.php?id=<?php echo $product["id"]; ?>"
                                class="product-card"
                            >

                                <?php if (!empty($product["image"])): ?>

                                    <img
                                        src="/assets/images/products/<?php echo htmlspecialchars($product["image"]); ?>"
                                        alt="<?php echo htmlspecialchars($product["name"]); ?>"
                                        class="product-real-image"
                                    >

                                <?php else: ?>

                                    <div class="product-image">
                                        🌿
                                    </div>

                                <?php endif; ?>


                                <div class="product-info">

                                    <h3>
                                        <?php echo htmlspecialchars($product["name"]); ?>
                                    </h3>

                                    <?php if (!empty($product["category_name"])): ?>

                                        <small>
                                            <?php echo htmlspecialchars($product["category_name"]); ?>
                                        </small>

                                    <?php endif; ?>

                                    <p>
                                        <?php echo htmlspecialchars($product["description"] ?? ""); ?>
                                    </p>

                                    <?php if ($product["price"] !== null): ?>

                                        <div class="product-price">
                                            <?php echo number_format($product["price"]); ?>
                                            تومان
                                        </div>

                                    <?php endif; ?>

                                </div>

                            </a>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- مقالات -->

            <?php if (count($articles) > 0): ?>

                <div class="search-section">

                    <h2>📝 مقالات</h2>

                    <div class="articles-grid">

                        <?php foreach ($articles as $article): ?>

                            <article class="article-card">

                                <?php if (!empty($article["image"])): ?>

                                    <img
                                        src="/assets/images/articles/<?php echo htmlspecialchars($article["image"]); ?>"
                                        alt="<?php echo htmlspecialchars($article["title"]); ?>"
                                        class="article-card-image"
                                    >

                                <?php else: ?>

                                    <div class="article-card-image-placeholder">
                                        🌿
                                    </div>

                                <?php endif; ?>


                                <div class="article-card-body">

                                    <?php if (!empty($article["category_name"])): ?>

                                        <span class="article-category">
                                            📂
                                            <?php echo htmlspecialchars($article["category_name"]); ?>
                                        </span>

                                    <?php endif; ?>

                                    <h2>
                                        <?php echo htmlspecialchars($article["title"]); ?>
                                    </h2>

                                    <p>
                                        <?php
                                        $text = strip_tags($article["content"]);
                                        echo htmlspecialchars(mb_substr($text, 0, 120));
                                        ?>
                                        ...
                                    </p>

                                    <a
                                        href="/article.php?id=<?php echo $article["id"]; ?>"
                                        class="btn btn-secondary"
                                    >
                                        ادامه مطلب ←
                                    </a>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- نتیجه پیدا نشد -->

            <?php if (count($products) === 0 && count($articles) === 0): ?>

                <div class="empty-articles">

                    <div class="empty-icon">
                        🔍
                    </div>

                    <h2>
                        نتیجه‌ای پیدا نشد
                    </h2>

                    <p>
                        عبارت دیگری را امتحان کنید.
                    </p>

                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>

</section>


<?php

require_once "includes/footer.php";

?>