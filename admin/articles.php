<?php

session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";


/* =========================
   افزودن مقاله
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_article"])) {

    $title = trim($_POST["title"]);
    $slug = trim($_POST["slug"]);
    $category_id = intval($_POST["category_id"]);
    $content = trim($_POST["content"]);

    if ($title === "") {

        $error = "عنوان مقاله را وارد کنید.";

    } else {

        if ($slug === "") {
            $slug = preg_replace('/\s+/', '-', strtolower($title));
        }

        $stmt = $conn->prepare("
            INSERT INTO articles
            (category_id, title, slug, content)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isss",
            $category_id,
            $title,
            $slug,
            $content
        );

        if ($stmt->execute()) {

            $message = "مقاله با موفقیت اضافه شد ✅";

        } else {

            $error = "خطا در افزودن مقاله.";
        }
    }
}


/* =========================
   حذف مقاله
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_article"])) {

    $id = intval($_POST["id"]);

    $stmt = $conn->prepare(
        "DELETE FROM articles WHERE id = ?"
    );

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        $message = "مقاله حذف شد ✅";

    } else {

        $error = "خطا در حذف مقاله.";
    }
}


/* =========================
   دریافت دسته‌بندی‌ها
========================= */

$categories = $conn->query(
    "SELECT id, name FROM categories ORDER BY name ASC"
);


/* =========================
   دریافت مقالات
========================= */

$articles = $conn->query("
    SELECT
        articles.id,
        articles.title,
        articles.slug,
        articles.content,
        categories.name AS category_name
    FROM articles
    LEFT JOIN categories
        ON articles.category_id = categories.id
    ORDER BY articles.id DESC
");

?>

<!DOCTYPE html>

<html lang="fa" dir="rtl">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>مقالات | Goolland</title>

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

    font-size: 23px;
}

.back {

    color: white;

    text-decoration: none;

    background: rgba(255,255,255,0.15);

    padding: 10px 18px;

    border-radius: 8px;
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

    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
}

.form-group {

    margin-bottom: 15px;
}

label {

    display: block;

    margin-bottom: 7px;

    font-weight: bold;
}

input,
select,
textarea {

    width: 100%;

    padding: 12px;

    border: 1px solid #ddd;

    border-radius: 8px;

    font-family: Tahoma, Arial, sans-serif;

    font-size: 14px;
}

textarea {

    min-height: 180px;

    resize: vertical;
}

.add-button {

    width: 100%;

    border: none;

    border-radius: 8px;

    padding: 13px;

    background: #198754;

    color: white;

    font-family: Tahoma;

    font-size: 15px;

    cursor: pointer;
}

.add-button:hover {

    background: #157347;
}

.success {

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

table {

    width: 100%;

    border-collapse: collapse;
}

th,
td {

    padding: 13px;

    border-bottom: 1px solid #eee;

    text-align: right;

    vertical-align: top;
}

th {

    background: #f8f9fa;
}

.delete-button {

    border: none;

    background: #dc3545;

    color: white;

    padding: 8px 12px;

    border-radius: 7px;

    cursor: pointer;

    font-family: Tahoma;
}

.delete-button:hover {

    background: #bb2d3b;
}

.content-preview {

    color: #666;

    line-height: 1.8;

    max-width: 350px;
}

.empty {

    text-align: center;

    color: #777;

    padding: 25px;
}

</style>

</head>

<body>


<header class="header">

    <h1>📝 مدیریت مقالات</h1>

    <a class="back" href="dashboard.php">
        ← داشبورد
    </a>

</header>


<div class="container">


<?php if ($message): ?>

    <div class="success">

        <?php echo htmlspecialchars($message); ?>

    </div>

<?php endif; ?>


<?php if ($error): ?>

    <div class="error">

        <?php echo htmlspecialchars($error); ?>

    </div>

<?php endif; ?>


<!-- =========================
     افزودن مقاله
========================= -->

<div class="box">

    <h2>➕ افزودن مقاله جدید</h2>


    <form method="POST">


        <div class="form-group">

            <label>عنوان مقاله</label>

            <input
                type="text"
                name="title"
                placeholder="مثلاً روش نگهداری گل‌های آپارتمانی"
                required
            >

        </div>


        <div class="form-group">

            <label>Slug</label>

            <input
                type="text"
                name="slug"
                placeholder="مثلاً apartment-flower-care"
            >

        </div>


        <div class="form-group">

            <label>دسته‌بندی مقاله</label>

            <select name="category_id">

                <option value="0">
                    بدون دسته‌بندی
                </option>

                <?php while ($category = $categories->fetch_assoc()): ?>

                    <option value="<?php echo $category["id"]; ?>">

                        <?php echo htmlspecialchars($category["name"]); ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </div>


        <div class="form-group">

            <label>متن مقاله</label>

            <textarea
                name="content"
                placeholder="متن کامل مقاله را اینجا بنویسید..."
            ></textarea>

        </div>


        <button
            type="submit"
            name="add_article"
            class="add-button"
        >

            📝 افزودن مقاله

        </button>


    </form>

</div>


<!-- =========================
     لیست مقالات
========================= -->

<div class="box">

    <h2>📋 مقالات موجود</h2>


    <?php if ($articles->num_rows > 0): ?>

        <table>

            <thead>

                <tr>

                    <th>شناسه</th>

                    <th>عنوان</th>

                    <th>دسته‌بندی</th>

                    <th>متن</th>

                    <th>عملیات</th>

                </tr>

            </thead>


            <tbody>


            <?php while ($article = $articles->fetch_assoc()): ?>

                <tr>

                    <td>

                        <?php echo $article["id"]; ?>

                    </td>


                    <td>

                        <strong>

                            <?php echo htmlspecialchars($article["title"]); ?>

                        </strong>

                        <br>

                        <small>

                            <?php echo htmlspecialchars($article["slug"]); ?>

                        </small>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $article["category_name"] ?? "بدون دسته‌بندی"
                        );

                        ?>

                    </td>


                    <td class="content-preview">

                        <?php

                        $preview = strip_tags($article["content"]);

                        if (mb_strlen($preview) > 100) {
                            $preview = mb_substr($preview, 0, 100) . "...";
                        }

                        echo htmlspecialchars($preview);

                        ?>

                    </td>


                    <td>

                        <form method="POST"
                              onsubmit="return confirm('آیا از حذف این مقاله مطمئن هستید؟');">

                            <input
                                type="hidden"
                                name="id"
                                value="<?php echo $article["id"]; ?>"
                            >

                            <button
                                type="submit"
                                name="delete_article"
                                class="delete-button"
                            >

                                🗑️ حذف

                            </button>

                        </form>

                    </td>

                </tr>

            <?php endwhile; ?>


            </tbody>

        </table>


    <?php else: ?>

        <div class="empty">

            هنوز هیچ مقاله‌ای ثبت نشده است.

        </div>

    <?php endif; ?>


</div>


</div>

</body>

</html>