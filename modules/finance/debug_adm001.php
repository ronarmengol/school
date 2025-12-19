<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin', 'accountant']);

echo "<h2>Debug: ADM001 Invoice Status</h2>";
echo "<style>
  table { border-collapse: collapse; width: 100%; margin: 20px 0; }
  th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
  th { background-color: #4CAF50; color: white; font-weight: bold; }
  .highlight { background-color: #ffff99; font-weight: bold; }
  .null { color: red; font-style: italic; }
  .success { color: green; font-weight: bold; }
  .error { color: red; font-weight: bold; }
  .info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 15px 0; }
</style>";

// 1. Check if student ADM001 exists
echo "<h3>1. Student Information for ADM001</h3>";
$sql = "SELECT student_id, first_name, last_name, admission_number, current_class_id, status 
        FROM students 
        WHERE admission_number = 'adm001'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
  $student = mysqli_fetch_assoc($result);
  echo "<div class='info'>";
  echo "<p><strong>Student ID:</strong> {$student['student_id']}</p>";
  echo "<p><strong>Name:</strong> {$student['first_name']} {$student['last_name']}</p>";
  echo "<p><strong>Admission Number:</strong> {$student['admission_number']}</p>";
  echo "<p><strong>Current Class ID:</strong> {$student['current_class_id']}</p>";
  echo "<p><strong>Status:</strong> {$student['status']}</p>";
  echo "</div>";

  $student_id = $student['student_id'];
  $class_id = $student['current_class_id'];
} else {
  echo "<p class='error'>❌ Student with admission number 'adm001' NOT FOUND!</p>";
  echo "<p>Note: Admission numbers are case-sensitive. Checking for variations...</p>";

  // Check for case variations
  $sql = "SELECT student_id, first_name, last_name, admission_number, current_class_id, status 
          FROM students 
          WHERE LOWER(admission_number) = 'adm001'";
  $result = mysqli_query($conn, $sql);

  if (mysqli_num_rows($result) > 0) {
    $student = mysqli_fetch_assoc($result);
    echo "<p class='success'>✓ Found student with admission number: <strong>{$student['admission_number']}</strong></p>";
    echo "<div class='info'>";
    echo "<p><strong>Student ID:</strong> {$student['student_id']}</p>";
    echo "<p><strong>Name:</strong> {$student['first_name']} {$student['last_name']}</p>";
    echo "<p><strong>Current Class ID:</strong> {$student['current_class_id']}</p>";
    echo "<p><strong>Status:</strong> {$student['status']}</p>";
    echo "</div>";

    $student_id = $student['student_id'];
    $class_id = $student['current_class_id'];
  } else {
    echo "<p class='error'>No student found with any variation of 'adm001'</p>";
    exit;
  }
}

// 2. Check ALL invoices for this student
echo "<h3>2. All Invoices for Student ID: $student_id</h3>";
$sql = "SELECT sf.invoice_id, sf.invoice_number, sf.term_id, t.term_name, y.year_name,
        sf.total_amount, sf.paid_amount, sf.status, sf.created_at
        FROM student_fees sf
        LEFT JOIN terms t ON sf.term_id = t.term_id
        LEFT JOIN academic_years y ON t.academic_year_id = y.year_id
        WHERE sf.student_id = $student_id
        ORDER BY sf.invoice_id DESC";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
  echo "<table>";
  echo "<tr><th>Invoice ID</th><th>Invoice #</th><th>Term ID</th><th>Academic Period</th><th>Total Amount</th><th>Paid Amount</th><th>Status</th><th>Created At</th></tr>";
  while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['invoice_id']}</td>";
    echo "<td>{$row['invoice_number']}</td>";
    echo "<td>{$row['term_id']}</td>";
    echo "<td>{$row['year_name']} - {$row['term_name']}</td>";
    echo "<td>" . number_format($row['total_amount'], 2) . "</td>";
    echo "<td>" . number_format($row['paid_amount'], 2) . "</td>";
    echo "<td>{$row['status']}</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "</tr>";
  }
  echo "</table>";
  echo "<p class='success'>✓ Found " . mysqli_num_rows($result) . " invoice(s) for this student</p>";
} else {
  echo "<p class='error'>❌ NO INVOICES found for this student!</p>";
}

// 3. Check all available terms
echo "<h3>3. All Available Academic Terms</h3>";
$sql = "SELECT t.term_id, t.term_name, y.year_name, t.is_active
        FROM terms t
        JOIN academic_years y ON t.academic_year_id = y.year_id
        ORDER BY y.year_name DESC, t.term_name";
$result = mysqli_query($conn, $sql);

