<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Follow the role used by the existing Equipment page.
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Captain") {
    header("Location: ../index.php");
    exit();
}

// Flash messages
$message = $_SESSION["personnel_message"] ?? "";
$message_type = $_SESSION["personnel_message_type"] ?? "success";

unset(
    $_SESSION["personnel_message"],
    $_SESSION["personnel_message_type"]
);

function personnelRedirect($message, $type = "success")
{
    $_SESSION["personnel_message"] = $message;
    $_SESSION["personnel_message_type"] = $type;

    header("Location: personnel.php");
    exit();
}

function personnelEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// Update Personnel information
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit_personnel"])) {
    $user_id = filter_var($_POST["user_id"] ?? "", FILTER_VALIDATE_INT);
    $name = trim($_POST["name"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $username = trim($_POST["username"] ?? "");

    if (!$user_id || $name === "" || $address === "" || $username === "") {
        personnelRedirect("Please complete all required fields.", "error");
    }

    // Make sure the selected account is Personnel.
    $check = mysqli_prepare(
        $connect,
        "SELECT user_id FROM `user`
         WHERE user_id = ? AND role = 'Personnel'"
    );

    mysqli_stmt_bind_param($check, "i", $user_id);
    mysqli_stmt_execute($check);
    $check_result = mysqli_stmt_get_result($check);
    $personnel_exists = mysqli_num_rows($check_result) > 0;
    mysqli_stmt_close($check);

    if (!$personnel_exists) {
        personnelRedirect("Personnel account not found.", "error");
    }

    // Check for a duplicate username.
    $check = mysqli_prepare(
        $connect,
        "SELECT user_id FROM `user`
         WHERE username = ? AND user_id != ?"
    );

    mysqli_stmt_bind_param($check, "si", $username, $user_id);
    mysqli_stmt_execute($check);
    $duplicate_result = mysqli_stmt_get_result($check);
    $duplicate = mysqli_num_rows($duplicate_result) > 0;
    mysqli_stmt_close($check);

    if ($duplicate) {
        personnelRedirect(
            "That username is already being used. Please choose another.",
            "error"
        );
    }

    $update = mysqli_prepare(
        $connect,
        "UPDATE `user`
         SET name = ?, address = ?, username = ?
         WHERE user_id = ? AND role = 'Personnel'"
    );

    mysqli_stmt_bind_param(
        $update,
        "sssi",
        $name,
        $address,
        $username,
        $user_id
    );

    $success = mysqli_stmt_execute($update);
    mysqli_stmt_close($update);

    if ($success) {
        personnelRedirect("Personnel information updated successfully.");
    }

    personnelRedirect("Unable to update the Personnel account.", "error");
}

// Reset Personnel password using a secure hash.
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["reset_personnel_password"])
) {
    $user_id = filter_var($_POST["user_id"] ?? "", FILTER_VALIDATE_INT);
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (!$user_id) {
        personnelRedirect("Invalid Personnel account.", "error");
    }

    if (strlen($new_password) < 6) {
        personnelRedirect(
            "The password must contain at least 6 characters.",
            "error"
        );
    }

    if ($new_password !== $confirm_password) {
        personnelRedirect("The passwords do not match.", "error");
    }

    // Only reset an account with the Personnel role.
    $check = mysqli_prepare(
        $connect,
        "SELECT user_id FROM `user`
         WHERE user_id = ? AND role = 'Personnel'"
    );

    mysqli_stmt_bind_param($check, "i", $user_id);
    mysqli_stmt_execute($check);
    $check_result = mysqli_stmt_get_result($check);
    $personnel_exists = mysqli_num_rows($check_result) > 0;
    mysqli_stmt_close($check);

    if (!$personnel_exists) {
        personnelRedirect("Personnel account not found.", "error");
    }

    $plain_password = $new_password;

    $update = mysqli_prepare(
        $connect,
        "UPDATE `user`
         SET password = ?
         WHERE user_id = ? AND role = 'Personnel'"
    );

    mysqli_stmt_bind_param(
    $update,
    "si",
    $plain_password,
    $user_id
    );

    $success = mysqli_stmt_execute($update);
    mysqli_stmt_close($update);

    if ($success) {
        personnelRedirect("Personnel password reset successfully.");
    }

    personnelRedirect("Unable to reset the Personnel password.", "error");
}

