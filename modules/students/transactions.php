<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();

$student_id = $_GET['id'] ?? 0;
$currency_symbol = get_setting('currency_symbol', '$');

// Fetch student data
$sql = "SELECT s.*, c.class_name, c.section_name 
        FROM students s 
        LEFT JOIN classes c ON s.current_class_id = c.class_id 
        WHERE s.student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
  header("Location: index.php");
  exit();
}

// Fetch all transactions (payments)
$transactions_sql = "SELECT 
    p.payment_id,
    p.amount,
    p.payment_date,
    p.payment_method,
    p.recorded_by,
    u.full_name as recorded_by_name,
    sf.invoice_number,
    sf.total_amount as invoice_total,
    t.term_name,
    ay.year_name
FROM payments p
INNER JOIN student_fees sf ON p.invoice_id = sf.invoice_id
LEFT JOIN terms t ON sf.term_id = t.term_id
LEFT JOIN academic_years ay ON t.academic_year_id = ay.year_id
LEFT JOIN users u ON p.recorded_by = u.user_id
WHERE sf.student_id = ?
ORDER BY p.payment_date DESC, p.payment_id DESC";

$stmt_trans = mysqli_prepare($conn, $transactions_sql);
mysqli_stmt_bind_param($stmt_trans, "i", $student_id);
mysqli_stmt_execute($stmt_trans);
$transactions_result = mysqli_stmt_get_result($stmt_trans);

// Calculate summary statistics
$total_paid = 0;
$transaction_count = 0;
$transactions = [];
while ($row = mysqli_fetch_assoc($transactions_result)) {
  $transactions[] = $row;
  $total_paid += $row['amount'];
  $transaction_count++;
}

// Get total fees and balance
$fees_sql = "SELECT 
    SUM(total_amount) as total_fees,
    SUM(paid_amount) as total_paid_amount
FROM student_fees 
WHERE student_id = ?";
$stmt_fees = mysqli_prepare($conn, $fees_sql);
mysqli_stmt_bind_param($stmt_fees, "i", $student_id);
mysqli_stmt_execute($stmt_fees);
$fees_result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_fees));

$total_fees = $fees_result['total_fees'] ?? 0;
$balance = $total_fees - ($fees_result['total_paid_amount'] ?? 0);

$page_title = "Transaction History - " . $student['first_name'] . " " . $student['last_name'];
include '../../includes/header.php';
?>

<style>
  /* Premium Page Styles */
  .page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 32px;
    border-radius: 16px;
    margin-bottom: 32px;
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
  }

  .student-info {
    display: flex;
    align-items: center;
    gap: 24px;
  }

  .student-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: white;
    color: #667eea;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 700;
    flex-shrink: 0;
  }

  .student-details h1 {
    margin: 0 0 8px 0;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: -0.02em;
  }

  .student-meta {
    display: flex;
    gap: 24px;
    font-size: 14px;
    opacity: 0.95;
  }

  .student-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  /* Summary Cards */
  .summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
  }

  .summary-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
    border: 1px solid #e2e8f0;
    border-top: 4px solid;
  }

  .summary-card.total {
    border-top-color: #3b82f6;
  }

  .summary-card.paid {
    border-top-color: #10b981;
  }

  .summary-card.balance {
    border-top-color: #ef4444;
  }

  .summary-card.count {
    border-top-color: #8b5cf6;
  }

  .summary-label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
  }

  .summary-value {
    font-size: 32px;
    font-weight: 700;
    color: #0f172a;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.02em;
  }

  /* Transaction Table */
  .transactions-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
    border: 1px solid #e2e8f0;
    overflow: hidden;
  }

  .card-header {
    padding: 24px 28px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .card-title {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.01em;
  }

  .transactions-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
  }

  .transactions-table thead th {
    text-align: left;
    font-size: 12px;
    color: #64748b;
    font-weight: 700;
    padding: 16px 24px;
    border-bottom: 2px solid #f1f5f9;
    background: #f8fafc;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .transactions-table thead th.text-right {
    text-align: right;
  }

  .transactions-table tbody tr {
    transition: background 0.15s ease;
    border-bottom: 1px solid #f1f5f9;
  }

  .transactions-table tbody tr:hover {
    background: #f8fafc;
  }

  .transactions-table tbody tr:last-child {
    border-bottom: none;
  }

  .transactions-table tbody td {
    padding: 18px 24px;
    font-size: 14px;
    color: #475569;
    font-weight: 500;
    vertical-align: middle;
  }

  .transactions-table tbody td.text-right {
    text-align: right;
  }

  .amount-cell {
    font-weight: 700;
    font-size: 16px;
    color: #10b981;
    font-variant-numeric: tabular-nums;
  }

  .date-cell {
    color: #1e293b;
    font-weight: 600;
  }

  .method-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
  }

  .method-cash {
    background: #dbeafe;
    color: #1e40af;
  }

  .method-bank {
    background: #d1fae5;
    color: #065f46;
  }

  .method-mobile {
    background: #fef3c7;
    color: #92400e;
  }

  .method-cheque {
    background: #e0e7ff;
    color: #3730a3;
  }

  .ref-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: #f1f5f9;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    font-family: 'Courier New', monospace;
  }

  .empty-state {
    text-align: center;
    padding: 64px 24px;
    color: #94a3b8;
  }

  .empty-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
    opacity: 0.3;
  }

  .empty-title {
    font-size: 18px;
    font-weight: 600;
    color: #64748b;
    margin: 0 0 8px 0;
  }

  .empty-text {
    font-size: 14px;
    color: #94a3b8;
    margin: 0;
  }

  /* Action Buttons */
  .btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    border: 1.5px solid #e2e8f0;
    background: white;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
  }

  .btn-back:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #1e293b;
  }

  .btn-print {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
  }

  .btn-print:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
    transform: translateY(-1px);
  }

  /* Print Styles */
  @media print {
    .no-print {
      display: none !important;
    }

    .page-header {
      background: none !important;
      color: #000 !important;
      box-shadow: none !important;
      border: 2px solid #000;
    }

    .summary-card {
      break-inside: avoid;
    }

    .transactions-card {
      box-shadow: none !important;
      border: 1px solid #000;
    }
  }

  /* Responsive */
  @media (max-width: 768px) {
    .student-info {
      flex-direction: column;
      text-align: center;
    }

    .student-meta {
      flex-direction: column;
      gap: 8px;
    }

    .summary-grid {
      grid-template-columns: 1fr;
    }

    .transactions-table {
      font-size: 12px;
    }

    .transactions-table thead th,
    .transactions-table tbody td {
      padding: 12px 16px;
    }
  }
