<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

if ($_SESSION['role'] != "Captain") {
    header("Location: ../index.php");
    exit();
}

// Release equipment
if (isset($_POST['release_equipment'])) {

    $request_id = (int) ($_POST['request_id'] ?? 0);
    $condition_at_borrow = trim(
        $_POST['condition_at_borrow'] ?? ''
    );

    $allowedConditions = ['Good', 'Fair', 'Damaged'];

    if (!in_array($condition_at_borrow, $allowedConditions, true)) {
        $_SESSION['transaction_error'] =
            "Please select the equipment condition before releasing it.";

        header("Location: transactions.php");
        exit();
    }
    if ($request_id <= 0) {
        $_SESSION['transaction_error'] = "Invalid request.";
        header("Location: transactions.php");
        exit();
    }

    mysqli_begin_transaction($connect);

    try {

        // Get the approved request and its quantity.
        $requestStatement = mysqli_prepare(
            $connect,
            "SELECT equipment_id, borrow_quantity
             FROM borrower_request
             WHERE request_id = ?
               AND status = 'Approved'
             LIMIT 1
             FOR UPDATE"
        );

        if (!$requestStatement) {
            throw new Exception("Could not check the borrowing request.");
        }

        mysqli_stmt_bind_param(
            $requestStatement,
            "i",
            $request_id
        );

        mysqli_stmt_execute($requestStatement);

        $requestResult = mysqli_stmt_get_result(
            $requestStatement
        );

        $request = mysqli_fetch_assoc($requestResult);

        mysqli_stmt_close($requestStatement);

        if (!$request) {
            throw new Exception(
                "This request is not approved or has already been released."
            );
        }

        $equipment_id = (int) $request['equipment_id'];
        $borrow_quantity = (int) $request['borrow_quantity'];

        // Lock the equipment record while checking its stock.
        $equipmentStatement = mysqli_prepare(
            $connect,
            "SELECT quantity
             FROM equipment
             WHERE equipment_id = ?
               AND status = 'Active'
             LIMIT 1
             FOR UPDATE"
        );

        if (!$equipmentStatement) {
            throw new Exception("Could not check equipment stock.");
        }

        mysqli_stmt_bind_param(
            $equipmentStatement,
            "i",
            $equipment_id
        );

        mysqli_stmt_execute($equipmentStatement);

        $equipmentResult = mysqli_stmt_get_result(
            $equipmentStatement
        );

        $equipment = mysqli_fetch_assoc($equipmentResult);

        mysqli_stmt_close($equipmentStatement);

        if (!$equipment) {
            throw new Exception(
                "This equipment does not exist or is inactive."
            );
        }

        $totalQuantity = (int) $equipment['quantity'];

        // Count units from requests that are already borrowed.
        $borrowedStatement = mysqli_prepare(
            $connect,
            "SELECT COALESCE(SUM(borrow_quantity), 0) AS borrowed_quantity
             FROM borrower_request
             WHERE equipment_id = ?
               AND status = 'Borrowed'"
        );

        if (!$borrowedStatement) {
            throw new Exception("Could not calculate borrowed stock.");
        }

        mysqli_stmt_bind_param(
            $borrowedStatement,
            "i",
            $equipment_id
        );

        mysqli_stmt_execute($borrowedStatement);

        $borrowedResult = mysqli_stmt_get_result(
            $borrowedStatement
        );

        $borrowedRow = mysqli_fetch_assoc($borrowedResult);

        mysqli_stmt_close($borrowedStatement);

        $alreadyBorrowed = (int) $borrowedRow['borrowed_quantity'];

        $availableQuantity = $totalQuantity - $alreadyBorrowed;

        // Stop if there are not enough units.
        if (
            $borrow_quantity < 1 ||
            $borrow_quantity > $availableQuantity
        ) {
            throw new Exception(
                "Not enough stock. Only " .
                max(0, $availableQuantity) .
                " unit(s) are available."
            );
        }

        // Change the request to Borrowed.
        $updateRequest = mysqli_prepare(
            $connect,
            "UPDATE borrower_request
            SET status = 'Borrowed',
                condition_at_borrow = ?
            WHERE request_id = ?
            AND status = 'Approved'"
        );

        if (!$updateRequest) {
            throw new Exception("Could not release the request.");
        }

        mysqli_stmt_bind_param(
            $updateRequest,
            "si",
            $condition_at_borrow,
            $request_id
        );

        mysqli_stmt_execute($updateRequest);

        if (mysqli_stmt_affected_rows($updateRequest) !== 1) {
            mysqli_stmt_close($updateRequest);
            throw new Exception(
                "The request could not be released. Please try again."
            );
        }

        mysqli_stmt_close($updateRequest);

        // Update the availability label based on remaining stock.
        $remainingQuantity =
            $availableQuantity - $borrow_quantity;

        $availability = ($remainingQuantity > 0)
            ? 'Available'
            : 'Borrowed';

        $updateEquipment = mysqli_prepare(
            $connect,
            "UPDATE equipment
             SET availability = ?
             WHERE equipment_id = ?"
        );

        if (!$updateEquipment) {
            throw new Exception("Could not update equipment availability.");
        }

        mysqli_stmt_bind_param(
            $updateEquipment,
            "si",
            $availability,
            $equipment_id
        );

        mysqli_stmt_execute($updateEquipment);

        if (mysqli_stmt_affected_rows($updateEquipment) < 0) {
            mysqli_stmt_close($updateEquipment);
            throw new Exception("Could not update equipment availability.");
        }

        mysqli_stmt_close($updateEquipment);

        // Save all changes together.
        mysqli_commit($connect);

        $_SESSION['transaction_success'] =
            "Equipment released successfully.";

    } catch (Exception $error) {

        mysqli_rollback($connect);

        $_SESSION['transaction_error'] = $error->getMessage();
    }

    header("Location: transactions.php");
    exit();
}


