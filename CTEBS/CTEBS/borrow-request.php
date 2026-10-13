<?php
session_start();
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/includes/db.php';

// Generate a CSRF token to help protect the form.
if (empty($_SESSION['borrow_request_csrf'])) {
    $_SESSION['borrow_request_csrf'] = bin2hex(random_bytes(32));
}

$today = date('Y-m-d');
$errors = [];

$successMessage = $_SESSION['borrow_request_success'] ?? '';
unset($_SESSION['borrow_request_success']);

// Keep submitted values when validation fails.
$form = [
    'borrower_name' => '',
    'borrower_address' => '',
    'borrower_contact' => '',
    'purpose' => '',
    'equipment_id' => '',
    'borrow_quantity' => '1',
    'borrow_date' => '',
    'return_date' => ''
];

function escapeBorrowRequestValue($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function isValidBorrowRequestDate($value)
{
    if (!is_string($value) || $value === '') {
        return false;
    }

    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $value
    );

    return $date && $date->format('Y-m-d') === $value;
}

// Process the submitted form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($form as $field => $defaultValue) {
        $form[$field] = trim(
            (string) ($_POST[$field] ?? $defaultValue)
        );
    }

    // Verify the form token.
    $submittedToken = (string) (
        $_POST['csrf_token'] ?? ''
    );

    if (!hash_equals(
        $_SESSION['borrow_request_csrf'],
        $submittedToken
    )) {
        $errors[] = 'Your form session has expired. Please refresh the page and try again.';
    }

    // Validate resident name.
    $nameLength = function_exists('mb_strlen')
        ? mb_strlen($form['borrower_name'])
        : strlen($form['borrower_name']);

    if (
        $form['borrower_name'] === '' ||
        $nameLength > 100
    ) {
        $errors[] = 'Please enter your full name (up to 100 characters).';
    }

    // Validate address.
    $addressLength = function_exists('mb_strlen')
        ? mb_strlen($form['borrower_address'])
        : strlen($form['borrower_address']);

    if (
        $form['borrower_address'] === '' ||
        $addressLength > 255
    ) {
        $errors[] = 'Please enter your complete address (up to 255 characters).';
    }

    // Validate contact number.
    if (
        $form['borrower_contact'] === '' ||
        strlen($form['borrower_contact']) > 15 ||
        !preg_match(
            '/^[0-9+()\-\s]{7,15}$/',
            $form['borrower_contact']
        )
    ) {
        $errors[] = 'Please enter a valid contact number using 7 to 15 characters.';
    }
    // Validate purpose of borrowing.
    $purposeLength = function_exists('mb_strlen')
        ? mb_strlen($form['purpose'])
        : strlen($form['purpose']);

    if (
        $form['purpose'] === '' ||
        $purposeLength > 500
    ) {
        $errors[] = 'Please enter your purpose of borrowing (up to 500 characters).';
    }

    // Validate equipment selection.
    $equipmentId = filter_var(
        $form['equipment_id'],
        FILTER_VALIDATE_INT,
        [
            'options' => ['min_range' => 1]
        ]
    );

    if ($equipmentId === false) {
        $errors[] = 'Please select an equipment item.';
    }
    $borrowQuantity = filter_var(
        $form['borrow_quantity'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($borrowQuantity === false) {
        $errors[] = 'Please enter a valid quantity of at least 1.';
    }
    // Validate borrow date.
    if (
        !isValidBorrowRequestDate($form['borrow_date']) ||
        $form['borrow_date'] < $today
    ) {
        $errors[] = 'Please choose a valid borrow date that is today or later.';
    }

    // Validate return date.
    if (!isValidBorrowRequestDate($form['return_date'])) {
        $errors[] = 'Please choose a valid expected return date.';
    } elseif (
        isValidBorrowRequestDate($form['borrow_date']) &&
        $form['return_date'] < $form['borrow_date']
    ) {
        $errors[] = 'The return date must be the same as or later than the borrow date.';
    }

    // Check that the selected equipment is still available.
    if (!$errors) {
    $equipmentStatement = mysqli_prepare(
        $connect,
        "SELECT
            e.equipment_id,
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
         WHERE e.equipment_id = ?
           AND e.status = 'Active'
         LIMIT 1"
    );

    if (!$equipmentStatement) {
        $errors[] = 'Unable to check equipment availability.';
    } else {
        mysqli_stmt_bind_param(
            $equipmentStatement,
            'i',
            $equipmentId
        );

        mysqli_stmt_execute($equipmentStatement);

        $equipmentResult = mysqli_stmt_get_result(
            $equipmentStatement
        );

        $availableEquipment = mysqli_fetch_assoc(
            $equipmentResult
        );

        mysqli_stmt_close($equipmentStatement);

        if (!$availableEquipment) {
            $errors[] = 'The selected equipment does not exist or is inactive.';
        } else {
            $availableQuantity = (int)
                $availableEquipment['available_quantity'];

            if ($availableQuantity < 1) {
                $errors[] = 'This equipment is currently unavailable.';
            } elseif ($borrowQuantity > $availableQuantity) {
                $errors[] = "Only {$availableQuantity} unit(s) are available.";
            }
        }
    }
}

    // Save the request if there are no validation errors.
    if (empty($errors)) {

        $insertStatement = mysqli_prepare(
            $connect,
            "INSERT INTO borrower_request
                (
                    borrower_name,
                    borrower_address,
                    borrower_contact,
                    purpose,
                    request_date,
                    borrow_date,
                    return_date,
                    status,
                    user_id,
                    equipment_id,
                    borrow_quantity
                )
            VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 'Pending', NULL, ?, ?)"
        );

        if (!$insertStatement) {

            error_log(
                'Borrow request preparation failed: ' .
                mysqli_error($connect)
            );

            $errors[] = 'We could not submit your request right now. Please try again later.';

        } else {

            mysqli_stmt_bind_param(
                $insertStatement,
                "ssssssii",
                $form['borrower_name'],
                $form['borrower_address'],
                $form['borrower_contact'],
                $form['purpose'],
                $form['borrow_date'],
                $form['return_date'],
                $equipmentId,
                $borrowQuantity
            );

            if (mysqli_stmt_execute($insertStatement)) {

                $newRequestId = mysqli_insert_id($connect);

                mysqli_stmt_close($insertStatement);

                $_SESSION['borrow_request_success'] =
                    'Your borrowing request was submitted successfully. Reference number: #' .
                    $newRequestId .
                    '. Your request is now Pending for staff review.';

                // Generate a fresh token after submission.
                $_SESSION['borrow_request_csrf'] =
                    bin2hex(random_bytes(32));

                // Prevent duplicate submissions when the page refreshes.
                header('Location: borrow-request.php');
                exit();

            } else {

                error_log(
                    'Borrow request insert failed: ' .
                    mysqli_stmt_error($insertStatement)
                );

                mysqli_stmt_close($insertStatement);

                $errors[] = 'We could not submit your request right now. Please check your details and try again.';
            }
        }
    }
}

