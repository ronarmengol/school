<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
require_once '../../config/database.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: maintenance.php");
  exit();
}

// Get form data
$asset_id = intval($_POST['asset_id'] ?? 0);
$task_type = $_POST['task_type'] ?? 'Maintenance';
$task_description = trim($_POST['task_description'] ?? '');
$scheduled_date = $_POST['scheduled_date'] ?? null;
$due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
$priority = $_POST['priority'] ?? 'Medium';
$status = $_POST['status'] ?? 'Scheduled';
$assigned_to_user_id = !empty($_POST['performed_by_user_id']) ? intval($_POST['performed_by_user_id']) : null;
$cost = !empty($_POST['cost']) ? floatval($_POST['cost']) : null;
$notes = trim($_POST['notes'] ?? '');
$repeat_schedule = isset($_POST['repeat_schedule']) ? 1 : 0;
$frequency = $repeat_schedule ? ($_POST['frequency'] ?? 'Monthly') : null;
$created_by = $_SESSION['user_id'];

// Validate
if ($asset_id <= 0 || empty($scheduled_date)) {
  $_SESSION['toast_message'] = "Asset and scheduled date are required.";
  $_SESSION['toast_type'] = "error";
  header("Location: schedule_maintenance.php");
  exit();
}

// Insert into database
$query = "
  INSERT INTO asset_maintenance (
    asset_id,
    task_type,
    task_description,
    scheduled_date,
    due_date,
    priority,
    status,
    assigned_to_user_id,
    cost,
    notes,
    repeat_schedule,
    frequency,
    created_by,
    created_at
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
";

$stmt = $conn->prepare($query);
$stmt->bind_param(
  "issssssidsisi",
  $asset_id,
  $task_type,
  $task_description,
  $scheduled_date,
  $due_date,
  $priority,
  $status,
  $assigned_to_user_id,
  $cost,
  $notes,
  $repeat_schedule,
  $frequency,
  $created_by
);

if ($stmt->execute()) {
  $maintenance_id = $stmt->insert_id;

  // Log activity
  $log_query = "
    INSERT INTO asset_activity_log (
      asset_id,
      action_type,
      description,
      performed_by,
      created_at
    ) VALUES (?, 'Maintenance', ?, ?, NOW())
  ";
  $log_stmt = $conn->prepare($log_query);
  $log_description = $task_type . " task scheduled: " . ($task_description ?: 'No description') . " (Scheduled for " . $scheduled_date . ")";
  $log_stmt->bind_param("isi", $asset_id, $log_description, $created_by);
  $log_stmt->execute();
  $log_stmt->close();

  $_SESSION['toast_message'] = "Maintenance task has been successfully scheduled.";
  $_SESSION['toast_type'] = "success";

  if (isset($_POST['add_another']) && $_POST['add_another'] === '1') {
    header("Location: schedule_maintenance.php");
  } else {
    header("Location: maintenance.php");
  }
} else {
  $_SESSION['toast_message'] = "Failed to schedule maintenance: " . $conn->error;
  $_SESSION['toast_type'] = "error";
  header("Location: schedule_maintenance.php");
}

$stmt->close();
$conn->close();
exit();
?>