<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']); // Allow super_admin and admin to reset database

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$sections = $input['sections'] ?? [];
$password = $input['password'] ?? '';

// Verify password
$user_id = $_SESSION['user_id'];
$sql = "SELECT password_hash FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// DEVELOPMENT: Plain text password check
if ($user['password_hash'] !== $password) {
    echo json_encode(['success' => false, 'message' => 'Invalid password. Authentication failed.']);
    exit;
}

if (empty($sections)) {
    echo json_encode(['success' => false, 'message' => 'No sections selected for reset.']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    $deleted_counts = [];

    foreach ($sections as $section) {
        switch ($section) {
            case 'students':
                // Delete students and related data (cascading will handle most)
                $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM students");
                $count = mysqli_fetch_assoc($result)['count'];
                mysqli_query($conn, "DELETE FROM students");
                mysqli_query($conn, "DELETE FROM student_academic_history");
                mysqli_query($conn, "DELETE FROM attendance");
                $deleted_counts['Students'] = $count;
                break;

            case 'classes':
                // Delete classes and subjects
                $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM classes");
                $count_classes = mysqli_fetch_assoc($result)['count'];
                $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM subjects");
                $count_subjects = mysqli_fetch_assoc($result)['count'];
                mysqli_query($conn, "DELETE FROM class_subjects");
                mysqli_query($conn, "DELETE FROM classes");
                mysqli_query($conn, "DELETE FROM subjects");
                $deleted_counts['Classes'] = $count_classes;
                $deleted_counts['Subjects'] = $count_subjects;
                break;

            case 'exams':
                // Delete exams and results
                $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM exams");
                $count = mysqli_fetch_assoc($result)['count'];
                mysqli_query($conn, "DELETE FROM exam_results");
                mysqli_query($conn, "DELETE FROM exams");
                $deleted_counts['Exams'] = $count;
                break;

            case 'finance':
                // Delete finance data
                $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM student_fees");
                $count_invoices = mysqli_fetch_assoc($result)['count'];
                $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM payments");
                $count_payments = mysqli_fetch_assoc($result)['count'];
                mysqli_query($conn, "DELETE FROM payments");
                mysqli_query($conn, "DELETE FROM student_fees");
                mysqli_query($conn, "DELETE FROM fee_structures");
                $deleted_counts['Invoices'] = $count_invoices;
                $deleted_counts['Payments'] = $count_payments;
                break;

            case 'academic_years':
                // Delete academic years and terms
                $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM academic_years");
                $count_years = mysqli_fetch_assoc($result)['count'];
                $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM terms");
                $count_terms = mysqli_fetch_assoc($result)['count'];
                mysqli_query($conn, "DELETE FROM terms");
                mysqli_query($conn, "DELETE FROM academic_years");
                mysqli_query($conn, "DELETE FROM promotion_batches");
                $deleted_counts['Academic Years'] = $count_years;
                $deleted_counts['Terms'] = $count_terms;
                break;

            case 'staff':
                // Delete staff users (except super admin)
                $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role_id != 1");
                $count = mysqli_fetch_assoc($result)['count'];
                mysqli_query($conn, "DELETE FROM teachers");
                mysqli_query($conn, "DELETE FROM users WHERE role_id != 1"); // Keep super admin
                $deleted_counts['Staff Users'] = $count;
                break;
        }
    }

    // Reset auto-increment values for clean start
    if (in_array('students', $sections)) {
        mysqli_query($conn, "ALTER TABLE students AUTO_INCREMENT = 1");
        mysqli_query($conn, "ALTER TABLE attendance AUTO_INCREMENT = 1");
        mysqli_query($conn, "ALTER TABLE student_academic_history AUTO_INCREMENT = 1");
    }
    if (in_array('classes', $sections)) {
        mysqli_query($conn, "ALTER TABLE classes AUTO_INCREMENT = 1");
        mysqli_query($conn, "ALTER TABLE subjects AUTO_INCREMENT = 1");
        mysqli_query($conn, "ALTER TABLE class_subjects AUTO_INCREMENT = 1");
    }
    if (in_array('exams', $sections)) {
        mysqli_query($conn, "ALTER TABLE exams AUTO_INCREMENT = 1");
        mysqli_query($conn, "ALTER TABLE exam_results AUTO_INCREMENT = 1");
    }
    if (in_array('finance', $sections)) {
        mysqli_query($conn, "ALTER TABLE student_fees AUTO_INCREMENT = 1");
        mysqli_query($conn, "ALTER TABLE payments AUTO_INCREMENT = 1");
        mysqli_query($conn, "ALTER TABLE fee_structures AUTO_INCREMENT = 1");
    }
    if (in_array('academic_years', $sections)) {
        mysqli_query($conn, "ALTER TABLE academic_years AUTO_INCREMENT = 1");
        mysqli_query($conn, "ALTER TABLE terms AUTO_INCREMENT = 1");
        mysqli_query($conn, "ALTER TABLE promotion_batches AUTO_INCREMENT = 1");
    }
    if (in_array('staff', $sections)) {
        mysqli_query($conn, "ALTER TABLE teachers AUTO_INCREMENT = 1");
    }

    mysqli_commit($conn);

    // Build success message
    $message_parts = [];
    foreach ($deleted_counts as $type => $count) {
        $message_parts[] = "$count $type";
    }
    $message = "Deleted: " . implode(", ", $message_parts);

    echo json_encode([
        'success' => true,
        'message' => $message,
        'deleted_counts' => $deleted_counts
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>