<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Invalid request method']);
  exit();
}

$action = $_POST['action'] ?? '';
$student_id = intval($_POST['student_id'] ?? 0);

if ($action === 'delete') {
  if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit();
  }

  // Start transaction for data integrity
  mysqli_begin_transaction($conn);

  try {
    // 1. Delete attendance records
    $sql_attendance = "DELETE FROM attendance WHERE student_id = ?";
    $stmt_attendance = mysqli_prepare($conn, $sql_attendance);
    mysqli_stmt_bind_param($stmt_attendance, "i", $student_id);
    mysqli_stmt_execute($stmt_attendance);
    $attendance_deleted = mysqli_stmt_affected_rows($stmt_attendance);
    mysqli_stmt_close($stmt_attendance);

    // 2. Delete payment records (from student_fees table)
    $sql_fees = "DELETE FROM student_fees WHERE student_id = ?";
    $stmt_fees = mysqli_prepare($conn, $sql_fees);
    mysqli_stmt_bind_param($stmt_fees, "i", $student_id);
    mysqli_stmt_execute($stmt_fees);
    $fees_deleted = mysqli_stmt_affected_rows($stmt_fees);
    mysqli_stmt_close($stmt_fees);

    // 3. Delete exam results if they exist
    $sql_results = "DELETE FROM exam_results WHERE student_id = ?";
    if ($stmt_results = mysqli_prepare($conn, $sql_results)) {
      mysqli_stmt_bind_param($stmt_results, "i", $student_id);
      mysqli_stmt_execute($stmt_results);
      $results_deleted = mysqli_stmt_affected_rows($stmt_results);
      mysqli_stmt_close($stmt_results);
    } else {
      $results_deleted = 0; // Table might not exist
    }

    // 4. Soft delete the student record (mark as Deleted)
    $sql_student = "UPDATE students SET status = 'Deleted' WHERE student_id = ?";
    $stmt_student = mysqli_prepare($conn, $sql_student);

    if ($stmt_student === false) {
      throw new Exception('Database preparation error: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt_student, "i", $student_id);

    if (!mysqli_stmt_execute($stmt_student)) {
      throw new Exception('Database execution error: ' . mysqli_error($conn));
    }

    if (mysqli_stmt_affected_rows($stmt_student) === 0) {
      throw new Exception('Student not found or already deleted');
    }

    mysqli_stmt_close($stmt_student);

    // Commit the transaction
    mysqli_commit($conn);

    // Build detailed success message
    $details = [];
    if ($attendance_deleted > 0)
      $details[] = "$attendance_deleted attendance record(s)";
    if ($fees_deleted > 0)
      $details[] = "$fees_deleted invoice(s)/payment(s)";
    if ($results_deleted > 0)
      $details[] = "$results_deleted exam result(s)";

    $message = 'Student deleted successfully';
    if (!empty($details)) {
      $message .= '. Removed: ' . implode(', ', $details);
    }

    echo json_encode(['success' => true, 'message' => $message]);
  } catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }

  exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>