<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher', 'accountant']);

header('Content-Type: application/json');

$class_filter = $_GET['class_id'] ?? '';

// Build Query
$sql = "SELECT s.*, c.class_name, c.section_name 
        FROM students s 
        LEFT JOIN classes c ON s.current_class_id = c.class_id 
        WHERE s.status != 'Deleted' ";

$sort_col = $_GET['sort'] ?? 'first_name';
$sort_order = $_GET['order'] ?? 'ASC';

// Whitelist allowed columns to prevent SQL injection
$allowed_cols = [
    'admission_number' => 's.admission_number',
    'first_name' => 's.first_name', // We'll manually handle full name sorting if needed, but first_name is good default
    'gender' => 's.gender'
];

// Validate sort column
if (!array_key_exists($sort_col, $allowed_cols)) {
    $sort_col = 'first_name';
}
$order_by = $allowed_cols[$sort_col];

// Validate sort order
$sort_order = strtoupper($sort_order);
if ($sort_order !== 'ASC' && $sort_order !== 'DESC') {
    $sort_order = 'ASC';
}

if ($class_filter) {
    $sql .= " AND s.current_class_id = " . intval($class_filter);
}

$sql .= " ORDER BY $order_by $sort_order";
$result = mysqli_query($conn, $sql);

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = [
        'student_id' => $row['student_id'],
        'admission_number' => $row['admission_number'] ?? '',
        'first_name' => $row['first_name'] ?? '',
        'last_name' => $row['last_name'] ?? '',
        'gender' => $row['gender'] ?? '',
        'class_name' => $row['class_name'] ?? '',
        'section_name' => $row['section_name'] ?? '',
        'status' => $row['status'] ?? ''
    ];
}

echo json_encode([
    'success' => true,
    'students' => $students,
    'count' => count($students)
]);
?>