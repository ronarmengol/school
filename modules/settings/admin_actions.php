<?php
require_once '../../includes/auth_functions.php';

check_auth();
check_role(['super_admin']);

$action = $_POST['action'] ?? '';
$success = '';
$error = '';

// Helper function to check if request is AJAX
function isAjax()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Helper function to send response (JSON for AJAX, redirect for normal)
function sendResponse($success, $message, $tab = 'admin')
{
    if (isAjax()) {
        // Clear main output buffer
        while (ob_get_level())
            ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
        exit();
    } else {
        $param = $success ? 'success' : 'error';
        header("Location: index.php?tab={$tab}&{$param}=" . urlencode($message));
        exit();
    }
}

try {
    // Change Password
    if ($action == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $user_id = $_SESSION['user_id'];

        // Verify new passwords match
        if ($new_password !== $confirm_password) {
            sendResponse(false, "New passwords do not match");
        }

        // Get current password hash
        $sql = "SELECT password_hash FROM users WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        // Verify current password (support both hashed and plain text)
        $password_valid = false;
        if ($user) {
            if (password_verify($current_password, $user['password_hash'])) {
                $password_valid = true;
            } elseif ($user['password_hash'] === $current_password) {
                $password_valid = true;
            }
        }

        if (!$password_valid) {
            sendResponse(false, "Current password is incorrect");
        }

        // Update password (store as plain text for development)
        $update_sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $new_password, $user_id);

        if (mysqli_stmt_execute($update_stmt)) {
            sendResponse(true, "Password updated successfully");
        } else {
            sendResponse(false, "Failed to update password: " . mysqli_stmt_error($update_stmt));
        }
    }

    // Change Username
    if ($action == 'change_username') {
        $new_username = trim($_POST['new_username']);
        $password = $_POST['password'];
        $user_id = $_SESSION['user_id'];

        // Check if username already exists
        $check_sql = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "si", $new_username, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            sendResponse(false, "Username already exists");
        }

        // Verify password
        $sql = "SELECT password_hash FROM users WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        $password_valid = false;
        if ($user) {
            if (password_verify($password, $user['password_hash'])) {
                $password_valid = true;
            } elseif ($user['password_hash'] === $password) {
                $password_valid = true;
            }
        }

        if (!$password_valid) {
            sendResponse(false, "Incorrect password");
        }

        // Update username
        $update_sql = "UPDATE users SET username = ? WHERE user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $new_username, $user_id);

        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['username'] = $new_username;
            sendResponse(true, "Username updated successfully");
        } else {
            sendResponse(false, "Failed to update username: " . mysqli_stmt_error($update_stmt));
        }
    }

    // Add Admin
    if ($action == 'add_admin') {
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = $_POST['password'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Handle empty optional fields
        $email = empty($email) ? null : $email;
        $full_name = empty($full_name) ? null : $full_name;

        // Get role_id
        $role_sql = "SELECT role_id FROM roles WHERE role_name = ?";
        $role_stmt = mysqli_prepare($conn, $role_sql);
        mysqli_stmt_bind_param($role_stmt, "s", $role);
        mysqli_stmt_execute($role_stmt);
        $role_result = mysqli_stmt_get_result($role_stmt);
        $role_data = mysqli_fetch_assoc($role_result);
        $role_id = $role_data['role_id'];

        // Check if username exists
        $check_sql = "SELECT user_id FROM users WHERE username = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $username);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            sendResponse(false, "Username already exists");
        }

        // Prevent creating new super_admin accounts (only one allowed or handled differently)
        if ($role == 'super_admin' && $_SESSION['role'] !== 'super_admin') {
            sendResponse(false, "Cannot create additional Super Admin accounts");
        }

        // Insert new admin
        $insert_sql = "INSERT INTO users (username, password_hash, role_id, email, full_name, is_active) VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);

        mysqli_stmt_bind_param($insert_stmt, "ssissi", $username, $password, $role_id, $email, $full_name, $is_active);

        if (mysqli_stmt_execute($insert_stmt)) {
            sendResponse(true, "Administrator added successfully");
        } else {
            sendResponse(false, "Failed to add administrator: " . mysqli_stmt_error($insert_stmt));
        }
    }

    // Edit Admin
    if ($action == 'edit_admin') {
        $user_id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = $_POST['password'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Handle empty optional fields
        $email = empty($email) ? null : $email;
        $full_name = empty($full_name) ? null : $full_name;

        // Get role_id
        $role_sql = "SELECT role_id FROM roles WHERE role_name = ?";
        $role_stmt = mysqli_prepare($conn, $role_sql);
        mysqli_stmt_bind_param($role_stmt, "s", $role);
        mysqli_stmt_execute($role_stmt);
        $role_result = mysqli_stmt_get_result($role_stmt);
        $role_data = mysqli_fetch_assoc($role_result);
        $role_id = $role_data['role_id'];

        // Check if user is super_admin (can only be edited by themselves)
        $check_role_sql = "SELECT r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?";
        $check_role_stmt = mysqli_prepare($conn, $check_role_sql);
        mysqli_stmt_bind_param($check_role_stmt, "i", $user_id);
        mysqli_stmt_execute($check_role_stmt);
        $check_role_result = mysqli_stmt_get_result($check_role_stmt);
        $user_role_data = mysqli_fetch_assoc($check_role_result);

        // Super admin can only edit themselves, not other super admins
        if ($user_role_data['role_name'] == 'super_admin' && $user_id != $_SESSION['user_id']) {
            sendResponse(false, "Cannot modify other Super Admin accounts");
        }

        // Prevent changing role to super_admin unless they already are a super_admin
        if ($role == 'super_admin' && $user_role_data['role_name'] !== 'super_admin') {
            sendResponse(false, "Cannot change role to Super Admin");
        }

        // Check if username exists for another user
        $check_sql = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "si", $username, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            sendResponse(false, "Username already exists");
        }

        // Update admin
        if (!empty($password)) {
            $update_sql = "UPDATE users SET username = ?, password_hash = ?, role_id = ?, email = ?, full_name = ?, is_active = ? WHERE user_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "ssissii", $username, $password, $role_id, $email, $full_name, $is_active, $user_id);
        } else {
            $update_sql = "UPDATE users SET username = ?, role_id = ?, email = ?, full_name = ?, is_active = ? WHERE user_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "sissii", $username, $role_id, $email, $full_name, $is_active, $user_id);
        }

        if (mysqli_stmt_execute($update_stmt)) {
            sendResponse(true, "Administrator updated successfully");
        } else {
            sendResponse(false, "Failed to update administrator: " . mysqli_stmt_error($update_stmt));
        }
    }

    // Delete Admin
    if ($action == 'delete_admin') {
        $user_id = $_POST['user_id'];

        // Prevent deleting yourself
        if ($user_id == $_SESSION['user_id']) {
            sendResponse(false, "You cannot delete your own account");
        }

        // Prevent deleting super_admin
        $check_role_sql = "SELECT r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?";
        $check_role_stmt = mysqli_prepare($conn, $check_role_sql);
        mysqli_stmt_bind_param($check_role_stmt, "i", $user_id);
        mysqli_stmt_execute($check_role_stmt);
        $check_role_result = mysqli_stmt_get_result($check_role_stmt);
        $user_role_data = mysqli_fetch_assoc($check_role_result);

        if ($user_role_data['role_name'] == 'super_admin') {
            sendResponse(false, "Super Admin account cannot be deleted");
        }

        $delete_sql = "DELETE FROM users WHERE user_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $user_id);

        if (mysqli_stmt_execute($delete_stmt)) {
            sendResponse(true, "Administrator deleted successfully");
        } else {
            sendResponse(false, "Failed to delete administrator: " . mysqli_stmt_error($delete_stmt));
        }
    }
} catch (Exception $e) {
    // Catch database errors and return them
    sendResponse(false, "System Error: " . $e->getMessage());
}

// Default redirect (fallback)
header("Location: index.php?tab=admin");
exit();
?>