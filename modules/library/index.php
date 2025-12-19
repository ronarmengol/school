<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

$page_title = "Library Dashboard";
include '../../includes/header.php';

// Real Data Queries
// 1. Dashboard Stats
$total_books_res = mysqli_query($conn, "SELECT SUM(quantity) as total FROM library_books");
$total_books = mysqli_fetch_assoc($total_books_res)['total'] ?? 0;

$issued_books_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM library_issuances WHERE status = 'Issued'");
$issued_books = mysqli_fetch_assoc($issued_books_res)['total'] ?? 0;

$available_books_res = mysqli_query($conn, "SELECT SUM(available_copies) as total FROM library_books");
$available_books = mysqli_fetch_assoc($available_books_res)['total'] ?? 0;

$overdue_books_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM library_issuances WHERE status = 'Issued' AND due_date < CURRENT_DATE");
$overdue_books = mysqli_fetch_assoc($overdue_books_res)['total'] ?? 0;

$stats = [
  'total_books' => ['value' => $total_books, 'label' => 'Total Books', 'icon' => 'book', 'color' => '#2c3e50'],
  'issued_books' => ['value' => $issued_books, 'label' => 'Books Issued', 'icon' => 'upload', 'color' => '#3498db'],
  'available_books' => ['value' => $available_books, 'label' => 'Available', 'icon' => 'check-circle', 'color' => '#27ae60'],
  'overdue_books' => ['value' => $overdue_books, 'label' => 'Overdue', 'icon' => 'alert-circle', 'color' => '#e74c3c']
];

// 2. Recent Activity
$activity_sql = "SELECT i.*, b.title, s.first_name, s.last_name 
                 FROM library_issuances i 
                 JOIN library_books b ON i.book_id = b.book_id 
                 JOIN students s ON i.student_id = s.student_id 
                 ORDER BY i.issuance_id DESC LIMIT 5";
$activity_res = mysqli_query($conn, $activity_sql);
$recent_activity = [];
while ($row = mysqli_fetch_assoc($activity_res)) {
  $type = $row['status'] == 'Returned' ? 'Return' : 'Issue';
  $status = ($row['status'] == 'Issued' && strtotime($row['due_date']) < time()) ? 'Overdue' : 'On Time';
  if ($row['status'] == 'Issued' && strtotime($row['due_date']) >= time())
    $status = 'Pending';

  $recent_activity[] = [
    'title' => $row['title'],
    'student' => $row['first_name'] . ' ' . $row['last_name'],
    'type' => $type,
    'date' => $row['status'] == 'Returned' ? $row['return_date'] : $row['issue_date'],
    'status' => $status
  ];
}

// 3. Overdue Alerts
$overdue_sql = "SELECT i.*, s.first_name, s.last_name, b.title 
                FROM library_issuances i 
                JOIN students s ON i.student_id = s.student_id 
                JOIN library_books b ON i.book_id = b.book_id 
                WHERE i.status = 'Issued' AND i.due_date < CURRENT_DATE 
                LIMIT 3";
$overdue_res = mysqli_query($conn, $overdue_sql);
$overdue_alerts = [];
while ($row = mysqli_fetch_assoc($overdue_res)) {
  $due_date = new DateTime($row['due_date']);
  $today = new DateTime();
  $days = $today->diff($due_date)->days;
  $overdue_alerts[] = [
    'student' => $row['first_name'] . ' ' . $row['last_name'],
    'book' => $row['title'],
    'days' => $days,
    'fine' => '$' . number_format($days * 0.5, 2) // Assuming $0.50 per day
  ];
}

// 4. New Arrivals
$arrivals_sql = "SELECT b.*, c.category_name 
                 FROM library_books b 
                 LEFT JOIN library_categories c ON b.category_id = c.category_id 
                 ORDER BY b.book_id DESC LIMIT 3";
$arrivals_res = mysqli_query($conn, $arrivals_sql);
$new_arrivals = [];
while ($row = mysqli_fetch_assoc($arrivals_res)) {
  $new_arrivals[] = [
    'title' => $row['title'],
    'author' => $row['author'],
    'category' => $row['category_name'] ?: 'General'
  ];
}
?>

