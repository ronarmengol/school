<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin', 'accountant']);

// Debug script to check invoice data
echo "<h2>Invoice Debug Information</h2>";
echo "<style>table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #4CAF50; color: white; }</style>";

// 1. Check for Peter Banda in students table
echo "<h3>1. Peter Banda in Students Table</h3>";
$sql = "SELECT student_id, first_name, last_name, admission_number, current_class_id, status FROM students WHERE first_name LIKE '%Peter%' OR last_name LIKE '%Banda%'";
$result = mysqli_query($conn, $sql);
echo "<table><tr><th>Student ID</th><th>Name</th><th>Admission #</th><th>Class ID</th><th>Status</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
  echo "<tr>";
  echo "<td>{$row['student_id']}</td>";
  echo "<td>{$row['first_name']} {$row['last_name']}</td>";
  echo "<td>{$row['admission_number']}</td>";
  echo "<td>{$row['current_class_id']}</td>";
  echo "<td>{$row['status']}</td>";
  echo "</tr>";
  $peter_student_id = $row['student_id'];
}
echo "</table>";

// 2. Check invoices for Peter Banda
if (isset($peter_student_id)) {
  echo "<h3>2. Invoices for Peter Banda (Student ID: $peter_student_id)</h3>";
  $sql = "SELECT sf.*, t.term_name, y.year_name 
            FROM student_fees sf 
            LEFT JOIN terms t ON sf.term_id = t.term_id 
            LEFT JOIN academic_years y ON t.academic_year_id = y.year_id 
            WHERE sf.student_id = $peter_student_id";
  $result = mysqli_query($conn, $sql);
  echo "<table><tr><th>Invoice ID</th><th>Invoice #</th><th>Term ID</th><th>Term</th><th>Total</th><th>Paid</th><th>Status</th></tr>";
  if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
      echo "<tr>";
      echo "<td>{$row['invoice_id']}</td>";
      echo "<td>{$row['invoice_number']}</td>";
      echo "<td>{$row['term_id']}</td>";
      echo "<td>{$row['year_name']} - {$row['term_name']}</td>";
      echo "<td>{$row['total_amount']}</td>";
      echo "<td>{$row['paid_amount']}</td>";
      echo "<td>{$row['status']}</td>";
      echo "</tr>";
    }
  } else {
    echo "<tr><td colspan='7'>No invoices found</td></tr>";
  }
  echo "</table>";
}

// 3. Check all terms
echo "<h3>3. All Academic Terms</h3>";
$sql = "SELECT t.term_id, t.term_name, y.year_name FROM terms t JOIN academic_years y ON t.academic_year_id = y.year_id ORDER BY y.year_name DESC, t.term_name";
$result = mysqli_query($conn, $sql);
echo "<table><tr><th>Term ID</th><th>Year</th><th>Term</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
  echo "<tr>";
  echo "<td>{$row['term_id']}</td>";
  echo "<td>{$row['year_name']}</td>";
  echo "<td>{$row['term_name']}</td>";
  echo "</tr>";
}
echo "</table>";

// 4. Check recent invoice generation
echo "<h3>4. Most Recent Invoices (Last 10)</h3>";
$sql = "SELECT sf.invoice_id, sf.invoice_number, sf.student_id, s.first_name, s.last_name, sf.term_id, sf.total_amount, sf.created_at 
        FROM student_fees sf 
        JOIN students s ON sf.student_id = s.student_id 
        ORDER BY sf.invoice_id DESC LIMIT 10";
$result = mysqli_query($conn, $sql);
echo "<table><tr><th>Invoice ID</th><th>Invoice #</th><th>Student ID</th><th>Student Name</th><th>Term ID</th><th>Amount</th><th>Created</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
  echo "<tr>";
  echo "<td>{$row['invoice_id']}</td>";
  echo "<td>{$row['invoice_number']}</td>";
  echo "<td>{$row['student_id']}</td>";
  echo "<td>{$row['first_name']} {$row['last_name']}</td>";
  echo "<td>{$row['term_id']}</td>";
  echo "<td>{$row['total_amount']}</td>";
  echo "<td>" . ($row['created_at'] ?? 'N/A') . "</td>";
  echo "</tr>";
}
echo "</table>";

echo "<p><a href='billing_status.php'>Back to Billing Status</a></p>";
?>