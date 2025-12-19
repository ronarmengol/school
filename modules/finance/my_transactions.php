<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();

// Allow multiple roles but we will verify relationship below
check_role(['super_admin', 'admin', 'accountant', 'parent', 'student']);

$student_id = $_GET['student_id'] ?? 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$currency_symbol = get_setting('currency_symbol', '$');

if (!$student_id) {
  if ($role == 'student') {
    // Find their student_id
    $s = mysqli_fetch_assoc(mysqli_query($conn, "SELECT student_id FROM students WHERE user_id = $user_id"));
    if ($s) {
      $student_id = $s['student_id'];
    } else {
      die("Student record not found.");
    }
  } elseif ($role == 'parent') {
    // Get first child for this parent
    $s = mysqli_fetch_assoc(mysqli_query($conn, "SELECT student_id FROM students WHERE parent_id = $user_id LIMIT 1"));
    if ($s) {
      $student_id = $s['student_id'];
    } else {
      die("No student records found for this parent.");
    }
  } else {
    die("Student ID required.");
  }
}

// Security / Relationship Check
$authorized = false;
if (in_array($role, ['super_admin', 'admin', 'accountant'])) {
  $authorized = true;
} elseif ($role == 'parent') {
  // Check if this student belongs to this parent
  $stmt = mysqli_prepare($conn, "SELECT student_id FROM students WHERE student_id = ? AND parent_id = ?");
  mysqli_stmt_bind_param($stmt, "ii", $student_id, $user_id);
  mysqli_stmt_execute($stmt);
  if (mysqli_stmt_fetch($stmt)) {
    $authorized = true;
  }
  mysqli_stmt_close($stmt);
} elseif ($role == 'student') {
  // Check if looking at own record
  $stmt = mysqli_prepare($conn, "SELECT student_id FROM students WHERE student_id = ? AND user_id = ?");
  mysqli_stmt_bind_param($stmt, "ii", $student_id, $user_id);
  mysqli_stmt_execute($stmt);
  if (mysqli_stmt_fetch($stmt)) {
    $authorized = true;
  }
  mysqli_stmt_close($stmt);
}

if (!$authorized) {
  die("Unauthorized access to this student's financial records.");
}

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
  header("Location: ../students/index.php");
  exit();
}

// Fetch all invoices and payments as ledger entries
$ledger_entries = [];

// Get all invoices (bills)
$invoices_sql = "SELECT 
    sf.invoice_id,
    sf.invoice_number,
    sf.total_amount,
    sf.created_at as transaction_date,
    t.term_name,
    ay.year_name,
    'invoice' as entry_type
FROM student_fees sf
LEFT JOIN terms t ON sf.term_id = t.term_id
LEFT JOIN academic_years ay ON t.academic_year_id = ay.year_id
WHERE sf.student_id = ?
ORDER BY sf.created_at DESC";

$stmt_inv = mysqli_prepare($conn, $invoices_sql);
mysqli_stmt_bind_param($stmt_inv, "i", $student_id);
mysqli_stmt_execute($stmt_inv);
$invoices_result = mysqli_stmt_get_result($stmt_inv);

while ($invoice = mysqli_fetch_assoc($invoices_result)) {
  $ledger_entries[] = $invoice;
}

// Get all payments
$payments_sql = "SELECT 
    p.payment_id,
    p.amount,
    p.payment_date as transaction_date,
    p.payment_method,
    p.reference_number,
    p.recorded_by,
    u.full_name as recorded_by_name,
    sf.invoice_number,
    t.term_name,
    ay.year_name,
    'payment' as entry_type
FROM payments p
INNER JOIN student_fees sf ON p.invoice_id = sf.invoice_id
LEFT JOIN terms t ON sf.term_id = t.term_id
LEFT JOIN academic_years ay ON t.academic_year_id = ay.year_id
LEFT JOIN users u ON p.recorded_by = u.user_id
WHERE sf.student_id = ?
ORDER BY p.payment_date DESC";

$stmt_pay = mysqli_prepare($conn, $payments_sql);
mysqli_stmt_bind_param($stmt_pay, "i", $student_id);
mysqli_stmt_execute($stmt_pay);
$payments_result = mysqli_stmt_get_result($stmt_pay);

while ($payment = mysqli_fetch_assoc($payments_result)) {
  $ledger_entries[] = $payment;
}

// Sort all entries by date (newest first - descending order)
usort($ledger_entries, function ($a, $b) {
  return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
});

// Calculate running balance and statistics
$total_billed = 0;
$total_paid = 0;
$transaction_count = count($ledger_entries);