/*
 * Get transactions
 *
 * Approved and Borrowed requests are shown here.
 */
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
            e.equipment_id,
            e.equipment_name
        FROM borrower_request br
        LEFT JOIN equipment e
            ON br.equipment_id = e.equipment_id
        WHERE br.status IN ('Approved', 'Borrowed')
        ORDER BY br.request_id DESC";

$query = mysqli_query($connect, $sql);

if (!$query) {
    die("Database error: " . mysqli_error($connect));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Transactions - CTEBS</title>

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

            <a href="dashboard.php">
                <span class="menu-icon">⌂</span>
                Dashboard
            </a>

            <a href="equipment.php">
                <span class="menu-icon">▤</span>
                Equipment
            </a>

            <a href="borrow_requests.php">
                <span class="menu-icon">▧</span>
                Borrow Requests
            </a>

            <a href="transactions.php" class="active">
                <span class="menu-icon">⇄</span>
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

            <a href="../logout.php" class="logout-link">
                <span class="menu-icon">↪</span>
                Logout
            </a>

        </div>

    </aside>


    <!-- MAIN CONTENT -->

    <main class="main-content">

        <!-- TOPBAR -->

        <header class="topbar">

            <div>

                <h1>Transactions</h1>

                <p>
                    Manage approved and active equipment borrowings.
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


        <!-- CONTENT -->

        <section class="transaction-content">
            <?php if (isset($_SESSION['transaction_success'])): ?>
                <div class="success-message">
                <?php
                    echo htmlspecialchars($_SESSION['transaction_success']);
                    unset($_SESSION['transaction_success']);
            ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['transaction_error'])): ?>
                <div class="error-message">
                    <?php
                    echo htmlspecialchars($_SESSION['transaction_error']);
                    unset($_SESSION['transaction_error']);
                ?>
                </div>
            <?php endif; ?>
            <div class="transaction-header">

                <div>

                    <h2>Borrowing Transactions</h2>

                    <p>
                        Approved requests ready for equipment release
                        and currently borrowed equipment.
                    </p>

                </div>

            </div>


            <div class="transaction-card">

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>Request ID</th>

                                <th>Borrower</th>

                                <th>Purpose</th>

                                <th>Equipment</th>

                                <th>Quantity</th>

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
                                        #<?php echo $row['request_id']; ?>
                                    </td>

                                    <td>

                                        <div class="borrower-info">

                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $row['borrower_name']
                                                );
                                                ?>
                                            </strong>

                                            <span>
                                                <?php
                                                echo htmlspecialchars(
                                                    $row['borrower_contact']
                                                );
                                                ?>
                                            </span>

                                        </div>

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

                                        <?php if ($row['status'] == 'Approved'): ?>

                                            <span class="status-badge approved">
                                                Approved
                                            </span>

                                        <?php elseif ($row['status'] == 'Borrowed'): ?>

                                            <span class="status-badge borrowed">
                                                Borrowed
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php if ($row['status'] == 'Approved'): ?>

                                            <form method="POST" action="transactions.php" 
                                            class="release-equipment-form">

                                                <input
                                                    type="hidden"
                                                    name="request_id"
                                                    value="<?php echo (int) $row['request_id']; ?>"
                                                >

                                                <select
                                                    name="condition_at_borrow"
                                                    required
                                                    aria-label="Equipment condition before borrowing"
                                                >
                                                    <option value="">Select condition</option>
                                                    <option value="Good">Good</option>
                                                    <option value="Fair">Fair</option>
                                                    <option value="Damaged">Damaged</option>
                                                </select>

                                                <button
                                                    type="submit"
                                                    name="release_equipment"
                                                    class="release-button"
                                                >
                                                    Release Equipment
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <span class="transaction-active">
                                                Currently Borrowed
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="9" class="empty-state">

                                    No active transactions found.

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