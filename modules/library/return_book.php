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
        ORDER BY i.due_date ASC";
$issuances = mysqli_query($conn, $sql);
$total_issued = mysqli_num_rows($issuances);

// Count overdue books
$overdue_sql = "SELECT COUNT(*) as count FROM library_issuances WHERE status = 'Issued' AND due_date < CURRENT_DATE";
$overdue_result = mysqli_query($conn, $overdue_sql);
$overdue_count = mysqli_fetch_assoc($overdue_result)['count'] ?? 0;

// Count due today
$today_sql = "SELECT COUNT(*) as count FROM library_issuances WHERE status = 'Issued' AND due_date = CURRENT_DATE";
$today_result = mysqli_query($conn, $today_sql);
$due_today = mysqli_fetch_assoc($today_result)['count'] ?? 0;
?>

<style>
  :root {
    --lib-primary: #10b981;
    --lib-primary-dark: #059669;
    --lib-primary-light: #6ee7b7;
    --lib-secondary: #0ea5e9;
    --lib-success: #10b981;
    --lib-warning: #f59e0b;
    --lib-danger: #ef4444;
    --lib-bg: #f8fafc;
    --lib-card: #ffffff;
    --lib-border: #e2e8f0;
    --lib-text: #1e293b;
    --lib-muted: #64748b;
    --lib-light: #94a3b8;
  }

  .return-page {
    min-height: 100vh;
    padding-bottom: 60px;
  }

  .return-container {
    max-width: 1200px;
    margin: 0 auto;
  }

  /* Header Section */
  .page-header {
    background: linear-gradient(135deg, var(--lib-primary) 0%, var(--lib-primary-dark) 100%);
    color: white;
    padding: 32px;
    border-radius: 20px;
    margin-bottom: 32px;
    position: relative;
    overflow: hidden;
  }

  .page-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
  }

  .page-header::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: 20%;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
  }

  .header-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .header-text h1 {
    font-size: 28px;
    font-weight: 800;
    margin: 0 0 8px 0;
    letter-spacing: -0.5px;
  }

  .header-text p {
    font-size: 15px;
    opacity: 0.9;
    margin: 0;
  }

  .btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s ease;
    backdrop-filter: blur(10px);
  }

  .btn-back:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
  }

  /* Stats Cards */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 32px;
  }

  .stat-card {
    background: var(--lib-card);
    padding: 24px;
    border-radius: 16px;
    border: 1px solid var(--lib-border);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.2s ease;
  }

  .stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px -10px rgba(16, 185, 129, 0.2);
  }

  .stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .stat-icon.issued {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #2563eb;
  }

  .stat-icon.overdue {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #dc2626;
  }

  .stat-icon.today {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706;
  }

  .stat-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--lib-text);
    line-height: 1;
  }

  .stat-label {
    font-size: 13px;
    color: var(--lib-muted);
    margin-top: 4px;
    font-weight: 500;
  }

  /* Table Card */
  .table-card {
    background: var(--lib-card);
    border-radius: 20px;
    border: 1px solid var(--lib-border);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    overflow: hidden;
  }

  .card-header {
    padding: 28px 32px;
    border-bottom: 1px solid var(--lib-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(to right, #fafafb, #f8fafc);
  }

  .card-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .card-header-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--lib-primary) 0%, var(--lib-primary-dark) 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
  }

  .card-header-text h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: var(--lib-text);
  }

  .card-header-text p {
    margin: 4px 0 0 0;
    font-size: 14px;
    color: var(--lib-muted);
  }

  .record-count {
    background: var(--lib-bg);
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    color: var(--lib-muted);
  }

  .record-count strong {
    color: var(--lib-text);
  }

  /* Table Styling */
  .return-table {
    width: 100%;
    border-collapse: collapse;
  }

  .return-table th {
    text-align: left;
    padding: 16px 24px;
    background: #f8fafc;
    color: var(--lib-muted);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--lib-border);
  }

  .return-table td {
    padding: 20px 24px;
    border-bottom: 1px solid var(--lib-border);
    font-size: 14px;
    color: var(--lib-text);
    vertical-align: middle;
  }

  .return-table tr:hover {
    background: linear-gradient(90deg, #f0fdf4 0%, transparent 50%);
  }

  .return-table tr:last-child td {
    border-bottom: none;
  }

  /* Student Cell */
  .student-cell {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .student-avatar {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2563eb;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
  }

  .student-info .name {
    font-weight: 700;
    color: var(--lib-text);
    margin-bottom: 2px;
  }

  .student-info .admission {
    font-size: 12px;
    color: var(--lib-muted);
    font-family: monospace;
  }

  /* Book Title */
  .book-title {
    font-weight: 600;
    color: #2563eb;
    max-width: 250px;
  }

  /* Date Styling */
  .date-cell {
    font-size: 13px;
    color: var(--lib-muted);
  }

  .date-cell strong {
    display: block;
    color: var(--lib-text);
    font-weight: 600;
  }

  /* Due Tag */
  .due-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 12px;
  }

  .due-tag svg {
    width: 14px;
    height: 14px;
  }

  .due-normal {
    background: #dcfce7;
    color: #16a34a;
  }

  .due-late {
    background: #fee2e2;
    color: #dc2626;
  }

  .due-today {
    background: #fef3c7;
    color: #d97706;
  }

  /* Action Button */
  .btn-return-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: linear-gradient(135deg, var(--lib-primary) 0%, var(--lib-primary-dark) 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
  }

  .btn-return-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
  }

  .btn-return-action:active {
    transform: translateY(0);
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 80px 40px;
  }

  .empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: #dcfce7;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--lib-primary);
  }

  .empty-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--lib-text);
    margin-bottom: 8px;
  }

  .empty-text {
    font-size: 14px;
    color: var(--lib-muted);
  }

  /* Responsive */
  @media (max-width: 1024px) {
    .return-table {
      display: block;
      overflow-x: auto;
    }
  }

  @media (max-width: 768px) {
    .return-container {
      padding: 0 16px;
    }

    .page-header {
      padding: 24px;
      border-radius: 16px;
    }

    .header-content {
      flex-direction: column;
      align-items: flex-start;
      gap: 16px;
    }

    .stats-row {
      grid-template-columns: 1fr;
    }

    .card-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 16px;
    }
  }