foreach ($ledger_entries as $entry) {
  if ($entry['entry_type'] == 'invoice') {
    $total_billed += $entry['total_amount'];
  } else {
    $total_paid += $entry['amount'];
  }
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

$page_title = "My Transaction History";
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
      <?php if ($role == 'parent'): ?>
        <a href="../parents/index.php" class="btn-back">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          Back to Portal
        </a>
      <?php elseif ($role == 'student'): ?>
        <a href="../dashboard/index.php" class="btn-back">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          Back to Dashboard
        </a>
      <?php else: ?>
        <a href="../students/view.php?id=<?php echo $student_id; ?>" class="btn-back">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          Back to Profile
        </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (count($ledger_entries) > 0): ?>
    <?php
    // Pre-calculate balances for all entries (from oldest to newest)
    $entries_with_balance = [];
    $balance = 0;

    // Reverse the array to calculate from oldest to newest
    $reversed_entries = array_reverse($ledger_entries);

    foreach ($reversed_entries as $entry) {
      $is_invoice = ($entry['entry_type'] == 'invoice');

      if ($is_invoice) {
        $balance += $entry['total_amount'];
      } else {
        $balance -= $entry['amount'];
      }

      $entry['calculated_balance'] = $balance;
      $entries_with_balance[] = $entry;
    }

    // Reverse back to newest first for display
    $entries_with_balance = array_reverse($entries_with_balance);
    ?>

    <table class="transactions-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Description</th>
          <th>REF</th>
          <th class="text-right">Billed</th>
          <th class="text-right">Paid</th>
          <th class="text-right">Balance</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($entries_with_balance as $entry):
          $is_invoice = ($entry['entry_type'] == 'invoice');
          $running_balance = $entry['calculated_balance'];
          ?>
          <tr style="<?php echo $is_invoice ? 'background: #fef9f3;' : ''; ?>">
            <td class="date-cell">
              <?php echo date('d M Y', strtotime($entry['transaction_date'])); ?>
              <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                <?php echo date('H:i', strtotime($entry['transaction_date'])); ?>
              </div>
            </td>
            <td>
              <?php if ($is_invoice): ?>
                <div>
                  <div style="font-weight: 600; color: #1e293b;">Invoice Generated</div>
                  <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                    <?php echo htmlspecialchars($entry['term_name'] ?? 'N/A'); ?> -
                    <?php echo htmlspecialchars($entry['year_name'] ?? ''); ?>
                  </div>
                </div>
              <?php else: ?>
                <div>
                  <div style="font-weight: 600; color: #1e293b;">Payment Received</div>
                  <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                    <?php
                    $method = $entry['payment_method'];
                    $method_class = 'method-cash';
                    if (strpos(strtolower($method), 'bank') !== false)
                      $method_class = 'method-bank';
                    elseif (strpos(strtolower($method), 'mobile') !== false)
                      $method_class = 'method-mobile';
                    elseif (strpos(strtolower($method), 'cheque') !== false)
                      $method_class = 'method-cheque';
                    ?>
                    <span class="method-badge <?php echo $method_class; ?>" style="margin-right: 4px;">
                      <?php echo htmlspecialchars($method); ?>
                    </span>
                    <?php if (!empty($entry['reference_number'])): ?>
                      <span style="color: #94a3b8;">Ref: <?php echo htmlspecialchars($entry['reference_number']); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <span style="font-weight: 600; color: #475569; font-family: 'Courier New', monospace; font-size: 13px;">
                <?php
                if ($is_invoice) {
                  echo htmlspecialchars($entry['invoice_number'] ?? 'N/A');
                } else {
                  // Debug: Show what we have
                  if (isset($entry['reference_number'])) {
                    echo htmlspecialchars($entry['reference_number'] ?: '—');
                  } else {
                    echo '—';
                  }
                }
                ?>
              </span>
            </td>
            <td class="text-right"
              style="font-weight: 700; font-size: 15px; color: <?php echo $is_invoice ? '#f59e0b' : '#94a3b8'; ?>;">
              <?php echo $is_invoice ? $currency_symbol . number_format($entry['total_amount'], 2) : '—'; ?>
            </td>
            <td class="text-right"
              style="font-weight: 700; font-size: 15px; color: <?php echo !$is_invoice ? '#10b981' : '#94a3b8'; ?>;">
              <?php echo !$is_invoice ? $currency_symbol . number_format($entry['amount'], 2) : '—'; ?>
            </td>
            <td class="text-right" style="font-weight: 700; font-size: 16px; color: #1e293b;">
              <?php
              if ($running_balance > 0) {
                echo '(' . $currency_symbol . number_format($running_balance, 2) . ')';
              } elseif ($running_balance < 0) {
                echo $currency_symbol . number_format(abs($running_balance), 2);
              } else {
                echo $currency_symbol . '0.00';
              }
              ?>
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
</div><?php include '../../includes/footer.php'; ?>