<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

if ($_SESSION['role'] != "Captain") {
    header("Location: ../index.php");
    exit();
}


// Record equipment return and lost units
if (isset($_POST['record_return'])) {

    $request_id = filter_var(
        $_POST['request_id'] ?? '',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $lost_quantity = filter_var(
        $_POST['lost_quantity'] ?? '',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0]]
    );

    $return_condition = trim(
        $_POST['return_condition'] ?? ''
    );

    $remarks = trim($_POST['remarks'] ?? '');

    $allowedConditions = ['Good', 'Fair', 'Damaged'];

    // Basic validation
    if (
        $request_id === false ||
        $lost_quantity === false ||
        !in_array($return_condition, $allowedConditions, true) ||
        strlen($remarks) > 2000
    ) {
        $_SESSION['return_error'] =
            "Please enter valid return information.";

        header("Location: returns.php");
        exit();
    }

    mysqli_begin_transaction($connect);

    try {

        // Get the borrowing request.
        $requestStatement = mysqli_prepare(
            $connect,
            "SELECT equipment_id, borrow_quantity
             FROM borrower_request
             WHERE request_id = ?
               AND status = 'Borrowed'
             LIMIT 1
             FOR UPDATE"
        );

        if (!$requestStatement) {
            throw new RuntimeException(
                "Could not check the borrowing request."
            );
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
            throw new DomainException(
                "This request is invalid or has already been returned."
            );
        }

        $equipment_id = (int) $request['equipment_id'];
        $borrow_quantity = (int) $request['borrow_quantity'];

        // Do not allow more units to be lost than were borrowed.
        if (
            $borrow_quantity < 1 ||
            $lost_quantity > $borrow_quantity
        ) {
            throw new DomainException(
                "Lost quantity cannot exceed the borrowed quantity."
            );
        }

        // If every unit is lost, record the condition as Lost.
        // Otherwise, the selected condition describes the items returned.
        if ($lost_quantity === $borrow_quantity) {
            $return_condition = 'Lost';
        }


        // Lock the equipment record and get total stock.
        $equipmentStatement = mysqli_prepare(
            $connect,
            "SELECT quantity
             FROM equipment
             WHERE equipment_id = ?
             LIMIT 1
             FOR UPDATE"
        );

        if (!$equipmentStatement) {
            throw new RuntimeException(
                "Could not retrieve equipment information."
            );
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
            throw new DomainException(
                "The equipment record could not be found."
            );
        }

        $totalStock = (int) $equipment['quantity'];
        $newTotalStock = $totalStock - $lost_quantity;

        if ($newTotalStock < 0) {
            throw new DomainException(
                "The lost quantity exceeds the recorded equipment stock."
            );
        }


        // Save return condition and remarks.
        $returnStatement = mysqli_prepare(
            $connect,
            "INSERT INTO return_record
                (
                    request_id,
                    return_date,
                    return_condition,
                    remarks
                )
             VALUES (?, CURDATE(), ?, ?)"
        );

        if (!$returnStatement) {
            throw new RuntimeException(
                "Could not prepare the return record."
            );
        }

        mysqli_stmt_bind_param(
            $returnStatement,
            "iss",
            $request_id,
            $return_condition,
            $remarks
        );

        if (!mysqli_stmt_execute($returnStatement)) {
            mysqli_stmt_close($returnStatement);

            throw new RuntimeException(
                "Could not save the return record."
            );
        }

        mysqli_stmt_close($returnStatement);


        // Close the request as Returned or Lost.
        $newStatus = (
            $lost_quantity === $borrow_quantity
        ) ? 'Lost' : 'Returned';

        $updateRequest = mysqli_prepare(
            $connect,
            "UPDATE borrower_request
             SET status = ?, lost_quantity = ?
             WHERE request_id = ?
               AND status = 'Borrowed'"
        );

        if (!$updateRequest) {
            throw new RuntimeException(
                "Could not prepare the request update."
            );
        }

        mysqli_stmt_bind_param(
            $updateRequest,
            "sii",
            $newStatus,
            $lost_quantity,
            $request_id
        );

        mysqli_stmt_execute($updateRequest);

        $updatedRows = mysqli_stmt_affected_rows(
            $updateRequest
        );

        mysqli_stmt_close($updateRequest);

        if ($updatedRows !== 1) {
            throw new DomainException(
                "The request could not be updated."
            );
        }


        // Count units still borrowed in other requests.
        $borrowedStatement = mysqli_prepare(
            $connect,
            "SELECT COALESCE(
                        SUM(borrow_quantity),
                        0
                    ) AS borrowed_quantity
             FROM borrower_request
             WHERE equipment_id = ?
               AND status = 'Borrowed'"
        );

        if (!$borrowedStatement) {
            throw new RuntimeException(
                "Could not calculate borrowed stock."
            );
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

        $stillBorrowed = (int) $borrowedRow['borrowed_quantity'];

        $availableQuantity = max(
            0,
            $newTotalStock - $stillBorrowed
        );

        $availability = (
            $availableQuantity > 0
        ) ? 'Available' : 'Borrowed';


        // Reduce total stock by the number of lost units.
        $updateEquipment = mysqli_prepare(
            $connect,
            "UPDATE equipment
             SET quantity = ?, availability = ?
             WHERE equipment_id = ?"
        );

        if (!$updateEquipment) {
            throw new RuntimeException(
                "Could not prepare the equipment update."
            );
        }

        mysqli_stmt_bind_param(
            $updateEquipment,
            "isi",
            $newTotalStock,
            $availability,
            $equipment_id
        );

        if (!mysqli_stmt_execute($updateEquipment)) {
            mysqli_stmt_close($updateEquipment);

            throw new RuntimeException(
                "Could not update equipment stock."
            );
        }

        mysqli_stmt_close($updateEquipment);

        mysqli_commit($connect);

        $_SESSION['return_success'] =
            "Return recorded successfully. " .
            $lost_quantity . " unit(s) marked as lost. " .
            $availableQuantity . " unit(s) are now available.";

    } catch (Throwable $error) {

        mysqli_rollback($connect);

        error_log(
            "Return processing failed: " . $error->getMessage()
        );

        if ($error instanceof DomainException) {
            $_SESSION['return_error'] = $error->getMessage();
        } else {
            $_SESSION['return_error'] =
                "The return could not be recorded. Please try again.";
        }
    }

    header("Location: returns.php");
    exit();
}

