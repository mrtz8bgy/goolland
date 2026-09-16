<?php

require_once "includes/db.php";

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

$stmt = $conn->prepare("
    SELECT
        products.*,
        categories.name AS category_name
    FROM products
    LEFT JOIN categories
        ON products.category_id = categories.id
    WHERE products.id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    http_response_code(404);
    die("محصول مورد نظر پیدا نشد.");
}

require_once "includes/header.php";

?>

<section class="product-detail-section">

    <div class="container">

        <div class="product-detail">

            <div class="product-detail-image">

                <?php if (!empty($product["image"])): ?>

                    <img
                        src="/assets/images/products/<?php echo htmlspecialchars($product["image"]); ?>"
                        alt="<?php echo htmlspecialchars($product["name"]); ?>"
                    >

                <?php else: ?>

                    <div class="product-detail-no-image">
                        🌿
                    </div>

                <?php endif; ?>

            </div>


            <div class="product-detail-info">

                <?php if (!empty($product["category_name"])): ?>

                    <div class="product-category">
                        📂
                        <?php echo htmlspecialchars($product["category_name"]); ?>
                    </div>

                <?php endif; ?>


                <h1>
                    <?php echo htmlspecialchars($product["name"]); ?>
                </h1>


                <?php if (!empty($product["description"])): ?>

                    <p class="product-description">
                        <?php echo nl2br(htmlspecialchars($product["description"])); ?>
                    </p>

                <?php endif; ?>


                <?php if ($product["price"] !== null): ?>

                    <div class="product-detail-price">

                        <?php echo number_format($product["price"]); ?>

                        <span>تومان</span>

                    </div>

                <?php endif; ?>


                <a href="/contact.php" class="btn btn-primary">
                    📞 برای اطلاعات بیشتر تماس بگیرید
                </a>

                <br><br>

                <a href="/index.php#products" class="back-products">
                    ← بازگشت به محصولات
                </a>

            </div>

        </div>

    </div>

</section>

<?php

require_once "includes/footer.php";

?>