<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Captain") {
    header("Location: ../index.php");
    exit();
}

function qrRedirect($message, $type = "success")
{
    $_SESSION["qr_message"] = $message;
    $_SESSION["qr_message_type"] = $type;

    header("Location: qr_tagging.php");
    exit();
}

function qrEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$message = $_SESSION["qr_message"] ?? "";
$message_type = $_SESSION["qr_message_type"] ?? "success";

unset($_SESSION["qr_message"], $_SESSION["qr_message_type"]);

// Generate and save a unique QR value for one equipment record.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["generate_qr"])) {
    $equipment_id = filter_var(
        $_POST["equipment_id"] ?? "",
        FILTER_VALIDATE_INT
    );

    if (!$equipment_id || $equipment_id < 1) {
        qrRedirect("Invalid equipment selected.", "error");
    }

    $check = mysqli_prepare(
        $connect,
        "SELECT equipment_id, equipment_name, qr_code
         FROM equipment
         WHERE equipment_id = ?"
    );

    mysqli_stmt_bind_param($check, "i", $equipment_id);
    mysqli_stmt_execute($check);
    $equipment_result = mysqli_stmt_get_result($check);
    $equipment = mysqli_fetch_assoc($equipment_result);
    mysqli_stmt_close($check);

    if (!$equipment) {
        qrRedirect("Equipment record not found.", "error");
    }

    if (trim((string) $equipment["qr_code"]) !== "") {
        qrRedirect(
            "This equipment already has a QR code. Use View QR to display it.",
            "error"
        );
    }

    // This value is encoded in the QR image and saved in the existing column.
    $qr_value = "CTEBS-EQUIPMENT-" . $equipment_id;

    $update = mysqli_prepare(
        $connect,
        "UPDATE equipment
         SET qr_code = ?
         WHERE equipment_id = ?
           AND (qr_code IS NULL OR TRIM(qr_code) = '')"
    );

    mysqli_stmt_bind_param($update, "si", $qr_value, $equipment_id);
    $success = mysqli_stmt_execute($update);
    $updated_rows = mysqli_stmt_affected_rows($update);
    mysqli_stmt_close($update);

    if ($success && $updated_rows > 0) {
        qrRedirect(
            "QR code created for " . $equipment["equipment_name"] . "."
        );
    }

    qrRedirect(
        "The QR code could not be saved. Refresh the page and try again.",
        "error"
    );
}

// Search equipment by name, category, or ID.
$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $search_like = "%" . $search . "%";

    $stmt = mysqli_prepare(
        $connect,
        "SELECT equipment_id, equipment_name, category, quantity,
                availability, `condition`, status, qr_code
         FROM equipment
         WHERE CAST(equipment_id AS CHAR) LIKE ?
            OR equipment_name LIKE ?
            OR category LIKE ?
         ORDER BY equipment_id DESC"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $search_like,
        $search_like,
        $search_like
    );

    mysqli_stmt_execute($stmt);
    $equipment_result = mysqli_stmt_get_result($stmt);
} else {
    $stmt = mysqli_prepare(
        $connect,
        "SELECT equipment_id, equipment_name, category, quantity,
                availability, `condition`, status, qr_code
         FROM equipment
         ORDER BY equipment_id DESC"
    );

    mysqli_stmt_execute($stmt);
    $equipment_result = mysqli_stmt_get_result($stmt);
}

$equipment_rows = [];
$generated_count = 0;
$not_generated_count = 0;

while ($row = mysqli_fetch_assoc($equipment_result)) {
    $equipment_rows[] = $row;

    if (trim((string) $row["qr_code"]) !== "") {
        $generated_count++;
    } else {
        $not_generated_count++;
    }
}

mysqli_stmt_close($stmt);

// Summary totals across all equipment, not just search results.
$summary_result = mysqli_query(
    $connect,
    "SELECT
        COUNT(*) AS total_equipment,
        SUM(CASE
            WHEN qr_code IS NOT NULL AND TRIM(qr_code) <> '' THEN 1
            ELSE 0
        END) AS tagged_equipment
     FROM Equipment"
);

