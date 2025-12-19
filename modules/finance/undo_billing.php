<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin', 'accountant']);

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
  exit;
}

$class_id = intval($_POST['class_id'] ?? 0);
$term_id = intval($_POST['term_id'] ?? 0);

// Validate input
if ($class_id <= 0 || $term_id <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid class or term ID.']);
  exit;
}

try {
  // Start transaction
  mysqli_begin_transaction($conn);

  // Get class and term names for logging
  $sql_info = "SELECT c.class_name, c.section_name, t.term_name, y.year_name 
                 FROM classes c, terms t 
                 JOIN academic_years y ON t.academic_year_id = y.year_id 
                 WHERE c.class_id = ? AND t.term_id = ?";
  $stmt_info = mysqli_prepare($conn, $sql_info);
  mysqli_stmt_bind_param($stmt_info, "ii", $class_id, $term_id);
  mysqli_stmt_execute($stmt_info);
  $result_info = mysqli_stmt_get_result($stmt_info);
  $info = mysqli_fetch_assoc($result_info);

  if (!$info) {
    throw new Exception('Class or term not found.');
  }

  $class_name = "{$info['class_name']} {$info['section_name']}";
  $term_name = "{$info['year_name']} - {$info['term_name']}";

  // Check for invoices with payments
  $sql_check_payments = "SELECT COUNT(*) as count_with_payments, 
                           SUM(paid_amount) as total_paid
                           FROM student_fees sf
                           JOIN students s ON sf.student_id = s.student_id
                           WHERE s.current_class_id = ? 
                           AND sf.term_id = ? 
                           AND sf.paid_amount > 0";

  $stmt_check = mysqli_prepare($conn, $sql_check_payments);
  mysqli_stmt_bind_param($stmt_check, "ii", $class_id, $term_id);
  mysqli_stmt_execute($stmt_check);
  $result_check = mysqli_stmt_get_result($stmt_check);
  $payment_check = mysqli_fetch_assoc($result_check);

  if ($payment_check['count_with_payments'] > 0) {
    throw new Exception("Cannot undo billing: {$payment_check['count_with_payments']} student(s) have made payments totaling " . number_format($payment_check['total_paid'], 2) . ". Please reverse payments first.");
  }

  // Get count and total of invoices to delete
  $sql_count = "SELECT COUNT(*) as invoice_count, 
                  COALESCE(SUM(total_amount), 0) as total_amount
                  FROM student_fees sf
                  JOIN students s ON sf.student_id = s.student_id
                  WHERE s.current_class_id = ? 
                  AND sf.term_id = ? 
                  AND sf.paid_amount = 0";

  $stmt_count = mysqli_prepare($conn, $sql_count);
  mysqli_stmt_bind_param($stmt_count, "ii", $class_id, $term_id);
  mysqli_stmt_execute($stmt_count);
  $result_count = mysqli_stmt_get_result($stmt_count);
  $count_data = mysqli_fetch_assoc($result_count);

  $invoice_count = $count_data['invoice_count'];
  $total_amount = $count_data['total_amount'];

  if ($invoice_count == 0) {
    throw new Exception('No unpaid invoices found for this class and term.');
  }

  // Delete the invoices (payments will cascade delete due to foreign key)
  $sql_delete = "DELETE sf FROM student_fees sf
                   JOIN students s ON sf.student_id = s.student_id
                   WHERE s.current_class_id = ? 
                   AND sf.term_id = ? 
                   AND sf.paid_amount = 0";

  $stmt_delete = mysqli_prepare($conn, $sql_delete);
  mysqli_stmt_bind_param($stmt_delete, "ii", $class_id, $term_id);

  if (!mysqli_stmt_execute($stmt_delete)) {
    throw new Exception('Failed to delete invoices: ' . mysqli_error($conn));
  }

  $deleted_count = mysqli_stmt_affected_rows($stmt_delete);

  // Log the undo operation
  $user_id = $_SESSION['user_id'];
  $details = json_encode([
    'class_name' => $class_name,
    'term_name' => $term_name,
    'deleted_invoices' => $deleted_count,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
  ]);

  $sql_log = "INSERT INTO billing_audit_log 
                (action_type, class_id, term_id, invoices_affected, total_amount, performed_by, details) 
                VALUES ('UNDO_BILLING', ?, ?, ?, ?, ?, ?)";

  $stmt_log = mysqli_prepare($conn, $sql_log);
  mysqli_stmt_bind_param($stmt_log, "iiidis", $class_id, $term_id, $deleted_count, $total_amount, $user_id, $details);

  if (!mysqli_stmt_execute($stmt_log)) {
    throw new Exception('Failed to log audit entry: ' . mysqli_error($conn));
  }

  // Commit transaction
  mysqli_commit($conn);

  echo json_encode([
    'success' => true,
    'message' => "Successfully undone billing for $deleted_count student(s) in $class_name.",
    'deleted_count' => $deleted_count,
    'total_amount' => $total_amount
  ]);

} catch (Exception $e) {
  // Rollback on error
  mysqli_rollback($conn);

  echo json_encode([
    'success' => false,
    'message' => $e->getMessage()
  ]);
}
?>