<?php
date_default_timezone_set('Asia/Manila');
$connect = mysqli_connect("localhost", "root", "", "ctebs");

if (!$connect) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($connect, "utf8mb4");
mysqli_query($connect, "SET time_zone = '+08:00'");
?>