/*
 * Get currently borrowed equipment
 */
$sql = "SELECT
            br.request_id,
            br.borrower_name,
            br.borrower_contact,
            br.borrow_date,
            br.return_date,
            br.status,
            br.borrow_quantity,
            e.equipment_id,
            e.equipment_name
        FROM borrower_request br
        LEFT JOIN equipment e
            ON br.equipment_id = e.equipment_id
        WHERE br.status = 'Borrowed'
        ORDER BY br.return_date ASC";

$query = mysqli_query($connect, $sql);

if (!$query) {
    die("Database error: " . mysqli_error($connect));
}

// Search Return History
$historySearch = trim($_GET['history_search'] ?? '');

$historySql = "SELECT
                    rr.return_id,
                    rr.request_id,
                    rr.return_date,
                    rr.return_condition,
                    rr.remarks,
                    br.borrower_name,
                    br.borrow_quantity,
                    br.condition_at_borrow,
                    br.lost_quantity,
                    e.equipment_name
               FROM return_record rr
               LEFT JOIN borrower_request br
                   ON rr.request_id = br.request_id
               LEFT JOIN equipment e
                   ON br.equipment_id = e.equipment_id";

if ($historySearch !== '') {

    $historySql .= " WHERE
                        br.borrower_name LIKE ?
                        OR e.equipment_name LIKE ?
                        OR rr.return_condition LIKE ?
                        OR rr.remarks LIKE ?
                        OR CAST(rr.request_id AS CHAR) LIKE ?
                        OR CAST(rr.return_id AS CHAR) LIKE ?";

    $historySql .= " ORDER BY rr.return_date DESC, rr.return_id DESC";

    $historyStatement = mysqli_prepare($connect, $historySql);

    if (!$historyStatement) {
        die("Return history error: " . mysqli_error($connect));
    }

    $searchName = '%' . $historySearch . '%';
    $searchEquipment = '%' . $historySearch . '%';
    $searchCondition = '%' . $historySearch . '%';
    $searchRemarks = '%' . $historySearch . '%';
    $searchRequestId = '%' . $historySearch . '%';
    $searchReturnId = '%' . $historySearch . '%';

    mysqli_stmt_bind_param(
        $historyStatement,
        "ssssss",
        $searchName,
        $searchEquipment,
        $searchCondition,
        $searchRemarks,
        $searchRequestId,
        $searchReturnId
    );

    mysqli_stmt_execute($historyStatement);
    $historyQuery = mysqli_stmt_get_result($historyStatement);

} else {

    $historySql .= " ORDER BY rr.return_date DESC, rr.return_id DESC";

    $historyQuery = mysqli_query($connect, $historySql);

    if (!$historyQuery) {
        die("Return history error: " . mysqli_error($connect));
    }

    $historyStatement = null;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Returns - CTEBS</title>

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

            <a href="transactions.php">
                <span class="menu-icon">⇄</span>
                Transactions
            </a>

            <a href="returns.php" class="active">
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

                <h1>Returns</h1>

                <p>
                    Record returned equipment and update its condition.
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

        <section class="return-content">


            <?php if (isset($_SESSION['return_success'])): ?>

                <div class="return-message success">

                    <?php
                    echo htmlspecialchars($_SESSION['return_success']);
                    unset($_SESSION['return_success']);
                    ?>

                </div>

            <?php endif; ?>


            <?php if (isset($_SESSION['return_error'])): ?>

                <div class="return-message error">

                    <?php
                    echo htmlspecialchars($_SESSION['return_error']);
                    unset($_SESSION['return_error']);
                    ?>

                </div>

            <?php endif; ?>


            <div class="return-header">

                <div>

                    <h2>Active Borrowings</h2>

                    <p>
                        Select a borrowed equipment to record its return.
                    </p>

                </div>

            </div>


            <div class="return-card">

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>Request ID</th>

                                <th>Borrower</th>

                                <th>Equipment</th>

                                <th>Quantity</th>

                                <th>Borrow Date</th>

                                <th>Expected Return</th>

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

                                        <span class="status-badge borrowed">
                                            Borrowed
                                        </span>

                                    </td>


                                    <td>

                                        <button
                                            type="button"
                                            class="return-button"
                                            data-borrow-quantity="
                                            <?php echo (int) $row['borrow_quantity']; ?>"
                                            onclick="openReturnModal(
                                                <?php echo (int) $row['request_id']; ?>,
                                                this.dataset.equipmentName,
                                                this.dataset.borrowQuantity
                                            )"
                                        >
                                            Record Return
                                        </button>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="8" class="empty-state">

                                    No borrowed equipment waiting for return.

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>
            <br><br>
            <!-- RETURN HISTORY -->

