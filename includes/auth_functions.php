<?php
// includes/auth_functions.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/load_settings.php';

function check_auth()
{
    global $conn;
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "auth/login.php");
        exit();
    }

    // 5 Minute Session Timeout logic
    $timeout_duration = 300; // 5 minutes in seconds
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();

        // Handle AJAX requests differently
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json', true, 401);
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.', 'redirect' => BASE_URL . 'auth/login.php?error=timeout']);
            exit();
        }

        header("Location: " . BASE_URL . "auth/login.php?error=timeout");
        exit();
    }
    $_SESSION['last_activity'] = time();

    // After a database reset, the session might exist but the user might be gone
    // Or if the database was rebuilt, the user_id might be invalid
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT user_id FROM users WHERE user_id = ? LIMIT 1";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) == 0) {
            // User no longer exists, clear session and redirect
            session_destroy();
            header("Location: " . BASE_URL . "auth/login.php?error=session_expired");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

function check_role($allowed_roles)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        // Redirect to unauthorized page or dashboard
        header("Location: " . BASE_URL . "modules/dashboard/index.php?error=unauthorized");
        exit();
    }
}

function attempt_login($conn, $username, $password)
{
    $sql = "SELECT u.*, r.role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.username = ? LIMIT 1";

    $outcome = "Database error.";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // Verify password (support both hashed and plain text)
            $password_valid = false;
            if (password_verify($password, $row['password_hash'])) {
                $password_valid = true;
            } elseif ($password == $row['password_hash']) {
                $password_valid = true;
            }

            if ($password_valid) {
                if ($row['is_active'] != 1) {
                    $outcome = "Account is inactive.";
                } else {
                    // Set Session
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['role'] = $row['role_name'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['full_name'] = $row['full_name'];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    // Update Last Login
                    $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
                    if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                        mysqli_stmt_bind_param($update_stmt, "i", $row['user_id']);
                        mysqli_stmt_execute($update_stmt);
                        mysqli_stmt_close($update_stmt);
                    }

                    $outcome = true;
                }
            } else {
                $outcome = "Invalid password.";
            }
        } else {
            // User not found in users table. 
            // Check if it's a student admission number for JIT Parent creation
            // Policy: Admission Number = Username, Password = '123'

            $stud_sql = "SELECT student_id, first_name, last_name, parent_id FROM students WHERE admission_number = ? LIMIT 1";
            if ($stud_stmt = mysqli_prepare($conn, $stud_sql)) {
                mysqli_stmt_bind_param($stud_stmt, "s", $username);
                mysqli_stmt_execute($stud_stmt);
                $stud_res = mysqli_stmt_get_result($stud_stmt);

                if ($stud_row = mysqli_fetch_assoc($stud_res)) {
                    // Student found. Check if parent already linked (account exists but username mismatch?)
                    // If parent_id is set, it means an account exists. But we are here because 'username' fetch failed.
                    // So either they changed username or it's a fresh case.
                    // For safety, only auto-create if parent_id is NULL.

                    if (is_null($stud_row['parent_id'])) {
                        if ($password === '123') {
                            // JIT Creation
                            // 1. Get 'parent' role ID
                            $role_res = mysqli_query($conn, "SELECT role_id FROM roles WHERE role_name = 'parent' LIMIT 1");
                            if ($role_row = mysqli_fetch_assoc($role_res)) {
                                $role_id = $role_row['role_id'];
                                $full_name = "Parent of " . $stud_row['first_name'];

                                // 2. Create User
                                $ins_sql = "INSERT INTO users (username, password_hash, role_id, full_name, is_active) VALUES (?, ?, ?, ?, 1)";
                                if ($ins_stmt = mysqli_prepare($conn, $ins_sql)) {
                                    // Use '123' as hash (plain text based on current dev config)
                                    mysqli_stmt_bind_param($ins_stmt, "ssis", $username, $password, $role_id, $full_name);
                                    if (mysqli_stmt_execute($ins_stmt)) {
                                        $new_user_id = mysqli_insert_id($conn);

                                        // 3. Link Student
                                        $upd_sql = "UPDATE students SET parent_id = ? WHERE student_id = ?";
                                        $upd_stmt = mysqli_prepare($conn, $upd_sql);
                                        mysqli_stmt_bind_param($upd_stmt, "ii", $new_user_id, $stud_row['student_id']);
                                        mysqli_stmt_execute($upd_stmt);

                                        // 4. Set Session (Log them in immediately)
                                        $_SESSION['user_id'] = $new_user_id;
                                        $_SESSION['role'] = 'parent';
                                        $_SESSION['username'] = $username;
                                        $_SESSION['full_name'] = $full_name;
                                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                                        $outcome = true;
                                    } else {
                                        $outcome = "Failed to create parent account.";
                                    }
                                }
                            } else {
                                $outcome = "System error: Parent role not defined.";
                            }
                        } else {
                            $outcome = "Invalid password."; // Found student, but wrong default pass
                        }
                    } else {
                        // Parent ID exists, but username didn't match 'users' table lookup? 
                        // This implies the parent changed their username, or the linked user was deleted.
                        // We shouldn't auto-recreate to avoid orphans or security issues.
                        $outcome = "Account exists but username mismatch. Contact Admin.";
                    }
                } else {
                    $outcome = "User not found.";
                }
            } else {
                $outcome = "Database error.";
            }
        }
        mysqli_stmt_close($stmt);
    }
    return $outcome;
}

function get_role_dashboard_url($role)
{
    // Currently all go to the same dashboard module, which adapts content
    return BASE_URL . 'modules/dashboard/index.php';
}
?>