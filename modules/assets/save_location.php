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
  $location_name = trim($_POST['location_name'] ?? '');
  $location_type = trim($_POST['location_type'] ?? '');
  $description = trim($_POST['description'] ?? '');

  // Validate required fields
  if (empty($location_name)) {
    echo json_encode(['success' => false, 'message' => 'Location name is required']);
    exit();
  }


  // Check if location already exists
  $check_stmt = $conn->prepare("SELECT location_id FROM asset_locations WHERE location_name = ?");
  $check_stmt->bind_param("s", $location_name);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();

  if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'A location with this name already exists']);
    exit();
  }

  // Insert new location
  // Convert empty location_type to NULL
  $location_type_value = !empty($location_type) ? $location_type : null;

  $stmt = $conn->prepare("INSERT INTO asset_locations (location_name, location_type, description) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $location_name, $location_type_value, $description);


  if ($stmt->execute()) {
    echo json_encode([
      'success' => true,
      'message' => 'Location added successfully!',
      'location_id' => $conn->insert_id
    ]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed to save location: ' . $conn->error]);
  }

  $stmt->close();
  $check_stmt->close();

} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>