<div class="return-header">
    <div>
        <h2>Return History</h2>
        <p>View previously returned equipment and recorded remarks.</p>
    </div>
</div>

<form
    action="returns.php"
    method="GET"
    class="return-history-search"
>
    <input
        type="text"
        name="history_search"
        placeholder="Search borrower, equipment, ID, or remarks..."
        value="<?php echo htmlspecialchars($historySearch); ?>"
    >

    <button type="submit">
        Search
    </button>

    <?php if ($historySearch !== ''): ?>
        <a href="returns.php" class="clear-search">
            Clear
        </a>
    <?php endif; ?>
</form>

<div class="return-card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Return ID</th>
                    <th>Request ID</th>
                    <th>Borrower</th>
                    <th>Equipment</th>
                    <th>Returned Qty</th>
                    <th>Lost Qty</th>
                    <th>Condition Before</th>
                    <th>Condition After</th>
                    <th>Return Date</th>
                    <th>Remarks</th>
                </tr>
            </thead>

            <tbody>
                <?php if (mysqli_num_rows($historyQuery) > 0): ?>

                    <?php while ($history = mysqli_fetch_assoc($historyQuery)): ?>
                        <tr>
                            <td>
                                #<?php echo (int) $history['return_id']; ?>
                            </td>

                            <td>
                                #<?php echo (int) $history['request_id']; ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $history['borrower_name'] ?? 'Unknown'
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $history['equipment_name'] ?? 'Unknown'
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo max(
                                    0,
                                    (int) ($history['borrow_quantity'] ?? 0) -
                                    (int) ($history['lost_quantity'] ?? 0)
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo (int) ($history['lost_quantity'] ?? 0); ?>
                            </td>

                            <td>
                                <?php
                                echo !empty($history['condition_at_borrow'])
                                    ? htmlspecialchars($history['condition_at_borrow'])
                                    : 'Not recorded';
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $history['return_condition'] ?? 'Not recorded'
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo !empty($history['return_date'])
                                    ? date(
                                        'M d, Y',
                                        strtotime($history['return_date'])
                                    )
                                    : '-';
                                ?>
                            </td>

                            <td>
                                <?php
                                $remarks = trim(
                                    $history['remarks'] ?? ''
                                );

                                echo $remarks !== ''
                                    ? nl2br(htmlspecialchars($remarks))
                                    : 'No remarks';
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="10" class="empty-state">
                            No return records found.
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


<!-- RETURN MODAL -->

<div class="return-modal" id="returnModal">

    <div class="return-modal-box">

        <div class="return-modal-header">

            <div>

                <h2>Record Equipment Return</h2>

                <p id="returnEquipmentName"></p>

            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closeReturnModal()"
            >
                ×
            </button>

        </div>


        <form method="POST">

            <input
                type="hidden"
                name="request_id"
                id="returnRequestId"
            >
            <div class="form-group">

            <label for="lost_quantity">
                Number of Units Lost
            </label>

            <input
                type="number"
                name="lost_quantity"
                id="lost_quantity"
                min="0"
                value="0"
                required
            >

            <small>
                Enter 0 if no units were lost. If all borrowed units were lost,
                the request will be marked Lost.
            </small>

        </div>


            <div class="form-group">

                <label for="return_condition">
                    Returned Condition
                </label>

                <select
                    name="return_condition"
                    id="return_condition"
                    required
                >

                    <option value="">
                        Select condition
                    </option>

                    <option value="Good">
                        Good
                    </option>

                    <option value="Fair">
                        Fair
                    </option>

                    <option value="Damaged">
                        Damaged
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label for="remarks">
                    Remarks
                </label>

                <textarea
                    name="remarks"
                    id="remarks"
                    rows="4"
                    placeholder="Enter remarks about the returned equipment..."
                ></textarea>

            </div>


            <div class="return-modal-actions">

                <button
                    type="button"
                    class="cancel-button"
                    onclick="closeReturnModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    name="record_return"
                    class="confirm-return-button"
                >
                    Confirm Return
                </button>

            </div>

        </form>

    </div>

</div>


<script src="../js/script.js"> </script>
<script>
function openReturnModal(requestId, equipmentName, borrowQuantity) {

    document.getElementById('returnRequestId').value = requestId;

    document.getElementById('returnEquipmentName').textContent =
        equipmentName;

    const lostInput = document.getElementById('lost_quantity');

    lostInput.max = borrowQuantity;
    lostInput.value = 0;

    document.getElementById('returnModal').classList.add('show');
}

function closeReturnModal() {
    document.getElementById('returnModal').classList.remove('show');
}
</script>
</body>

</html>