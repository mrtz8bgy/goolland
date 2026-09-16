<?php

session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

/* گرفتن اطلاعات قبلی */
$result = $conn->query("SELECT * FROM settings ORDER BY id ASC LIMIT 1");

$settings = $result->fetch_assoc();

/* اگر اطلاعاتی وجود نداشت، یک رکورد بساز */
if (!$settings) {

    $conn->query("
        INSERT INTO settings
        (site_name, phone, instagram, telegram, whatsapp, address, description, logo)
        VALUES
        ('Goolland', '', '', '', '', '', '', '')
    ");

    $result = $conn->query("SELECT * FROM settings ORDER BY id ASC LIMIT 1");
    $settings = $result->fetch_assoc();
}


/* ذخیره تنظیمات */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $site_name = trim($_POST["site_name"]);
    $phone = trim($_POST["phone"]);
    $instagram = trim($_POST["instagram"]);
    $telegram = trim($_POST["telegram"]);
    $whatsapp = trim($_POST["whatsapp"]);
    $address = trim($_POST["address"]);
    $description = trim($_POST["description"]);

    $stmt = $conn->prepare("
        UPDATE settings SET
        site_name = ?,
        phone = ?,
        instagram = ?,
        telegram = ?,
        whatsapp = ?,
        address = ?,
        description = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "sssssssi",
        $site_name,
        $phone,
        $instagram,
        $telegram,
        $whatsapp,
        $address,
        $description,
        $settings["id"]
    );

    if ($stmt->execute()) {

        $message = "تنظیمات با موفقیت ذخیره شد ✅";

        /* اطلاعات جدید را دوباره بخوان */
        $result = $conn->query("SELECT * FROM settings ORDER BY id ASC LIMIT 1");
        $settings = $result->fetch_assoc();

    } else {

        $error = "خطا در ذخیره تنظیمات ❌";
    }
}

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>تنظیمات سایت | Goolland</title>

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

    max-width: 900px;

    margin: 30px auto;

    padding: 0 20px;

}

.box {

    background: white;

    padding: 30px;

    border-radius: 15px;

    box-shadow: 0 3px 15px rgba(0,0,0,0.05);

}

h2 {

    margin-top: 0;

    margin-bottom: 25px;

}

.form-group {

    margin-bottom: 20px;

}

label {

    display: block;

    margin-bottom: 8px;

    font-weight: bold;

}

input,
textarea {

    width: 100%;

    padding: 12px;

    border: 1px solid #ddd;

    border-radius: 8px;

    font-family: Tahoma, Arial, sans-serif;

    font-size: 14px;

}

textarea {

    min-height: 120px;

    resize: vertical;

}

button {

    width: 100%;

    padding: 14px;

    border: none;

    border-radius: 8px;

    background: #198754;

    color: white;

    font-size: 16px;

    cursor: pointer;

}

button:hover {

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

.note {

    background: #f8f9fa;

    padding: 15px;

    border-radius: 8px;

    margin-bottom: 25px;

    color: #666;

    line-height: 1.8;

}

</style>

</head>

<body>


<header class="header">

    <h1>⚙️ تنظیمات سایت Goolland</h1>

    <a class="back" href="dashboard.php">
        ← بازگشت به داشبورد
    </a>

</header>


<div class="container">

    <div class="box">

        <h2>اطلاعات اصلی سایت</h2>


        <div class="note">

            از این قسمت می‌توانی اطلاعات اصلی سایت را تغییر بدهی.

            بعد از ذخیره، اطلاعات در دیتابیس سایت ثبت می‌شود.

        </div>


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


        <form method="POST">


            <div class="form-group">

                <label>🌿 نام سایت</label>

                <input
                    type="text"
                    name="site_name"
                    value="<?php echo htmlspecialchars($settings["site_name"] ?? ""); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>📞 شماره تلفن</label>

                <input
                    type="text"
                    name="phone"
                    value="<?php echo htmlspecialchars($settings["phone"] ?? ""); ?>"
                    placeholder="مثلاً 09123456789"
                >

            </div>


            <div class="form-group">

                <label>📸 اینستاگرام</label>

                <input
                    type="text"
                    name="instagram"
                    value="<?php echo htmlspecialchars($settings["instagram"] ?? ""); ?>"
                    placeholder="@goolland"
                >

            </div>


            <div class="form-group">

                <label>💬 تلگرام</label>

                <input
                    type="text"
                    name="telegram"
                    value="<?php echo htmlspecialchars($settings["telegram"] ?? ""); ?>"
                    placeholder="@goolland"
                >

            </div>


            <div class="form-group">

                <label>🟢 واتساپ</label>

                <input
                    type="text"
                    name="whatsapp"
                    value="<?php echo htmlspecialchars($settings["whatsapp"] ?? ""); ?>"
                    placeholder="09123456789"
                >

            </div>


            <div class="form-group">

                <label>📍 آدرس</label>

                <textarea
                    name="address"
                    placeholder="آدرس فروشگاه یا دفتر"
                ><?php echo htmlspecialchars($settings["address"] ?? ""); ?></textarea>

            </div>


            <div class="form-group">

                <label>📝 توضیحات سایت</label>

                <textarea
                    name="description"
                    placeholder="توضیح کوتاهی درباره Goolland"
                ><?php echo htmlspecialchars($settings["description"] ?? ""); ?></textarea>

            </div>


            <button type="submit">

                💾 ذخیره تنظیمات

            </button>


        </form>

    </div>

</div>


</body>

</html>