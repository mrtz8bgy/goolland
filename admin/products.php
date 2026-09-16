<?php

session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

/* افزودن محصول */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_product"])) {

    $name = trim($_POST["name"]);
    $category_id = !empty($_POST["category_id"])
        ? intval($_POST["category_id"])
        : null;

    $description = trim($_POST["description"]);
    $price = !empty($_POST["price"])
        ? floatval($_POST["price"])
        : null;

    $image_name = null;

    /* آپلود عکس */
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
            $error = "خطا در آپلود تصویر.";
        } else {

            $allowed_types = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];

            $file_type = mime_content_type($_FILES["image"]["tmp_name"]);

            if (!isset($allowed_types[$file_type])) {

                $error = "فرمت تصویر باید JPG، PNG یا WEBP باشد.";

            } elseif ($_FILES["image"]["size"] > 5 * 1024 * 1024) {

                $error = "حجم تصویر نباید بیشتر از 5 مگابایت باشد.";

            } else {

                $extension = $allowed_types[$file_type];

                $image_name = uniqid("product_", true) . "." . $extension;

                $upload_dir = "../assets/images/products/";

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $upload_path = $upload_dir . $image_name;

                if (!move_uploaded_file($_FILES["image"]["tmp_name"], $upload_path)) {
                    $error = "ذخیره تصویر انجام نشد.";
                    $image_name = null;
                }
            }
        }
    }

    /* ذخیره محصول */
    if ($error === "") {

        $stmt = $conn->prepare("
            INSERT INTO products
            (category_id, name, description, price, image)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "issds",
            $category_id,
            $name,
            $description,
            $price,
            $image_name
        );

        if ($stmt->execute()) {
            $message = "محصول با موفقیت اضافه شد ✅";
        } else {
            $error = "خطا در ثبت محصول: " . $stmt->error;
        }
    }
}


/* حذف محصول */
if (isset($_GET["delete"])) {

    $id = intval($_GET["delete"]);

    /* پیدا کردن عکس */
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    /* حذف محصول */
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        /* حذف عکس محصول */
        if (!empty($product["image"])) {

            $image_path = "../assets/images/products/" . $product["image"];

            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        $message = "محصول حذف شد.";
    }
}


/* دریافت دسته‌بندی‌ها */
$categories = $conn->query("
    SELECT *
    FROM categories
    ORDER BY name ASC
");


/* دریافت محصولات */
$products = $conn->query("
    SELECT
        products.*,
        categories.name AS category_name
    FROM products
    LEFT JOIN categories
        ON products.category_id = categories.id
    ORDER BY products.id DESC
");

?>

<!DOCTYPE html>

<html lang="fa" dir="rtl">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>مدیریت محصولات | Goolland</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Tahoma, Arial, sans-serif;
    background: #f4f7f5;
    color: #333;
}

.header {
    background: #198754;
    color: white;
    padding: 20px 30px;
}

.header h1 {
    margin: 0;
}

.container {
    max-width: 1100px;
    margin: 30px auto;
    padding: 0 20px;
}

.box {
    background: white;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}

h2 {
    margin-top: 0;
}

input,
select,
textarea {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-family: inherit;
}

textarea {
    min-height: 120px;
    resize: vertical;
}

button {
    background: #198754;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 15px;
}

button:hover {
    background: #146c43;
}

.message {
    background: #d1e7dd;
    color: #0f5132;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.error {
    background: #f8d7da;
    color: #842029;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.products {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 20px;
}

.product {
    border: 1px solid #eee;
    border-radius: 12px;
    overflow: hidden;
    background: white;
}

.product img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    display: block;
}

.no-image {
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eef5f0;
    font-size: 50px;
}

.product-info {
    padding: 18px;
}

.product-info h3 {
    margin-top: 0;
}

.delete {
    display: inline-block;
    background: #dc3545;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    text-decoration: none;
    margin-top: 10px;
}

.delete:hover {
    background: #bb2d3b;
}

.back {
    display: inline-block;
    margin-bottom: 20px;
    color: #198754;
    text-decoration: none;
}

</style>

</head>

<body>

<header class="header">

<h1>🌿 مدیریت محصولات Goolland</h1>

</header>

<div class="container">

<a class="back" href="dashboard.php">
    ← بازگشت به داشبورد
</a>


<?php if ($message): ?>

<div class="message">
    <?php echo htmlspecialchars($message); ?>
</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="error">
    <?php echo htmlspecialchars($error); ?>
</div>

<?php endif; ?>


<div class="box">

<h2>➕ افزودن محصول جدید</h2>

<form method="POST" enctype="multipart/form-data">

<input
    type="text"
    name="name"
    placeholder="نام محصول"
    required
>

<select name="category_id">

<option value="">
    بدون دسته‌بندی
</option>

<?php while ($category = $categories->fetch_assoc()): ?>

<option value="<?php echo $category["id"]; ?>">

<?php echo htmlspecialchars($category["name"]); ?>

</option>

<?php endwhile; ?>

</select>


<textarea
    name="description"
    placeholder="توضیحات محصول"
></textarea>


<input
    type="number"
    name="price"
    placeholder="قیمت به تومان"
    step="0.01"
>


<label>
    تصویر محصول
</label>

<input
    type="file"
    name="image"
    accept=".jpg,.jpeg,.png,.webp"
>


<button
    type="submit"
    name="add_product"
>
    افزودن محصول
</button>

</form>

</div>


<div class="box">

<h2>🌱 محصولات ثبت شده</h2>

<div class="products">

<?php if ($products && $products->num_rows > 0): ?>

<?php while ($product = $products->fetch_assoc()): ?>

<div class="product">

<?php if (!empty($product["image"])): ?>

<img
    src="../assets/images/products/<?php echo htmlspecialchars($product["image"]); ?>"
    alt="<?php echo htmlspecialchars($product["name"]); ?>"
>

<?php else: ?>

<div class="no-image">
    🌿
</div>

<?php endif; ?>


<div class="product-info">

<h3>
<?php echo htmlspecialchars($product["name"]); ?>
</h3>


<?php if (!empty($product["category_name"])): ?>

<p>
📂
<?php echo htmlspecialchars($product["category_name"]); ?>
</p>

<?php endif; ?>


<?php if (!empty($product["description"])): ?>

<p>
<?php echo htmlspecialchars($product["description"]); ?>
</p>

<?php endif; ?>


<?php if ($product["price"] !== null): ?>

<strong>
<?php echo number_format($product["price"]); ?>
تومان
</strong>

<?php endif; ?>


<br>

<a
    class="delete"
    href="?delete=<?php echo $product["id"]; ?>"
    onclick="return confirm('آیا از حذف این محصول مطمئن هستید؟');"
>
    حذف
</a>

</div>

</div>

<?php endwhile; ?>

<?php else: ?>

<p>
هنوز محصولی ثبت نشده است.
</p>

<?php endif; ?>

</div>

</div>

</div>

</body>

</html>