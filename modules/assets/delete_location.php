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
  // Get JSON input
  $input = json_decode(file_get_contents('php://input'), true);

  // Debug logging
  error_log('Delete location input: ' . print_r($input, true));

  $location_id = intval($input['location_id'] ?? 0);

  error_log('Location ID after intval: ' . $location_id);

  if ($location_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Location ID is required', 'debug' => ['received_input' => $input, 'parsed_id' => $location_id]]);
    exit();
  }


  // Check if location has assets assigned
  $check_stmt = $conn->prepare("SELECT COUNT(*) as asset_count FROM assets WHERE location_id = ?");
  $check_stmt->bind_param("i", $location_id);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();
  $check_row = $check_result->fetch_assoc();

  if ($check_row['asset_count'] > 0) {
    echo json_encode([
      'success' => false,
      'message' => 'Cannot delete location. ' . $check_row['asset_count'] . ' asset(s) are assigned to this location.'
    ]);
    exit();
  }

  // Delete the location
  $stmt = $conn->prepare("DELETE FROM asset_locations WHERE location_id = ?");
  $stmt->bind_param("i", $location_id);

  if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
      echo json_encode([
        'success' => true,
        'message' => 'Location deleted successfully!'
      ]);
    } else {
      echo json_encode([
        'success' => false,
        'message' => 'Location not found or already deleted'
      ]);
    }
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete location: ' . $conn->error]);
  }

  $stmt->close();
  $check_stmt->close();

} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>