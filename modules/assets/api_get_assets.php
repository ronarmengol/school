<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

header('Content-Type: application/json');

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, trim($_GET['status'])) : '';

// Build the base query
$sql = "
    SELECT 
        a.asset_id,
        a.asset_code,
        a.asset_name,
        a.brand,
        a.description,
        a.purchase_date,
        a.purchase_price,
        a.supplier,
        a.warranty_expiry,
        a.status,
        a.condition,
        a.assigned_to,
        a.sub_location,
        ac.category_name,
        al.location_name,
        al.location_type
    FROM assets a
    LEFT JOIN asset_categories ac ON a.category_id = ac.category_id
    LEFT JOIN asset_locations al ON a.location_id = al.location_id
    WHERE a.status != 'Removed'
";

// Add category filter if specified
if ($category_id > 0) {
  $sql .= " AND a.category_id = " . intval($category_id);
}

// Add status filter if specified
if (!empty($status)) {
  $sql .= " AND a.status = '" . $status . "'";
}

$sql .= " ORDER BY a.asset_code ASC";

// Execute query
$result = mysqli_query($conn, $sql);

if ($result) {
  $assets = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $assets[] = $row;
  }
  mysqli_free_result($result);

  echo json_encode([
    'success' => true,
    'assets' => $assets,
    'count' => count($assets)
  ]);
} else {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => 'Failed to fetch assets: ' . mysqli_error($conn)
  ]);
}
