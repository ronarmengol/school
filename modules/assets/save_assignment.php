<?php
require_once '../../includes/auth_functions.php';
require_once '../../config/database.php';
check_auth();
check_role(['super_admin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: assignment.php");
  exit();
}

$asset_id = intval($_POST['asset_id'] ?? 0);
$assigned_to_user_id = intval($_POST['assigned_to_user_id'] ?? 0);
$assignment_date = $_POST['assignment_date'] ?? '';
$notes = $_POST['notes'] ?? '';
$assigned_by = $_SESSION['user_id'];

// Validation
if ($asset_id <= 0 || $assigned_to_user_id <= 0 || empty($assignment_date)) {
  $_SESSION['toast_message'] = 'Please fill in all required fields.';
  $_SESSION['toast_type'] = 'error';
  header("Location: create_assignment.php");
  exit();
}

// Get user details
$user_query = "SELECT u.full_name, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $assigned_to_user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
  $_SESSION['toast_message'] = 'User not found.';
  $_SESSION['toast_type'] = 'error';
  header("Location: create_assignment.php");
  exit();
}

$user = $user_result->fetch_assoc();
$assigned_to_name = $user['full_name'];
$assigned_to_role = $user['role_name'] ?? 'Staff';

// Check if asset exists and is not removed
$check_query = "SELECT asset_id, asset_name, status FROM assets WHERE asset_id = ? AND status != 'Removed'";
$stmt2 = $conn->prepare($check_query);
$stmt2->bind_param("i", $asset_id);
$stmt2->execute();
$result = $stmt2->get_result();

if ($result->num_rows === 0) {
  $_SESSION['toast_message'] = 'Asset not found or has been removed.';
  $_SESSION['toast_type'] = 'error';
  header("Location: create_assignment.php");
  exit();
}

// Check if asset is already assigned
$check_assignment = "SELECT assignment_id FROM asset_assignments WHERE asset_id = ? AND status = 'Active'";
$stmt3 = $conn->prepare($check_assignment);
$stmt3->bind_param("i", $asset_id);
$stmt3->execute();
$assignment_result = $stmt3->get_result();

if ($assignment_result->num_rows > 0) {
  $_SESSION['toast_message'] = 'This asset is already assigned. Please transfer or recover it first.';
  $_SESSION['toast_type'] = 'error';
  header("Location: create_assignment.php");
  exit();
}

// Create assignment
$insert_query = "INSERT INTO asset_assignments 
                (asset_id, assigned_to_type, assigned_to_name, assigned_to_role, assignment_date, status, notes, assigned_by, created_at) 
                VALUES (?, 'Staff', ?, ?, ?, 'Active', ?, ?, NOW())";

$stmt4 = $conn->prepare($insert_query);
$stmt4->bind_param("issssi", $asset_id, $assigned_to_name, $assigned_to_role, $assignment_date, $notes, $assigned_by);

if ($stmt4->execute()) {
  // Update asset status to "In Use"
  $update_asset = "UPDATE assets SET status = 'In Use', assigned_to = ? WHERE asset_id = ?";
  $stmt5 = $conn->prepare($update_asset);
  $stmt5->bind_param("si", $assigned_to_name, $asset_id);
  $stmt5->execute();

  // Log activity
  $log_query = "INSERT INTO asset_activity_log (asset_id, action_type, description, performed_by, created_at) 
                  VALUES (?, 'Assignment', ?, ?, NOW())";
  $description = "Asset assigned to " . $assigned_to_name;
  $stmt6 = $conn->prepare($log_query);
  $stmt6->bind_param("isi", $asset_id, $description, $assigned_by);
  $stmt6->execute();

  $_SESSION['toast_message'] = 'Asset assigned successfully!';
  $_SESSION['toast_type'] = 'success';
  header("Location: assignment.php");
} else {
  $_SESSION['toast_message'] = 'Failed to create assignment: ' . $conn->error;
  $_SESSION['toast_type'] = 'error';
  header("Location: create_assignment.php");
}

$conn->close();
