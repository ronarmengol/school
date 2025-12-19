<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

check_auth();
check_role(['super_admin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Invalid request method']);
  exit();
}

$asset_code = trim($_POST['asset_code'] ?? '');
$removal_reason = trim($_POST['removal_reason'] ?? '');

if (empty($asset_code) || empty($removal_reason)) {
  echo json_encode(['success' => false, 'message' => 'Asset code and removal reason are required']);
  exit();
}

try {
  // Update asset status and reason
  $query = "UPDATE assets SET status = 'Removed', removal_reason = ?, removed_at = NOW() WHERE asset_code = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ss", $removal_reason, $asset_code);

  if ($stmt->execute()) {
    // Log activity
    $log_query = "
      INSERT INTO asset_activity_log (
        asset_id,
        action_type,
        description,
        performed_by,
        created_at
      ) SELECT asset_id, 'Status Change', ?, ?, NOW() 
      FROM assets WHERE asset_code = ?
    ";
    $log_stmt = $conn->prepare($log_query);
    $log_description = "Asset marked as Removed. Reason: " . $removal_reason;
    $user_id = $_SESSION['user_id'];
    $log_stmt->bind_param("sis", $log_description, $user_id, $asset_code);
    $log_stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Asset successfully marked as removed']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed to update asset status']);
  }
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>