// Retrieve Personnel account(s).
$sql = "SELECT user_id, name, address, username, role
        FROM `user`
        WHERE role = 'Personnel'
        ORDER BY user_id ASC";

$result = mysqli_query($connect, $sql);

if (!$result) {
    die("Unable to load Personnel accounts: " . mysqli_error($connect));
}

$personnel_count = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Personnel | CTEBS</title>

    <!-- Use the same stylesheet path as Equipment -->
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR: reused from Equipment -->
    <aside class="sidebar">

        <div class="sidebar-brand">
            <img
                src="../img/barangay-logo.png"
                alt="Barangay San Miguel Logo"
            >

            <div>
                <strong>CTEBS</strong>
                <span>Community Tool and Equipment Borrowing System</span>
            </div>
        </div>

        <nav class="sidebar-menu">

            <a href="dashboard.php" class="sidebar-link">
                <span class="menu-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a href="equipment.php" class="sidebar-link">
                <span class="menu-icon">▣</span>
                <span>Equipment</span>
            </a>

            <a href="borrow_requests.php" class="sidebar-link">
                <span class="menu-icon">▤</span>
                <span>Borrow Requests</span>
            </a>

            <a href="transactions.php" class="sidebar-link">
                <span class="menu-icon">↔</span>
                <span>Transactions</span>
            </a>

            <a href="returns.php" class="sidebar-link">
                <span class="menu-icon">↩</span>
                <span>Returns</span>
            </a>

            <a href="penalties.php" class="sidebar-link">
                <span class="menu-icon">₱</span>
                <span>Penalties</span>
            </a>

            <a href="reports.php" class="sidebar-link">
                <span class="menu-icon">▥</span>
                <span>Reports</span>
            </a>

            <a href="personnel.php" class="sidebar-link active">
                <span class="menu-icon">♙</span>
                <span>Personnel</span>
            </a>

            <a href="qr_tagging.php" class="sidebar-link">
                <span class="menu-icon">▦</span>
                <span>QR Tagging</span>
            </a>

        </nav>

        <div class="sidebar-bottom">
            <a href="../logout.php" class="sidebar-link">
                <span class="menu-icon">↪</span>
                <span>Logout</span>
            </a>
        </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOPBAR: reused from Equipment -->
        <header class="topbar">

            <div class="page-title">
                <h1>Personnel</h1>
                <p>Manage the secretary's account.</p>
            </div>

            <div class="admin-profile">
                <div class="profile-avatar">
                    <span>👤</span>
                </div>

                <div class="profile-info">
                    <strong>
                        <?= personnelEscape($_SESSION["name"] ?? "Barangay Captain") ?>
                    </strong>
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

        <!-- PERSONNEL CONTENT -->
        <section class="personnel-content">

            <div class="personnel-page-heading">
                <div>
                    <h2>Personnel Management</h2>
                    <p>
                        View and update the account used by the barangay secretary.
                    </p>
                </div>
            </div>

            <!-- SUCCESS OR ERROR MESSAGE -->
            <?php if ($message !== ""): ?>
                <div class="<?= $message_type === "error" ? "error-message" : "success-message" ?>">
                    <?= personnelEscape($message) ?>
                </div>
            <?php endif; ?>

            <!-- ACCOUNT SUMMARY -->
            <div class="personnel-summary-card">
                <div class="personnel-summary-number">
                    <span>Personnel Accounts</span>
                    <strong><?= $personnel_count ?></strong>
                </div>

                <p>
                    The secretary uses the Personnel role to access the system.
                </p>
            </div>

            <!-- PERSONNEL TABLE -->
            <div class="personnel-table-card">

                <div class="section-header">
                    <div>
                        <h2>Secretary Account Details</h2>
                        <p>Account information and available actions.</p>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($personnel_count > 0): ?>

                                <?php while ($person = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td>
                                            <?= (int) $person["user_id"] ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= personnelEscape($person["name"]) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= personnelEscape($person["address"]) ?>
                                        </td>

                                        <td>
                                            <?= personnelEscape($person["username"]) ?>
                                        </td>

                                        <td>
                                            <span class="personnel-role-badge">
                                                Personnel
                                            </span>
                                        </td>

                                        <td>
                                            <div class="personnel-action-buttons">

                                                <button
                                                    type="button"
                                                    class="personnel-edit-button"
                                                    data-id="<?= (int) $person["user_id"] ?>"
                                                    data-name="<?= personnelEscape($person["name"]) ?>"
                                                    data-address="<?= personnelEscape($person["address"]) ?>"
                                                    data-username="<?= personnelEscape($person["username"]) ?>"
                                                    onclick="openEditModal(this)"
                                                >
                                                    Edit
                                                </button>

                                                <button
                                                    type="button"
                                                    class="personnel-reset-button"
                                                    data-id="<?= (int) $person["user_id"] ?>"
                                                    data-name="<?= personnelEscape($person["name"]) ?>"
                                                    onclick="openResetModal(this)"
                                                >
                                                    Reset Password
                                                </button>

                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="6" class="personnel-empty">
                                        No Personnel account was found.
                                        Check the role value in your User table.
                                    </td>
                                </tr>

                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </section>

    </main>

</div>

<!-- EDIT PERSONNEL MODAL -->
<div class="personnel-modal" id="editPersonnelModal">
    <div class="personnel-modal-box">

        <div class="personnel-modal-header">
            <div>
                <h2>Edit Personnel</h2>
                <p>Update the secretary's account information.</p>
            </div>

            <button
                type="button"
                class="personnel-modal-close"
                onclick="closePersonnelModals()"
                aria-label="Close"
            >&times;</button>
        </div>

        <form method="POST" action="personnel.php">

            <input type="hidden" name="user_id" id="edit_user_id">

            <div class="personnel-field">
                <label for="edit_name">Full Name</label>
                <input type="text" name="name" id="edit_name" required>
            </div>

            <div class="personnel-field">
                <label for="edit_address">Address</label>
                <input type="text" name="address" id="edit_address" required>
            </div>

            <div class="personnel-field">
                <label for="edit_username">Username</label>
                <input type="text" name="username" id="edit_username" required>
            </div>

            <div class="personnel-modal-actions">
                <button
                    type="button"
                    class="personnel-cancel-button"
                    onclick="closePersonnelModals()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    name="edit_personnel"
                    class="personnel-save-button"
                >
                    Save Changes
                </button>
            </div>

        </form>

    </div>
</div>

<!-- RESET PASSWORD MODAL -->
<div class="personnel-modal" id="resetPasswordModal">
    <div class="personnel-modal-box">

        <div class="personnel-modal-header">
            <div>
                <h2>Reset Password</h2>
                <p id="reset_password_description">
                    Set a new password for the secretary.
                </p>
            </div>

            <button
                type="button"
                class="personnel-modal-close"
                onclick="closePersonnelModals()"
                aria-label="Close"
            >&times;</button>
        </div>

        <form method="POST" action="personnel.php">

            <input type="hidden" name="user_id" id="reset_user_id">

            <div class="personnel-field">
                <label for="new_password">New Password</label>
                <input
                    type="password"
                    name="new_password"
                    id="new_password"
                    minlength="6"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="personnel-field">
                <label for="confirm_password">Confirm New Password</label>
                <input
                    type="password"
                    name="confirm_password"
                    id="confirm_password"
                    minlength="6"
                    autocomplete="new-password"
                    required
                >
            </div>

            <p class="personnel-password-note">
                Use at least 6 characters for the new password.
            </p>

            <div class="personnel-modal-actions">
                <button
                    type="button"
                    class="personnel-cancel-button"
                    onclick="closePersonnelModals()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    name="reset_personnel_password"
                    class="personnel-save-button"
                >
                    Reset Password
                </button>
            </div>

        </form>

    </div>
</div>

<script src="../js/script.js"></script>

</body>
</html>