<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
require_once '../../config/database.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

$page_title = "Return Book";
include '../../includes/header.php';

// Handle Return
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
  $issuance_id = intval($_POST['issuance_id']);

  if ($issuance_id > 0) {
    $check = mysqli_query($conn, "SELECT book_id, status FROM library_issuances WHERE issuance_id = $issuance_id");
    $issuance = mysqli_fetch_assoc($check);

    if ($issuance && $issuance['status'] == 'Issued') {
      mysqli_begin_transaction($conn);
      try {
        // Update issuance
        mysqli_query($conn, "UPDATE library_issuances SET return_date = CURRENT_DATE, status = 'Returned' WHERE issuance_id = $issuance_id");

        // Update book stock
        mysqli_query($conn, "UPDATE library_books SET available_copies = available_copies + 1 WHERE book_id = " . $issuance['book_id']);

        mysqli_commit($conn);
        $message = "Book returned successfully!";
        $message_type = "success";
      } catch (Exception $e) {
        mysqli_rollback($conn);
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
      }
    }
  }
}

// Fetch active issuances
$sql = "SELECT i.*, b.title, s.first_name, s.last_name, s.admission_number 
        FROM library_issuances i 
        JOIN library_books b ON i.book_id = b.book_id 
        JOIN students s ON i.student_id = s.student_id 
        WHERE i.status = 'Issued' 
        ORDER BY i.issue_date DESC";
$issuances = mysqli_query($conn, $sql);
?>

<style>
  .return-container {
    padding: 10px 0;
  }

  .return-header {
    margin-bottom: 32px;
  }

  .return-header h1 {
    font-size: 28px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
  }

  .return-header p {
    color: #64748b;
    margin: 4px 0 0 0;
  }

  .return-panel {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .return-table {
    width: 100%;
    border-collapse: collapse;
  }

  .return-table th {
    text-align: left;
    padding: 16px 24px;
    background: #f8fafc;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    border-bottom: 1px solid #e2e8f0;
  }

  .return-table td {
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 14px;
    color: #1e293b;
  }

  .due-tag {
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 12px;
  }

  .due-normal {
    background: #f0fdf4;
    color: #166534;
  }

  .due-late {
    background: #fef2f2;
    color: #991b1b;
  }

  .btn-return-action {
    padding: 8px 16px;
    background: #27ae60;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    transition: all 200ms ease;
  }

  .btn-return-action:hover {
    background: #219150;
    transform: translateY(-1px);
  }
</style>

<div class="return-container">
  <div class="return-header">
    <a href="index.php"
      style="display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-weight: 600; font-size: 13px; margin-bottom: 12px; transition: color 0.2s;"
      onmouseover="this.style.color='#3498db'" onmouseout="this.style.color='#64748b'">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M19 12H5M12 19l-7-7 7-7" />
      </svg>
      Back to Dashboard
    </a>
    <h1>Return Book</h1>
    <p>Manage and record book returns from students.</p>
  </div>

  <div class="return-panel">
    <table class="return-table">
      <thead>
        <tr>
          <th>Student</th>
          <th>Book Title</th>
          <th>Issue Date</th>
          <th>Due Date</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($issuances) > 0): ?>
          <?php while ($row = mysqli_fetch_assoc($issuances)):
            $is_late = strtotime($row['due_date']) < time();
            ?>
            <tr>
              <td>
                <div style="font-weight: 700;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                </div>
                <div style="font-size: 11px; color: #64748b;"><?php echo $row['admission_number']; ?></div>
              </td>
              <td style="font-weight: 600; color: #3498db;"><?php echo htmlspecialchars($row['title']); ?></td>
              <td><?php echo date('M d, Y', strtotime($row['issue_date'])); ?></td>
              <td>
                <span class="due-tag <?php echo $is_late ? 'due-late' : 'due-normal'; ?>">
                  <?php echo date('M d, Y', strtotime($row['due_date'])); ?>
                  <?php if ($is_late)
                    echo ' (LATE)'; ?>
                </span>
              </td>
              <td style="text-align: right;">
                <form action="return_book.php" method="POST" style="display: inline;">
                  <input type="hidden" name="issuance_id" value="<?php echo $row['issuance_id']; ?>">
                  <button type="submit" name="return_book" class="btn-return-action">Mark Returned</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="text-align: center; padding: 60px; color: #94a3b8;">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                style="margin-bottom: 16px; opacity: 0.3;">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                <polyline points="22 4 12 14.01 9 11.01" />
              </svg>
              <p>No books are currently issued.</p>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  <?php if (!empty($message)): ?>
    showToast("<?php echo $message; ?>", "<?php echo $message_type; ?>");
  <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>