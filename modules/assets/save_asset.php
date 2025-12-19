<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
require_once '../../config/database.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: add.php");
  exit();
}


// Get form data
$asset_name = trim($_POST['name'] ?? '');
$asset_code = trim($_POST['code'] ?? '');
$category = trim($_POST['category'] ?? '');
$brand = trim($_POST['brand'] ?? '');
$description = trim($_POST['description'] ?? '');
$purchase_date = $_POST['purchase_date'] ?? null;
$purchase_price = $_POST['purchase_price'] ?? null;
$supplier = trim($_POST['supplier'] ?? '');
$warranty_expiry = $_POST['warranty_expiry'] ?? null;
$status = $_POST['status'] ?? 'Available';
$condition = $_POST['condition'] ?? 'Good';
$location_id = $_POST['location'] ?? null;
$sub_location = trim($_POST['sub_location'] ?? '');

// Validate required fields
if (empty($asset_name) || empty($asset_code)) {
  $_SESSION['toast_message'] = "Asset name and code are required.";
  $_SESSION['toast_type'] = "error";
  header("Location: add.php");
  exit();
}


// Get category_id from category name (for now, we'll need to handle this properly)
// First, let's check if category is an ID or a name
$category_id = null;
if (!empty($category)) {
  // Check if it's numeric (ID) or text (name)
  if (is_numeric($category)) {
    $category_id = intval($category);
  } else {
    // Try to find the category by name
    $cat_query = "SELECT category_id FROM asset_categories WHERE category_name = ? AND is_active = 1";
    $cat_stmt = $conn->prepare($cat_query);
    $cat_stmt->bind_param("s", $category);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    if ($cat_row = $cat_result->fetch_assoc()) {
      $category_id = $cat_row['category_id'];
    }
    $cat_stmt->close();
  }
}

// Convert empty strings to NULL for optional fields
$purchase_date = !empty($purchase_date) ? $purchase_date : null;
$purchase_price = !empty($purchase_price) ? floatval($purchase_price) : null;
$warranty_expiry = !empty($warranty_expiry) ? $warranty_expiry : null;
$location_id = !empty($location_id) ? intval($location_id) : null;
$supplier = !empty($supplier) ? $supplier : null;
$brand = !empty($brand) ? $brand : null;
$description = !empty($description) ? $description : null;
$sub_location = !empty($sub_location) ? $sub_location : null;

// Check if asset code already exists
$check_query = "SELECT asset_id FROM assets WHERE asset_code = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("s", $asset_code);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
  $_SESSION['toast_message'] = "Asset code '$asset_code' already exists. Please use a unique code.";
  $_SESSION['toast_type'] = "error";
  $check_stmt->close();
  header("Location: add.php");
  exit();
}

$check_stmt->close();

// Insert the asset
$insert_query = "
  INSERT INTO assets (
    asset_code,
    asset_name,
    category_id,
    location_id,
    sub_location,
    brand,
    description,
    purchase_date,
    purchase_price,
    supplier,
    warranty_expiry,
    status,
    `condition`,
    created_by,
    created_at
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
";

$stmt = $conn->prepare($insert_query);
$created_by = $_SESSION['user_id'];

$stmt->bind_param(
  "ssiissssdssssi",
  $asset_code,
  $asset_name,
  $category_id,
  $location_id,
  $sub_location,
  $brand,
  $description,
  $purchase_date,
  $purchase_price,
  $supplier,
  $warranty_expiry,
  $status,
  $condition,
  $created_by
);

if ($stmt->execute()) {
  $asset_id = $stmt->insert_id;

  // Log the activity
  $log_query = "
    INSERT INTO asset_activity_log (
      asset_id,
      action_type,
      description,
      performed_by,
      created_at
    ) VALUES (?, 'Registration', ?, ?, NOW())
  ";

  $log_stmt = $conn->prepare($log_query);
  $log_description = "Asset '$asset_name' (Code: $asset_code) registered in the system";
  $log_stmt->bind_param("isi", $asset_id, $log_description, $created_by);
  $log_stmt->execute();
  $log_stmt->close();

  $_SESSION['toast_message'] = "Asset '$asset_name' has been successfully added to the inventory.";
  $_SESSION['toast_type'] = "success";

  $stmt->close();
  $conn->close();

  // Redirect to the asset list or view page
  if (isset($_POST['add_another']) && $_POST['add_another'] === '1') {
    header("Location: add.php");
  } else {
    header("Location: list.php");
  }
  exit();
} else {
  $_SESSION['toast_message'] = "Failed to save asset: " . $conn->error;
  $_SESSION['toast_type'] = "error";

  $stmt->close();
  $conn->close();

  header("Location: add.php");
  exit();
}

