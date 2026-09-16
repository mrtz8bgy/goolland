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
   افزودن دسته‌بندی
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_category"])) {

    $name = trim($_POST["name"]);
    $slug = trim($_POST["slug"]);

    if ($name === "") {

        $error = "نام دسته‌بندی را وارد کنید.";

    } else {

        if ($slug === "") {
            $slug = preg_replace('/\s+/', '-', strtolower($name));
        }

        $stmt = $conn->prepare(
            "INSERT INTO categories (name, slug) VALUES (?, ?)"
        );

        $stmt->bind_param("ss", $name, $slug);

        if ($stmt->execute()) {
            $message = "دسته‌بندی با موفقیت اضافه شد ✅";
        } else {
            $error = "خطا در افزودن دسته‌بندی.";
        }
    }
}


/* =========================
   حذف دسته‌بندی
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_category"])) {

    $id = intval($_POST["id"]);

    $stmt = $conn->prepare(
        "DELETE FROM categories WHERE id = ?"
    );

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "دسته‌بندی حذف شد ✅";
    } else {
        $error = "خطا در حذف دسته‌بندی.";
    }
}


/* =========================
   دریافت دسته‌بندی‌ها
========================= */

$result = $conn->query(
    "SELECT * FROM categories ORDER BY id DESC"
);

?>

<!DOCTYPE html>

<html lang="fa" dir="rtl">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>دسته‌بندی‌ها | Goolland</title>

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

    max-width: 1000px;

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

input {

    width: 100%;

    padding: 12px;

    border: 1px solid #ddd;

    border-radius: 8px;

    font-family: Tahoma;
}

button {

    border: none;

    border-radius: 8px;

    padding: 11px 18px;

    cursor: pointer;

    font-family: Tahoma;
}

.add-button {

    width: 100%;

    background: #198754;

    color: white;

    font-size: 15px;
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

    padding: 14px;

    border-bottom: 1px solid #eee;

    text-align: right;
}

th {

    background: #f8f9fa;
}

.delete-button {

    background: #dc3545;

    color: white;
}

.delete-button:hover {

    background: #bb2d3b;
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

    <h1>📂 مدیریت دسته‌بندی‌ها</h1>

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


<!-- افزودن دسته‌بندی -->

<div class="box">

    <h2>➕ افزودن دسته‌بندی جدید</h2>

    <form method="POST">

        <div class="form-group">

            <label>نام دسته‌بندی</label>

            <input
                type="text"
                name="name"
                placeholder="مثلاً گل‌های آپارتمانی"
                required
            >

        </div>


        <div class="form-group">

            <label>Slug</label>

            <input
                type="text"
                name="slug"
                placeholder="مثلاً apartment-flowers"
            >

        </div>


        <button
            type="submit"
            name="add_category"
            class="add-button"
        >

            ➕ افزودن دسته‌بندی

        </button>

    </form>

</div>


<!-- لیست دسته‌بندی‌ها -->

<div class="box">

    <h2>📋 دسته‌بندی‌های موجود</h2>


    <?php if ($result->num_rows > 0): ?>

        <table>

            <thead>

                <tr>

                    <th>شناسه</th>

                    <th>نام</th>

                    <th>Slug</th>

                    <th>عملیات</th>

                </tr>

            </thead>


            <tbody>

            <?php while ($category = $result->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?php echo $category["id"]; ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($category["name"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($category["slug"]); ?>
                    </td>

                    <td>

                        <form method="POST"
                              onsubmit="return confirm('آیا از حذف این دسته‌بندی مطمئن هستید؟');">

                            <input
                                type="hidden"
                                name="id"
                                value="<?php echo $category["id"]; ?>"
                            >

                            <button
                                type="submit"
                                name="delete_category"
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

            هنوز هیچ دسته‌بندی‌ای ثبت نشده است.

        </div>

    <?php endif; ?>


</div>


</div>

</body>

</html>