<?php
require_once '../../includes/auth_functions.php';
check_auth();

$page_title = "Dashboard";
include '../../includes/header.php';

$role = $_SESSION['role'] ?? '';

// Load specific dashboard based on role
if ($role == 'super_admin' || $role == 'admin') {
    include 'admin_dashboard.php';
} elseif ($role == 'teacher') {
    include 'teacher_dashboard.php';
} elseif ($role == 'parent') {
    // Redirect to separate Parent Portal module
    header("Location: ../parents/index.php");
    exit();
} elseif ($role == 'student') {
    include 'student_dashboard.php';
} elseif ($role == 'accountant') {
    // Re-use admin dashboard for now, or create specific
    include 'admin_dashboard.php';
} else {
    echo "<p>Welcome to " . APP_NAME . ". Your role is: $role</p>";
}

include '../../includes/footer.php';
?>