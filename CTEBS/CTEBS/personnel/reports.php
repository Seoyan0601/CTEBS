<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Only Personnel can access this page.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Personnel') {
    header('Location: ../index.php');
    exit();
}

function reportDateCondition($column, $filter) {
    if ($filter === 'today') {
        return "WHERE $column >= CURDATE() AND $column < CURDATE() + INTERVAL 1 DAY";
    }

    if ($filter === 'week') {
        return "WHERE $column >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                AND $column < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY)";
    }

    if ($filter === 'month') {
        return "WHERE $column >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                AND $column < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')";
    }

    return '';
}

if (isset($_POST['generate_report'])) {
    $report_type = trim($_POST['report_type'] ?? '');
    $allowedReports = [
        'Borrower Report',
        'Equipment Inventory',
        'Available Tools Report',
        'Overdue Equipment Report',
        'Damaged Equipment Report',
        'Borrowing Transactions',
        'Return Records',
        'Penalty Records'
    ];

    if (!in_array($report_type, $allowedReports, true)) {
        $_SESSION['report_error'] = 'Please select a valid report type.';
        header('Location: reports.php');
        exit();
    }

    $date_filter = $_POST['date_filter'] ?? 'all';
    $allowedFilters = ['today', 'week', 'month', 'all'];
    if (!in_array($date_filter, $allowedFilters, true)) {
        $date_filter = 'all';
    }

    $filterLabels = [
        'today' => 'Today',
        'week' => 'This Week',
        'month' => 'This Month',
        'all' => 'All Records'
    ];

    $generated_by = (int) ($_SESSION['user_id'] ?? 0);
    if ($generated_by <= 0) {
        $_SESSION['report_error'] = 'Unable to identify the logged-in Personnel account.';
        header('Location: reports.php');
        exit();
    }

    $details = '';
    $result = false;

    // EQUIPMENT INVENTORY: current snapshot, not a historical date-filtered report.
    if ($report_type === 'Equipment Inventory') {
        $sql = "SELECT e.equipment_id, e.equipment_name, e.category, e.quantity,
                       e.`condition`, e.status,
                       COALESCE((SELECT SUM(br.borrow_quantity)
                                 FROM borrower_request br
                                 WHERE br.equipment_id = e.equipment_id
                                   AND br.status = 'Borrowed'), 0) AS borrowed_quantity,
                       GREATEST(e.quantity - COALESCE((SELECT SUM(br.borrow_quantity)
                                                       FROM borrower_request br
                                                       WHERE br.equipment_id = e.equipment_id
                                                         AND br.status = 'Borrowed'), 0), 0) AS available_quantity
                FROM equipment e
                ORDER BY e.equipment_name ASC";

        $result = mysqli_query($connect, $sql);
        if ($result) {
            $details .= "EQUIPMENT INVENTORY REPORT\n";
            $details .= "Date Generated: " . date('F d, Y h:i A') . "\n";
            $details .= "Date Range: Current Inventory Snapshot\n";
            $details .= "========================================\n\n";

            if (mysqli_num_rows($result) === 0) {
                $details .= "No equipment records found.\n";
            }

            while ($row = mysqli_fetch_assoc($result)) {
                $details .= "Equipment ID: {$row['equipment_id']}\n";
                $details .= "Equipment Name: {$row['equipment_name']}\n";
                $details .= "Category: {$row['category']}\n";
                $details .= "Total Stock: {$row['quantity']}\n";
                $details .= "Borrowed Quantity: {$row['borrowed_quantity']}\n";
                $details .= "Available Quantity: {$row['available_quantity']}\n";
                $details .= "Condition: {$row['condition']}\n";
                $details .= "Status: {$row['status']}\n";
                $details .= "----------------------------------------\n\n";
            }
        }
    }

    // AVAILABLE TOOLS REPORT: current snapshot of equipment with at least one available unit.
    elseif ($report_type === 'Available Tools Report') {
        $sql = "SELECT e.equipment_id, e.equipment_name, e.category, e.quantity,
                       e.`condition`, e.status,
                       COALESCE(b.borrowed_quantity, 0) AS borrowed_quantity,
                       GREATEST(e.quantity - COALESCE(b.borrowed_quantity, 0), 0) AS available_quantity
                FROM equipment e
                LEFT JOIN (
                    SELECT equipment_id, SUM(borrow_quantity) AS borrowed_quantity
                    FROM borrower_request
                    WHERE status = 'Borrowed'
                    GROUP BY equipment_id
                ) b ON b.equipment_id = e.equipment_id
                WHERE GREATEST(e.quantity - COALESCE(b.borrowed_quantity, 0), 0) > 0
                ORDER BY e.equipment_name ASC";

        $result = mysqli_query($connect, $sql);
        if ($result) {
            $details .= "AVAILABLE TOOLS REPORT\n";
            $details .= "Date Generated: " . date('F d, Y h:i A') . "\n";
            $details .= "Date Range: Current Availability Snapshot\n";
            $details .= "========================================\n\n";

            if (mysqli_num_rows($result) === 0) {
                $details .= "No equipment currently has available units.\n";
            }

            while ($row = mysqli_fetch_assoc($result)) {
                $details .= "Equipment ID: {$row['equipment_id']}\n";
                $details .= "Equipment Name: {$row['equipment_name']}\n";
                $details .= "Category: {$row['category']}\n";
                $details .= "Total Stock: {$row['quantity']}\n";
                $details .= "Borrowed Quantity: {$row['borrowed_quantity']}\n";
                $details .= "Available Quantity: {$row['available_quantity']}\n";
                $details .= "Condition: {$row['condition']}\n";
                $details .= "Status: {$row['status']}\n";
                $details .= "----------------------------------------\n\n";
            }
        }
    }

    // OVERDUE EQUIPMENT REPORT: current snapshot of borrowed equipment past its expected return date.
    elseif ($report_type === 'Overdue Equipment Report') {
        $sql = "SELECT br.request_id, br.borrower_name, br.borrower_contact,
                       br.borrow_date, br.return_date, br.borrow_quantity,
                       br.condition_at_borrow, e.equipment_id, e.equipment_name,
                       e.category, DATEDIFF(CURDATE(), DATE(br.return_date)) AS days_overdue
                FROM borrower_request br
                LEFT JOIN equipment e ON br.equipment_id = e.equipment_id
                WHERE LOWER(TRIM(COALESCE(br.status, ''))) = 'borrowed'
                  AND br.return_date IS NOT NULL
                  AND DATE(br.return_date) < CURDATE()
                ORDER BY br.return_date ASC, br.request_id ASC";

        $result = mysqli_query($connect, $sql);
        if ($result) {
            $details .= "OVERDUE EQUIPMENT REPORT\n";
            $details .= "Date Generated: " . date('F d, Y h:i A') . "\n";
            $details .= "Date Range: Current Overdue Snapshot\n";
            $details .= "========================================\n\n";

            if (mysqli_num_rows($result) === 0) {
                $details .= "No borrowed equipment is currently overdue.\n";
            }

            while ($row = mysqli_fetch_assoc($result)) {
                $details .= "Request ID: #{$row['request_id']}\n";
                $details .= "Borrower: {$row['borrower_name']}\n";
                $details .= "Contact Number: " . ($row['borrower_contact'] ?? 'Not provided') . "\n";
                $details .= "Equipment ID: " . ($row['equipment_id'] ?? 'Unknown') . "\n";
                $details .= "Equipment: " . ($row['equipment_name'] ?? 'Unknown') . "\n";
                $details .= "Category: " . ($row['category'] ?? 'Unknown') . "\n";
                $details .= "Borrowed Quantity: {$row['borrow_quantity']}\n";
                $details .= "Borrow Date: " . ($row['borrow_date'] ?? 'Not recorded') . "\n";
                $details .= "Expected Return Date: {$row['return_date']}\n";
                $details .= "Days Overdue: " . max(0, (int) $row['days_overdue']) . "\n";
                $details .= "Condition at Borrow: " . ($row['condition_at_borrow'] ?? 'Not recorded') . "\n";
                $details .= "----------------------------------------\n\n";
            }
        }
    }

    // DAMAGED EQUIPMENT REPORT: current damaged inventory plus damaged return records filtered by return date.
    elseif ($report_type === 'Damaged Equipment Report') {
        // Equipment has no date that records when its condition changed, so this section is always a current snapshot.
        $damagedInventorySql = "SELECT equipment_id, equipment_name, category, quantity,
                                       `condition`, status
                                FROM equipment
                                WHERE LOWER(TRIM(COALESCE(`condition`, ''))) = 'damaged'
                                ORDER BY equipment_name ASC";

        // Filter the dated return records by the selected date range.
        $damageDateCondition = '';
        if ($date_filter === 'today') {
            $damageDateCondition = " AND rr.return_date >= CURDATE()
                                     AND rr.return_date < CURDATE() + INTERVAL 1 DAY";
        } elseif ($date_filter === 'week') {
            $damageDateCondition = " AND rr.return_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                                     AND rr.return_date < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY)";
        } elseif ($date_filter === 'month') {
            $damageDateCondition = " AND rr.return_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                                     AND rr.return_date < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')";
        }

        $damagedReturnsSql = "SELECT rr.return_id, rr.request_id, rr.return_date,
                                     rr.return_condition, rr.remarks,
                                     br.borrower_name, br.borrower_contact,
                                     br.borrow_quantity, br.lost_quantity,
                                     e.equipment_id, e.equipment_name, e.category
                              FROM return_record rr
                              LEFT JOIN borrower_request br ON rr.request_id = br.request_id
                              LEFT JOIN equipment e ON br.equipment_id = e.equipment_id
                              WHERE LOWER(TRIM(COALESCE(rr.return_condition, ''))) = 'damaged'
                              $damageDateCondition
                              ORDER BY rr.return_date DESC, rr.return_id DESC";

        $damagedInventoryResult = mysqli_query($connect, $damagedInventorySql);
        $damagedReturnsResult = mysqli_query($connect, $damagedReturnsSql);

        if ($damagedInventoryResult && $damagedReturnsResult) {
            $details .= "DAMAGED EQUIPMENT REPORT\n";
            $details .= "Date Generated: " . date('F d, Y h:i A') . "\n";
            $details .= "Selected Date Range: {$filterLabels[$date_filter]}\n";
            $details .= "Note: Inventory condition is a current snapshot. Damaged return records are filtered by return date.\n";
            $details .= "========================================\n\n";

            $details .= "SECTION 1: EQUIPMENT CURRENTLY MARKED DAMAGED\n";
            $details .= "----------------------------------------\n";
            if (mysqli_num_rows($damagedInventoryResult) === 0) {
                $details .= "No equipment is currently marked as damaged in the inventory.\n";
            }

            while ($row = mysqli_fetch_assoc($damagedInventoryResult)) {
                $details .= "Equipment ID: {$row['equipment_id']}\n";
                $details .= "Equipment Name: {$row['equipment_name']}\n";
                $details .= "Category: {$row['category']}\n";
                $details .= "Total Stock: {$row['quantity']}\n";
                $details .= "Condition: {$row['condition']}\n";
                $details .= "Status: {$row['status']}\n";
                $details .= "----------------------------------------\n";
            }

            $details .= "\nSECTION 2: RETURN RECORDS WITH DAMAGED CONDITION\n";
            $details .= "----------------------------------------\n";
            if (mysqli_num_rows($damagedReturnsResult) === 0) {
                $details .= "No damaged return records found for {$filterLabels[$date_filter]}.\n";
            }

            while ($row = mysqli_fetch_assoc($damagedReturnsResult)) {
                $details .= "Return ID: #{$row['return_id']}\n";
                $details .= "Request ID: #{$row['request_id']}\n";
                $details .= "Borrower: " . ($row['borrower_name'] ?? 'Unknown') . "\n";
                $details .= "Contact Number: " . ($row['borrower_contact'] ?? 'Not provided') . "\n";
                $details .= "Equipment ID: " . ($row['equipment_id'] ?? 'Unknown') . "\n";
                $details .= "Equipment Name: " . ($row['equipment_name'] ?? 'Unknown') . "\n";
                $details .= "Category: " . ($row['category'] ?? 'Unknown') . "\n";
                $details .= "Borrowed Quantity: " . (int) ($row['borrow_quantity'] ?? 0) . "\n";
                $details .= "Lost Quantity: " . (int) ($row['lost_quantity'] ?? 0) . "\n";
                $details .= "Return Date: " . ($row['return_date'] ?? 'Not recorded') . "\n";
                $details .= "Recorded Return Condition: {$row['return_condition']}\n";
                $details .= "Remarks: " . (($row['remarks'] ?? '') !== '' ? $row['remarks'] : 'None') . "\n";
                $details .= "----------------------------------------\n";
            }

            mysqli_free_result($damagedInventoryResult);
            mysqli_free_result($damagedReturnsResult);
            $result = true;
        } else {
            if ($damagedInventoryResult) {
                mysqli_free_result($damagedInventoryResult);
            }
            if ($damagedReturnsResult) {
                mysqli_free_result($damagedReturnsResult);
            }
            $result = false;
        }
    }

    // BORROWER REPORT
    elseif ($report_type === 'Borrower Report') {
        $dateCondition = reportDateCondition('br.request_date', $date_filter);
        $sql = "SELECT br.request_id, br.borrower_name, br.borrower_address,
                       br.borrower_contact, br.purpose, br.request_date,
                       br.borrow_date, br.return_date, br.borrow_quantity,
                       br.status, e.equipment_name
                FROM borrower_request br
                LEFT JOIN equipment e ON br.equipment_id = e.equipment_id
                $dateCondition
                ORDER BY br.request_date DESC, br.request_id DESC";

        $result = mysqli_query($connect, $sql);
        if ($result) {
            $details .= "BORROWER REPORT\n";
            $details .= "Date Generated: " . date('F d, Y h:i A') . "\n";
            $details .= "Date Range: {$filterLabels[$date_filter]}\n";
            $details .= "========================================\n\n";

            if (mysqli_num_rows($result) === 0) {
                $details .= "No borrower requests found for this date range.\n";
            }

            while ($row = mysqli_fetch_assoc($result)) {
                $details .= "Request ID: #{$row['request_id']}\n";
                $details .= "Borrower Name: {$row['borrower_name']}\n";
                $details .= "Address: {$row['borrower_address']}\n";
                $details .= "Contact Number: {$row['borrower_contact']}\n";
                $details .= "Purpose: " . ($row['purpose'] ?: 'Not provided') . "\n";
                $details .= "Equipment: " . ($row['equipment_name'] ?? 'Unknown') . "\n";
                $details .= "Quantity: {$row['borrow_quantity']}\n";
                $details .= "Date Requested: {$row['request_date']}\n";
                $details .= "Borrow Date: {$row['borrow_date']}\n";
                $details .= "Expected Return Date: {$row['return_date']}\n";
                $details .= "Request Status: {$row['status']}\n";
                $details .= "----------------------------------------\n\n";
            }
        }
    }

    // BORROWING TRANSACTIONS
    elseif ($report_type === 'Borrowing Transactions') {
        $dateCondition = reportDateCondition('br.request_date', $date_filter);
        $sql = "SELECT br.request_id, br.borrower_name, br.borrower_contact,
                       br.request_date, br.borrow_date, br.return_date,
                       br.status, br.borrow_quantity, e.equipment_name,
                       CASE WHEN br.status = 'Borrowed' AND br.return_date < CURDATE()
                            THEN 'Overdue' ELSE br.status END AS display_status
                FROM borrower_request br
                LEFT JOIN equipment e ON br.equipment_id = e.equipment_id
                $dateCondition
                ORDER BY br.request_id DESC";

        $result = mysqli_query($connect, $sql);
        if ($result) {
            $details .= "BORROWING TRANSACTIONS REPORT\n";
            $details .= "Date Generated: " . date('F d, Y h:i A') . "\n";
            $details .= "Date Range: {$filterLabels[$date_filter]}\n";
            $details .= "========================================\n\n";

            if (mysqli_num_rows($result) === 0) {
                $details .= "No borrowing requests found for this date range.\n";
            }

            while ($row = mysqli_fetch_assoc($result)) {
                $details .= "Request ID: #{$row['request_id']}\n";
                $details .= "Borrower: {$row['borrower_name']}\n";
                $details .= "Contact: {$row['borrower_contact']}\n";
                $details .= "Equipment: " . ($row['equipment_name'] ?? 'Unknown') . "\n";
                $details .= "Quantity: {$row['borrow_quantity']}\n";
                $details .= "Date Requested: {$row['request_date']}\n";
                $details .= "Borrow Date: {$row['borrow_date']}\n";
                $details .= "Return Date: {$row['return_date']}\n";
                $details .= "Status: {$row['display_status']}\n";
                $details .= "----------------------------------------\n\n";
            }
        }
    }

    // RETURN RECORDS
    elseif ($report_type === 'Return Records') {
        $dateCondition = reportDateCondition('rr.return_date', $date_filter);
        $sql = "SELECT rr.return_id, rr.request_id, rr.return_date,
                       rr.return_condition, rr.remarks, br.borrower_name,
                       br.borrow_quantity, br.lost_quantity, e.equipment_name
                FROM return_record rr
                LEFT JOIN borrower_request br ON rr.request_id = br.request_id
                LEFT JOIN equipment e ON br.equipment_id = e.equipment_id
                $dateCondition
                ORDER BY rr.return_date DESC, rr.return_id DESC";

        $result = mysqli_query($connect, $sql);
        if ($result) {
            $details .= "RETURN RECORDS REPORT\n";
            $details .= "Date Generated: " . date('F d, Y h:i A') . "\n";
            $details .= "Date Range: {$filterLabels[$date_filter]}\n";
            $details .= "========================================\n\n";

            if (mysqli_num_rows($result) === 0) {
                $details .= "No return records found for this date range.\n";
            }

            while ($row = mysqli_fetch_assoc($result)) {
                $returnedQuantity = max(0, (int) $row['borrow_quantity'] - (int) ($row['lost_quantity'] ?? 0));
                $details .= "Return ID: #{$row['return_id']}\n";
                $details .= "Request ID: #{$row['request_id']}\n";
                $details .= "Borrower: " . ($row['borrower_name'] ?? 'Unknown') . "\n";
                $details .= "Equipment: " . ($row['equipment_name'] ?? 'Unknown') . "\n";
                $details .= "Returned Quantity: {$returnedQuantity}\n";
                $details .= "Lost Quantity: " . (int) ($row['lost_quantity'] ?? 0) . "\n";
                $details .= "Return Date: {$row['return_date']}\n";
                $details .= "Condition: {$row['return_condition']}\n";
                $details .= "Remarks: " . ($row['remarks'] ?: 'None') . "\n";
                $details .= "----------------------------------------\n\n";
            }
        }
    }

    // PENALTY RECORDS
    elseif ($report_type === 'Penalty Records') {
        $dateCondition = reportDateCondition('p.created_at', $date_filter);
        $sql = "SELECT p.penalty_id, p.reason, p.amount, p.status,
                       p.request_id, p.created_at, br.borrower_name,
                       br.borrow_quantity, e.equipment_name
                FROM penalty p
                LEFT JOIN borrower_request br ON p.request_id = br.request_id
                LEFT JOIN equipment e ON br.equipment_id = e.equipment_id
                $dateCondition
                ORDER BY p.created_at DESC, p.penalty_id DESC";

        $result = mysqli_query($connect, $sql);
        if ($result) {
            $details .= "PENALTY RECORDS REPORT\n";
            $details .= "Date Generated: " . date('F d, Y h:i A') . "\n";
            $details .= "Date Range: {$filterLabels[$date_filter]}\n";
            $details .= "========================================\n\n";

            if (mysqli_num_rows($result) === 0) {
                $details .= "No penalty records found for this date range.\n";
            }

            while ($row = mysqli_fetch_assoc($result)) {
                $details .= "Penalty ID: #{$row['penalty_id']}\n";
                $details .= "Request ID: #{$row['request_id']}\n";
                $details .= "Borrower: " . ($row['borrower_name'] ?? 'Unknown') . "\n";
                $details .= "Equipment: " . ($row['equipment_name'] ?? 'Unknown') . "\n";
                $details .= "Borrowed Quantity: " . (int) ($row['borrow_quantity'] ?? 0) . "\n";
                $details .= "Reason: {$row['reason']}\n";
                $details .= "Amount: PHP " . number_format((float) $row['amount'], 2) . "\n";
                $details .= "Status: {$row['status']}\n";
                $details .= "Penalty Created: " . ($row['created_at'] ?? 'Date unavailable') . "\n";
                $details .= "----------------------------------------\n\n";
            }
        }
    }

    if (!$result) {
        error_log('Report query failed: ' . mysqli_error($connect));
        $_SESSION['report_error'] = 'Unable to generate the selected report. Please check the database and try again.';
        header('Location: reports.php');
        exit();
    }

    $insertStatement = mysqli_prepare(
        $connect,
        "INSERT INTO reports (report_type, generated_by, date_generated, details, equipment_id, return_id)
         VALUES (?, ?, NOW(), ?, NULL, NULL)"
    );

    if (!$insertStatement) {
        error_log('Report insert preparation failed: ' . mysqli_error($connect));
        $_SESSION['report_error'] = 'Failed to prepare the report.';
        header('Location: reports.php');
        exit();
    }

    mysqli_stmt_bind_param($insertStatement, 'sis', $report_type, $generated_by, $details);

    if (!mysqli_stmt_execute($insertStatement)) {
        error_log('Report insert failed: ' . mysqli_stmt_error($insertStatement));
        mysqli_stmt_close($insertStatement);
        $_SESSION['report_error'] = 'Failed to save the report. Please try again.';
        header('Location: reports.php');
        exit();
    }

    mysqli_stmt_close($insertStatement);
    $_SESSION['report_success'] = 'Report successfully generated.';
    header('Location: reports.php');
    exit();
}

