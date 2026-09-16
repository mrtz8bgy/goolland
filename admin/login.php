<?php

session_start();

require_once "../includes/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $admin = $result->fetch_assoc();

        if (password_verify($password, $admin["password"])) {

            $_SESSION["admin_id"] = $admin["id"];
            $_SESSION["admin_username"] = $admin["username"];

            header("Location: dashboard.php");
            exit;

        } else {
            $error = "نام کاربری یا رمز عبور اشتباه است.";
        }

    } else {
        $error = "نام کاربری یا رمز عبور اشتباه است.";
    }
}

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>ورود مدیر | Goolland</title>

    <style>
        body {
            font-family: Tahoma, sans-serif;
            background: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-box {
            width: 350px;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 25px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #198754;
            color: white;
            cursor: pointer;
            font-size: 16px;
        }

        .error {
            background: #ffe0e0;
            color: #b00000;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>

<body>

<div class="login-box">

    <h1>ورود مدیریت</h1>

    <?php if ($error): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input
            type="text"
            name="username"
            placeholder="نام کاربری"
            required
        >

        <input
            type="password"
            name="password"
            placeholder="رمز عبور"
            required
        >

        <button type="submit">
            ورود به پنل
        </button>

    </form>

</div>

</body>
</html>