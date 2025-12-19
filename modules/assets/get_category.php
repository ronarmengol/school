<?php
require_once '../../includes/auth_functions.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

check_auth();
check_role(['super_admin', 'admin']);

if (!isset($_GET['id'])) {
  echo json_encode(['success' => false, 'message' => 'Category ID is required']);
  exit();
}

$category_id = intval($_GET['id']);

try {
  $query = "SELECT category_id, category_name, description, is_active FROM asset_categories WHERE category_id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("i", $category_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $category = $result->fetch_assoc();
    echo json_encode([
      'success' => true,
      'category' => $category
    ]);
  } else {
    echo json_encode([
      'success' => false,
      'message' => 'Category not found'
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