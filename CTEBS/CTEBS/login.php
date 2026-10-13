<?php

session_start();
require_once "includes/db.php";

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string(
        $connect,
        trim($_POST['user'])
    );

    $password = mysqli_real_escape_string(
        $connect,
        trim($_POST['pass'])
    );

    if ($username == "" || $password == "") {

        $_SESSION['login_error'] = "Please enter your username and password.";

        header("Location: index.php");
        exit();
    }

    $sql = "SELECT * FROM `user`
            WHERE username = '$username'
            LIMIT 1";

    $query = mysqli_query($connect, $sql);

    if (!$query) {

        $_SESSION['login_error'] = "Database error: " . mysqli_error($connect);

        header("Location: index.php");
        exit();
    }

    $fetch = mysqli_fetch_array($query);

    if (!$fetch) {

        $_SESSION['login_error'] = "Failed to login. Please try again.";

        header("Location: index.php");
        exit();
    }

    if ($fetch['password'] != $password) {

        $_SESSION['login_error'] = "Failed to login. Please try again.";

        header("Location: index.php");
        exit();
    }


    $_SESSION['user_id'] = $fetch['user_id'];
    $_SESSION['username'] = $fetch['username'];
    $_SESSION['name'] = $fetch['name'];
    $_SESSION['role'] = $fetch['role'];


    if ($fetch['role'] == "Captain") {

        header("Location: admin/dashboard.php");
        exit();

    } elseif ($fetch['role'] == "Personnel") {

        header("Location: personnel/dashboard.php");
        exit();

    } else {

        $_SESSION['login_error'] = "Your account does not have a valid system role.";

        header("Location: index.php");
        exit();
    }
}
?>