<style>
  /* Library Dashboard Exclusive Styles */
  :root {
    --library-primary: #2c3e50;
    --library-secondary: #3498db;
    --library-accent: #e74c3c;
    --library-success: #27ae60;
    --library-warning: #f1c40f;
    --library-bg: #f8fafc;
    --library-card-bg: #ffffff;
    --library-text: #334155;
    --library-text-light: #64748b;
    --library-border: #e2e8f0;
    --radius-lg: 16px;
    --radius-md: 12px;
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --transition: all 200ms ease;
  }

  .library-dashboard {
    padding: 10px 0;
    font-family: 'Inter', -apple-system, sans-serif;
  }

  /* KPI Cards */
  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
  }

  .kpi-card {
    background: var(--library-card-bg);
    padding: 24px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--library-border);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: var(--transition);
    cursor: pointer;
  }

  .kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
    border-color: var(--library-secondary);
  }

  .kpi-icon {
    width: 56px;
    height: 56px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    flex-shrink: 0;
  }

  .kpi-data {
    flex-grow: 1;
  }

  .kpi-value {
    display: block;
    font-size: 28px;
    font-weight: 800;
    color: var(--library-text);
    line-height: 1.2;
    text-align: right;
  }

  .kpi-label {
    display: block;
    font-size: 14px;
    color: var(--library-text-light);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
    text-align: right;
  }

  /* Main Grid Layout */
  .dashboard-main-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 32px;
    align-items: start;
  }

  /* Sections */
  .dashboard-section {
    background: var(--library-card-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--library-border);
    box-shadow: var(--shadow-sm);
    margin-bottom: 32px;
    overflow: hidden;
  }

  .section-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--library-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fafafa;
  }

  .section-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--library-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .section-action {
    font-size: 14px;
    color: var(--library-secondary);
    font-weight: 600;
    text-decoration: none;
  }

  .section-action:hover {
    text-decoration: underline;
  }

  /* Recent Activity Table */
  .table-responsive {
    width: 100%;
    overflow-x: auto;
  }

  .library-table {
    width: 100%;
    border-collapse: collapse;
  }

  .library-table th {
    text-align: left;
    padding: 16px 24px;
    background: #f8fafc;
    color: var(--library-text-light);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--library-border);
  }

  .library-table td {
    padding: 16px 24px;
    border-bottom: 1px solid var(--library-border);
    color: var(--library-text);
    font-size: 14px;
  }

  .library-table tr:last-child td {
    border-bottom: none;
  }

  .status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
  }

  .status-pending {
    background: #eff6ff;
    color: #3b82f6;
  }

  .status-ontime {
    background: #f0fdf4;
    color: #16a34a;
  }

  .status-overdue {
    background: #fef2f2;
    color: #ef4444;
  }

  /* Right Side Panels */
  .overdue-list {
    padding: 16px;
  }

  .overdue-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: var(--radius-md);
    border: 1px solid transparent;
    transition: var(--transition);
  }

  .overdue-item:hover {
    background: #fffafa;
    border-color: #fee2e2;
  }

  .overdue-avatar {
    width: 40px;
    height: 40px;
    background: #fee2e2;
    color: #ef4444;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
  }

  .overdue-info {
    flex-grow: 1;
  }

  .overdue-name {
    display: block;
    font-weight: 700;
    font-size: 14px;
    color: var(--library-text);
  }

  .overdue-meta {
    display: block;
    font-size: 12px;
    color: var(--library-text-light);
  }

  .overdue-fine {
    font-weight: 700;
    color: #ef4444;
    font-size: 14px;
  }

  /* Arrivals */
  .arrivals-grid {
    padding: 20px;
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .arrival-card {
    display: flex;
    gap: 16px;
    align-items: center;
    padding: 12px;
    background: #f8fafc;
    border-radius: var(--radius-md);
    border: 1px solid var(--library-border);
  }

  .arrival-thumb {
    width: 45px;
    height: 60px;
    background: #cbd5e1;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
  }

  .arrival-details h4 {
    margin: 0;
    font-size: 14px;
    color: var(--library-text);
  }

  .arrival-details p {
    margin: 2px 0 0;
    font-size: 12px;
    color: var(--library-text-light);
  }

  /* Notices */
  .notice-card {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: white;
    padding: 24px;
    border-radius: var(--radius-lg);
    margin-top: 32px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  }

  .notice-card::after {
    content: '\24D8';
    position: absolute;
    right: -20px;
    bottom: -20px;
    font-size: 120px;
    opacity: 0.1;
    transform: rotate(-15deg);
  }

  .notice-card h3 {
    margin: 0 0 12px 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .notice-card p {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
    line-height: 1.6;
  }

  /* Quick Actions Bar */
  .quick-actions-bar {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
  }

  .action-btn {
    padding: 12px 20px;
    border-radius: var(--radius-md);
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    font-size: 14px;
    transition: var(--transition);
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  }

  .btn-issue {
    background: var(--library-secondary);
    color: white;
  }

  .btn-issue:hover {
    background: #2980b9;
  }

  .btn-return {
    background: var(--library-success);
    color: white;
  }

  .btn-return:hover {
    background: #219150;
  }

  .btn-inventory {
    background: var(--library-primary);
    color: white;
  }

  .btn-inventory:hover {
    background: #1a252f;
  }

  .btn-warning-alt {
    background: #f59e0b;
    color: white;
  }

  .btn-warning-alt:hover {
    background: #d97706;
  }

  .action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
  }

  /* Popular items hover */
  .popular-cat {
    transition: var(--transition);
    cursor: pointer;
    border: 1px solid transparent;
  }

  .popular-cat:hover {
    transform: scale(1.05);
    box-shadow: var(--shadow-md);
  }

  /* Responsive */
  @media (max-width: 1024px) {
    .dashboard-main-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 768px) {
    .kpi-grid {
      grid-template-columns: 1fr 1fr;
    }

    .quick-actions-bar {
      flex-wrap: wrap;
    }

    .action-btn {
      flex: 1;
      min-width: 150px;
    }
  }

  @media (max-width: 480px) {
    .kpi-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="library-dashboard">
  <!-- Quick Actions -->
  <div class="quick-actions-bar">
    <button class="action-btn btn-issue" onclick="window.location.href='issue_book.php'">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M12 5v14M5 12h14" />
      </svg>
      Issue Book
    </button>
    <button class="action-btn btn-return" onclick="window.location.href='return_book.php'">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M11 15h2m-1-1v-4m-7 4l4-4 4 4" />
      </svg>
      Return Book
    </button>
    <button class="action-btn btn-inventory" onclick="window.location.href='inventory.php'">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M21 8l-2-2H5L3 8v10h18V8zM3 8h18M10 12h4" />
      </svg>
      Inventory
    </button>
    <button class="action-btn btn-warning-alt" onclick="window.location.href='categories.php'">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
      </svg>
      Categories
    </button>
  </div>

  <!-- KPI Section -->
  <div class="kpi-grid">
    <?php foreach ($stats as $key => $data): ?>
      <div class="kpi-card">
        <div class="kpi-icon"
          style="background: <?php echo $data['color']; ?>; box-shadow: 0 4px 10px <?php echo $data['color']; ?>44;">
          <?php if ($data['icon'] == 'book'): ?>
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path
                d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 19.5A2.5 2.5 0 0 0 6.5 22H20M4 19.5V3a1 1 0 0 1 1-1h15v15H6.5a1 1 0 0 0-1 1z" />
            </svg>
          <?php elseif ($data['icon'] == 'upload'): ?>
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" />
            </svg>
          <?php elseif ($data['icon'] == 'check-circle'): ?>
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
              <polyline points="22 4 12 14.01 9 11.01" />
            </svg>
          <?php elseif ($data['icon'] == 'alert-circle'): ?>
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="12" cy="12" r="10" />
              <line x1="12" y1="8" x2="12" y2="12" />
              <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
          <?php endif; ?>
        </div>
        <div class="kpi-data">
          <span class="kpi-value"><?php echo number_format($data['value']); ?></span>
          <span class="kpi-label"><?php echo $data['label']; ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Main Content -->
  <div class="dashboard-main-grid">
    <!-- Left Column -->
    <div class="left-column">
      <!-- Recent Activity -->
      <div class="dashboard-section">
        <div class="section-header">
          <h2 class="section-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Recent Activity
          </h2>
          <a href="#" class="section-action">View All Transactions</a>
        </div>
        <div class="table-responsive">
          <table class="library-table">
            <thead>
              <tr>
                <th>Book Title</th>
                <th>Student / Borrower</th>
                <th>Type</th>
                <th>Date</th>
                <th style="text-align: right;">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_activity as $item): ?>
                <tr style="transition: background 0.2s;">
                  <td style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($item['title']); ?></td>
                  <td><?php echo htmlspecialchars($item['student']); ?></td>
                  <td>
                    <span style="display: inline-flex; align-items: center; gap: 8px;">
                      <?php if ($item['type'] == 'Issue'): ?>
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #3498db;"></span>
                      <?php else: ?>
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #27ae60;"></span>
                      <?php endif; ?>
                      <?php echo $item['type']; ?>
                    </span>
                  </td>
                  <td><?php echo date('M d, Y', strtotime($item['date'])); ?></td>
                  <td style="text-align: right;">
                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $item['status'])); ?>">
                      <?php echo $item['status']; ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Popular Categories -->
      <div class="dashboard-section">
        <div class="section-header">
          <h2 class="section-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z" />
            </svg>
            Weekly Trends
          </h2>
        </div>
        <div
          style="padding: 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px;">
          <?php
          // Real Popular Categories Query
          $popular_sql = "SELECT c.category_name, COUNT(i.issuance_id) as borrow_count 
                         FROM library_categories c 
                         LEFT JOIN library_books b ON c.category_id = b.category_id 
                         LEFT JOIN library_issuances i ON b.book_id = i.book_id 
                         GROUP BY c.category_id 
                         ORDER BY borrow_count DESC LIMIT 4";
          $popular_res = mysqli_query($conn, $popular_sql);

          $styles = [
            ['color' => '#f0f9ff', 'border' => '#bae6fd'],
            ['color' => '#fffbeb', 'border' => '#fef3c7'],
            ['color' => '#f0fdf4', 'border' => '#dcfce7'],
            ['color' => '#faf5ff', 'border' => '#f3e8ff'],
          ];

          $idx = 0;
          while ($p = mysqli_fetch_assoc($popular_res)):
            $style = $styles[$idx % 4];
            $idx++;
            ?>
            <div class="popular-cat"
              style="background: <?php echo $style['color']; ?>; border: 1px solid <?php echo $style['border']; ?>; padding: 20px; border-radius: var(--radius-md); text-align: center;">
              <span
                style="display: block; font-weight: 700; color: #475569; font-size: 13px; text-transform: uppercase;"><?php echo htmlspecialchars($p['category_name']); ?></span>
              <span
                style="display: block; font-size: 32px; font-weight: 800; margin-top: 8px; color: #1e293b;"><?php echo number_format($p['borrow_count']); ?></span>
              <span style="font-size: 12px; color: #94a3b8; font-weight: 600;">BORROWS</span>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>

    <!-- Right Column -->
    <div class="right-column">
      <!-- Overdue Alerts -->
      <div class="dashboard-section" style="border-top: 4px solid var(--library-accent);">
        <div class="section-header">
          <h2 class="section-title" style="color: var(--library-accent);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            Urgent Overdue
          </h2>
          <span class="status-badge status-overdue" style="font-size: 10px;"><?php echo count($overdue_alerts); ?>
            NEW</span>
        </div>
        <div class="overdue-list">
          <?php foreach ($overdue_alerts as $alert): ?>
            <div class="overdue-item">
              <div class="overdue-avatar">
                <?php echo substr($alert['student'], 0, 1); ?>
              </div>
              <div class="overdue-info">
                <span class="overdue-name"><?php echo htmlspecialchars($alert['student']); ?></span>
                <span class="overdue-meta"><?php echo htmlspecialchars($alert['book']); ?> • <span
                    style="color: #ef4444; font-weight: 600;"><?php echo $alert['days']; ?>d late</span></span>
              </div>
              <div class="overdue-fine"><?php echo $alert['fine']; ?></div>
            </div>
          <?php endforeach; ?>
          <div style="padding: 16px; border-top: 1px solid var(--library-border); text-align: center;">
            <button class="btn btn-outline-danger"
              style="width: 100%; font-size: 13px; font-weight: 700; border-radius: 10px;"
              onclick="showToastSuccess('Reminders sent to students!')">Notify All</button>
          </div>
        </div>
      </div>

      <!-- New Arrivals -->
      <div class="dashboard-section">
        <div class="section-header">
          <h2 class="section-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M12 4v16m8-8H4" />
            </svg>
            New Arrivals
          </h2>
        </div>
        <div class="arrivals-grid">
          <?php foreach ($new_arrivals as $book): ?>
            <div class="arrival-card" style="transition: transform 0.2s; cursor: pointer;"
              onmouseover="this.style.transform='translateX(5px)'" onmouseout="this.style.transform='translateX(0)'">
              <div class="arrival-thumb" style="background: #f1f5f9; color: #64748b;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path
                    d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 19.5A2.5 2.5 0 0 0 6.5 22H20M4 19.5V3a1 1 0 0 1 1-1h15v15H6.5a1 1 0 0 0-1 1z" />
                </svg>
              </div>
              <div class="arrival-details">
                <h4 style="font-weight: 700;"><?php echo htmlspecialchars($book['title']); ?></h4>
                <p><?php echo htmlspecialchars($book['author']); ?> • <span
                    style="color: var(--library-secondary);"><?php echo $book['category']; ?></span></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>


    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>