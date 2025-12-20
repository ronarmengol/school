<?php
require_once '../../includes/auth_functions.php';
require_once '../../config/database.php';
check_auth();
check_role(['super_admin', 'admin']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
  exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$maintenance_id = intval($data['maintenance_id'] ?? 0);

if ($maintenance_id <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid maintenance ID.']);
  exit();
}

// Update maintenance record
$query = "UPDATE asset_maintenance SET status = 'Completed', completed_date = NOW() WHERE maintenance_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $maintenance_id);

if ($stmt->execute()) {
  // Optionally log this activity
  $asset_query = "SELECT asset_id FROM asset_maintenance WHERE maintenance_id = ?";
  $asset_stmt = $conn->prepare($asset_query);
  $asset_stmt->bind_param("i", $maintenance_id);
  $asset_stmt->execute();
  $asset_result = $asset_stmt->get_result();
  if ($asset_row = $asset_result->fetch_assoc()) {
    $asset_id = $asset_row['asset_id'];
    $log_query = "INSERT INTO asset_activity_log (asset_id, action_type, description, performed_by, created_at) VALUES (?, 'Maintenance', 'Maintenance task completed', ?, NOW())";
    $log_stmt = $conn->prepare($log_query);
    $user_id = $_SESSION['user_id'];
    $log_stmt->bind_param("ii", $asset_id, $user_id);
    $log_stmt->execute();
  }

  echo json_encode(['success' => true, 'message' => 'Maintenance marked as completed.']);
} else {
  echo json_encode(['success' => false, 'message' => 'Failed to update record: ' . $conn->error]);
}

$stmt->close();
$conn->close();
