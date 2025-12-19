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
  $query = "
    SELECT 
      asset_id,
      asset_code,
      asset_name,
      category_id,
      location_id,
      status,
      assigned_to,
      purchase_price,
      purchase_date,
      warranty_expiry,
      description,
      brand,
      supplier,
      `condition`,
      sub_location
    FROM assets
    WHERE asset_code = ?
  ";

  $stmt = $conn->prepare($query);
  $stmt->bind_param("s", $asset_code);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $asset = $result->fetch_assoc();
    echo json_encode([
      'success' => true,
      'asset' => $asset
    ]);
  } else {
    echo json_encode([
      'success' => false,
      'message' => 'Asset not found'
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