// Get previously generated reports.
$sql = "SELECT r.report_id, r.report_type, r.date_generated, r.details,
               u.name AS generated_by_name
        FROM reports r
        LEFT JOIN `user` u ON r.generated_by = u.user_id
        ORDER BY r.report_id DESC";
$query = mysqli_query($connect, $sql);
if (!$query) {
    error_log('Reports list query failed: ' . mysqli_error($connect));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - CTEBS Personnel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="../img/barangay-logo.png" alt="Barangay San Miguel Logo">
            <div><strong>CTEBS</strong><span>Community Tool and Equipment Borrowing System</span></div>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php"><span class="menu-icon">⌂</span>Dashboard</a>
            <a href="equipment.php"><span class="menu-icon">▤</span>Equipment</a>
            <a href="transactions.php"><span class="menu-icon">⇄</span>Transactions</a>
            <a href="returns.php"><span class="menu-icon">↩</span>Returns</a>
            <a href="penalties.php"><span class="menu-icon">₱</span>Penalties</a>
            <a href="reports.php" class="active"><span class="menu-icon">▥</span>Reports</a>
        </nav>
        <div class="sidebar-bottom"><a href="../logout.php" class="logout-link"><span class="menu-icon">↪</span>Logout</a></div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div><h1>Reports</h1><p>Generate and view equipment, borrowing, return, and penalty reports.</p></div>
            <div class="admin-profile">
                <div class="profile-avatar"><span>👤</span></div>
                <div class="profile-info"><strong><?php echo htmlspecialchars($_SESSION['name'] ?? 'Personnel'); ?></strong><span>Personnel</span></div>
            </div>
        </header>

        <section class="report-content">
            <?php if (isset($_SESSION['report_success'])): ?>
                <div class="report-message success" role="status"><?php echo htmlspecialchars($_SESSION['report_success']); unset($_SESSION['report_success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['report_error'])): ?>
                <div class="report-message error" role="alert"><?php echo htmlspecialchars($_SESSION['report_error']); unset($_SESSION['report_error']); ?></div>
            <?php endif; ?>

            <div class="report-header">
                <div><h2>Generated Reports</h2><p>Generate and view equipment, borrowing, return, and penalty reports.</p></div>
                <button type="button" class="generate-report-button" onclick="openReportModal()">+ Generate Report</button>
            </div>

            <div class="report-card">
                <div class="table-container">
                    <table>
                        <thead><tr><th>Report ID</th><th>Report Type</th><th>Generated By</th><th>Date Generated</th><th>Details</th></tr></thead>
                        <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($query)): ?>
                                <?php $detailsJson = json_encode((string) ($row['details'] ?? ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
                                <tr>
                                    <td>#<?php echo (int) $row['report_id']; ?></td>
                                    <td><span class="report-type"><?php echo htmlspecialchars($row['report_type'] ?? 'Unknown'); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['generated_by_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo !empty($row['date_generated']) ? date('M d, Y h:i A', strtotime($row['date_generated'])) : '-'; ?></td>
                                    <td><button type="button" class="view-report-button" data-report-details="<?php echo htmlspecialchars($detailsJson ?: '""', ENT_QUOTES, 'UTF-8'); ?>" onclick="viewReport(JSON.parse(this.dataset.reportDetails))">View Report</button></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="empty-state"><?php echo $query ? 'No reports have been generated yet.' : 'Reports could not be loaded. Please try again later.'; ?></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- GENERATE REPORT MODAL -->
<div class="report-modal" id="reportModal">
    <div class="report-modal-box">
        <div class="report-modal-header">
            <div><h2>Generate Report</h2><p>Select the report you want to generate.</p></div>
            <button type="button" class="modal-close" onclick="closeReportModal()" aria-label="Close modal">×</button>
        </div>
        <form method="POST" action="reports.php">
            <div class="form-group">
                <label for="report_type">Report Type</label>
                <select name="report_type" id="report_type" required>
                    <option value="">Select report type</option>
                    <option value="Borrower Report">Borrower Report</option>
                    <option value="Equipment Inventory">Equipment Inventory</option>
                    <option value="Available Tools Report">Available Tools Report</option>
                    <option value="Overdue Equipment Report">Overdue Equipment Report</option>
                    <option value="Damaged Equipment Report">Damaged Equipment Report</option>
                    <option value="Borrowing Transactions">Borrowing Transactions</option>
                    <option value="Return Records">Return Records</option>
                    <option value="Penalty Records">Penalty Records</option>
                </select>
            </div>
            <div class="form-group">
                <label for="date_filter">Date Range</label>
                <select name="date_filter" id="date_filter">
                    <option value="all">All Records</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
                <small>The date range applies to borrower, borrowing, return, penalty, and damaged return records. Equipment Inventory and Available Tools Report show current snapshots. Overdue Equipment Report shows currently overdue borrowed items; the inventory section of Damaged Equipment Report is also a current snapshot.</small>
            </div>
            <div class="report-modal-actions">
                <button type="button" class="cancel-button" onclick="closeReportModal()">Cancel</button>
                <button type="submit" name="generate_report" class="confirm-report-button">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<!-- VIEW REPORT MODAL -->
<div class="report-modal" id="viewReportModal">
    <div class="report-modal-box report-view-box">
        <div class="report-modal-header"><div><h2>Report Details</h2></div><button type="button" class="modal-close" onclick="closeViewReportModal()" aria-label="Close report details">×</button></div>
        <div class="report-details"><pre id="reportDetailsText"></pre></div>
        <div class="report-modal-actions"><button type="button" class="cancel-button" onclick="closeViewReportModal()">Close</button></div>
    </div>
</div>

<script src="../js/script.js"></script>
</body>
</html>
