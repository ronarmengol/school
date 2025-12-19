<?php
require_once '../../includes/auth_functions.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

check_auth();
check_role(['super_admin', 'admin']);

if (!isset($_GET['code'])) {
  echo json_encode(['success' => false, 'message' => 'Asset code is required']);
  exit();
}

$asset_code = trim($_GET['code']);

try {
  $query = "SELECT asset_id FROM assets WHERE asset_code = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("s", $asset_code);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    echo json_encode([
      'success' => true,
      'exists' => true,
      'message' => 'Asset code already exists'
    ]);
  } else {
    echo json_encode([
      'success' => true,
      'exists' => false,
      'message' => 'Asset code is available'
    ]);
  }

  $stmt->close();
} catch (Exception $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Error: ' . $e->getMessage()
  ]);
}

$conn->close();
?>