$summary = mysqli_fetch_assoc($summary_result);
$total_equipment = (int) ($summary["total_equipment"] ?? 0);
$tagged_equipment = (int) ($summary["tagged_equipment"] ?? 0);
$untagged_equipment = $total_equipment - $tagged_equipment;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>QR Tagging | CTEBS</title>

    <link rel="stylesheet" href="../css/style.css">

    <!-- QR image generation in the browser -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR: consistent with Equipment -->
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

            <a href="personnel.php" class="sidebar-link">
                <span class="menu-icon">♙</span>
                <span>Personnel</span>
            </a>

            <a href="qr_tagging.php" class="sidebar-link active">
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

        <header class="topbar">
            <div class="page-title">
                <h1>QR Tagging</h1>
                <p>Generate and print QR codes for barangay equipment.</p>
            </div>

            <div class="admin-profile">
                <div class="profile-avatar">
                    <span>👤</span>
                </div>

                <div class="profile-info">
                    <strong>
                        <?= qrEscape($_SESSION["name"] ?? "Barangay Captain") ?>
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

        <section class="qr-content">

            <div class="qr-page-heading">
                <div>
                    <h2>Equipment QR Codes</h2>
                    <p>
                        Assign a QR code to each equipment record to make identification easier.
                    </p>
                </div>
            </div>

            <?php if ($message !== ""): ?>
                <div class="qr-message <?= $message_type === "error" ? "error" : "success" ?>">
                    <?= qrEscape($message) ?>
                </div>
            <?php endif; ?>

            <!-- SUMMARY CARDS -->
            <div class="qr-summary-grid">

                <div class="qr-summary-card">
                    <span>Total Equipment</span>
                    <strong><?= $total_equipment ?></strong>
                    <small>Equipment records in the system</small>
                </div>

                <div class="qr-summary-card">
                    <span>QR Codes Generated</span>
                    <strong><?= $tagged_equipment ?></strong>
                    <small>Equipment with saved QR values</small>
                </div>

                <div class="qr-summary-card">
                    <span>Not Yet Tagged</span>
                    <strong><?= $untagged_equipment ?></strong>
                    <small>Equipment still needing a QR code</small>
                </div>

            </div>

            <!-- EQUIPMENT TABLE -->
            <div class="qr-table-card">

                <div class="section-header">
                    <div>
                        <h3>Equipment List</h3>
                        <p>Generate, view, and print equipment QR labels.</p>
                    </div>
                </div>

                <form method="GET" action="qr_tagging.php" class="qr-search-form">
                    <input
                        type="text"
                        name="search"
                        value="<?= qrEscape($search) ?>"
                        placeholder="Search by equipment name, category, or ID..."
                        aria-label="Search equipment"
                    >

                    <button type="submit" class="qr-search-button">
                        Search
                    </button>

                    <?php if ($search !== ""): ?>
                        <a href="qr_tagging.php" class="qr-clear-button">Clear</a>
                    <?php endif; ?>
                </form>

                <div class="qr-table-scroll">
                    <table class="qr-equipment-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Equipment</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>QR Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (count($equipment_rows) > 0): ?>

                                <?php foreach ($equipment_rows as $item): ?>
                                    <?php
                                        $has_qr = trim((string) $item["qr_code"]) !== "";
                                    ?>

                                    <tr>
                                        <td><?= (int) $item["equipment_id"] ?></td>

                                        <td>
                                            <strong>
                                                <?= qrEscape($item["equipment_name"]) ?>
                                            </strong>
                                        </td>

                                        <td><?= qrEscape($item["category"]) ?></td>

                                        <td><?= (int) $item["quantity"] ?></td>

                                        <td>
                                            <?php if ($has_qr): ?>
                                                <span class="qr-status qr-status-generated">
                                                    Generated
                                                </span>
                                            <?php else: ?>
                                                <span class="qr-status qr-status-pending">
                                                    Not Generated
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div class="qr-action-buttons">

                                                <?php if (!$has_qr): ?>
                                                    <form
                                                        method="POST"
                                                        action="qr_tagging.php"
                                                        onsubmit="return confirm('Generate a QR code for this equipment?');"
                                                    >
                                                        <input
                                                            type="hidden"
                                                            name="equipment_id"
                                                            value="<?= (int) $item["equipment_id"] ?>"
                                                        >

                                                        <button
                                                            type="submit"
                                                            name="generate_qr"
                                                            class="qr-generate-button"
                                                        >
                                                            Generate QR
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button
                                                        type="button"
                                                        class="qr-view-button"
                                                        data-id="<?= (int) $item["equipment_id"] ?>"
                                                        data-name="<?= qrEscape($item["equipment_name"]) ?>"
                                                        data-category="<?= qrEscape($item["category"]) ?>"
                                                        data-code="<?= qrEscape($item["qr_code"]) ?>"
                                                        onclick="openQrModal(this)"
                                                    >
                                                        View / Print
                                                    </button>
                                                <?php endif; ?>

                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="qr-empty">
                                        No equipment records found.
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

<!-- QR PREVIEW AND PRINT MODAL -->
<div class="qr-modal" id="qrModal">
    <div class="qr-modal-box">

        <div class="qr-modal-header no-print">
            <div>
                <h2>Equipment QR Label</h2>
                <p>Preview the label before printing.</p>
            </div>

            <button
                type="button"
                class="qr-modal-close"
                onclick="closeQrModal()"
                aria-label="Close"
            >&times;</button>
        </div>

        <div id="qrPrintArea" class="qr-print-area">

            <div class="qr-label-brand">
                <img src="../img/barangay-logo.png" alt="Barangay Logo">
                <div>
                    <strong>CTEBS</strong>
                    <span>Barangay San Miguel, Iligan City</span>
                </div>
            </div>

            <div id="qrImage" class="qr-image"></div>

            <h3 id="qrEquipmentName">Equipment Name</h3>
            <p id="qrEquipmentCategory">Category</p>
            <p class="qr-equipment-id">
                Equipment ID: <strong id="qrEquipmentId"></strong>
            </p>
            <p class="qr-code-text" id="qrCodeText"></p>

        </div>

        <div class="qr-modal-actions no-print">
            <button
                type="button"
                class="qr-cancel-button"
                onclick="closeQrModal()"
            >
                Close
            </button>

            <button
                type="button"
                class="qr-print-button"
                onclick="printQrLabel()"
            >
                Print QR Label
            </button>
        </div>

    </div>
</div>

<script src="../js/script.js"></script>

</body>
</html>
