<?php

require_once "includes/db.php";

$settings = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc();

$categories = $conn->query("
    SELECT *
    FROM categories
    ORDER BY id DESC
");

$products = $conn->query("
    SELECT products.*, categories.name AS category_name
    FROM products
    LEFT JOIN categories
        ON products.category_id = categories.id
    ORDER BY products.id DESC
    LIMIT 8
");

$articles = $conn->query("
    SELECT articles.*, categories.name AS category_name
    FROM articles
    LEFT JOIN categories
        ON articles.category_id = categories.id
    ORDER BY articles.id DESC
    LIMIT 6
");

require_once "includes/header.php";

?>

<!-- =========================
     Hero
========================= -->

<section class="hero">

    <div class="container hero-content">

        <div class="hero-text">

            <h1>
                <?php echo htmlspecialchars($settings["site_name"] ?? "Goolland"); ?>
            </h1>

            <p>
                <?php
                echo htmlspecialchars(
                    $settings["description"]
                    ?? "به دنیای گل‌ها و گیاهان زیبا خوش آمدید."
                );
                ?>
            </p>

            <div class="hero-buttons">

                <a href="#products" class="btn btn-primary">
                    مشاهده محصولات
                </a>

                <a href="/contact.php" class="btn btn-secondary">
                    تماس با ما
                </a>

            </div>

        </div>

        <div class="hero-image">
            🌿🌱🪴
        </div>

    </div>

</section>


<!-- =========================
     Categories
========================= -->

<section class="section">

    <div class="container">

        <div class="section-title">

            <h2>دسته‌بندی‌ها</h2>

            <p>
                محصولات مورد نظر خود را انتخاب کنید
            </p>

        </div>


        <div class="cards">

            <?php if ($categories && $categories->num_rows > 0): ?>

                <?php while ($category = $categories->fetch_assoc()): ?>

                    <div class="card">

                        <h3>
                            🌱
                            <?php
                            echo htmlspecialchars($category["name"]);
                            ?>
                        </h3>

                        <p>
                            مشاهده محصولات این دسته
                        </p>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="card">

                    <h3>هنوز دسته‌ای ثبت نشده</h3>

                    <p>
                        از پنل مدیریت یک دسته‌بندی اضافه کنید.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>


<!-- =========================
     Products
========================= -->

<section class="section" id="products">

    <div class="container">

        <div class="section-title">

            <h2>محصولات ما</h2>

            <p>
                جدیدترین گل‌ها و گیاهان
            </p>

        </div>


        <div class="products">

            <?php if ($products && $products->num_rows > 0): ?>

                <?php while ($product = $products->fetch_assoc()): ?>

                    <a
    href="/product.php?id=<?php echo $product["id"]; ?>"
    class="product-card"
></a>

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
                                <?php
                                echo htmlspecialchars($product["name"]);
                                ?>
                            </h3>

                            <?php if (!empty($product["category_name"])): ?>

                                <small>
                                    <?php
                                    echo htmlspecialchars(
                                        $product["category_name"]
                                    );
                                    ?>
                                </small>

                            <?php endif; ?>

                            <p>
                                <?php
                                echo htmlspecialchars(
                                    $product["description"] ?? ""
                                );
                                ?>
                            </p>


                            <?php if ($product["price"] !== null): ?>

                                <div class="product-price">

                                    <?php
                                    echo number_format(
                                        $product["price"]
                                    );
                                    ?>

                                    تومان

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="card">

                    <h3>هنوز محصولی ثبت نشده</h3>

                    <p>
                        از پنل مدیریت یک محصول اضافه کنید.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>


<!-- =========================
     Articles
========================= -->

<section class="section">

    <div class="container">

        <div class="section-title">

            <h2>آخرین مقالات</h2>

            <p>
                مطالب آموزشی درباره گل و گیاه
            </p>

        </div>


        <div class="articles">

            <?php if ($articles && $articles->num_rows > 0): ?>

                <?php while ($article = $articles->fetch_assoc()): ?>

                    <div class="article-card">

                        <div class="date">

                            <?php
                            echo date(
                                "Y/m/d",
                                strtotime($article["created_at"])
                            );
                            ?>

                        </div>

                        <h3>
                            <?php
                            echo htmlspecialchars($article["title"]);
                            ?>
                        </h3>

                        <p>

                            <?php

                            $text = strip_tags(
                                $article["content"]
                            );

                            echo htmlspecialchars(
                                mb_substr($text, 0, 120)
                            );

                            ?>

                            ...

                        </p>

                        <a
                            href="/article.php?id=<?php echo $article["id"]; ?>"
                            class="btn btn-secondary"
                        >
                            ادامه مطلب
                        </a>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="card">

                    <h3>هنوز مقاله‌ای ثبت نشده</h3>

                    <p>
                        از پنل مدیریت یک مقاله اضافه کنید.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>


<!-- =========================
     Contact
========================= -->

<section class="section">

    <div class="container">

        <div class="section-title">

            <h2>ارتباط با ما</h2>

            <p>
                برای دریافت اطلاعات بیشتر با ما در تماس باشید.
            </p>

        </div>


        <div class="cards">

            <?php if (!empty($settings["phone"])): ?>

                <div class="card">

                    <h3>📞 تلفن</h3>

                    <p>
                        <?php
                        echo htmlspecialchars($settings["phone"]);
                        ?>
                    </p>

                </div>

            <?php endif; ?>


            <?php if (!empty($settings["address"])): ?>

                <div class="card">

                    <h3>📍 آدرس</h3>

                    <p>
                        <?php
                        echo htmlspecialchars($settings["address"]);
                        ?>
                    </p>

                </div>

            <?php endif; ?>


            <div class="card">

                <h3>🌿 Goolland</h3>

                <p>
                    همراه شما برای انتخاب و نگهداری بهتر گل و گیاه.
                </p>

            </div>

        </div>

    </div>

</section>


<?php

require_once "includes/footer.php";

?>