</style>

<div class="return-page">
  <div class="return-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1>Return Book</h1>
          <p>Manage and record book returns from students. View all currently issued books below.</p>
        </div>
        <a href="index.php" class="btn-back">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Back to Library
        </a>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon issued">
          <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
        </div>
        <div>
          <div class="stat-value"><?php echo $total_issued; ?></div>
          <div class="stat-label">Books Currently Issued</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon overdue">
          <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <div class="stat-value"><?php echo $overdue_count; ?></div>
          <div class="stat-label">Overdue Returns</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon today">
          <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <div class="stat-value"><?php echo $due_today; ?></div>
          <div class="stat-label">Due Today</div>
        </div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">
      <div class="card-header">
        <div class="card-header-left">
          <div class="card-header-icon">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
          </div>
          <div class="card-header-text">
            <h2>Active Issuances</h2>
            <p>Books currently checked out by students</p>
          </div>
        </div>
        <div class="record-count">
          <strong><?php echo $total_issued; ?></strong> records
        </div>
      </div>

      <?php if ($total_issued > 0): ?>
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
            <?php while ($row = mysqli_fetch_assoc($issuances)):
              $is_late = strtotime($row['due_date']) < strtotime('today');
              $is_today = $row['due_date'] == date('Y-m-d');
              $initials = strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1));
              ?>
              <tr>
                <td>
                  <div class="student-cell">
                    <div class="student-avatar"><?php echo $initials; ?></div>
                    <div class="student-info">
                      <div class="name"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                      <div class="admission"><?php echo $row['admission_number']; ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="book-title"><?php echo htmlspecialchars($row['title']); ?></div>
                </td>
                <td>
                  <div class="date-cell">
                    <strong><?php echo date('d/m/Y', strtotime($row['issue_date'])); ?></strong>
                    <?php echo date('l', strtotime($row['issue_date'])); ?>
                  </div>
                </td>
                <td>
                  <?php if ($is_late): ?>
                    <span class="due-tag due-late">
                      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <?php echo date('d/m/Y', strtotime($row['due_date'])); ?> (OVERDUE)
                    </span>
                  <?php elseif ($is_today): ?>
                    <span class="due-tag due-today">
                      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Due Today
                    </span>
                  <?php else: ?>
                    <span class="due-tag due-normal">
                      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <?php echo date('d/m/Y', strtotime($row['due_date'])); ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right;">
                  <form action="return_book.php" method="POST" style="display: inline;">
                    <input type="hidden" name="issuance_id" value="<?php echo $row['issuance_id']; ?>">
                    <button type="submit" name="return_book" class="btn-return-action">
                      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Mark Returned
                    </button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="empty-title">All Books Returned</div>
          <div class="empty-text">No books are currently issued. All library books have been returned.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  <?php if (!empty($message)): ?>
    document.addEventListener('DOMContentLoaded', function () {
      <?php if ($message_type === 'success'): ?>
        showToastSuccess("<?php echo addslashes($message); ?>");
      <?php else: ?>
        showToastError("<?php echo addslashes($message); ?>");
      <?php endif; ?>
    });
  <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>