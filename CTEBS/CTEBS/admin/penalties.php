
<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Only the Captain can access this page.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "Captain") {
    header("Location: ../index.php");
    exit();
}


// ==================================================
// ADD PENALTY
// ==================================================

if (isset($_POST['add_penalty'])) {

    $request_id = filter_var(
        $_POST['request_id'] ?? '',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $reason = trim($_POST['reason'] ?? '');
    $amountInput = trim($_POST['amount'] ?? '');

    $allowedReasons = [
        'Late Return',
        'Damaged Equipment',
        'Lost Equipment'
    ];

    if (
        $request_id === false ||
        !in_array($reason, $allowedReasons, true) ||
        $amountInput === '' ||
        !is_numeric($amountInput) ||
        !is_finite((float) $amountInput) ||
        (float) $amountInput <= 0
    ) {
        $_SESSION['penalty_error'] =
            "Please enter a valid penalty amount and case information.";

        header("Location: penalties.php");
        exit();
    }

    $amount = (float) $amountInput;

    // Check that the case is still eligible.
    if ($reason === 'Late Return') {

        $caseSql = "SELECT br.request_id
                    FROM borrower_request br
                    WHERE br.request_id = ?
                    AND (
                        (
                            br.status = 'Borrowed'
                            AND br.return_date < CURDATE()
                        )
                        OR
                        (
                            br.status = 'Returned'
                            AND EXISTS (
                                SELECT 1
                                FROM return_record rr
                                WHERE rr.request_id = br.request_id
                                    AND rr.return_date > br.return_date
                            )
                        )
                    )
                    LIMIT 1";

    } elseif ($reason === 'Damaged Equipment') {

        $caseSql = "SELECT br.request_id
                    FROM borrower_request br
                    WHERE br.request_id = ?
                    AND br.status = 'Returned'
                    AND EXISTS (
                        SELECT 1
                        FROM return_record rr
                        WHERE rr.request_id = br.request_id
                            AND rr.return_condition = 'Damaged'
                    )
                    LIMIT 1";

    } else {

        // A request can have some or all of its units marked as lost.
        $caseSql = "SELECT br.request_id
                    FROM borrower_request br
                    WHERE br.request_id = ?
                    AND br.lost_quantity > 0
                    AND br.status IN ('Returned', 'Lost')
                    LIMIT 1";
    }
    $caseStatement = mysqli_prepare($connect, $caseSql);

    if (!$caseStatement) {
        error_log("Penalty case validation failed: " . mysqli_error($connect));

        $_SESSION['penalty_error'] =
            "Unable to verify the case. Please try again.";

        header("Location: penalties.php");
        exit();
    }

    mysqli_stmt_bind_param($caseStatement, "i", $request_id);
    mysqli_stmt_execute($caseStatement);

    $caseResult = mysqli_stmt_get_result($caseStatement);
    $validCase = mysqli_fetch_assoc($caseResult);

    mysqli_stmt_close($caseStatement);

    if (!$validCase) {
        $_SESSION['penalty_error'] =
            "This case is no longer eligible for the selected penalty.";

        header("Location: penalties.php");
        exit();
    }

    // Prevent duplicate penalties for the same request and reason.
    $duplicateStatement = mysqli_prepare(
        $connect,
        "SELECT penalty_id
         FROM penalty
         WHERE request_id = ?
           AND reason = ?
         LIMIT 1"
    );

    if (!$duplicateStatement) {
        error_log("Penalty duplicate check failed: " . mysqli_error($connect));

        $_SESSION['penalty_error'] =
            "Unable to check existing penalties.";

        header("Location: penalties.php");
        exit();
    }

    mysqli_stmt_bind_param(
        $duplicateStatement,
        "is",
        $request_id,
        $reason
    );

    mysqli_stmt_execute($duplicateStatement);

    $duplicateResult = mysqli_stmt_get_result($duplicateStatement);
    $duplicatePenalty = mysqli_fetch_assoc($duplicateResult);

    mysqli_stmt_close($duplicateStatement);

    if ($duplicatePenalty) {
        $_SESSION['penalty_error'] =
            "A penalty for this request and reason has already been recorded.";

        header("Location: penalties.php");
        exit();
    }

    // Save the penalty as Unpaid.
    $insertStatement = mysqli_prepare(
        $connect,
        "INSERT INTO penalty
            (reason, amount, status, request_id, created_at)
            VALUES (?, ?, 'Unpaid', ?, NOW())"
        );

    if (!$insertStatement) {
        error_log("Penalty insert preparation failed: " . mysqli_error($connect));

        $_SESSION['penalty_error'] =
            "Unable to prepare the penalty record.";

        header("Location: penalties.php");
        exit();
    }

    mysqli_stmt_bind_param(
        $insertStatement,
        "sdi",
        $reason,
        $amount,
        $request_id
    );

    if (mysqli_stmt_execute($insertStatement)) {
        $_SESSION['penalty_success'] =
            "Penalty successfully added as Unpaid.";
    } else {
        error_log("Penalty insert failed: " . mysqli_stmt_error($insertStatement));

        $_SESSION['penalty_error'] =
            "Failed to add the penalty. Please try again.";
    }

    mysqli_stmt_close($insertStatement);

    header("Location: penalties.php");
    exit();
}


// ==================================================
// MARK PENALTY AS PAID
// ==================================================

if (isset($_POST['mark_paid'])) {

    $penalty_id = filter_var(
        $_POST['penalty_id'] ?? '',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($penalty_id === false) {
        $_SESSION['penalty_error'] = "Invalid penalty record.";

        header("Location: penalties.php");
        exit();
    }

    $updateStatement = mysqli_prepare(
        $connect,
        "UPDATE penalty
         SET status = 'Paid'
         WHERE penalty_id = ?
           AND status = 'Unpaid'"
    );

    if (!$updateStatement) {
        $_SESSION['penalty_error'] = "Unable to update the penalty.";

        header("Location: penalties.php");
        exit();
    }

    mysqli_stmt_bind_param($updateStatement, "i", $penalty_id);
    mysqli_stmt_execute($updateStatement);

    if (mysqli_stmt_affected_rows($updateStatement) === 1) {
        $_SESSION['penalty_success'] = "Penalty marked as paid.";
    } else {
        $_SESSION['penalty_error'] =
            "The penalty was already paid or could not be found.";
    }

    mysqli_stmt_close($updateStatement);

    header("Location: penalties.php");
    exit();
}


// ==================================================
// FIND POTENTIAL LATE-RETURN CASES
// ==================================================

$potentialCases = [];

$lateSql = "SELECT
                br.request_id,
                br.borrower_name,
                br.borrower_contact,
                br.borrow_quantity,
                br.borrow_date,
                br.return_date AS due_date,
                br.status AS request_status,
                e.equipment_name,
                rr.return_date AS actual_return_date,
                rr.remarks AS return_remarks
            FROM borrower_request br
            LEFT JOIN equipment e
                ON br.equipment_id = e.equipment_id
            LEFT JOIN return_record rr
                ON rr.request_id = br.request_id
            WHERE (
                (
                    br.status = 'Borrowed'
                    AND br.return_date < CURDATE()
                )
                OR
                (
                    br.status = 'Returned'
                    AND rr.return_date IS NOT NULL
                    AND rr.return_date > br.return_date
                )
            )
            AND NOT EXISTS (
                SELECT 1
                FROM penalty p
                WHERE p.request_id = br.request_id
                  AND p.reason = 'Late Return'
            )
            ORDER BY br.return_date ASC";

$lateQuery = mysqli_query($connect, $lateSql);

if ($lateQuery) {
    while ($row = mysqli_fetch_assoc($lateQuery)) {

        if ($row['request_status'] === 'Borrowed') {
            $row['case_type'] = 'Overdue - Not Returned';

            $row['case_details'] =
                'The due date has passed and the item has not been returned.';
        } else {
            $row['case_type'] = 'Returned Late';

            $row['case_details'] =
                'Actual return date: ' . $row['actual_return_date'];
        }

        $row['suggested_reason'] = 'Late Return';
        $potentialCases[] = $row;
    }
} else {
    error_log("Late-return case query failed: " . mysqli_error($connect));
}


// ==================================================
// FIND POTENTIAL DAMAGED-EQUIPMENT CASES
// ==================================================

$damageSql = "SELECT
                  br.request_id,
                  br.borrower_name,
                  br.borrower_contact,
                  br.borrow_quantity,
                  br.borrow_date,
                  br.return_date AS due_date,
                  br.status AS request_status,
                  e.equipment_name,
                  rr.return_id,
                  rr.return_date AS actual_return_date,
                  rr.return_condition,
                  rr.remarks AS return_remarks
              FROM return_record rr
              INNER JOIN borrower_request br
                  ON rr.request_id = br.request_id
              LEFT JOIN equipment e
                  ON br.equipment_id = e.equipment_id
              WHERE br.status = 'Returned'
                AND rr.return_condition = 'Damaged'
                AND NOT EXISTS (
                    SELECT 1
                    FROM penalty p
                    WHERE p.request_id = br.request_id
                      AND p.reason = 'Damaged Equipment'
                )
              ORDER BY rr.return_date DESC";

$damageQuery = mysqli_query($connect, $damageSql);

if ($damageQuery) {
    while ($row = mysqli_fetch_assoc($damageQuery)) {

        $row['case_type'] = 'Damaged Equipment';
        $row['suggested_reason'] = 'Damaged Equipment';

        $remarks = trim((string) ($row['return_remarks'] ?? ''));

        $row['case_details'] = $remarks !== ''
            ? $remarks
            : 'The return condition was recorded as Damaged. No remarks were entered.';

        $potentialCases[] = $row;
    }
} else {
    error_log("Damaged-equipment case query failed: " . mysqli_error($connect));
}

// ==================================================
// FIND POTENTIAL LOST-EQUIPMENT CASES
// ==================================================

$lostSql = "SELECT
                br.request_id,
                br.borrower_name,
                br.borrower_contact,
                br.borrow_quantity,
                br.lost_quantity,
                br.borrow_date,
                br.return_date AS due_date,
                br.status AS request_status,
                e.equipment_name,
                rr.return_date AS actual_return_date,
                rr.remarks AS return_remarks
            FROM borrower_request br
            LEFT JOIN equipment e
                ON br.equipment_id = e.equipment_id
            LEFT JOIN return_record rr
                ON rr.request_id = br.request_id
            WHERE br.lost_quantity > 0
              AND br.status IN ('Returned', 'Lost')
              AND NOT EXISTS (
                  SELECT 1
                  FROM penalty p
                  WHERE p.request_id = br.request_id
                    AND p.reason = 'Lost Equipment'
              )
            ORDER BY br.request_id DESC";

$lostQuery = mysqli_query($connect, $lostSql);

if ($lostQuery) {

    while ($row = mysqli_fetch_assoc($lostQuery)) {

        $row['case_type'] = 'Lost Equipment';
        $row['suggested_reason'] = 'Lost Equipment';

        $lostCount = (int) $row['lost_quantity'];

        $row['case_details'] =
            $lostCount . ' unit(s) recorded as lost.';

        $remarks = trim(
            (string) ($row['return_remarks'] ?? '')
        );

        if ($remarks !== '') {
            $row['case_details'] .= ' Remarks: ' . $remarks;
        }

        $potentialCases[] = $row;
    }

} else {

    error_log(
        "Lost-equipment case query failed: " .
        mysqli_error($connect)
    );
}
// ==================================================
// GET SAVED PENALTY RECORDS
// ==================================================

$penaltySql = "SELECT
                   p.penalty_id,
                   p.reason,
                   p.amount,
                   p.status,
                   p.request_id,
                   br.borrower_name,
                   br.borrower_contact,
                   br.borrow_quantity,
                   e.equipment_name
               FROM penalty p
               LEFT JOIN borrower_request br
                   ON p.request_id = br.request_id
               LEFT JOIN equipment e
                   ON br.equipment_id = e.equipment_id
               ORDER BY p.penalty_id DESC";

$penaltyQuery = mysqli_query($connect, $penaltySql);

if (!$penaltyQuery) {
    error_log("Penalty records query failed: " . mysqli_error($connect));
}


// ==================================================
// SUMMARY COUNTS
// ==================================================

$unpaidCount = 0;
$unpaidAmount = 0;

$countSql = "SELECT
                 COUNT(*) AS total_count,
                 COALESCE(SUM(amount), 0) AS total_amount
             FROM penalty
             WHERE status = 'Unpaid'";

$countQuery = mysqli_query($connect, $countSql);

if ($countQuery) {
    $countRow = mysqli_fetch_assoc($countQuery);

    $unpaidCount = (int) $countRow['total_count'];
    $unpaidAmount = (float) $countRow['total_amount'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Penalties - CTEBS</title>

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

            <a href="returns.php">
                <span class="menu-icon">↩</span>
                Returns
            </a>

            <a href="penalties.php" class="active">
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

        <header class="topbar">

            <div>
                <h1>Penalties</h1>
                <p>Review potential cases and manage penalty payments.</p>
            </div>

            <div class="admin-profile">

                <div class="profile-avatar">
                    <span>👤</span>
                </div>

                <div class="profile-info">
                    <strong>
                        <?php
                        echo htmlspecialchars($_SESSION['name'] ?? 'Captain');
                        ?>
                    </strong>

                    <span>Barangay Captain</span>
                </div>

                <div class="profile-dropdown">

                    <button
                        type="button"
                        class="profile-dropdown-toggle"
                        aria-label="Open account menu"
                        aria-expanded="false"
                        onclick="toggleProfileMenu(this)"
                    >
                        <span>⌄</span>
                    </button>

                    <div class="profile-dropdown-menu">
                        <a href="profile.php">Profile Settings</a>
                    </div>

                </div>

            </div>

        </header>


        <section class="penalty-content">

            <!-- SUCCESS MESSAGE -->

            <?php if (isset($_SESSION['penalty_success'])): ?>
                <div class="penalty-message success" role="status">
                    <?php
                    echo htmlspecialchars($_SESSION['penalty_success']);
                    unset($_SESSION['penalty_success']);
                    ?>
                </div>
            <?php endif; ?>


            <!-- ERROR MESSAGE -->

            <?php if (isset($_SESSION['penalty_error'])): ?>
                <div class="penalty-message error" role="alert">
                    <?php
                    echo htmlspecialchars($_SESSION['penalty_error']);
                    unset($_SESSION['penalty_error']);
                    ?>
                </div>
            <?php endif; ?>


            <!-- PAGE HEADER -->

            <div class="penalty-header">
                <div>
                    <h2>Penalty Management</h2>
                    <p>
                        Review possible late-return and damaged-equipment
                        cases before adding a penalty.
                    </p>
                </div>
            </div>


            <!-- SUMMARY CARDS -->

            <div class="penalty-summary-row">

                <div class="penalty-summary-card">
                    <span>Potential Cases to Review</span>
                    <strong><?php echo count($potentialCases); ?></strong>
                </div>

                <div class="penalty-summary-card">
                    <span>Unpaid Penalties</span>
                    <strong><?php echo $unpaidCount; ?></strong>
                </div>

                <div class="penalty-summary-card">
                    <span>Total Unpaid Amount</span>
                    <strong>
                        ₱<?php echo number_format($unpaidAmount, 2); ?>
                    </strong>
                </div>

            </div>


            <!-- POTENTIAL PENALTY CASES -->

            <div class="penalty-section-heading">
                <h2>Potential Penalty Cases</h2>
                <p>
                    Review overdue items, late returns, damaged equipment,
                    and lost units before creating a penalty.
                </p>
            </div>


            <!-- SEARCH -->

            <div class="penalty-case-search">
                <input
                    type="search"
                    id="potentialCaseSearch"
                    placeholder="Search borrower, equipment, request ID, or issue..."
                    aria-label="Search potential penalty cases"
                >
            </div>


            <!-- POTENTIAL CASES TABLE -->

            <div class="penalty-card">

                <div class="table-container">

                    <table>

                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Borrower</th>
                                <th>Equipment</th>
                                <th>Quantity</th>
                                <th>Potential Issue</th>
                                <th>Details / Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (!empty($potentialCases)): ?>

                            <?php foreach ($potentialCases as $case): ?>

                                <?php
                                $searchText =
                                    ($case['request_id'] ?? '') . ' ' .
                                    ($case['borrower_name'] ?? '') . ' ' .
                                    ($case['equipment_name'] ?? '') . ' ' .
                                    ($case['case_type'] ?? '') . ' ' .
                                    ($case['case_details'] ?? '');
                                ?>

                                <tr
                                    class="potential-case-row"
                                    data-search="<?php
                                    echo htmlspecialchars(
                                        $searchText,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>"
                                >

                                    <td>
                                        #<?php echo (int) $case['request_id']; ?>
                                    </td>

                                    <td>
                                        <div class="borrower-info">
                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $case['borrower_name'] ?? 'Unknown'
                                                );
                                                ?>
                                            </strong>

                                            <span>
                                                <?php
                                                echo htmlspecialchars(
                                                    $case['borrower_contact'] ?? ''
                                                );
                                                ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $case['equipment_name'] ?? 'Unknown'
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo (int) ($case['borrow_quantity'] ?? 0);
                                        ?>
                                    </td>

                                    <td>

                                        <span class="status-badge <?php
                                            echo $case['suggested_reason'] === 'Damaged Equipment'
                                                ? 'damaged'
                                                : 'overdue';
                                        ?>">
                                            <?php
                                            echo htmlspecialchars($case['case_type']);
                                            ?>
                                        </span>

                                        <small class="penalty-case-date">
                                            Due:
                                            <?php
                                            echo htmlspecialchars(
                                                $case['due_date'] ?? '-'
                                            );
                                            ?>
                                        </small>

                                    </td>

                                    <td class="penalty-case-details">
                                        <?php
                                        echo nl2br(
                                            htmlspecialchars($case['case_details'])
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="add-penalty-button"
                                            data-request-id="<?php
                                            echo (int) $case['request_id'];
                                            ?>"
                                            data-reason="<?php
                                            echo htmlspecialchars(
                                                $case['suggested_reason'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>"
                                            data-borrower="<?php
                                            echo htmlspecialchars(
                                                $case['borrower_name'] ?? 'Unknown',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>"
                                            data-equipment="<?php
                                            echo htmlspecialchars(
                                                $case['equipment_name'] ?? 'Unknown',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>"
                                            onclick="openPenaltyCaseModal(this)"
                                        >
                                            Review &amp; Add Penalty
                                        </button>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            <tr id="noSearchMatches" style="display:none;">
                                <td colspan="7" class="empty-state">
                                    No matching potential cases found.
                                </td>
                            </tr>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="empty-state">
                                    No potential penalty cases need review right now.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


            <!-- EXISTING PENALTY RECORDS -->

            <div class="penalty-section-heading penalty-history-heading">
                <h2>Penalty Records</h2>
                <p>
                    Review saved penalties and mark them as paid when
                    payment is received.
                </p>
            </div>


            <div class="penalty-card">

                <div class="table-container">

                    <table>

                        <thead>
                            <tr>
                                <th>Penalty ID</th>
                                <th>Request ID</th>
                                <th>Borrower</th>
                                <th>Equipment</th>
                                <th>Quantity</th>
                                <th>Reason</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($penaltyQuery && mysqli_num_rows($penaltyQuery) > 0): ?>

                            <?php while ($penalty = mysqli_fetch_assoc($penaltyQuery)): ?>

                                <tr>

                                    <td>
                                        #<?php echo (int) $penalty['penalty_id']; ?>
                                    </td>

                                    <td>
                                        #<?php echo (int) $penalty['request_id']; ?>
                                    </td>

                                    <td>
                                        <div class="borrower-info">
                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $penalty['borrower_name'] ?? 'Unknown'
                                                );
                                                ?>
                                            </strong>

                                            <span>
                                                <?php
                                                echo htmlspecialchars(
                                                    $penalty['borrower_contact'] ?? ''
                                                );
                                                ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $penalty['equipment_name'] ?? 'Unknown'
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo (int) ($penalty['borrow_quantity'] ?? 0);
                                        ?>
                                    </td>

                                    <td>
                                        <span class="penalty-reason">
                                            <?php
                                            echo htmlspecialchars($penalty['reason']);
                                            ?>
                                        </span>
                                    </td>

                                    <td>
                                        <strong class="penalty-amount">
                                            ₱<?php
                                            echo number_format(
                                                (float) $penalty['amount'],
                                                2
                                            );
                                            ?>
                                        </strong>
                                    </td>

                                    <td>

                                        <?php if ($penalty['status'] === 'Paid'): ?>
                                            <span class="status-badge paid">Paid</span>
                                        <?php else: ?>
                                            <span class="status-badge unpaid">Unpaid</span>
                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php if ($penalty['status'] === 'Unpaid'): ?>

                                            <form
                                                method="POST"
                                                action="penalties.php"
                                                style="display:inline;"
                                                onsubmit="return confirm('Mark this penalty as paid?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="penalty_id"
                                                    value="<?php
                                                    echo (int) $penalty['penalty_id'];
                                                    ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="mark_paid"
                                                    class="paid-button"
                                                >
                                                    Mark as Paid
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <span class="paid-label">
                                                Completed
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="9" class="empty-state">
                                    <?php
                                    echo $penaltyQuery
                                        ? 'No penalty records found.'
                                        : 'Penalty records could not be loaded.';
                                    ?>
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


<!-- ADD PENALTY MODAL -->

<div class="penalty-modal" id="penaltyModal">

    <div class="penalty-modal-box">

        <div class="penalty-modal-header">

            <div>
                <h2>Review Potential Penalty</h2>
                <p id="penaltyCaseSummary">
                    Review the case before saving the penalty.
                </p>
            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closePenaltyModal()"
                aria-label="Close modal"
            >
                ×
            </button>

        </div>


        <form method="POST" action="penalties.php">

            <input
                type="hidden"
                name="request_id"
                id="penaltyRequestId"
            >

            <input
                type="hidden"
                name="reason"
                id="penaltyReason"
            >

            <div class="form-group">

                <label>Penalty Reason</label>

                <p id="penaltyReasonText" class="penalty-modal-reason"></p>

            </div>

            <div class="form-group">

                <label for="penaltyAmount">
                    Penalty Amount (PHP)
                </label>

                <div class="amount-input">

                    <span>₱</span>

                    <input
                        type="number"
                        id="penaltyAmount"
                        name="amount"
                        min="0.01"
                        step="0.01"
                        placeholder="Enter amount"
                        required
                    >

                </div>

                <small>
                    Enter the amount according to barangay policy.
                    The system does not calculate it automatically.
                </small>

            </div>

            <div class="penalty-modal-actions">

                <button
                    type="button"
                    class="cancel-button"
                    onclick="closePenaltyModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    name="add_penalty"
                    class="confirm-penalty-button"
                >
                    Confirm Penalty
                </button>

            </div>

        </form>

    </div>

</div>


<script src="../js/script.js"></script>

<script>
function openPenaltyCaseModal(button) {

    const requestId = button.dataset.requestId;
    const reason = button.dataset.reason;
    const borrower = button.dataset.borrower;
    const equipment = button.dataset.equipment;

    document.getElementById('penaltyRequestId').value = requestId;
    document.getElementById('penaltyReason').value = reason;
    document.getElementById('penaltyReasonText').textContent = reason;

    document.getElementById('penaltyCaseSummary').textContent =
        'Request #' + requestId + ' - ' + borrower + ' - ' + equipment;

    document.getElementById('penaltyAmount').value = '';

    document.getElementById('penaltyModal').classList.add('show');
}


// Search potential penalty cases.
const potentialCaseSearch = document.getElementById('potentialCaseSearch');

if (potentialCaseSearch) {

    potentialCaseSearch.addEventListener('input', function () {

        const searchText = this.value.toLowerCase().trim();

        const rows = document.querySelectorAll('.potential-case-row');

        let visibleRows = 0;

        rows.forEach(function (row) {

            const rowText = (row.dataset.search || '').toLowerCase();
            const matches = rowText.includes(searchText);

            row.style.display = matches ? '' : 'none';

            if (matches) {
                visibleRows++;
            }
        });

        const noMatches = document.getElementById('noSearchMatches');

        if (noMatches) {
            noMatches.style.display =
                rows.length > 0 && visibleRows === 0
                    ? ''
                    : 'none';
        }
    });
}
</script>

</body>
</html>
