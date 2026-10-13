<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

// Admin only
if ($_SESSION['role'] != "Captain") {
    header("Location: ../index.php");
    exit();
}


// Add Equipment
if (isset($_POST['add_equipment'])) {

    $equipment_name = mysqli_real_escape_string(
        $connect,
        trim($_POST['equipment_name'])
    );

    $category = mysqli_real_escape_string(
        $connect,
        trim($_POST['category'])
    );

    $quantity = intval($_POST['quantity']);

    $condition = mysqli_real_escape_string(
        $connect,
        trim($_POST['condition'])
    );

    $qr_code = mysqli_real_escape_string(
        $connect,
        trim($_POST['qr_code'])
    );

    if ($equipment_name == "" || $category == "" || $quantity <= 0) {

        $_SESSION['equipment_error'] = "Please complete all required fields.";

    } else {

        $availability = "Available";
        $status = "Active";

        $sql = "INSERT INTO Equipment
                (
                    equipment_name,
                    category,
                    quantity,
                    availability,
                    status,
                    `condition`,
                    qr_code
                )
                VALUES
                (
                    '$equipment_name',
                    '$category',
                    '$quantity',
                    '$availability',
                    '$status',
                    '$condition',
                    '$qr_code'
                )";

        if (mysqli_query($connect, $sql)) {

            $_SESSION['equipment_success'] = "Equipment added successfully.";

        } else {

            $_SESSION['equipment_error'] =
                "Failed to add equipment: " . mysqli_error($connect);
        }
    }

    header("Location: equipment.php");
    exit();
}


// Get Equipment
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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Equipment | CTEBS</title>

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


        <!-- TOPBAR -->

        <header class="topbar">

            <div class="page-title">

                <h2>Equipment</h2>

                <span>Manage barangay tools and equipment</span>

            </div>


            <div class="admin-profile">

                <div class="profile-avatar">
                    <span>👤</span>
                </div>

                <div class="profile-info">

                    <strong>
                        <?php echo htmlspecialchars($_SESSION['name']); ?>
                    </strong>

                    <span>
                        Barangay Captain
                    </span>

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



        <!-- EQUIPMENT CONTENT -->

        <section class="equipment-content">


            <?php if (isset($_SESSION['equipment_success'])): ?>

                <div class="success-message">

                    <?php
                    echo htmlspecialchars($_SESSION['equipment_success']);
                    unset($_SESSION['equipment_success']);
                    ?>

                </div>

            <?php endif; ?>


            <?php if (isset($_SESSION['equipment_error'])): ?>

                <div class="error-message">

                    <?php
                    echo htmlspecialchars($_SESSION['equipment_error']);
                    unset($_SESSION['equipment_error']);
                    ?>

                </div>

            <?php endif; ?>



            <!-- PAGE HEADER -->

            <div class="equipment-header">

                <div>

                    <h1>Equipment Management</h1>

                    <p>
                        View and manage barangay tools and equipment.
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

                            <label>Equipment Name</label>

                            <input
                                type="text"
                                name="equipment_name"
                                placeholder="e.g. Grass Cutter"
                                required
                            >

                        </div>


                        <div class="equipment-form-group">

                            <label>Category</label>

                            <input
                                type="text"
                                name="category"
                                placeholder="e.g. Gardening"
                                required
                            >

                        </div>


                        <div class="equipment-form-group">

                            <label>Quantity</label>

                            <input
                                type="number"
                                name="quantity"
                                min="1"
                                placeholder="0"
                                required
                            >

                        </div>


                    </div>


                    <div class="form-row">


                        <div class="equipment-form-group">

                            <label>Condition</label>

                            <select name="condition" required>

                                <option value="Good">Good</option>

                                <option value="Fair">Fair</option>

                                <option value="Damaged">Damaged</option>

                            </select>

                        </div>


                        <div class="equipment-form-group">

                            <label>QR Code</label>

                            <input
                                type="text"
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
                            All registered barangay equipment.
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

                        <?php if (mysqli_num_rows($query) > 0): ?>

                            <?php while ($row = mysqli_fetch_assoc($query)): ?>

                                <tr>

                                    <td>
                                        <?php echo $row['equipment_id']; ?>
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
                                        <?php echo (int) $row['quantity']; ?>
                                    </td>

                                    <td>
                                        <?php echo (int) $row['borrowed_quantity']; ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?php echo (int) $row['available_quantity']; ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php
                                        $availability = (
                                            (int) $row['available_quantity'] > 0
                                        ) ? 'Available' : 'Borrowed';
                                        ?>

                                        <span class="status-badge <?php echo strtolower($availability); ?>">
                                            <?php echo htmlspecialchars($availability); ?>
                                        </span>
                                    </td>

                                    <td>

                                        <span class="status-badge
                                            <?php
                                            echo strtolower(
                                                $row['condition']
                                            );
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

                                    No equipment has been registered yet.

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