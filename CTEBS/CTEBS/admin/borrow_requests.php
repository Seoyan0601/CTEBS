<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

// Admin only
if ($_SESSION['role'] != "Captain") {
    header("Location: ../index.php");
    exit();
}


// APPROVE REQUEST
if (isset($_POST['approve_request'])) {

    $request_id = intval($_POST['request_id']);

    $sql = "UPDATE `borrower_request`
            SET status = 'Approved'
            WHERE request_id = $request_id
            AND status = 'Pending'";

    if (mysqli_query($connect, $sql)) {

        $_SESSION['request_success'] = "Borrow request approved successfully.";

    } else {

        $_SESSION['request_error'] =
            "Failed to approve request: " . mysqli_error($connect);
    }

    header("Location: borrow_requests.php");
    exit();
}


// REJECT REQUEST
if (isset($_POST['reject_request'])) {

    $request_id = intval($_POST['request_id']);

    $sql = "UPDATE `borrower_request`
            SET status = 'Rejected'
            WHERE request_id = $request_id
            AND status = 'Pending'";

    if (mysqli_query($connect, $sql)) {

        $_SESSION['request_success'] = "Borrow request rejected.";

    } else {

        $_SESSION['request_error'] =
            "Failed to reject request: " . mysqli_error($connect);
    }

    header("Location: borrow_requests.php");
    exit();
}


// GET BORROW REQUESTS

$sql = "SELECT
            br.request_id,
            br.borrower_name,
            br.borrower_address,
            br.borrower_contact,
            br.purpose,
            br.request_date,
            br.borrow_date,
            br.return_date,
            br.status,
            br.borrow_quantity,
            e.equipment_name
        FROM borrower_request br
        LEFT JOIN equipment e
            ON br.equipment_id = e.equipment_id
        ORDER BY br.request_id DESC";

$query = mysqli_query($connect, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Borrow Requests | CTEBS</title>

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

                <span>
                    Community Tool and Equipment Borrowing System
                </span>

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


            <a href="borrow_requests.php" class="sidebar-link active">

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


        <!-- LOGOUT -->

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

                <h2>Borrow Requests</h2>

                <span>
                    Review and manage equipment borrowing requests
                </span>

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

        <section class="borrow-request-content">


            <?php if (isset($_SESSION['request_success'])): ?>

                <div class="success-message">

                    <?php

                    echo htmlspecialchars(
                        $_SESSION['request_success']
                    );

                    unset($_SESSION['request_success']);

                    ?>

                </div>

            <?php endif; ?>


            <?php if (isset($_SESSION['request_error'])): ?>

                <div class="error-message">

                    <?php

                    echo htmlspecialchars(
                        $_SESSION['request_error']
                    );

                    unset($_SESSION['request_error']);

                    ?>

                </div>

            <?php endif; ?>



            <!-- PAGE INTRO -->

            <div class="borrow-request-header">

                <h1>Borrow Request Management</h1>

                <p>
                    Review submitted requests and decide whether to approve
                    or reject them.
                </p>

            </div>



            <!-- REQUEST TABLE -->

            <div class="borrow-request-card">

                <div class="section-header">

                    <div>

                        <h3>Borrow Requests</h3>

                        <p>
                            List of equipment borrowing requests.
                        </p>

                    </div>

                </div>


                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>Request ID</th>

                                <th>Borrower</th>

                                <th>Purpose</th>

                                <th>Equipment</th>

                                <th>Quantity</th>

                                <th>Request Date</th>

                                <th>Borrow Date</th>

                                <th>Return Date</th>

                                <th>Status</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (mysqli_num_rows($query) > 0): ?>

                            <?php while ($row = mysqli_fetch_assoc($query)): ?>

                                <tr>


                                    <td>

                                        #<?php
                                        echo $row['request_id'];
                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $row['borrower_name']
                                            ?? 'Unknown'
                                        );

                                        ?>

                                    </td>
                                    <td class="purpose-cell">
                                        <?php if (!empty($row['purpose'])): ?>
                                            <?php echo nl2br(htmlspecialchars($row['purpose'])); ?>
                                        <?php else: ?>
                                            <span class="no-purpose">Not provided</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $row['equipment_name']
                                            ?? 'Unknown'
                                        );

                                        ?>

                                    </td>
                                    
                                    <td>
                                        <?php echo (int) $row['borrow_quantity']; ?>
                                    </td>

                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $row['request_date']
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $row['borrow_date']
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $row['return_date']
                                        );

                                        ?>

                                    </td>

                                    <td>

                                        <span class="status-badge
                                            <?php

                                            echo strtolower(
                                                $row['status']
                                            );

                                            ?>"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $row['status']
                                            );

                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php if ($row['status'] == 'Pending'): ?>

                                            <div class="request-actions">


                                                <form
                                                    method="POST"
                                                    action="borrow_requests.php"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="request_id"
                                                        value="<?php
                                                        echo $row['request_id'];
                                                        ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        name="approve_request"
                                                        class="approve-button"
                                                    >
                                                        Approve
                                                    </button>

                                                </form>


                                                <form
                                                    method="POST"
                                                    action="borrow_requests.php"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="request_id"
                                                        value="<?php
                                                        echo $row['request_id'];
                                                        ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        name="reject_request"
                                                        class="reject-button"
                                                    >
                                                        Reject
                                                    </button>

                                                </form>

                                            </div>

                                        <?php else: ?>

                                            <span class="no-action">
                                                No action
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="10"
                                    class="empty-state"
                                >

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