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

$data = json_decode(file_get_contents('php://input'), true);
$category_id = intval($data['category_id'] ?? 0);

if ($category_id <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
  exit();
}

try {
  // Check if category has assets
  $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM assets WHERE category_id = ?");
  $check_stmt->bind_param("i", $category_id);
  $check_stmt->execute();
  $result = $check_stmt->get_result();
  $row = $result->fetch_assoc();

  if ($row['count'] > 0) {
    echo json_encode([
      'success' => false,
      'message' => 'Cannot delete category because it has ' . $row['count'] . ' assets assigned to it. Please reassign or delete the assets first.'
    ]);
    exit();
  }

  $stmt = $conn->prepare("DELETE FROM asset_categories WHERE category_id = ?");
  $stmt->bind_param("i", $category_id);

  if ($stmt->execute()) {
    echo json_encode([
      'success' => true,
      'message' => 'Category deleted successfully!'
    ]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete category: ' . $conn->error]);
  }

  $stmt->close();
  $check_stmt->close();

} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>