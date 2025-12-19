<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin', 'accountant']);

$selected_term = $_GET['term_id'] ?? 1; // Default to Term 1
$selected_class = $_GET['class_id'] ?? '';

echo "<h2>Billing Status Query Debug</h2>";
echo "<style>table { border-collapse: collapse; width: 100%; margin: 20px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #4CAF50; color: white; } .null { color: red; font-style: italic; }</style>";

echo "<p><strong>Selected Term ID:</strong> $selected_term</p>";
echo "<p><strong>Selected Class ID:</strong> " . ($selected_class ?: 'All Classes') . "</p>";

// Run the exact same query as billing_status.php
$sql_details = "SELECT 
            s.student_id, s.first_name, s.last_name, s.admission_number, c.class_name, c.section_name,
            sf.invoice_id, sf.invoice_number, sf.total_amount, sf.paid_amount, sf.status as payment_status
            FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            LEFT JOIN student_fees sf ON s.student_id = sf.student_id AND sf.term_id = ?
            WHERE s.status = 'Active'
            AND (? = '' OR s.current_class_id = ?)
            ORDER BY c.class_name, s.first_name";

$stmt_d = mysqli_prepare($conn, $sql_details);
mysqli_stmt_bind_param($stmt_d, "isi", $selected_term, $selected_class, $selected_class);
mysqli_stmt_execute($stmt_d);
$res_details = mysqli_stmt_get_result($stmt_d);

echo "<h3>Query Results</h3>";
echo "<table>";
echo "<tr><th>Student ID</th><th>Name</th><th>Adm #</th><th>Class</th><th>Invoice ID</th><th>Invoice #</th><th>Total</th><th>Paid</th><th>Status</th></tr>";

$peter_found = false;
while ($row = mysqli_fetch_assoc($res_details)) {
  $is_peter = ($row['admission_number'] == 'ADM007');
  if ($is_peter)
    $peter_found = true;

  echo "<tr" . ($is_peter ? " style='background-color: #ffff99; font-weight: bold;'" : "") . ">";
  echo "<td>{$row['student_id']}</td>";
  echo "<td>{$row['first_name']} {$row['last_name']}</td>";
  echo "<td>{$row['admission_number']}</td>";
  echo "<td>{$row['class_name']} {$row['section_name']}</td>";
  echo "<td>" . ($row['invoice_id'] ? $row['invoice_id'] : "<span class='null'>NULL</span>") . "</td>";
  echo "<td>" . ($row['invoice_number'] ? $row['invoice_number'] : "<span class='null'>NULL</span>") . "</td>";
  echo "<td>" . ($row['total_amount'] ? number_format($row['total_amount'], 2) : "<span class='null'>NULL</span>") . "</td>";
  echo "<td>" . ($row['paid_amount'] !== null ? number_format($row['paid_amount'], 2) : "<span class='null'>NULL</span>") . "</td>";
  echo "<td>" . ($row['payment_status'] ? $row['payment_status'] : "<span class='null'>NULL</span>") . "</td>";
  echo "</tr>";
}
echo "</table>";

if (!$peter_found) {
  echo "<p style='color: red; font-weight: bold;'>⚠️ Peter Banda (ADM007) was NOT found in the results!</p>";
} else {
  echo "<p style='color: green; font-weight: bold;'>✓ Peter Banda (ADM007) is highlighted in yellow above</p>";
}

// Now check what's actually in the database for Peter Banda
echo "<h3>Direct Database Check for Peter Banda (ADM007)</h3>";
$sql = "SELECT s.student_id, s.admission_number, s.status, s.current_class_id,
        sf.invoice_id, sf.term_id, sf.total_amount
        FROM students s
        LEFT JOIN student_fees sf ON s.student_id = sf.student_id
        WHERE s.admission_number = 'ADM007'";
$result = mysqli_query($conn, $sql);
echo "<table>";
echo "<tr><th>Student ID</th><th>Adm #</th><th>Status</th><th>Class ID</th><th>Invoice ID</th><th>Term ID</th><th>Amount</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
  echo "<tr>";
  echo "<td>{$row['student_id']}</td>";
  echo "<td>{$row['admission_number']}</td>";
  echo "<td>{$row['status']}</td>";
  echo "<td>{$row['current_class_id']}</td>";
  echo "<td>" . ($row['invoice_id'] ?: 'NULL') . "</td>";
  echo "<td>" . ($row['term_id'] ?: 'NULL') . "</td>";
  echo "<td>" . ($row['total_amount'] ?: 'NULL') . "</td>";
  echo "</tr>";
}
echo "</table>";

echo "<p><a href='billing_status.php?term_id=$selected_term'>Back to Billing Status (Term $selected_term)</a></p>";
echo "<p><a href='debug_invoices.php'>Back to Invoice Debug</a></p>";
?>