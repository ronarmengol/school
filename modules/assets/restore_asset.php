<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

check_auth();
check_role(['super_admin', 'admin']);

$data = json_decode(file_get_contents('php://input'), true);
$asset_code = trim($data['asset_code'] ?? '');

if (empty($asset_code)) {
  echo json_encode(['success' => false, 'message' => 'Asset code is required']);
  exit();
}

try {
  $query = "UPDATE assets SET status = 'Available', removal_reason = NULL, removed_at = NULL WHERE asset_code = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("s", $asset_code);

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
    $log_description = "Asset restored to archive from Removed status.";
    $user_id = $_SESSION['user_id'];
    $log_stmt->bind_param("sis", $log_description, $user_id, $asset_code);
    $log_stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Asset successfully restored']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed to restore asset']);
  }
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>