<?php

require_once "../includes/db.php";

$username = "admin";
$password = "Goolland@2026!";

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO admins (username, password) VALUES (?, ?)"
);

$stmt->bind_param("ss", $username, $hashed_password);

if ($stmt->execute()) {

    echo "مدیر با موفقیت ساخته شد ✅";
    echo "<br>";
    echo "نام کاربری: admin";
    echo "<br>";
    echo "رمز عبور: Goolland@2026!";

} else {

    echo "خطا: " . $stmt->error;

}

?>