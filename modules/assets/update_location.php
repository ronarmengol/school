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
  // Get and sanitize input
  $location_id = intval($_POST['location_id'] ?? 0);
  $location_name = trim($_POST['location_name'] ?? '');
  $location_type = trim($_POST['location_type'] ?? '');
  $description = trim($_POST['description'] ?? '');

  // Validate required fields
  if (empty($location_id)) {
    echo json_encode(['success' => false, 'message' => 'Location ID is required']);
    exit();
  }

  if (empty($location_name)) {
    echo json_encode(['success' => false, 'message' => 'Location name is required']);
    exit();
  }

  // Check if location name already exists (excluding current location)
  $check_stmt = $conn->prepare("SELECT location_id FROM asset_locations WHERE location_name = ? AND location_id != ?");
  $check_stmt->bind_param("si", $location_name, $location_id);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();

  if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'A location with this name already exists']);
    exit();
  }

  // Convert empty location_type to NULL
  $location_type_value = !empty($location_type) ? $location_type : null;
  $description_value = !empty($description) ? $description : null;

  // Update location
  $stmt = $conn->prepare("UPDATE asset_locations SET location_name = ?, location_type = ?, description = ? WHERE location_id = ?");
  $stmt->bind_param("sssi", $location_name, $location_type_value, $description_value, $location_id);

  if ($stmt->execute()) {
    if ($stmt->affected_rows > 0 || $stmt->errno === 0) {
      echo json_encode([
        'success' => true,
        'message' => 'Location updated successfully!'
      ]);
    } else {
      echo json_encode([
        'success' => false,
        'message' => 'No changes were made or location not found'
      ]);
    }
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed to update location: ' . $conn->error]);
  }

  $stmt->close();
  $check_stmt->close();

} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>