<?php
require_once '../../includes/auth_functions.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

check_auth();
check_role(['super_admin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Invalid request method']);
  exit();
}

try {
  // Get form data
  $asset_id = intval($_POST['asset_id'] ?? 0);
  $asset_name = trim($_POST['asset_name'] ?? '');
  $asset_code = trim($_POST['asset_code'] ?? '');
  $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
  $location_id = !empty($_POST['location_id']) ? intval($_POST['location_id']) : null;
  $status = trim($_POST['status'] ?? 'Available');
  $assigned_to = !empty($_POST['assigned_to']) ? trim($_POST['assigned_to']) : null;
  $purchase_price = !empty($_POST['purchase_price']) ? floatval($_POST['purchase_price']) : null;
  $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
  $warranty_expiry = !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null;
  $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
  $brand = !empty($_POST['brand']) ? trim($_POST['brand']) : null;
  $supplier = !empty($_POST['supplier']) ? trim($_POST['supplier']) : null;
  $condition = !empty($_POST['condition']) ? trim($_POST['condition']) : 'Good';
  $sub_location = !empty($_POST['sub_location']) ? trim($_POST['sub_location']) : null;

  // Validate required fields
  if ($asset_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Asset ID is required']);
    exit();
  }

  if (empty($asset_name)) {
    echo json_encode(['success' => false, 'message' => 'Asset name is required']);
    exit();
  }

  // Update asset
  $query = "
    UPDATE assets SET
      asset_name = ?,
      category_id = ?,
      location_id = ?,
      status = ?,
      assigned_to = ?,
      purchase_price = ?,
      purchase_date = ?,
      warranty_expiry = ?,
      description = ?,
      brand = ?,
      supplier = ?,
      `condition` = ?,
      sub_location = ?
    WHERE asset_id = ?
  ";

  $stmt = $conn->prepare($query);
  $stmt->bind_param(
    "siissdsssssssi",
    $asset_name,
    $category_id,
    $location_id,
    $status,
    $assigned_to,
    $purchase_price,
    $purchase_date,
    $warranty_expiry,
    $description,
    $brand,
    $supplier,
    $condition,
    $sub_location,
    $asset_id
  );

  if ($stmt->execute()) {
    // Log the activity
    $user_id = $_SESSION['user_id'] ?? 0;

    // Check if asset_activity_log table exists and log
    $log_query = "
      INSERT INTO asset_activity_log (
        asset_id,
        action_type,
        description,
        performed_by,
        created_at
      ) VALUES (?, ?, ?, ?, NOW())
    ";

    if ($log_stmt = $conn->prepare($log_query)) {
      $action_type = "Update";
      $log_description = "Asset '$asset_name' (Code: $asset_code) details updated";
      $log_stmt->bind_param("issi", $asset_id, $action_type, $log_description, $user_id);
      $log_stmt->execute();
      $log_stmt->close();
    }

    echo json_encode([
      'success' => true,
      'message' => 'Asset updated successfully!'
    ]);
  } else {
    echo json_encode([
      'success' => false,
      'message' => 'Failed to update asset: ' . $stmt->error
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