<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';

// Auth checks - similar to my_invoices.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// We expect a parent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

$parent_id = $_SESSION['user_id'];
$student_id = $_GET['student_id'] ?? 0;

if (!$student_id) {
    echo "<div class='alert alert-danger'>No student specified.</div>";
    exit;
}

// Verify this student belongs to this parent
$check_sql = "SELECT student_id FROM students WHERE student_id = ? AND parent_id = ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "ii", $student_id, $parent_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($res) == 0) {
    echo "<div class='alert alert-danger'>Access Denied. Student not found or does not belong to you.</div>";
    exit;
}

// Helper to find date column
function find_date_column($conn, $table) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table`");
    $candidates = ['created_at', 'payment_date', 'date', 'added_at', 'timestamp', 'created_on'];
    $columns = [];
    while($row = mysqli_fetch_assoc($res)) {
        $columns[] = $row['Field'];
    }
    
    foreach ($candidates as $cand) {
        if (in_array($cand, $columns)) return $cand;
    }
    return null; // Fallback
}

$inv_date_col = find_date_column($conn, 'student_fees') ?? 'created_at'; // Default if check fails
$pay_date_col = find_date_column($conn, 'payments') ?? 'created_at';

// Fetch Invoices (Debits)
$invoices = [];
$sql_inv = "SELECT invoice_id, total_amount, $inv_date_col as date, invoice_number, term_id 
            FROM student_fees 
            WHERE student_id = ?";
$stmt = mysqli_prepare($conn, $sql_inv);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $row['type'] = 'Bill';
    $row['dr_cr'] = 'Dr'; // Debit (Increase balance/Due)
    // Extract numbers from invoice_number (e.g. INV-001 -> 001) or use invoice_id
    $num_part = preg_replace('/[^0-9]/', '', $row['invoice_number'] ?? '');
    $final_num = $num_part ?: $row['invoice_id'];
    $row['description'] = "#B-" . str_pad($final_num, 6, '0', STR_PAD_LEFT);
    $invoices[] = $row;
}

// Fetch Payments (Credits)
$payments = [];
// Need to join to verify student logic (payments link to invoice, invoice links to student)
$sql_pay = "SELECT p.payment_id, p.amount, p.$pay_date_col as date, p.payment_method, sf.invoice_number 
            FROM payments p
            JOIN student_fees sf ON p.invoice_id = sf.invoice_id
            WHERE sf.student_id = ?";
$stmt = mysqli_prepare($conn, $sql_pay);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $row['type'] = 'Payment';
    $row['dr_cr'] = 'Cr'; // Credit (Decrease balance/Paid)
    $row['description'] = "Payment for Invoice #" . ($row['invoice_number'] ?? 'N/A') . " ({$row['payment_method']})";
    $payments[] = $row;
}

// Merge and Sort
$ledger = array_merge($invoices, $payments);

// Sort by date desc (latest first)
usort($ledger, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Calculate Running Balance (Need to go Ascending for this, or calculated backwards)
// Let's go Ascending for calculation, then we can display Descending if needed?
// Ledger usually shows Oldest -> Newest with running balance.
usort($ledger, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});

?>

<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead class="bg-light">
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Type</th>
                <th class="text-end">Amount (<?php echo get_setting('currency') ?: 'K'; ?>)</th>
                <th class="text-end">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $balance = 0;
            if (empty($ledger)): ?>
                <tr><td colspan="5" class="text-center">No transactions found.</td></tr>
            <?php else: 
                foreach ($ledger as $item): 
                    $amount = floatval($item['amount'] ?? 0); // Correct key is amount for payments, and total_amount for invoices?
                    // Ah, I mapped invoice total_amount to amount? No I didn't in the fetch loop properly.
                    // Let's fix keys in fetch loop.
                    // Actually, I did: $row['total_amount'] in invoices. I should map to 'amount'.
                    
                    if ($item['type'] == 'Bill') {
                        $amount = floatval($item['total_amount']);
                        $balance += $amount;
                        $color = 'text-danger'; // Money owed
                    } else {
                        $amount = floatval($item['amount']);
                        $balance -= $amount;
                        $color = 'text-success'; // Money paid
                    }
            ?>
                <tr>
                    <td><?php echo date('d M Y, h:i A', strtotime($item['date'])); ?></td>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td>
                        <span class="badge <?php echo $item['type'] == 'Bill' ? 'bg-warning text-dark' : 'bg-success'; ?>">
                            <?php echo $item['type']; ?>
                        </span>
                    </td>
                    <td class="text-end <?php echo $color; ?>">
                        <?php echo number_format($amount, 2); ?>
                    </td>
                    <td class="text-end fw-bold">
                        <?php echo number_format($balance, 2); ?>
                    </td>
                </tr>
            <?php endforeach; 
            endif; ?>
        </tbody>
    </table>
</div>
