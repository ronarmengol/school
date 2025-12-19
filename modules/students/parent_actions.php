<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$student_id = $_POST['student_id'] ?? 0;

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

// Check if student exists
$sql = "SELECT * FROM students WHERE student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

if ($action == 'create_parent') {
    // Basic validation
    if ($student['parent_id']) {
        echo json_encode(['success' => false, 'message' => 'Student already has a parent account linked']);
        exit;
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);

    if (empty($username) || empty($password) || empty($full_name)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Check if username exists
    $check_sql = "SELECT user_id FROM users WHERE username = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $username);
    mysqli_stmt_execute($check_stmt);
    if (mysqli_num_rows(mysqli_stmt_get_result($check_stmt)) > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit;
    }

    // Create User
    // Get parent role id
    $role_res = mysqli_query($conn, "SELECT role_id FROM roles WHERE role_name = 'parent'");
    $role_row = mysqli_fetch_assoc($role_res);
    $role_id = $role_row['role_id'];

    mysqli_begin_transaction($conn);

    try {
        $insert_sql = "INSERT INTO users (username, password_hash, role_id, full_name, is_active) VALUES (?, ?, ?, ?, 1)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "ssis", $username, $password, $role_id, $full_name);
        
        if (!mysqli_stmt_execute($insert_stmt)) {
            throw new Exception("Failed to create user: " . mysqli_stmt_error($insert_stmt));
        }
        
        $new_user_id = mysqli_insert_id($conn);

        // Update Student
        $update_sql = "UPDATE students SET parent_id = ? WHERE student_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ii", $new_user_id, $student_id);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Failed to link student to parent: " . mysqli_stmt_error($update_stmt));
        }

        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => 'Parent account created successfully']);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
