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
  $category_name = trim($_POST['category_name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $is_active = isset($_POST['is_active']) ? 1 : 0;

  if (empty($category_name)) {
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit();
  }

  // Check if category already exists
  $check_stmt = $conn->prepare("SELECT category_id FROM asset_categories WHERE category_name = ?");
  $check_stmt->bind_param("s", $category_name);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();

  if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'A category with this name already exists']);
    exit();
  }

  $stmt = $conn->prepare("INSERT INTO asset_categories (category_name, description, is_active) VALUES (?, ?, ?)");
  $stmt->bind_param("ssi", $category_name, $description, $is_active);

  if ($stmt->execute()) {
    echo json_encode([
      'success' => true,
      'message' => 'Category added successfully!',
      'category_id' => $conn->insert_id
    ]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed to save category: ' . $conn->error]);
  }

  $stmt->close();
  $check_stmt->close();

} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>