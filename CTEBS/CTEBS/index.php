<?php

session_start();

if (isset($_SESSION['user_id'])) {

    if ($_SESSION['role'] == "Captain") {
        header("Location: admin/dashboard.php");
        exit();
    }

    if ($_SESSION['role'] == "Personnel") {
        header("Location: personnel/dashboard.php");
        exit();
    }
}

$error = "";

if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CTBS - Login</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <div class="login-container">

        <div class="login-box">

            <div class="logo">
                <img src="img/barangay-logo.png" alt="Barangay San Miguel Logo">
            </div>

            <h2>Welcome Back</h2>

            <p class="subtitle">
                Community Tool and Equipment Borrowing System
            </p>

            <?php if ($error != ""): ?>

                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

            <form action="login.php" method="POST">

                <div class="form-group">

                    <label for="user">
                        Username
                    </label>

                    <input
                        type="text"
                        id="user"
                        name="user"
                        placeholder="Enter your username"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="pass">
                        Password
                    </label>

                    <input
                        type="password"
                        id="pass"
                        name="pass"
                        placeholder="Enter your password"
                        required
                    >

                </div>

                <button type="submit" name="login">
                    Login
                </button>

            </form>
            <p class="borrow-request-link">
                Are you a resident?
                <a href="borrow-request.php">Submit a borrowing request</a>
            </p>
            <div class="login-footer">

                <p>
                    Authorized Barangay Personnel Only
                </p>

            </div>

        </div>

    </div>

</body>

</html>