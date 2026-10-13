<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Captain/Admin only
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Captain") {
    header("Location: ../index.php");
    exit();
}

if (empty($_SESSION["user_id"])) {
    header("Location: ../logout.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

if (empty($_SESSION["profile_csrf"])) {
    $_SESSION["profile_csrf"] = bin2hex(random_bytes(32));
}

$success = "";
$error = "";

// Load the current account
$stmt = mysqli_prepare(
    $connect,
    "SELECT user_id, name, address, username, password, role
     FROM `user`
     WHERE user_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user || $user["role"] !== "Captain") {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// Handle profile and password forms
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["profile_csrf"], $token)
    ) {
        $error = "Invalid form submission. Please refresh the page and try again.";
    } elseif (isset($_POST["update_profile"])) {
        $name = trim($_POST["name"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $username = trim($_POST["username"] ?? "");

        if ($name === "" || $address === "" || $username === "") {
            $error = "Please complete all profile fields.";
        } elseif (
            strlen($name) > 150 ||
            strlen($address) > 255 ||
            strlen($username) > 100
        ) {
            $error = "One or more fields exceed the allowed length.";
        } elseif (preg_match('/\s/', $username)) {
            $error = "Username must not contain spaces.";
        } else {
            // Check if another account already uses this username
            $stmt = mysqli_prepare(
                $connect,
                "SELECT user_id FROM `user`
                 WHERE username = ? AND user_id <> ?
                 LIMIT 1"
            );

            mysqli_stmt_bind_param($stmt, "si", $username, $user_id);
            mysqli_stmt_execute($stmt);
            $duplicate = mysqli_stmt_get_result($stmt);
            $username_exists = mysqli_num_rows($duplicate) > 0;
            mysqli_stmt_close($stmt);

            if ($username_exists) {
                $error = "That username is already in use. Please choose another.";
            } else {
                $stmt = mysqli_prepare(
                    $connect,
                    "UPDATE `user`
                     SET name = ?, address = ?, username = ?
                     WHERE user_id = ? AND role = 'Captain'"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssi",
                    $name,
                    $address,
                    $username,
                    $user_id
                );

                if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) >= 0) {
                    $_SESSION["name"] = $name;
                    $_SESSION["username"] = $username;

                    $success = "Profile updated successfully.";

                    $user["name"] = $name;
                    $user["address"] = $address;
                    $user["username"] = $username;
                } else {
                    $error = "Unable to update your profile. Please try again.";
                }

                mysqli_stmt_close($stmt);
            }
        }
    } elseif (isset($_POST["change_password"])) {
        $current_password = $_POST["current_password"] ?? "";
        $new_password = $_POST["new_password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";

        // Support both legacy plaintext passwords and password_hash() values.
        $stored_password = (string) $user["password"];
        $password_matches = hash_equals($stored_password, $current_password);

        if ($current_password === "" || $new_password === "" || $confirm_password === "") {
            $error = "Please complete all password fields.";
        } elseif (!$password_matches) {
            $error = "Your current password is incorrect.";
        } elseif (strlen($new_password) < 8) {
            $error = "Your new password must be at least 8 characters long.";
        } elseif (strlen($new_password) > 4096) {
            $error = "Your new password is too long.";
        } elseif ($new_password !== $confirm_password) {
            $error = "The new passwords do not match.";
        } elseif ($current_password === $new_password) {
            $error = "Your new password must be different from your current password.";
        } else {
            $plain_password = $new_password;

            $stmt = mysqli_prepare(
                $connect,
                "UPDATE `user`
                 SET password = ?
                 WHERE user_id = ? AND role = 'Captain'"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $plain_password,
                $user_id
            );

            if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) === 1) {
                $success = "Password changed successfully.";
                $user["password"] = $plain_password;
            } else {
                $error = "Unable to change your password. Please try again.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

$csrf_token = $_SESSION["profile_csrf"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | CTEBS</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">

    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="../img/barangay-logo.png" alt="Barangay San Miguel Logo">
            <div>
                <strong>CTEBS</strong>
                <span>Community Tool and Equipment Borrowing System</span>
            </div>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-link">
                <span class="menu-icon">⌂</span><span>Dashboard</span>
            </a>
            <a href="equipment.php" class="sidebar-link">
                <span class="menu-icon">▣</span><span>Equipment</span>
            </a>
            <a href="borrow_requests.php" class="sidebar-link">
                <span class="menu-icon">▤</span><span>Borrow Requests</span>
            </a>
            <a href="transactions.php" class="sidebar-link">
                <span class="menu-icon">↔</span><span>Transactions</span>
            </a>
            <a href="returns.php" class="sidebar-link">
                <span class="menu-icon">↩</span><span>Returns</span>
            </a>
            <a href="penalties.php" class="sidebar-link">
                <span class="menu-icon">₱</span><span>Penalties</span>
            </a>
            <a href="reports.php" class="sidebar-link">
                <span class="menu-icon">▥</span><span>Reports</span>
            </a>
            <a href="personnel.php" class="sidebar-link">
                <span class="menu-icon">♙</span><span>Personnel</span>
            </a>
            <a href="qr_tagging.php" class="sidebar-link">
                <span class="menu-icon">▦</span><span>QR Tagging</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../logout.php" class="sidebar-link">
                <span class="menu-icon">↪</span><span>Logout</span>
            </a>
        </div>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div class="page-title">
                <h2>Profile Settings</h2>
                <span>Manage your administrator account</span>
            </div>

            <div class="admin-profile">
                <div class="profile-avatar"><span>👤</span></div>
                <div class="profile-info">
                    <strong><?php echo htmlspecialchars($_SESSION["name"]); ?></strong>
                    <span>Barangay Captain</span>
                </div>
                <div class="profile-dropdown">
                    <button type="button"
                            class="profile-dropdown-toggle"
                            aria-label="Open account menu"
                            aria-expanded="false"
                            onclick="toggleProfileMenu(this)">
                    <span>⌄</span>
                    </button>

                <div class="profile-dropdown-menu">
                    <a href="profile.php">Profile Settings</a>
                </div>
                </div>
            </div>
        </header>

        <section class="profile-content">

            <div class="profile-page-heading">
                <h1>Account Settings</h1>
                <p>Update your profile information and keep your account secure.</p>
            </div>

            <?php if ($success !== ""): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ""): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="profile-card-heading">
                    <div class="profile-card-icon">👤</div>
                    <div>
                        <h3>Edit Profile</h3>
                        <p>Update the details associated with your account.</p>
                    </div>
                </div>

                <form method="POST" action="profile.php" class="profile-form">
                    <input type="hidden" name="csrf_token"
                           value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div class="profile-form-group">
                        <label for="name">Full Name</label>
                        <input id="name" type="text" name="name"
                               maxlength="150" required
                               value="<?php echo htmlspecialchars($user["name"]); ?>">
                    </div>

                    <div class="profile-form-group">
                        <label for="username">Username</label>
                        <input id="username" type="text" name="username"
                               maxlength="100" required
                               value="<?php echo htmlspecialchars($user["username"]); ?>">
                    </div>

                    <div class="profile-form-group">
                        <label for="address">Address</label>
                        <input id="address" type="text" name="address"
                               maxlength="255" required
                               value="<?php echo htmlspecialchars($user["address"]); ?>">
                    </div>

                    <div class="profile-form-actions">
                        <button type="submit" name="update_profile"
                                class="profile-save-button">
                            Save Profile
                        </button>
                    </div>
                </form>
            </div>

            <div class="profile-card">
                <div class="profile-card-heading">
                    <div class="profile-card-icon">🔒</div>
                    <div>
                        <h3>Change Password</h3>
                        <p>Use your current password to set a new one.</p>
                    </div>
                </div>

                <form method="POST" action="profile.php" class="profile-form">
                    <input type="hidden" name="csrf_token"
                           value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div class="profile-form-group">
                        <label for="current_password">Current Password</label>
                        <input id="current_password" type="password"
                               name="current_password" autocomplete="current-password"
                               required>
                    </div>

                    <div class="profile-form-group">
                        <label for="new_password">New Password</label>
                        <input id="new_password" type="password"
                               name="new_password" minlength="8" maxlength="4096"
                               autocomplete="new-password" required>
                        <small>Use at least 8 characters.</small>
                    </div>

                    <div class="profile-form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input id="confirm_password" type="password"
                               name="confirm_password" minlength="8" maxlength="4096"
                               autocomplete="new-password" required>
                    </div>

                    <div class="profile-form-actions">
                        <button type="submit" name="change_password"
                                class="profile-save-button">
                            Change Password
                        </button>
                    </div>
                </form>
            </div>

            <div class="profile-security-note">
                <strong>Account security</strong>
                <p>Your account role cannot be changed from this page. Only your own
                   profile details and password can be updated here.</p>
            </div>

        </section>
    </main>
</div>
<script src="../js/script.js"></script>

</body>
</html>
