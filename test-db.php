<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "includes/db.php";

if ($conn->ping()) {
    echo "<h1>اتصال موفق شد ✅</h1>";
    echo "<p>دیتابیس Goolland با موفقیت متصل شد.</p>";
} else {
    echo "<h1>اتصال ناموفق ❌</h1>";
}

?>