echo "<table>";
echo "<tr><th>Term ID</th><th>Academic Year</th><th>Term Name</th><th>Active</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
  $active_badge = $row['is_active'] ? "<span style='color: green;'>✓ Active</span>" : "";
  echo "<tr>";
  echo "<td>{$row['term_id']}</td>";
  echo "<td>{$row['year_name']}</td>";
  echo "<td>{$row['term_name']}</td>";
  echo "<td>$active_badge</td>";
  echo "</tr>";
}
echo "</table>";

// 4. Get the latest term (what billing_status.php would default to)
echo "<h3>4. Default Term Selection (What billing_status.php uses)</h3>";
$sql = "SELECT t.term_id, t.term_name, y.year_name 
        FROM terms t 
        JOIN academic_years y ON t.academic_year_id = y.year_id 
        ORDER BY y.year_name DESC, t.term_name DESC 
        LIMIT 1";
$result = mysqli_query($conn, $sql);
$latest_term = mysqli_fetch_assoc($result);

echo "<div class='info'>";
echo "<p><strong>Latest Term ID:</strong> {$latest_term['term_id']}</p>";
echo "<p><strong>Term:</strong> {$latest_term['year_name']} - {$latest_term['term_name']}</p>";
echo "</div>";

// 5. Check if student has invoice for the latest term
echo "<h3>5. Invoice Check for Latest Term (Term ID: {$latest_term['term_id']})</h3>";
$sql = "SELECT sf.invoice_id, sf.invoice_number, sf.total_amount, sf.paid_amount, sf.status
        FROM student_fees sf
        WHERE sf.student_id = $student_id AND sf.term_id = {$latest_term['term_id']}";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
  $invoice = mysqli_fetch_assoc($result);
  echo "<p class='success'>✓ Invoice EXISTS for the latest term!</p>";
  echo "<div class='info'>";
  echo "<p><strong>Invoice ID:</strong> {$invoice['invoice_id']}</p>";
  echo "<p><strong>Invoice Number:</strong> {$invoice['invoice_number']}</p>";
  echo "<p><strong>Total Amount:</strong> " . number_format($invoice['total_amount'], 2) . "</p>";
  echo "<p><strong>Paid Amount:</strong> " . number_format($invoice['paid_amount'], 2) . "</p>";
  echo "<p><strong>Status:</strong> {$invoice['status']}</p>";
  echo "</div>";
} else {
  echo "<p class='error'>❌ NO invoice found for the latest term (Term ID: {$latest_term['term_id']})</p>";
  echo "<p>This is why the student shows as 'Unbilled' on the billing status page!</p>";
}

// 6. Check fee structure for student's class and latest term
echo "<h3>6. Fee Structure for Student's Class (Class ID: $class_id) and Latest Term</h3>";
$sql = "SELECT fs.structure_id, fs.amount, fs.description, c.class_name, c.section_name
        FROM fee_structures fs
        JOIN classes c ON fs.class_id = c.class_id
        WHERE fs.class_id = $class_id AND fs.term_id = {$latest_term['term_id']}";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
  echo "<table>";
  echo "<tr><th>Structure ID</th><th>Class</th><th>Amount</th><th>Description</th></tr>";
  $total_fee = 0;
  while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['structure_id']}</td>";
    echo "<td>{$row['class_name']} {$row['section_name']}</td>";
    echo "<td>" . number_format($row['amount'], 2) . "</td>";
    echo "<td>{$row['description']}</td>";
    echo "</tr>";
    $total_fee += $row['amount'];
  }
  echo "</table>";
  echo "<p class='success'>✓ Total Fee for this class/term: <strong>" . number_format($total_fee, 2) . "</strong></p>";
} else {
  echo "<p class='error'>❌ NO fee structure found for this class and term!</p>";
}

echo "<hr>";
echo "<h3>Summary & Recommendations</h3>";
echo "<div class='info'>";
echo "<p><strong>Issue:</strong> Student ADM001 shows as 'Unbilled' on billing_status.php</p>";
echo "<p><strong>Possible Causes:</strong></p>";
echo "<ul>";
echo "<li>Invoice was created for a different term than the one currently selected</li>";
echo "<li>Invoice was created but not properly linked to the student</li>";
echo "<li>The billing_status.php page is filtering by a different term</li>";
echo "</ul>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Check which term is selected on the billing_status.php page</li>";
echo "<li>Verify the invoice was created for the correct term</li>";
echo "<li>If needed, generate a new invoice for the correct term</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='billing_status.php?term_id={$latest_term['term_id']}'>→ Go to Billing Status (Latest Term)</a></p>";
echo "<p><a href='fees_structure.php'>→ Go to Fee Structure</a></p>";
?>