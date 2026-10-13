<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

// Admin only
if ($_SESSION['role'] != "Captain") {
    header("Location: index.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| Dashboard Counts
|--------------------------------------------------------------------------
*/

// Available units
$sql = "SELECT COALESCE(
            SUM(
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
                )
            ),
            0
        ) AS total
        FROM equipment e
        WHERE e.status = 'Active'";

$query = mysqli_query($connect, $sql);
$available = mysqli_fetch_assoc($query)['total'];


// Borrowed units
$sql = "SELECT COALESCE(
            SUM(borrow_quantity),
            0
        ) AS total
        FROM borrower_request
        WHERE status = 'Borrowed'";

$query = mysqli_query($connect, $sql);
$borrowed = mysqli_fetch_assoc($query)['total'];


// Pending requests (count of requests, not units)
$sql = "SELECT COUNT(*) AS total
        FROM borrower_request
        WHERE status = 'Pending'";

$query = mysqli_query($connect, $sql);
$pending = mysqli_fetch_assoc($query)['total'];


// Damaged equipment records
$sql = "SELECT COUNT(*) AS total
        FROM equipment
        WHERE `condition` = 'Damaged'";

$query = mysqli_query($connect, $sql);
$damaged = mysqli_fetch_assoc($query)['total'];


// Overdue units
$sql = "SELECT COALESCE(
            SUM(borrow_quantity),
            0
        ) AS total
        FROM borrower_request
        WHERE return_date < CURDATE()
          AND status = 'Borrowed'";

$query = mysqli_query($connect, $sql);
$overdue = mysqli_fetch_assoc($query)['total'];


/*
|--------------------------------------------------------------------------
| Recent Borrow Requests
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            br.request_id,
            br.borrower_name,
            br.request_date,
            br.borrow_date,
            br.return_date,
            br.status,
            br.borrow_quantity,
            e.equipment_name
        FROM borrower_request br
        LEFT JOIN equipment e
            ON br.equipment_id = e.equipment_id
        ORDER BY br.request_id DESC
        LIMIT 5";

$requests = mysqli_query($connect, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>CTEBS - Admin Dashboard</title>

    <link rel="stylesheet"
          href="../css/style.css">

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
            <div>
                <strong>CTEBS</strong>
                <span>Community Tool and Equipment Borrowing System</span>
            </div>
            </div>

        </div>


        <nav class="sidebar-menu">

            <a href="dashboard.php"
               class="active">

                <span class="menu-icon">⌂</span>

                Dashboard

            </a>


            <a href="equipment.php">

                <span class="menu-icon">▣</span>

                Equipment

            </a>


            <a href="borrow_requests.php">

                <span class="menu-icon">▤</span>

                Borrow Requests

            </a>


            <a href="transactions.php">

                <span class="menu-icon">↔</span>

                Transactions

            </a>


            <a href="returns.php">

                <span class="menu-icon">↩</span>

                Returns

            </a>


            <a href="penalties.php">

                <span class="menu-icon">₱</span>

                Penalties

            </a>


            <a href="reports.php">

                <span class="menu-icon">▥</span>

                Reports

            </a>


            <a href="personnel.php">

                <span class="menu-icon">♙</span>

                Personnel

            </a>


            <a href="qr_tagging.php">

                <span class="menu-icon">▦</span>

                QR Tagging

            </a>

        </nav>


        <div class="sidebar-bottom">

            <a href="../logout.php">

                <span class="menu-icon">↪</span>

                Logout

            </a>

        </div>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">


        <!-- TOP BAR -->
        <header class="topbar">

            <div>

                <h1>Admin Dashboard</h1>

                <p>
                    Community Tool and Equipment Borrowing System
                </p>

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


        <!-- PAGE CONTENT -->
        <section class="dashboard-content">


            <!-- SUMMARY CARDS -->
            <div class="dashboard-cards">


                <!-- Available -->
                <div class="dashboard-card">

                    <div class="card-icon available">
                        ✓
                    </div>

                    <div class="card-details">

                        <span>
                            Available Equipment
                        </span>

                        <strong>
                            <?php echo $available; ?>
                        </strong>

                    </div>

                </div>


                <!-- Borrowed -->
                <div class="dashboard-card">

                    <div class="card-icon borrowed">
                        ↗
                    </div>

                    <div class="card-details">

                        <span>
                            Borrowed Equipment
                        </span>

                        <strong>
                            <?php echo $borrowed; ?>
                        </strong>

                    </div>

                </div>


                <!-- Pending -->
                <div class="dashboard-card">

                    <div class="card-icon pending">
                        !
                    </div>

                    <div class="card-details">

                        <span>
                            Pending Requests
                        </span>

                        <strong>
                            <?php echo $pending; ?>
                        </strong>

                    </div>

                </div>


                <!-- Overdue -->
                <div class="dashboard-card">

                    <div class="card-icon overdue">
                        !
                    </div>

                    <div class="card-details">

                        <span>
                            Overdue Equipment
                        </span>

                        <strong>
                            <?php echo $overdue; ?>
                        </strong>

                    </div>

                </div>


                <!-- Damaged -->
                <div class="dashboard-card">

                    <div class="card-icon damaged">
                        ×
                    </div>

                    <div class="card-details">

                        <span>
                            Damaged Equipment
                        </span>

                        <strong>
                            <?php echo $damaged; ?>
                        </strong>

                    </div>

                </div>


            </div>


            <!-- RECENT REQUESTS -->
            <div class="dashboard-section">


                <div class="section-header">

                    <div>

                        <h2>
                            Recent Borrow Requests
                        </h2>

                        <p>
                            Latest borrowing requests recorded in the system.
                        </p>

                    </div>


                    <a href="borrow_requests.php"
                       class="view-all">

                        View All

                    </a>

                </div>


                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>Request ID</th>

                                <th>Borrower</th>

                                <th>Equipment</th>
                                
                                <th>Quantity</th>

                                <th>Borrow Date</th>

                                <th>Return Date</th>

                                <th>Status</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (mysqli_num_rows($requests) > 0): ?>

                            <?php while ($row = mysqli_fetch_assoc($requests)): ?>

                                <tr>

                                    <td>
                                        #<?php echo $row['request_id']; ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row['borrower_name'] ?? 'Unknown'
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row['equipment_name'] ?? 'Unknown'
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php echo (int) $row['borrow_quantity']; ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo date(
                                            "M d, Y",
                                            strtotime($row['borrow_date'])
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo date(
                                            "M d, Y",
                                            strtotime($row['return_date'])
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        $isOverdue = (
                                            $row['status'] === 'Borrowed' &&
                                            !empty($row['return_date']) &&
                                            $row['return_date'] < date('Y-m-d')
                                        );

                                        $displayStatus = $isOverdue
                                            ? 'Overdue'
                                            : $row['status'];
                                        ?>

                                        <span class="status-badge <?php echo strtolower($displayStatus); ?>">
                                            <?php echo htmlspecialchars($displayStatus); ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="7"
                                    class="empty-table">

                                    No borrow requests found.

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