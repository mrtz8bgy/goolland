<?php

$host = "localhost";
$dbname = "jidjpaow_goolland";
$username = "jidjpaow_goolland";
$password = "Goo!2026_xxxxx";

// اتصال به دیتابیس
$conn = new mysqli($host, $username, $password, $dbname);

// بررسی اتصال
if ($conn->connect_error) {
    die("خطا در اتصال به دیتابیس: " . $conn->connect_error);
}

// تنظیم زبان برای فارسی
$conn->set_charset("utf8mb4");

?>