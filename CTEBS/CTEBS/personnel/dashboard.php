<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Only Personnel can access this dashboard.
if ($_SESSION['role'] !== "Personnel") {
    header("Location: ../index.php");
    exit();
}


// Available units
$available = 0;

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

if ($query) {
    $available = (int) mysqli_fetch_assoc($query)['total'];
} else {
    error_log("Personnel available count failed: " . mysqli_error($connect));
}


// Borrowed units
$borrowed = 0;

$sql = "SELECT COALESCE(SUM(borrow_quantity), 0) AS total
        FROM borrower_request
        WHERE status = 'Borrowed'";

$query = mysqli_query($connect, $sql);

if ($query) {
    $borrowed = (int) mysqli_fetch_assoc($query)['total'];
} else {
    error_log("Personnel borrowed count failed: " . mysqli_error($connect));
}


// Overdue units
$overdue = 0;

$sql = "SELECT COALESCE(SUM(borrow_quantity), 0) AS total
        FROM borrower_request
        WHERE status = 'Borrowed'
          AND return_date < CURDATE()";

$query = mysqli_query($connect, $sql);

if ($query) {
    $overdue = (int) mysqli_fetch_assoc($query)['total'];
} else {
    error_log("Personnel overdue count failed: " . mysqli_error($connect));
}


// Recent transactions
$sql = "SELECT
            br.request_id,
            br.borrower_name,
            br.borrow_date,
            br.return_date,
            br.status,
            br.borrow_quantity,
            e.equipment_name
        FROM borrower_request br
        LEFT JOIN equipment e
            ON br.equipment_id = e.equipment_id
        WHERE br.status IN ('Approved', 'Borrowed')
        ORDER BY br.request_id DESC
        LIMIT 5";

$transactions = mysqli_query($connect, $sql);

if (!$transactions) {
    error_log("Personnel transactions query failed: " . mysqli_error($connect));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>CTEBS - Personnel Dashboard</title>

    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="sidebar-brand">
            <img src="../img/barangay-logo.png"
                 alt="Barangay San Miguel Logo">

            <div>
                <strong>CTEBS</strong>
                <span>Community Tool and Equipment Borrowing System</span>
            </div>
        </div>

        <nav class="sidebar-menu">

            <a href="dashboard.php" class="active">
                <span class="menu-icon">⌂</span>
                Dashboard
            </a>

            <a href="equipment.php">
                <span class="menu-icon">▣</span>
                Equipment
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
                <h1>Personnel Dashboard</h1>

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
                        <?php
                        echo htmlspecialchars($_SESSION['name']);
                        ?>
                    </strong>

                    <span>Personnel</span>
                </div>

            </div>

        </header>


        <!-- DASHBOARD CONTENT -->
        <section class="dashboard-content">

            <!-- SUMMARY CARDS -->
            <div class="dashboard-cards personnel-dashboard-cards">

                <div class="dashboard-card">

                    <div class="card-icon available">
                        ✓
                    </div>

                    <div class="card-details">
                        <span>Available Units</span>
                        <strong>
                            <?php echo $available; ?>
                        </strong>
                    </div>

                </div>


                <div class="dashboard-card">

                    <div class="card-icon borrowed">
                        ↗
                    </div>

                    <div class="card-details">
                        <span>Borrowed Units</span>
                        <strong>
                            <?php echo $borrowed; ?>
                        </strong>
                    </div>

                </div>


                <div class="dashboard-card">

                    <div class="card-icon overdue">
                        !
                    </div>

                    <div class="card-details">
                        <span>Overdue Units</span>
                        <strong>
                            <?php echo $overdue; ?>
                        </strong>
                    </div>

                </div>

            </div>


            <!-- RECENT TRANSACTIONS -->
            <div class="dashboard-section">

                <div class="section-header">

                    <div>
                        <h2>Recent Transactions</h2>

                        <p>
                            Recently approved and released borrowing requests.
                        </p>
                    </div>

                    <a href="transactions.php"
                       class="view-all">
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

                        <?php if ($transactions && mysqli_num_rows($transactions) > 0): ?>

                            <?php while ($row = mysqli_fetch_assoc($transactions)): ?>

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

                                <tr>

                                    <td>
                                        #<?php echo (int) $row['request_id']; ?>
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
                                        <?php
                                        echo (int) $row['borrow_quantity'];
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo !empty($row['borrow_date'])
                                            ? date(
                                                'M d, Y',
                                                strtotime($row['borrow_date'])
                                            )
                                            : '-';
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo !empty($row['return_date'])
                                            ? date(
                                                'M d, Y',
                                                strtotime($row['return_date'])
                                            )
                                            : '-';
                                        ?>
                                    </td>

                                    <td>
                                        <span class="status-badge <?php
                                            echo strtolower($displayStatus);
                                        ?>">
                                            <?php
                                            echo htmlspecialchars($displayStatus);
                                            ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="empty-table">
                                    No recent transactions found.
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