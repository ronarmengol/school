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
  $category_id = intval($_POST['category_id'] ?? 0);
  $category_name = trim($_POST['category_name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $is_active = isset($_POST['is_active']) ? 1 : 0;

  if ($category_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
    exit();
  }

  if (empty($category_name)) {
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit();
  }

  // Check for duplicate name excluding current category
  $check_stmt = $conn->prepare("SELECT category_id FROM asset_categories WHERE category_name = ? AND category_id != ?");
  $check_stmt->bind_param("si", $category_name, $category_id);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();

  if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Another category with this name already exists']);
    exit();
  }

  $stmt = $conn->prepare("UPDATE asset_categories SET category_name = ?, description = ?, is_active = ? WHERE category_id = ?");
  $stmt->bind_param("ssii", $category_name, $description, $is_active, $category_id);

  if ($stmt->execute()) {
    echo json_encode([
      'success' => true,
      'message' => 'Category updated successfully!'
    ]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed to update category: ' . $conn->error]);
  }

  $stmt->close();
  $check_stmt->close();

} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>