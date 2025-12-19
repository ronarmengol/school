<?php
require_once '../../includes/auth_functions.php';
check_auth();

// Debug: Show current user role
$current_role = $_SESSION['role'] ?? 'unknown';

// Check if user is super_admin
if ($current_role !== 'super_admin') {
    die("<h1>Access Denied</h1><p>Only super administrators can access this page.</p><p>Your role: $current_role</p><p><a href='index.php'>Go Back</a></p>");
}

$confirmed = $_POST['confirmed'] ?? false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $confirmed === 'yes') {
  // Start transaction
  mysqli_begin_transaction($conn);

  try {
    // 1. Delete all attendance records
    $result1 = mysqli_query($conn, "DELETE FROM attendance");
    $attendance_count = mysqli_affected_rows($conn);

    // 2. Delete all student fees/invoices
    $result2 = mysqli_query($conn, "DELETE FROM student_fees");
    $fees_count = mysqli_affected_rows($conn);

    // 3. Delete all exam results (if table exists)
    $exam_count = 0;
    $result3 = mysqli_query($conn, "DELETE FROM exam_results");
    if ($result3) {
      $exam_count = mysqli_affected_rows($conn);
    }

    // 4. Delete all students
    $result4 = mysqli_query($conn, "DELETE FROM students");
    $students_count = mysqli_affected_rows($conn);

    // Commit transaction
    mysqli_commit($conn);

    $success = "All student data has been permanently deleted!<br>";
    $success .= "- Students removed: $students_count<br>";
    $success .= "- Attendance records removed: $attendance_count<br>";
    $success .= "- Invoices/Payments removed: $fees_count<br>";
    if ($exam_count > 0) {
      $success .= "- Exam results removed: $exam_count<br>";
    }

  } catch (Exception $e) {
    mysqli_rollback($conn);
    $error = "Error: " . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html>

<head>
  <title>Delete All Students - DANGER ZONE</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      max-width: 800px;
      margin: 50px auto;
      padding: 20px;
      background: #f5f5f5;
    }

    .container {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .danger-zone {
      background: #fee2e2;
      border: 2px solid #ef4444;
      border-radius: 8px;
      padding: 20px;
      margin: 20px 0;
    }

    .warning {
      color: #991b1b;
      font-weight: bold;
      font-size: 18px;
      margin-bottom: 15px;
    }

    .btn {
      padding: 12px 24px;
      border-radius: 8px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      font-size: 16px;
      margin: 10px 5px;
    }

    .btn-danger {
      background: #ef4444;
      color: white;
    }

    .btn-danger:hover {
      background: #dc2626;
    }

    .btn-secondary {
      background: #6b7280;
      color: white;
    }

    .btn-secondary:hover {
      background: #4b5563;
    }

    .success {
      background: #d1fae5;
      border: 2px solid #10b981;
      color: #065f46;
      padding: 20px;
      border-radius: 8px;
      margin: 20px 0;
    }

    .error {
      background: #fee2e2;
      border: 2px solid #ef4444;
      color: #991b1b;
      padding: 20px;
      border-radius: 8px;
      margin: 20px 0;
    }

    ul {
      margin: 15px 0;
      padding-left: 20px;
    }

    li {
      margin: 8px 0;
    }
  </style>
</head>

<body>
  <div class="container">
    <h1 style="color: #ef4444;">⚠️ DANGER ZONE</h1>

    <?php if (isset($success)): ?>
      <div class="success">
        <h2>✓ Operation Completed</h2>
        <?php echo $success; ?>
        <p style="margin-top: 20px;">
          <a href="index.php" class="btn btn-secondary">Go to Students Page</a>
          <a href="../dashboard/index.php" class="btn btn-secondary">Go to Dashboard</a>
        </p>
      </div>
    <?php elseif (isset($error)): ?>
      <div class="error">
        <h2>✗ Operation Failed</h2>
        <?php echo $error; ?>
        <p style="margin-top: 20px;">
          <a href="delete_all_students.php" class="btn btn-secondary">Try Again</a>
        </p>
      </div>
    <?php else: ?>
      <div class="danger-zone">
        <div class="warning">⚠️ WARNING: This action is PERMANENT and IRREVERSIBLE!</div>

        <p style="font-size: 16px; line-height: 1.6;">
          You are about to <strong>permanently delete ALL students</strong> and their associated data from the database.
        </p>

        <p style="font-weight: bold; margin-top: 15px;">This will remove:</p>
        <ul>
          <li>All student records</li>
          <li>All attendance history</li>
          <li>All invoices and payment records</li>
          <li>All exam results</li>
          <li>Any other student-related data</li>
        </ul>

        <p style="color: #991b1b; font-weight: bold; margin-top: 20px;">
          ⚠️ There is NO UNDO for this operation!
        </p>

        <p style="margin-top: 20px;">Are you absolutely sure you want to proceed?</p>
      </div>

      <form method="POST"
        onsubmit="return confirm('FINAL WARNING: This will delete ALL students and their data permanently. Are you ABSOLUTELY SURE?');">
        <input type="hidden" name="confirmed" value="yes">
        <button type="submit" class="btn btn-danger">
          🗑️ YES, DELETE ALL STUDENTS
        </button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
      </form>
    <?php endif; ?>
  </div>
</body>

</html>