</style>

<!-- Page Header -->
<div class="page-header">
  <div class="student-info">
    <div class="student-avatar">
      <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
    </div>
    <div class="student-details">
      <h1><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h1>
      <div class="student-meta">
        <div class="student-meta-item">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          <?php echo htmlspecialchars($student['admission_number']); ?>
        </div>
        <div class="student-meta-item">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
            </path>
          </svg>
          <?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section_name']); ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Summary Cards -->
<div class="summary-grid">
  <div class="summary-card total">
    <div class="summary-label">Total Fees</div>
    <div class="summary-value"><?php echo $currency_symbol . number_format($total_fees, 2); ?></div>
  </div>

  <div class="summary-card paid">
    <div class="summary-label">Total Paid</div>
    <div class="summary-value"><?php echo $currency_symbol . number_format($total_paid, 2); ?></div>
  </div>

  <div class="summary-card balance">
    <div class="summary-label">Balance Due</div>
    <div class="summary-value"><?php echo $currency_symbol . number_format($balance, 2); ?></div>
  </div>

  <div class="summary-card count">
    <div class="summary-label">Transactions</div>
    <div class="summary-value"><?php echo $transaction_count; ?></div>
  </div>
</div>

<!-- Transactions Table -->
<div class="transactions-card">
  <div class="card-header">
    <h2 class="card-title">Payment History</h2>
    <div style="display: flex; gap: 12px;" class="no-print">
      <button class="btn-print" onclick="window.print()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="6 9 6 2 18 2 18 9"></polyline>
          <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
          <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        Print
      </button>
      <a href="view.php?id=<?php echo $student_id; ?>" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="19" y1="12" x2="5" y2="12"></line>
          <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back to Profile
      </a>
    </div>
  </div>

  <?php if (count($transactions) > 0): ?>
    <table class="transactions-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Invoice</th>
          <th>Term</th>
          <th>Method</th>
          <th>Recorded By</th>
          <th class="text-right">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transactions as $trans): ?>
          <tr>
            <td class="date-cell">
              <?php echo date('d M Y', strtotime($trans['payment_date'])); ?>
              <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                <?php echo date('H:i', strtotime($trans['payment_date'])); ?>
              </div>
            </td>
            <td>
              <span style="font-weight: 600; color: #475569;">
                <?php echo htmlspecialchars($trans['invoice_number'] ?? 'N/A'); ?>
              </span>
            </td>
            <td>
              <div style="font-weight: 600; color: #1e293b;">
                <?php echo htmlspecialchars($trans['term_name'] ?? 'N/A'); ?>
              </div>
              <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                <?php echo htmlspecialchars($trans['year_name'] ?? ''); ?>
              </div>
            </td>
            <td>
              <?php
              $method = $trans['payment_method'];
              $method_class = 'method-' . strtolower(str_replace(' ', '', $method));
              if (strpos(strtolower($method), 'bank') !== false)
                $method_class = 'method-bank';
              elseif (strpos(strtolower($method), 'mobile') !== false)
                $method_class = 'method-mobile';
              elseif (strpos(strtolower($method), 'cheque') !== false || strpos(strtolower($method), 'check') !== false)
                $method_class = 'method-cheque';
              else
                $method_class = 'method-cash';
              ?>
              <span class="method-badge <?php echo $method_class; ?>">
                <?php echo htmlspecialchars($method); ?>
              </span>
            </td>
            <td>
              <span style="font-size: 13px; color: #64748b;">
                <?php echo htmlspecialchars($trans['recorded_by_name'] ?? 'System'); ?>
              </span>
            </td>
            <td class="text-right amount-cell">
              <?php echo $currency_symbol . number_format($trans['amount'], 2); ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="empty-state">
      <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
      </svg>
      <div class="empty-title">No Transactions Yet</div>
      <div class="empty-text">Payment history will appear here once transactions are recorded</div>
    </div>
  <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>