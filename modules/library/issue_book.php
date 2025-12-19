<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
require_once '../../config/database.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

$page_title = "Issue Book";
include '../../includes/header.php';

// Handle Issuance
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_book'])) {
  $book_id = intval($_POST['book_id']);
  $student_id = intval($_POST['student_id']);
  $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
  $recorded_by = $_SESSION['user_id'];

  if ($book_id <= 0 || $student_id <= 0 || empty($due_date)) {
    $message = "Please select a student, a book, and a due date.";
    $message_type = "error";
  } else {
    // Check availability
    $check = mysqli_query($conn, "SELECT available_copies FROM library_books WHERE book_id = $book_id");
    $book = mysqli_fetch_assoc($check);

    if ($book['available_copies'] <= 0) {
      $message = "This book is currently out of stock.";
      $message_type = "error";
    } else {
      mysqli_begin_transaction($conn);
      try {
        // Insert issuance
        $sql = "INSERT INTO library_issuances (book_id, student_id, issue_date, due_date, recorded_by, status) VALUES (?, ?, CURRENT_DATE, ?, ?, 'Issued')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iisi", $book_id, $student_id, $due_date, $recorded_by);
        mysqli_stmt_execute($stmt);

        // Update available copies
        mysqli_query($conn, "UPDATE library_books SET available_copies = available_copies - 1 WHERE book_id = $book_id");

        mysqli_commit($conn);
        $message = "Book issued successfully!";
        $message_type = "success";
      } catch (Exception $e) {
        mysqli_rollback($conn);
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
      }
    }
  }
}

// Fetch available books
$books = mysqli_query($conn, "SELECT book_id, title, available_copies FROM library_books WHERE available_copies > 0 ORDER BY title ASC");

// Fetch active students
$students = mysqli_query($conn, "SELECT student_id, first_name, last_name, admission_number FROM students WHERE status = 'Active' ORDER BY first_name ASC");
?>

<style>
  .issue-container {
    max-width: 800px;
    margin: 20px auto;
  }

  .issue-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .card-header {
    padding: 24px;
    border-bottom: 1px solid #e2e8f0;
    background: #fafafa;
  }

  .card-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: #1e293b;
  }

  .card-body {
    padding: 32px;
  }

  .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
  }

  .form-group.full {
    grid-column: span 2;
  }

  .form-group label {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: #475569;
    margin-bottom: 8px;
  }

  .form-control {
    width: 100%;
    padding: 14px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    background: #f8fafc;
  }

  .form-control:focus {
    outline: none;
    border-color: #3498db;
    background: white;
    box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
  }

  .btn-issue {
    width: 100%;
    padding: 16px;
    background: #2c3e50;
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    transition: all 200ms ease;
    margin-top: 10px;
  }

  .btn-issue:hover {
    background: #1a252f;
    transform: translateY(-2px);
  }
</style>

<div class="issue-container">
  <div style="margin-bottom: 24px;">
    <a href="index.php"
      style="display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-weight: 600; font-size: 13px; margin-bottom: 12px; transition: color 0.2s;"
      onmouseover="this.style.color='#3498db'" onmouseout="this.style.color='#64748b'">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M19 12H5M12 19l-7-7 7-7" />
      </svg>
      Back to Dashboard
    </a>
    <h1 style="font-size: 28px; font-weight: 800; color: #1e293b; margin: 0;">Issue Book</h1>
    <p style="color: #64748b; margin: 4px 0 0 0;">Record a new book issuance to a student.</p>
  </div>

  <div class="issue-card">
    <div class="card-header">
      <h2>Issuance Details</h2>
    </div>
    <form action="issue_book.php" method="POST" class="card-body">
      <div class="form-grid">
        <div class="form-group full">
          <label>Select Student</label>
          <select name="student_id" class="form-control" required id="student_select">
            <option value="">Search Student...</option>
            <?php while ($s = mysqli_fetch_assoc($students)): ?>
              <option value="<?php echo $s['student_id']; ?>">
                <?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name'] . ' (' . $s['admission_number'] . ')'); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group full">
          <label>Select Book</label>
          <select name="book_id" class="form-control" required id="book_select">
            <option value="">Search Book...</option>
            <?php while ($b = mysqli_fetch_assoc($books)): ?>
              <option value="<?php echo $b['book_id']; ?>">
                <?php echo htmlspecialchars($b['title'] . ' (' . $b['available_copies'] . ' available)'); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Issue Date</label>
          <input type="text" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly
            style="background: #f1f5f9;">
        </div>

        <div class="form-group">
          <label>Due Date</label>
          <input type="date" name="due_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>"
            value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
        </div>
      </div>

      <button type="submit" name="issue_book" class="btn-issue">
        Confirm Issuance
      </button>
    </form>
  </div>
</div>

<script>
  <?php if (!empty($message)): ?>
    showToast("<?php echo $message; ?>", "<?php echo $message_type; ?>");
  <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>