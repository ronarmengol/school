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

  // Convert dd/mm/yyyy to Y-m-d for database
  $due_date_input = $_POST['due_date'];
  $due_date_parts = explode('/', $due_date_input);
  $due_date = (count($due_date_parts) === 3) ? $due_date_parts[2] . '-' . $due_date_parts[1] . '-' . $due_date_parts[0] : $due_date_input;
  $due_date = mysqli_real_escape_string($conn, $due_date);

  // Handle manual issue date
  $manual_dates = isset($_POST['manual_dates']) ? true : false;
  if ($manual_dates && !empty($_POST['issue_date'])) {
    $issue_date_input = $_POST['issue_date'];
    $issue_date_parts = explode('/', $issue_date_input);
    $issue_date = (count($issue_date_parts) === 3) ? $issue_date_parts[2] . '-' . $issue_date_parts[1] . '-' . $issue_date_parts[0] : $issue_date_input;
    $issue_date = mysqli_real_escape_string($conn, $issue_date);
  } else {
    $issue_date = date('Y-m-d');
  }

  $recorded_by = $_SESSION['user_id'];

  if ($book_id <= 0 || $student_id <= 0 || empty($due_date)) {
    $message = "Please select a student, a book, and a due date.";
    $message_type = "error";
  } else {
    // Check availability
    $check = mysqli_query($conn, "SELECT available_copies, title FROM library_books WHERE book_id = $book_id");
    $book = mysqli_fetch_assoc($check);

    if ($book['available_copies'] <= 0) {
      $message = "This book is currently out of stock.";
      $message_type = "error";
    } else {
      mysqli_begin_transaction($conn);
      try {
        // Insert issuance with manual or auto issue date
        $sql = "INSERT INTO library_issuances (book_id, student_id, issue_date, due_date, recorded_by, status) VALUES (?, ?, ?, ?, ?, 'Issued')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iissi", $book_id, $student_id, $issue_date, $due_date, $recorded_by);
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
$books = mysqli_query($conn, "SELECT book_id, title, author, available_copies FROM library_books WHERE available_copies > 0 ORDER BY title ASC");

// Fetch active students
$students = mysqli_query($conn, "SELECT student_id, first_name, last_name, admission_number FROM students WHERE status = 'Active' ORDER BY first_name ASC");

// Count totals for display
$total_books = mysqli_num_rows($books);
$total_students = mysqli_num_rows($students);
?>

<!-- External Libraries -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">

<style>
  :root {
    --lib-primary: #6366f1;
    --lib-primary-dark: #4f46e5;
    --lib-primary-light: #a5b4fc;
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

  .issue-page {
    min-height: 100vh;
    padding-bottom: 60px;
  }

  .issue-container {
    max-width: 900px;
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
    box-shadow: 0 10px 25px -10px rgba(99, 102, 241, 0.2);
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

  .stat-icon.books {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #2563eb;
  }

  .stat-icon.students {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #16a34a;
  }

  .stat-icon.today {
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    color: #7c3aed;
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

  /* Main Form Card */
  .form-card {
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
    gap: 16px;
    background: linear-gradient(to right, #fafafb, #f8fafc);
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
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
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

  .card-body {
    padding: 32px;
  }

  /* Form Sections */
  .form-section {
    margin-bottom: 32px;
  }

  .form-section:last-child {
    margin-bottom: 0;
  }

  .section-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--lib-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--lib-border);
  }

  .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
  }

  .form-group {
    position: relative;
  }

  .form-group.full {
    grid-column: span 2;
  }

  .form-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    font-weight: 600;
    color: var(--lib-text);
    margin-bottom: 10px;
  }

  .form-label svg {
    color: var(--lib-muted);
  }

  .required-star {
    color: var(--lib-danger);
    font-weight: 700;
  }

  /* Input Styling */
  .form-input,
  .form-select {
    width: 100%;
    padding: 16px 18px;
    border: 2px solid var(--lib-border);
    border-radius: 14px;
    font-size: 15px;
    font-weight: 500;
    color: var(--lib-text);
    background: var(--lib-bg);
    transition: all 0.2s ease;
  }

  .form-input:hover,
  .form-select:hover {
    border-color: #cbd5e1;
    background: white;
  }

  .form-input:focus,
  .form-select:focus {
    outline: none;
    border-color: var(--lib-primary);
    background: white;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
  }

  .form-input::placeholder {
    color: var(--lib-light);
  }

  .form-input.readonly {
    background: #f1f5f9;
    color: var(--lib-muted);
    cursor: not-allowed;
  }

  .form-select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1.5L6 6.5L11 1.5' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 18px center;
    padding-right: 48px;
  }

  /* Input with Icon */
  .input-with-icon {
    position: relative;
  }

  .input-with-icon .form-input,
  .input-with-icon .form-select {
    padding-left: 50px;
  }

  .input-icon {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--lib-muted);
    pointer-events: none;
    transition: color 0.2s ease;
  }

  .input-with-icon:focus-within .input-icon {
    color: var(--lib-primary);
  }

  /* Help Text */
  .form-help {
    font-size: 12px;
    color: var(--lib-light);
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 4px;
  }

  /* Manual Toggle Checkbox */
  .manual-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
    border: 2px solid #e9d5ff;
    border-radius: 12px;
    margin-bottom: 20px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .manual-toggle:hover {
    border-color: var(--lib-primary-light);
    background: linear-gradient(135deg, #f3e8ff 0%, #ede9fe 100%);
  }

  .manual-toggle input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: var(--lib-primary);
    cursor: pointer;
  }

  .manual-toggle-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--lib-primary-dark);
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .manual-toggle-hint {
    font-size: 12px;
    color: var(--lib-muted);
    margin-left: auto;
  }

  .form-input.editable {
    background: white;
    color: var(--lib-text);
    cursor: pointer;
  }

  /* Buttons */
  .form-actions {
    display: flex;
    gap: 16px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--lib-border);
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 32px;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent;
  }

  .btn-primary {
    flex: 1;
    background: linear-gradient(135deg, var(--lib-primary) 0%, var(--lib-primary-dark) 100%);
    color: white;
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
  }

  .btn-primary:active {
    transform: translateY(0);
  }

  .btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
  }

  .btn-secondary {
    background: white;
    color: var(--lib-muted);
    border-color: var(--lib-border);
  }

  .btn-secondary:hover {
    background: #f8fafc;
    color: var(--lib-text);
    border-color: #cbd5e1;
  }

  /* Loading Spinner */
  .btn-loading .btn-text {
    display: none;
  }

  .btn-loading .btn-spinner {
    display: inline-flex;
  }

  .btn-spinner {
    display: none;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    from {
      transform: rotate(0deg);
    }

    to {
      transform: rotate(360deg);
    }
  }

  /* Responsive */
  @media (max-width: 768px) {
    .issue-container {
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

    .form-grid {
      grid-template-columns: 1fr;
    }

    .form-group.full {
      grid-column: span 1;
    }

    .card-body {
      padding: 24px;
    }

    .form-actions {
      flex-direction: column;
    }
  }
</style>

<div class="issue-page">
  <div class="issue-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1>Issue Book</h1>
          <p>Record a new book issuance to a student. Fill in the details below and confirm.</p>
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
        <div class="stat-icon books">
          <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
        </div>
        <div>
          <div class="stat-value"><?php echo $total_books; ?></div>
          <div class="stat-label">Available Books</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon students">
          <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </div>
        <div>
          <div class="stat-value"><?php echo $total_students; ?></div>
          <div class="stat-label">Active Students</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon today">
          <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div>
          <div class="stat-value"><?php echo date('d'); ?></div>
          <div class="stat-label"><?php echo date('F Y'); ?></div>
        </div>
      </div>
    </div>

    <!-- Main Form Card -->
    <div class="form-card">
      <div class="card-header">
        <div class="card-header-icon">
          <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
        </div>
        <div class="card-header-text">
          <h2>Issuance Details</h2>
          <p>Complete all required fields to issue a book</p>
        </div>
      </div>

      <form id="issueForm" action="issue_book.php" method="POST" class="card-body">
        <!-- Student Selection Section -->
        <div class="form-section">
          <div class="section-title">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Student Information
          </div>
          <div class="form-group full">
            <label class="form-label">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Select Student <span class="required-star">*</span>
            </label>
            <div class="input-with-icon">
              <span class="input-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </span>
              <select name="student_id" class="form-select" required id="student_select">
                <option value="">Search or select a student...</option>
                <?php
                mysqli_data_seek($students, 0);
                while ($s = mysqli_fetch_assoc($students)): ?>
                  <option value="<?php echo $s['student_id']; ?>">
                    <?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name'] . ' (' . $s['admission_number'] . ')'); ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-help">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Type to search by name or admission number
            </div>
          </div>
        </div>

        <!-- Book Selection Section -->
        <div class="form-section">
          <div class="section-title">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            Book Selection
          </div>
          <div class="form-group full">
            <label class="form-label">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
              Select Book <span class="required-star">*</span>
            </label>
            <div class="input-with-icon">
              <span class="input-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </span>
              <select name="book_id" class="form-select" required id="book_select">
                <option value="">Search or select a book...</option>
                <?php
                mysqli_data_seek($books, 0);
                while ($b = mysqli_fetch_assoc($books)): ?>
                  <option value="<?php echo $b['book_id']; ?>">
                    <?php echo htmlspecialchars($b['title'] . ' (' . $b['available_copies'] . ' available)'); ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-help">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Only books with available copies are shown
            </div>
          </div>
        </div>

        <!-- Date Section -->
        <div class="form-section">
          <div class="section-title">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Issuance Dates
          </div>

          <!-- Manual Toggle -->
          <label class="manual-toggle">
            <input type="checkbox" name="manual_dates" id="manual_dates">
            <span class="manual-toggle-label">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Set Dates Manually
            </span>
            <span class="manual-toggle-hint">Enable to customize issue and due dates</span>
          </label>

          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Issue Date
              </label>
              <input type="text" name="issue_date" class="form-input readonly" id="issue_date"
                value="<?php echo date('d/m/Y'); ?>" readonly>
              <div class="form-help" id="issue_date_help">Automatically set to today's date</div>
            </div>

            <div class="form-group">
              <label class="form-label">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Due Date <span class="required-star">*</span>
              </label>
              <input type="text" name="due_date" id="due_date" class="form-input" required
                placeholder="Select due date...">
              <div class="form-help" id="due_date_help">Default: 14 days from issue date</div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="form-actions">
          <a href="index.php" class="btn btn-secondary">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Cancel
          </a>
          <button type="submit" name="issue_book" class="btn btn-primary" id="submitBtn">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="btn-spinner">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span class="btn-text">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Confirm Issuance
            </span>
          </button>
        </div>
      </form>
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

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const manualCheckbox = document.getElementById('manual_dates');
    const issueDateInput = document.getElementById('issue_date');
    const dueDateInput = document.getElementById('due_date');
    const issueDateHelp = document.getElementById('issue_date_help');
    const dueDateHelp = document.getElementById('due_date_help');

    let issueDatePicker = null;
    let dueDatePicker = null;

    // Initialize Due Date picker (always enabled)
    function initDueDatePicker(minDate = 'today') {
      if (dueDatePicker) dueDatePicker.destroy();
      dueDatePicker = flatpickr('#due_date', {
        dateFormat: 'd/m/Y',
        altInput: true,
        altFormat: 'd M Y',
        minDate: minDate,
        defaultDate: new Date().fp_incr(14),
        allowInput: true,
        theme: 'airbnb',
        disableMobile: true
      });
    }

    // Initialize Issue Date picker (only when manual mode is on)
    function initIssueDatePicker() {
      if (issueDatePicker) issueDatePicker.destroy();
      issueDatePicker = flatpickr('#issue_date', {
        dateFormat: 'd/m/Y',
        altInput: true,
        altFormat: 'd M Y',
        maxDate: 'today',
        defaultDate: 'today',
        allowInput: true,
        theme: 'airbnb',
        disableMobile: true,
        onChange: function (selectedDates, dateStr) {
          // Update due date min to be the selected issue date
          if (selectedDates.length > 0) {
            const issueDate = selectedDates[0];
            const defaultDue = new Date(issueDate);
            defaultDue.setDate(defaultDue.getDate() + 14);

            if (dueDatePicker) {
              dueDatePicker.set('minDate', issueDate);
              dueDatePicker.setDate(defaultDue);
            }
          }
        }
      });
    }

    // Destroy Issue Date picker and reset to readonly
    function destroyIssueDatePicker() {
      if (issueDatePicker) {
        issueDatePicker.destroy();
        issueDatePicker = null;
      }
      issueDateInput.value = '<?php echo date('d/m/Y'); ?>';
      issueDateInput.classList.remove('editable');
      issueDateInput.classList.add('readonly');
      issueDateInput.readOnly = true;
      issueDateHelp.textContent = "Automatically set to today's date";

      // Reset due date picker
      initDueDatePicker('today');
    }

    // Handle manual checkbox toggle
    manualCheckbox.addEventListener('change', function () {
      if (this.checked) {
        // Enable manual mode
        issueDateInput.classList.remove('readonly');
        issueDateInput.classList.add('editable');
        issueDateInput.readOnly = false;
        issueDateHelp.textContent = 'Click to select a past or current date';
        dueDateHelp.textContent = 'Click to select the return deadline';

        initIssueDatePicker();
        initDueDatePicker(null); // No min date restriction in manual mode
      } else {
        // Disable manual mode
        destroyIssueDatePicker();
        dueDateHelp.textContent = 'Default: 14 days from issue date';
      }
    });

    // Initialize with default (auto mode)
    initDueDatePicker('today');

    // Form submission with loading state
    const form = document.getElementById('issueForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', function (e) {
      // Add loading state
      submitBtn.classList.add('btn-loading');
      submitBtn.disabled = true;
    });
  });
</script>

<?php include '../../includes/footer.php'; ?>