// Retrieve available equipment for the dropdown.
$equipmentOptions = [];

$equipmentQuery = mysqli_query(
    $connect,
    "SELECT
        e.equipment_id,
        e.equipment_name,
        e.category,
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
     WHERE e.status = 'Active'
     HAVING available_quantity > 0
     ORDER BY e.equipment_name ASC"
);

if ($equipmentQuery) {
    while ($equipment = mysqli_fetch_assoc($equipmentQuery)) {
        $equipmentOptions[] = $equipment;
    }
} else {
    error_log(
        'Equipment list query failed: ' . mysqli_error($connect)
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

    <title>Borrowing Request | CTEBS</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <main class="login-container borrow-request-page">

        <section
            class="login-box"
            aria-labelledby="borrow-request-title"
        >

            <div class="logo">
                <img
                    src="img/barangay-logo.png"
                    alt="Barangay San Miguel Logo"
                >
            </div>

            <h2 id="borrow-request-title">
                Equipment Borrowing Request
            </h2>

            <p class="subtitle">
                Community Tool and Equipment Borrowing System
                <br>
                Barangay San Miguel, Iligan City
            </p>

            <!-- Success message -->
            <?php if ($successMessage !== ''): ?>

                <div class="success-message" role="status">
                    <?php
                    echo escapeBorrowRequestValue(
                        $successMessage
                    );
                    ?>
                </div>

            <?php endif; ?>

            <!-- Validation errors -->
            <?php if (!empty($errors)): ?>

                <div class="error-message" role="alert">

                    <?php foreach ($errors as $error): ?>

                        <p>
                            <?php
                            echo escapeBorrowRequestValue(
                                $error
                            );
                            ?>
                        </p>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

            <!-- Equipment availability message -->
            <?php if (!$equipmentQuery): ?>

                <p class="error-message">
                    Equipment choices are unavailable right now.
                    Please try again later.
                </p>

            <?php elseif (count($equipmentOptions) === 0): ?>

                <p class="error-message">
                    There is currently no available equipment
                    to request. Please check again later.
                </p>

            <?php endif; ?>

            <!-- Resident borrowing form -->
            <form
                action="borrow-request.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php
                    echo escapeBorrowRequestValue(
                        $_SESSION['borrow_request_csrf']
                    );
                    ?>"
                >

                <div class="form-group">

                    <label for="borrower_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="borrower_name"
                        name="borrower_name"
                        maxlength="100"
                        placeholder="Enter your full name"
                        value="<?php
                        echo escapeBorrowRequestValue(
                            $form['borrower_name']
                        );
                        ?>"
                        autocomplete="name"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="borrower_address">
                        Complete Address
                    </label>

                    <input
                        type="text"
                        id="borrower_address"
                        name="borrower_address"
                        maxlength="255"
                        placeholder="House number, street, and barangay"
                        value="<?php
                        echo escapeBorrowRequestValue(
                            $form['borrower_address']
                        );
                        ?>"
                        autocomplete="street-address"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="borrower_contact">
                        Contact Number
                    </label>

                    <input
                        type="tel"
                        id="borrower_contact"
                        name="borrower_contact"
                        maxlength="15"
                        minlength="7"
                        pattern="[0-9+() -]{7,15}"
                        placeholder="e.g. 09171234567"
                        value="<?php
                        echo escapeBorrowRequestValue(
                            $form['borrower_contact']
                        );
                        ?>"
                        autocomplete="tel"
                        inputmode="tel"
                        required
                    >

                </div>
                <div class="form-group">

                    <label for="purpose">
                        Purpose of Borrowing
                    </label>

                    <textarea
                        id="purpose"
                        name="purpose"
                        rows="3"
                        maxlength="500"
                        placeholder="Explain why you need to borrow this equipment"
                        required
                    ><?php
                        echo escapeBorrowRequestValue($form['purpose']);
                    ?></textarea>

                </div>
                <div class="form-group">

                    <label for="equipment_id">
                        Equipment to Borrow
                    </label>

                    <select
                        id="equipment_id"
                        name="equipment_id"
                        required
                    >

                        <option value="">
                            Select available equipment
                        </option>

                        <?php foreach ($equipmentOptions as $equipment): ?>
                        <option
                            value="<?php echo (int) $equipment['equipment_id']; ?>"
                            data-available="<?php echo (int) $equipment['available_quantity']; ?>"
                            <?php
                            echo (
                                (string) $form['equipment_id'] ===
                                (string) $equipment['equipment_id']
                            ) ? 'selected' : '';
                            ?>
                        >
                            <?php
                            echo escapeBorrowRequestValue(
                                $equipment['equipment_name']
                            );

                            if (!empty($equipment['category'])) {
                                echo ' - ' . escapeBorrowRequestValue(
                                    $equipment['category']
                                );
                            }

                            echo ' (Available: ' .
                                (int) $equipment['available_quantity'] . ')';
                            ?>
                        </option>
                        <?php endforeach; ?>

                    </select>

                </div>
                <div class="form-group">
                <label for="borrow_quantity">Quantity</label>

                <input
                    type="number"
                    id="borrow_quantity"
                    name="borrow_quantity"
                    min="1"
                    value="<?php echo escapeBorrowRequestValue($form['borrow_quantity']); ?>"
                    required
                >

                <small id="quantity-help" class="quantity-help">
                    Select equipment to see the available quantity.
                </small>
                </div>                
                <div class="form-group">

                    <label for="borrow_date">
                        Borrow Date
                    </label>

                    <input
                        type="date"
                        id="borrow_date"
                        name="borrow_date"
                        min="<?php
                        echo escapeBorrowRequestValue($today);
                        ?>"
                        value="<?php
                        echo escapeBorrowRequestValue(
                            $form['borrow_date']
                        );
                        ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="return_date">
                        Expected Return Date
                    </label>

                    <input
                        type="date"
                        id="return_date"
                        name="return_date"
                        min="<?php
                        echo escapeBorrowRequestValue(
                            $form['borrow_date'] !== ''
                                ? $form['borrow_date']
                                : $today
                        );
                        ?>"
                        value="<?php
                        echo escapeBorrowRequestValue(
                            $form['return_date']
                        );
                        ?>"
                        required
                    >

                </div>

                <button
                    type="submit"
                    name="submit_borrow_request"
                    <?php
                    echo (
                        !$equipmentQuery ||
                        count($equipmentOptions) === 0
                    ) ? 'disabled' : '';
                    ?>
                >
                    Submit Borrowing Request
                </button>

            </form>

            <div class="login-footer">
                <p>
                    Your request will be marked Pending until
                    reviewed by barangay staff.
                </p>
            </div>

            <p class="borrow-request-back">
                <a href="index.php">
                    Back to Staff Login
                </a>
            </p>

        </section>

    </main>
<script src="../js/script.js"></script>
</body>
</html>