<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Only Personnel can access this page.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "Personnel") {
    header("Location: ../index.php");
    exit();
}

// Add Equipment
if (isset($_POST['add_equipment'])) {

    $equipment_name = trim($_POST['equipment_name'] ?? '');
    $category = trim($_POST['category'] ?? '');

    $quantity = filter_var(
        $_POST['quantity'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $condition = trim($_POST['condition'] ?? '');
    $qr_code = trim($_POST['qr_code'] ?? '');

    $allowedConditions = ['Good', 'Fair', 'Damaged'];

    if (
        $equipment_name === '' ||
        $category === '' ||
        $quantity === false ||
        !in_array($condition, $allowedConditions, true)
    ) {
        $_SESSION['equipment_error'] =
            "Please complete all required fields correctly.";

    } else {

        $sql = "INSERT INTO equipment
                    (
                        equipment_name,
                        category,
                        quantity,
                        availability,
                        status,
                        `condition`,
                        qr_code
                    )
                VALUES (?, ?, ?, 'Available', 'Active', ?, ?)";

        $stmt = mysqli_prepare($connect, $sql);

        if (!$stmt) {

            error_log(
                "Equipment insert preparation failed: " .
                mysqli_error($connect)
            );

            $_SESSION['equipment_error'] =
                "Unable to add equipment. Please try again.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "ssiss",
                $equipment_name,
                $category,
                $quantity,
                $condition,
                $qr_code
            );

            if (mysqli_stmt_execute($stmt)) {

                $_SESSION['equipment_success'] =
                    "Equipment added successfully.";

            } else {

                error_log(
                    "Equipment insert failed: " .
                    mysqli_stmt_error($stmt)
                );

                $_SESSION['equipment_error'] =
                    "Failed to add equipment. Please try again.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    header("Location: equipment.php");
    exit();
}


// Get Equipment and Calculate Quantities
$sql = "SELECT
            e.*,

            COALESCE(
                (
                    SELECT SUM(br.borrow_quantity)
                    FROM borrower_request br
                    WHERE br.equipment_id = e.equipment_id
                      AND br.status = 'Borrowed'
                ),
                0
            ) AS borrowed_quantity,

            GREATEST(
                e.quantity - COALESCE(
                    (
                        SELECT SUM(br.borrow_quantity)
                        FROM borrower_request br
                        WHERE br.equipment_id = e.equipment_id
                          AND br.status = 'Borrowed'
                    ),
                    0
                ),
                0
            ) AS available_quantity

        FROM equipment e
        ORDER BY e.equipment_id DESC";

$query = mysqli_query($connect, $sql);

if (!$query) {
    error_log(
        "Equipment query failed: " . mysqli_error($connect)
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Equipment | CTEBS Personnel</title>

    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR -->

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

            <a href="equipment.php" class="sidebar-link active">
                <span class="menu-icon">▣</span>
                <span>Equipment</span>
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

        <!-- TOPBAR -->

        <header class="topbar">

            <div class="page-title">

                <h2>Equipment</h2>

                <span>
                    View and manage barangay tools and equipment
                </span>

            </div>

            <div class="admin-profile">

                <div class="profile-avatar">
                    <span>👤</span>
                </div>

                <div class="profile-info">

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION['name'] ?? 'Personnel'
                        );
                        ?>
                    </strong>

                    <span>Personnel</span>

                </div>

            </div>

        </header>


        <!-- EQUIPMENT CONTENT -->

        <section class="equipment-content">

            <!-- SUCCESS MESSAGE -->

            <?php if (isset($_SESSION['equipment_success'])): ?>

                <div class="success-message">

                    <?php
                    echo htmlspecialchars(
                        $_SESSION['equipment_success']
                    );

                    unset($_SESSION['equipment_success']);
                    ?>

                </div>

            <?php endif; ?>


            <!-- ERROR MESSAGE -->

            <?php if (isset($_SESSION['equipment_error'])): ?>

                <div class="error-message">

                    <?php
                    echo htmlspecialchars(
                        $_SESSION['equipment_error']
                    );

                    unset($_SESSION['equipment_error']);
                    ?>

                </div>

            <?php endif; ?>


            <?php if (!$query): ?>

                <div class="error-message">
                    Unable to load equipment. Please try again later.
                </div>

            <?php endif; ?>


            <!-- PAGE HEADER -->

            <div class="equipment-header">

                <div>

                    <h1>Equipment Management</h1>

                    <p>
                        View total stock, borrowed units, and available equipment.
                    </p>

                </div>

            </div>


            <!-- ADD EQUIPMENT -->

            <div class="equipment-form-card">

                <div class="section-header">

                    <div>

                        <h3>Add Equipment</h3>

                        <p>
                            Register a new tool or equipment.
                        </p>

                    </div>

                </div>

                <form method="POST" action="equipment.php">

                    <div class="form-row">

                        <div class="equipment-form-group">

                            <label for="equipment_name">
                                Equipment Name
                            </label>

                            <input
                                type="text"
                                id="equipment_name"
                                name="equipment_name"
                                placeholder="e.g. Grass Cutter"
                                required
                            >

                        </div>

                        <div class="equipment-form-group">

                            <label for="category">
                                Category
                            </label>

                            <input
                                type="text"
                                id="category"
                                name="category"
                                placeholder="e.g. Gardening"
                                required
                            >

                        </div>

                        <div class="equipment-form-group">

                            <label for="quantity">
                                Quantity
                            </label>

                            <input
                                type="number"
                                id="quantity"
                                name="quantity"
                                min="1"
                                step="1"
                                placeholder="0"
                                required
                            >

                        </div>

                    </div>


                    <div class="form-row">

                        <div class="equipment-form-group">

                            <label for="condition">
                                Condition
                            </label>

                            <select
                                id="condition"
                                name="condition"
                                required
                            >
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Damaged">Damaged</option>
                            </select>

                        </div>

                        <div class="equipment-form-group">

                            <label for="qr_code">
                                QR Code
                            </label>

                            <input
                                type="text"
                                id="qr_code"
                                name="qr_code"
                                placeholder="Enter QR code"
                            >

                        </div>

                        <div class="equipment-form-group form-button">

                            <button
                                type="submit"
                                name="add_equipment"
                            >
                                Add Equipment
                            </button>

                        </div>

                    </div>

                </form>

            </div>


            <!-- EQUIPMENT TABLE -->

            <div class="equipment-table-card">

                <div class="section-header">

                    <div>

                        <h3>Equipment List</h3>

                        <p>
                            Total stock and availability based on borrowing records.
                        </p>

                    </div>

                </div>

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>
                                <th>ID</th>
                                <th>Equipment Name</th>
                                <th>Category</th>
                                <th>Total Stock</th>
                                <th>Borrowed</th>
                                <th>Available Stock</th>
                                <th>Availability</th>
                                <th>Condition</th>
                                <th>QR Code</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if ($query && mysqli_num_rows($query) > 0): ?>

                                <?php while ($row = mysqli_fetch_assoc($query)): ?>

                                    <?php
                                    $availableQuantity =
                                        (int) $row['available_quantity'];

                                    $availabilityLabel =
                                        $availableQuantity > 0
                                            ? 'Available'
                                            : 'Borrowed';

                                    if ($row['status'] !== 'Active') {
                                        $availabilityLabel = 'Unavailable';
                                    }
                                    ?>

                                    <tr>

                                        <td>
                                            <?php
                                            echo (int) $row['equipment_id'];
                                            ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $row['equipment_name']
                                                );
                                                ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['category']
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo (int) $row['quantity'];
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo (int) $row['borrowed_quantity'];
                                            ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?php
                                                echo $availableQuantity;
                                                ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <span class="status-badge <?php
                                                echo strtolower($availabilityLabel);
                                            ?>">
                                                <?php
                                                echo htmlspecialchars(
                                                    $availabilityLabel
                                                );
                                                ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="status-badge <?php
                                                echo strtolower($row['condition']);
                                            ?>">
                                                <?php
                                                echo htmlspecialchars(
                                                    $row['condition']
                                                );
                                                ?>
                                            </span>
                                        </td>

                                        <td>

                                            <?php if (!empty($row['qr_code'])): ?>

                                                <?php
                                                echo htmlspecialchars(
                                                    $row['qr_code']
                                                );
                                                ?>

                                            <?php else: ?>

                                                <span class="no-qr">
                                                    Not assigned
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="9" class="empty-state">

                                        <?php if ($query): ?>

                                            No equipment has been registered yet.

                                        <?php else: ?>

                                            Equipment could not be loaded.

                                        <?php endif; ?>

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

<script src="../js/script.js"></script>

</body>
</html>