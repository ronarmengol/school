<?php
require_once '../../includes/auth_functions.php';

header('Content-Type: application/json');

check_auth();
check_role(['super_admin', 'admin', 'accountant']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_invoice'])) {
    $invoice_id = $_POST['invoice_id'] ?? 0;
    $amount_paying = floatval($_POST['amount'] ?? 0);
    $method = $_POST['payment_method'] ?? 'Cash';
    $reference_number = $_POST['invoice_ref'] ?? null; // Get reference number from form

    if ($invoice_id <= 0 || $amount_paying <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid invoice ID or amount.'
        ]);
        exit;
    }

    // Check if invoice exists and get current status
    $sql_check = "SELECT total_amount, paid_amount FROM student_fees WHERE invoice_id = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "i", $invoice_id);
    mysqli_stmt_execute($stmt_check);
    $res_check = mysqli_stmt_get_result($stmt_check);

    if (!$inv = mysqli_fetch_assoc($res_check)) {
        echo json_encode(['success' => false, 'message' => 'Invoice not found.']);
        exit;
    }

    $current_balance = $inv['total_amount'] - $inv['paid_amount'];
    if ($amount_paying > $current_balance + 0.01) { // Floating point tolerance
        echo json_encode(['success' => false, 'message' => 'Payment amount exceeds balance.']);
        exit;
    }

    mysqli_begin_transaction($conn);

    try {
        // 1. Record Payment with reference number
        $stmt_pay = mysqli_prepare($conn, "INSERT INTO payments (invoice_id, amount, payment_method, reference_number, recorded_by) VALUES (?, ?, ?, ?, ?)");
        $uid = $_SESSION['user_id'];
        mysqli_stmt_bind_param($stmt_pay, "idssi", $invoice_id, $amount_paying, $method, $reference_number, $uid);

        if (!mysqli_stmt_execute($stmt_pay)) {
            throw new Exception("Failed to record payment: " . mysqli_error($conn));
        }

        // 2. Update Invoice
        $stmt_update = mysqli_prepare($conn, "UPDATE student_fees SET paid_amount = paid_amount + ? WHERE invoice_id = ?");
        mysqli_stmt_bind_param($stmt_update, "di", $amount_paying, $invoice_id);

        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Failed to update invoice balance: " . mysqli_error($conn));
        }

        // 3. Check and Update Status
        $res = mysqli_query($conn, "SELECT total_amount, paid_amount FROM student_fees WHERE invoice_id = $invoice_id");
        $inv_new = mysqli_fetch_assoc($res);
        $status = 'Unpaid';
        if ($inv_new['paid_amount'] >= $inv_new['total_amount'] - 0.01)
            $status = 'Paid';
        elseif ($inv_new['paid_amount'] > 0)
            $status = 'Partial';

        mysqli_query($conn, "UPDATE student_fees SET status = '$status' WHERE invoice_id = $invoice_id");

        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => 'Payment recorded successfully.']);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>